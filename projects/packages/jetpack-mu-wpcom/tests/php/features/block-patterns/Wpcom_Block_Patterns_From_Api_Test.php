<?php
/**
 * Test class for Wpcom_Block_Patterns_From_Api.
 *
 * @package automattic/jetpack-mu-wpcom
 */

use Automattic\Jetpack\Jetpack_Mu_Wpcom;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;

require_once Jetpack_Mu_Wpcom::PKG_DIR . 'src/features/block-patterns/block-patterns.php';
require_once Jetpack_Mu_Wpcom::PKG_DIR . 'src/features/block-patterns/class-wpcom-block-patterns-from-api.php';

/**
 * Tests block pattern registration in the API context.
 */
class Wpcom_Block_Patterns_From_Api_Test extends TestCase {
	/**
	 * Representation of a Pattern as returned by the API.
	 *
	 * @var array
	 */
	protected $pattern_mock_object;

	/**
	 * Pre-test setup.
	 *
	 * @before
	 */
	#[Before]
	public function custom_setup() {
		parent::setUp();
		$this->pattern_mock_object = array(
			'ID'            => '1',
			'site_id'       => '2',
			'title'         => 'test title',
			'name'          => 'test pattern name',
			'description'   => 'test description',
			'html'          => '<p>test</p>',
			'source_url'    => 'http;//test',
			'modified_date' => 'dd:mm:YY',
			'categories'    => array(
				'test_slug' => array(
					'title'       => 'category title',
					'description' => 'category description',
				),
			),
		);
	}

	/**
	 *  Returns a mock of Wpcom_Block_Patterns_Utils.
	 *
	 * @param array      $pattern_mock_response     What we want Wpcom_Block_Patterns_Utils->remote_get() to return.
	 * @param bool|array $cache_get                 What we want Wpcom_Block_Patterns_Utils->cache_get() to return.
	 * @param bool       $cache_add                 What we want Wpcom_Block_Patterns_Utils->cache_add() to return.
	 * @param string     $get_patterns_cache_key    What we want Wpcom_Block_Patterns_Utils->get_patterns_cache_key() to return.
	 * @param string     $get_block_patterns_locale What we want Wpcom_Block_Patterns_Utils->get_block_patterns_locale() to return.
	 * @return Wpcom_Block_Patterns_Utils&\PHPUnit\Framework\MockObject\MockObject PHP Unit mock object.
	 */
	public function createBlockPatternsUtilsMock( $pattern_mock_response, $cache_get = false, $cache_add = true, $get_patterns_cache_key = 'key-largo', $get_block_patterns_locale = 'fr' ) {
		$mock = $this->createMock( Wpcom_Block_Patterns_Utils::class );

		$mock->method( 'remote_get' )
			->willReturn( $pattern_mock_response );

		$mock->method( 'cache_get' )
			->willReturn( $cache_get );

		$mock->method( 'cache_add' )
			->willReturn( $cache_add );

		$mock->method( 'get_patterns_cache_key' )
			->willReturn( $get_patterns_cache_key );

		$mock->method( 'get_block_patterns_locale' )
			->willReturn( $get_block_patterns_locale );

		return $mock;
	}

	/**
	 *  Tests that we're making a request where there are no cached patterns.
	 */
	public function test_patterns_request_succeeds_with_empty_cache() {
		$utils_mock              = $this->createBlockPatternsUtilsMock( array( $this->pattern_mock_object ) );
		$block_patterns_from_api = new Wpcom_Block_Patterns_From_Api( $utils_mock );

		$utils_mock->expects( $this->once() )
			->method( 'cache_add' )
			->with( $this->stringContains( 'key-largo' ), array( $this->pattern_mock_object ), 'ptk_patterns', 5 * MINUTE_IN_SECONDS );

		$this->assertEquals( array( 'a8c/' . $this->pattern_mock_object['name'] => true ), $block_patterns_from_api->register_patterns() );
	}

	/**
	 *  Tests that we're making a request
	 */
	public function test_patterns_site_editor_source_site() {
		$utils_mock              = $this->createBlockPatternsUtilsMock( array( $this->pattern_mock_object ) );
		$block_patterns_from_api = new Wpcom_Block_Patterns_From_Api( $utils_mock );

		$utils_mock->expects( $this->once() )
			->method( 'remote_get' )
			->willReturn( 'https://public-api.wordpress.com/rest/v1/ptk/patterns/fr?post_type=wp_block' );

		$this->assertEquals( array( 'a8c/' . $this->pattern_mock_object['name'] => true ), $block_patterns_from_api->register_patterns() );
	}

