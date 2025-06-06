<?php

namespace Automattic\Jetpack\Publicize;

use Automattic\Jetpack\Connection\Tokens;
use Automattic\Jetpack\Constants;
use Automattic\Jetpack\Publicize\REST_API\Social_Image_Generator_Controller;
use Jetpack_Options;
use PHPUnit\Framework\TestCase;
use WorDBless\Options as WorDBless_Options;
use WorDBless\Users as WorDBless_Users;
use WP_REST_Request;
use WP_REST_Server;

/**
 * Unit tests for the Social_Image_Generator_Controller class.
 *
 * @package automattic/jetpack-publicize
 */
class Social_Image_Generator_Controller_Test extends TestCase {

	/**
	 * Admin user ID.
	 *
	 * @var int
	 */
	private $admin_id;

	/**
	 * REST Server object.
	 *
	 * @var WP_REST_Server
	 */
	private $server;

	/**
	 * Setting up the test.
	 */
	public function setUp(): void {
		parent::setUp();
		global $wp_rest_server;

		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;
		$this->admin_id = wp_insert_user(
			array(
				'user_login' => 'dummy_user',
				'user_pass'  => 'dummy_pass',
				'role'       => 'administrator',
			)
		);
		wp_set_current_user( 0 );

		// Mock site connection.
		( new Tokens() )->update_blog_token( 'new.blogtoken' );
		Jetpack_Options::update_option( 'id', get_current_blog_id() );
		Constants::set_constant( 'JETPACK__WPCOM_JSON_API_BASE', 'https://public-api.wordpress.com' );

		// Register REST routes.
		add_action( 'rest_api_init', array( new Social_Image_Generator_Controller(), 'register_routes' ) );

		do_action( 'rest_api_init' );
	}

	/**
	 * Returning the environment into its initial state.
	 */
	public function tearDown(): void {
		parent::tearDown();
		wp_set_current_user( 0 );

		unset( $_SERVER['REQUEST_METHOD'] );

		WorDBless_Options::init()->clear_options();
		WorDBless_Users::init()->clear_all_users();
	}

	/**
	 * Testing the `POST /wpcom/v2/publicize/social-image-generater/generate-token` endpoint without proper permissions.
	 */
	public function test_generate_preview_token_without_proper_permission() {
		$request = new WP_REST_Request( 'POST', '/wpcom/v2/publicize/social-image-generator/generate-token' );
		$request->set_body_params(
			array(
				'text' => 'Testing the token generation',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );
		$this->assertEquals( 'Sorry, you are not allowed to access Jetpack Social data on this site.', $response->get_data()['message'] );
	}

	/**
	 * Testing the `POST /wpcom/v2/publicize/social-image-generater/generate-token` endpoint without required parameter.
	 */
	public function test_generate_preview_token_without_required_parameters() {
		$request = new WP_REST_Request( 'POST', '/wpcom/v2/publicize/social-image-generator/generate-token' );
		wp_set_current_user( $this->admin_id );
		$user = wp_get_current_user();
		$user->add_cap( 'manage_options' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'rest_missing_callback_param', $response->as_error()->get_error_code() );
	}

	/**
	 * Testing the `POST /wpcom/v2/publicize/social-image-generater/generate-token` endpoint with the happy path.
	 */
	public function test_generate_preview_token() {
		$request = new WP_REST_Request( 'POST', '/wpcom/v2/publicize/social-image-generator/generate-token' );
		$request->set_body_params(
			array(
				'text' => 'Testing the token generation',
			)
		);
		wp_set_current_user( $this->admin_id );
		$user = wp_get_current_user();
		$user->add_cap( 'manage_options' );
		add_filter( 'pre_http_request', array( $this, 'mock_success_response' ) );
		$response = $this->server->dispatch( $request );
		remove_filter( 'pre_http_request', array( $this, 'mock_success_response' ) );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'dummy-token', $response->get_data() );
	}

	/**
	 * Mocks a successful response from WPCOM
	 */
	public function mock_success_response() {
		return array(
			'body'     => wp_json_encode( 'dummy-token' ),
			'response' => array(
				'code'    => 200,
				'message' => '',
			),
		);
	}
}
