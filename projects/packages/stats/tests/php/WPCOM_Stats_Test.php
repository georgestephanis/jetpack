<?php
/**
 * Tests WPCOM_Stats class.
 *
 * @package jetpack-stats
 */

namespace Automattic\Jetpack\Stats;

use PHPUnit\Framework\Attributes\CoversClass;
use WP_Error;

/**
 * Class to test the WPCOM_Stats class.
 *
 * @covers Automattic\Jetpack\Stats\WPCOM_Stats
 */
#[CoversClass( WPCOM_Stats::class )]
class WPCOM_Stats_Test extends StatsBaseTestCase {
	/**
	 * Mocked WPCOM_Stats.
	 *
	 * @var WPCOM_Stats&\PHPUnit\Framework\MockObject\MockObject
	 */
	protected $wpcom_stats;

	/**
	 * Set up before each test
	 */
	protected function set_up() {
		parent::set_up();

		$this->wpcom_stats = $this->getMockBuilder( 'Automattic\Jetpack\Stats\WPCOM_Stats' )
			->onlyMethods( array( 'fetch_remote_stats', 'fetch_stats_on_wpcom_simple' ) )
			->getMock();
	}

	/**
	 * Test get_stats.
	 */
	public function test_get_stats() {
		$expected_stats = array(
			'date'   => '2022-09-29',
			'stats'  => array(),
			'visits' => array(),
		);

		$this->wpcom_stats
			->expects( $this->once() )
			->method( 'fetch_remote_stats' )
			->with(
				'/sites/1234/stats/',
				array()
			)
			->willReturn( $expected_stats );

		$stats = $this->wpcom_stats->get_stats();
		$this->assertSame( $expected_stats, $stats );
		$this->assertSame( wp_json_encode( $expected_stats ), self::get_stats_transient( '/sites/1234/stats/' ) );
	}

	/**
	 * Test get_stats_summary.
	 */
	public function test_get_stats_summary() {
		$expected_stats = array(
			'date'      => '2022-09-29',
			'period'    => 'day',
			'views'     => 0,
			'visitors'  => 0,
			'likes'     => 0,
			'reblogs'   => 0,
			'comments'  => 0,
			'followers' => 1,
		);

		$this->wpcom_stats
			->expects( $this->once() )
			->method( 'fetch_remote_stats' )
			->with(
				'/sites/1234/stats/summary',
				array()
			)
			->willReturn( $expected_stats );

		$stats = $this->wpcom_stats->get_stats_summary();
		$this->assertSame( $expected_stats, $stats );
		$this->assertSame( wp_json_encode( $expected_stats ), self::get_stats_transient( '/sites/1234/stats/summary' ) );
	}

	/**
	 * Test get_top_posts.
	 */
	public function test_get_top_posts() {
		$expected_stats = array(
			'date'   => '2022-09-29',
			'days'   => array(
				'2022-09-29' => array(
					'postviews'   => array(),
					'total_views' => 0,
				),
			),
			'period' => 'day',
		);

		$this->wpcom_stats
			->expects( $this->once() )
			->method( 'fetch_remote_stats' )
			->with(
				'/sites/1234/stats/top-posts',
				array()
			)
			->willReturn( $expected_stats );

		$stats = $this->wpcom_stats->get_top_posts();
		$this->assertSame( $expected_stats, $stats );
		$this->assertSame( wp_json_encode( $expected_stats ), self::get_stats_transient( '/sites/1234/stats/top-posts' ) );
	}

	/**
	 * Test get_video_details.
	 */
	public function test_get_video_details() {
		$video_id       = 1234;
		$expected_stats = array(
			'fields' => array( 'period', 'plays' ),
			'data'   => array(
				array(
					'date' => '9-29',
					'p'    => 0,
				),
			),
			'pages'  => array(),
			'post'   => false,
		);

		$this->wpcom_stats
			->expects( $this->once() )
			->method( 'fetch_remote_stats' )
			->with(
				'/sites/1234/stats/video/' . $video_id,
				array()
			)
			->willReturn( $expected_stats );

		$stats = $this->wpcom_stats->get_video_details( $video_id );
		$this->assertSame( $expected_stats, $stats );
		$this->assertSame( wp_json_encode( $expected_stats ), self::get_stats_transient( '/sites/1234/stats/video/' . $video_id ) );
	}

