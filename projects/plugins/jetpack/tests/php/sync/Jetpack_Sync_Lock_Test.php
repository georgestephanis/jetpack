<?php
/**
 * Tests for the Lock class.
 *
 * @package automattic/jetpack-sync
 */

use Automattic\Jetpack\Sync\Lock;

require_once __DIR__ . '/Jetpack_Sync_TestBase.php';

/**
 * Tests for the Lock class.
 */
class Jetpack_Sync_Lock_Test extends Jetpack_Sync_TestBase {

	/**
	 * Test request lock twice
	 */
	public function test_request_lock_twice() {
		$this->assertNotFalse( ( new Lock() )->attempt( 'test' ) );
		$this->assertFalse( ( new Lock() )->attempt( 'test' ) );
		( new Lock() )->remove( 'test', true );
	}

	/**
	 * Test remove lock
	 */
	public function test_remove_lock() {
		$lock = ( new Lock() )->attempt( 'test' );
		$this->assertNotFalse( $lock );
		( new Lock() )->remove( 'test', $lock );
		$this->assertNotFalse( ( new Lock() )->attempt( 'test' ) );
		( new Lock() )->remove( 'test', true );
	}

	/**
	 * Test two locks with different name
	 */
	public function test_two_locks_different_name() {
		$this->assertNotFalse( ( new Lock() )->attempt( 'test' ) );
		$this->assertNotFalse( ( new Lock() )->attempt( 'test2' ) );
		( new Lock() )->remove( 'test', true );
		( new Lock() )->remove( 'test2', true );
	}

	/**
	 * Test two locks with different name remove one lock
	 */
	public function test_two_locks_different_name_remove_one_lock() {
		$lock = ( new Lock() )->attempt( 'test' );
		$this->assertNotFalse( $lock );
		$this->assertNotFalse( ( new Lock() )->attempt( 'test2' ) );
		( new Lock() )->remove( 'test', $lock );
		$this->assertFalse( ( new Lock() )->attempt( 'test2' ) );
		$this->assertNotFalse( ( new Lock() )->attempt( 'test' ) );
		( new Lock() )->remove( 'test', true );
		( new Lock() )->remove( 'test2', true );
	}
}
