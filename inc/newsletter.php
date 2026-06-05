<?php
/**
 * Newsletter form submit handlers.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const NOYONA_NEWSLETTER_ACTION = 'noyona_newsletter_subscribe';

add_action( 'admin_post_nopriv_' . NOYONA_NEWSLETTER_ACTION, 'noyona_handle_newsletter_subscribe' );
add_action( 'admin_post_' . NOYONA_NEWSLETTER_ACTION, 'noyona_handle_newsletter_subscribe' );
add_action( 'wp_ajax_nopriv_' . NOYONA_NEWSLETTER_ACTION, 'noyona_ajax_newsletter_subscribe' );
add_action( 'wp_ajax_' . NOYONA_NEWSLETTER_ACTION, 'noyona_ajax_newsletter_subscribe' );

/**
 * URL to return to after newsletter submit (current page when the form was shown).
 */
function noyona_newsletter_redirect_back_url() {
	if ( is_front_page() ) {
		return home_url( '/' );
	}

	if ( is_singular() ) {
		$permalink = get_permalink();
		if ( is_string( $permalink ) && '' !== $permalink ) {
			return $permalink;
		}
	}

	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
	if ( ! is_string( $request_uri ) || '' === $request_uri ) {
		$request_uri = '/';
	}

	return home_url( $request_uri );
}

/**
 * Safe redirect target after admin-post handler (strips stale newsletter query args).
 */
function noyona_newsletter_redirect_url() {
	$url       = wp_get_referer() ?: home_url( '/' );
	$validated = wp_validate_redirect( $url, home_url( '/' ) ) ?: home_url( '/' );

	return remove_query_arg( array( 'newsletter_error', 'newsletter_success' ), $validated );
}

/**
 * @return array<string, string>
 */
function noyona_newsletter_error_messages() {
	return array(
		'invalid_nonce'  => __( 'Session expired. Please try again.', 'noyona-childtheme' ),
		'captcha_failed' => __( 'Captcha verification failed. Please try again.', 'noyona-childtheme' ),
		'invalid_email'  => __( 'Please enter a valid email address.', 'noyona-childtheme' ),
		'mail_failed'    => __( 'Could not send your signup right now. Please try again later.', 'noyona-childtheme' ),
	);
}

/**
 * @param string $code Error code.
 * @return array{type: string, message: string, autohide: int}
 */
function noyona_newsletter_error_notice( $code ) {
	$code     = sanitize_key( (string) $code );
	$messages = noyona_newsletter_error_messages();

	return array(
		'type'     => 'error',
		'message'  => $messages[ $code ] ?? __( 'Could not subscribe right now. Please try again.', 'noyona-childtheme' ),
		'autohide' => 0,
	);
}

/**
 * @return array{type: string, message: string, autohide: int}
 */
function noyona_newsletter_success_notice() {
	return array(
		'type'     => 'success',
		'message'  => __( 'Thank you! You are subscribed to our newsletter updates.', 'noyona-childtheme' ),
		'autohide' => 10000,
	);
}

/**
 * Notice payload for the newsletter strip after redirect (?newsletter_error= / ?newsletter_success=).
 *
 * @return array{type: string, message: string, autohide: int}|null
 */
function noyona_newsletter_notice_from_query() {
	if ( isset( $_GET['newsletter_success'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return noyona_newsletter_success_notice();
	}

	if ( ! isset( $_GET['newsletter_error'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return null;
	}

	return noyona_newsletter_error_notice( wp_unslash( $_GET['newsletter_error'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

/**
 * Validate and process a newsletter signup.
 *
 * @return array{type: string, message: string, autohide: int}|WP_Error
 */
function noyona_process_newsletter_subscribe() {
	if (
		! isset( $_POST['noyona_newsletter_nonce'] )
		|| ! wp_verify_nonce(
			sanitize_text_field( wp_unslash( $_POST['noyona_newsletter_nonce'] ) ),
			NOYONA_NEWSLETTER_ACTION
		)
	) {
		return new WP_Error( 'invalid_nonce' );
	}

	if ( function_exists( 'noyona_recaptcha_form_verify_post' ) ) {
		$captcha_result = noyona_recaptcha_form_verify_post( 'newsletter' );
		if ( is_wp_error( $captcha_result ) ) {
			return new WP_Error( 'captcha_failed' );
		}
	}

	$email = isset( $_POST['newsletter_email'] )
		? sanitize_email( wp_unslash( $_POST['newsletter_email'] ) )
		: '';

	if ( '' === $email || ! is_email( $email ) ) {
		return new WP_Error( 'invalid_email' );
	}

	$sent = wp_mail(
		get_option( 'admin_email' ),
		'Newsletter Signup',
		"A new newsletter signup was submitted.\n\nEmail: {$email}\n",
		array( 'Content-Type: text/plain; charset=UTF-8' )
	);

	if ( ! $sent ) {
		return new WP_Error( 'mail_failed' );
	}

	return noyona_newsletter_success_notice();
}

/**
 * Non-JS fallback: full-page POST + redirect.
 */
function noyona_handle_newsletter_subscribe() {
	$redirect_url = noyona_newsletter_redirect_url();
	$result       = noyona_process_newsletter_subscribe();

	if ( is_wp_error( $result ) ) {
		wp_safe_redirect( add_query_arg( 'newsletter_error', $result->get_error_code(), $redirect_url ) );
		exit;
	}

	wp_safe_redirect( add_query_arg( 'newsletter_success', '1', $redirect_url ) );
	exit;
}

/**
 * AJAX handler: JSON notice payload, no page reload.
 */
function noyona_ajax_newsletter_subscribe() {
	$result = noyona_process_newsletter_subscribe();

	if ( is_wp_error( $result ) ) {
		wp_send_json_error(
			array( 'notice' => noyona_newsletter_error_notice( $result->get_error_code() ) ),
			400
		);
	}

	wp_send_json_success( array( 'notice' => $result ) );
}

add_filter( 'render_block', 'noyona_newsletter_strip_prepare_view_script', 9, 2 );
function noyona_newsletter_strip_prepare_view_script( $content, $block ) {
	if ( ! is_array( $block ) || ( $block['blockName'] ?? '' ) !== 'noyona/newsletter-strip' ) {
		return $content;
	}

	static $prepared = false;
	if ( $prepared ) {
		return $content;
	}
	$prepared = true;

	$handle = 'noyona-newsletter-strip-view-script';
	if ( ! wp_script_is( $handle, 'registered' ) ) {
		return $content;
	}

	if ( wp_script_is( 'noyona-notices', 'registered' ) ) {
		$deps = (array) wp_scripts()->registered[ $handle ]->deps;
		if ( ! in_array( 'noyona-notices', $deps, true ) ) {
			wp_scripts()->registered[ $handle ]->deps = array_merge( $deps, array( 'noyona-notices' ) );
		}

		wp_enqueue_script( 'noyona-notices' );
	}

	wp_localize_script(
		$handle,
		'noyonaNewsletterStrip',
		array(
			'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
			'ajaxAction'    => NOYONA_NEWSLETTER_ACTION,
			'invalidEmail'  => __( 'Please enter a valid email address.', 'noyona-childtheme' ),
			'captchaFailed' => __( 'Captcha verification failed. Please try again.', 'noyona-childtheme' ),
			'networkError'  => __( 'Could not subscribe right now. Please try again.', 'noyona-childtheme' ),
			'submitting'    => __( 'Submitting...', 'noyona-childtheme' ),
		)
	);

	return $content;
}
