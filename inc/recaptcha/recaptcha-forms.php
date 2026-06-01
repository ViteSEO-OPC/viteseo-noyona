<?php
/**
 * Per-form reCAPTCHA version switcher (modular layer on top of inc/recaptcha/recaptcha.php).
 *
 * GOAL: each form (contact, login, register, newsletter) can independently use
 * reCAPTCHA 'v2' (checkbox) or 'v3' (invisible score) and be switched at any time.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * HOW TO SWITCH A FORM'S VERSION
 * ─────────────────────────────────────────────────────────────────────────────
 * Edit the array in noyona_recaptcha_form_version_map() below. Set each form to
 * 'v2' or 'v3'. That is the ONLY place you need to change.
 *
 * (Advanced: you can also override at runtime without editing this file via the
 *  'noyona_recaptcha_form_version_map' or 'noyona_recaptcha_form_version' filters.)
 *
 * This file only wraps the existing shared helpers in inc/recaptcha/recaptcha.php. It does
 * not change wp-config, keys, or any unrelated behavior. Form files should only
 * CALL the functions here; no reCAPTCHA logic should live inside the form files.
 *
 * @package Noyona
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * THE SWITCH: form slug => version ( 'v2' | 'v3' ).
 *
 * @return array<string,string>
 */
function noyona_recaptcha_form_version_map() {
    $map = array(
        'contact'    => 'v2',
        'login'      => 'v3',
        'register'   => 'v2',
        'newsletter' => 'v3',
    );

    return (array) apply_filters( 'noyona_recaptcha_form_version_map', $map );
}

/**
 * Resolve the reCAPTCHA version for a given form, normalized to 'v2' or 'v3'.
 *
 * @param string $form Form slug (contact|login|register|newsletter).
 * @return string
 */
function noyona_recaptcha_form_version( $form ) {
    $map     = noyona_recaptcha_form_version_map();
    $version = isset( $map[ $form ] ) ? strtolower( trim( (string) $map[ $form ] ) ) : 'v2';

    if ( ! in_array( $version, array( 'v2', 'v3' ), true ) ) {
        $version = 'v2';
    }

    return (string) apply_filters( 'noyona_recaptcha_form_version', $version, $form );
}

/**
 * The v3 "action" name reported for a form (used for verification + JS execute).
 * Returns '' for v2 (v2 has no action concept).
 *
 * @param string $form Form slug.
 * @return string
 */
function noyona_recaptcha_form_action( $form ) {
    $actions = array(
        'contact'    => 'contact',
        'login'      => 'login',
        'register'   => 'register',
        'newsletter' => 'newsletter_subscribe',
    );

    $action = isset( $actions[ $form ] ) ? $actions[ $form ] : sanitize_key( (string) $form );

    return (string) apply_filters( 'noyona_recaptcha_form_action', $action, $form );
}

/**
 * Whether reCAPTCHA is configured/enabled for a form's chosen version.
 *
 * @param string $form Form slug.
 * @return bool
 */
function noyona_recaptcha_form_enabled( $form ) {
    if ( ! function_exists( 'noyona_is_recaptcha_enabled' ) ) {
        return false;
    }

    return (bool) noyona_is_recaptcha_enabled( noyona_recaptcha_form_version( $form ) );
}

/**
 * Allowed HTML for echoing a widget (kept in one place so callers stay tiny).
 * Permits the v2 checkbox div (data-* attributes) and the v3 hidden input.
 *
 * @return array
 */
function noyona_recaptcha_form_allowed_html() {
    return array(
        'div'   => array(
            'class'                 => true,
            'data-sitekey'          => true,
            'data-callback'         => true,
            'data-expired-callback' => true,
            'data-size'             => true,
            'data-theme'            => true,
            'data-badge'            => true,
            'data-action'           => true,
        ),
        'input' => array(
            'type'  => true,
            'name'  => true,
            'value' => true,
        ),
    );
}

/**
 * Enqueue the front-end assets needed by a form for its chosen version.
 * Safe to call multiple times (underlying enqueues are idempotent).
 *
 * @param string $form Form slug.
 * @return void
 */
