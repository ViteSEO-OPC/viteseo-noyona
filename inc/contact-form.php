<?php
/**
 * Contact form submit handler and validators.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ----- Contact form submit handler (with validators) ----- */
add_action('admin_post_nopriv_noyona_contact_form_submit', 'noyona_handle_contact_form');
add_action('admin_post_noyona_contact_form_submit', 'noyona_handle_contact_form');

function noyona_is_valid_name($name) {
    $name = trim($name);
    if ($name === '' || mb_strlen($name) > 60) return false;

    // Unicode letters + optional separators: space, apostrophe, hyphen
    return (bool) preg_match("/^[\p{L}\p{M}]+(?:[ '\-][\p{L}\p{M}]+)*$/u", $name);
}

function noyona_normalize_phone($phone) {
    $phone = trim($phone);

    // Keep digits, spaces, +, -, parentheses (for display/validation)
    $phone = preg_replace('/[^0-9\+\-\s\(\)]/', '', $phone);

    // Must contain at least 7 digits
    $digits = preg_replace('/\D+/', '', $phone);
    if (strlen($digits) < 7 || strlen($digits) > 15) {
        return false;
    }

    return $phone;
}

function noyona_handle_contact_form() {

    // CSRF
    if (
        ! isset($_POST['noyona_contact_form_nonce']) ||
        ! wp_verify_nonce($_POST['noyona_contact_form_nonce'], 'noyona_contact_form')
    ) {
        wp_die('Security check failed.', 403);
    }

    // Honeypot (bots)
    if (!empty($_POST['website'])) {
        wp_safe_redirect( wp_get_referer() ?: home_url('/') );
        exit;
    }

    // OPTIONAL: Rate limit by IP (simple spam control)
    $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown';
    $key = 'noyona_cf_' . md5($ip);
    $count = (int) get_transient($key);
    if ($count >= 5) { // 5 submits per 10 minutes
        wp_safe_redirect( add_query_arg('cf_error', 'rate_limited', wp_get_referer() ?: home_url('/') ) );
        exit;
    }
    set_transient($key, $count + 1, 10 * MINUTE_IN_SECONDS);

    // Sanitize
    $first_name = isset($_POST['first_name']) ? sanitize_text_field(wp_unslash($_POST['first_name'])) : '';
    $last_name  = isset($_POST['last_name']) ? sanitize_text_field(wp_unslash($_POST['last_name'])) : '';
    $email      = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
    $contact    = isset($_POST['contact_number']) ? sanitize_text_field(wp_unslash($_POST['contact_number'])) : '';
    $subject    = isset($_POST['subject']) ? sanitize_text_field(wp_unslash($_POST['subject'])) : '';
    $message    = isset($_POST['message']) ? sanitize_textarea_field(wp_unslash($_POST['message'])) : '';

    // Validate names
    if (!noyona_is_valid_name($first_name) || !noyona_is_valid_name($last_name)) {
        wp_safe_redirect( add_query_arg('cf_error', 'invalid_name', wp_get_referer() ?: home_url('/') ) );
        exit;
    }

    // Validate email
    if (empty($email) || !is_email($email)) {
        wp_safe_redirect( add_query_arg('cf_error', 'invalid_email', wp_get_referer() ?: home_url('/') ) );
        exit;
    }

    // Validate phone
    $contact_normalized = noyona_normalize_phone($contact);
    if ($contact_normalized === false) {
        wp_safe_redirect( add_query_arg('cf_error', 'invalid_phone', wp_get_referer() ?: home_url('/') ) );
        exit;
    }

    // Validate subject/message length
    if (mb_strlen($subject) < 3 || mb_strlen($subject) > 120) {
        wp_safe_redirect( add_query_arg('cf_error', 'invalid_subject', wp_get_referer() ?: home_url('/') ) );
        exit;
    }

    if (mb_strlen($message) < 10 || mb_strlen($message) > 2000) {
        wp_safe_redirect( add_query_arg('cf_error', 'invalid_message', wp_get_referer() ?: home_url('/') ) );
        exit;
    }

    // ✅ Example: Send email safely
    $to = get_option('admin_email');
    $mail_subject = 'Contact Form: ' . $subject;

    $body  = "Name: {$first_name} {$last_name}\n";
    $body .= "Email: {$email}\n";
    $body .= "Phone: {$contact_normalized}\n\n";
    $body .= "Message:\n{$message}\n";

    $headers = array(
        'Content-Type: text/plain; charset=UTF-8',
        'Reply-To: ' . $first_name . ' ' . $last_name . ' <' . $email . '>',
    );

    wp_mail($to, $mail_subject, $body, $headers);

    wp_safe_redirect( add_query_arg('cf_success', '1', wp_get_referer() ?: home_url('/') ) );
    exit;
}