	/**
	 *  Tests that we're NOT making a request where there ARE cached patterns.
	 */
	public function test_patterns_request_succeeds_with_set_cache() {
		$utils_mock              = $this->createBlockPatternsUtilsMock( array( $this->pattern_mock_object ), array( $this->pattern_mock_object ) );
		$block_patterns_from_api = new Wpcom_Block_Patterns_From_Api( $utils_mock );

		$utils_mock->expects( $this->once() )
			->method( 'cache_get' )
			->with( $this->stringContains( 'key-largo' ), 'ptk_patterns' );

		$utils_mock->expects( $this->never() )
			->method( 'cache_add' );

		$this->assertEquals( array( 'a8c/' . $this->pattern_mock_object['name'] => true ), $block_patterns_from_api->register_patterns() );
	}

	/**
	 *  Tests that we're making a request where we're overriding the source site.
	 */
	public function test_patterns_request_succeeds_with_override_source_site() {
		$example_site = function () {
			return 'dotcom';
		};

		add_filter( 'a8c_override_patterns_source_site', $example_site );
		$utils_mock              = $this->createBlockPatternsUtilsMock( array( $this->pattern_mock_object ) );
		$block_patterns_from_api = new Wpcom_Block_Patterns_From_Api( $utils_mock );

		$utils_mock->expects( $this->never() )
			->method( 'cache_add' );

		$utils_mock->expects( $this->once() )
			->method( 'remote_get' )
			->with( 'https://public-api.wordpress.com/rest/v1/ptk/patterns/fr?site=dotcom&post_type=wp_block' );

		$this->assertEquals( array( 'a8c/' . $this->pattern_mock_object['name'] => true ), $block_patterns_from_api->register_patterns() );

		remove_filter( 'a8c_override_patterns_source_site', $example_site );
	}

	/**
	 * Tests the given patterns registration mock against multiple REST API routes.
	 *
	 * @param object $patterns_from_api_mock A mock object for the block pattern from API class.
	 * @param array  $test_routes               An array of strings of routes to test.
	 */
	public function multiple_route_pattern_registration( $patterns_from_api_mock, $test_routes ) {
		foreach ( $test_routes as $route ) {
			$request_mock = $this->createMock( \WP_REST_Request::class );
			$request_mock->method( 'get_route' )->willReturn( $route );

			$function = register_patterns_on_api_request(
				function () use ( $patterns_from_api_mock ) {
					$patterns_from_api_mock->register_patterns();
				}
			);
			$function( null, $request_mock );
		}
	}

	/**
	 * Tests that pattern registration does occur on API routes related to block patterns.
	 */
	public function test_load_Wpcom_Block_Patterns_From_Api_runs_in_correct_request_context() {
		add_filter( 'a8c_enable_block_patterns_api', '__return_true' );
		$test_routes = array(
			'/wp/v2/block-patterns/categories',
			'/wp/v2/block-patterns/patterns',
			'/wp/v2/sites/178915379/block-patterns/categories',
			'/wp/v2/sites/178915379/block-patterns/patterns',
		);

		$patterns_mock = $this->createMock( Wpcom_Block_Patterns_From_Api::class );
		$patterns_mock->expects( $this->exactly( count( $test_routes ) ) )->method( 'register_patterns' );

		$this->multiple_route_pattern_registration( $patterns_mock, $test_routes );
	}

	/**
	 * Tests that pattern registration does not occur on rest API routes unrelated
	 * to block patterns.
	 */
	public function test_load_Wpcom_Block_Patterns_From_Api_is_skipped_in_wrong_request_context() {
		add_filter( 'a8c_enable_block_patterns_api', '__return_true' );

		$test_routes = array(
			'/rest/v1.1/help/olark/mine',
			'/wpcom/v2/sites/178915379/post-counts',
			'/rest/v1.1/me/shopping-cart/',
			'/wpcom/v3/sites/178915379/gutenberg',
			'/wp/v2/sites/178915379/types',
			'/wp/v2/sites/178915379/block-patterns/3ategories',
			'/wp/v2//block-patterns/patterns',
			'/wp/v2block-patterns/categories',
			'/wp/v2/123/block-patterns/categories',
		);

		$patterns_mock = $this->createMock( Wpcom_Block_Patterns_From_Api::class );
		$patterns_mock->expects( $this->never() )->method( 'register_patterns' );

		$this->multiple_route_pattern_registration( $patterns_mock, $test_routes );
	}
}