function noyona_recaptcha_form_enqueue_assets( $form ) {
    if ( ! noyona_recaptcha_form_enabled( $form ) ) {
        return;
    }

    $version = noyona_recaptcha_form_version( $form );

    if ( function_exists( 'noyona_enqueue_recaptcha_script' ) ) {
        noyona_enqueue_recaptcha_script( $version );
    }

    // For v3, the hidden token field must be populated by JS before submit.
    // The newsletter form already ships its own dedicated handler
    // (assets/js/recaptcha/recaptcha-v3.js), so we only attach the generic generator to
    // the login / register / contact forms.
    if ( 'v3' === $version && 'newsletter' !== $form ) {
        noyona_recaptcha_enqueue_v3_form_token_script();
    }
}

/**
 * Enqueue + configure the generic v3 token generator for login/register/contact.
 *
 * @return void
 */
function noyona_recaptcha_enqueue_v3_form_token_script() {
    static $localized = false;

    $handle = 'noyona-recaptcha-forms-v3';

    if ( ! wp_script_is( $handle, 'enqueued' ) && ! wp_script_is( $handle, 'registered' ) ) {
        $script_path = get_stylesheet_directory() . '/assets/js/recaptcha/recaptcha-forms-v3.js';

        wp_enqueue_script(
            $handle,
            get_stylesheet_directory_uri() . '/assets/js/recaptcha/recaptcha-forms-v3.js',
            array( 'noyona-recaptcha-v3' ),
            file_exists( $script_path ) ? (string) filemtime( $script_path ) : wp_get_theme()->get( 'Version' ),
            true
        );
    }

    if ( ! $localized ) {
        $localized = true;

        wp_localize_script(
            $handle,
            'noyonaRecaptchaForms',
            array(
                'forms' => array(
                    array(
                        'selector' => 'form.woocommerce-form-login',
                        'action'   => noyona_recaptcha_form_action( 'login' ),
                    ),
                    array(
                        'selector' => 'form.noyona-register-form',
                        'action'   => noyona_recaptcha_form_action( 'register' ),
                    ),
                    array(
                        'selector' => 'form.contact-form__form',
                        'action'   => noyona_recaptcha_form_action( 'contact' ),
                    ),
                    array(
                        'selector' => '.contact-form__form--cf7 .wpcf7-form',
                        'action'   => noyona_recaptcha_form_action( 'contact' ),
                    ),
                ),
            )
        );
    }
}

/**
 * Build the widget markup string for a form (also enqueues the needed assets).
 * Returns '' when reCAPTCHA is disabled for the form.
 *
 * @param string $form          Form slug.
 * @param string $wrapper_class CSS class for the wrapper element.
 * @param array  $attributes    Optional data-* attributes (v2 only, e.g. data-callback).
 * @return string
 */
function noyona_recaptcha_form_widget_html( $form, $wrapper_class = 'noyona-recaptcha', $attributes = array() ) {
    if ( ! noyona_recaptcha_form_enabled( $form ) ) {
        return '';
    }

    if ( ! function_exists( 'noyona_get_recaptcha_widget_markup' ) ) {
        return '';
    }

    noyona_recaptcha_form_enqueue_assets( $form );

    $version = noyona_recaptcha_form_version( $form );

    return (string) noyona_get_recaptcha_widget_markup( $wrapper_class, $version, (array) $attributes );
}

/**
 * Echo the widget markup for a form, safely escaped.
 *
 * @param string $form          Form slug.
 * @param string $wrapper_class CSS class for the wrapper element.
 * @param array  $attributes    Optional data-* attributes (v2 only).
 * @return void
 */
function noyona_recaptcha_form_render_widget( $form, $wrapper_class = 'noyona-recaptcha', $attributes = array() ) {
    $markup = noyona_recaptcha_form_widget_html( $form, $wrapper_class, $attributes );

    if ( '' === trim( $markup ) ) {
        return;
    }

    echo wp_kses( $markup, noyona_recaptcha_form_allowed_html() );
}

/**
 * Verify the reCAPTCHA token from $_POST for a form.
 * Returns true when reCAPTCHA is disabled or verification succeeds; WP_Error otherwise.
 *
 * @param string $form       Form slug.
 * @param string $field_name POST field name.
 * @return true|WP_Error
 */
function noyona_recaptcha_form_verify_post( $form, $field_name = 'g-recaptcha-response' ) {
    if ( ! noyona_recaptcha_form_enabled( $form ) ) {
        return true;
    }

    if ( ! function_exists( 'noyona_verify_recaptcha_from_post' ) ) {
        return true;
    }

    $version = noyona_recaptcha_form_version( $form );
    $action  = ( 'v3' === $version ) ? noyona_recaptcha_form_action( $form ) : '';

    return noyona_verify_recaptcha_from_post( $field_name, $action, $version );
}

