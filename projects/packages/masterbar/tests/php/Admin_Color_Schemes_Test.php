<?php
/**
 * Tests for Admin_Color_Schemes class.
 *
 * @package automattic/jetpack-masterbar
 */

namespace Automattic\Jetpack\Masterbar;

use PHPUnit\Framework\Attributes\CoversClass;
use WorDBless\BaseTestCase;
use WP_Http;
use WP_REST_Request;
use WP_REST_Server;
use WP_REST_Users_Controller;
use WpOrg\Requests\Requests;

/**
 * Class Admin_Color_Schemes_Test.
 *
 * @covers Automattic\Jetpack\Masterbar\Admin_Color_Schemes
 */
#[CoversClass( Admin_Color_Schemes::class )]
class Admin_Color_Schemes_Test extends BaseTestCase {

	/**
	 * REST Server object.
	 *
	 * @var WP_REST_Server
	 */
	private $server;

	/**
	 * Mock user ID.
	 *
	 * @var int
	 */
	private static $user_id = 0;

	/**
	 * Set up each test.
	 */
	public function set_up() {
		global $wp_rest_server;

		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;

		static::$user_id = wp_insert_user(
			array(
				'user_login' => 'test_editor',
				'user_pass'  => '123',
				'role'       => 'editor',
			)
		);

		new Admin_Color_Schemes();

		do_action( 'rest_api_init' );
	}

	/**
	 * Returning the environment into its initial state.
	 */
	public function tear_down() {
		wp_dequeue_style( 'jetpack-core-color-schemes-overrides' );
		wp_dequeue_style( 'jetpack-core-color-schemes-overrides-sidebar-notice' );
	}

	/**
	 * Tests the schema response for OPTIONS requests.
	 */
	public function test_schema_request() {
		$request  = new WP_REST_Request( Requests::OPTIONS, '/wp/v2/users/' . static::$user_id );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$schema = ( new WP_REST_Users_Controller() )->get_public_item_schema();

		$this->assertEquals( $schema, $data['schema'] );
		$this->assertArrayHasKey( 'meta', $data['schema']['properties'] );
		$this->assertArrayHasKey( 'admin_color', $data['schema']['properties']['meta']['properties'] );
	}

	/**
	 * Tests retrieving the color scheme setting for a user.
	 */
	public function test_get_color_scheme() {
		wp_set_current_user( static::$user_id );

		$request  = new WP_REST_Request( Requests::GET, '/wp/v2/users/' . static::$user_id );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertArrayHasKey( 'meta', $data );
		$this->assertArrayHasKey( 'admin_color', $data['meta'] );
		$this->assertSame( 'fresh', $data['meta']['admin_color'] );
	}

	/**
	 * Tests updating the color scheme setting for a user.
	 */
	public function test_update_color_scheme() {
		wp_set_current_user( static::$user_id );

		// Editor can update their own meta value.
		$request = new WP_REST_Request( Requests::PUT, '/wp/v2/users/' . static::$user_id );
		$request->set_body_params(
			array(
				'meta' => array(
					'admin_color' => 'classic',
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertArrayHasKey( 'meta', $data );
		$this->assertArrayHasKey( 'admin_color', $data['meta'] );
		$this->assertSame( 'classic', $data['meta']['admin_color'] );
	}

	/**
	 * Tests updating the color scheme setting as editor for another user.
	 */
	public function test_update_color_scheme_will_fail_when_editor_updates_another_user() {
		wp_set_current_user( static::$user_id );

		$admin_user_id = wp_insert_user(
			array(
				'user_login' => 'test_admin',
				'user_pass'  => '123',
				'role'       => 'administrator',
			)
		);

		// Editor can't update someone else's meta value.
		$request = new WP_REST_Request( Requests::PUT, '/wp/v2/users/' . $admin_user_id );
		$request->set_body_params(
			array(
				'meta' => array(
					'admin_color' => 'classic',
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( WP_Http::FORBIDDEN, $response->get_status() );
		$this->assertSame( 'rest_cannot_edit', $data['code'] );
	}
}
