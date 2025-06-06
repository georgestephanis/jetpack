<?php
/**
 * Podcast Helper unit tests.
 *
 * @package automattic/jetpack
 */

use PHPUnit\Framework\Attributes\CoversClass;

require_once __DIR__ . '/mocks/simplepie.php';
require_once JETPACK__PLUGIN_DIR . '/_inc/lib/class-jetpack-podcast-helper.php';

/**
 * Class for testing the Jetpack_Podcast_Helper class.
 *
 * @covers \Jetpack_Podcast_Helper
 */
#[CoversClass( Jetpack_Podcast_Helper::class )]
class Jetpack_Podcast_Helper_Test extends WP_UnitTestCase {
	use \Automattic\Jetpack\PHPUnit\WP_UnitTestCase_Fix;

	/**
	 * Tests get_track_data() when the feed cannot be retrieved.
	 */
	public function test_get_track_data_feed_error() {
		$podcast_helper = $this->getMockBuilder( 'Jetpack_Podcast_Helper' )
			->disableOriginalConstructor()
			->onlyMethods( array( 'load_feed', 'setup_tracks_callback' ) )
			->getMock();

		$podcast_helper->expects( $this->once() )
			->method( 'load_feed' )
			->willReturn( new WP_Error( 'feed_error', 'Feed error.' ) );

		$error = $podcast_helper->get_track_data( 'invalid_id' );
		$this->assertWPError( $error );
		$this->assertSame( 'feed_error', $error->get_error_code() );
		$this->assertSame( 'Feed error.', $error->get_error_message() );
	}

	/**
	 * Tests get_track_data() finds the given episode.
	 */
	public function test_get_track_data_find_episode() {
		$podcast_helper = $this->getMockBuilder( 'Jetpack_Podcast_Helper' )
			->disableOriginalConstructor()
			->onlyMethods( array( 'load_feed', 'setup_tracks_callback' ) )
			->getMock();

		$track = $this->getMockBuilder( SimplePie\Item::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_id' ) )
			->getMock();

		$track->expects( $this->exactly( 2 ) )
			->method( 'get_id' )
			->willReturn( '1' );

		$rss = $this->getMockBuilder( SimplePie\SimplePie::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_items' ) )
			->getMock();

		$rss->expects( $this->exactly( 2 ) )
			->method( 'get_items' )
			->willReturn( array( $track ) );

		$podcast_helper->expects( $this->exactly( 2 ) )
			->method( 'load_feed' )
			->willReturn( $rss );

		$id = wp_unique_id( 'podcast-track-' );

		$podcast_helper->expects( $this->once() )
			->method( 'setup_tracks_callback' )
			->willReturn(
				array(
					'id'          => $id,
					'link'        => 'https://example.org',
					'src'         => 'https://example.org',
					'type'        => 'episode',
					'description' => '',
					'title'       => '',
					'guid'        => '123',
				)
			);

		// Can't find an episode.
		$error = $podcast_helper->get_track_data( 'invalid_id' );
		$this->assertWPError( $error );
		$this->assertSame( 'no_track', $error->get_error_code() );
		$this->assertSame( 'The track was not found.', $error->get_error_message() );

		// Success.
		$episode = $podcast_helper->get_track_data( '1' );
		$this->assertSame(
			$episode,
			array(
				'id'          => $id,
				'link'        => 'https://example.org',
				'src'         => 'https://example.org',
				'type'        => 'episode',
				'description' => '',
				'title'       => '',
				'guid'        => '123',
			)
		);
	}
}
