<?php
/**
 * Tests the Legacy Jetpack_XMLRPC_Server class.
 */

use Automattic\Jetpack\Connection\Tokens;
use Automattic\Jetpack\Constants;
use WorDBless\BaseTestCase;

/**
 * Class to test the legacy Jetpack_XMLRPC_Server class.
 */
class Jetpack_XMLRPC_Server_Test extends BaseTestCase {

	/**
	 * The test user ID
	 *
	 * @var integer
	 */
	protected $xmlrpc_admin = 0;

	/**
	 * Set up before each test
	 */
	protected function set_up() {
		$user_id = wp_insert_user(
			array(
				'user_login' => 'admin',
				'user_pass'  => 'pass',
				'user_email' => 'admin@admin.com',
				'role'       => 'administrator',
			)
		);
		( new Tokens() )->update_user_token( $user_id, sprintf( '%s.%s.%d', 'key', 'private', $user_id ), false );

		$this->xmlrpc_admin = $user_id;

		Constants::set_constant( 'JETPACK__API_BASE', 'https://jetpack.wordpress.com/jetpack.' );
	}

	/**
	 * Clean up the testing environment.
	 */
	public function tear_down() {
		Constants::clear_constants();
	}

	/**
	 * Test test_xmlrpc_get_user_by_id
	 */
	public function test_xmlrpc_get_user_by_id() {
		$user   = get_user_by( 'id', $this->xmlrpc_admin );
		$server = new Jetpack_XMLRPC_Server();

		$response = $server->get_user( $user->ID );
		$this->assertGetUserEqual( $user, $response );
		$this->assertEquals( 'key', $response['token_key'] );
	}

	/**
	 * Test test_xmlrpc_get_user_by_id
	 */
	public function test_xmlrpc_get_user_by_user_login() {
		$user   = get_user_by( 'id', $this->xmlrpc_admin );
		$server = new Jetpack_XMLRPC_Server();

		$response = $server->get_user( $user->user_login );
		$this->assertGetUserEqual( $user, $response );
		$this->assertEquals( 'key', $response['token_key'] );
	}

	/**
	 * Test test_xmlrpc_get_user_by_id
	 */
	public function test_xmlrpc_get_user_by_user_email() {
		$user   = get_user_by( 'id', $this->xmlrpc_admin );
		$server = new Jetpack_XMLRPC_Server();

		$response = $server->get_user( $user->user_email );
		$this->assertGetUserEqual( $user, $response );
		$this->assertEquals( 'key', $response['token_key'] );
	}

	/**
	 * Test test_xmlrpc_get_user_by_id
	 */
	public function test_xmlrpc_get_user_invalid_input() {
		$server = new Jetpack_XMLRPC_Server();

		$missing_response = $server->get_user( '' );

		$this->assertEquals( 'IXR_Error', get_class( $missing_response ) );
		$this->assertEquals( 400, $missing_response->code );
		$this->assertEquals( 'Jetpack: [invalid_user] Invalid user identifier.', $missing_response->message );
	}

	/**
	 * Test test_xmlrpc_get_user_by_id
	 */
	public function test_xmlrpc_get_user_no_matching_user() {
		$server = new Jetpack_XMLRPC_Server();

		$missing_response = $server->get_user( 'nope@nope.nope' );

		$this->assertEquals( 'IXR_Error', get_class( $missing_response ) );
		$this->assertEquals( 404, $missing_response->code );
		$this->assertEquals( 'Jetpack: [user_unknown] User not found.', $missing_response->message );
	}

	/**
	 * Test test_xmlrpc_get_user_by_id
	 *
	 * @param WP_User $user The user object.
	 * @param array   $response XMLRPC response.
	 * @return void
	 */
	protected function assertGetUserEqual( $user, $response ) {
		$this->assertEquals( $user->ID, $response['id'] );
		$this->assertEquals( md5( strtolower( trim( $user->user_email ) ) ), $response['email_hash'] );
		$this->assertEquals( $user->user_login, $response['login'] );
		$this->assertEquals( sort( $user->roles ), sort( $response['roles'] ) );
		$this->assertEquals( sort( $user->caps ), sort( $response['caps'] ) );
		$this->assertEquals( sort( $user->allcaps ), sort( $response['allcaps'] ) );
	}