	/**
	 * Test get_referrers.
	 */
	public function test_get_referrers() {
		$expected_stats = array(
			'date'   => '2022-09-29',
			'days'   => array(
				'2022-09-29' => array(
					'groups'      => array(),
					'other_views' => 0,
					'total_views' => 0,
				),
			),
			'period' => 'day',
		);

		$this->wpcom_stats
			->expects( $this->once() )
			->method( 'fetch_remote_stats' )
			->with(
				'/sites/1234/stats/referrers',
				array()
			)
			->willReturn( $expected_stats );

		$stats = $this->wpcom_stats->get_referrers();
		$this->assertSame( $expected_stats, $stats );
		$this->assertSame( wp_json_encode( $expected_stats ), self::get_stats_transient( '/sites/1234/stats/referrers' ) );
	}

	/**
	 * Test get_clicks.
	 */
	public function test_get_clicks() {
		$expected_stats = array(
			'date'   => '2022-09-29',
			'days'   => array(
				'2022-09-29' => array(
					'clicks'       => array(),
					'other_clicks' => 0,
					'total_clicks' => 0,
				),
			),
			'period' => 'day',
		);

		$this->wpcom_stats
			->expects( $this->once() )
			->method( 'fetch_remote_stats' )
			->with(
				'/sites/1234/stats/clicks',
				array()
			)
			->willReturn( $expected_stats );

		$stats = $this->wpcom_stats->get_clicks();
		$this->assertSame( $expected_stats, $stats );
		$this->assertSame( wp_json_encode( $expected_stats ), self::get_stats_transient( '/sites/1234/stats/clicks' ) );
	}

	/**
	 * Test get_tags.
	 */
	public function test_get_tags() {
		$expected_stats = array(
			'date' => '2022-09-29',
			'tags' => array(),
		);

		$this->wpcom_stats
			->expects( $this->once() )
			->method( 'fetch_remote_stats' )
			->with(
				'/sites/1234/stats/tags',
				array()
			)
			->willReturn( $expected_stats );

		$stats = $this->wpcom_stats->get_tags();
		$this->assertSame( $expected_stats, $stats );
		$this->assertSame( wp_json_encode( $expected_stats ), self::get_stats_transient( '/sites/1234/stats/tags' ) );
	}

	/**
	 * Test get_top_authors.
	 */
	public function test_get_top_authors() {
		$expected_stats = array(
			'date'   => '2022-09-29',
			'days'   => array(
				'2022-09-29' => array(
					'authors' => array(),
				),
			),
			'period' => 'day',
		);

		$this->wpcom_stats
			->expects( $this->once() )
			->method( 'fetch_remote_stats' )
			->with(
				'/sites/1234/stats/top-authors',
				array()
			)
			->willReturn( $expected_stats );

		$stats = $this->wpcom_stats->get_top_authors();
		$this->assertSame( $expected_stats, $stats );
		$this->assertSame( wp_json_encode( $expected_stats ), self::get_stats_transient( '/sites/1234/stats/top-authors' ) );
	}

	/**
	 * Test get_top_comments.
	 */
	public function test_get_top_comments() {
		$expected_stats = array(
			'date'    => '2022-09-29',
			'authors' => array(),
			'posts'   => array(),
		);

		$this->wpcom_stats
			->expects( $this->once() )
			->method( 'fetch_remote_stats' )
			->with(
				'/sites/1234/stats/comments',
				array()
			)
			->willReturn( $expected_stats );

		$stats = $this->wpcom_stats->get_top_comments();
		$this->assertSame( $expected_stats, $stats );
		$this->assertSame( wp_json_encode( $expected_stats ), self::get_stats_transient( '/sites/1234/stats/comments' ) );
	}

