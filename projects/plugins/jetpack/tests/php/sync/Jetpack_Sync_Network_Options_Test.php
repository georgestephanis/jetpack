<?php

use Automattic\Jetpack\Sync\Modules;

require_once __DIR__ . '/Jetpack_Sync_TestBase.php';

/**
 * Testing CRUD on Network Options
 * use phpunit --testsuite sync --filter Jetpack_Sync_Network_Options_Test
 */
class Jetpack_Sync_Network_Options_Test extends Jetpack_Sync_TestBase {
	protected $post;

	/** @var \Automattic\Jetpack\Sync\Modules\Network_Options */
	protected $network_options_module;

	/**
	 * Set up.
	 */
	public function set_up() {
		parent::set_up();

		$network_options_module = Modules::get_module( 'network_options' );
		'@phan-var \Automattic\Jetpack\Sync\Modules\Network_Options $network_options_module';
		$this->network_options_module = $network_options_module;

		$this->network_options_module->set_network_options_whitelist( array( 'test_network_option' ) );
		add_site_option( 'test_network_option', 'foo' );
		$this->sender->do_sync();
	}

	public function test_added_network_option_is_synced() {
		$synced_option_value = $this->server_replica_storage->get_site_option( 'test_network_option' );
		$this->assertEquals( 'foo', $synced_option_value );
	}

	public function test_updated_network_option_is_synced() {
		update_site_option( 'test_network_option', 'bar' );
		$this->sender->do_sync();
		$synced_option_value = $this->server_replica_storage->get_site_option( 'test_network_option' );
		$this->assertEquals( 'bar', $synced_option_value );
	}

	public function test_deleted_network_option_is_synced() {
		delete_site_option( 'test_network_option' );
		$this->sender->do_sync();
		$synced_option_value = $this->server_replica_storage->get_site_option( 'test_network_option' );
		$this->assertFalse( $synced_option_value );
	}

	public function test_don_t_sync_network_option_if_not_on_whitelist() {
		add_site_option( 'don_t_sync_test_network_option', 'foo' );
		$this->sender->do_sync();
		$synced_option_value = $this->server_replica_storage->get_site_option( 'don_t_sync_test_network_option' );
		$this->assertFalse( $synced_option_value );
	}
}
