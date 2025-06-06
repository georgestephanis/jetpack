<?php
/**
 * Meta tags testing.
 *
 * @package automattic/jetpack-social-plugin
 */

use Brain\Monkey;
use PHPUnit\Framework\Attributes\DataProvider;
use WorDBless\BaseTestCase;

/**
 * Meta tags testing.
 */
class Meta_Tags_Test extends BaseTestCase {
	/**
	 * Post ID of the testing post.
	 *
	 * @var int $post
	 */
	protected static $post;

	/**
	 * ID of the test attachment (1200 pixels wide).
	 *
	 * @var int $attachment_id
	 */
	protected static $attachment_id;

	/**
	 * ID of the test attachment (100 pixels wide).
	 *
	 * @var int $attachment_id_small
	 */
	protected static $attachment_id_small;

	/**
	 * Meta tags.
	 *
	 * @var \Automattic\Jetpack\Social\Meta_Tags
	 */
	public $meta_tags;

	/**
	 * Initialize tests
	 */
	public function set_up() {
		$this->meta_tags           = new Automattic\Jetpack\Social\Meta_Tags();
		self::$post                = wp_insert_post(
			array(
				'post_title'   => 'hello',
				'post_content' => 'world',
				'post_status'  => 'publish',
			)
		);
		self::$attachment_id       = $this->create_upload_object( __DIR__ . '/images/jetpack-logo-1200w.png' );
		self::$attachment_id_small = $this->create_upload_object( __DIR__ . '/images/jetpack-logo-100w.png' );
	}

	/**
	 * Reverting the testing environment to its original state.
	 */
	public function tear_down() {
		Monkey\tearDown();
	}

	/**
	 * Create a upload
	 *
	 * @param string  $file File path.
	 * @param integer $parent Parent post ID.
	 * @return integer
	 */
	public function create_upload_object( $file, $parent = 0 ) {
		$contents = file_get_contents( $file );
		$upload   = wp_upload_bits( basename( $file ), null, $contents );

		$type = '';
		if ( ! empty( $upload['type'] ) ) {
			$type = $upload['type'];
		} else {
			$mime = wp_check_filetype( $upload['file'], null );
			if ( $mime ) {
				$type = $mime['type'];
			}
		}

		$attachment = array(
			'post_title'     => basename( $upload['file'] ),
			'post_content'   => '',
			'post_type'      => 'attachment',
			'post_parent'    => $parent,
			'post_mime_type' => $type,
			'guid'           => $upload['url'],
		);

		// Save the data.
		$id = wp_insert_attachment( $attachment, $upload['file'], $parent );

		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );

