<?php
/**
 * Utility functions for jetpack-mu-wpcom.
 *
 * @package automattic/jetpack-mu-wpcom
 */

use Automattic\Jetpack\Connection\Manager as Connection_Manager;
use Automattic\Jetpack\Jetpack_Mu_Wpcom;

/**
 * Whether the site is fully managed agency site.
 *
 * @return bool True if the site is fully managed agency site.
 */
function is_fully_managed_agency_site() {
	return ! empty( get_option( 'is_fully_managed_agency_site' ) );
}

/**
 * Whether the current user is logged-in via WordPress.com account.
 *
 * @return bool True if the user has associated WordPress.com account.
 */
function is_wpcom_user() {
	// If the site is explicitly marked as agency-managed, treat the user as non-wpcom user.
	if ( is_fully_managed_agency_site() ) {
		return false;
	}

	$user_id = get_current_user_id();

	if ( function_exists( '\A8C\Billingdaddy\Users\get_wpcom_user' ) ) {
		// On Simple sites, use get_wpcom_user function to check if the user has a WordPress.com account.
		$user        = \A8C\Billingdaddy\Users\get_wpcom_user( $user_id );
		$has_account = isset( $user->ID );
	} else {
		// On Atomic sites, use the Connection Manager to check if the user has a WordPress.com account.
		$connection_manager = new Connection_Manager();
		$wpcom_user_data    = $connection_manager->get_connected_user_data( $user_id );
		$has_account        = isset( $wpcom_user_data['ID'] );
	}

	return $has_account;
}

/**
 * Helper function to return the site slug for Calypso URLs.
 * The fallback logic here is derived from the following code:
 *
 * @see https://github.com/Automattic/wc-calypso-bridge/blob/85664e2c7836b2ddc29e99871ec2c5dc4015bcc8/class-wc-calypso-bridge.php#L227-L251
 *
 * @return string
 */
function wpcom_get_site_slug() {
	if ( defined( 'IS_WPCOM' ) && IS_WPCOM && class_exists( 'WPCOM_Masterbar' ) && method_exists( 'WPCOM_Masterbar', 'get_calypso_site_slug' ) ) {
		return WPCOM_Masterbar::get_calypso_site_slug( get_current_blog_id() );
	}

	// The Jetpack class should be auto-loaded if Jetpack has been loaded,
	// but we've seen fatal errors from cases where the class wasn't defined.
	// So let's make double-sure it exists before calling it.
	if ( class_exists( '\Automattic\Jetpack\Status' ) ) {
		$jetpack_status = new \Automattic\Jetpack\Status();

		return $jetpack_status->get_site_suffix();
	}

	// If the Jetpack Status class doesn't exist, fall back on site_url()
	// with any trailing '/' characters removed.
	$site_url = untrailingslashit( site_url( '/', 'https' ) );

	// Remove the leading 'https://' and replace any remaining `/` characters with ::
	return str_replace( '/', '::', substr( $site_url, 8 ) );
}

/**
 * Returns the Calypso domain that originated the current request.
 *
 * @return string
 */
function wpcom_get_calypso_origin() {
	$origin  = ! empty( $_GET['calypso_origin'] ) ? wp_unslash( $_GET['calypso_origin'] ) : 'https://wordpress.com'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$allowed = array(
		'http://calypso.localhost:3000',
		'http://127.0.0.1:41050', // Desktop App.
		'https://wpcalypso.wordpress.com',
		'https://horizon.wordpress.com',
		'https://wordpress.com',
	);
	return in_array( $origin, $allowed, true ) ? $origin : 'https://wordpress.com';
}

/**
 * Returns the Calypso domain that originated the current request.
 *
 * @param string $asset_name The name of the asset.
 * @param array  $asset_types The types of the asset.
 *
 * @return string
 */
function jetpack_mu_wpcom_enqueue_assets( $asset_name, $asset_types = array() ) {
	$asset_handle = "jetpack-mu-wpcom-$asset_name";
	$asset_file   = include Jetpack_Mu_Wpcom::BASE_DIR . "build/$asset_name/$asset_name.asset.php";

	if ( in_array( 'js', $asset_types, true ) ) {
		$js_file      = "build/$asset_name/$asset_name.js";
		$dependencies = $asset_file['dependencies'] ?? array();
		wp_enqueue_script(
			"jetpack-mu-wpcom-$asset_name",
			plugins_url( $js_file, Jetpack_Mu_Wpcom::BASE_FILE ),
			$dependencies,
			$asset_file['version'] ?? filemtime( Jetpack_Mu_Wpcom::BASE_DIR . $js_file ),
			true
		);
		if ( in_array( 'wp-i18n', $dependencies, true ) ) {
			wp_set_script_translations( $asset_handle, 'jetpack-mu-wpcom' );
		}
	}

	if ( in_array( 'css', $asset_types, true ) ) {
		$css_ext  = is_rtl() ? 'rtl.css' : 'css';
		$css_file = "build/$asset_name/$asset_name.$css_ext";
		wp_enqueue_style(
			"jetpack-mu-wpcom-$asset_name",
			plugins_url( $css_file, Jetpack_Mu_Wpcom::BASE_FILE ),
			array(),
			filemtime( Jetpack_Mu_Wpcom::BASE_DIR . $css_file )
		);
	}

	return $asset_handle;
}

/**
 * Returns the WP.com blog ID for the current site.
 *
 * @return int|false The WP.com blog ID, or false if the site does not have a WP.com blog ID.
 */
function get_wpcom_blog_id() {
	if ( defined( 'IS_WPCOM' ) && IS_WPCOM ) {
		return get_current_blog_id();
	}

	if ( defined( 'IS_ATOMIC' ) && IS_ATOMIC ) {
		/*
		 * Atomic sites have the WP.com blog ID stored as a Jetpack option. This
		 * code deliberately doesn't use `Jetpack_Options::get_option` so it
		 * works even when Jetpack has not been loaded.
		 */
		$jetpack_options = get_option( 'jetpack_options' );
		if ( is_array( $jetpack_options ) && isset( $jetpack_options['id'] ) ) {
			return (int) $jetpack_options['id'];
		}

		return get_current_blog_id();
	}

	return false;
}

/**
 * Check if the site is a WordPress.com Atomic site.
 *
 * @return bool
 */
function is_woa_site() {
	if ( ! class_exists( 'Automattic\Jetpack\Status\Host' ) ) {
		return false;
	}
	$host = new Automattic\Jetpack\Status\Host();
	return $host->is_woa_site();
}

/**
 * Whether the current user is connected to WordPress.com.
 *
 * @param int $user_id the user identifier. Default is the current user.
 * @return bool Boolean is the user connected?
 */
function is_user_connected( $user_id ) {
	if ( defined( 'IS_WPCOM' ) && IS_WPCOM ) {
		return true;
	}

	return ( new Connection_Manager( 'jetpack' ) )->is_user_connected( $user_id );
}
