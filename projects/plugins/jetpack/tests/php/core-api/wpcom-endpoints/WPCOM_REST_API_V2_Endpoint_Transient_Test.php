<?php
/**
 * Tests for /wpcom/v2/transients endpoints.
 */

use PHPUnit\Framework\Attributes\CoversClass;
use WpOrg\Requests\Requests;

require_once dirname( __DIR__, 2 ) . '/lib/Jetpack_REST_TestCase.php';

/**
 * Class WPCOM_REST_API_V2_Endpoint_Transient_Test
 *
 * @covers \WPCOM_REST_API_V2_Endpoint_Transient
 */
#[CoversClass( WPCOM_REST_API_V2_Endpoint_Transient::class )]
class WPCOM_REST_API_V2_Endpoint_Transient_Test extends Jetpack_REST_TestCase {

	/**
	 * Mock user ID.
	 *
	 * @var int
	 */
	private static $user_id = 0;

	/**
	 * Name of test transient.
	 *
	 * @var string
	 */
	private static $transient_name;

	/**
	 * Value of test transient.
	 *
	 * @var array
	 */
	private $transient_value = array( 'setting' => 'value' );

	/**
	 * Create shared database fixtures.
	 *
	 * @param WP_UnitTest_Factory $factory Fixture factory.
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		static::$user_id        = $factory->user->create( array( 'role' => 'editor' ) );
		static::$transient_name = 'jetpack_connected_user_data_' . static::$user_id;
	}

	/**
	 * Setup the environment for a test.
	 */
	public function set_up() {
		parent::set_up();

		wp_set_current_user( static::$user_id );
		set_transient( static::$transient_name, $this->transient_value );
	}

	/**
	 * Tests the permission check.
	 */
	public function test_delete_transient_permissions_check() {
		wp_set_current_user( 0 );

		$request  = new WP_REST_Request( Requests::DELETE, '/wpcom/v2/transients/' . static::$transient_name );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'authorization_required', $response, 403 );
	}

	/**
	 * Tests delete transient.
	 */
	public function test_delete_transient() {
		$request  = new WP_REST_Request( Requests::DELETE, '/wpcom/v2/transients/' . static::$transient_name );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( array( 'success' => true ), $response->get_data() );
		$this->assertFalse( get_transient( static::$transient_name ) );
	}
}