		return $id;
	}

	/**
	 * Test that getting active plugins works.
	 */
	public function test_get_active_plugins() {
		update_option( 'active_plugins', array( 'hello-world.php' ) );
		$active_plugins = $this->meta_tags->get_active_plugins();
		$this->assertEquals( array( 'hello-world.php' ), $active_plugins );
	}

	/**
	 * Test that meta tags don't get rendered when a conflicting plugin is active.
	 */
	public function test_meta_tags_dont_get_rendered_when_conflicting_plugin_is_active() {
		Monkey\Functions\when( 'is_singular' )->justReturn( true );
		$this->assertTrue( $this->meta_tags->should_render_meta_tags() );
		update_option( 'active_plugins', array( 'og-tags/og-tags.php' ) );
		$this->assertFalse( $this->meta_tags->should_render_meta_tags() );
	}

	/**
	 * Test that Twitter Cards tags don't get rendered when a conflicting plugin is active.
	 */
	public function test_twitter_cards_tags_dont_get_rendered_when_conflicting_plugin_is_active() {
		$this->assertTrue( $this->meta_tags->should_render_twitter_cards_tags() );
		update_option( 'active_plugins', array( 'wp-twitter-cards/twitter_cards.php' ) );
		$this->assertFalse( $this->meta_tags->should_render_twitter_cards_tags() );
	}

	/**
	 * Test that meta tags don't get rendered on a non-singular page.
	 */
	public function test_meta_tags_dont_get_rendered_on_non_singular_pages() {
		Monkey\Functions\when( 'is_singular' )->justReturn( true );
		$this->assertTrue( $this->meta_tags->should_render_meta_tags() );
		Monkey\Functions\when( 'is_singular' )->justReturn( false );
		$this->assertFalse( $this->meta_tags->should_render_meta_tags() );
	}

	/**
	 * Test that meta tags don't get rendered when they're disabled by a filter.
	 */
	public function test_meta_tags_dont_get_rendered_when_disabled_by_filter() {
		Monkey\Functions\when( 'is_singular' )->justReturn( true );
		$this->assertTrue( $this->meta_tags->should_render_meta_tags() );
		add_filter( 'jetpack_enable_open_graph', '__return_false' );
		$this->assertFalse( $this->meta_tags->should_render_meta_tags() );
	}

	/**
	 * Test that the featured image gets returned correctly.
	 */
	public function test_featured_image_gets_returned_correctly() {
		$this->assertEmpty( $this->meta_tags->get_featured_image( self::$post ) );
		set_post_thumbnail( self::$post, self::$attachment_id );
		$this->assertNotEmpty( $this->meta_tags->get_featured_image( self::$post ) );
	}

	/**
	 * Test that the featured image gets skipped when the image is too small.
	 */
	public function test_featured_image_too_small_gets_skipped() {
		set_post_thumbnail( self::$post, self::$attachment_id_small );
		$this->assertEmpty( $this->meta_tags->get_featured_image( self::$post ) );
	}

	/**
	 * Test that the featured image gets skipped when the post is password protected.
	 */
	public function test_featured_image_does_not_get_returned_for_password_protected_posts() {
		set_post_thumbnail( self::$post, self::$attachment_id );
		$this->assertNotEmpty( $this->meta_tags->get_featured_image( self::$post ) );
		wp_update_post(
			array(
				'ID'            => self::$post,
				'post_password' => 'foo',
			)
		);
		$this->assertEmpty( $this->meta_tags->get_featured_image( self::$post ) );
	}

	/**
	 * Test potential descriptions given to OG description.
	 *
	 * @dataProvider get_description_data_provider
	 *
	 * @param string $description Post description.
	 * @param string $cleaned_description Description cleaned up and ready to be used.
	 */
	#[DataProvider( 'get_description_data_provider' )]
	public function test_get_description_default( $description, $cleaned_description ) {
		// A test shortcode that should be removed from descriptions.
		add_shortcode(
			'foo',
			function () {
				return 'bar';
			}
		);

		$processed_description = $this->meta_tags->get_description( $description );

		$this->assertEquals(
			$cleaned_description,
			$processed_description
		);
	}

	/**
	 * Potential descriptions given to OG description.
	 */
	public static function get_description_data_provider() {
		return array(
			'empty'                                       => array(
				'',
				'Visit the post for more.',
			),
			'null'                                        => array(
				null,
				'Visit the post for more.',
			),
			'no_entities'                                 => array(
				"OpenGraph's test",
				'OpenGraph&#8217;s test',
			),
			'too_many_words'                              => array(
				'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Etiam consectetur quam eget finibus consectetur. Donec sollicitudin finibus massa, ut cursus elit. Mauris dictum quam eu ullamcorper feugiat. Proin id ante purus. Aliquam lorem libero, tempus id dictum non, feugiat vel eros. Sed sed viverra libero. Praesent eu lacinia felis, et tempus turpis. Proin bibendum, ligula. These last sentence should be removed.',
				'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Etiam consectetur quam eget finibus consectetur. Donec sollicitudin finibus massa, ut cursus elit. Mauris dictum quam eu ullamcorper feugia…',
			),
			'no_tags'                                     => array(
				'A post description<script>alert("hello");</script>',
				'A post description',
			),
			'no_shortcodes'                               => array(
				'[foo test="true"]A post description',
				'A post description',
			),
			'no_links'                                    => array(
				'A post description https://jetpack.com',
				'A post description',
			),
			'no_html'                                     => array(
				'<strong>A post description</strong>',
				'A post description',
			),
			'image_then_text'                             => array(
				'<img src="https://example.org/jetpack-icon.jpg" />A post description',
				'A post description',
			),
			'linked_image_then_text'                      => array(
				'<a href="https://jetpack.com"><img src="https://example.org/jetpack-icon.jpg" /></a>A post description',
				'A post description',
			),
			'removed_tags_dont_count_for_character_limit' => array(
				'<img src="https://example.org/jetpack-icon.jpg" />This string is exactly 197 characters long if you ignore the HTML tags, which should be removed by the function—after which we start enforcing the limit. Just making sure it works the way it should',
				'This string is exactly 197 characters long if you ignore the HTML tags, which should be removed by the function—after which we start enforcing the limit. Just making sure it works the way it should',
			),
		);
	}

	/**
	 * Test the logic to create a title for Social Notes from the content.
	 *
	 * @dataProvider get_note_title_data_provider
	 *
	 * @param string $content Post content.
	 * @param string $note_title The expected note title.
	 */
	#[DataProvider( 'get_note_title_data_provider' )]
	public function test_get_social_note_title( $content, $note_title ) {
		global $post;

		wp_update_post(
			array(
				'ID'           => self::$post,
				'post_type'    => Automattic\Jetpack\Social\Note::JETPACK_SOCIAL_NOTE_CPT,
				'post_title'   => '',
				'post_content' => $content,
			)
		);
		$post = get_post( self::$post );
		setup_postdata( $post );

		$this->assertEquals( $note_title, $this->meta_tags->get_og_title_for_social_notes() );
	}

	/**
	 * Provides the test cases for the note titles.
	 *
	 * @return array An array of test case tuples. Input and expected title.
	 */
	public static function get_note_title_data_provider() {
		$ellipsis = "\u{2026}";
		return array(
			'standard_content'                   => array(
				'This is a test of the title logic to see at what point it will cut it off',
				'This is a test of the title logic to see at what point' . $ellipsis,
			),
			'55_chars'                           => array(
				'This is a test of the title logic where it is 55 charss',
				'This is a test of the title logic where it is 55 charss',
			),
			'56_chars'                           => array(
				'This is a test of the title logic where it is 56 charsss',
				'This is a test of the title logic where it is 56' . $ellipsis,
			),
			'long_word_55'                       => array(
				'Thisisatestofthetitlelogicwhereitis55wordbutthatsoktooo',
				'Thisisatestofthetitlelogicwhereitis55wordbutthatsoktooo',
			),
			'long_word_56'                       => array(
				'Thisisatestofthetitlelogicwhereitisfiftysixcharacterslon',
				'Thisisatestofthetitlelogicwhereitisfiftysixcharacterslo' . $ellipsis,
			),
			'leading_non_word_55_whitespace'     => array(
				'@#$#$@%#$%@#$%@#$%                    Thisisatestofthe',
				'@#$#$@%#$%@#$%@#$% Thisisatestofthe',
			),
			'leading_non_word_56_whitespace'     => array(
				'@#$#$@%#$%@#$%@#$%                    Thisisatestoftheuo',
				'@#$#$@%#$%@#$%@#$% Thisisatestoftheuo',
			),
			'leading_55_non_word_multiple_space' => array(
				'@#$#$@%#$%@#$%@#$%                    Thisisatestofthoentuh aoestuhaoeusth',
				'@#$#$@%#$%@#$%@#$% Thisisatestofthoentuh aoestuhaoeusth',
			),
			'leading_56_non_word_multiple_space' => array(
				'@#$#$@%#$%@#$%@#$%                    Thisisatestofthoentuh aoestuhaoeusthu',
				'@#$#$@%#$%@#$%@#$% Thisisatestofthoentuh' . $ellipsis,
			),
			'leading_non_word_55_multiple'       => array(
				'@#$#$@%#$%@#$%@#$%#$%#$%#$^#$^%#$%#$%#$%#$%#$%%This isa',
				'@#$#$@%#$%@#$%@#$%#$%#$%#$^#$^%#$%#$%#$%#$%#$%%This isa',
			),
			'leading_non_word_56_multiple'       => array(
				'@#$#$@%#$%@#$%@#$%#$%#$%#$^#$^%#$%#$%#$%#$%#$%%This isat',
				'@#$#$@%#$%@#$%@#$%#$%#$%#$^#$^%#$%#$%#$%#$%#$%%This' . $ellipsis,
			),
			'leading_non_word_55'                => array(
				'@#$#$@%#$%@#$%@#$%#$%#$%#$^#$^%#$%#$%#$%#$%#$%%Thisisat',
				'@#$#$@%#$%@#$%@#$%#$%#$%#$^#$^%#$%#$%#$%#$%#$%%Thisisat',
			),
			'leading_non_word_56'                => array(
				'@#$#$@%#$%@#$%@#$%#$%#$%#$^#$^%#$%#$%#$%#$%#$%%Thisisate',
				'@#$#$@%#$%@#$%@#$%#$%#$%#$^#$^%#$%#$%#$%#$%#$%%Thisisat' . $ellipsis,
			),
		);
	}
}
