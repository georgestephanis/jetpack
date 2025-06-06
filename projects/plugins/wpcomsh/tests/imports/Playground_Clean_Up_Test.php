<?php
/**
 * Playground Clean Up file.
 *
 * @package wpcomsh
 */

use Imports\Playground_Clean_Up;

/**
 * Class Playground_Clean_Up_Test.
 */
class Playground_Clean_Up_Test extends WP_UnitTestCase {
	use \Automattic\Jetpack\PHPUnit\WP_UnitTestCase_Fix;

	/**
	 * Open an empty file.
	 */
	public function test_error_open_an_not_existing_file() {
		$tmp_folder = tempnam( sys_get_temp_dir(), uniqid() );
		try {
			Playground_Clean_Up::remove_tmp_files( uniqid(), $tmp_folder );
			$this->assertFalse( is_dir( $tmp_folder ) );
		} finally {
			unlink( $tmp_folder );
		}
	}

	/**
	 * Open an empty folder.
	 */
	public function test_error_open_an_not_existing_folder() {
		$tmp_file = tempnam( sys_get_temp_dir(), 'tmp' );

		Playground_Clean_Up::remove_tmp_files( $tmp_file, uniqid() );

		$this->assertFalse( file_exists( $tmp_file ) );
	}

	/**
	 * Clean a file and a folder.
	 */
	public function test_remove_tmp_files() {
		$tmp_file   = tempnam( sys_get_temp_dir(), 'tmp' );
		$tmp_folder = tempnam( sys_get_temp_dir(), uniqid() );
		try {
			Playground_Clean_Up::remove_tmp_files( $tmp_file, $tmp_folder );
			$this->assertFalse( file_exists( $tmp_file ) );
			$this->assertFalse( is_dir( $tmp_folder ) );
		} finally {
			unlink( $tmp_folder );
		}
	}
}
