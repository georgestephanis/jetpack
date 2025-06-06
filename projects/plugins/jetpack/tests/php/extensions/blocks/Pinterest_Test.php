<?php
/**
 * Pinterest Block tests.
 *
 * @package automattic/jetpack
 */

use Automattic\Jetpack\Extensions\Pinterest;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
require_once JETPACK__PLUGIN_DIR . '/extensions/blocks/pinterest/pinterest.php';

/**
 * Pinterest block tests.
 *
 * @covers ::Automattic\Jetpack\Extensions\Pinterest\pin_type
 */
#[CoversFunction( 'Automattic\\Jetpack\\Extensions\\Pinterest\\pin_type' )]
class Pinterest_Test extends \WP_UnitTestCase {
	use \Automattic\Jetpack\PHPUnit\WP_UnitTestCase_Fix;

	/**
	 * Test the Pin type detected for a given Pinterest URL.
	 *
	 * @dataProvider get_pinterest_urls
	 *
	 * @since 9.2.0
	 *
	 * @param null|string $url      Pinterest URL.
	 * @param string      $expected Pinterest pin type.
	 */
	#[DataProvider( 'get_pinterest_urls' )]
	public function test_pin_type( $url, $expected ) {
		$pin_type = Pinterest\pin_type( $url );

		$this->assertSame( $expected, $pin_type );
	}

	/**
	 * URL variations to be used by the Pinterest block.
	 */
	public static function get_pinterest_urls() {
		return array(
			'null_url'               => array(
				null,
				'',
			),
			'empty_url'              => array(
				'',
				'',
			),
			'invalid_url'            => array(
				'abcdefghijk',
				'',
			),
			'invalid_protocol'       => array(
				'file://www.pinterest.com/pin/12345',
				'',
			),
			'invalid_subdomain_url'  => array(
				'https://abc.pinterest.com/pin/12345',
				'',
			),
			'invalid_path'           => array(
				'https://www.pinterest.com/',
				'',
			),
			'www_subdomain'          => array(
				'https://www.pinterest.com/pin/12345',
				'embedPin',
			),
			'locale_subdomain'       => array(
				'https://in.pinterest.com/pin/12345',
				'embedPin',
			),
			'username_url'           => array(
				'https://www.pinterest.ca/foo/',
				'embedUser',
			),
			'username_and_board_url' => array(
				'https://www.pinterest.ca/foo/bar/',
				'embedBoard',
			),
		);
	}
}
