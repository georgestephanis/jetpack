<?php
/**
 * REST Authentication functionality testing.
 *
 * @package automattic/jetpack-connection
 */

namespace Automattic\Jetpack\Connection;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * REST Authentication functionality testing.
 *
 * @covers \Automattic\Jetpack\Connection\REST_Authentication
 */
#[CoversClass( REST_Authentication::class )]
class REST_Authentication_Test extends TestCase {

	/**
	 * Rest_Authentication instance.
	 *
	 * @var Rest_Authentication
	 */
	protected $rest_authentication;

	/**
	 * Connection manager mock object.
	 *
	 * @var Manager
	 */
	protected $manager;

	/**
	 * Delete any cached Rest_Authentication singleton.
	 */
	private static function clear_auth_singleton() {
		$reflection_class  = new \ReflectionClass( Rest_Authentication::class );
		$instance_property = $reflection_class->getProperty( 'instance' );
		$instance_property->setAccessible( true );
		$instance_property->setValue( null, null );
	}

	/**
	 * Setting up the test.
	 */
	public function setUp(): void {
		parent::setUp();
		self::clear_auth_singleton();
		$this->rest_authentication = Rest_Authentication::init();

		$this->manager = $this->getMockBuilder( Manager::class )
			->onlyMethods( array( 'verify_xml_rpc_signature', 'reset_saved_auth_state' ) )
			->getMock();

		$reflection_class = new \ReflectionClass( get_class( $this->rest_authentication ) );
		$manager_property = $reflection_class->getProperty( 'connection_manager' );
		$manager_property->setAccessible( true );
		$manager_property->setValue( $this->rest_authentication, $this->manager );
	}

	/**
	 * Returning the environment into its initial state.
	 */
	public function tearDown(): void {
		parent::tearDown();
		$_GET = null;
		unset( $_SERVER['REQUEST_METHOD'] );
		$this->rest_authentication->reset_saved_auth_state();
		self::clear_auth_singleton();
	}

	/**
	 * Tests wp_rest_authentication_errors with an incoming error.
	 */
	public function test_wp_rest_authentication_errors_existing_error() {
		$error = new \WP_Error( 'test_error', 'This is a test error' );
		$this->assertEquals( $error, $this->rest_authentication->wp_rest_authentication_errors( $error ) );
	}

	/**
	 * Tests wp_rest_authenticate with an incoming user id.
	 */
	public function test_wp_rest_authenticate_existing_user() {
		$user_id = 123;
		$this->assertEquals( $user_id, $this->rest_authentication->wp_rest_authenticate( $user_id ) );
	}

	/**
	 * Tests wp_rest_authenticate with an incoming user id.
	 *
	 * @param array $test_inputs      The array containing the test inputs.
	 * @param array $expected_outputs The array containg the expected test outputs.
	 *
	 * @dataProvider wp_rest_authenticate_data_provider
	 */
	#[DataProvider( 'wp_rest_authenticate_data_provider' )]
	public function test_wp_rest_authenticate( $test_inputs, $expected_outputs ) {
		$_GET = $test_inputs['get_params'];
		if ( isset( $test_inputs['request_method'] ) ) {
			$_SERVER['REQUEST_METHOD'] = $test_inputs['request_method'];
		}

		$this->manager->expects( $this->any() )
			->method( 'verify_xml_rpc_signature' )
			->willReturn( $test_inputs['verified'] );

		$this->assertEquals( $expected_outputs['authenticate'], $this->rest_authentication->wp_rest_authenticate( '' ) );

		if ( is_string( $expected_outputs['errors'] ) ) {
			$this->assertInstanceOf( $expected_outputs['errors'], $this->rest_authentication->wp_rest_authentication_errors( null ) );
		} else {
			$this->assertEquals( $expected_outputs['errors'], $this->rest_authentication->wp_rest_authentication_errors( null ) );
		}
	}

