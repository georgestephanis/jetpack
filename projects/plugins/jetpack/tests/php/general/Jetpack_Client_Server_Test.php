<?php

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for Jetpack_Client_Server.
 *
 * @covers Jetpack_Client_Server
 */
#[CoversClass( Jetpack_Client_Server::class )]
class Jetpack_Client_Server_Test extends WP_UnitTestCase {
	use \Automattic\Jetpack\PHPUnit\WP_UnitTestCase_Fix;

	/**
	 * Set up before class.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();
		self::$ignore_files = true;
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_jetpack_client_server_initialize() {
		$client_server = new Jetpack_Client_Server();

		$this->assertNotNull( $client_server );
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_jetpack_client_server_authorize_role_cap() {
		$author_id = self::factory()->user->create(
			array(
				'role' => 'administrator',
			)
		);
		wp_set_current_user( $author_id );

		// @todo Does this actually test Jetpack_Client_Server at all after #14043?

		$result = Jetpack::connection()->authorize();

		$this->assertNotEquals( 'no_role', $result->get_error_code() );
		$this->assertNotEquals( 'no_cap', $result->get_error_code() );
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_jetpack_client_server_authorize_no_role() {
		$author_id = self::factory()->user->create(
			array(
				'role' => 'imagination_mover',
			)
		);
		wp_set_current_user( $author_id );

		// @todo Does this actually test Jetpack_Client_Server at all after #14043?

		$result = Jetpack::connection()->authorize();

		$this->assertEquals( 'no_role', $result->get_error_code() );
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_jetpack_client_server_authorize_data_error() {
		$author_id = self::factory()->user->create(
			array(
				'role' => 'administrator',
			)
		);
		wp_set_current_user( $author_id );

		// @todo Does this actually test Jetpack_Client_Server at all after #14043?

		$result = Jetpack::connection()->authorize( array( 'error' => 'test_error' ) );

		$this->assertEquals( 'test_error', $result->get_error_code() );
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_jetpack_client_server_deactivate_plugin() {
		$client_server = new Jetpack_Client_Server();

		$return_value = $client_server->deactivate_plugin( 'herp', 'derp' );

		$this->assertSame( 0, $return_value );
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_jetpack_client_server_get_token() {
		$author_id = self::factory()->user->create(
			array(
				'role' => 'administrator',
			)
		);
		wp_set_current_user( $author_id );

		$return_value = Jetpack::connection()->get_token( 'test' );

		$this->assertInstanceOf( 'WP_Error', $return_value );
	}
}
