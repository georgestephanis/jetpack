<?php

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__ . '/trait.http-request-cache.php';

/**
 * @covers ::jetpack_googlemaps_shortcode
 */
#[CoversFunction( 'jetpack_googlemaps_shortcode' )]
class Jetpack_Shortcodes_Googlemaps_Test extends WP_UnitTestCase {
	use \Automattic\Jetpack\PHPUnit\WP_UnitTestCase_Fix;
	use Automattic\Jetpack\Tests\HttpRequestCacheTrait;

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_shortcodes_googlemaps_exists() {
		$this->assertTrue( shortcode_exists( 'googlemaps' ) );
	}

	/**
	 * Gets the test data for test_shortcodes_googlemaps().
	 *
	 * @return array The test data.
	 */
	public static function get_shortcode_googlemaps_data() {
		return array(
			'non_amp'         => array(
				'[googlemaps https://mapsengine.google.com/map/embed?mid=zbBhkou4wwtE.kUmp8K6QJ7SA&amp;w=640&amp;h=480]',
				true,
				'<div class="googlemaps"><iframe width="640" height="480" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" sandbox="allow-popups allow-scripts allow-same-origin" src="https://mapsengine.google.com/map/embed?mid=zbBhkou4wwtE.kUmp8K6QJ7SA"></iframe></div>',
			),
			'amp'             => array(
				'[googlemaps https://mapsengine.google.com/map/embed?mid=zbBhkou4wwtE.kUmp8K6QJ7SA&amp;w=640&amp;h=480]',
				false,
				'<div class="googlemaps"><iframe width="640" height="480" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="https://mapsengine.google.com/map/embed?mid=zbBhkou4wwtE.kUmp8K6QJ7SA"></iframe></div>',
			),
			'align_attribute' => array(
				'[googlemaps https://mapsengine.google.com/map/embed?mid=zbBhkou4wwtE.kUmp8K6QJ7SA align="center"]',
				false,
				'<div class="googlemaps aligncenter"><iframe width="425" height="350" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="https://mapsengine.google.com/map/embed?mid=zbBhkou4wwtE.kUmp8K6QJ7SA"></iframe></div>',
			),
		);
	}

	/**
	 * Test the shortcode output.
	 *
	 * @dataProvider get_shortcode_googlemaps_data
	 * @author scotchfield
	 * @since 3.2
	 *
	 * @param string $shortcode The shortcode to render.
	 * @param bool   $is_amp    Whether this is an AMP endpoint.
	 * @param string $expected  The expected rendered shortcode.
	 */
	#[DataProvider( 'get_shortcode_googlemaps_data' )]
	public function test_shortcodes_googlemaps( $shortcode, $is_amp, $expected ) {
		if ( $is_amp && defined( 'IS_WPCOM' ) && IS_WPCOM ) {
			self::markTestSkipped( 'WordPress.com is in the process of removing AMP plugin.' );
			return; // @phan-suppress-current-line PhanPluginUnreachableCode
		}

		if ( $is_amp ) {
			add_filter( 'jetpack_is_amp_request', '__return_true' );
		}

		$actual = preg_replace( '/\s+/', ' ', do_shortcode( $shortcode ) );
		$actual = preg_replace( '/(?<=>)\s+(?=<)/', '', trim( $actual ) );

		$this->assertEquals( $expected, $actual );
	}
}