/**
 * Verify an already-extracted reCAPTCHA token for a form (e.g. Contact Form 7).
 * Returns true when reCAPTCHA is disabled or verification succeeds; WP_Error otherwise.
 *
 * @param string $form      Form slug.
 * @param string $token     The reCAPTCHA response token.
 * @param string $remote_ip Optional remote IP.
 * @return true|WP_Error
 */
function noyona_recaptcha_form_verify_token( $form, $token, $remote_ip = '' ) {
    if ( ! noyona_recaptcha_form_enabled( $form ) ) {
        return true;
    }

    if ( ! function_exists( 'noyona_verify_recaptcha_token' ) ) {
        return true;
    }

    $version = noyona_recaptcha_form_version( $form );
    $action  = ( 'v3' === $version ) ? noyona_recaptcha_form_action( $form ) : '';

    return noyona_verify_recaptcha_token( $token, $remote_ip, $action, $version );
}

/* ─────────────────────────────────────────────────────────────────────────────
 * Register form — split placement helpers.
 *
 * On the register page the "Sign Up with Google" button and the "Log In" link
 * live OUTSIDE the <form>, but the reCAPTCHA token must be submitted WITH the
 * form. So we render two pieces:
 *   1) a hidden token field INSIDE the form (always), and
 *   2) the visible v2 checkbox OUTSIDE the form (below the Google button),
 *      whose token is copied into the hidden field via its callback.
 * For v3 there is no visible widget, so only the (invisible) hidden field is
 * used and the generic v3 script fills it.
 * ───────────────────────────────────────────────────────────────────────────── */

/**
 * Echo the register reCAPTCHA token field that must sit INSIDE the <form>.
 *
 * @return void
 */
function noyona_recaptcha_register_token_field() {
    if ( ! noyona_recaptcha_form_enabled( 'register' ) ) {
        return;
    }

    if ( 'v2' === noyona_recaptcha_form_version( 'register' ) ) {
        // The visible checkbox is rendered outside the form; keep only the
        // hidden token here so the solved token is submitted with the form.
        noyona_recaptcha_form_enqueue_assets( 'register' );
        echo '<input type="hidden" name="g-recaptcha-response" value="" />';
        return;
    }

    // v3: invisible widget (hidden input) sits inside the form, filled by JS.
    noyona_recaptcha_form_render_widget( 'register', 'noyona-register-recaptcha' );
}

/**
 * Echo the visible register reCAPTCHA widget that sits OUTSIDE the <form>
 * (e.g. below the "Sign Up with Google" button). Only outputs for v2; v3 is
 * invisible and produces nothing here.
 *
 * @param string $wrapper_class CSS class for the wrapper element.
 * @return void
 */
function noyona_recaptcha_register_external_widget( $wrapper_class = 'noyona-register-recaptcha' ) {
    if ( ! noyona_recaptcha_form_enabled( 'register' ) ) {
        return;
    }

    if ( 'v2' !== noyona_recaptcha_form_version( 'register' ) ) {
        return;
    }

    $site_key = function_exists( 'noyona_get_recaptcha_site_key' ) ? noyona_get_recaptcha_site_key( 'v2' ) : '';
    if ( '' === $site_key ) {
        return;
    }

    noyona_recaptcha_form_enqueue_assets( 'register' );

    printf(
        '<div class="%1$s"><div class="g-recaptcha" data-sitekey="%2$s" data-callback="noyonaRegisterRecaptchaOnSuccess" data-expired-callback="noyonaRegisterRecaptchaOnExpire" data-error-callback="noyonaRegisterRecaptchaOnExpire"></div></div>',
        esc_attr( $wrapper_class ),
        esc_attr( $site_key )
    );
    ?>
    <script>
    (function () {
        function setRegisterToken( value ) {
            var form = document.querySelector( 'form.noyona-register-form' );
            if ( ! form ) { return; }
            var input = form.querySelector( 'input[name="g-recaptcha-response"]' );
            if ( ! input ) { return; }
            input.value = value || '';
        }
        window.noyonaRegisterRecaptchaOnSuccess = function ( token ) { setRegisterToken( token ); };
        window.noyonaRegisterRecaptchaOnExpire  = function () { setRegisterToken( '' ); };
    })();
    </script>
    <?php
}
