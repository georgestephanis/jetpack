<?php
/**
 * Tokens functionality testing.
 *
 * @package automattic/jetpack-connection
 */

namespace Automattic\Jetpack\Connection;

use Automattic\Jetpack\Constants;
use DateTime;
use Jetpack_Options;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WP_Error;
use WpOrg\Requests\Utility\CaseInsensitiveDictionary;

/**
 * Tokens functionality testing.
 *
 * @covers \Automattic\Jetpack\Connection\Tokens
 */
#[CoversClass( Tokens::class )]
class TokensTest extends TestCase {

	/**
	 * Used by filters to set the current `site_url`.
	 *
	 * @var string
	 */
	private $site_url;

	/**
	 * Tokens mock object.
	 *
	 * @var Tokens
	 */
	private $tokens;

	/**
	 * Initialize the object before running the test method.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->tokens = $this->getMockBuilder( 'Automattic\Jetpack\Connection\Tokens' )
			->onlyMethods( array( 'get_access_token' ) )
			->getMock();
	}

	/**
	 * Clean up the testing environment.
	 */
	public function tearDown(): void {
		parent::tearDown();
		remove_all_filters( 'jetpack_options' );
		unset( $this->tokens );
		Constants::clear_constants();
	}

	/**
	 * Test the `validate` functionality when the site is not registered.
	 */
	public function test_validate_when_site_is_not_registered() {
		$expected = new WP_Error( 'site_not_registered', 'Site not registered.' );
		$this->assertEquals( $expected, $this->tokens->validate() );
	}

	/**
	 * Test the `validate` functionality when the current user is not connnected, aka user token is missing.
	 */
	public function test_validate_with_missing_user_token() {
		add_filter(
			'jetpack_options',
			function ( $value, $name ) {
				return 'id' === $name ? 123 : $value;
			},
			10,
			2
		);
		$blog_token = (object) array(
			'secret'           => 'abcd.1234',
			'external_user_id' => null,
		);

		$user_token = false;

		$this->tokens->expects( $this->exactly( 2 ) )
			->method( 'get_access_token' )
			->willReturnOnConsecutiveCalls( $blog_token, $user_token );
		$this->assertFalse( $this->tokens->validate() );
	}

	/**
	 * Test the `validate` functionality when the remote request to the `jetpack-token-health` endpoint fails.
	 */
	public function test_validate_with_failed_remote_request() {
		add_filter(
			'jetpack_options',
			function ( $value, $name ) {
				return 'id' === $name ? 123 : $value;
			},
			10,
			2
		);
		add_filter( 'pre_http_request', array( $this, 'intercept_jetpack_token_health_request_failed' ), 10, 3 );
		$blog_token = (object) array(
			'secret'           => 'abcd.1234',
			'external_user_id' => null,
		);

		$user_token = (object) array(
			'secret'           => 'abcd.4321',
			'external_user_id' => 1,
		);

		$this->tokens->expects( $this->exactly( 2 ) )
			->method( 'get_access_token' )
			->willReturnOnConsecutiveCalls( $blog_token, $user_token );

		$this->assertFalse( $this->tokens->validate() );

		remove_filter( 'pre_http_request', array( $this, 'intercept_jetpack_token_health_request_failed' ), 10 );
	}

	/**
	 * Test the `validate` functionality when the remote request to the `jetpack-token-health` endpoint succeeds.
	 */
	public function test_validate() {
		add_filter(
			'jetpack_options',
			function ( $value, $name ) {
				return 'id' === $name ? 123 : $value;
			},
			10,
			2
		);
		add_filter( 'pre_http_request', array( $this, 'intercept_jetpack_token_health_request_success' ), 10, 3 );
		$blog_token = (object) array(
			'secret'           => 'abcd.1234',
			'external_user_id' => null,
		);

		$user_token = (object) array(
			'secret'           => 'abcd.4321',
			'external_user_id' => 1,
		);

		$this->tokens->expects( $this->exactly( 2 ) )
			->method( 'get_access_token' )
			->willReturnOnConsecutiveCalls( $blog_token, $user_token );

		$expected = array(
			'blog_token' => array(
				'is_healthy' => true,
			),
			'user_token' => array(
				'is_healthy'     => true,
				'is_master_user' => true,
			),
		);
		$this->assertSame( $expected, $this->tokens->validate() );

		remove_filter( 'pre_http_request', array( $this, 'intercept_jetpack_token_health_request_success' ), 10 );
	}

