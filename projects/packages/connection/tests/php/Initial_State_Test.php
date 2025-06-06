<?php

namespace Automattic\Jetpack\Connection;

use Automattic\Jetpack\Status;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Initial_State class.
 *
 * @package automattic/jetpack-connection
 */
class Initial_State_Test extends TestCase {

	/**
	 * Ensures that all of the expected fields and no other fields are returned by get_data().
	 */
	public function test_render() {
		global $wp_version;

		// Ensure that the nonces match up despite slight time differences.
		add_filter(
			'nonce_life',
			function () {
				return PHP_INT_MAX;
			}
		);

		// Ensure a consistent gravatar URL (by default, it has a random subdomain of 0, 1, or 2.
		add_filter(
			'get_avatar_url',
			function () {
				return 'https://gravatar.com/';
			}
		);

		$_GET['calypso_env'] = 'wpcalypso';

		$expected_state = array(
			'apiRoot'            => esc_url_raw( rest_url() ),
			'apiNonce'           => wp_create_nonce( 'wp_rest' ),
			'registrationNonce'  => wp_create_nonce( 'jetpack-registration-nonce' ),
			'connectionStatus'   => REST_Connector::connection_status( false ),
			'userConnectionData' => REST_Connector::get_user_connection_data( false ),
			'connectedPlugins'   => REST_Connector::get_connection_plugins( false ),
			'wpVersion'          => $wp_version,
			'siteSuffix'         => ( new Status() )->get_site_suffix(),
			'connectionErrors'   => Error_Handler::get_instance()->get_verified_errors(),
			'isOfflineMode'      => ( new Status() )->is_offline_mode(),
			'calypsoEnv'         => 'wpcalypso',
		);
		$expected_value = 'var JP_CONNECTION_INITIAL_STATE; typeof JP_CONNECTION_INITIAL_STATE === "object" || (JP_CONNECTION_INITIAL_STATE = JSON.parse(decodeURIComponent("' . rawurlencode( wp_json_encode( $expected_state ) ) . '")));';

		$actual_value = Initial_State::render();

		unset( $_GET['calypso_env'] );

		$this->assertEquals( $expected_value, $actual_value );
	}
}
