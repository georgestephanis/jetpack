<?php
/**
 * This file contains PHPUnit tests for the Activitypub compatibility functions.
 * To run the package unit tests, run jetpack test php packages/image-cdn
 *
 * @package automattic/jetpack-image-cdn
 */

use PHPUnit\Framework\Attributes\CoversFunction;
use WorDBless\BaseTestCase;

require __DIR__ . '/../../src/compatibility/activitypub.php';

/**
 * @covers ::\Automattic\Jetpack\Image_CDN\Compatibility\load_activitypub_compat
 */
#[CoversFunction( '\\Automattic\\Jetpack\\Image_CDN\\Compatibility\\load_activitypub_compat' )]
class Activitypub_Compat_Test extends BaseTestCase {
	/**
	 * Test that we do not disable CDN for ActivityPub requests by default.
	 */
	public function test_load_activitypub_compat_default() {
		\Automattic\Jetpack\Image_CDN\Compatibility\load_activitypub_compat();
		// By default we should not hook into the ActivityPub filters.
		$this->assertFalse( has_action( 'activitypub_get_image_pre' ) );
		$this->assertFalse( has_action( 'activitypub_get_image_post' ) );
	}

	/**
	 * Test that we disable CDN for ActivityPub requests when the filter is used.
	 */
	public function test_load_activitypub_compat_disabled_filter() {
		// Set the filter to overwrite the default behavior.
		add_filter( 'jetpack_activitypub_post_disable_cdn', '__return_true' );

		\Automattic\Jetpack\Image_CDN\Compatibility\load_activitypub_compat();

		// We should now hook into the filters.
		$this->assertNotFalse( has_action( 'activitypub_get_image_pre' ) );
		$this->assertNotFalse( has_action( 'activitypub_get_image_post' ) );

		// Remove the filter.
		add_filter( 'jetpack_activitypub_post_disable_cdn', '__return_false' );
	}
}