	/**
	 * Test get_video_plays.
	 */
	public function test_get_video_plays() {
		$expected_stats = array(
			'date'   => '2022-09-29',
			'days'   => array(
				'2022-09-29' => array(
					'plays'       => array(),
					'other_plays' => 0,
					'total_plays' => 0,
				),
			),
			'period' => 'day',
		);

		$this->wpcom_stats
			->expects( $this->once() )
			->method( 'fetch_remote_stats' )
			->with(
				'/sites/1234/stats/video-plays',
				array()
			)
			->willReturn( $expected_stats );

		$stats = $this->wpcom_stats->get_video_plays();
		$this->assertSame( $expected_stats, $stats );
		$this->assertSame( wp_json_encode( $expected_stats ), self::get_stats_transient( '/sites/1234/stats/video-plays' ) );
	}

	/**
	 * Test get_file_downloads.
	 */
	public function test_get_file_downloads() {
		$expected_stats = array(
			'date'   => '2022-09-29',
			'days'   => array(
				'2022-09-29' => array(
					'files'           => array(),
					'other_downloads' => 0,
					'total_downloads' => 0,
				),
			),
			'period' => 'day',
		);

		$this->wpcom_stats
			->expects( $this->once() )
			->method( 'fetch_remote_stats' )
			->with(
				'/sites/1234/stats/file-downloads',
				array()
			)
			->willReturn( $expected_stats );

		$stats = $this->wpcom_stats->get_file_downloads();
		$this->assertSame( $expected_stats, $stats );
		$this->assertSame( wp_json_encode( $expected_stats ), self::get_stats_transient( '/sites/1234/stats/file-downloads' ) );
	}

	/**
	 * Test get_post_views.
	 */
	public function test_get_post_views() {
		$post_id        = 1234;
		$expected_stats = array(
			'date'  => '2022-09-29',
			'views' => 0,
			'years' => array(),
		);

		$this->wpcom_stats
			->expects( $this->once() )
			->method( 'fetch_remote_stats' )
			->with(
				'/sites/1234/stats/post/' . $post_id,
				array()
			)
			->willReturn( $expected_stats );

		$stats = $this->wpcom_stats->get_post_views( $post_id );
		$this->assertSame( $expected_stats, $stats );
		$this->assertSame( wp_json_encode( $expected_stats ), self::get_stats_transient( '/sites/1234/stats/post/' . $post_id ) );
	}

	/**
	 * Test get_views_by_country.
	 */
	public function test_get_views_by_country() {
		$expected_stats = array(
			'date'         => '2022-09-29',
			'days'         => array(
				'2022-09-29' => array(
					'views'       => array(),
					'other_views' => 0,
					'total_views' => 0,
				),
			),
			'country-info' => array(),
		);

		$this->wpcom_stats
			->expects( $this->once() )
			->method( 'fetch_remote_stats' )
			->with(
				'/sites/1234/stats/country-views',
				array()
			)
			->willReturn( $expected_stats );

		$stats = $this->wpcom_stats->get_views_by_country();
		$this->assertSame( $expected_stats, $stats );
		$this->assertSame( wp_json_encode( $expected_stats ), self::get_stats_transient( '/sites/1234/stats/country-views' ) );
	}

	/**
	 * Test get_followers.
	 */
	public function test_get_followers() {
		$expected_stats = array(
			'page'        => 0,
			'pages'       => 0,
			'total'       => 0,
			'total_email' => 0,
			'total_wpcom' => 0,
			'subscribers' => array(),
		);

		$this->wpcom_stats
			->expects( $this->once() )
			->method( 'fetch_remote_stats' )
			->with(
				'/sites/1234/stats/followers',
				array()
			)
			->willReturn( $expected_stats );

		$stats = $this->wpcom_stats->get_followers();
		$this->assertSame( $expected_stats, $stats );
		$this->assertSame( wp_json_encode( $expected_stats ), self::get_stats_transient( '/sites/1234/stats/followers' ) );
	}

