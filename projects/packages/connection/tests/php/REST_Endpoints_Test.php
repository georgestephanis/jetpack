<?php

namespace Automattic\Jetpack\Connection;

use Automattic\Jetpack\Connection\Package_Version_Tracker as Connection_Package_Version_Tracker;
use Automattic\Jetpack\Connection\Plugin as Connection_Plugin;
use Automattic\Jetpack\Connection\Plugin_Storage as Connection_Plugin_Storage;
use Automattic\Jetpack\Connection\Rest_Authentication as Connection_Rest_Authentication;
use Automattic\Jetpack\Constants;
use Automattic\Jetpack\Heartbeat;
use Automattic\Jetpack\Redirect;
use Automattic\Jetpack\Status\Cache as StatusCache;
use Jetpack_Options;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WorDBless\Options as WorDBless_Options;
use WorDBless\Users as WorDBless_Users;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WpOrg\Requests\Utility\CaseInsensitiveDictionary;

/**
 * Unit tests for the REST API endpoints.
 *
 * @package automattic/jetpack-connection
 * @see \Automattic\Jetpack\Connection\REST_Connector
 */
class REST_Endpoints_Test extends TestCase {

	const BLOG_TOKEN = 'new.blogtoken';
	const BLOG_ID    = 42;

	/**
	 * REST Server object.
	 *
	 * @var WP_REST_Server
	 */
	private $server;

	/**
	 * The original hostname to restore after tests are finished.
	 *
	 * @var string
	 */
	private $api_host_original;

	/**
	 * The current user id.
	 *
	 * @var int|null
	 */
	private static $user_id;

	/**
	 * The secondary user id.
	 *
	 * @var int|null
	 */
	private static $secondary_user_id;

	/**
	 * ID of a non-admin user
	 *
	 * @var int|null
	 */
	private static $non_admin_user_id;

	/**
	 * Setting up the test.
	 */
	public function setUp(): void {
		parent::setUp();
		global $wp_rest_server;

		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;

		do_action( 'rest_api_init' );
		new REST_Connector( new Manager() );
		Heartbeat::init()->initialize_rest_api();

		add_action( 'jetpack_disabled_raw_options', array( $this, 'bypass_raw_options' ) );

		self::$user_id = wp_insert_user(
			array(
				'user_login' => 'endpoints_test_user',
				'user_pass'  => '123',
				'role'       => 'administrator',
			)
		);
		wp_set_current_user( self::$user_id );
		$user = wp_get_current_user();

		// Hack to prevent Tracking.
		// @see Tracking::tracks_record_event
		// @todo Fix this properly.
		$user->cap_key = 'wptests_capabilities';

		$user->add_cap( 'jetpack_reconnect' );
		$user->add_cap( 'jetpack_connect' );
		$user->add_cap( 'jetpack_disconnect' );
		$user->add_cap( 'jetpack_connect_user' );
		$user->add_cap( 'jetpack_unlink_user' );

		self::$secondary_user_id = wp_insert_user(
			array(
				'user_login' => 'endpoints_test_secondary_user',
				'user_pass'  => '123',
				'role'       => 'administrator',
			)
		);

		self::$non_admin_user_id = wp_insert_user(
			array(
				'user_login' => 'endpoints_test_non_admin_user',
				'user_pass'  => '123',
				'role'       => 'editor',
			)
		);
		// mapped cap assignment is not set up in the tests
		// this cap needs to be assigned manually
		$non_admin_user = get_user_by( 'id', self::$non_admin_user_id );
		$non_admin_user->add_cap( 'jetpack_unlink_user' );

		$this->api_host_original                                  = Constants::get_constant( 'JETPACK__WPCOM_JSON_API_BASE' );
		Constants::$set_constants['JETPACK__WPCOM_JSON_API_BASE'] = 'https://public-api.wordpress.com';

		Constants::$set_constants['JETPACK__API_BASE'] = 'https://jetpack.wordpress.com/jetpack.';

		set_transient( 'jetpack_assumed_site_creation_date', '2020-02-28 01:13:27' );
		$this->reset_connection_status();
	}

	/**
	 * Returning the environment into its initial state.
	 */
	public function tearDown(): void {
		parent::tearDown();
		remove_action( 'jetpack_disabled_raw_options', array( $this, 'bypass_raw_options' ) );

		// Only remove caps if user is still valid
		$user = wp_get_current_user();
		if ( $user && $user->ID ) {
			$user->remove_cap( 'jetpack_reconnect' );
			$user->remove_cap( 'jetpack_connect' );
			$user->remove_cap( 'jetpack_disconnect' );
			$user->remove_cap( 'jetpack_connect_user' );
			$user->remove_cap( 'jetpack_unlink_user' );
		}

		// Only remove cap if non-admin user is still valid
		if ( ! is_wp_error( self::$non_admin_user_id ) ) {
			$non_admin_user = get_user_by( 'id', self::$non_admin_user_id );
			if ( $non_admin_user ) {
				$non_admin_user->remove_cap( 'jetpack_unlink_user' );
			}
		}

		// Reset current user
		wp_set_current_user( 0 );

		// Explicitly delete the users we created
		if ( isset( self::$user_id ) && ! is_wp_error( self::$user_id ) ) {
			wp_delete_user( self::$user_id );
			self::$user_id = null;
		}

		if ( isset( self::$secondary_user_id ) && ! is_wp_error( self::$secondary_user_id ) ) {
			wp_delete_user( self::$secondary_user_id );
			self::$secondary_user_id = null;
		}

		if ( isset( self::$non_admin_user_id ) && ! is_wp_error( self::$non_admin_user_id ) ) {
			wp_delete_user( self::$non_admin_user_id );
			self::$non_admin_user_id = null;
		}

		// Also clean up any other users that might have been created
		$users = get_users();
		foreach ( $users as $user ) {
			if ( $user->ID > 0 ) {
				wp_delete_user( $user->ID );
			}
		}

		Constants::$set_constants['JETPACK__WPCOM_JSON_API_BASE'] = $this->api_host_original;

		delete_transient( 'jetpack_assumed_site_creation_date' );

		WorDBless_Options::init()->clear_options();
		WorDBless_Users::init()->clear_all_users();

		unset( $_SERVER['REQUEST_METHOD'] );
		$_GET = array();

		Connection_Rest_Authentication::init()->reset_saved_auth_state();
		$this->reset_connection_status();

		// Clean up user meta and options
		global $wpdb;
		if ( isset( $wpdb->usermeta ) ) {
			$wpdb->query( "DELETE FROM $wpdb->usermeta" );
		}
		if ( isset( $wpdb->users ) ) {
			$wpdb->query( "DELETE FROM $wpdb->users" );
		}
	}

	/**
	 * Reset the connection status.
	 * Needed because the connection status is memoized and not reset between tests.
	 * WorDBless does not fire the options update hooks that would reset the connection status.
	 */
	public function reset_connection_status() {
		static $manager = null;
		if ( ! $manager ) {
			$manager = new \Automattic\Jetpack\Connection\Manager();
		}
		$manager->reset_connection_status();
	}

	/**
	 * Testing the `/jetpack/v4/remote_authorize` endpoint.
	 */
	public function test_remote_authorize() {
		wp_set_current_user( 0 );
		add_filter( 'jetpack_options', array( $this, 'mock_jetpack_site_connection_options' ), 10, 2 );
		add_filter( 'pre_http_request', array( $this, 'intercept_auth_token_request' ), 10, 3 );

		$secret_1 = 'Az0g39toGWlYiTJ4NnDuAz0g39toGWlY';

		$secrets = array(
			'jetpack_authorize_' . self::$user_id => array(
				'secret_1' => $secret_1,
				'secret_2' => 'zfIFcym2Jlzd8AVgzfIFcym2Jlzd8AVg',
				'exp'      => time() + 60,
			),
		);

		// phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$options_filter = function ( $value ) use ( $secrets ) {
			return $secrets;
		};
		add_filter( 'pre_option_' . Secrets::LEGACY_SECRETS_OPTION_NAME, $options_filter );

