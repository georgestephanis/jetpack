<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase
/**
 * Trait to cache HTTP requests for unit tests.
 *
 * @package automattic/jetpack
 */

namespace Automattic\Jetpack\Tests;

use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use ReflectionClass;
use UnexpectedValueException;
use WpOrg\Requests\Utility\CaseInsensitiveDictionary;

/**
 * Trait to cache HTTP requests for unit tests.
 *
 * The trait can be used in two ways. By default, it reads the cache file.
 * If you set the static property `$update_cache` to true, it will instead
 * write to the cache file.
 */
trait HttpRequestCacheTrait {

	/**
	 * Whether to update the cache instead of reading it.
	 *
	 * @var bool
	 */
	protected static $update_cache = false;

	/**
	 * Cache array.
	 *
	 * @var array
	 */
	protected static $request_cache = array();

	/**
	 * Args for WP_Http::request that we care about.
	 *
	 * @var array
	 */
	protected static $request_args = array( 'method', 'body' );

	/**
	 * From WP_UnitTestCase_Base.
	 *
	 * @var array
	 */
	protected static $hooks_saved = array();

	/** From WP_UnitTestCase_Base. */
	abstract protected function _backup_hooks(); // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore

	/**
	 * Determine the cache filename.
	 *
	 * @return string
	 */
	private static function get_http_request_cache_filename() {
		$rc       = new ReflectionClass( static::class );
		$filename = $rc->getFileName();
		if ( str_ends_with( $filename, '.php' ) ) {
			$filename = substr( $filename, 0, -4 );
		}
		return $filename . '-HttpRequestCache.json';
	}

	/**
	 * Set up the test class.
	 *
	 * @beforeClass
	 */
	#[BeforeClass]
	public static function setup_http_request_cache_before_class() {
		if ( ! static::$update_cache ) {
			$filename = self::get_http_request_cache_filename();
			if ( file_exists( $filename ) ) {
				static::$request_cache = (array) json_decode( file_get_contents( $filename ), true );
			}
		}
	}

	/**
	 * Tear down the test class.
	 *
	 * @afterClass
	 */
	#[AfterClass]
	public static function teardown_http_request_cache_after_class() {
		if ( static::$update_cache ) {
			$filename = self::get_http_request_cache_filename();
			if ( array() !== static::$request_cache ) {
				file_put_contents(
					$filename,
					json_encode( static::$request_cache, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
				);
			} elseif ( file_exists( $filename ) ) {
				unlink( $filename );
			}
		}
	}

	/**
	 * Set up the test.
	 *
	 * @before
	 */
	#[Before]
	public function setup_http_request_cache() {
		// This gets called before WP_UnitTestCase_Base::set_up(), so make sure the hooks are saved before we start adding some
		// so they'll get removed correctly by WP_UnitTestCase_Base::tear_down().
		if ( ! self::$hooks_saved ) {
			$this->_backup_hooks();
		}

		$request_args = array_flip( static::$request_args );
		if ( ! static::$update_cache ) {
			add_filter(
				'pre_http_request',
				function ( $preempt, $parsed_args, $url ) use ( $request_args ) {
					if ( $preempt ) {
						// Something else already overrode it.
						return $preempt;
					}
					if ( ! isset( static::$request_cache[ $url ] ) ) {
						throw new UnexpectedValueException( "No cache for $url" );
					}
					$args = array_intersect_key( $parsed_args, $request_args );
					ksort( $args );
					foreach ( static::$request_cache[ $url ] as $data ) {
						if ( $data['args'] === $args ) {
							$ret = $data['response'];
							if ( is_string( $ret ) ) {
								$ret = unserialize( $ret );
							} elseif ( is_array( $ret ) && isset( $ret['headers'] ) ) {
								$headers = new CaseInsensitiveDictionary();
								foreach ( $ret['headers'] as $k => $v ) {
									$headers[ $k ] = $v;
								}
								$ret['headers'] = $headers;
							}
							return $ret;
						}
					}
					throw new UnexpectedValueException( "No cache for $url with the specified arguments\n" . var_export( $args, true ) );
				},
				90,
				3
			);
		} else {
			add_action(
				'http_api_debug',
				function ( $response, $context, $class, $parsed_args, $url ) use ( $request_args ) {
					$args = array_intersect_key( $parsed_args, $request_args );
					ksort( $args );

					if ( is_object( $response ) ) {
						// Probably a WP_Error.
						$response = serialize( $response );
					} elseif ( is_array( $response ) ) {
						// We probably don't care about most of these fields. If it turns out you do, comment
						// out the appropriate lines temporarily (or just add them back to the json manually).
						$response['headers'] = iterator_to_array( $response['headers'] );
						unset( $response['http_response'] );
						unset( $response['cookies'] );
						unset( $response['headers'] );
						unset( $response['filename'] );
					}

					if ( isset( static::$request_cache[ $url ] ) ) {
						foreach ( static::$request_cache[ $url ] as $data ) {
							if ( $data['args'] === $args ) {
								// Duplicate. Hope the response is functionally the same.
								return;
							}
						}
					}
					static::$request_cache[ $url ][] = array(
						'args'     => $args,
						'response' => $response,
					);
				},
				10,
				5
			);
		}
	}

	/**
	 * Fail tests if `$update_cache` is set.
	 */
	public function test_update_cache_setting() {
		\PHPUnit\Framework\Assert::assertFalse( static::$update_cache, __CLASS__ . '::$update_cache cannot be set for tests to pass' );
	}
}
