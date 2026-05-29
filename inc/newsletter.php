<?php
/**
 * Newsletter form submit handler.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_post_nopriv_noyona_newsletter_subscribe', 'noyona_handle_newsletter_subscribe' );
add_action( 'admin_post_noyona_newsletter_subscribe', 'noyona_handle_newsletter_subscribe' );

function noyona_handle_newsletter_subscribe() {
    $redirect_url = wp_get_referer() ?: home_url( '/' );

    if (
        ! isset( $_POST['noyona_newsletter_nonce'] )
        || ! wp_verify_nonce(
            sanitize_text_field( wp_unslash( $_POST['noyona_newsletter_nonce'] ) ),
            'noyona_newsletter_subscribe'
        )
    ) {
        wp_safe_redirect( add_query_arg( 'newsletter_error', 'invalid_nonce', $redirect_url ) );
        exit;
    }

    if ( noyona_is_recaptcha_enabled() ) {
        $captcha_result = noyona_verify_recaptcha_from_post( 'g-recaptcha-response', 'newsletter_subscribe' );
        if ( is_wp_error( $captcha_result ) ) {
            wp_safe_redirect( add_query_arg( 'newsletter_error', 'captcha_failed', $redirect_url ) );
            exit;
        }
    }

    $email = isset( $_POST['newsletter_email'] )
        ? sanitize_email( wp_unslash( $_POST['newsletter_email'] ) )
        : '';

    if ( '' === $email || ! is_email( $email ) ) {
        wp_safe_redirect( add_query_arg( 'newsletter_error', 'invalid_email', $redirect_url ) );
        exit;
    }

    $to      = get_option( 'admin_email' );
    $subject = 'Newsletter Signup';
    $body    = "A new newsletter signup was submitted.\n\nEmail: {$email}\n";
    $headers = array(
        'Content-Type: text/plain; charset=UTF-8',
    );

    $sent = wp_mail( $to, $subject, $body, $headers );
    if ( ! $sent ) {
        wp_safe_redirect( add_query_arg( 'newsletter_error', 'mail_failed', $redirect_url ) );
        exit;
    }

    wp_safe_redirect( add_query_arg( 'newsletter_success', '1', $redirect_url ) );
    exit;
}
