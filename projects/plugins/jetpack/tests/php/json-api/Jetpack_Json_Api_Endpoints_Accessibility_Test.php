<?php
/**
 * Generic tests for Jetpack_JSON_API_Endpoint accessibility.
 *
 * @package automattic/jetpack
 *
 * @phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound
 */

use Automattic\Jetpack\Status\Cache as StatusCache;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

if ( defined( 'JETPACK__PLUGIN_DIR' ) && JETPACK__PLUGIN_DIR ) {
	require_once JETPACK__PLUGIN_DIR . 'modules/module-extras.php';
}

require_once JETPACK__PLUGIN_DIR . 'class.json-api-endpoints.php';

/**
 * Generic tests for Jetpack_JSON_API_Endpoint accessibility.
 *
 * @covers \Jetpack_JSON_API_Endpoint
 * @covers \WPCOM_JSON_API
 * @covers \WPCOM_JSON_API_Endpoint
 */
#[CoversClass( Jetpack_JSON_API_Endpoint::class )]
#[CoversClass( WPCOM_JSON_API::class )]
#[CoversClass( WPCOM_JSON_API_Endpoint::class )]
class Jetpack_Json_Api_Endpoints_Accessibility_Test extends WP_UnitTestCase {
	use \Automattic\Jetpack\PHPUnit\WP_UnitTestCase_Fix;

	/**
	 * An admin user_id.
	 *
	 * @var int $admin_user_id.
	 */
	private static $admin_user_id;
	/**
	 * The user_id of a user without read capabilities.
	 *
	 * @var int $no_read_user_id.
	 */
	private static $no_read_user_id;

	/**
	 * Create fixtures once, before any tests in the class have run.
	 *
	 * @param object $factory A factory object needed for creating fixtures.
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		self::$admin_user_id   = $factory->user->create( array( 'role' => 'administrator' ) );
		self::$no_read_user_id = $factory->user->create();

		$no_read_user = get_user_by( 'id', self::$no_read_user_id );
		$no_read_user->add_cap( 'read', false );
	}

	/**
	 * Inserts globals needed to initialize the endpoint.
	 */
	private function set_globals() {
		$_SERVER['REQUEST_METHOD'] = 'Get';
		$_SERVER['HTTP_HOST']      = '127.0.0.1';
		$_SERVER['REQUEST_URI']    = '/';
	}

	/**
	 *  Called before every test.
	 */
	public function set_up() {
		parent::set_up();

		global $blog_id;

		if ( ! defined( 'WPCOM_JSON_API__BASE' ) ) {
			define( 'WPCOM_JSON_API__BASE', 'public-api.wordpress.com/rest/v1' );
		}

		$this->set_globals();

		// Initialize some missing stuff for the API.
		WPCOM_JSON_API::init()->token_details = array( 'blog_id' => $blog_id );
	}

	/**
	 * Tests accepts_site_based_authentication method.
	 *
	 * @author fgiannar
	 * @group json-api
	 * @dataProvider data_provider_test_accepts_site_based_authentication
	 *
	 * @param bool $allow_jetpack_site_auth The endpoint's `allow_jetpack_site_auth` value.
	 * @param bool $is_user_logged_in If a user is logged in.
	 * @param bool $result The expected result.
	 */
	#[Group( 'json-api' )]
	#[DataProvider( 'data_provider_test_accepts_site_based_authentication' )]
	public function test_accepts_site_based_authentication( $allow_jetpack_site_auth, $is_user_logged_in, $result ) {

		$endpoint = new Jetpack_JSON_API_Dummy_Endpoint(
			array(
				'stat'                    => 'dummy',
				'allow_jetpack_site_auth' => $allow_jetpack_site_auth,
			)
		);

		if ( $is_user_logged_in ) {
			wp_set_current_user( self::$admin_user_id );
		}

		$this->assertEquals( $result, $endpoint->accepts_site_based_authentication() );
	}