	/**
	 * Test the `get_signed_token` functionality.
	 */
	public function test_get_signed_token() {
		$access_token = (object) array(
			'external_user_id' => 1,
		);

		// Missing secret.
		$invalid_token_error = new WP_Error( 'invalid_token' );
		$this->assertEquals( $invalid_token_error, ( new Tokens() )->get_signed_token( $access_token ) );
		// Secret is null.
		$access_token->secret = null;
		$this->assertEquals( $invalid_token_error, ( new Tokens() )->get_signed_token( $access_token ) );
		// Secret is empty.
		$access_token->secret = '';
		$this->assertEquals( $invalid_token_error, ( new Tokens() )->get_signed_token( $access_token ) );
		// Valid secret.
		$access_token->secret = 'abcd.1234';

		$signed_token = ( new Tokens() )->get_signed_token( $access_token );

		$this->assertStringContainsString( 'token', $signed_token );
		$this->assertStringContainsString( 'timestamp', $signed_token );
		$this->assertStringContainsString( 'nonce', $signed_token );
		$this->assertStringContainsString( 'signature', $signed_token );
	}

	/**
	 * Intercept the `jetpack-token-health` API request sent to WP.com, and mock failed response.
	 *
	 * @param bool|array $response The existing response.
	 * @param array      $args The request arguments.
	 * @param string     $url The request URL.
	 *
	 * @return array
	 */
	public function intercept_jetpack_token_health_request_failed( $response, $args, $url ) {
		if ( ! str_contains( $url, 'jetpack-token-health' ) ) {
			return $response;
		}

		return array(
			'headers'  => new CaseInsensitiveDictionary( array( 'content-type' => 'application/json' ) ),
			'body'     => wp_json_encode( array( 'dummy_error' => true ) ),
			'response' => array(
				'code'    => 500,
				'message' => 'failed',
			),
		);
	}

	/**
	 * Intercept the `jetpack-token-health` API request sent to WP.com, and mock successful response.
	 *
	 * @param bool|array $response The existing response.
	 * @param array      $args The request arguments.
	 * @param string     $url The request URL.
	 *
	 * @return array
	 */
	public function intercept_jetpack_token_health_request_success( $response, $args, $url ) {
		if ( ! str_contains( $url, 'jetpack-token-health' ) ) {
			return $response;
		}

		$body = array(
			'blog_token' => array(
				'is_healthy' => true,
			),
			'user_token' => array(
				'is_healthy'     => true,
				'is_master_user' => true,
			),
		);

		return array(
			'headers'  => new CaseInsensitiveDictionary( array( 'content-type' => 'application/json' ) ),
			'body'     => wp_json_encode( $body ),
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
		);
	}

	/**
	 * Test the locking/unlocking tokens functionality.
	 */
	public function test_set_lock() {
		$tokens = new Tokens();

		$this->site_url = 'https://test1.example.org';

		add_filter( 'jetpack_sync_site_url', array( $this, 'filter_site_url' ), 10 );

		$lock_set = $tokens->set_lock( DAY_IN_SECONDS );

		list( $lock_expiration, $lock_site_url ) = explode( '|||', Jetpack_Options::get_option( 'token_lock' ), 2 );
		$is_locked                               = $tokens->is_locked();

		$this->site_url  = 'https://test2.example.org';
		$is_locked_site2 = $tokens->is_locked();

		$tokens->remove_lock();
		$is_locked_still = $tokens->is_locked();

		static::assertTrue( $lock_set );
		static::assertFalse( $is_locked );
		static::assertTrue( $is_locked_site2 );
		static::assertFalse( $is_locked_still );

		static::assertSame( 'https://test1.example.org', base64_decode( $lock_site_url ) );

		$date = $lock_expiration ? DateTime::createFromFormat( Tokens::DATE_FORMAT_ATOM, $lock_expiration )->format( 'Y-m-d' ) : false;
		static::assertSame( gmdate( 'Y-m-d', strtotime( 'tomorrow' ) ), $date );

		remove_filter( 'jetpack_sync_site_url', array( $this, 'filter_site_url' ), 10 );
	}

	/**
	 * Test the auto-unlocking tokens functionality.
	 */
	public function test_unlock() {
		$tokens = new Tokens();

		$this->site_url = 'https://test1.example.org';

		add_filter( 'jetpack_sync_site_url', array( $this, 'filter_site_url' ), 10 );

		$tokens->set_lock( 1 );

		$this->site_url = 'https://test2.example.org';
		$is_locked      = $tokens->is_locked();

		sleep( 2 );

		$is_locked_expired_non_matching = $tokens->is_locked();
		$still_locked                   = (bool) Jetpack_Options::get_option( 'token_lock' );

		$this->site_url             = 'https://test1.example.org';
		$is_locked_expired_matching = $tokens->is_locked();
		$no_longer_locked           = (bool) Jetpack_Options::get_option( 'token_lock' );

		static::assertTrue( $is_locked );
		static::assertTrue( $still_locked );
		static::assertTrue( $is_locked_expired_non_matching );
		static::assertFalse( $is_locked_expired_matching );
		static::assertFalse( $no_longer_locked );

		remove_filter( 'jetpack_sync_site_url', array( $this, 'filter_site_url' ), 10 );
	}

	/**
	 * Filter to get the current site URL.
	 *
	 * @return string
	 */
	public function filter_site_url() {
		return $this->site_url;
	}
}
