<?php

namespace Automattic\Jetpack\Backup;

use PHPUnit\Framework\TestCase;
use function add_filter;
use function apply_filters;

/**
 * Unit tests for the Package_Version class.
 *
 * @package automattic/jetpack-backup
 */
class Package_Version_Test extends TestCase {

	/**
	 * Tests that the backup package version is added to the package versions array obtained by the
	 * Package_Version_Tracker.
	 */
	public function test_send_package_version_to_tracker_empty_array() {
		$expected = array(
			Package_Version::PACKAGE_SLUG => Package_Version::PACKAGE_VERSION,
		);

		add_filter( 'jetpack_package_versions', __NAMESPACE__ . '\Package_Version::send_package_version_to_tracker' );

		$this->assertSame( $expected, apply_filters( 'jetpack_package_versions', array() ) );
	}

	/**
	 * Tests that the backup package version is added to the package versions array obtained by the
	 * Package_Version_Tracker.
	 */
	public function test_send_package_version_to_tracker_existing_array() {
		$existing_array = array(
			'test-package-slug' => '1.0.0',
		);

		$expected = array_merge(
			$existing_array,
			array( Package_Version::PACKAGE_SLUG => Package_Version::PACKAGE_VERSION )
		);

		add_filter( 'jetpack_package_versions', __NAMESPACE__ . '\Package_Version::send_package_version_to_tracker' );

		$this->assertSame( $expected, apply_filters( 'jetpack_package_versions', $existing_array ) );
	}
}