	/**
	 * Test get_comment_followers.
	 */
	public function test_get_comment_followers() {
		$expected_stats = array(
			'page'  => 0,
			'pages' => 0,
			'total' => 0,
			'posts' => array(),
		);

		$this->wpcom_stats
			->expects( $this->once() )
			->method( 'fetch_remote_stats' )
			->with(
				'/sites/1234/stats/comment-followers',
				array()
			)
			->willReturn( $expected_stats );

		$stats = $this->wpcom_stats->get_comment_followers();
		$this->assertSame( $expected_stats, $stats );
		$this->assertSame( wp_json_encode( $expected_stats ), self::get_stats_transient( '/sites/1234/stats/comment-followers' ) );
	}

	/**
	 * Test get_publicize_followers.
	 */
	public function test_get_publicize_followers() {
		$expected_stats = array(
			'services' => array(),
		);

		$this->wpcom_stats
			->expects( $this->once() )
			->method( 'fetch_remote_stats' )
			->with(
				'/sites/1234/stats/publicize',
				array()
			)
			->willReturn( $expected_stats );

		$stats = $this->wpcom_stats->get_publicize_followers();
		$this->assertSame( $expected_stats, $stats );
		$this->assertSame( wp_json_encode( $expected_stats ), self::get_stats_transient( '/sites/1234/stats/publicize' ) );
	}

	/**
	 * Test get_search_terms.
	 */
	public function test_get_search_terms() {
		$expected_stats = array(
			'date'   => '2022-09-29',
			'days'   => array(
				'2022-09-29' => array(
					'search_terms'           => array(),
					'encrypted_search_terms' => 0,
					'other_search_terms'     => 0,
					'total_search_terms'     => 0,
				),
			),
			'period' => 'day',
		);

		$this->wpcom_stats
			->expects( $this->once() )
			->method( 'fetch_remote_stats' )
			->with(
				'/sites/1234/stats/search-terms',
				array()
			)
			->willReturn( $expected_stats );

		$stats = $this->wpcom_stats->get_search_terms();
		$this->assertSame( $expected_stats, $stats );
		$this->assertSame( wp_json_encode( $expected_stats ), self::get_stats_transient( '/sites/1234/stats/search-terms' ) );
	}

	/**
	 * Test get_total_post_views.
	 */
	public function test_get_total_post_views() {
		$expected_stats = array(
			'date'  => '2022-09-29',
			'posts' => array(
				'2022-09-29' => array(
					'search_terms'           => array(),
					'encrypted_search_terms' => 0,
					'other_search_terms'     => 0,
					'total_search_terms'     => 0,
				),
			),
		);

		$this->wpcom_stats
			->expects( $this->once() )
			->method( 'fetch_remote_stats' )
			->with(
				'/sites/1234/stats/views/posts',
				array()
			)
			->willReturn( $expected_stats );

		$stats = $this->wpcom_stats->get_total_post_views();
		$this->assertSame( $expected_stats, $stats );
		$this->assertSame( wp_json_encode( $expected_stats ), self::get_stats_transient( '/sites/1234/stats/views/posts' ) );
	}

	/**
	 * Test get_total_post_views on WPCOM Simple sites.
	 *
	 * @return void
	 */
	public function test_get_total_post_views_with_valid_post_ids_on_simple_sites() {
		$reflection = new \ReflectionClass( $this->wpcom_stats );
		$property   = $reflection->getProperty( 'is_wpcom_simple' );
		$property->setAccessible( true );
		$property->setValue( $this->wpcom_stats, true );

		// Prepare mock data for the stats
		$mock_stats = array(
			'-' => array(
				1 => 100,  // Post ID 1 has 100 views
				2 => 200,  // Post ID 2 has 200 views
			),
		);

		// Set up the mock to return the mock stats when called
		$this->wpcom_stats->expects( $this->once() )
					->method( 'fetch_stats_on_wpcom_simple' )
					->with( '2025-03-07', 1, '1,2' )
					->willReturn( $mock_stats );

		$args = array(
			'post_ids' => '1,2',
			'end'      => '2025-03-07',
			'num'      => 1,
		);

		// Execute the method
		$result = $this->wpcom_stats->get_total_post_views( $args );

		// Assert the structure and the expected values
		$this->assertArrayHasKey( 'posts', $result );
		$this->assertCount( 2, $result['posts'] );

		// Check if the correct views are returned for each post
		$this->assertEquals( 100, $result['posts'][0]['views'] );
		$this->assertEquals( 200, $result['posts'][1]['views'] );
	}

