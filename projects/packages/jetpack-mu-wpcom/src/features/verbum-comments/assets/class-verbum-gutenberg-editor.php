<?php
/**
 * Verbum Gutenberg Editor
 *
 * @package automattic/jetpack-mu-plugins
 */

declare( strict_types = 1 );

require_once __DIR__ . '/class-verbum-block-utils.php';
require_once __DIR__ . '/class-verbum-asset-loader.php';

/**
 * Verbum_Gutenberg_Editor is responsible for loading the Gutenberg editor for comments.
 *
 * This loads the isolated editor, and sets up the editor to be used for Verbum_Comments.
 *
 * @see https://github.com/Automattic/isolated-block-editor
 */
class Verbum_Gutenberg_Editor {
	/**
	 * Comment forms can appear anywhere (page, post, query loop, etc), there is no reliable way to determine if there are comments on the page,
	 * So we hook into `comment_form_before` and set this flag to true when a comment form is found.
	 *
	 * @var bool
	 */
	public $should_enqueue_assets = false;

	/**
	 * Class constructor
	 */
	public function __construct() {
		define( 'VERBUM_USING_GUTENBERG', true );

		// Override the placeholder text
		add_filter(
			'write_your_story',
			function () {
				return __( 'Write a comment...', 'jetpack-mu-wpcom' );
			},
			9999
		);

		add_action(
			'comment_form_before',
			function () {
				$this->should_enqueue_assets = true;
			}
		);

		add_filter( 'init', array( $this, 'remove_strict_kses_filters' ) );
		add_filter( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'comment_text', array( \Verbum_Block_Utils::class, 'render_verbum_blocks' ) );
		add_filter( 'pre_comment_content', array( \Verbum_Block_Utils::class, 'remove_blocks' ) );
	}

	/**
	 * Default KSES filters on wpcom only allow HTML for admins and people who can post "posts" to the blog they're commenting on.
	 * See: wp-includes/kses.php (this one adds the restrictions).
	 * See: wp-content/mu-plugins/misc.php (this one removes it, but only has_cap('publish_posts')).
	 */
	public function remove_strict_kses_filters() {
		// Allow HTML when blocks are enabled.
		remove_filter( 'pre_comment_content', 'wp_filter_kses' );
		add_filter( 'pre_comment_content', 'wp_filter_post_kses' );
	}

	/**
	 * Enqueue the assets for the Gutenberg editor
	 *
	 * In case the page is singular and has comment closed or front page with comments closed we avoid the enqueueing
	 */
	public function enqueue_assets() {
		if (
			! ( is_singular() && comments_open() )
			&& ! ( is_front_page() && is_page() && comments_open() )
			&& ! $this->should_enqueue_assets
		) {
			return;
		}

		\Verbum_Asset_Loader::load_editor_supporting_assets(); // Editor itself is loaded dynamically
	}
}
