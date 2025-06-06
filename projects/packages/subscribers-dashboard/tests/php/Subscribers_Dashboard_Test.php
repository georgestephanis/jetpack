<?php
/**
 * Tests for Subscribers Dashboard.
 *
 * @package automattic/jetpack-subscribers-dashboard
 */

namespace Automattic\Jetpack\Subscribers_Dashboard;

use Automattic\Jetpack\Status\Cache;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Class Subscribers_Dashboard_Test.
 *
 * @covers Automattic\Jetpack\Subscribers_Dashboard\Dashboard
 */
#[CoversClass( Dashboard::class )]
class Subscribers_Dashboard_Test extends TestCase {

	/**
	 * Setup runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		add_filter( 'jetpack_wp_admin_subscriber_management_enabled', '__return_true' );
	}

	/**
	 * Returning the environment into its initial state.
	 */
	public function tearDown(): void {
		parent::tearDown();
		remove_filter( 'jetpack_wp_admin_subscriber_management_enabled', '__return_true' );
		Cache::clear();
	}

	public function test_init() {
		$subscriber_dashboard = new Dashboard();
		$subscriber_dashboard->init_hooks();
		$this->assertSame( 999, has_action( 'admin_menu', array( $subscriber_dashboard, 'add_wp_admin_submenu' ) ) );
	}

	public function test_add_wp_admin_submenu() {
		$subscriber_dashboard = new Dashboard();
		$subscriber_dashboard->add_wp_admin_submenu();
		$this->assertSame( 10, has_action( 'load-jetpack_page_subscribers', array( $subscriber_dashboard, 'admin_init' ) ) );
	}

	public function test_admin_init() {
		$subscriber_dashboard = new Dashboard();
		$subscriber_dashboard->admin_init();
		$this->assertSame( 10, has_action( 'admin_enqueue_scripts', array( $subscriber_dashboard, 'load_admin_scripts' ) ) );
	}
}
