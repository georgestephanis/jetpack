<?php
/**
 * WP_REST_Help_Center_Odie file.
 *
 * @package automattic/jetpack-mu-wpcom
 */

namespace A8C\FSE;

use Automattic\Jetpack\Connection\Client;

/**
 * Class WP_REST_Help_Center_Odie.
 */
class WP_REST_Help_Center_Odie extends \WP_REST_Controller {

	/**
	 * WP_REST_Help_Center_Odie constructor.
	 */
	public function __construct() {
		$this->namespace = 'help-center';
		$this->rest_base = '/odie';
	}

	/**
	 * Register available routes.
	 */
	public function register_rest_route() {
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/chat/(?P<bot_id>[a-zA-Z0-9-]+)/(?P<chat_id>\d+)',
			array(
				// Get a chat. Supports pagination of messages.
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_chat' ),
					'permission_callback' => 'is_user_logged_in',
					'args'                => array(
						'bot_id'           => array(
							'description' => __( 'The bot id to get the chat for.', 'jetpack-mu-wpcom' ),
							'type'        => 'string',
							'required'    => true,
						),
						'chat_id'          => array(
							'description' => __( 'The chat id to get the chat for.', 'jetpack-mu-wpcom' ),
							'type'        => 'integer',
							'required'    => true,
						),
						'page_number'      => array(
							'description' => __( 'The number of the page to retrieve, limited to 100', 'jetpack-mu-wpcom' ),
							'type'        => 'integer',
							'required'    => false,
							'default'     => 1,
						),
						'items_per_page'   => array(
							'description' => __( 'The number of items per page.', 'jetpack-mu-wpcom' ),
							'type'        => 'integer',
							'required'    => false,
							'default'     => 10,
						),
						'include_feedback' => array(
							'required'    => false,
							'type'        => 'boolean',
							'description' => __( 'If true, include the feedback rating value for each message in the response.', 'jetpack-mu-wpcom' ),
						),
					),
				),
				// Add a message to a chat.
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'send_chat_message' ),
					'permission_callback' => 'is_user_logged_in',
					'args'                => array(
						'bot_id'  => array(
							'description' => __( 'The bot id to chat with.', 'jetpack-mu-wpcom' ),
							'type'        => 'string',
							'required'    => true,
						),
						'chat_id' => array(
							'description' => __( 'The chat id for the existing chat.', 'jetpack-mu-wpcom' ),
							'type'        => 'integer',
							'required'    => true,
						),
						'message' => array(
							'description' => __( 'The message to add to the chat', 'jetpack-mu-wpcom' ),
							'type'        => 'string',
							'required'    => true,
						),
						// an arbitray key/value object of data to pass to the bot
						'context' => array(
							'description' => __( 'The context to continue the chat with.', 'jetpack-mu-wpcom' ),
							'type'        => 'object',
							'required'    => false,
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/chat/(?P<bot_id>[a-zA-Z0-9-]+)/(?P<chat_id>\d+)/(?P<message_id>\d+)/feedback',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_chat_message_feedback' ),
					'permission_callback' => 'is_user_logged_in',
					'args'                => array(
						'bot_id'       => array(
							'description' => __( 'The bot id to chat with.', 'jetpack-mu-wpcom' ),
							'type'        => 'string',
							'required'    => true,
						),
						'chat_id'      => array(
							'description' => __( 'The chat id for the existing chat.', 'jetpack-mu-wpcom' ),
							'type'        => 'integer',
							'required'    => true,
						),
						'message_id'   => array(
							'description' => __( 'The message id for the existing message.', 'jetpack-mu-wpcom' ),
							'type'        => 'integer',
							'required'    => true,
						),
						'rating_value' => array(
							'description' => __( 'The feedback rating value.', 'jetpack-mu-wpcom' ),
							'type'        => 'integer',
							'required'    => true,
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/chat/(?P<bot_id>[a-zA-Z0-9-]+)',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'send_chat_message' ),
					'permission_callback' => 'is_user_logged_in',
					'args'                => array(
						'bot_id'  => array(
							'description' => __( 'The bot id to chat with.', 'jetpack-mu-wpcom' ),
							'type'        => 'string',
							'required'    => true,
						),
						'context' => array(
							'description' => __( 'The context to continue the chat with.', 'jetpack-mu-wpcom' ),
							'type'        => 'object',
							'required'    => false,
						),
						'message' => array(
							'description' => __( 'The message to add to the chat', 'jetpack-mu-wpcom' ),
							'type'        => 'string',
							'required'    => true,
						),
						'test'    => array(
							'description' => __( 'Whether to mark this as a test chat (a11n-only).', 'jetpack-mu-wpcom' ),
							'type'        => 'boolean',
							'required'    => false,
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/conversations/(?P<bot_id>[a-zA-Z0-9-]+)',
			// Retrieve the latest conversations of a user with the specified bot (i.e. the last messages from each chat)
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_conversations' ),
					'permission_callback' => 'is_user_logged_in',
					'args'                => array(
						'bot_id'         => array(
							'description' => __( 'The bot id to chat with.', 'jetpack-mu-wpcom' ),
							'type'        => 'string',
							'required'    => true,
						),
						'page_number'    => array(
							'description' => __( 'The number of the page to retrieve, limited to 100', 'jetpack-mu-wpcom' ),
							'type'        => 'integer',
							'required'    => false,
							'default'     => 1,
						),
						'items_per_page' => array(
							'description' => __( 'The number of items per page.', 'jetpack-mu-wpcom' ),
							'type'        => 'integer',
							'required'    => false,
							'default'     => 10,
						),
					),
				),
			)
		);
	}

	/**
	 * Send a message to the support chat.
	 *
	 * @param \WP_REST_Request $request The request sent to the API.
	 */
	public function send_chat_message( \WP_REST_Request $request ) {
		$bot_name_slug = $request->get_param( 'bot_id' );
		$chat_id       = $request->get_param( 'chat_id' );

		// Forward the request body to the support chat endpoint.
		$body = Client::wpcom_json_api_request_as_user(
			'/odie/chat/' . $bot_name_slug . '/' . $chat_id,
			'2',
			array(
				'method'  => 'POST',
				'timeout' => 60,
			),
			array(
				'message' => $request->get_param( 'message' ),
				'context' => $request->get_param( 'context' ) ?? array(),
				'version' => $request->get_param( 'version' ),
			)
		);

		if ( is_wp_error( $body ) ) {
			return $body;
		}

		$response = json_decode( wp_remote_retrieve_body( $body ) );

		return rest_ensure_response( $response );
	}

	/**
	 * Get the chat messages.
	 *
	 * @param \WP_REST_Request $request The request sent to the API.
	 */
	public function get_chat( \WP_REST_Request $request ) {
		$bot_name_slug    = $request->get_param( 'bot_id' );
		$chat_id          = $request->get_param( 'chat_id' );
		$page_number      = $request['page_number'];
		$items_per_page   = $request['items_per_page'];
		$include_feedback = $request['include_feedback'];

		$url_query_params = http_build_query(
			array(
				'page_number'      => $page_number,
				'items_per_page'   => $items_per_page,
				'include_feedback' => $include_feedback,
			)
		);

		$body = Client::wpcom_json_api_request_as_user(
			'/odie/chat/' . $bot_name_slug . '/' . $chat_id . '?' . $url_query_params
		);

		if ( is_wp_error( $body ) ) {
			return $body;
		}

		$response = json_decode( wp_remote_retrieve_body( $body ) );

		return rest_ensure_response( $response );
	}

	/**
	 * Save feedback for a chat message.
	 *
	 * @param \WP_REST_Request $request The request sent to the API.
	 */
	public function save_chat_message_feedback( \WP_REST_Request $request ) {
		$bot_id       = $request->get_param( 'bot_id' );
		$chat_id      = $request->get_param( 'chat_id' );
		$message_id   = $request->get_param( 'message_id' );
		$rating_value = $request->get_param( 'rating_value' );

		// Forward the request body to the feedback endpoint.
		$body = Client::wpcom_json_api_request_as_user(
			'/odie/chat/' . $bot_id . '/' . $chat_id . '/' . $message_id . '/feedback',
			'2',
			array( 'method' => 'POST' ),
			array(
				'rating_value' => $rating_value,
			)
		);

		if ( is_wp_error( $body ) ) {
			return $body;
		}

		$response = json_decode( wp_remote_retrieve_body( $body ) );

		return rest_ensure_response( $response );
	}

	/**
	 * Get list of AI conversations.
	 *
	 * @param \WP_REST_Request $request The request sent to the API.
	 */
	public function get_conversations( \WP_REST_Request $request ) {
		$bot_name_slug  = $request->get_param( 'bot_id' );
		$page_number    = $request['page_number'];
		$items_per_page = $request['items_per_page'];

		$url_query_params = http_build_query(
			array(
				'page_number'    => $page_number,
				'items_per_page' => $items_per_page,
			)
		);

		$body = Client::wpcom_json_api_request_as_user(
			'/odie/conversations/' . $bot_name_slug . '?' . $url_query_params
		);

		if ( is_wp_error( $body ) ) {
			return $body;
		}

		$response = json_decode( wp_remote_retrieve_body( $body ) );

		return rest_ensure_response( $response );
	}
}
