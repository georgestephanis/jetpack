<?php
/**
 * Testing CRUD on Meta
 */

use Automattic\Jetpack\Forms\ContactForm\Contact_Form_Plugin;
use Automattic\Jetpack\Sync\Defaults;
use Automattic\Jetpack\Sync\Modules;
use Automattic\Jetpack\Sync\Modules\Posts;
use Automattic\Jetpack\Sync\Settings;

require_once __DIR__ . '/Jetpack_Sync_TestBase.php';

class Jetpack_Sync_Meta_Test extends Jetpack_Sync_TestBase {
	protected $post_id;
	protected $meta_id;
	protected $meta_module;

	protected $whitelisted_post_meta = 'foobar';

	/**
	 * Set up.
	 */
	public function set_up() {
		parent::set_up();

		// create a post
		$this->meta_module = Modules::get_module( 'meta' );
		Settings::update_settings( array( 'post_meta_whitelist' => array( 'foobar' ) ) );
		$this->post_id = self::factory()->post->create();
		$this->meta_id = add_post_meta( $this->post_id, $this->whitelisted_post_meta, 'foo' );
		$this->sender->do_sync();
	}

	/**
	 * Verify that meta_values below size limit are not tuncated.
	 */
	public function test_meta_adheres_size_limit_max() {
		$meta_test_value = str_repeat( 'X', Posts::MAX_META_LENGTH - 1 );
		update_post_meta( $this->post_id, $this->whitelisted_post_meta, $meta_test_value );

		$this->sender->do_sync();

		$meta_key_value = $this->server_replica_storage->get_metadata( 'post', $this->post_id, $this->whitelisted_post_meta, true );
		$this->assertEquals( $meta_test_value, $meta_key_value );
	}

	/**
	 * Verify that meta_values above size limit are truncated.
	 */
	public function test_meta_adheres_size_limit_exceeded() {
		$meta_test_value = str_repeat( 'X', Posts::MAX_META_LENGTH );
		update_post_meta( $this->post_id, $this->whitelisted_post_meta, $meta_test_value );

		$this->sender->do_sync();

		$meta_key_value = $this->server_replica_storage->get_metadata( 'post', $this->post_id, $this->whitelisted_post_meta, true );
		$this->assertSame( '', $meta_key_value );
	}

	public function test_added_post_meta_is_synced() {

		$meta_key_value = $this->server_replica_storage->get_metadata( 'post', $this->post_id, $this->whitelisted_post_meta, true );
		$meta_key_array = $this->server_replica_storage->get_metadata( 'post', $this->post_id, $this->whitelisted_post_meta );

		$this->assertEquals( 'foo', $meta_key_value );
		$this->assertEquals( array( 'foo' ), $meta_key_array );
	}

	public function test_added_multiple_post_meta_is_synced() {

		add_post_meta( $this->post_id, $this->whitelisted_post_meta, 'foo', true );
		add_post_meta( $this->post_id, $this->whitelisted_post_meta, 'bar' );

		$this->sender->do_sync();

		$meta_key_array = $this->server_replica_storage->get_metadata( 'post', $this->post_id, $this->whitelisted_post_meta );
		$this->assertEquals( array( 'foo', 'bar' ), $meta_key_array );
	}

	/**
	 * Verify that update_post_meta is synced after an add_post_meta.
	 */
	public function test_add_then_updated_post_meta_is_synced() {
		add_post_meta( $this->post_id, $this->whitelisted_post_meta, 'foo' );
		update_post_meta( $this->post_id, $this->whitelisted_post_meta, 'bar', 'foo' );

		$this->sender->do_sync();

		$meta_key_array = $this->server_replica_storage->get_metadata( 'post', $this->post_id, $this->whitelisted_post_meta );
		$this->assertEquals( get_post_meta( $this->post_id, $this->whitelisted_post_meta ), $meta_key_array );
	}