	/**
	 * Tests api accessibility on a private site.
	 *
	 * @author fgiannar
	 * @group json-api
	 * @dataProvider data_provider_test_private_site_accessibility
	 *
	 * @param bool            $allow_jetpack_site_auth The endpoint's `allow_jetpack_site_auth` value.
	 * @param bool            $use_blog_token If we should simulate a blog token for this test.
	 * @param bool            $user_can_read If the current user has read capability. When a blog token is used this has no effect.
	 * @param WP_Error|string $result The expected result.
	 */
	#[Group( 'json-api' )]
	#[DataProvider( 'data_provider_test_private_site_accessibility' )]
	public function test_private_site_accessibility( $allow_jetpack_site_auth, $use_blog_token, $user_can_read, $result ) {
		StatusCache::clear();
		// Private site.
		add_filter( 'jetpack_is_private_site', '__return_true' );
		update_option( 'blog_public', '-1' );

		$endpoint = new Jetpack_JSON_API_Dummy_Endpoint(
			array(
				'stat'                    => 'dummy',
				'allow_jetpack_site_auth' => $allow_jetpack_site_auth,
			)
		);

		if ( ! $use_blog_token ) {
			$user_id = $user_can_read ? self::$admin_user_id : self::$no_read_user_id;
			wp_set_current_user( $user_id );
		}
		$this->assertEquals( $result, $endpoint->api->process_request( $endpoint, array() ) );

		remove_filter( 'jetpack_is_private_site', '__return_true' );
		StatusCache::clear();
	}

	/**
	 * Tests endpoint capabilities.
	 *
	 * @author fgiannar
	 * @group json-api
	 * @dataProvider data_provider_test_endpoint_capabilities
	 *
	 * @param bool            $allow_jetpack_site_auth The endpoint's `allow_jetpack_site_auth` value.
	 * @param bool            $use_blog_token If we should simulate a blog token for this test.
	 * @param bool            $user_with_permissions If the current user has the needed capabilities to access the endpoint. When a blog token is used this has no effect.
	 * @param WP_Error|string $result The expected result.
	 */
	#[Group( 'json-api' )]
	#[DataProvider( 'data_provider_test_endpoint_capabilities' )]
	public function test_endpoint_capabilities( $allow_jetpack_site_auth, $use_blog_token, $user_with_permissions, $result ) {
		$endpoint = new Jetpack_JSON_API_Dummy_Endpoint(
			array(
				'stat'                    => 'dummy',
				'allow_jetpack_site_auth' => $allow_jetpack_site_auth,
			)
		);

		if ( ! $use_blog_token ) {
			$user_id = $user_with_permissions ? self::$admin_user_id : self::$no_read_user_id;
			wp_set_current_user( $user_id );
		}
		$this->assertEquals( $result, $endpoint->api->process_request( $endpoint, array() ) );
	}

	/**
	 * Data provider for test_accepts_site_based_authentication.
	 */
	public static function data_provider_test_accepts_site_based_authentication() {
		return array(
			'allow_jetpack_site_auth: true; logged_in_user: false;'  => array( true, false, true ),
			'allow_jetpack_site_auth: false; logged_in_user: false;' => array( false, false, false ),
			'allow_jetpack_site_auth: true; logged_in_user: true;'   => array( true, true, false ),
		);
	}

	/**
	 * Data provider for test_private_site_accessibility.
	 */
	public static function data_provider_test_private_site_accessibility() {
		$success = 'success';
		$error   = new WP_Error( 'unauthorized', 'User cannot access this private blog.', 403 );

		return array(
			'allow_jetpack_site_auth: true; blog_token: true; can_read: null'   => array( true, true, null, $success ),
			'allow_jetpack_site_auth: false; blog_token: true; can_read: null'   => array( false, true, null, $error ),
			'allow_jetpack_site_auth: false; blog_token: false; can_read: false'   => array( false, false, false, $error ),
			'allow_jetpack_site_auth: false; blog_token: false; can_read: true'   => array( false, false, true, $success ),
		);
	}

	/**
	 * Data provider for test_endpoint_capabilities.
	 */
	public static function data_provider_test_endpoint_capabilities() {
		$success = 'success';
		$error   = new WP_Error( 'unauthorized', 'This user is not authorized to manage_options on this blog.', 403 );

		return array(
			'allow_jetpack_site_auth: true; blog_token: true; user_with_permissions: null'   => array( true, true, null, $success ),
			'allow_jetpack_site_auth: false; blog_token: true; user_with_permissions: null'   => array( false, true, null, $error ),
			'allow_jetpack_site_auth: false; blog_token: false; user_with_permissions: false'   => array( false, false, false, $error ),
			'allow_jetpack_site_auth: false; blog_token: false; user_with_permissions: true'   => array( false, false, true, $success ),
		);
	}
}

/**
 * Dummy endpoint for testing.
 */
class Jetpack_JSON_API_Dummy_Endpoint extends Jetpack_JSON_API_Endpoint {
	/**
	 * Only accessible to admins.
	 *
	 * @var array|string
	 */
	protected $needed_capabilities = 'manage_options';

	/**
	 * Dummy result.
	 */
	public function result() {

		return 'success';
	}
}