	/**
	 * Test test_xmlrpc_get_user_by_id
	 */
	public function test_xmlrpc_remote_register_nonce_validation() {
		$server  = new Jetpack_XMLRPC_Server();
		$filters = array(
			'return_invalid_nonce_status' => array(
				'code'    => 400,
				'message' => 'invalid_nonce',
			),
			'return_nonce_404_status'     => array(
				'code'    => 400,
				'message' => 'invalid_nonce',
			),
		);

		foreach ( $filters as $filter => $expected ) {
			add_filter( 'pre_http_request', array( $this, $filter ) );
			$response = $server->remote_register(
				array(
					'nonce'      => '12345',
					'local_user' => $this->xmlrpc_admin,
				)
			);
			remove_filter( 'pre_http_request', array( $this, $filter ) );

			$this->assertInstanceOf( 'IXR_Error', $response );
			$this->assertEquals( $expected['code'], $response->code );

			$this->assertStringContainsString( sprintf( '[%s]', $expected['message'] ), $response->message );
		}
	}

	/**
	 * Test test_xmlrpc_get_user_by_id
	 */
	public function test_successful_remote_register_return() {
		$server = new Jetpack_XMLRPC_Server();

		// Set these so that we don't try to register unnecessarily.
		Jetpack_Options::update_option( 'blog_token', 1 );
		Jetpack_Options::update_option( 'id', 1001 );

		add_filter( 'pre_http_request', array( $this, 'return_ok_status' ) );
		$response = $server->remote_register(
			array(
				'nonce'      => '12345',
				'local_user' => $this->xmlrpc_admin,
			)
		);
		remove_filter( 'pre_http_request', array( $this, 'return_ok_status' ) );

		$this->assertArrayHasKey( 'client_id', $response );
		$this->assertEquals( 1001, $response['client_id'] );
	}

	/**
	 * Test test_xmlrpc_get_user_by_id
	 */
	public function test_remote_connect_error_when_site_active() {
		// Simulate the site being active.
		Jetpack_Options::update_options(
			array(
				'blog_token' => 1,
				'id'         => 1001,
			)
		);
		( new Tokens() )->update_user_token( 1, sprintf( '%s.%d', 'token', 1 ), true );

		try {
			$server = new Jetpack_XMLRPC_Server();

			$response = $server->remote_connect(
				array(
					'nonce'      => '1234',
					'local_user' => $this->xmlrpc_admin,
				)
			);

			$this->assertInstanceOf( 'IXR_Error', $response );
			$this->assertObjectHasProperty( 'code', $response );
			$this->assertObjectHasProperty( 'message', $response );
			$this->assertEquals( 400, $response->code );
			$this->assertEquals(
				'Jetpack: [token_fetch_failed] Failed to fetch user token from WordPress.com.',
				$response->message
			);
		} finally {
			foreach ( array( 'blog_token', 'id', 'master_user', 'user_tokens' ) as $option_name ) {
				Jetpack_Options::delete_option( $option_name );
			}
		}
	}

	/**
	 * Test test_xmlrpc_get_user_by_id
	 */
	public function test_remote_connect_error_invalid_user() {
		$server   = new Jetpack_XMLRPC_Server();
		$response = $server->remote_connect(
			array(
				'nonce'      => '1234',
				'local_user' => '100000000',
			)
		);

		$this->assertInstanceOf( 'IXR_Error', $response );
		$this->assertObjectHasProperty( 'code', $response );
		$this->assertObjectHasProperty( 'message', $response );
		$this->assertEquals( 400, $response->code );
		$this->assertEquals(
			'Jetpack: [input_error] Valid user is required.',
			$response->message
		);
	}

	/**
	 * Test test_xmlrpc_get_user_by_id
	 */
	public function test_remote_connect_empty_nonce() {
		$server   = new Jetpack_XMLRPC_Server();
		$response = $server->remote_connect(
			array(
				'local_user' => $this->xmlrpc_admin,
			)
		);

		$this->assertInstanceOf( 'IXR_Error', $response );
		$this->assertObjectHasProperty( 'code', $response );
		$this->assertObjectHasProperty( 'message', $response );
		$this->assertEquals( 400, $response->code );
		$this->assertEquals(
			'Jetpack: [input_error] A non-empty nonce must be supplied.',
			$response->message
		);
	}

	/**
	 * Test test_xmlrpc_get_user_by_id
	 */
	public function test_remote_connect_fails_no_blog_token() {
		Jetpack_Options::delete_option( 'blog_token' );

		$server = new Jetpack_XMLRPC_Server();

		$response = $server->remote_connect(
			array(
				'nonce'      => '1234',
				'local_user' => $this->xmlrpc_admin,
			)
		);

		$this->assertInstanceOf( 'IXR_Error', $response );
		$this->assertObjectHasProperty( 'code', $response );
		$this->assertObjectHasProperty( 'message', $response );
		$this->assertEquals( 400, $response->code );
		$this->assertEquals(
			'Jetpack: [token_fetch_failed] Failed to fetch user token from WordPress.com.',
			$response->message
		);
	}