	/**
	 * Verify that update_post_meta is sycned.
	 */
	public function test_updated_post_meta_is_synced() {
		update_post_meta( $this->post_id, $this->whitelisted_post_meta, 'foo' );
		update_post_meta( $this->post_id, $this->whitelisted_post_meta, 'bar', 'foo' );

		$this->sender->do_sync();

		$meta_key_array = $this->server_replica_storage->get_metadata( 'post', $this->post_id, $this->whitelisted_post_meta );
		$this->assertEquals( get_post_meta( $this->post_id, $this->whitelisted_post_meta ), $meta_key_array );
	}

	/**
	 * Verify that delete_post_meta triggers sync.
	 */
	public function test_deleted_post_meta_is_synced() {
		add_post_meta( $this->post_id, $this->whitelisted_post_meta, 'foo' );

		delete_post_meta( $this->post_id, $this->whitelisted_post_meta, 'foo' );
		$this->sender->do_sync();

		$meta_key_value = $this->server_replica_storage->get_metadata( 'post', $this->post_id, $this->whitelisted_post_meta, true );
		$meta_key_array = $this->server_replica_storage->get_metadata( 'post', $this->post_id, $this->whitelisted_post_meta );

		$this->assertEquals( get_post_meta( $this->post_id, $this->whitelisted_post_meta, true ), $meta_key_value );
		$this->assertEquals( get_post_meta( $this->post_id, $this->whitelisted_post_meta ), $meta_key_array );
	}

	/**
	 * Verify deleting all post meta is synced.
	 */
	public function test_delete_all_post_meta_is_synced() {

		add_post_meta( $this->post_id, $this->whitelisted_post_meta, 'foo' );

		delete_metadata( 'post', $this->post_id, $this->whitelisted_post_meta, '', true );
		$this->sender->do_sync();

		$meta_key_value = $this->server_replica_storage->get_metadata( 'post', $this->post_id, $this->whitelisted_post_meta, true );
		$meta_key_array = $this->server_replica_storage->get_metadata( 'post', $this->post_id, $this->whitelisted_post_meta );
		$this->assertEquals( get_post_meta( $this->post_id, $this->whitelisted_post_meta, true ), $meta_key_value );
		$this->assertEquals( get_post_meta( $this->post_id, $this->whitelisted_post_meta ), $meta_key_array );
	}

	/**
	 * Verify private meta is not synced.
	 */
	public function test_doesn_t_sync_private_meta() {
		add_post_meta( $this->post_id, '_private_meta', 'foo' );

		$this->sender->do_sync();

		$this->assertSame( '', $this->server_replica_storage->get_metadata( 'post', $this->post_id, '_private_meta', true ) );
	}

	/**
	 * Verify search allowed meta is not synced if Search module inactive.
	 */
	public function test_doesn_t_sync_search_meta() {
		$this->assertFalse( \Jetpack::is_module_active( 'search' ) );

		// A meta key that is only in Search module.
		add_post_meta( $this->post_id, 'session_transcript1234', 'foo' );

		$this->sender->do_sync();
		$this->assertEquals(
			array(),
			$this->server_replica_storage->get_metadata( 'post', $this->post_id, 'session_transcript' )
		);
		delete_post_meta( $this->post_id, 'session_transcript1234', 'foo' );
	}

	public function test_post_meta_whitelist_cab_be_appened_in_settings() {
		add_post_meta( $this->post_id, '_private_meta', 'foo' );
		$this->sender->do_sync();

		$this->assertSame( '', $this->server_replica_storage->get_metadata( 'post', $this->post_id, '_private_meta', true ) );

		Settings::update_settings( array( 'post_meta_whitelist' => array( '_private_meta' ) ) );

		add_post_meta( $this->post_id, '_private_meta', 'boo' );

		$this->sender->do_sync();

		$this->assertEquals( 'boo', $this->server_replica_storage->get_metadata( 'post', $this->post_id, '_private_meta', true ) );
	}

