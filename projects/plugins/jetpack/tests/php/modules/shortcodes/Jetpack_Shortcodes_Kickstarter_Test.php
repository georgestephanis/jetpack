<?php

require_once __DIR__ . '/trait.http-request-cache.php';

class Jetpack_Shortcodes_Kickstarter_Test extends WP_UnitTestCase {
	use \Automattic\Jetpack\PHPUnit\WP_UnitTestCase_Fix;
	use Automattic\Jetpack\Tests\HttpRequestCacheTrait;

	/**
	 * Verify that [kickstarter] exists.
	 *
	 * @since  4.5.0
	 */
	public function test_shortcodes_kickstarter_exists() {
		$this->assertTrue( shortcode_exists( 'kickstarter' ) );
	}

	/**
	 * Verify that executing the shortcode doesn't return the same content but empty, since it has no attributes.
	 *
	 * @since 4.5.0
	 */
	public function test_shortcodes_kickstarter() {
		$content = '[kickstarter]';

		$shortcode_content = do_shortcode( $content );

		$this->assertNotEquals( $content, $shortcode_content );
		$this->assertSame( '', $shortcode_content );
	}

	/**
	 * Verify that executing shortcode with an invalid URL fails.
	 *
	 * @since 4.5.0
	 */
	public function test_shortcodes_kickstarter_invalid_url() {
		$content = '[kickstarter url="https://kikstarter.com"]';

		$shortcode_content = do_shortcode( $content );

		$this->assertEquals( '<!-- Invalid Kickstarter URL -->', $shortcode_content );
	}

	/**
	 * Verify that rendering the shortcode returns a Kickstarter link.
	 *
	 * @since 4.5.0
	 */
	public function test_shortcodes_kickstarter_image() {
		$this->markTestSkipped();
		// @phan-suppress-next-line PhanPluginUnreachableCode
		$url     = 'https://www.kickstarter.com/projects/peaktoplateau/yak-wool-baselayers-from-tibet-to-the-world';
		$content = "[kickstarter url='$url']";

		$shortcode_content = do_shortcode( $content );

		$this->assertStringContainsString( '<a href="https://www.kickstarter.com/projects/peaktoplateau/yak-wool-baselayers-from-tibet-to-the-world">', $shortcode_content );
	}
}
