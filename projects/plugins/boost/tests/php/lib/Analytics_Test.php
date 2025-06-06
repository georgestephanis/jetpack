<?php
namespace Automattic\Jetpack_Boost\Tests\Lib;

use Automattic\Jetpack_Boost\Lib\Analytics;
use Automattic\Jetpack_Boost\Tests\Base_TestCase;

/**
 * Class Analytics_Test
 *
 * @package Automattic\Jetpack_Boost\Tests\Lib
 */
class Analytics_Test extends Base_TestCase {
	public function test_get_tracking() {
		$tracking_object = Analytics::get_tracking();
		$this->assertTrue( method_exists( $tracking_object, 'ajax_tracks' ), 'Class does not have method ajax_tracks' );
		$this->assertTrue( method_exists( $tracking_object, 'enqueue_tracks_scripts' ), 'Class does not have method enqueue_tracks_scripts' );
	}
}
