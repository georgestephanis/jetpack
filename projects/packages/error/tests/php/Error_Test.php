<?php
/**
 * Testing the Error package.
 *
 * @package automattic/jetpack-error
 */

use Automattic\Jetpack\Error;
use PHPUnit\Framework\TestCase;

/**
 * Class Error_Test
 */
class Error_Test extends TestCase {
	/**
	 * Test Jetpack Error.
	 */
	public function test_jetpack_error() {
		$error = new Error();
		$this->assertInstanceOf( '\\WP_Error', $error );
	}
}

/**
 * Class WP_Error dummy class.
 */
class WP_Error {} // phpcs:ignore Generic.Files.OneObjectStructurePerFile.MultipleFound
