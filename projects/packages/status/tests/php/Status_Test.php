<?php
/**
 * Tests for Automattic\Jetpack\Status methods
 *
 * @package automattic/jetpack-status
 */

namespace Automattic\Jetpack;

use Brain\Monkey;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

/**
 * Status test suite.
 *
 * @covers \Automattic\Jetpack\Status
 */
#[CoversClass( Status::class )]
class Status_Test extends TestCase {
	/**
	 * Default site URL.
	 *
	 * @var string
	 */
	private $site_url = 'https://yourjetpack.blog';

	/**
	 * Status instance.
	 *
	 * @var \Automattic\Jetpack\Status
	 */
	private $status_obj;

	/**
	 * Mocked constants.
	 *
	 * @var array
	 */
	private $mocked_constants = array();

	/**
	 * Setup before running any of the tests.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
			define( 'HOUR_IN_SECONDS', 60 * 60 );
		}
	}

	/**
	 * Test setup.
	 */
	public function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Set defaults for Core functionality.
		Functions\when( 'get_current_blog_id' )->justReturn( 1 );
		Functions\expect( 'site_url' )->atMost()->once()->andReturnUsing(
			function () {
				return $this->site_url;
			}
		);
		Functions\when( 'wp_get_environment_type' )->justReturn( 'production' );
		Functions\when( 'wp_parse_url' )->alias( 'parse_url' );
		Functions\expect( 'defined' )->andReturnUsing(
			function ( $const ) {
				return array_key_exists( $const, $this->mocked_constants ) ? true : defined( $const );
			}
		);
		Functions\expect( 'constant' )->andReturnUsing(
			function ( $const ) {
				return array_key_exists( $const, $this->mocked_constants ) ? $this->mocked_constants[ $const ] : constant( $const );
			}
		);

		// Alias-mock Identity_Crisis so the tests don't try to call into it.
		\Mockery::mock( 'alias:Automattic\\Jetpack\\Identity_Crisis' );