	/**
	 * Test get_visits.
	 */
	public function test_get_visits() {
		$expected_stats = array(
			'date'   => '2022-10-12',
			'unit'   => 'month',
			'fields' => array(
				'period',
				'views',
			),
			'data'   => array(
				array(
					'2022-08-01',
					138,
				),
				array(
					'2022-09-01',
					41,
				),
				array(
					'2022-10-01',
					57,
				),
			),
		);

		$this->wpcom_stats
			->expects( $this->once() )
			->method( 'fetch_remote_stats' )
			->with(
				'/sites/1234/stats/visits',
				array()
			)
			->willReturn( $expected_stats );

		$stats = $this->wpcom_stats->get_visits();
		$this->assertSame( $expected_stats, $stats );
		$this->assertSame( wp_json_encode( $expected_stats ), self::get_stats_transient( '/sites/1234/stats/visits' ) );
	}

	/**
	 * Test get_highlights.
	 */
	public function test_get_highlights() {
		$expected_stats = array(
			'past_seven_days'                     => array(
				'range'    => array(
					'start' => '2022-11-21',
					'end'   => '2022-11-27',
				),
				'comments' => 0,
				'likes'    => 1,
				'views'    => 106,
				'visitors' => 28,
			),
			'between_past_eight_and_fifteen_days' => array(
				'range'    => array(
					'start' => '2022-11-14',
					'end'   => '2022-11-20',
				),
				'comments' => 0,
				'likes'    => 0,
				'views'    => 23,
				'visitors' => 17,
			),
		);

		$this->wpcom_stats
			->expects( $this->once() )
			->method( 'fetch_remote_stats' )
			->with(
				'/sites/1234/stats/highlights',
				array()
			)
			->willReturn( $expected_stats );

		$stats = $this->wpcom_stats->get_highlights();
		$this->assertSame( $expected_stats, $stats );
		$this->assertSame( wp_json_encode( $expected_stats ), self::get_stats_transient( '/sites/1234/stats/highlights' ) );
	}

	/**
	 * Test get_insights.
	 */
	public function test_get_insights() {
		$expected_stats = array(
			'highest_hour'         => 19,
			'highest_hour_percent' => 32,
			'highest_day_of_week'  => 2,
			'highest_day_percent'  => 31,
			'days'                 =>
			array(
				'0' => 103,
				'1' => 99,
			),
			'hours'                =>
			array(
				'00' => 101,
				'01' => 43,
			),
			'hourly_views'         =>
			array(
				'2022-11-26 04:00:00' => 0,
				'2022-11-26 05:00:00' => 0,
				'2022-11-26 06:00:00' => 0,
			),
			'years'                =>
			array(
				array(
					'year'           => '2022',
					'total_posts'    => 2,
					'total_words'    => 35,
					'avg_words'      => 17.5,
					'total_likes'    => 1,
					'avg_likes'      => 0.5,
					'total_comments' => 0,
					'avg_comments'   => 0,
					'total_images'   => 2,
					'avg_images'     => 1,
				),
			),
		);

		$this->wpcom_stats
			->expects( $this->once() )
			->method( 'fetch_remote_stats' )
			->with(
				'/sites/1234/stats/insights',
				array()
			)
			->willReturn( $expected_stats );

		$stats = $this->wpcom_stats->get_insights();
		$this->assertSame( $expected_stats, $stats );
		$this->assertSame( wp_json_encode( $expected_stats ), self::get_stats_transient( '/sites/1234/stats/insights' ) );
	}

