<?php
/**
 * Tests the Module Override functionality.
 *
 * @package automattic/jetpack
 */

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests the Module Override functionality.
 *
 * @covers \Jetpack_Modules_Overrides
 */
#[CoversClass( Jetpack_Modules_Overrides::class )]
class Jetpack_Modules_Overrides_Test extends WP_UnitTestCase {
	use \Automattic\Jetpack\PHPUnit\WP_UnitTestCase_Fix;

	/**
	 * Holder for the module override instance.
	 *
	 * @var Jetpack_Modules_Overrides
	 */
	private $instance = null;

	/**
	 * Test setup.
	 */
	public function set_up() {
		parent::set_up();
		$this->instance = Jetpack_Modules_Overrides::instance();
	}

	/**
	 * Test tear down.
	 */
	public function tear_down() {
		remove_all_filters( 'option_jetpack_active_modules' );
		remove_all_filters( 'jetpack_active_modules' );
		$this->instance->clear_cache();
		parent::tear_down();
	}

	/**
	 * Tests that an override exists.
	 *
	 * @param string $filter_name Filter to test against.
	 *
	 * @dataProvider get_supported_filters
	 */
	#[DataProvider( 'get_supported_filters' )]
	public function test_do_overrides_exist( $filter_name ) {
		if ( defined( 'IS_ATOMIC' ) && IS_ATOMIC ) {
			$this->markTestSkipped( 'is irrelevant because there are already overrides on tested filters in Atomic.' );
		}
		$this->assertFalse( $this->instance->do_overrides_exist() );

		add_filter( $filter_name, '__return_true' );
		$this->assertTrue( $this->instance->do_overrides_exist() );
		remove_filter( $filter_name, '__return_true' );
	}

	/**
	 * Tests getting the list of overrides.
	 *
	 * @param string $filter_name Filter to test against.
	 *
	 * @dataProvider get_supported_filters
	 */
	#[DataProvider( 'get_supported_filters' )]
	public function test_get_overrides( $filter_name ) {
		$this->assertEmpty( $this->instance->get_overrides() );

		add_filter( $filter_name, array( $this, 'force_active_modules' ) );
		$expected = array(
			'photon'     => 'active',
			'photon-cdn' => 'active',
		);
		$this->assertSame( $expected, $this->instance->get_overrides( false ) );

		add_filter( $filter_name, array( $this, 'force_inactive_module' ) );
		$expected = array(
			'photon'     => 'active',
			'photon-cdn' => 'active',
			'sitemaps'   => 'inactive',
		);
		$this->assertSame( $expected, $this->instance->get_overrides( false ) );

		remove_filter( $filter_name, array( $this, 'force_active_modules' ) );

		$expected = array(
			'sitemaps' => 'inactive',
		);
		$this->assertSame( $expected, $this->instance->get_overrides( false ) );

		remove_filter( $filter_name, array( $this, 'force_inactive_module' ) );

		$this->assertEmpty( $this->instance->get_overrides( false ) );
	}

	/**
	 * Tets the override cache.
	 *
	 * @param string $filter_name Filter to test against.
	 *
	 * @dataProvider get_supported_filters
	 */
	#[DataProvider( 'get_supported_filters' )]
	public function test_get_overrides_cache( $filter_name ) {
		$this->assertEmpty( $this->instance->get_overrides() );

		add_filter( $filter_name, array( $this, 'force_active_modules' ) );
		$expected = array(
			'photon'     => 'active',
			'photon-cdn' => 'active',
		);

		if ( defined( 'IS_ATOMIC' ) && IS_ATOMIC ) {

			// Atomic does not override either.
			$expected = array();
		}
		$this->assertSame( $expected, $this->instance->get_overrides() );

		add_filter( $filter_name, array( $this, 'force_inactive_module' ) );

		$this->assertSame( $expected, $this->instance->get_overrides() );
	}

	/**
	 * Tests get_module_override.
	 *
	 * @param string $filter_name Filter to test against.
	 *
	 * @dataProvider get_supported_filters
	 */
	#[DataProvider( 'get_supported_filters' )]
	public function test_get_module_override( $filter_name ) {
		if ( defined( 'IS_ATOMIC' ) && IS_ATOMIC ) {
			$this->markTestSkipped( 'is unnecessary in Atomic environment.' );
		}

		$this->assertFalse( $this->instance->get_module_override( 'photon' ) );
		$this->assertFalse( $this->instance->get_module_override( 'photon-cdn' ) );
		$this->assertFalse( $this->instance->get_module_override( 'sitemaps' ) );

		add_filter( $filter_name, array( $this, 'force_active_modules' ) );
		add_filter( $filter_name, array( $this, 'force_inactive_module' ) );

		$this->assertSame( 'active', $this->instance->get_module_override( 'photon' ) );
		$this->assertSame( 'active', $this->instance->get_module_override( 'photon-cdn' ) );
		$this->assertSame( 'inactive', $this->instance->get_module_override( 'sitemaps' ) );
	}

	/**
	 * Helpers
	 */

	/**
	 * Helper to force active Photon and the Asset CDN.
	 *
	 * @param array $modules Jetpack modules.
	 *
	 * @return array Jetpack modules.
	 */
	public function force_active_modules( $modules ) {
		return array_merge( $modules, array( 'photon', 'photon-cdn' ) );
	}

	/**
	 * Helper to force the `sitemaps` module as inactive.
	 *
	 * @param array $modules Jetpack modules.
	 *
	 * @return array Jetpack modules.
	 */
	public function force_inactive_module( $modules ) {
		$found = array_search( 'sitemaps', $modules, true );
		if ( $found ) {
			unset( $modules[ $found ] );
		}

		return $modules;
	}

	/**
	 * Helper to get supported filters.
	 */
	public static function get_supported_filters() {
		return array(
			'option_jetpack_active_modules' => array( // Case for filtering the option via core filter.
				'option_jetpack_active_modules',
			),
			'jetpack_active_modules'        => array( // Case for filtering using Jetpack filter.
				'jetpack_active_modules',
			),
		);
	}
}
