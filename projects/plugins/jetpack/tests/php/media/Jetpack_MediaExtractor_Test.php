<?php

use PHPUnit\Framework\Attributes\CoversClass;

require_once JETPACK__PLUGIN_DIR . '_inc/lib/class.media-extractor.php';

/**
 * @covers \Jetpack_Media_Meta_Extractor
 */
#[CoversClass( Jetpack_Media_Meta_Extractor::class )]
class Jetpack_MediaExtractor_Test extends WP_UnitTestCase {
	use \Automattic\Jetpack\PHPUnit\WP_UnitTestCase_Fix;

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_mediaextractor_extract_empty_array() {
		$post_id = self::factory()->post->create(
			array(
				'post_content' => '',
			)
		);

		$extract = Jetpack_Media_Meta_Extractor::extract( get_current_blog_id(), $post_id );

		$this->assertIsArray( $extract );
		$this->assertEmpty( $extract );
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_mediaextractor_extract_image() {
		$img_title = 'alt_title.jpg';

		$post_id = self::factory()->post->create(
			array(
				'post_content' => "<img src='$img_title' width='250' height='200'>",
			)
		);

		$extract = Jetpack_Media_Meta_Extractor::extract( get_current_blog_id(), $post_id );

		$this->assertIsArray( $extract );
		$this->assertArrayHasKey( 'image', $extract );
		$this->assertEquals( $extract['image'][0]['url'], $img_title );
	}

	/**
	 * @author robfelty
	 * @since 13.1
	 */
	public function test_mediaextractor_extract_image_with_alttext() {
		$img_title = 'title.jpg';

		$post_id = self::factory()->post->create(
			array(
				'post_content' => "<img alt='alt text' src='$img_title' width='250' height='200'>",
			)
		);

		$extract = Jetpack_Media_Meta_Extractor::extract( get_current_blog_id(), $post_id, Jetpack_Media_Meta_Extractor::ALL, true );

		$this->assertIsArray( $extract );
		$this->assertArrayHasKey( 'image', $extract );
		$this->assertEquals( $extract['image'][0]['url'], $img_title );
		$this->assertEquals( 'alt text', $extract['image'][0]['alt_text'] );
		$this->assertEquals( 250, $extract['image'][0]['src_width'] );
		$this->assertEquals( 200, $extract['image'][0]['src_height'] );
	}

	public function shortcode_nop() {
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_mediaextractor_extract_shortcode() {
		$shortcode = 'test_mediaextractor_shortcode';
		add_shortcode( $shortcode, array( $this, 'shortcode_nop' ) );

		$post_id = self::factory()->post->create(
			array(
				'post_content' => "[$shortcode]",
			)
		);

		$extract = Jetpack_Media_Meta_Extractor::extract( get_current_blog_id(), $post_id );

		$this->assertIsArray( $extract );
		$this->assertArrayHasKey( 'shortcode', $extract );
		$this->assertArrayHasKey( $shortcode, $extract['shortcode'] );
		$this->assertEquals( $extract['shortcode_types'][0], $shortcode );
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_mediaextractor_extract_link() {
		$url_link = WP_TESTS_DOMAIN;
		$url      = "<a href='http://$url_link'>";

		$post_id = self::factory()->post->create(
			array(
				'post_content' => "$url",
			)
		);

		$extract = Jetpack_Media_Meta_Extractor::extract( get_current_blog_id(), $post_id );

		$this->assertIsArray( $extract );
		$this->assertArrayHasKey( 'link', $extract );
		$this->assertEquals( $extract['link'][0]['url'], $url_link );
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_mediaextractor_extract_mention() {
		$mention = 'user';

		$post_id = self::factory()->post->create(
			array(
				'post_content' => "@$mention",
			)
		);

		$extract = Jetpack_Media_Meta_Extractor::extract( Jetpack_Options::get_option( 'id' ), $post_id );

		$this->assertIsArray( $extract );
		$this->assertArrayHasKey( 'mention', $extract );
		$this->assertEquals( $extract['mention']['name'][0], $mention );
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_mediaextractor_extract_embed() {
		$embed_link = 'wordpress.tv/embed';
		$embed      = "\nhttp://$embed_link\n";

		$post_id = self::factory()->post->create(
			array(
				'post_content' => "$embed",
			)
		);

		$extract = Jetpack_Media_Meta_Extractor::extract( Jetpack_Options::get_option( 'id' ), $post_id );

		$this->assertIsArray( $extract );
		$this->assertArrayHasKey( 'embed', $extract );
		$this->assertEquals( $extract['embed']['url'][0], $embed_link );
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_mediaextractor_extract_images_from_content_return_empty_array() {
		$content = '';

		$image_struct = Jetpack_Media_Meta_Extractor::extract_images_from_content( $content, array() );

		$this->assertIsArray( $image_struct );
		$this->assertEmpty( $image_struct );
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_mediaextractor_extract_images_from_content_return_correct_image_struct() {
		$img_name = 'image.jpg';
		$content  = "<img src='$img_name' width='200' height='200'>";

		$image_struct = Jetpack_Media_Meta_Extractor::extract_images_from_content( $content, array() );

		$this->assertIsArray( $image_struct );
		$this->assertArrayHasKey( 'has', $image_struct );
		$this->assertArrayHasKey( 'image', $image_struct );
		$this->assertCount( 1, $image_struct['image'] );
		$this->assertEquals( $image_struct['image'][0]['url'], $img_name );
		$this->assertSame( 1, $image_struct['has']['image'] );
	}

	/**
	 * @author robfelty
	 * @since 13.1
	 */
	public function test_mediaextractor_extract_images_from_content_with_alttext_return_correct_image_struct() {
		$img_name = 'image.jpg';
		$content  = "<img src='$img_name' width='250' height='200' alt='alternative image'>";

		$image_struct = Jetpack_Media_Meta_Extractor::extract_images_from_content( $content, array(), true );

		$this->assertIsArray( $image_struct );
		$this->assertArrayHasKey( 'has', $image_struct );
		$this->assertArrayHasKey( 'image', $image_struct );
		$this->assertCount( 1, $image_struct['image'] );
		$this->assertEquals( $image_struct['image'][0]['url'], $img_name );
		$this->assertEquals( 'alternative image', $image_struct['image'][0]['alt_text'] );
		$this->assertEquals( 250, $image_struct['image'][0]['src_width'] );
		$this->assertEquals( 200, $image_struct['image'][0]['src_height'] );
		$this->assertSame( 1, $image_struct['has']['image'] );
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_mediaextractor_get_images_from_html_empty() {
		$content = '';

		$image_list = Jetpack_Media_Meta_Extractor::get_images_from_html( $content, array() );

		$this->assertIsArray( $image_list );
		$this->assertEmpty( $image_list );
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_mediaextractor_get_images_from_html_already_extracted() {
		$content          = '';
		$images_extracted = array( 'http://' . WP_TESTS_DOMAIN . '/image.jpg' );

		$image_list = Jetpack_Media_Meta_Extractor::get_images_from_html( $content, $images_extracted );

		$this->assertIsArray( $image_list );
		$this->assertEquals( $image_list, $images_extracted );
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_mediaextractor_get_images_from_html_duplicate_in_already_extracted() {
		$content          = 'http://' . WP_TESTS_DOMAIN . '/image.jpg';
		$images_extracted = array( 'http://' . WP_TESTS_DOMAIN . '/image.jpg' );

		$image_list = Jetpack_Media_Meta_Extractor::get_images_from_html( $content, $images_extracted );

		$this->assertIsArray( $image_list );
		$this->assertEquals( $image_list, $images_extracted );
	}

	private function add_test_post() {
		$post_id        = self::factory()->post->create();
		$img_name       = 'image1.jpg';
		$alt_text       = 'one image';
		$img_dimensions = array(
			'width'  => 300,
			'height' => 250,
		);

		$attachment_id_1 = self::factory()->attachment->create_object(
			$img_name,
			$post_id,
			array(
				'post_mime_type' => 'image/jpeg',
				'post_type'      => 'attachment',
			)
		);
		wp_update_attachment_metadata( $attachment_id_1, $img_dimensions );
		update_post_meta( $attachment_id_1, '_wp_attachment_image_alt', $alt_text );
		$img_name       = 'image2.jpg';
		$alt_text       = 'second image';
		$img_dimensions = array(
			'width'  => 350,
			'height' => 240,
		);

		$attachment_id_2 = self::factory()->attachment->create_object(
			$img_name,
			$post_id,
			array(
				'post_mime_type' => 'image/jpeg',
				'post_type'      => 'attachment',
			)
		);
		wp_update_attachment_metadata( $attachment_id_2, $img_dimensions );
		update_post_meta( $attachment_id_2, '_wp_attachment_image_alt', $alt_text );
		wp_update_post(
			array(
				'ID'                    => $post_id,
				'post_author'           => '1046316',
				'post_date'             => '2013-03-15 22:55:05',
				'post_date_gmt'         => '2013-03-15 22:55:05',
				'post_content'          => 'Test of embedded things, like @mremypub mentions, http://alink.com links and other stuff:

			Yo, #hashtags123 are now being extracted, too.

			#Youtube embed:

			http://www.youtube.com/watch?v=r0cN_bpLrxk

			Youtube shortcode:

			[youtube http://www.youtube.com/watch?v=r0cN_bpLrxk]

			Youtube iframe:

			[youtube http://www.youtube.com/watch?v=r0cN_bpLrxk&amp;w=420&amp;h=315]

			Youtube old embed method:

			@@@doesn\'t work@@@ (that is not a mention, btw)

			Vimeo Embed:

			http://vimeo.com/44633289

			Vimeo shortcode:

			[vimeo http://vimeo.com/44633289]

			New <a href="http://make.wordpress.org/core/2013/04/08/audio-video-support-in-core/">video shortcode</a>:
			[video src="video-source.mp4"]

			Vimeo shortcode just with ID and maybe some other params

			[vimeo 44633289 w=500&amp;h=280]

			And now @martinremy another mention and <a href="http://anotherlink.com/this/is/a/path/script.php?queryarg=queryval&amp;anotherart=anotherval#anchorhere" rel="nofollow">another link</a>.

			TED:

			[ted id=981]

			VideoPress:

			[wpvideo 6nd4Jsq7 w=640]

			&nbsp;

			&nbsp;

			An Image:

			<a href="http://example.org/wp-content/uploads/image1.jpg"><img class="alignnone size-full wp-image-32" alt="Screen Shot 2013-03-15 at 1.27.05 PM" src="http://example.org/wp-content/uploads/image1.jpg" width="519" height="317" /></a>

			&nbsp;

			A Gallery:

			[gallery ids="' . $attachment_id_1 . ',' . $attachment_id_2 . '"]

			Twitter:

			http://twitter.com/mremy

			',
				'post_title'            => 'Test of embedded things like @mremypub mentions http...',
				'post_excerpt'          => '',
				'post_status'           => 'publish',
				'comment_status'        => 'open',
				'ping_status'           => 'open',
				'post_password'         => '',
				'post_name'             => 'test-of-embedded-things-like-mentions-http-alink',
				'to_ping'               => '',
				'pinged'                => '',
				'post_modified'         => '2013-04-17 02:39:15',
				'post_modified_gmt'     => '2013-04-17 02:39:15',
				'post_content_filtered' => '',
				'post_parent'           => 0,
				'guid'                  => 'http://mrwpsandbox.wordpress.com/2013/03/15/test-of-embedded-things-like-mentions-http-alink/',
				'menu_order'            => 0,
				'post_type'             => 'post',
				'post_mime_type'        => '',
				'comment_count'         => '0',
				'filter'                => 'raw',
			)
		);

		return $post_id;
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_mediaextractor_extract_links() {
		$post_id = $this->add_test_post();

		$expected = array(
			'has'  => array( 'link' => 7 ),
			'link' => array(
				array(
					'url'           => 'alink.com',
					'host_reversed' => 'com.alink',
					'host'          => 'alink.com',
				),
				array(
					'url'           => 'www.youtube.com/watch?v=r0cN_bpLrxk',
					'host'          => 'www.youtube.com',
					'host_reversed' => 'com.youtube.www',
				),
				array(
					'url'           => 'vimeo.com/44633289',
					'host'          => 'vimeo.com',
					'host_reversed' => 'com.vimeo',
				),
				array(
					'url'           => 'make.wordpress.org/core/2013/04/08/audio-video-support-in-core/',
					'host'          => 'make.wordpress.org',
					'host_reversed' => 'org.wordpress.make',
				),
				array(
					'url'           => 'anotherlink.com/this/is/a/path/script.php?queryarg=queryval&amp;anotherart=anotherval#anchorhere',
					'host'          => 'anotherlink.com',
					'host_reversed' => 'com.anotherlink',
				),
				array(
					'url'           => 'example.org/wp-content/uploads/image1.jpg',
					'host_reversed' => 'org.example',
					'host'          => 'example.org',
				),
				array(
					'host_reversed' => 'com.twitter',
					'host'          => 'twitter.com',
					'url'           => 'twitter.com/mremy',
				),
			),
		);

		$result = Jetpack_Media_Meta_Extractor::extract( get_current_blog_id(), $post_id, Jetpack_Media_Meta_Extractor::LINKS );

		$this->assertEquals( $expected, $result );
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_extract_images() {
		$post_id = $this->add_test_post();

		$expected = array(
			'image' => array(
				0 => array( 'url' => 'http://example.org/wp-content/uploads/image1.jpg' ),
				1 => array( 'url' => 'http://example.org/wp-content/uploads/image2.jpg' ),
			),
			'has'   => array(
				'image'   => 2,
				'gallery' => 1,
			),
		);

		$result = Jetpack_Media_Meta_Extractor::extract( get_current_blog_id(), $post_id, Jetpack_Media_Meta_Extractor::IMAGES );

		$this->assertEquals( $expected, $result );
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_extract_images_with_alt_text() {
		$post_id = $this->add_test_post();

		$expected = array(
			'image' => array(
				0 => array(
					'url'        => 'http://example.org/wp-content/uploads/image1.jpg',
					'src_width'  => 300,
					'src_height' => 250,
					'alt_text'   => 'one image',
				),
				1 => array(
					'url'        => 'http://example.org/wp-content/uploads/image2.jpg',
					'src_width'  => 350,
					'src_height' => 240,
					'alt_text'   => 'second image',
				),
				2 => array(
					'url'        => 'http://example.org/wp-content/uploads/image1.jpg',
					'alt_text'   => 'Screen Shot 2013-03-15 at 1.27.05 PM',
					'src_width'  => 519,
					'src_height' => 317,
				),
			),
			'has'   => array(
				'image'   => 3,
				'gallery' => 1,
			),
		);

		$result = Jetpack_Media_Meta_Extractor::extract( get_current_blog_id(), $post_id, Jetpack_Media_Meta_Extractor::IMAGES, true );

		$this->assertEquals( $expected, $result );
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_extract_mentions() {
		$post_id = $this->add_test_post();

		$expected = array(
			'mention' => array(
				'name' => array(
					0 => 'mremypub',
					1 => 'martinremy',
				),
			),
			'has'     => array( 'mention' => 2 ),
		);

		$result = Jetpack_Media_Meta_Extractor::extract( get_current_blog_id(), $post_id, Jetpack_Media_Meta_Extractor::MENTIONS );

		$this->assertEquals( $expected, $result );
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_extract_shortcodes() {
		$post_id = $this->add_test_post();

		$expected = array(
			'has'             => array( 'shortcode' => 8 ),
			'shortcode'       => array(
				'youtube' => array(
					'count' => 2,
					'id'    => array(
						'r0cN_bpLrxk',
					),
				),
				'vimeo'   => array(
					'count' => 2,
					'id'    => array(
						44633289,
					),
				),
				'ted'     => array(
					'count' => 1,
					'id'    => array(
						'981',
					),
				),
				'wpvideo' => array(
					'count' => 1,
					'id'    => array(
						'6nd4Jsq7',
					),
				),
				'video'   => array(
					'count' => 1,
					'id'    => array(
						'video-source.mp4',
					),
				),
				'gallery' => array(
					'count' => 1,
				),
			),
			'shortcode_types' => array(
				'youtube',
				'vimeo',
				'video',
				'ted',
				'wpvideo',
				'gallery',
			),
		);

		$result = Jetpack_Media_Meta_Extractor::extract( get_current_blog_id(), $post_id, Jetpack_Media_Meta_Extractor::SHORTCODES );

		$this->assertEquals( $expected, $result );
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_extract_embeds() {
		$post_id = $this->add_test_post();

		$expected = array(
			'has'   => array(
				'embed' => 3,
			),
			'embed' => array(
				'url' => array(
					'www.youtube.com/watch?v=r0cN_bpLrxk',
					'vimeo.com/44633289',
					'twitter.com/mremy',
				),
			),
		);

		$result = Jetpack_Media_Meta_Extractor::extract( get_current_blog_id(), $post_id, Jetpack_Media_Meta_Extractor::EMBEDS );

		$this->assertEquals( $expected, $result );
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_extract_image_from_html() {
		$html = <<<EOT
<p><a href="http://paulbernal.files.wordpress.com/2013/05/mr-gove-cover.jpeg"><img class="aligncenter size-full wp-image-1027" alt="Mr Gove Cover" src="http://paulbernal.files.wordpress.com/2013/05/mr-gove-cover.jpeg" width="612" height="547" /></a></p>
<p>Mr Gove was extraordinarily arrogant.</p>
<p>Painfully arrogant.</p>
<p>He believed that he knew how everything should be done. He believed that everyone else in the world was stupid and ignorant.</p>
<p>The problem was, Mr Gove himself was the one who was ignorant.</p>
<p><a href="http://paulbernal.files.wordpress.com/2013/05/mr-gove-close-up.jpeg"><img class="aligncenter size-full wp-image-1030" alt="Mr Gove Close up" src="http://paulbernal.files.wordpress.com/2013/05/mr-gove-close-up.jpeg" width="612" height="542" /></a></p>
<p>He got most of his information from his own, misty, memory.</p>
<p>He thought he remembered what it had been like when he had been at school &#8211; and assumed that everyone else&#8217;s school should be the same.</p>
<p>He remembered the good things about his own school days, and thought that everyone should have the same.</p>
<p>He remembered the bad things about his own school days, and thought that it hadn&#8217;t done him any harm &#8211; and that other children should suffer the way that he had.</p>
EOT;

		$expected = array(
			0 => 'http://images-r-us.com/some-image.png',
			1 => 'http://paulbernal.files.wordpress.com/2013/05/mr-gove-cover.jpeg',
			2 => 'http://paulbernal.files.wordpress.com/2013/05/mr-gove-close-up.jpeg',
		);

		$already_extracted_images = array( 'http://images-r-us.com/some-image.png' );

		$result = Jetpack_Media_Meta_Extractor::get_images_from_html( $html, $already_extracted_images );

		$this->assertEquals( $expected, $result );
	}

	/**
	 * @author scotchfield
	 * @since 3.2
	 */
	public function test_mediaextractor_exclude_video_links() {
		$post_id = self::factory()->post->create(
			array(
				'post_author'           => '23314024',
				'post_date'             => '2013-10-25 16:43:34',
				'post_date_gmt'         => '2013-10-25 16:43:34',
				'post_content'          => 'Sed dapibus ut mauris imperdiet volutpat. http://vimeo.com/77120044/ Nullam in dolor vel nulla pulvinar accumsan facilisis quis lorem.
			',
				'post_title'            => 'Sed dapibus ut mauris imperdiet volutpat http vimeo...',
				'post_excerpt'          => '',
				'post_status'           => 'publish',
				'comment_status'        => 'open',
				'ping_status'           => 'open',
				'post_password'         => '',
				'post_name'             => 'sed-dapibus-ut-mauris-imperdiet-volutpat-http-vimeo',
				'to_ping'               => '',
				'pinged'                => '
			http://vimeo.com/77120044/',
				'post_modified'         => '2013-10-28 22:54:50',
				'post_modified_gmt'     => '2013-10-28 22:54:50',
				'post_content_filtered' => '',
				'post_parent'           => 0,
				'guid'                  => 'http://breakmyblog.wordpress.com/2013/10/25/sed-dapibus-ut-mauris-imperdiet-volutpat-http-vimeo/',
				'menu_order'            => 0,
				'post_type'             => 'post',
				'post_mime_type'        => '',
				'comment_count'         => '0',
				'filter'                => 'raw',
			)
		);

		$expected = array(
			'has'  => array( 'link' => 1 ),
			'link' => array(
				array(
					'url'           => 'vimeo.com/77120044/',
					'host_reversed' => 'com.vimeo',
					'host'          => 'vimeo.com',
				),
			),
		);

		$result = Jetpack_Media_Meta_Extractor::extract( get_current_blog_id(), $post_id, Jetpack_Media_Meta_Extractor::ALL );

		$this->assertEquals( $expected, $result );
	}

	/**
	 * Verify alt_text is extracted.
	 *
	 * @author mdbitz
	 * @since 3.2
	 */
	public function test_mediaextractor_alt_text() {
		$post_id = self::factory()->post->create(
			array(
				'post_content' => 'Sed dapibus ut mauris imperdiet volutpat. <img src="https://example.org/assets/test.jpg" alt="red green" /> yellow <img src="https://example.org/assets/test2.jpg" />Nullam in dolor vel nulla pulvinar accumsan facilisis quis lorem.',
			)
		);

		$expected = array(
			'has'   => array(
				'image'   => 2,
				'gallery' => 0,
				'link'    => 2,
			),
			'image' => array(
				array(
					'url'      => 'https://example.org/assets/test.jpg',
					'alt_text' => 'red green',
				),
				array(
					'url' => 'https://example.org/assets/test2.jpg',
				),
			),
			'link'  => array(
				array(
					'url'           => 'example.org/assets/test.jpg',
					'host_reversed' => 'org.example',
					'host'          => 'example.org',
				),
				array(
					'url'           => 'example.org/assets/test2.jpg',
					'host_reversed' => 'org.example',
					'host'          => 'example.org',
				),
			),
		);

		// We are not concerned with minimum dimensions.
		add_filter( 'jetpack_postimages_ignore_minimum_dimensions', '__return_true', 66 );
		$result = Jetpack_Media_Meta_Extractor::extract( get_current_blog_id(), $post_id, Jetpack_Media_Meta_Extractor::ALL, true );
		remove_filter( 'jetpack_postimages_ignore_minimum_dimensions', '__return_true', 66 );

		$this->assertEquals( $expected, $result );
	}

	/**
	 * Verify that audio shortcode URL is extracted.
	 */
	public function test_audio_shortcode() {
		$post_id = self::factory()->post->create(
			array(
				'post_content' => 'Sed dapibus ut mauris imperdiet volutpat. [audio http://example.com/files/2011/02/audio-post.mp3|titles=Audio Post] Nullam [audio https://example.com/place/test/filename.flac] in dolor vel nulla pulvinar accumsan facilisis quis lorem. [audio="http://example.com/file.wav"]',
			)
		);

		$expected = array(
			'has'             => array( 'shortcode' => 3 ),
			'shortcode'       => array(
				'audio' => array(
					'count' => 3,
					'id'    => array(
						'http://example.com/files/2011/02/audio-post.mp3',
						'https://example.com/place/test/filename.flac',
						'http://example.com/file.wav',
					),
				),
			),
			'shortcode_types' => array( 'audio' ),
		);

		$result = Jetpack_Media_Meta_Extractor::extract( get_current_blog_id(), $post_id );

		$this->assertEquals( $expected, $result );
	}

	/**
	 * Verify that video shortcode URL is extracted.
	 */
	public function test_video_shortcode() {
		$post_id = self::factory()->post->create(
			array(
				'post_content' => 'Lorem ipsum. [video wmv="http://example.com/video.wmv"][/video] Sed dapibus ut mauris imperdiet volutpat. [video width="1920" height="1080" mp4="https://example.files.wordpress.com/dir1/dir2/file.mp4"][/video] Nullam [video url="https://example.com/promo.mp4"] in dolor vel nulla pulvinar accumsan facilisis quis lorem. [video src="https://example.com/promo.m4v"] ',
			)
		);

		$expected = array(
			'has'             => array( 'shortcode' => 4 ),
			'shortcode'       => array(
				'video' => array(
					'count' => 4,
					'id'    => array(
						'http://example.com/video.wmv',
						'https://example.files.wordpress.com/dir1/dir2/file.mp4',
						'https://example.com/promo.mp4',
						'https://example.com/promo.m4v',
					),
				),
			),
			'shortcode_types' => array( 'video' ),
		);

		$result = Jetpack_Media_Meta_Extractor::extract( get_current_blog_id(), $post_id );

		$this->assertEquals( $expected, $result );
	}
}