	public function test_comment_meta_whitelist_cab_be_appened_in_settings() {
		$comment_ids = self::factory()->comment->create_post_comments( $this->post_id );

		add_comment_meta( $comment_ids[0], '_private_meta', 'foo' );
		$this->sender->do_sync();

		$this->assertSame( '', $this->server_replica_storage->get_metadata( 'comment', $comment_ids[0], '_private_meta', true ) );

		Settings::update_settings( array( 'comment_meta_whitelist' => array( '_private_meta' ) ) );

		add_comment_meta( $comment_ids[0], '_private_meta', 'boo' );
		$this->sender->do_sync();

		$this->assertEquals( 'boo', $this->server_replica_storage->get_metadata( 'comment', $comment_ids[0], '_private_meta', true ) );
	}

	public function test_sync_whitelisted_post_meta() {
		Settings::update_settings( array( 'post_meta_whitelist' => array() ) );
		$this->setSyncClientDefaults();
		// check that these values exists in the whitelist options
		$white_listed_post_meta = Defaults::$post_meta_whitelist;

		// update all the options.
		foreach ( $white_listed_post_meta as $meta_key ) {
			if ( $meta_key === 'footnotes' ) {
				// WordPress would filter non-array into an empty string, and fail the test
				// See sanitize_post_meta_footnotes filter
				add_post_meta( $this->post_id, $meta_key, wp_json_encode( array() ) );
				continue;
			}
			add_post_meta( $this->post_id, $meta_key, 'foo' );
		}

		$this->sender->do_sync();

		foreach ( $white_listed_post_meta as $meta_key ) {
			if ( $meta_key === 'footnotes' ) {
				$this->assertOptionIsSynced( $meta_key, '[]', 'post', $this->post_id );
				continue;
			}

			$this->assertOptionIsSynced( $meta_key, 'foo', 'post', $this->post_id );
		}
		$whitelist = Settings::get_setting( 'post_meta_whitelist' );

		$whitelist_and_option_keys_difference = array_diff( $whitelist, $white_listed_post_meta );
		// Are we testing all the options
		$unique_whitelist = array_unique( $whitelist );

		$this->assertSameSize( $unique_whitelist, $whitelist, 'The duplicate keys are: ' . print_r( array_diff_key( $whitelist, array_unique( $whitelist ) ), 1 ) );
		$this->assertEmpty( $whitelist_and_option_keys_difference, 'Some whitelisted options don\'t have a test: ' . print_r( $whitelist_and_option_keys_difference, 1 ) );
	}

	public function test_sync_whitelisted_comment_meta() {
		Settings::update_settings( array( 'comment_meta_whitelist' => array() ) );
		$this->setSyncClientDefaults();
		// check that these values exists in the whitelist options
		$white_listed_comment_meta = Defaults::$comment_meta_whitelist;

		$comment_ids = self::factory()->comment->create_post_comments( $this->post_id );

		// update all the comment meta
		foreach ( $white_listed_comment_meta as $meta_key ) {
			add_comment_meta( $comment_ids[0], $meta_key, 'foo', 'comment' );
		}

		$this->sender->do_sync();

		foreach ( $white_listed_comment_meta as $meta_key ) {
			$this->assertOptionIsSynced( $meta_key, 'foo', 'comment', $comment_ids[0] );
		}
		$whitelist = Settings::get_setting( 'comment_meta_whitelist' );

		$whitelist_and_option_keys_difference = array_diff( $whitelist, $white_listed_comment_meta );
		// Are we testing all the options
		$unique_whitelist = array_unique( $whitelist );

		$this->assertSameSize( $unique_whitelist, $whitelist, 'The duplicate keys are: ' . print_r( array_diff_key( $whitelist, array_unique( $whitelist ) ), 1 ) );
		$this->assertEmpty( $whitelist_and_option_keys_difference, 'Some whitelisted options don\'t have a test: ' . print_r( $whitelist_and_option_keys_difference, 1 ) );
	}

