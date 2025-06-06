<?php
/**
 * Jetpack AI Chat.
 *
 * @since 12.1
 *
 * @package automattic/jetpack
 */

namespace Automattic\Jetpack\Extensions\AIChat;

use Automattic\Jetpack\Blocks;
use Automattic\Jetpack\Connection\Manager as Connection_Manager;
use Automattic\Jetpack\Search\Module_Control as Search_Module_Control;
use Automattic\Jetpack\Search\Plan as Search_Plan;
use Automattic\Jetpack\Status;
use Automattic\Jetpack\Status\Host;
use Jetpack_Gutenberg;

/**
 * Registers our block for use in Gutenberg
 * This is done via an action so that we can disable
 * registration if we need to.
 */
function register_block() {
	if (
		( new Host() )->is_wpcom_simple()
		|| ( ( new Connection_Manager( 'jetpack' ) )->has_connected_owner() && ! ( new Status() )->is_offline_mode() )
	) {
		Blocks::jetpack_register_block(
			__DIR__,
			array( 'render_callback' => __NAMESPACE__ . '\load_assets' )
		);
	}
}
add_action( 'init', __NAMESPACE__ . '\register_block' );

/**
 * Jetpack AI Paragraph block registration/dependency declaration.
 *
 * @param array $attr Array containing the Jetpack AI Chat block attributes.
 *
 * @return string
 */
function load_assets( $attr ) {
	/*
	 * Enqueue necessary scripts and styles.
	 */
	Jetpack_Gutenberg::load_assets_as_required( __DIR__ );

	$ask_button_label = isset( $attr['askButtonLabel'] ) ? $attr['askButtonLabel'] : __( 'Ask', 'jetpack' );
	$placeholder      = isset( $attr['placeholder'] ) ? $attr['placeholder'] : __( 'Ask a question about this site.', 'jetpack' );

	if ( defined( 'IS_WPCOM' ) && IS_WPCOM ) {
		$blog_id = get_current_blog_id();
		$type    = 'wpcom'; // WPCOM simple sites.
	} else {
		$blog_id = \Jetpack_Options::get_option( 'id' );
		$type    = 'jetpack'; // Self-hosted (includes Atomic)
	}

	return sprintf(
		'<div class="%1$s" data-ask-button-label="%2$s" id="jetpack-ai-chat" data-blog-id="%3$d" data-blog-type="%4$s" data-placeholder="%5$s" data-show-copy="%6$d" data-show-feedback="%7$d" data-show-sources="%8$d"></div>',
		esc_attr( Blocks::classes( Blocks::get_block_feature( __DIR__ ), $attr ) ),
		esc_attr( $ask_button_label ),
		esc_attr( $blog_id ),
		esc_attr( $type ),
		esc_attr( $placeholder ),
		esc_attr( isset( $attr['showCopy'] ) ? ( $attr['showCopy'] ? 1 : 0 ) : 1 ),
		esc_attr( isset( $attr['showFeedback'] ) ? ( $attr['showFeedback'] ? 1 : 0 ) : 1 ),
		esc_attr( isset( $attr['showSources'] ) ? ( $attr['showSources'] ? 1 : 0 ) : 1 )
	);
}

/**
 * Add the initial state for the AI Chat block.
 */
function add_ai_chat_block_data() {
	// Only relevant to the editor right now.
	if ( ! is_admin() ) {
		return;
	}
	$search        = new Search_Module_Control();
	$plan          = new Search_Plan();
	$initial_state = array(
		'jetpackSettings' => array(
			'instant_search_enabled' => $search->is_instant_search_enabled(),
			'plan_supports_search'   => $plan->supports_instant_search(),
		),
	);
	wp_add_inline_script(
		'jetpack-blocks-editor',
		'var Jetpack_AIChatBlock = ' . wp_json_encode( $initial_state, JSON_HEX_TAG | JSON_HEX_AMP ) . ';',
		'before'
	);
}
add_action( 'enqueue_block_assets', __NAMESPACE__ . '\add_ai_chat_block_data', 11 );