	/**
	 * The data provider for test_wp_rest_authenticate.
	 *
	 * @return array An array containg the test inputs and expected outputs. Each test array has the format:
	 *     ['test_inputs'] => [
	 *         ['get'] =>
	 *             ['_for'] => (string) The _for parameter value. Optional.
	 *             ['token'] => (string) The token parameter value. Optional.
	 *             ['signature'] => (string) The signature parameter value. Optional.
	 *         ['request_method'] => (string) The request method. Optional.
	 *         ['verified'] => (false|array) The mocked return value of Manager::verify_xml_rpc_signature. Required.
	 *     ],
	 *     ['expected_outputs'] => [
	 *         ['authenticate'] (int|null) The expected return value of wp_rest_authenticate. Required.
	 *         ['errors'] (null|string|true) The expected return value of wp_rest_authenticate_errors. If the value is
	 *                                       a string, this is the expected class of the object returned by
	 *                                       wp_rest_authenticate_errors. Required.
	 *     ]
	 */
	public static function wp_rest_authenticate_data_provider() {
		$token_data = array(
			'type'      => 'user',
			'token_key' => '123abc',
			'user_id'   => 123,
		);

		$blog_token_data = array(
			'type'      => 'blog',
			'token_key' => '123.abc',
			'user_id'   => 0,
		);

		return array(
			'no for parameter'                   => array(
				'test_inputs'      => array(
					'get_params'     => array(
						'token'     => 'token',
						'signature' => 'signature',
					),
					'request_method' => 'GET',
					'verified'       => $token_data,
				),
				'expected_outputs' => array(
					'authenticate' => null,
					'errors'       => null,
				),
			),
			'for parameter is not jetpack'       => array(
				'test_inputs'      => array(
					'get_params'     => array(
						'_for'      => 'not_jetpack',
						'token'     => 'token',
						'signature' => 'signature',
					),
					'request_method' => 'GET',
					'verified'       => $token_data,
				),
				'expected_outputs' => array(
					'authenticate' => null,
					'errors'       => null,
				),
			),
			'no token or signature parameter'    => array(
				'test_inputs'      => array(
					'get_params'     => array(
						'_for' => 'jetpack',
					),
					'request_method' => 'GET',
					'verified'       => $token_data,
				),
				'expected_outputs' => array(
					'authenticate' => null,
					'errors'       => null,
				),
			),
			'no request method'                  => array(
				'test_inputs'      => array(
					'get_params' => array(
						'_for'      => 'jetpack',
						'token'     => 'token',
						'signature' => 'signature',
					),
					'verified'   => $token_data,
				),
				'expected_outputs' => array(
					'authenticate' => null,
					'errors'       => 'WP_Error',
				),
			),
			'invalid request method'             => array(
				'test_inputs'      => array(
					'get_params'     => array(
						'_for'      => 'jetpack',
						'token'     => 'token',
						'signature' => 'signature',
					),
					'request_method' => 'DELETE',
					'verified'       => $token_data,
				),
				'expected_outputs' => array(
					'authenticate' => null,
					'errors'       => 'WP_Error',
				),
			),
			'successful GET request'             => array(
				'test_inputs'      => array(
					'get_params'     => array(
						'_for'      => 'jetpack',
						'token'     => 'token',
						'signature' => 'signature',
					),
					'request_method' => 'GET',
					'verified'       => $token_data,
				),
				'expected_outputs' => array(
					'authenticate' => $token_data['user_id'],
					'errors'       => true,
				),
			),
			'successful POST request'            => array(
				'test_inputs'      => array(
					'get_params'     => array(
						'_for'      => 'jetpack',
						'token'     => 'token',
						'signature' => 'signature',
					),
					'request_method' => 'POST',
					'verified'       => $token_data,
				),
				'expected_outputs' => array(
					'authenticate' => $token_data['user_id'],
					'errors'       => true,
				),
			),
			'signature verification failed'      => array(
				'test_inputs'      => array(
					'get_params'     => array(
						'_for'      => 'jetpack',
						'token'     => 'token',
						'signature' => 'signature',
					),
					'request_method' => 'GET',
					'verified'       => false,
				),
				'expected_outputs' => array(
					'authenticate' => null,
					'errors'       => 'WP_Error',
				),
			),
			'successful GET request blog token'  => array(
				'test_inputs'      => array(
					'get_params'     => array(
						'_for'      => 'jetpack',
						'token'     => 'token',
						'signature' => 'signature',
					),
					'request_method' => 'GET',
					'verified'       => $blog_token_data,
				),
				'expected_outputs' => array(
					'authenticate' => null,
					'errors'       => true,
				),
			),
			'successful POST request blog token' => array(
				'test_inputs'      => array(
					'get_params'     => array(
						'_for'      => 'jetpack',
						'token'     => 'token',
						'signature' => 'signature',
					),
					'request_method' => 'POST',
					'verified'       => $blog_token_data,
				),
				'expected_outputs' => array(
					'authenticate' => null,
					'errors'       => true,
				),
			),
		);
	}