	public function test_syncs_wpas_skip_meta() {
		$this->setSyncClientDefaults();
		add_post_meta( $this->post_id, '_wpas_skip_1234', '1' );
		$this->sender->do_sync();

		$this->assertOptionIsSynced( '_wpas_skip_1234', '1', 'post', $this->post_id );
	}

	public function test_sync_daily_akismet_meta_cleanup() {
		$this->sender->do_sync();
		$this->server_event_storage->reset();
		$post_id = wp_insert_post(
			array(
				'post_type'  => 'feedback',
				'post_title' => 'fun',
			)
		);
		// This event can trigger a deletion of many _feedbacakismet_values terms.
		add_post_meta( $post_id, '_feedback_akismet_values', '1' );

		$grunion = Contact_Form_Plugin::init();
		$grunion->daily_akismet_meta_cleanup();

		$this->sender->do_sync();

		$event = $this->server_event_storage->get_most_recent_event( 'jetpack_post_meta_batch_delete' );

		$this->assertEquals( array( $post_id ), $event->args[0] );
		$this->assertEquals( '_feedback_akismet_values', $event->args[1] );

		$event = $this->server_event_storage->get_most_recent_event( 'deleted_post_meta' );

		$this->assertFalse( $event );
		$meta_key_value = $this->server_replica_storage->get_metadata( 'post', $post_id, '_feedback_akismet_values', true );
		$this->assertEquals( get_post_meta( $post_id, '_feedback_akismet_values', true ), $meta_key_value );
	}

	public function assertOptionIsSynced( $meta_key, $value, $type, $object_id ) {
		$this->assertEqualsObject( $value, $this->server_replica_storage->get_metadata( $type, $object_id, $meta_key, true ), 'Synced option doesn\'t match local option.' );
	}

	/**
	 * Verify that get_object_by_id will return null for non existing meta.
	 */
	public function test_get_object_by_id_will_return_null_for_non_existing_meta() {
		$module = Modules::get_module( 'meta' );
		$this->assertNull( $module->get_object_by_id( 'post', $this->post_id, 'does_not_exist' ) );
	}

	/**
	 * Test get_object_by_id with multiple meta for the same object_id and key.
	 */
	public function test_get_object_by_id_multiple_meta_same_object_id_and_key() {
		$meta_id = add_post_meta( $this->post_id, $this->whitelisted_post_meta, 'bar' );
		$module  = Modules::get_module( 'meta' );

		$metas    = $module->get_object_by_id( 'post', $this->post_id, $this->whitelisted_post_meta );
		$expected = array(
			array(
				'meta_type'  => 'post',
				'meta_id'    => (string) $this->meta_id,
				'meta_key'   => $this->whitelisted_post_meta,
				'meta_value' => 'foo',
				'object_id'  => (string) $this->post_id,
			),
			array(
				'meta_type'  => 'post',
				'meta_id'    => (string) $meta_id,
				'meta_key'   => $this->whitelisted_post_meta,
				'meta_value' => 'bar',
				'object_id'  => (string) $this->post_id,
			),
		);

		$this->assertSame( $expected, $metas );
	}

	/**
	 * Verify that meta_values above size limit are truncated in get_object_by_id
	 */
	public function test_get_object_by_id_size_limit_exceeded() {
		$meta_test_value = str_repeat( 'X', Posts::MAX_META_LENGTH );
		update_post_meta( $this->post_id, $this->whitelisted_post_meta, $meta_test_value );

		$module = Modules::get_module( 'meta' );
		'@phan-var \Automattic\Jetpack\Sync\Modules\Meta $module';
		$metas = $module->get_object_by_id( 'post', $this->post_id, $this->whitelisted_post_meta );
		$this->assertSame( '', $metas[0]['meta_value'] );
	}

