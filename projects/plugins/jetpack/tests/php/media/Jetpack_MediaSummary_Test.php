<?php

use PHPUnit\Framework\Attributes\CoversClass;

if ( ! class_exists( 'Jetpack_Media_Summary' ) ) {
	require_once JETPACK__PLUGIN_DIR . '_inc/lib/class.media-summary.php';
}

/**
 * @covers \Jetpack_Media_Summary
 */
#[CoversClass( Jetpack_Media_Summary::class )]
class Jetpack_MediaSummary_Test extends WP_UnitTestCase {
	use \Automattic\Jetpack\PHPUnit\WP_UnitTestCase_Fix;

	/**
	 * @author scotchfield
	 * @since 3.2
	 * @todo find a better way to test this large function
	 */
	public function test_mediasummary_get() {
		$post_id = self::factory()->post->create( array() );

		$get_obj = Jetpack_Media_Summary::get( $post_id );

		$this->assertIsArray( $get_obj );
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_mediasummary_https() {
		$content  = 'http://' . WP_TESTS_DOMAIN . '/';
		$expected = 'https://' . WP_TESTS_DOMAIN . '/';

		$this->assertEquals( $expected, Jetpack_Media_Summary::https( $content ) );
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_mediasummary_ssl_img() {
		$content  = 'http://' . WP_TESTS_DOMAIN . '/';
		$expected = 'https://' . WP_TESTS_DOMAIN . '/';

		$this->assertEquals( $expected, Jetpack_Media_Summary::ssl_img( $content ) );
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_mediasummary_ssl_img_wordpress_domain() {
		$content  = 'http://files.wordpress.com/';
		$expected = 'https://files.wordpress.com/';

		$this->assertEquals( $expected, Jetpack_Media_Summary::ssl_img( $content ) );
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_mediasummary_clean_text_empty() {
		$content = '';

		$this->assertEmpty( Jetpack_Media_Summary::clean_text( $content ) );
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_mediasummary_clean_text_simple() {
		$shortcode = 'test_mediasummary_shortcode';
		add_shortcode( $shortcode, array( $this, 'shortcode_nop' ) );

		$content = '[' . $shortcode . '] <a href="' . WP_TESTS_DOMAIN . '">test</a>';

		$this->assertEquals( 'test', Jetpack_Media_Summary::clean_text( $content ) );
	}

	public function shortcode_nop() { }

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_mediasummary_get_word_count_empty() {
		$content = '';

		$this->assertSame( 0, Jetpack_Media_Summary::get_word_count( $content ) );
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_mediasummary_get_word_count_sample() {
		$content = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.';

		$this->assertEquals( 19, Jetpack_Media_Summary::get_word_count( $content ) );
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_mediasummary_get_link_count_empty() {
		$content = '';

		$this->assertSame( 0, Jetpack_Media_Summary::get_link_count( $content ) );
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_mediasummary_get_link_count_simple() {
		$content = '<a href="' . WP_TESTS_DOMAIN . '"></a>';

		$this->assertSame( 1, Jetpack_Media_Summary::get_link_count( $content ) );
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_mediasummary_get_link_count_invalid_tag() {
		$content = '<abbr title="Canada">CA</abbr>';

		$this->assertSame( 0, Jetpack_Media_Summary::get_link_count( $content ) );
	}
}