	/**
	 * Tests is_signed_with_blog_token.
	 *
	 * @param array $test_inputs The array containing the test inputs.
	 * @param bool  $expected    The array containg the expected test outputs.
	 *
	 * @dataProvider is_signed_with_blog_token_data_provider
	 */
	#[DataProvider( 'is_signed_with_blog_token_data_provider' )]
	public function test_is_signed_with_blog_token( $test_inputs, $expected ) {
		$_GET = $test_inputs['get_params'];
		if ( isset( $test_inputs['request_method'] ) ) {
			$_SERVER['REQUEST_METHOD'] = $test_inputs['request_method'];
		}

		$this->manager->expects( $this->any() )
			->method( 'verify_xml_rpc_signature' )
			->willReturn( $test_inputs['verified'] );

		$this->rest_authentication->wp_rest_authenticate( '' );

		$this->assertSame( $expected, Rest_Authentication::is_signed_with_blog_token() );
	}

	/**
	 * The data provider for test_wp_rest_authenticate.
	 *
	 * @return array An array containg the test inputs and expected outputs. Each test array has the format:
	 *     ['test_inputs'] => [
	 *         ['get'] =>
	 *             ['_for'] => (string) The _for parameter value. Optional.
	 *             ['token'] => (string) The token parameter value. Optional.
	 *             ['signature'] => (string) The signature parameter value. Optional.
	 *         ['request_method'] => (string) The request method. Optional.
	 *         ['verified'] => (false|array) The mocked return value of Manager::verify_xml_rpc_signature. Required.
	 *     ],
	 *     ['expected'] => (bool) The expected return value of wp_rest_authenticate. Required.
	 */
	public static function is_signed_with_blog_token_data_provider() {
		$token_data = array(
			'type'      => 'user',
			'token_key' => '123abc',
			'user_id'   => 123,
		);

		$blog_token_data = array(
			'type'      => 'blog',
			'token_key' => '123.abc',
			'user_id'   => 0,
		);

		return array(
			'no for parameter'                   => array(
				'test_inputs' => array(
					'get_params'     => array(
						'token'     => 'token',
						'signature' => 'signature',
					),
					'request_method' => 'GET',
					'verified'       => $token_data,
				),
				'expected'    => false,
			),
			'for parameter is not jetpack'       => array(
				'test_inputs' => array(
					'get_params'     => array(
						'_for'      => 'not_jetpack',
						'token'     => 'token',
						'signature' => 'signature',
					),
					'request_method' => 'GET',
					'verified'       => $token_data,
				),
				'expected'    => false,
			),
			'no token or signature parameter'    => array(
				'test_inputs' => array(
					'get_params'     => array(
						'_for' => 'jetpack',
					),
					'request_method' => 'GET',
					'verified'       => $token_data,
				),
				'expected'    => false,
			),
			'no request method'                  => array(
				'test_inputs' => array(
					'get_params' => array(
						'_for'      => 'jetpack',
						'token'     => 'token',
						'signature' => 'signature',
					),
					'verified'   => $token_data,
				),
				'expected'    => false,
			),
			'invalid request method'             => array(
				'test_inputs' => array(
					'get_params'     => array(
						'_for'      => 'jetpack',
						'token'     => 'token',
						'signature' => 'signature',
					),
					'request_method' => 'DELETE',
					'verified'       => $token_data,
				),
				'expected'    => false,
			),
			'successful GET request'             => array(
				'test_inputs' => array(
					'get_params'     => array(
						'_for'      => 'jetpack',
						'token'     => 'token',
						'signature' => 'signature',
					),
					'request_method' => 'GET',
					'verified'       => $token_data,
				),
				'expected'    => false,
			),
			'successful POST request'            => array(
				'test_inputs' => array(
					'get_params'     => array(
						'_for'      => 'jetpack',
						'token'     => 'token',
						'signature' => 'signature',
					),
					'request_method' => 'POST',
					'verified'       => $token_data,
				),
				'expected'    => false,
			),
			'signature verification failed'      => array(
				'test_inputs' => array(
					'get_params'     => array(
						'_for'      => 'jetpack',
						'token'     => 'token',
						'signature' => 'signature',
					),
					'request_method' => 'GET',
					'verified'       => false,
				),
				'expected'    => false,
			),
			'successful GET request blog token'  => array(
				'test_inputs' => array(
					'get_params'     => array(
						'_for'      => 'jetpack',
						'token'     => 'token',
						'signature' => 'signature',
					),
					'request_method' => 'GET',
					'verified'       => $blog_token_data,
				),
				'expected'    => true,
			),
			'successful POST request blog token' => array(
				'test_inputs' => array(
					'get_params'     => array(
						'_for'      => 'jetpack',
						'token'     => 'token',
						'signature' => 'signature',
					),
					'request_method' => 'POST',
					'verified'       => $blog_token_data,
				),
				'expected'    => true,
			),
		);
	}
}
