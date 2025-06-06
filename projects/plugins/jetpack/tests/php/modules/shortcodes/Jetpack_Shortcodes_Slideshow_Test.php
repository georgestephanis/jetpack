<?php

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__ . '/trait.http-request-cache.php';
require_once JETPACK__PLUGIN_DIR . 'extensions/blocks/slideshow/slideshow.php';

/**
 * @covers \Jetpack_Slideshow_Shortcode
 */
#[CoversClass( Jetpack_Slideshow_Shortcode::class )]
class Jetpack_Shortcodes_Slideshow_Test extends WP_UnitTestCase {
	use \Automattic\Jetpack\PHPUnit\WP_UnitTestCase_Fix;
	use Automattic\Jetpack\Tests\HttpRequestCacheTrait;

	/**
	 * The mock IDs.
	 *
	 * @var string
	 */
	public $ids;

	/**
	 * Sets up each test.
	 *
	 * @inheritDoc
	 */
	public function set_up() {
		parent::set_up();

		if ( ! defined( 'TESTING_IN_JETPACK' ) || ! TESTING_IN_JETPACK ) {
			switch_to_blog( 104104364 ); // test.wordpress.com
			$this->ids = '161,162';
			return;
		}

		// Otherwise, create the two images we're going to be using ourselves!
		$a1 = self::factory()->attachment->create_object(
			'image1.jpg',
			0,
			array(
				'file'           => 'image1.jpg',
				'post_mime_type' => 'image/jpeg',
				'post_type'      => 'attachment',
			)
		);

		$a2 = self::factory()->attachment->create_object(
			'image1.jpg',
			0,
			array(
				'file'           => 'image2.jpg',
				'post_mime_type' => 'image/jpeg',
				'post_type'      => 'attachment',
			)
		);

		$this->ids = "{$a1},{$a2}";
	}

	/**
	 * Sets up each test.
	 *
	 * @inheritDoc
	 */
	public function tear_down() {
		if ( ! defined( 'TESTING_IN_JETPACK' ) || ! TESTING_IN_JETPACK ) {
			restore_current_blog();
		}

		parent::tear_down();
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_shortcodes_slideshow_exists() {
		$this->assertTrue( shortcode_exists( 'slideshow' ) );
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_shortcodes_slideshow() {
		$content = '[slideshow]';

		$shortcode_content = do_shortcode( $content );

		$this->assertNotEquals( $content, $shortcode_content );
	}

	public function test_shortcodes_slideshow_no_js() {
		$content = '[gallery type="slideshow" ids="' . $this->ids . '"]';

		$shortcode_content = do_shortcode( $content );

		$this->assertSame( 0, strpos( $shortcode_content, '<p class="jetpack-slideshow-noscript robots-nocontent">This slideshow requires JavaScript.</p>' ) );
	}

	public function test_shortcodes_slideshow_html() {
		$content = '[gallery type="slideshow" ids="' . $this->ids . '"]';

		$shortcode_content = do_shortcode( $content );

		$this->assertEquals( ! false, strpos( $shortcode_content, 'class="jetpack-slideshow-window jetpack-slideshow' ) );
	}

	public function test_shortcodes_slideshow_autostart_off() {
		$content = '[gallery type="slideshow" ids="' . $this->ids . '" autostart="false"]';

		$shortcode_content = do_shortcode( $content );

		$this->assertEquals( ! false, strpos( $shortcode_content, 'data-autostart="false"' ) );
	}

	public function test_shortcodes_slideshow_autostart_on() {
		$content = '[gallery type="slideshow" ids="' . $this->ids . '" autostart="true"]';

		$shortcode_content = do_shortcode( $content );

		$this->assertEquals( ! false, strpos( $shortcode_content, 'data-autostart="true"' ) );
	}

	/**
	 * Check that no markup is returned when an invalid image ID is used.
	 *
	 * @since 8.5.0
	 */
	public function test_shortcodes_slideshow_no_valid_id() {
		$content = sprintf( '[gallery type="slideshow" size="thumbnail" ids="%d"]', PHP_INT_MAX );

		$this->assertEmpty( do_shortcode( $content ) );
	}

	/**
	 * Gets the test data for test_shortcodes_slideshow_amp().
	 *
	 * @return array The test data.
	 */
	public static function get_slideshow_shortcode_amp() {
		// @todo Why does this pass with no ids? (This used to try to use `$this->ids` before it was set)
		$ids = '';

		return array(
			'amp_carousel'      => array(
				'[gallery type="slideshow" size="thumbnail" ids="' . $ids . '"]',
				'<amp-carousel width="800" height="600" layout="responsive" type="slides"',
			),
			'without_autostart' => array(
				'[gallery type="slideshow" size="thumbnail" ids="' . $ids . ' autostart="false"]',
				'wp-block-jetpack-slideshow wp-amp-block" id',
			),
			'with_autostart'    => array(
				'[gallery type="slideshow" size="thumbnail" ids="' . $ids . '"]',
				'wp-block-jetpack-slideshow wp-amp-block wp-block-jetpack-slideshow__autoplay wp-block-jetpack-slideshow__autoplay-playing" id',
			),
		);
	}

	/**
	 * Test slideshow shortcode output in AMP.
	 *
	 * @dataProvider get_slideshow_shortcode_amp
	 * @since 8.5.0
	 *
	 * @param string $shortcode The initial shortcode.
	 * @param string $expected  The expected markup, after processing the shortcode.
	 */
	#[DataProvider( 'get_slideshow_shortcode_amp' )]
	public function test_shortcodes_slideshow_amp( $shortcode, $expected ) {
		if ( defined( 'IS_WPCOM' ) && IS_WPCOM ) {
			self::markTestSkipped( 'WordPress.com is in the process of removing AMP plugin.' );
			return; // @phan-suppress-current-line PhanPluginUnreachableCode
		}

		add_filter( 'jetpack_is_amp_request', '__return_true' );

		$this->assertStringContainsString( $expected, do_shortcode( $shortcode ) );
	}
}
