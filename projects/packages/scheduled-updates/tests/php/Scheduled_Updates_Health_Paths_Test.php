<?php
/**
 * Test class for Scheduled_Updates_Health_Paths.
 *
 * @package automattic/scheduled-updates
 */

use Automattic\Jetpack\Scheduled_Updates;
use Automattic\Jetpack\Scheduled_Updates_Health_Paths;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Test class for Scheduled_Updates_Health_Paths.
 *
 * @covers \Automattic\Jetpack\Scheduled_Updates_Health_Paths
 * @covers \WPCOM_REST_API_V2_Endpoint_Update_Schedules
 */
#[CoversClass( Scheduled_Updates_Health_Paths::class )]
#[CoversClass( WPCOM_REST_API_V2_Endpoint_Update_Schedules::class )]
class Scheduled_Updates_Health_Paths_Test extends \WorDBless\BaseTestCase {

	/**
	 * Used to mock global functions inside a namespace.
	 *
	 * @see https://github.com/php-mock/php-mock-phpunit
	 */
	use \phpmock\phpunit\PHPMock;

	/**
	 * Admin user ID.
	 *
	 * @var int
	 */
	public $admin_id;

	/**
	 * Set up.
	 */
	public function set_up() {
		parent::set_up_wordbless();
		\WorDBless\Users::init()->clear_all_users();

		// Be sure wordbless cron is reset before each test.
		delete_option( 'cron' );
		update_option( 'cron', array( 'version' => 2 ), true );

		$this->admin_id = wp_insert_user(
			array(
				'user_login' => 'dummy_path_user',
				'user_pass'  => 'dummy_path_pass',
				'role'       => 'administrator',
			)
		);
		wp_set_current_user( $this->admin_id );

		Scheduled_Updates::init();
	}

	/**
	 * Clean up after test
	 */
	public function tear_down() {
		wp_delete_user( $this->admin_id );
		delete_option( Scheduled_Updates_Health_Paths::OPTION_NAME );
		parent::tear_down_wordbless();
	}

	/**
	 * Test logging events and retrieving logs for a specific schedule ID.
	 */
	public function test_validate() {
		$site_url     = get_site_url();
		$paths        = array(
			'',
			'/',
			"a\nb",
			'/a/b',
			'=',
			"test\n\t",
			'ünicode',
			'/index.php?foo=bar',
			'/index.php?foo=bar&bar=baz#test',
			'?foo=bar',
		);
		$parsed_paths = array(
			'/',
			'/',
			'/ab',
			'/a/b',
			'/=',
			'/test',
			'/ünicode',
			'/index.php?foo=bar',
			'/index.php?foo=bar&bar=baz',
			'/?foo=bar',
			'/',
			'/',
		);

		// Same paths with a URL prefix.
		$paths_with_url = array_map(
			function ( $path ) use ( $site_url ) {
				$path = strlen( $path ) ? '/' . ltrim( $path, '/' ) : '';
				return $site_url . $path;
			},
			$paths
		);

		foreach ( $paths as $index => $path ) {
			$this->assertSame( $parsed_paths[ $index ], Scheduled_Updates_Health_Paths::validate( $paths_with_url[ $index ] ) );
			$this->assertSame( $parsed_paths[ $index ], Scheduled_Updates_Health_Paths::validate( $path ) );
		}
	}

	/**
	 * Test create item with paths.
	 */
	public function test_create_item_with_no_paths() {
		$plugins = array( 'gutenberg/gutenberg.php' );
		$request = new WP_REST_Request( 'POST', '/wpcom/v2/update-schedules' );

		$request->set_body_params(
			array(
				'plugins'  => $plugins,
				'schedule' => array(
					'timestamp' => strtotime( 'next Monday 8:00' ),
					'interval'  => 'weekly',
				),
			)
		);
		$schedule_id = Scheduled_Updates::generate_schedule_id( $plugins );
		$result      = rest_do_request( $request );

		$this->assertSame( 200, $result->get_status() );
		$this->assertSame( $schedule_id, $result->get_data() );

		$option_paths = Scheduled_Updates_Health_Paths::get( $schedule_id );
		$this->assertSame( array(), $option_paths );
	}