		Status\Cache::clear();
		$this->status_obj = new Status();
	}

	/**
	 * Test teardown.
	 */
	public function tearDown(): void {
		parent::tearDown();
		Monkey\tearDown();
		Status\Cache::clear();
	}

	/**
	 * Test is_offline_mode when not using any filter
	 */
	public function test_is_offline_mode_default() {
		Functions\expect( 'get_option' )->once()->with( 'jetpack_offline_mode' )->andReturn( false );
		Filters\expectApplied( 'jetpack_offline_mode' )->once()->with( false )->andReturn( false );

		$this->assertFalse( $this->status_obj->is_offline_mode() );
	}

	/**
	 * Test is_offline_mode when using the jetpack_offline_mode filter
	 */
	public function test_is_offline_mode_filter_true() {
		Functions\expect( 'get_option' )->never();
		Filters\expectApplied( 'jetpack_offline_mode' )->once()->with( false )->andReturn( true );

		$this->assertTrue( $this->status_obj->is_offline_mode() );
	}

	/**
	 * Test when using a bool value for the jetpack_offline_mode filter.
	 */
	public function test_is_offline_mode_filter_bool() {
		Functions\expect( 'get_option' )->once()->with( 'jetpack_offline_mode' )->andReturn( false );
		Filters\expectApplied( 'jetpack_offline_mode' )->once()->with( false )->andReturn( 0 );

		$this->assertFalse( $this->status_obj->is_offline_mode() );
	}

	/**
	 * Test when site url is localhost (dev mode on)
	 */
	public function test_is_offline_mode_localhost() {
		$this->site_url = 'localhost';

		Functions\expect( 'get_option' )->never();
		Filters\expectApplied( 'jetpack_offline_mode' )->once()->with( true )->andReturn( true );

		$this->assertTrue( $this->status_obj->is_offline_mode() );
	}

	/**
	 * Test when wp_get_environment_type is local.
	 */
	public function test_is_local_wp_get_environment_type_local() {
		Functions\when( 'wp_get_environment_type' )->justReturn( 'local' );

		Filters\expectApplied( 'jetpack_is_local_site' )->once()->with( true )->andReturn( true );

		$this->assertTrue( $this->status_obj->is_local_site() );
	}

	/**
	 * Test when wp_get_environment_type is local.
	 */
	public function test_is_staging_wp_get_environment_type_local() {
		Functions\when( 'wp_get_environment_type' )->justReturn( 'local' );

		Filters\expectApplied( 'jetpack_is_development_site' )->once()->with( false )->andReturn( false );

		$this->assertFalse( $this->status_obj->is_development_site() );
	}

	/**
	 * Test when wp_get_environment_type is staging.
	 */
	public function test_is_staging_wp_get_environment_type_staging() {
		Functions\when( 'wp_get_environment_type' )->justReturn( 'staging' );

		Filters\expectApplied( 'jetpack_is_development_site' )->once()->with( true )->andReturn( true );

		$this->assertTrue( $this->status_obj->is_development_site() );
	}

	/**
	 * Test when wp_get_environment_type is production.
	 */
	public function test_is_staging_wp_get_environment_type_production() {
		Functions\when( 'wp_get_environment_type' )->justReturn( 'production' );

		Filters\expectApplied( 'jetpack_is_development_site' )->once()->with( false )->andReturn( false );

		$this->assertFalse( $this->status_obj->is_development_site() );
	}

	/**
	 * Test when wp_get_environment_type is a random value.
	 */
	public function test_is_staging_wp_get_environment_type_random() {
		Functions\when( 'wp_get_environment_type' )->justReturn( 'random_string' );

		Filters\expectApplied( 'jetpack_is_development_site' )->once()->with( true )->andReturn( true );

		$this->assertTrue( $this->status_obj->is_development_site() ); // We assume a site is a staging site for any non-local or non-production value.
	}

	/**
	 * Test when using the constant to set dev mode
	 *
	 * @runInSeparateProcess
	 */
	#[RunInSeparateProcess]
	public function test_is_offline_mode_constant() {
		Functions\expect( 'get_option' )->never();
		Filters\expectApplied( 'jetpack_offline_mode' )->once()->with( true )->andReturn( true );
		$this->mocked_constants['\\JETPACK_DEV_DEBUG'] = true;

		$this->assertTrue( $this->status_obj->is_offline_mode() );
	}

	/**
	 * Test that `is_offline_mode()` returns true when the `jetpack_offline_mode` option is set.
	 */
	public function test_is_offline_mode_option() {
		Functions\expect( 'get_option' )->once()->with( 'jetpack_offline_mode' )->andReturn( '1' );

		$this->assertTrue( $this->status_obj->is_offline_mode() );
	}

	/**
	 * Test that `is_offline_mode()` returns false when the `jetpack_offline_mode` option exists, but set to '0'.
	 */
	public function test_is_offline_mode_option_inactive() {
		Functions\expect( 'get_option' )->once()->with( 'jetpack_offline_mode' )->andReturn( '0' );

		$this->assertFalse( $this->status_obj->is_offline_mode() );
	}

	/**
	 * Test for is_multi_network with a single site
	 */
	public function test_is_multi_network_not_multisite() {
		Functions\when( 'is_multisite' )->justReturn( false );

		$this->assertFalse( $this->status_obj->is_multi_network() );
	}

	/**
	 * Test is_multi_network with a multisite install
	 */
	public function test_is_multi_network_when_single_network() {
		$this->mock_wpdb_get_var( 1 );
		Functions\when( 'is_multisite' )->justReturn( true );

		$this->assertFalse( $this->status_obj->is_multi_network() );

		$this->clean_mock_wpdb_get_var();
	}

	/**
	 * Test is_multi_network when multiple networks
	 */
	public function test_is_multi_network_when_multiple_networks() {
		$this->mock_wpdb_get_var( 2 );
		Functions\when( 'is_multisite' )->justReturn( true );

		$this->assertTrue( $this->status_obj->is_multi_network() );

		$this->clean_mock_wpdb_get_var();
	}

	/**
	 * Test cached is_single_user_site
	 */
	public function test_is_single_user_site_with_transient() {
		$this->mock_wpdb_get_var( 3 );
		Functions\when( 'get_transient' )->justReturn( 1 );

		$this->assertTrue( $this->status_obj->is_single_user_site() );

		$this->clean_mock_wpdb_get_var();
	}

	/**
	 * Test is_single_user_site
	 */
	public function test_is_single_user_site_with_one_user() {
		$this->mock_wpdb_get_var( 1 );
		Functions\when( 'get_transient' )->justReturn( false );
		Functions\when( 'set_transient' )->justReturn( true );

		$this->assertTrue( $this->status_obj->is_single_user_site() );

		$this->clean_mock_wpdb_get_var();
	}

	/**
	 * Test is_single_user_site with multiple users
	 */
	public function test_is_single_user_site_with_multiple_users() {
		$this->mock_wpdb_get_var( 3 );
		Functions\when( 'get_transient' )->justReturn( false );
		Functions\when( 'set_transient' )->justReturn( true );

		$this->assertFalse( $this->status_obj->is_single_user_site() );

		$this->clean_mock_wpdb_get_var();
	}

	/**
	 * Mock $wpdb->get_var() and make it return a certain value.
	 *
	 * @param mixed $return_value  Return value of the function.
	 */
	protected function mock_wpdb_get_var( $return_value = null ) {
		global $wpdb;

		$wpdb = new class( $return_value ) {
			public $prefix   = 'wp_';
			public $site     = 'wp_site';
			public $usermeta = 'wp_usermeta';

			private $return_value;

			public function __construct( $return_value ) {
				$this->return_value = $return_value;
			}

			public function get_var() {
				return $this->return_value;
			}
		};
	}

	/**
	 * Clean up the existing $wpdb->get_var() mock.
	 */
	protected function clean_mock_wpdb_get_var() {
		global $wpdb;
		unset( $wpdb );
	}

	/**
	 * Tests known local development sites.
	 *
	 * @dataProvider get_is_local_site_known_tld
	 *
	 * @param string $site_url Site URL.
	 * @param bool   $expected_response Expected response.
	 */
	#[DataProvider( 'get_is_local_site_known_tld' )]
	public function test_is_local_site_for_known_tld( $site_url, $expected_response ) {
		$this->site_url = $site_url;
		$result         = $this->status_obj->is_local_site();
		$this->assertEquals(
			$expected_response,
			$result,
			sprintf(
				'Expected %1$s to return %2$s for is_local_site()',
				$site_url,
				$expected_response
			)
		);
	}

	/**
	 * Known hosting providers.
	 *
	 * @return array
	 */
	public static function get_is_local_site_known_tld() {
		return array(
			'vvv'            => array(
				'http://jetpack.test',
				true,
			),
			'docksal'        => array(
				'http://jetpack.docksal',
				true,
			),
			'serverpress'    => array(
				'http://jetpack.dev.cc',
				true,
			),
			'lando'          => array(
				'http://jetpack.lndo.site',
				true,
			),
			'test_subdomain' => array(
				'https://test.jetpack.com',
				false,
			),
			'test_in_domain' => array(
				'https://jetpack.test.jetpack.com',
				false,
			),
		);
	}

	/**
	 * Tests for site_suffix().
	 *
	 * @dataProvider get_site_suffix_examples
	 *
	 * @param string $site     Given site URL.
	 * @param string $expected Site suffix.
	 */
	#[DataProvider( 'get_site_suffix_examples' )]
	public function test_jetpack_get_site_suffix( $site, $expected ) {
		Functions\when( 'home_url' )->justReturn( $this->site_url );
		Functions\when( 'get_option' )->justReturn();
		$suffix = $this->status_obj->get_site_suffix( $site );

		$this->assertSame( $expected, $suffix );
	}

	/**
	 * Examples of sites passed to get_site_suffix
	 */
	public static function get_site_suffix_examples() {
		return array(
			'no_site_home_url' => array(
				'',
				'yourjetpack.blog',
			),
			'tld'              => array(
				'https://example.org',
				'example.org',
			),
			'subdomain'        => array(
				'https://borussia.dortmund.example.org',
				'borussia.dortmund.example.org',
			),
			'subfolder'        => array(
				'https://example.org/borussia-dortmund',
				'example.org::borussia-dortmund',
			),
			'ip'               => array(
				'127.0.0.1',
				'127.0.0.1',
			),
			'no_tld'           => array(
				'https://localhost',
				'localhost',
			),
			'double_domain'    => array(
				'https://example.org/http://example.com',
				'example.org::http:::::example.com',
			),
			'trailing_slash'   => array(
				'https://example.org/',
				'example.org',
			),
		);
	}

	/**
	 * Test result is cached.
	 *
	 * @dataProvider provide_cached
	 * @param string      $func Function being tested.
	 * @param string|null $one_call Method that should be called only once.
	 * @param string|null $one_filter Filter that should be called only once.
	 */
	#[DataProvider( 'provide_cached' )]
	public function test_cached( $func, $one_call, $one_filter ) {
		if ( $one_call ) {
			Functions\expect( $one_call )->once();
		}

		$ret = $this->status_obj->$func();
		$this->assertSame( $ret, $this->status_obj->$func() );

		if ( $one_filter ) {
			$this->assertSame( 1, Filters\applied( $one_filter ), "Filter $one_filter was only applied once" );
		}
	}

	/** Data provider for test_cached */
	public static function provide_cached() {
		return array(
			array( 'is_offline_mode', 'get_option', 'jetpack_offline_mode' ),
			array( 'is_multi_network', 'is_multisite', null ),
			array( 'is_single_user_site', 'get_transient', null ),
			array( 'is_local_site', null, 'jetpack_is_local_site' ),
		);
	}

	/**
	 * Test that is_private_site returns true when get_option is set to -1.
	 */
	public function test_is_private_site() {
		Functions\when( 'get_option' )->justReturn( '-1' );

		$this->assertTrue( $this->status_obj->is_private_site() );
	}

	/**
	 * Test that is_coming_soon returns true when a site is set to coming soon.
	 *
	 * @dataProvider get_coming_soon_status
	 *
	 * @param bool $site_is_coming_soon      Site is coming soon value.
	 * @param int  $wpcom_public_coming_soon wpcom_public_coming_soon option value.
	 * @param bool $expected                 Expected result.
	 */
	#[DataProvider( 'get_coming_soon_status' )]
	public function test_is_coming_soon( $site_is_coming_soon, $wpcom_public_coming_soon, $expected ) {
		Functions\when( 'site_is_coming_soon' )->justReturn( $site_is_coming_soon );
		Functions\when( 'get_option' )->justReturn( $wpcom_public_coming_soon );
		$this->assertSame( $expected, $this->status_obj->is_coming_soon() );
	}

	/**
	 * Mock data for test_is_coming_soon
	 *
	 * @return array
	 */
	public static function get_coming_soon_status() {
		return array(
			'Jetpack public site'       => array( null, false, false ),
			'WoA public site'           => array( false, false, false ),
			'WoA private site'          => array( true, false, true ),
			'wpcom simple private site' => array( null, true, true ),
		);
	}
}