	/**
	 * Test get_streak.
	 */
	public function test_get_streak() {
		$expected_stats = array(
			'streak' => array(
				'long'    => array(
					'start'  => '',
					'end'    => '',
					'length' => 1,
				),
				'current' => array(
					'start'  => '2022-11-21',
					'end'    => '2022-11-21',
					'length' => 1,
				),
			),
			'data'   => array(
				1669011611 => 1,
				1656367289 => 1,
				1638135559 => 1,
			),
		);

		$this->wpcom_stats
			->expects( $this->once() )
			->method( 'fetch_remote_stats' )
			->with(
				'/sites/1234/stats/streak',
				array()
			)
			->willReturn( $expected_stats );

		$stats = $this->wpcom_stats->get_streak();
		$this->assertSame( $expected_stats, $stats );
		$this->assertSame( wp_json_encode( $expected_stats ), self::get_stats_transient( '/sites/1234/stats/streak' ) );
	}

	/**
	 * Test get_stats with cached result.
	 */
	public function test_get_stats_with_cached_result() {
		$cached_stats = array(
			'dummy' => 'test',
		);

		$this->wpcom_stats
			->expects( $this->once() )
			->method( 'fetch_remote_stats' )
			->willReturn( $cached_stats );

		$this->wpcom_stats->get_stats();

		$this->wpcom_stats
			->expects( $this->never() )
			->method( 'fetch_remote_stats' );

		$stats = $this->wpcom_stats->get_stats();

		$this->assertArrayHasKey( 'dummy', $stats );
		$this->assertArrayHasKey( 'cached_at', $stats );
	}

	/**
	 * Test get_stats with cached error.
	 */
	public function test_get_stats_with_cached_error() {
		$expected_error = new WP_Error( 'dummy' );

		$this->wpcom_stats
			->expects( $this->once() )
			->method( 'fetch_remote_stats' )
			->willReturn( $expected_error );

		$this->wpcom_stats->get_stats();

		$stats = $this->wpcom_stats->get_stats();
		$this->assertSame( $expected_error, $stats );
		$this->assertSame( $expected_error, self::get_stats_transient( '/sites/1234/stats/' ) );
	}

	/**
	 * Test get_stats with error.
	 */
	public function test_get_stats_with_error() {
		$expected_error = new WP_Error( 'dummy' );

		$this->wpcom_stats
			->expects( $this->once() )
			->method( 'fetch_remote_stats' )
			->willReturn( $expected_error );

		$stats = $this->wpcom_stats->get_stats();
		$this->assertSame( $expected_error, $stats );
		$this->assertSame( $expected_error, self::get_stats_transient( '/sites/1234/stats/' ) );
	}

	/**
	 * Test get_stats with arguments.
	 */
	public function test_get_stats_with_arguments() {
		$expected_stats = array(
			'date'   => '2022-09-29',
			'visits' => array(),
		);

		$args = array(
			'fields' => 'date,visits',
		);

		$this->wpcom_stats
			->expects( $this->once() )
			->method( 'fetch_remote_stats' )
			->with(
				'/sites/1234/stats/',
				$args
			)
			->willReturn( $expected_stats );

		$stats = $this->wpcom_stats->get_stats( $args );
		$this->assertSame( $expected_stats, $stats );
		$this->assertSame( wp_json_encode( $expected_stats ), self::get_stats_transient( '/sites/1234/stats/', $args ) );
	}

	/**
	 * Helper for fetching the stats transient.
	 *
	 * @param  string $endpoint The WPCOM REST API endpoint.
	 * @param  array  $args     Optional query args.
	 * @return string|false The transient value if set, otherwise false
	 */
	private static function get_stats_transient( $endpoint, $args = array() ) {
		$cache_key      = md5( implode( '|', array( $endpoint, WPCOM_Stats::STATS_REST_API_VERSION, wp_json_encode( $args ) ) ) );
		$transient_name = WPCOM_Stats::STATS_CACHE_TRANSIENT_PREFIX . $cache_key;
		$stats_cache    = get_transient( $transient_name );

		if ( empty( $stats_cache ) ) {
			return false;
		}

		return reset( $stats_cache );
	}
}