		$request = new WP_REST_Request( 'POST', '/jetpack/v4/remote_authorize' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( '{ "state": "' . self::$user_id . '", "secret": "' . $secret_1 . '", "redirect_uri": "https://example.org", "code": "54321" }' );

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		remove_filter( 'pre_option_' . Secrets::LEGACY_SECRETS_OPTION_NAME, $options_filter );
		remove_filter( 'pre_http_request', array( $this, 'intercept_auth_token_request' ) );
		remove_filter( 'jetpack_options', array( $this, 'mock_jetpack_site_connection_options' ) );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'authorized', $data['result'] );
	}

	/**
	 * Testing the `/jetpack/v4/connection` endpoint.
	 */
	public function test_connection() {
		StatusCache::clear();
		add_filter( 'jetpack_offline_mode', '__return_true' );
		try {
			$request = new WP_REST_Request( 'GET', '/jetpack/v4/connection' );

			$response = $this->server->dispatch( $request );
			$data     = $response->get_data();

			$this->assertFalse( $data['isActive'] );
			$this->assertFalse( $data['isRegistered'] );
			$this->assertTrue( $data['offlineMode']['isActive'] );
		} finally {
			remove_filter( 'jetpack_offline_mode', '__return_true' );
			StatusCache::clear();
		}
	}

	/**
	 * Testing the `/jetpack/v4/connection` endpoint jetpack_connection_status filter.
	 */
	public function test_connection_jetpack_connection_status_filter() {
		add_filter(
			'jetpack_connection_status',
			function ( $status_data ) {
				$this->assertIsArray( $status_data );
				return array();
			}
		);
		try {
			$request = new WP_REST_Request( 'GET', '/jetpack/v4/connection' );

			$response = $this->server->dispatch( $request );
			$data     = $response->get_data();

			$this->assertSame( array(), $data );
		} finally {
			remove_all_filters( 'jetpack_connection_status' );
		}
	}

	/**
	 * Testing the `/jetpack/v4/connection/plugins` endpoint.
	 */
	public function test_connection_plugins() {
		$user = wp_get_current_user();
		$user->add_cap( 'activate_plugins' );

		$plugins = array(
			array(
				'name' => 'Plugin Name 1',
				'slug' => 'plugin-slug-1',
			),
			array(
				'name' => 'Plugin Name 2',
				'slug' => 'plugin-slug-2',
			),
		);

		array_walk(
			$plugins,
			function ( $plugin ) {
				( new Connection_Plugin( $plugin['slug'] ) )->add( $plugin['name'] );
			}
		);

		Connection_Plugin_Storage::configure();

		$request = new WP_REST_Request( 'GET', '/jetpack/v4/connection/plugins' );

		$response = $this->server->dispatch( $request );

		$user->remove_cap( 'activate_plugins' );

		$this->assertEquals( $plugins, $response->get_data() );
	}

	/**
	 * Testing the `connection/reconnect` endpoint, full reconnect.
	 */
	public function test_connection_reconnect_full() {
		$this->setup_reconnect_test( null );
		add_filter( 'jetpack_connection_disconnect_site_wpcom', '__return_false' );
		add_filter( 'pre_http_request', array( static::class, 'intercept_register_request' ), 10, 3 );

		$response = $this->server->dispatch( $this->build_reconnect_request() );
		$data     = $response->get_data();

		remove_filter( 'pre_http_request', array( static::class, 'intercept_register_request' ), 10 );
		remove_filter( 'jetpack_connection_disconnect_site_wpcom', '__return_false' );
		$this->shutdown_reconnect_test( null );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'in_progress', $data['status'] );
		$this->assertSame( 0, strpos( $data['authorizeUrl'], 'https://jetpack.wordpress.com/jetpack.authorize/' ) );
	}

	/**
	 * Testing the `connection/reconnect` endpoint, successful partial reconnect (blog token).
	 */
	public function test_connection_reconnect_partial_blog_token_success() {
		$this->setup_reconnect_test( 'blog_token' );
		add_filter( 'pre_http_request', array( $this, 'intercept_refresh_blog_token_request' ), 10, 3 );

		$response = $this->server->dispatch( $this->build_reconnect_request() );
		$data     = $response->get_data();

		remove_filter( 'pre_http_request', array( $this, 'intercept_refresh_blog_token_request' ), 10 );
		$this->shutdown_reconnect_test( 'blog_token' );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'completed', $data['status'] );
	}

	/**
	 * Testing the `connection/reconnect` endpoint, failed partial reconnect (blog token).
	 */
	public function test_connection_reconnect_partial_blog_token_fail() {
		$this->setup_reconnect_test( 'blog_token' );
		add_filter( 'pre_http_request', array( $this, 'intercept_refresh_blog_token_request_fail' ), 10, 3 );

		$response = $this->server->dispatch( $this->build_reconnect_request() );
		$data     = $response->get_data();

		remove_filter( 'pre_http_request', array( $this, 'intercept_refresh_blog_token_request_fail' ), 10 );
		$this->shutdown_reconnect_test( 'blog_token' );

		$this->assertEquals( 500, $response->get_status() );
		$this->assertEquals( 'jetpack_secret', $data['code'] );
	}

	/**
	 * Testing the `connection/reconnect` endpoint, successful partial reconnect (user token).
	 */
	public function test_connection_reconnect_partial_user_token_success() {
		$this->setup_reconnect_test( 'user_token' );
		// Mock user successfully unlinked on WPCOM.
		add_filter( 'pre_http_request', array( $this, 'mock_xmlrpc_success' ), 10, 3 );

		$response = $this->server->dispatch( $this->build_reconnect_request() );
		$data     = $response->get_data();

		remove_filter( 'pre_http_request', array( $this, 'mock_xmlrpc_success' ), 10 );
		$this->shutdown_reconnect_test( 'user_token' );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'in_progress', $data['status'] );
		$this->assertSame( 0, strpos( $data['authorizeUrl'], 'https://jetpack.wordpress.com/jetpack.authorize/' ) );
	}

	/**
	 * Testing the `connection/reconnect` endpoint, site_connection (full reconnect).
	 */
	public function test_connection_reconnect_site_connection() {
		add_filter( 'jetpack_options', array( $this, 'mock_jetpack_site_connection_options' ), 10, 2 );
		add_filter( 'jetpack_connection_disconnect_site_wpcom', '__return_false' );
		add_filter( 'pre_http_request', array( static::class, 'intercept_register_request' ), 10, 3 );

		$response = $this->server->dispatch( $this->build_reconnect_request() );
		$data     = $response->get_data();

		remove_filter( 'pre_http_request', array( static::class, 'intercept_register_request' ), 10 );
		remove_filter( 'jetpack_connection_disconnect_site_wpcom', '__return_false' );
		remove_filter( 'jetpack_options', array( $this, 'mock_jetpack_site_connection_options' ) );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'completed', $data['status'] );
	}

	/**
	 * Testing the `connection/reconnect` endpoint when the token validation request fails.
	 */
	public function test_connection_reconnect_when_token_validation_request_fails() {
		$this->setup_reconnect_test( 'token_validation_failed' );
		add_filter( 'jetpack_connection_disconnect_site_wpcom', '__return_false' );
		add_filter( 'pre_http_request', array( static::class, 'intercept_register_request' ), 10, 3 );

		$response = $this->server->dispatch( $this->build_reconnect_request() );
		$data     = $response->get_data();

		remove_filter( 'pre_http_request', array( static::class, 'intercept_register_request' ), 10 );
		remove_filter( 'jetpack_connection_disconnect_site_wpcom', '__return_false' );
		$this->shutdown_reconnect_test( 'token_validation_failed' );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'in_progress', $data['status'] );
		$this->assertSame( 0, strpos( $data['authorizeUrl'], 'https://jetpack.wordpress.com/jetpack.authorize/' ) );
	}

	/**
	 * Testing the `connection/register` endpoint.
	 */
	public function test_connection_register() {
		add_filter( 'pre_http_request', array( static::class, 'intercept_register_request' ), 10, 3 );

		$request = new WP_REST_Request( 'POST', '/jetpack/v4/connection/register' );
		$request->set_header( 'Content-Type', 'application/json' );

		$request->set_body( wp_json_encode( array( 'registration_nonce' => wp_create_nonce( 'jetpack-registration-nonce' ) ) ) );

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		remove_filter( 'pre_http_request', array( static::class, 'intercept_register_request' ), 10 );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertSame( 0, strpos( $data['authorizeUrl'], 'https://jetpack.wordpress.com/jetpack.authorize/' ) );

		// Asserts that package_versions option will be populated on successful response.
		$this->assertNotFalse( get_option( Connection_Package_Version_Tracker::PACKAGE_VERSION_OPTION ) );

		// Asserts jetpack_register_site_rest_response filter is being properly hooked to add data from wpcom register endpoint response.
		$this->assertSame( '', $data['alternateAuthorizeUrl'] );
	}

	/**
	 * Testing the `connection/register` endpoint with alternate_authorization_url
	 */
	public function test_connection_register_with_alternate_auth_url() {
		add_filter( 'pre_http_request', array( static::class, 'intercept_register_request_with_alternate_auth_url' ), 10, 3 );

		$request = new WP_REST_Request( 'POST', '/jetpack/v4/connection/register' );
		$request->set_header( 'Content-Type', 'application/json' );

		$request->set_body( wp_json_encode( array( 'registration_nonce' => wp_create_nonce( 'jetpack-registration-nonce' ) ) ) );

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		remove_filter( 'pre_http_request', array( static::class, 'intercept_register_request_with_alternate_auth_url' ), 10 );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertSame( 0, strpos( $data['authorizeUrl'], 'https://jetpack.wordpress.com/jetpack.authorize/' ) );

		// Asserts jetpack_register_site_rest_response filter is being properly hooked to add data from wpcom register endpoint response.
		$this->assertSame( Redirect::get_url( 'https://dummy.com' ), $data['alternateAuthorizeUrl'] );
	}

	/**
	 * Testing the `user-token` endpoint without authentication.
	 * Response: failed authorization.
	 */
	public function test_set_user_token_unauthenticated() {
		wp_set_current_user( 0 );
		$request = new WP_REST_Request( 'POST', '/jetpack/v4/user-token' );
		$request->set_header( 'Content-Type', 'application/json' );

		$request->set_body( wp_json_encode( array( 'user_token' => 'test.test.1' ) ) );

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		static::assertEquals( 'invalid_permission_update_user_token', $data['code'] );
		static::assertEquals( 401, $data['data']['status'] );
	}

	/**
	 * Testing the `user-token` endpoint with admin user.
	 * Response: failed authorization.
	 */
	public function test_set_user_token_with_admin_user_fails_auth() {
		$request = new WP_REST_Request( 'POST', '/jetpack/v4/user-token' );
		$request->set_header( 'Content-Type', 'application/json' );

		$request->set_body( wp_json_encode( array( 'user_token' => 'test.test.1' ) ) );

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		static::assertEquals( 'invalid_permission_update_user_token', $data['code'] );
		static::assertEquals( 403, $data['data']['status'] );
	}

	/**
	 * Testing the `user-token` endpoint using blog token authorization.
	 * Response: user token updated.
	 */
	public function test_set_user_token_success() {
		add_filter( 'jetpack_options', array( $this, 'mock_jetpack_site_connection_options' ), 10, 2 );

		$action_hook_id    = null;
		$action_hook_token = null;
		$action_hook       = function ( $user_id, $user_token ) use ( &$action_hook_id, &$action_hook_token ) {
			$action_hook_id    = $user_id;
			$action_hook_token = $user_token;
		};

		add_action( 'jetpack_updated_user_token', $action_hook, 10, 2 );

		$this->mock_signed_post_request_with_blog_token();

		wp_cache_set(
			1,
			(object) array(
				'ID'         => 1,
				'user_email' => 'sample@example.org',
			),
			'users'
		);

		$request = new WP_REST_Request( 'POST', '/jetpack/v4/user-token' );
		$request->set_header( 'Content-Type', 'application/json' );

		$user_token = 'test.test.1';

		$request->set_body( wp_json_encode( array( 'user_token' => $user_token ) ) );

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		remove_action( 'jetpack_updated_user_token', $action_hook );
		remove_filter( 'jetpack_options', array( $this, 'mock_jetpack_site_connection_options' ) );
		wp_cache_delete( 1, 'users' );

		static::assertTrue( $data['success'] );
		static::assertEquals( 200, $response->status );
		static::assertEquals( array( 1 => $user_token ), Jetpack_Options::get_option( 'user_tokens' ) );
		static::assertSame( 1, $action_hook_id, "The 'jetpack_update_user_token_success' action was not properly executed." );
		static::assertEquals( $user_token, $action_hook_token, "The 'jetpack_update_user_token_success' action was not properly executed." );
	}

	/**
	 * Testing the `connection/owner` endpoint on failure.
	 */
	public function test_update_connection_owner_failures() {
		// Mock full connection established.
		add_filter( 'jetpack_options', array( $this, 'mock_jetpack_options' ), 10, 2 );

		$request = new WP_REST_Request( 'POST', '/jetpack/v4/connection/owner' );
		$request->set_header( 'Content-Type', 'application/json' );

		// Attempt owner change without setting an owner.
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'Missing parameter(s): owner', $response->get_data()['message'] );

		// Attempt owner change with bad user.
		$request->set_body( wp_json_encode( array( 'owner' => 999 ) ) );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'New owner is not admin', $response->get_data()['message'] );

		// Change owner to valid user but XML-RPC request to WPCOM failed.
		add_filter( 'pre_http_request', array( $this, 'mock_xmlrpc_failure' ), 10, 3 );

		$request->set_body( wp_json_encode( array( 'owner' => self::$secondary_user_id ) ) );
		$response = $this->server->dispatch( $request );

		remove_filter( 'pre_http_request', array( $this, 'mock_xmlrpc_failure' ), 10 );
		remove_filter( 'jetpack_options', array( $this, 'mock_jetpack_options' ), 10 );

		$this->assertEquals( 500, $response->get_status() );
		$this->assertEquals( 'Could not confirm new owner.', $response->get_data()['message'] );
	}

	/**
	 * Testing the `connection/owner` endpoint on success.
	 */
	public function test_update_connection_owner_success() {
		// Change owner to valid user.
		$request = new WP_REST_Request( 'POST', '/jetpack/v4/connection/owner' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( array( 'owner' => self::$secondary_user_id ) ) );

		// Mock full connection established.
		add_filter( 'jetpack_options', array( $this, 'mock_jetpack_options' ), 10, 2 );
		// Mock owner successfully updated on WPCOM.
		add_filter( 'pre_http_request', array( $this, 'mock_xmlrpc_success' ), 10, 3 );
		$response = $this->server->dispatch( $request );

		remove_filter( 'pre_http_request', array( $this, 'mock_xmlrpc_success' ), 10 );
		remove_filter( 'jetpack_options', array( $this, 'mock_jetpack_options' ), 10 );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( self::$secondary_user_id, Jetpack_Options::get_option( 'master_user' ), 'Connection owner should be updated.' );
	}

	/**
	 * Testing the `POST /jetpack/v4/connection` endpoint, aka site disconnect endpoint, when isActive is missing.
	 */
	public function test_disconnect_site_with_missing_param() {
		$request = new WP_REST_Request( 'POST', '/jetpack/v4/connection' );
		$request->set_header( 'Content-Type', 'application/json' );

		$response      = $this->server->dispatch( $request );
		$response_data = $response->get_data();

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'Missing parameter(s): isActive', $response_data['message'] );
	}

	/**
	 * Testing the `POST /jetpack/v4/connection` endpoint, aka site disconnect endpoint, when isActive is invalid.
	 */
	public function test_disconnect_site_with_invalid_param() {
		$request = new WP_REST_Request( 'POST', '/jetpack/v4/connection' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( array( 'isActive' => 'should_be_bool_false' ) ) );

		$response      = $this->server->dispatch( $request );
		$response_data = $response->get_data();

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'Invalid parameter(s): isActive', $response_data['message'] );
	}

	/**
	 * Testing the `POST /jetpack/v4/connection` endpoint, aka site disconnect endpoint, with invalid user permissions.
	 */
	public function test_disconnect_site_with_invalid_user_permissions() {
		// Invalid user permissions.
		$user = wp_get_current_user();
		$user->remove_cap( 'jetpack_disconnect' );

		$request = new WP_REST_Request( 'POST', '/jetpack/v4/connection' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( array( 'isActive' => false ) ) );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 403, $response->get_status() );
	}

	/**
	 * Testing the `POST /jetpack/v4/connection` endpoint, aka site disconnect endpoint, when the site is not connected.
	 */
	public function test_disconnect_site_site_not_connected() {

		$request = new WP_REST_Request( 'POST', '/jetpack/v4/connection' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( array( 'isActive' => false ) ) );

		$response      = $this->server->dispatch( $request );
		$response_data = $response->get_data();

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'Failed to disconnect the site as it appears already disconnected.', $response_data['message'] );
	}

	/**
	 * Testing the `POST /jetpack/v4/connection` endpoint, aka site disconnect endpoint, on success.
	 */
	public function test_disconnect_site_success() {
		$request = new WP_REST_Request( 'POST', '/jetpack/v4/connection' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( array( 'isActive' => false ) ) );

		// Mock full connection established.
		add_filter( 'jetpack_options', array( $this, 'mock_jetpack_options' ), 10, 2 );
		// Mock site successfully disconnected on WPCOM.
		add_filter( 'pre_http_request', array( $this, 'mock_xmlrpc_success' ), 10, 3 );

		$response      = $this->server->dispatch( $request );
		$response_data = $response->get_data();

		remove_filter( 'pre_http_request', array( $this, 'mock_xmlrpc_success' ), 10 );
		remove_filter( 'jetpack_options', array( $this, 'mock_jetpack_options' ), 10 );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'success', $response_data['code'] );
	}

	/**
	 * Testing the `POST /jetpack/v4/connection` endpoint, aka site disconnect endpoint, on success with a site-level connection (blog token).
	 */
	public function test_disconnect_site_site_connection_success() {
		wp_set_current_user( 0 );
		// Mock full connection established.
		add_filter( 'jetpack_options', array( $this, 'mock_jetpack_site_connection_options' ), 10, 2 );

		$this->mock_signed_post_request_with_blog_token();

		// Mock site successfully disconnected on WPCOM.
		add_filter( 'pre_http_request', array( $this, 'mock_xmlrpc_success' ), 10, 3 );

		$request = new WP_REST_Request( 'POST', '/jetpack/v4/connection' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( array( 'isActive' => false ) ) );

		$response      = $this->server->dispatch( $request );
		$response_data = $response->get_data();

		remove_filter( 'pre_http_request', array( $this, 'mock_xmlrpc_success' ), 10 );
		remove_filter( 'jetpack_options', array( $this, 'mock_jetpack_site_connection_options' ), 10 );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'success', $response_data['code'] );
	}

	/**
	 * Testing POST /jetpack/v4/connection/user, which is used to unlink a user
	 * Tests that the endpoint succeeds for a connected admin disconnecting themselves
	 */
	public function test_unlink_user_success() {

		$request = new WP_REST_Request( 'POST', '/jetpack/v4/connection/user' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'linked'               => false,
					'force'                => true,
					'disconnect-all-users' => true,
				)
			)
		);

		// Mock full connection established.
		add_filter( 'jetpack_options', array( $this, 'mock_jetpack_options' ), 10, 2 );
		// Mock user successfully disconnected on WPCOM.
		add_filter( 'pre_http_request', array( $this, 'mock_xmlrpc_success' ), 10, 3 );

		$response      = $this->server->dispatch( $request );
		$response_data = $response->get_data();

		remove_filter( 'pre_http_request', array( $this, 'mock_xmlrpc_success' ), 10 );
		remove_filter( 'jetpack_options', array( $this, 'mock_jetpack_options' ), 10 );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'success', $response_data['code'] );
	}

	/**
	 * Testing POST /jetpack/v4/connection/user, which is used to unlink a user
	 * Tests that the endpoint succeeds for a non-admin even a connection owner is absent
	 */
	public function test_unlink_user_success_when_non_admin_and_no_connection_owner() {
		wp_set_current_user( self::$non_admin_user_id );

		$request = new WP_REST_Request( 'POST', '/jetpack/v4/connection/user' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( array( 'linked' => false ) ) );

		// Mock non-admin user connected with no connection owner.
		add_filter( 'jetpack_options', array( $this, 'mock_jetpack_options_no_connection_owner' ), 10, 2 );
		// Mock user successfully disconnected on WPCOM.
		add_filter( 'pre_http_request', array( $this, 'mock_xmlrpc_success' ), 10, 3 );

		$response      = $this->server->dispatch( $request );
		$response_data = $response->get_data();

		remove_filter( 'pre_http_request', array( $this, 'mock_xmlrpc_success' ), 10 );
		remove_filter( 'jetpack_options', array( $this, 'mock_jetpack_options_no_connection_owner' ), 10 );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'success', $response_data['code'] );
	}

	/**
	 * Test data for test_get_user_connection_data_route_is_registered_with_jp_version
	 *
	 * @return array
	 */
	public static function get_user_connection_data_route_is_registered_with_jp_version_provider() {
		return array(
			'jp_version_null'       => array(
				null,
				true,
			),
			'jp_version_9.1'        => array(
				'9.1',
				false,
			),
			'jp_version_10.0-alpha' => array(
				'10.0-alpha',
				true,
			),
			'jp_version_10.0'       => array(
				'10.0',
				true,
			),
		);
	}

	/**
	 * Testing the `connection/data` endpoint will not be registered if Jetpack-the-plugin < 10.0 is active.
	 *
	 * @dataProvider get_user_connection_data_route_is_registered_with_jp_version_provider
	 *
	 * @param string $jp_version    The Jetpack plugin version.
	 * @param bool   $is_registered Whether the route should be registered or not.
	 */
	#[DataProvider( 'get_user_connection_data_route_is_registered_with_jp_version_provider' )]
	public function test_get_user_connection_data_route_is_registered_with_jp_version( $jp_version, $is_registered ) {
		global $wp_rest_server;

		if ( isset( $jp_version ) ) {
			Constants::$set_constants['JETPACK__VERSION'] = $jp_version;
		}

		// Trigger routes re-register.
		$wp_rest_server = new WP_REST_Server();
		new REST_Connector( new Manager() );

		$get_user_connection_data_route = '/jetpack/v4/connection/data';

		$routes = $wp_rest_server->get_routes();

		$route_is_registerd = array_key_exists( $get_user_connection_data_route, $routes );

		$this->assertSame( $is_registered, $route_is_registerd );

		// Clean-up.
		Constants::clear_single_constant( 'JETPACK__VERSION' );
	}

	/**
	 * Testing the `connection/data` endpoint with invalid user permissions.
	 */
	public function test_get_user_connection_data_with_invalid_user_permissions() {
		// Invalid user permissions.
		$user = wp_get_current_user();
		$user->remove_cap( 'jetpack_connect_user' );

		$request = new WP_REST_Request( 'GET', '/jetpack/v4/connection/data' );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 403, $response->get_status() );
		$this->assertEquals( REST_Connector::get_user_permissions_error_msg(), $response->get_data()['message'] );
	}

	/**
	 * Testing the `connection/data` endpoint without site or user level connection.
	 */
	public function test_get_user_connection_data_site_not_connected() {
		$user = wp_get_current_user();

		$request = new WP_REST_Request( 'GET', '/jetpack/v4/connection/data' );

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$expected = array(
			'currentUser'     => array(
				'isConnected'           => false,
				'isMaster'              => false,
				'username'              => $user->user_login,
				'id'                    => $user->ID,
				'blogId'                => false,
				'wpcomUser'             => array(
					'avatar' => false,
				),
				'permissions'           => array(
					'connect'        => true,
					'connect_user'   => true,
					'unlink_user'    => true,
					'disconnect'     => true,
					'manage_options' => true,
				),
				'possibleAccountErrors' => array(),
			),
			'connectionOwner' => null,
			'isRegistered'    => false,
		);

		$response_data = $response->get_data();

		// Remove gravatar as the url is random.
		unset( $response_data['currentUser']['gravatar'] );
		$this->assertSame( $expected, $response_data );
	}

	/**
	 * Testing the `connection/data` endpoint without user level connection.
	 */
	public function test_get_user_connection_data_without_user_connected() {
		$user = wp_get_current_user();

		// Mock full connection.
		add_filter( 'jetpack_options', array( $this, 'mock_jetpack_site_connection_options' ), 10, 2 );

		$request = new WP_REST_Request( 'GET', '/jetpack/v4/connection/data' );

		$response = $this->server->dispatch( $request );

		remove_filter( 'jetpack_options', array( $this, 'mock_jetpack_site_connection_options' ), 10 );

		$this->assertEquals( 200, $response->get_status() );

		$expected = array(
			'currentUser'     => array(
				'isConnected'           => false,
				'isMaster'              => false,
				'username'              => $user->user_login,
				'id'                    => $user->ID,
				'blogId'                => self::BLOG_ID,
				'wpcomUser'             => array(
					'avatar' => false,
				),
				'permissions'           => array(
					'connect'        => true,
					'connect_user'   => true,
					'unlink_user'    => true,
					'disconnect'     => true,
					'manage_options' => true,
				),
				'possibleAccountErrors' => array(),
			),
			'connectionOwner' => null,
			'isRegistered'    => true,
		);

		$response_data = $response->get_data();
		// Remove gravatar as the url is random.
		unset( $response_data['currentUser']['gravatar'] );
		$this->assertSame( $expected, $response_data );
	}

	/**
	 * Testing the `connection/data` endpoint with connected user.
	 */
	public function test_get_user_connection_data_with_connected_user() {
		$user = wp_get_current_user();

		// Mock full connection.
		add_filter( 'jetpack_options', array( $this, 'mock_jetpack_options' ), 10, 2 );
		// Set up some dummy cached user connection data.
		$dummy_wpcom_user_data = array(
			'ID'           => 999,
			'email'        => 'jane.doe@foobar.com',
			'login'        => 'janedoe',
			'display_name' => 'Jane Doe',
		);
		$transient_key         = 'jetpack_connected_user_data_' . self::$user_id;
		set_transient( $transient_key, $dummy_wpcom_user_data );

		$request = new WP_REST_Request( 'GET', '/jetpack/v4/connection/data' );

		$response = $this->server->dispatch( $request );

		delete_transient( $transient_key );
		remove_filter( 'jetpack_options', array( $this, 'mock_jetpack_options' ), 10 );

		$this->assertEquals( 200, $response->get_status() );

		$expected = array(
			'currentUser'     => array(
				'isConnected'           => true,
				'isMaster'              => true,
				'username'              => $user->user_login,
				'id'                    => $user->ID,
				'blogId'                => self::BLOG_ID,
				'wpcomUser'             => $dummy_wpcom_user_data,
				'permissions'           => array(
					'connect'        => true,
					'connect_user'   => true,
					'unlink_user'    => true,
					'disconnect'     => true,
					'manage_options' => true,
				),
				'possibleAccountErrors' => array(),
			),
			'connectionOwner' => $user->user_login,
			'isRegistered'    => true,
		);

		$response_data = $response->get_data();
		// Remove gravatar as the url is random.
		unset( $response_data['currentUser']['gravatar'] );
		unset( $response_data['currentUser']['wpcomUser']['avatar'] );
		$this->assertSame( $expected, $response_data );
	}

	/**
	 * Testing the `remote_register` endpoint without authentication on a fully connected site.
	 * Response: failed authorization.
	 */
	public function test_remote_register_connected_unauthenticated() {
		wp_set_current_user( 0 );
		$request = new WP_REST_Request( 'POST', '/jetpack/v4/remote_register' );
		$request->set_header( 'Content-Type', 'application/json' );

		$body = array(
			'local_user' => static::$user_id,
			'nonce'      => 'foobar',
		);
		$request->set_body( wp_json_encode( $body ) );

		// Mock full connection established.
		add_filter( 'jetpack_options', array( $this, 'mock_jetpack_options' ), 10, 2 );

		$response      = $this->server->dispatch( $request );
		$response_data = $response->get_data();

		remove_filter( 'jetpack_options', array( $this, 'mock_jetpack_options' ), 10 );

		static::assertEquals( 'already_registered', $response_data['code'] );
		static::assertEquals( 400, $response_data['data'] );
	}

	/**
	 * Testing the `remote_register` endpoint without authentication on a fully connected site.
	 * We intentionally provide an invalid user ID so the `Jetpack_XMLRPC_Server::remote_register()` would trigger an error.
	 * Response: `input_error`, meaning that the REST endpoint passed the data to the handler.
	 */
	public function test_remote_register_not_connected_unauthenticated() {
		wp_set_current_user( 0 );
		$request = new WP_REST_Request( 'POST', '/jetpack/v4/remote_register' );
		$request->set_header( 'Content-Type', 'application/json' );

		$body = array(
			'local_user' => -1,
			'nonce'      => 'foobar',
		);
		$request->set_body( wp_json_encode( $body ) );

		$response      = $this->server->dispatch( $request );
		$response_data = $response->get_data();

		static::assertTrue( false !== strpos( $response_data['message'], 'Valid user is required' ) );
		static::assertEquals( 400, $response_data['code'] );
	}

	/**
	 * Testing the `remote_register` endpoint with authentication on a fully connected site.
	 * We intentionally provide an invalid user ID so the `Jetpack_XMLRPC_Server::remote_register()` would trigger an error.
	 * Response: `input_error`, meaning that the REST endpoint passed the data to the handler.
	 */
	public function test_remote_register_connected_authenticated() {
		wp_set_current_user( 0 );

		// Mock full connection established.
		add_filter( 'jetpack_options', array( $this, 'mock_jetpack_options' ), 10, 2 );

		$this->mock_signed_post_request_with_blog_token();

		$request = new WP_REST_Request( 'POST', '/jetpack/v4/remote_register' );
		$request->set_header( 'Content-Type', 'application/json' );

		$body = array(
			'local_user' => -1,
			'nonce'      => 'foobar',
		);
		$request->set_body( wp_json_encode( $body ) );

		$response      = $this->server->dispatch( $request );
		$response_data = $response->get_data();

		remove_filter( 'jetpack_options', array( $this, 'mock_jetpack_options' ), 10 );

		static::assertTrue( false !== strpos( $response_data['message'], 'Valid user is required' ) );
		static::assertEquals( 400, $response_data['code'] );
	}

	/**
	 * Testing the `remote_provision` endpoint without authentication.
	 * Response: failed authorization.
	 */
	public function test_remote_provision_unauthenticated() {
		wp_set_current_user( 0 );
		$request = new WP_REST_Request( 'POST', '/jetpack/v4/remote_provision' );
		$request->set_header( 'Content-Type', 'application/json' );

		$body = array( 'local_user' => static::$user_id );
		$request->set_body( wp_json_encode( $body ) );

		// Mock full connection established.
		add_filter( 'jetpack_options', array( $this, 'mock_jetpack_options' ), 10, 2 );

		$response      = $this->server->dispatch( $request );
		$response_data = $response->get_data();

		remove_filter( 'jetpack_options', array( $this, 'mock_jetpack_options' ), 10 );

		static::assertEquals( 'invalid_permission_remote_provision', $response_data['code'] );
		static::assertEquals( 401, $response_data['data']['status'] );
	}

	/**
	 * Testing the `remote_provision` endpoint with proper authentication.
	 * We intentionally provide an invalid user ID so the `Jetpack_XMLRPC_Server::remote_provision()` would trigger an error.
	 * Response: `input_error`, meaning that the REST endpoint passed the data to the handler.
	 */
	public function test_remote_provision_authenticated() {
		wp_set_current_user( 0 );

		// Mock full connection established.
		add_filter( 'jetpack_options', array( $this, 'mock_jetpack_options' ), 10, 2 );

		$this->mock_signed_post_request_with_blog_token();

		$request = new WP_REST_Request( 'POST', '/jetpack/v4/remote_provision' );
		$request->set_header( 'Content-Type', 'application/json' );

		$body = array( 'local_user' => -1 );
		$request->set_body( wp_json_encode( $body ) );

		$response      = $this->server->dispatch( $request );
		$response_data = $response->get_data();

		remove_filter( 'jetpack_options', array( $this, 'mock_jetpack_options' ), 10 );

		static::assertTrue( false !== strpos( $response_data['message'], 'Valid user is required' ) );
		static::assertEquals( 400, $response_data['code'] );
	}

	/**
	 * Testing the `remote_connect` endpoint without authentication.
	 * Response: failed authorization.
	 */
	public function test_remote_connect_unauthenticated() {
		wp_set_current_user( 0 );
		$request = new WP_REST_Request( 'POST', '/jetpack/v4/remote_connect' );
		$request->set_header( 'Content-Type', 'application/json' );

		$body = array( 'local_user' => static::$user_id );
		$request->set_body( wp_json_encode( $body ) );

		// Mock full connection established.
		add_filter( 'jetpack_options', array( $this, 'mock_jetpack_options' ), 10, 2 );

		$response      = $this->server->dispatch( $request );
		$response_data = $response->get_data();

		remove_filter( 'jetpack_options', array( $this, 'mock_jetpack_options' ), 10 );

		static::assertEquals( 'invalid_permission_remote_connect', $response_data['code'] );
		static::assertEquals( 401, $response_data['data']['status'] );
	}

	/**
	 * Testing the `remote_connect` endpoint with proper authentication.
	 * Response: `already_connected`, meaning that the REST endpoint passed the data to the handler.
	 */
	public function test_remote_connect_authenticated() {
		wp_set_current_user( 0 );

		// Mock full connection established.
		add_filter( 'jetpack_options', array( $this, 'mock_jetpack_options' ), 10, 2 );

		$this->mock_signed_post_request_with_blog_token();

		$request = new WP_REST_Request( 'POST', '/jetpack/v4/remote_connect' );
		$request->set_header( 'Content-Type', 'application/json' );

		$body = array( 'local_user' => -1 );
		$request->set_body( wp_json_encode( $body ) );

		$response      = $this->server->dispatch( $request );
		$response_data = $response->get_data();

		remove_filter( 'jetpack_options', array( $this, 'mock_jetpack_options' ), 10 );

		static::assertTrue( false !== strpos( $response_data['message'], '[already_connected]' ) );
		static::assertEquals( 400, $response_data['code'] );
	}

	/**
	 * Testing the `heartbeat_data` endpoint without authentication.
	 * Response: failed authorization.
	 */
	public function test_heartbeat_data_unauthenticated() {
		wp_set_current_user( 0 );
		$request = new WP_REST_Request( 'GET', '/jetpack/v4/heartbeat/data' );
		$request->set_header( 'Content-Type', 'application/json' );

		// Mock full connection established.
		add_filter( 'jetpack_options', array( $this, 'mock_jetpack_options' ), 10, 2 );

		$response      = $this->server->dispatch( $request );
		$response_data = $response->get_data();

		remove_filter( 'jetpack_options', array( $this, 'mock_jetpack_options' ), 10 );

		static::assertEquals( 'invalid_permission_heartbeat_data', $response_data['code'] );
		static::assertEquals( 401, $response_data['data']['status'] );
	}

	/**
	 * Testing the `heartbeat_data` endpoint with proper authentication.
	 */
	public function test_heartbeat_data_authenticated() {
		wp_set_current_user( 0 );

		$data_filter = function () {
			return array(
				'key1' => 'val1',
				'key2' => 'val2',
			);
		};

		// Mock the heartbeat data.
		add_filter( 'jetpack_heartbeat_stats_array', $data_filter );

		// Mock full connection established.
		add_filter( 'jetpack_options', array( $this, 'mock_jetpack_options' ), 10, 2 );

		$this->mock_signed_post_request_with_blog_token();

		$request = new WP_REST_Request( 'GET', '/jetpack/v4/heartbeat/data' );
		$request->set_header( 'Content-Type', 'application/json' );

		$response      = $this->server->dispatch( $request );
		$response_data = $response->get_data();

		remove_filter( 'jetpack_options', array( $this, 'mock_jetpack_options' ) );
		remove_filter( 'jetpack_heartbeat_stats_array', $data_filter );

		static::assertEquals( $data_filter(), $response_data );
	}

	/**
	 * Testing the `test_connection` endpoint without authentication.
	 * Response: failed authorization.
	 */
	public function test_connection_check_unauthenticated() {
		wp_set_current_user( 0 );
		$request = new WP_REST_Request( 'GET', '/jetpack/v4/connection/check' );
		$request->set_header( 'Content-Type', 'application/json' );

		// Mock full connection established.
		add_filter( 'jetpack_options', array( $this, 'mock_jetpack_options' ), 10, 2 );

		$response      = $this->server->dispatch( $request );
		$response_data = $response->get_data();

		remove_filter( 'jetpack_options', array( $this, 'mock_jetpack_options' ), 10 );

		static::assertEquals( 'invalid_permission_connection_check', $response_data['code'] );
		static::assertEquals( 401, $response_data['data']['status'] );
	}

	/**
	 * Testing the `remote_connect` endpoint with proper authentication.
	 */
	public function test_connection_check_authenticated() {
		wp_set_current_user( 0 );

		// Mock full connection established.
		add_filter( 'jetpack_options', array( $this, 'mock_jetpack_options' ), 10, 2 );

		$this->mock_signed_post_request_with_blog_token();

		$request = new WP_REST_Request( 'GET', '/jetpack/v4/connection/check' );
		$request->set_header( 'Content-Type', 'application/json' );

		$response      = $this->server->dispatch( $request );
		$response_data = $response->get_data();

		remove_filter( 'jetpack_options', array( $this, 'mock_jetpack_options' ), 10 );

		static::assertEquals( 'success', $response_data['status'] );
	}

	/**
	 * This filter callback allows us to skip the database query by `Jetpack_Options` to retrieve the option.
	 *
	 * @param array $options List of options already skipping the database request.
	 *
	 * @return array
	 */
	public function bypass_raw_options( array $options ) {
		$options[ Secrets::LEGACY_SECRETS_OPTION_NAME ] = true;

		return $options;
	}

	/**
	 * Intercept the `jetpack.register` API request sent to WP.com, and mock the response.
	 *
	 * @param bool|array $response The existing response.
	 * @param array      $args The request arguments.
	 * @param string     $url The request URL.
	 *
	 * @return array
	 */
	public static function intercept_register_request( $response, $args, $url ) {
		if ( ! str_contains( $url, 'jetpack.register' ) ) {
			return $response;
		}

		return self::get_register_request_mock_response();
	}

	/**
	 * Intercept the `jetpack.register` API request sent to WP.com, and mock the response with allow_inplace_authorization as true.
	 *
	 * @param bool|array $response The existing response.
	 * @param array      $args The request arguments.
	 * @param string     $url The request URL.
	 *
	 * @return array
	 */
	public static function intercept_register_request_with_allow_inplace( $response, $args, $url ) {
		if ( ! str_contains( $url, 'jetpack.register' ) ) {
			return $response;
		}

		return self::get_register_request_mock_response( true );
	}

	/**
	 * Intercept the `jetpack.register` API request sent to WP.com, and mock the response with a value in alternate_authorization_url key.
	 *
	 * @param bool|array $response The existing response.
	 * @param array      $args The request arguments.
	 * @param string     $url The request URL.
	 *
	 * @return array
	 */
	public static function intercept_register_request_with_alternate_auth_url( $response, $args, $url ) {
		if ( ! str_contains( $url, 'jetpack.register' ) ) {
			return $response;
		}

		return self::get_register_request_mock_response( false, 'https://dummy.com' );
	}

	/**
	 * Gets a mocked REST response from jetpack.register WPCOM endpoint
	 *
	 * @param boolean $allow_inplace_authorization the value of allow_inplace_authorization returned by the server.
	 * @param string  $alternate_authorization_url the value of alternate_authorization_url returned by the server.
	 * @return array
	 */
	private static function get_register_request_mock_response( $allow_inplace_authorization = false, $alternate_authorization_url = '' ) {
		return array(
			'headers'  => new CaseInsensitiveDictionary( array( 'content-type' => 'application/json' ) ),
			'body'     => wp_json_encode(
				array(
					'jetpack_id'                  => '12345',
					'jetpack_secret'              => 'sample_secret',
					'allow_inplace_authorization' => $allow_inplace_authorization,
					'alternate_authorization_url' => $alternate_authorization_url,
				)
			),
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
		);
	}

	/**
	 * Intercept the `jetpack-token-health` API request sent to WP.com, and mock the "invalid blog token" response.
	 *
	 * @param bool|array $response The existing response.
	 * @param array      $args The request arguments.
	 * @param string     $url The request URL.
	 *
	 * @return array
	 */
	public function intercept_validate_tokens_request_invalid_blog_token( $response, $args, $url ) {
		if ( ! str_contains( $url, 'jetpack-token-health' ) ) {
			return $response;
		}

		return $this->build_validate_tokens_response( 'blog_token' );
	}

	/**
	 * Intercept the `jetpack-token-health` API request sent to WP.com, and mock the "invalid user token" response.
	 *
	 * @param bool|array $response The existing response.
	 * @param array      $args The request arguments.
	 * @param string     $url The request URL.
	 *
	 * @return array
	 */
	public function intercept_validate_tokens_request_invalid_user_token( $response, $args, $url ) {
		if ( ! str_contains( $url, 'jetpack-token-health' ) ) {
			return $response;
		}

		return $this->build_validate_tokens_response( 'user_token' );
	}

	/**
	 * Intercept the `jetpack-token-health` API request sent to WP.com, and mock the "valid tokens" response.
	 *
	 * @param bool|array $response The existing response.
	 * @param array      $args The request arguments.
	 * @param string     $url The request URL.
	 *
	 * @return array
	 */
	public function intercept_validate_tokens_request_valid_tokens( $response, $args, $url ) {
		if ( ! str_contains( $url, 'jetpack-token-health' ) ) {
			return $response;
		}

		return $this->build_validate_tokens_response( null );
	}

	/**
	 * Intercept the `jetpack-token-health` API request sent to WP.com, and mock failed response.
	 *
	 * @param bool|array $response The existing response.
	 * @param array      $args The request arguments.
	 * @param string     $url The request URL.
	 *
	 * @return array
	 */
	public function intercept_validate_tokens_request_failed( $response, $args, $url ) {
		if ( ! str_contains( $url, 'jetpack-token-health' ) ) {
			return $response;
		}

		return array(
			'headers'  => new CaseInsensitiveDictionary( array( 'content-type' => 'application/json' ) ),
			'body'     => wp_json_encode( array( 'dummy_error' => true ) ),
			'response' => array(
				'code'    => 500,
				'message' => 'failed',
			),
		);
	}

	/**
	 * Build the response for a tokens validation request
	 *
	 * @param string $invalid_token Accepted values: 'blog_token', 'user_token'.
	 *
	 * @return array
	 */
	private function build_validate_tokens_response( $invalid_token ) {
		$body = array(
			'blog_token' => array(
				'is_healthy' => true,
			),
			'user_token' => array(
				'is_healthy'     => true,
				'is_master_user' => true,
			),
		);

		switch ( $invalid_token ) {
			case 'blog_token':
				$body['blog_token'] = array(
					'is_healthy' => false,
					'code'       => 'unknown_token',
				);
				break;
			case 'user_token':
				$body['user_token'] = array(
					'is_healthy' => false,
					'code'       => 'unknown_token',
				);
				break;
		}

		return array(
			'headers'  => new CaseInsensitiveDictionary( array( 'content-type' => 'application/json' ) ),
			'body'     => wp_json_encode( $body ),
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
		);
	}

	/**
	 * Intercept the `jetpack-refresh-blog-token` API request sent to WP.com, and mock the success response.
	 *
	 * @param bool|array $response The existing response.
	 * @param array      $args The request arguments.
	 * @param string     $url The request URL.
	 *
	 * @return array
	 */
	public function intercept_refresh_blog_token_request( $response, $args, $url ) {
		if ( ! str_contains( $url, 'jetpack-refresh-blog-token' ) ) {
			return $response;
		}

		return array(
			'headers'  => new CaseInsensitiveDictionary( array( 'content-type' => 'application/json' ) ),
			'body'     => wp_json_encode( array( 'jetpack_secret' => self::BLOG_TOKEN ) ),
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
		);
	}

	/**
	 * Intercept the `jetpack-refresh-blog-token` API request sent to WP.com, and mock the failure response.
	 *
	 * @param bool|array $response The existing response.
	 * @param array      $args The request arguments.
	 * @param string     $url The request URL.
	 *
	 * @return array
	 */
	public function intercept_refresh_blog_token_request_fail( $response, $args, $url ) {
		if ( ! str_contains( $url, 'jetpack-refresh-blog-token' ) ) {
			return $response;
		}

		return array(
			'headers'  => new CaseInsensitiveDictionary( array( 'content-type' => 'application/json' ) ),
			'body'     => wp_json_encode( array( 'jetpack_secret_missing' => true ) ), // Meaningless body.
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
		);
	}

	/**
	 * Intercept the `jetpack-token-health` API request sent to WP.com, and mock the "invalid blog token" response.
	 *
	 * @param bool|array $response The existing response.
	 * @param array      $args The request arguments.
	 * @param string     $url The request URL.
	 *
	 * @return array
	 */
	public function intercept_auth_token_request( $response, $args, $url ) {
		if ( ! str_contains( $url, '/jetpack.token/' ) ) {
			return $response;
		}

		return array(
			'headers'  => new CaseInsensitiveDictionary( array( 'content-type' => 'application/json' ) ),
			'body'     => wp_json_encode(
				array(
					'access_token' => 'mock.token',
					'token_type'   => 'X_JETPACK',
					'scope'        => ( new Manager() )->sign_role( 'administrator' ),
				)
			),
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
		);
	}

	/**
	 * Used to simulate a successful response to any XML-RPC request.
	 * Should be hooked on the `pre_http_request` filter.
	 *
	 * @param false  $preempt A preemptive return value of an HTTP request.
	 * @param array  $args    HTTP request arguments.
	 * @param string $url     The request URL.
	 *
	 * @return WP_REST_Response
	 */
	public function mock_xmlrpc_success( $preempt, $args, $url ) {
		if ( strpos( $url, 'https://jetpack.wordpress.com/xmlrpc.php' ) !== false ) {
			$response = array();

			$response['body'] = '
				<methodResponse>
					<params>
						<param>
							<value>1</value>
						</param>
					</params>
				</methodResponse>
			';

			$response['response']['code'] = 200;
			return $response;
		}

		return $preempt;
	}

	/**
	 * Used to simulate a failed response to any XML-RPC request.
	 * Should be hooked on the `pre_http_request` filter.
	 *
	 * @param false  $preempt A preemptive return value of an HTTP request.
	 * @param array  $args    HTTP request arguments.
	 * @param string $url     The request URL.
	 *
	 * @return WP_REST_Response
	 */
	public function mock_xmlrpc_failure( $preempt, $args, $url ) {
		if ( strpos( $url, 'https://jetpack.wordpress.com/xmlrpc.php' ) !== false ) {
			$response = array();

			$response['body'] = '';

			$response['response']['code'] = 500;
			return $response;
		}

		return $preempt;
	}

	/**
	 * Intercept the `Jetpack_Options` call and mock the values.
	 * Site level / user-less connection set-up.
	 *
	 * @param mixed  $value The current option value.
	 * @param string $name Option name.
	 *
	 * @return mixed
	 */
	public function mock_jetpack_site_connection_options( $value, $name ) {
		switch ( $name ) {
			case 'blog_token':
				return self::BLOG_TOKEN;
			case 'id':
				return self::BLOG_ID;
		}

		return $value;
	}

	/**
	 * Intercept the `Jetpack_Options` call and mock the values.
	 * Full connection set-up.
	 *
	 * @param mixed  $value The current option value.
	 * @param string $name Option name.
	 *
	 * @return mixed
	 */
	public function mock_jetpack_options( $value, $name ) {
		switch ( $name ) {
			case 'blog_token':
				return self::BLOG_TOKEN;
			case 'id':
				return self::BLOG_ID;
			case 'master_user':
				return self::$user_id;
			case 'user_tokens':
				return array(
					self::$user_id           => 'new.usertoken.' . self::$user_id,
					self::$secondary_user_id => 'new2.secondarytoken.' . self::$secondary_user_id,
					self::$non_admin_user_id => 'new3.nonadmintoken.' . self::$non_admin_user_id,
				);
		}

		return $value;
	}

	/**
	 * Intercept the `Jetpack_Options` call and mock the values.
	 * Full connection set-up, but with no connection owner.
	 *
	 * @param mixed  $value The current option value.
	 * @param string $name Option name.
	 *
	 * @return mixed
	 */
	public function mock_jetpack_options_no_connection_owner( $value, $name ) {
		switch ( $name ) {
			case 'blog_token':
				return self::BLOG_TOKEN;
			case 'id':
				return self::BLOG_ID;
			case 'user_tokens':
				return array(
					self::$non_admin_user_id => 'new3.nonadmintoken.' . self::$non_admin_user_id,
				);
		}

		return $value;
	}

	/**
	 * Build the `connection/reconnect` request object.
	 *
	 * @return WP_REST_Request
	 */
	private function build_reconnect_request() {
		$request = new WP_REST_Request( 'POST', '/jetpack/v4/connection/reconnect' );
		$request->set_header( 'Content-Type', 'application/json' );

		return $request;
	}

	/**
	 * Setup the environment to test the reconnection process.
	 *
	 * @param string|null $invalid_token The invalid token to be returned in the response. Null if the tokens should be valid.
	 */
	private function setup_reconnect_test( $invalid_token ) {
		switch ( $invalid_token ) {
			case 'blog_token':
				add_filter(
					'pre_http_request',
					array(
						$this,
						'intercept_validate_tokens_request_invalid_blog_token',
					),
					10,
					3
				);
				break;
			case 'user_token':
				add_filter(
					'pre_http_request',
					array(
						$this,
						'intercept_validate_tokens_request_invalid_user_token',
					),
					10,
					3
				);
				break;
			case 'token_validation_failed':
				add_filter(
					'pre_http_request',
					array(
						$this,
						'intercept_validate_tokens_request_failed',
					),
					10,
					3
				);
				break;
			case null:
				add_filter(
					'pre_http_request',
					array(
						$this,
						'intercept_validate_tokens_request_valid_tokens',
					),
					10,
					3
				);
				break;
		}

		add_filter( 'jetpack_options', array( $this, 'mock_jetpack_options' ), 10, 2 );
		$this->reset_connection_status();
	}

	/**
	 * Restore the environment after the `reconnect` test has been run.
	 *
	 * @param string|null $invalid_token The invalid token to be returned in the response. Null if the tokens should be valid.
	 */
	private function shutdown_reconnect_test( $invalid_token ) {
		switch ( $invalid_token ) {
			case 'blog_token':
				remove_filter(
					'pre_http_request',
					array(
						$this,
						'intercept_validate_tokens_request_invalid_blog_token',
					),
					10
				);
				break;
			case 'user_token':
				remove_filter(
					'pre_http_request',
					array(
						$this,
						'intercept_validate_tokens_request_invalid_user_token',
					),
					10
				);
				break;
			case 'token_validation_failed':
				remove_filter(
					'pre_http_request',
					array(
						$this,
						'intercept_validate_tokens_request_failed',
					),
					10
				);
				break;
			case null:
				remove_filter(
					'pre_http_request',
					array(
						$this,
						'intercept_validate_tokens_request_valid_tokens',
					),
					10
				);
				break;
		}

		remove_filter( 'jetpack_options', array( $this, 'mock_jetpack_options' ), 10 );
		$this->reset_connection_status();
	}

	private function mock_signed_post_request_with_blog_token() {
		$token     = 'new:1:0';
		$timestamp = (string) time();
		$nonce     = 'testing123';
		$body_hash = '';

		$_SERVER['REQUEST_METHOD'] = 'POST';

		$_GET['_for']      = 'jetpack';
		$_GET['token']     = $token;
		$_GET['timestamp'] = $timestamp;
		$_GET['nonce']     = $nonce;
		$_GET['body-hash'] = $body_hash;
		$_GET['signature'] = base64_encode(
			hash_hmac(
				'sha1',
				implode(
					"\n",
					$data  = array(
						$token,
						$timestamp,
						$nonce,
						$body_hash,
						'POST',
						'anything.example',
						'80',
						'',
					)
				) . "\n",
				'blogtoken',
				true
			)
		);

		Connection_Rest_Authentication::init()->wp_rest_authenticate( false );
	}
}
