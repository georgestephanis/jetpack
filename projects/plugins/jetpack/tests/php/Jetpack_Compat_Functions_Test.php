<?php
/**
 * Test the compat file.
 *
 * @package Automattic/jetpack
 */

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Testing class.
 */
class Jetpack_Compat_Functions_Test extends WP_UnitTestCase {
	use \Automattic\Jetpack\PHPUnit\WP_UnitTestCase_Fix;

	/**
	 * @dataProvider provider_wp_startswith
	 */
	#[DataProvider( 'provider_wp_startswith' )]
	public function test_wp_startswith( $haystack, $needle, $expected ) {
		$this->assertEquals( $expected, wp_startswith( $haystack, $needle ) );
	}

	public static function provider_wp_startswith() {
		return array(
			array( 'Random String', 'Random', true ), // Regular usage.
			array( '12345', 123, true ), // Passing an int as the needle is deprecated, but previously supported.
			array( 'Random String', 'random', false ), // case-sensitive.
			array( 'Random String', 'string', false ), // Nope.
			array( null, 'string', false ),
			array( array( 'random' ), 'string', false ),
		);
	}

	/**
	 * @dataProvider provider_wp_endswith
	 */
	#[DataProvider( 'provider_wp_endswith' )]
	public function test_wp_endswith( $haystack, $needle, $expected ) {
		$this->assertEquals( $expected, wp_endswith( $haystack, $needle ) );
	}

	public static function provider_wp_endswith() {
		return array(
			array( 'Random String', 'String', true ), // Regular usage.
			array( '12345', 45, true ), // Passing an int as the needle is deprecated, but previously supported.
			array( 'Random String', 'string', false ), // case-sensitive.
			array( 'Random String', 'Random', false ), // Nope.
			array( null, 'string', false ),
			array( array( 'random' ), 'string', false ),
		);
	}

	/**
	 * @dataProvider provider_wp_in
	 */
	#[DataProvider( 'provider_wp_in' )]
	public function test_wp_in( $haystack, $needle, $expected ) {
		$this->assertEquals( $expected, wp_in( $needle, $haystack ) );
	}

	public static function provider_wp_in() {
		// Notice this is in the $haystack, $needle format, even though this function is $needle, $haystack.
		return array(
			array( 'Random String', 'dom', true ), // Regular usage.
			array( '12345', 23, true ), // Passing an int as the needle is deprecated, but previously supported.
			array( 'Random String', 'str', false ), // case-sensitive.
			array( 'Random String', 'Bananas', false ), // Nope.
			array( null, 'string', false ),
			array( array( 'random' ), 'string', false ),
		);
	}
}
