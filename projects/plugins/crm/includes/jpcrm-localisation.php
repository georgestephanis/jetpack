<?php
/**
 * Herein lie various functions designed to make localisation easier.
 * // phpcs:ignore Squiz.Commenting.FileComment.MissingPackageTag
 */

// prevent direct access
if ( ! defined( 'ZEROBSCRM_PATH' ) ) {
	exit( 0 );
}

/**
 * Creates a timezone-aware datetime string
 *
 * @param int  $timestamp Unix timestamp.
 * @param str  $format DateTime formatting string (e.g. 'Y-m-d H:i').
 * @param bool $use_utc Output in UTC timezone or WP timezone.
 *
 * @return str formatted datetime string
 */
function jpcrm_uts_to_datetime_str( $timestamp, $format = false, $use_utc = false ) {

	if ( $timestamp === '' ) {
		return false;
	}
	// default to WP format
	if ( ! $format ) {
		$format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
	}

	// create DateTime object from UTS
	try {
		$date_obj = new DateTime( '@' . $timestamp );
	} catch ( Exception $e ) {
		// Unable to parse timestamp, so probably not a UTS.
		return false;
	}

	// something's wrong, so abort
	if ( ! $date_obj ) {
		return false;
	}

	// set timezone for object
	if ( $use_utc ) {
		// output date as if in UTC
		$timezone = new DateTimeZone( 'UTC' );
	} else {
		// output date using WP timezone
		$timezone = new DateTimeZone( wp_timezone_string() );
	}
	$date_obj->setTimezone( $timezone );

	// return formatted string
	return $date_obj->format( $format );
}

/**
 * Creates a timezone-aware date string
 * This is a wrapper of jpcrm_uts_to_datetime_str()
 *
 * @param int  $timestamp Unix timestamp.
 * @param str  $format DateTime formatting string (e.g. 'Y-m-d').
 * @param bool $use_utc Output in UTC timezone or WP timezone.
 *
 * @return string formatted date string
 */
function jpcrm_uts_to_date_str( $timestamp, $format = false, $use_utc = false ) {

	// default to WP format
	if ( ! $format ) {
		$format = get_option( 'date_format' );
	}

	return jpcrm_uts_to_datetime_str( $timestamp, $format, $use_utc );
}

/**
 * Creates a timezone-aware time string
 * This is a wrapper of jpcrm_uts_to_datetime_str()
 *
 * @param int $timestamp Unix timestamp.
 * @param str $format DateTime formatting string (e.g. 'H:i').
 *
 * @return str formatted time string
 */
function jpcrm_uts_to_time_str( $timestamp, $format = false ) {

	// default to WP format
	if ( ! $format ) {
		$format = get_option( 'time_format' );
	}

	return jpcrm_uts_to_datetime_str( $timestamp, $format );
}

/**
 * Creates a UTS from a date time string
 *
 * @param str  $datetime_str String containing date and time (in WP timezone).
 * @param str  $format DateTime formatting string (e.g. 'Y-m-d H:i:s').
 * @param bool $use_utc Treat input as UTC timezone or WP timezone.
 *
 * @return int $uts
 */
function jpcrm_datetime_str_to_uts( $datetime_str, $format = false, $use_utc = false ) {

	// default to ISO
	if ( ! $format ) {
		// append seconds to provided string if not present
		if ( substr_count( $datetime_str, ':' ) === 1 ) {
			$datetime_str .= ':00';
		}

		$format = 'Y-m-d H:i:s';
	}

	// set timezone
	if ( $use_utc ) {
		// output date as if in UTC
		$timezone = new DateTimeZone( 'UTC' );
	} else {
		// output date using WP timezone
		$timezone = new DateTimeZone( wp_timezone_string() );
	}

	// create DateTime object from string
	$date_obj = DateTime::createFromFormat( $format, $datetime_str, $timezone );

	// something's wrong, so abort
	if ( ! $date_obj ) {
		return false;
	}

	return $date_obj->getTimestamp();
}

/**
 * Creates a UTS from two POST keys (e.g. 'somefield_datepart' and 'somefield_timepart')
 *
 * @param string $post_key POST key prefix (e.g. 'somefield').
 * @param string $format DateTime formatting string (e.g. 'Y-m-d H:i').
 *
 * @return int $uts
 */
function jpcrm_datetime_post_keys_to_uts( $post_key, $format = false ) {
	$datepart = empty( $_POST[ $post_key . '_datepart' ] ) ? '' : sanitize_text_field( $_POST[ $post_key . '_datepart' ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.MissingUnslash

	// if no time, default to midnight
	$timepart = empty( $_POST[ $post_key . '_timepart' ] ) ? '0:00:00' : sanitize_text_field( $_POST[ $post_key . '_timepart' ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.MissingUnslash

	// return the UTS if possible
	if ( ! empty( $datepart ) ) {
		return jpcrm_datetime_str_to_uts( $datepart . ' ' . $timepart, $format );
	}

	return false;
}

/**
 * Creates a UTS from a WP-formatted date time string
 * This is a wrapper of jpcrm_datetime_str_to_uts()
 *
 * @param str $datetime_str String containing date and time (in WP timezone).
 *
 * @return int $uts
 */
function jpcrm_datetime_str_wp_format_to_uts( $datetime_str ) {
	// use WP format
	$format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
	return jpcrm_datetime_str_to_uts( $datetime_str, $format );
}

/**
 * Creates a UTS from a date string (midnight timestamp)
 * This is a wrapper of jpcrm_datetime_str_to_uts()
 *
 * @param str  $date_str String containing date (in WP timezone).
 * @param str  $format DateTime formatting string (e.g. 'Y-m-d').
 * @param bool $use_utc Treat input as UTC timezone or WP timezone.
 *
 * @return int $uts
 */
function jpcrm_date_str_to_uts( $date_str, $format = false, $use_utc = false ) {
	if ( ! $format ) {
		// Using ! ensures object is created with midnight timestamp instead of system time
		$format = '!Y-m-d';
	}

	return jpcrm_datetime_str_to_uts( $date_str, $format, $use_utc );
}

/**
 * Creates a UTS from a WP-formatted date string
 * This is a wrapper of jpcrm_datetime_str_to_uts()
 *
 * @param string  $date_str String containing date (in WP timezone).
 * @param boolean $use_utc Treat input as UTC timezone or WP timezone.
 *
 * @return int $uts
 */
function jpcrm_date_str_wp_format_to_uts( $date_str, $use_utc = false ) {
	// use WP format
	$format = '!' . get_option( 'date_format' );
	return jpcrm_datetime_str_to_uts( $date_str, $format, $use_utc );
}

/**
 * Returns WP timezone offset string (e.g. -10:00)
 *
 * @return str timezone offset string
 */
function jpcrm_get_wp_timezone_offset() {
	$date_obj = new DateTime();
	$date_obj->setTimezone( new DateTimeZone( wp_timezone_string() ) );
	return $date_obj->format( 'P' );
}

/**
 * Returns WP timezone offset string in seconds (e.g. -3600 for -1h)
 *
 * @return string timezone offset string
 */
function jpcrm_get_wp_timezone_offset_in_seconds() {
	$date_obj = new DateTime();
	$date_obj->setTimezone( new DateTimeZone( wp_timezone_string() ) );
	return $date_obj->format( 'Z' );
}
