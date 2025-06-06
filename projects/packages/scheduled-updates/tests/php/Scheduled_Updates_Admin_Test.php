<?php
/**
 * Test class for Scheduled_Updates.
 *
 * @package automattic/scheduled-updates
 */

namespace Automattic\Jetpack;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test class for Scheduled_Updates_Admin.
 *
 * @covers \Automattic\Jetpack\Scheduled_Updates_Admin
 */
#[CoversClass( Scheduled_Updates_Admin::class )]
class Scheduled_Updates_Admin_Test extends \WorDBless\BaseTestCase {
	/**
	 * Test get_scheduled_update_text.
	 *
	 * @dataProvider update_text_provider
	 * @param object $schedule The schedule object.
	 * @param string $expected The expected text.
	 */
	#[DataProvider( 'update_text_provider' )]
	public function test_get_scheduled_update_text( $schedule, $expected ) {
		$this->assertSame( $expected, Scheduled_Updates_Admin::get_scheduled_update_text( $schedule ) );
	}

	/**
	 * Data provider for test_get_scheduled_update_text.
	 *
	 * @return array[]
	 */
	public static function update_text_provider() {
		return array(
			array(
				(object) array(
					'timestamp' => strtotime( 'next Monday 00:00' ),
					'interval'  => WEEK_IN_SECONDS,
				),
				sprintf( 'Mondays at %s.', gmdate( get_option( 'time_format' ), strtotime( 'next Monday 8:00' ) ) ),
			),
			array(
				(object) array(
					'timestamp' => strtotime( 'next Tuesday 00:00' ),
					'interval'  => DAY_IN_SECONDS,
				),
				sprintf( 'Daily at %s.', gmdate( get_option( 'time_format' ), strtotime( 'next Tuesday 8:00' ) ) ),
			),
			array(
				(object) array(
					'timestamp' => strtotime( 'next Sunday 00:00' ),
					'interval'  => WEEK_IN_SECONDS,
				),
				sprintf( 'Sundays at %s.', gmdate( get_option( 'time_format' ), strtotime( 'next Sunday 8:00' ) ) ),
			),
		);
	}
}