	/**
	 * Test create item with paths.
	 */
	public function test_create_item_with_various_paths() {
		$plugins = array( 'gutenberg/gutenberg.php' );
		$paths   = array(
			"a\nb",
			'=',
			' ',
			"\ntest\t",
			'ünicode',
		);
		$request = new WP_REST_Request( 'POST', '/wpcom/v2/update-schedules' );
		$request->set_body_params(
			array(
				'plugins'            => $plugins,
				'schedule'           => array(
					'timestamp' => strtotime( 'next Monday 8:00' ),
					'interval'  => 'weekly',
				),
				'health_check_paths' => $paths,
			)
		);
		$schedule_id = Scheduled_Updates::generate_schedule_id( $plugins );
		$result      = rest_do_request( $request );

		$this->assertSame( 200, $result->get_status() );
		$this->assertSame( $schedule_id, $result->get_data() );

		$option_paths = Scheduled_Updates_Health_Paths::get( $schedule_id );

		$this->assertSame(
			array(
				'/ab',
				'/=',
				'/',
				'/test',
				'/ünicode',
			),
			$option_paths
		);
	}

	/**
	 * Test create item with too many paths.
	 */
	public function test_create_item_with_too_many_paths() {
		$plugins = array( 'gutenberg/gutenberg.php' );
		$paths   = array_fill( 0, 6, '/a' );
		$request = new WP_REST_Request( 'POST', '/wpcom/v2/update-schedules' );
		$request->set_body_params(
			array(
				'plugins'            => $plugins,
				'schedule'           => array(
					'timestamp' => strtotime( 'next Monday 8:00' ),
					'interval'  => 'weekly',
				),
				'health_check_paths' => $paths,
			)
		);
		$result = rest_do_request( $request )->get_data();

		$this->assertSame( 'rest_invalid_param', $result['code'] );
		$this->assertSame( 400, $result['data']['status'] );
	}

	/**
	 * Test remove item with paths.
	 */
	public function test_remove_item_with_paths() {
		$plugins = array( 'gutenberg/gutenberg.php' );
		$request = new WP_REST_Request( 'POST', '/wpcom/v2/update-schedules' );

		$request->set_body_params(
			array(
				'plugins'            => $plugins,
				'schedule'           => array(
					'timestamp' => strtotime( 'next Monday 8:00' ),
					'interval'  => 'weekly',
				),
				'health_check_paths' => array( 'a', 'b' ),
			)
		);
		$schedule_id_1 = Scheduled_Updates::generate_schedule_id( $plugins );

		// Successful request.
		wp_set_current_user( $this->admin_id );
		$result = rest_do_request( $request );

		$this->assertSame( 200, $result->get_status() );
		$this->assertSame( $schedule_id_1, $result->get_data() );

		$option_paths = Scheduled_Updates_Health_Paths::get( $schedule_id_1 );
		$this->assertSame( array( '/a', '/b' ), $option_paths );

		$plugins[]     = 'wp-test-plugin/wp-test-plugin.php';
		$schedule_id_2 = Scheduled_Updates::generate_schedule_id( $plugins );
		$request->set_body_params(
			array(
				'plugins'            => $plugins,
				'schedule'           => array(
					'timestamp' => strtotime( 'next Monday 11:00' ),
					'interval'  => 'daily',
				),
				'health_check_paths' => array( 'c', 'd' ),
			)
		);

		$result = rest_do_request( $request );

		$this->assertSame( 200, $result->get_status() );
		$this->assertSame( $schedule_id_2, $result->get_data() );

		$option_paths = Scheduled_Updates_Health_Paths::get( $schedule_id_2 );
		$this->assertSame( array( '/c', '/d' ), $option_paths );

		$request = new WP_REST_Request( 'DELETE', '/wpcom/v2/update-schedules/' . $schedule_id_1 );
		$result  = rest_do_request( $request );
		$this->assertSame( 200, $result->get_status() );

		$option_paths = Scheduled_Updates_Health_Paths::get( $schedule_id_1 );
		$this->assertSame( array(), $option_paths );

		$request = new WP_REST_Request( 'DELETE', '/wpcom/v2/update-schedules/' . $schedule_id_2 );
		$result  = rest_do_request( $request );
		$this->assertSame( 200, $result->get_status() );

		$option_paths = Scheduled_Updates_Health_Paths::get( $schedule_id_2 );
		$this->assertSame( array(), $option_paths );

		// The option should be removed.
		$this->assertSame( 'test', get_option( Scheduled_Updates_Health_Paths::OPTION_NAME, 'test' ) );
	}

	/**
	 * Asserts that the given value is an instance of WP_Error.
	 *
	 * @param mixed  $actual  The value to check.
	 * @param string $message Optional. Message to display when the assertion fails.
	 */
	public function assertWPError( $actual, $message = '' ) {
		$this->assertInstanceOf( 'WP_Error', $actual, $message );
	}
}