	/**
	 * Test test_xmlrpc_get_user_by_id
	 */
	public function test_remote_connect_nonce_validation_error() {
		Jetpack_Options::update_options(
			array(
				'id'         => 1001,
				'blog_token' => '123456.123456',
			)
		);

		$server   = new Jetpack_XMLRPC_Server();
		$response = $server->remote_connect(
			array(
				'nonce'      => '1234',
				'local_user' => $this->xmlrpc_admin,
			),
			$this->get_mocked_ixr_client( true, false )
		);

		$this->assertInstanceOf( 'IXR_Error', $response );
		$this->assertObjectHasProperty( 'code', $response );
		$this->assertObjectHasProperty( 'message', $response );
		$this->assertEquals( 400, $response->code );
		$this->assertEquals(
			'Jetpack: [token_fetch_failed] Failed to fetch user token from WordPress.com.',
			$response->message
		);
	}

	/**
	 * Test test_xmlrpc_get_user_by_id
	 */
	public function test_remote_connect_success() {
		Jetpack_Options::update_options(
			array(
				'id'         => 1001,
				'blog_token' => '123456.123456',
			)
		);

		$server   = new Jetpack_XMLRPC_Server();
		$response = $server->remote_connect(
			array(
				'nonce'      => '1234',
				'local_user' => $this->xmlrpc_admin,
			),
			$this->get_mocked_ixr_client( true, 'this_is.a_token' )
		);

		$this->assertTrue( $response );
	}

	/**
	 * Unit test for the `validate_urls_for_idc_mitigation()` method.
	 */
	public function test_validate_urls_for_idc_mitigation() {
		$url          = 'http://example.org';
		$custom_param = 'test12345';

		Constants::set_constant( 'WP_HOME', $url );
		Constants::set_constant( 'WP_SITEURL', $url );

		$response_filter = function ( array $response ) use ( $custom_param ) {
			$response['custom_param'] = $custom_param;
			return $response;
		};

		add_filter( 'jetpack_connection_validate_urls_for_idc_mitigation_response', $response_filter );

		$server   = new Jetpack_XMLRPC_Server();
		$response = $server->validate_urls_for_idc_mitigation();

		Constants::clear_single_constant( 'WP_HOME' );
		Constants::clear_single_constant( 'WP_SITEURL' );
		remove_filter( 'jetpack_connection_validate_urls_for_idc_mitigation_response', $response_filter );

		$expected = array(
			'home'         => $url,
			'siteurl'      => $url,
			'custom_param' => $custom_param,
		);
		$this->assertEquals( $expected, $response );
	}

	/*
	 * Helpers
	 */

	/**
	 * Return an "ok" status.
	 *
	 * @return array
	 */
	public function return_ok_status() {
		return array(
			'body'     => 'OK',
			'response' => array(
				'code'    => 200,
				'message' => '',
			),
		);
	}

	/**
	 * Return an "invalid nonce" status.
	 *
	 * @return array
	 */
	public function return_invalid_nonce_status() {
		return array(
			'body'     => 'FAIL: NOT OK',
			'response' => array(
				'code'    => 200,
				'message' => '',
			),
		);
	}

	/**
	 * Return an "nonce 404" status.
	 *
	 * @return array
	 */
	public function return_nonce_404_status() {
		return array(
			'body'     => '',
			'response' => array(
				'code'    => 404,
				'message' => '',
			),
		);
	}

	/**
	 * Get a mocked IXR client.
	 *
	 * @param bool   $query_called Whether `query` should be called.
	 * @param string $response Return value for `getResponse`.
	 * @param bool   $query_return Return value for `query`.
	 * @param string $error Return value for `isError`.
	 * @return Jetpack_IXR_Client
	 */
	protected function get_mocked_ixr_client( $query_called = false, $response = '', $query_return = true, $error = null ) {
		$xml = $this->getMockBuilder( 'Jetpack_IXR_Client' )
			->onlyMethods(
				array(
					'query',
					'isError',
					'getResponse',
				)
			)
			->getMock();

		$xml->expects( $this->exactly( $query_called ? 1 : 0 ) )
			->method( 'query' )
			->willReturn( $query_return );

		$xml->expects( $this->exactly( $query_called ? 1 : 0 ) )
			->method( 'isError' )
			->willReturn( empty( $error ) ? false : true );

		$xml->expects( $this->exactly( $error ? 0 : 1 ) )
			->method( 'getResponse' )
			->willReturn( $response );

		return $xml;
	}
}