	/**
	 * Verify that meta_values below size limit are not truncated in get_object_by_id
	 */
	public function test_get_object_by_id_size_limit_max() {
		$meta_test_value = str_repeat( 'X', Posts::MAX_META_LENGTH - 1 );
		update_post_meta( $this->post_id, $this->whitelisted_post_meta, $meta_test_value );

		$module = Modules::get_module( 'meta' );
		'@phan-var \Automattic\Jetpack\Sync\Modules\Meta $module';
		$metas = $module->get_object_by_id( 'post', $this->post_id, $this->whitelisted_post_meta );
		$this->assertEquals( $meta_test_value, $metas[0]['meta_value'] );
	}

	/**
	 * Tests get_objects_by_id
	 */
	public function test_get_objects_by_id() {
		$module  = Modules::get_module( 'meta' );
		$meta_id = add_post_meta( $this->post_id, $this->whitelisted_post_meta, 'bar' );
		$config  = array(
			array(
				'id'       => $this->post_id,
				'meta_key' => $this->whitelisted_post_meta,
			),
		);
		$metas   = $module->get_objects_by_id( 'post', $config );

		$expected = array(
			$this->post_id . '-' . $this->whitelisted_post_meta => array(
				array(
					'meta_type'  => 'post',
					'meta_id'    => (string) $this->meta_id,
					'meta_key'   => $this->whitelisted_post_meta,
					'meta_value' => 'foo',
					'object_id'  => (string) $this->post_id,
				),
				array(
					'meta_type'  => 'post',
					'meta_id'    => (string) $meta_id,
					'meta_key'   => $this->whitelisted_post_meta,
					'meta_value' => 'bar',
					'object_id'  => (string) $this->post_id,
				),
			),
		);
		$this->assertSame( $expected, $metas );
	}

	/**
	 * Verify that meta_values above size limit are truncated in get_objects_by_id.
	 */
	public function test_get_objects_by_id_size_limit_exceeded() {
		$meta_test_value = str_repeat( 'X', Posts::MAX_META_LENGTH );
		update_post_meta( $this->post_id, $this->whitelisted_post_meta, $meta_test_value );

		$module = Modules::get_module( 'meta' );
		$config = array(
			array(
				'id'       => $this->post_id,
				'meta_key' => $this->whitelisted_post_meta,
			),
		);
		$metas  = $module->get_objects_by_id( 'post', $config );

		$expected = array(
			$this->post_id . '-' . $this->whitelisted_post_meta => array(
				array(
					'meta_type'  => 'post',
					'meta_id'    => (string) $this->meta_id,
					'meta_key'   => $this->whitelisted_post_meta,
					'meta_value' => '',
					'object_id'  => (string) $this->post_id,
				),
			),
		);
		$this->assertSame( $expected, $metas );
	}

	/**
	 * Tests get_objects_by_id when the max DB query length is exceeded.
	 */
	public function test_get_objects_by_id_max_query_length_exceeded() {
		$module        = Modules::get_module( 'meta' );
		$long_meta_key = str_repeat( 'X', $module::MAX_DB_QUERY_LENGTH );
		// We are not actually adding the meta, only in $config so that it produces a long DB query.
		$config = array(
			array(
				'id'       => 9999,
				'meta_key' => $long_meta_key,
			),
			array(
				'id'       => $this->post_id,
				'meta_key' => $this->whitelisted_post_meta,
			),
		);

		$metas = $module->get_objects_by_id( 'post', $config );

		$expected = array(
			$this->post_id . '-' . $this->whitelisted_post_meta => array(
				array(
					'meta_type'  => 'post',
					'meta_id'    => (string) $this->meta_id,
					'meta_key'   => $this->whitelisted_post_meta,
					'meta_value' => 'foo',
					'object_id'  => (string) $this->post_id,
				),
			),
		);

		$this->assertSame( $expected, $metas );
	}
}
