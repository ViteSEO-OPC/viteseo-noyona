<?php
/**
 * Brevo (Sendinblue) API integration.
 *
 * Adds newsletter signups (from the newsletter strip and the checkout opt-in)
 * to a Brevo contact list via the Brevo REST API v3.
 *
 * Configuration lives in wp-config.php (kept out of the repo), mirroring the
 * reCAPTCHA key convention:
 *
 *   define( 'NOYONA_BREVO_API_KEY', 'xkeysib-...' );  // Brevo > SMTP & API > API Keys
 *   define( 'NOYONA_BREVO_LIST_ID', 3 );              // Brevo > Contacts > Lists (numeric ID)
 *
 * When the constants are absent the helpers no-op safely, so the site keeps
 * working (the newsletter strip falls back to its admin-email notification).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Brevo API key from wp-config, or '' when not configured.
 *
 * @return string
 */
function noyona_brevo_api_key() {
	return defined( 'NOYONA_BREVO_API_KEY' ) ? trim( (string) NOYONA_BREVO_API_KEY ) : '';
}

/**
 * Default Brevo list ID new contacts are added to, or 0 when not configured.
 *
 * @return int
 */
function noyona_brevo_list_id() {
	return defined( 'NOYONA_BREVO_LIST_ID' ) ? (int) NOYONA_BREVO_LIST_ID : 0;
}

/**
 * Whether Brevo is configured enough to send contacts.
 *
 * @return bool
 */
function noyona_brevo_is_configured() {
	return '' !== noyona_brevo_api_key() && noyona_brevo_list_id() > 0;
}

/**
 * Add (or update) a contact in Brevo and subscribe them to the list.
 *
 * Uses `updateEnabled: true` so an already-existing contact is updated rather
 * than returning an error — re-subscribing is therefore idempotent.
 *
 * @param string               $email      Contact email address.
 * @param array<string, mixed> $attributes Optional Brevo attributes (e.g. FIRSTNAME, LASTNAME).
 * @param int|null             $list_id    Optional list override; defaults to NOYONA_BREVO_LIST_ID.
 * @return true|WP_Error True on success, WP_Error on failure or when unconfigured.
 */
function noyona_brevo_add_contact( $email, $attributes = array(), $list_id = null ) {
	$email = sanitize_email( (string) $email );
	if ( '' === $email || ! is_email( $email ) ) {
		return new WP_Error( 'brevo_invalid_email', 'Invalid email address.' );
	}

	if ( ! noyona_brevo_is_configured() ) {
		return new WP_Error( 'brevo_not_configured', 'Brevo API key / list ID is not configured.' );
	}

	$list_id = ( null === $list_id ) ? noyona_brevo_list_id() : (int) $list_id;

	// Drop empty attribute values so we never overwrite existing data with blanks.
	$attributes = array_filter(
		(array) $attributes,
		static function ( $value ) {
			return '' !== trim( (string) $value );
		}
	);

	$body = array(
		'email'         => $email,
		'listIds'       => array( $list_id ),
		'updateEnabled' => true,
	);
	if ( ! empty( $attributes ) ) {
		$body['attributes'] = $attributes;
	}

	$response = wp_remote_post(
		'https://api.brevo.com/v3/contacts',
		array(
			'timeout' => 10,
			'headers' => array(
				'api-key'      => noyona_brevo_api_key(),
				'Content-Type' => 'application/json',
				'Accept'       => 'application/json',
			),
			'body'    => wp_json_encode( $body ),
		)
	);

	if ( is_wp_error( $response ) ) {
		noyona_brevo_log( 'request failed: ' . $response->get_error_message() );
		return $response;
	}

	$code = (int) wp_remote_retrieve_response_code( $response );

	// 201 = created, 204 = updated (both success). 400 with "duplicate_parameter" can
	// also occur on some accounts; treat any 2xx as success, everything else as error.
	if ( $code >= 200 && $code < 300 ) {
		return true;
	}

	$message = wp_remote_retrieve_body( $response );
	noyona_brevo_log( "unexpected response {$code}: {$message}" );

	return new WP_Error( 'brevo_http_error', "Brevo returned HTTP {$code}.", array( 'status' => $code ) );
}

/**
 * Lightweight error logger (only writes when WP_DEBUG_LOG is on).
 *
 * @param string $message Message to log.
 * @return void
 */
function noyona_brevo_log( $message ) {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
		error_log( '[noyona-brevo] ' . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}
}
