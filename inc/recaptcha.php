<?php
/**
 * Shared Google reCAPTCHA helpers.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ----- reCAPTCHA config readers ----- */
function noyona_get_recaptcha_config_value( $constant_name, $env_name ) {
    $value = '';

    if ( '' !== trim( (string) $constant_name ) && defined( $constant_name ) ) {
        $value = (string) constant( $constant_name );
    }

    if ( '' === trim( $value ) && '' !== trim( (string) $env_name ) ) {
        $env_value = getenv( $env_name );
        if ( false !== $env_value ) {
            $value = (string) $env_value;
        }
    }

    return trim( $value );
}

function noyona_get_recaptcha_site_key( $version = '' ) {
    $version  = noyona_resolve_recaptcha_version( $version );
    $site_key = '';

    if ( 'v2' === $version ) {
        $site_key = noyona_get_recaptcha_config_value( 'NOYONA_RECAPTCHA_V2_SITE_KEY', 'NOYONA_RECAPTCHA_V2_SITE_KEY' );
    } elseif ( 'v3' === $version ) {
        $site_key = noyona_get_recaptcha_config_value( 'NOYONA_RECAPTCHA_V3_SITE_KEY', 'NOYONA_RECAPTCHA_V3_SITE_KEY' );
    }

    if ( '' === $site_key ) {
        $site_key = noyona_get_recaptcha_config_value( 'NOYONA_RECAPTCHA_SITE_KEY', 'NOYONA_RECAPTCHA_SITE_KEY' );
    }

    return trim( (string) apply_filters( 'noyona_recaptcha_site_key', $site_key, $version ) );
}

function noyona_get_recaptcha_secret_key( $version = '' ) {
    $version    = noyona_resolve_recaptcha_version( $version );
    $secret_key = '';

    if ( 'v2' === $version ) {
        $secret_key = noyona_get_recaptcha_config_value( 'NOYONA_RECAPTCHA_V2_SECRET_KEY', 'NOYONA_RECAPTCHA_V2_SECRET_KEY' );
    } elseif ( 'v3' === $version ) {
        $secret_key = noyona_get_recaptcha_config_value( 'NOYONA_RECAPTCHA_V3_SECRET_KEY', 'NOYONA_RECAPTCHA_V3_SECRET_KEY' );
    }

    if ( '' === $secret_key ) {
        $secret_key = noyona_get_recaptcha_config_value( 'NOYONA_RECAPTCHA_SECRET_KEY', 'NOYONA_RECAPTCHA_SECRET_KEY' );
    }

    return trim( (string) apply_filters( 'noyona_recaptcha_secret_key', $secret_key, $version ) );
}

function noyona_get_recaptcha_version() {
    $version = '';

    if ( defined( 'NOYONA_RECAPTCHA_VERSION' ) ) {
        $version = (string) NOYONA_RECAPTCHA_VERSION;
    }

    if ( '' === trim( $version ) ) {
        $env_version = getenv( 'NOYONA_RECAPTCHA_VERSION' );
        if ( false !== $env_version ) {
            $version = (string) $env_version;
        }
    }

    $version = strtolower( trim( $version ) );
    if ( ! in_array( $version, array( 'v2', 'v3' ), true ) ) {
        $version = 'v2';
    }

    return (string) apply_filters( 'noyona_recaptcha_version', $version );
}

function noyona_resolve_recaptcha_version( $version = '' ) {
    $version = strtolower( trim( (string) $version ) );
    if ( in_array( $version, array( 'v2', 'v3' ), true ) ) {
        return $version;
    }

    return noyona_get_recaptcha_version();
}

function noyona_get_recaptcha_score_threshold() {
    $threshold = 0.5;

    if ( defined( 'NOYONA_RECAPTCHA_SCORE_THRESHOLD' ) ) {
        $threshold = (float) NOYONA_RECAPTCHA_SCORE_THRESHOLD;
    } else {
        $env_threshold = getenv( 'NOYONA_RECAPTCHA_SCORE_THRESHOLD' );
        if ( false !== $env_threshold && '' !== trim( (string) $env_threshold ) ) {
            $threshold = (float) $env_threshold;
        }
    }

    if ( $threshold < 0 ) {
        $threshold = 0;
    } elseif ( $threshold > 1 ) {
        $threshold = 1;
    }

    return (float) apply_filters( 'noyona_recaptcha_score_threshold', $threshold );
}

function noyona_is_recaptcha_enabled( $version = '' ) {
    $version = noyona_resolve_recaptcha_version( $version );
    return '' !== noyona_get_recaptcha_site_key( $version ) && '' !== noyona_get_recaptcha_secret_key( $version );
}

/* ----- reCAPTCHA frontend assets + widget ----- */
function noyona_enqueue_recaptcha_script( $version = '' ) {
    static $enqueued_versions = array();

    $version = noyona_resolve_recaptcha_version( $version );
    if ( ! noyona_is_recaptcha_enabled( $version ) ) {
        return false;
    }

    if ( isset( $enqueued_versions[ $version ] ) ) {
        return false;
    }
    $enqueued_versions[ $version ] = true;

    $site_key      = noyona_get_recaptcha_site_key( $version );
    $api_url       = 'https://www.recaptcha.net/recaptcha/api.js';
    $script_handle = 'noyona-google-recaptcha-' . $version;

    if ( 'v3' === $version ) {
        $api_url = add_query_arg( 'render', $site_key, $api_url );
    }

    wp_register_script(
        $script_handle,
        $api_url,
        array(),
        null,
        true
    );
    wp_script_add_data( $script_handle, 'strategy', 'defer' );
    wp_enqueue_script( $script_handle );

    if ( 'v3' === $version ) {
        $script_path = get_stylesheet_directory() . '/assets/js/recaptcha-v3.js';
        wp_enqueue_script(
            'noyona-recaptcha-v3',
            get_stylesheet_directory_uri() . '/assets/js/recaptcha-v3.js',
            array( $script_handle ),
            file_exists( $script_path ) ? (string) filemtime( $script_path ) : wp_get_theme()->get( 'Version' ),
            true
        );
        wp_localize_script(
            'noyona-recaptcha-v3',
            'noyonaRecaptchaV3',
            array(
                'siteKey' => $site_key,
            )
        );
    }

    return true;
}

function noyona_get_recaptcha_widget_markup( $wrapper_class = 'noyona-recaptcha', $version = '', $widget_attributes = array() ) {
    $version = noyona_resolve_recaptcha_version( $version );
    $site_key = noyona_get_recaptcha_site_key( $version );
    if ( '' === $site_key ) {
        return '';
    }

    $wrapper_class = trim( (string) $wrapper_class );
    if ( '' === $wrapper_class ) {
        $wrapper_class = 'noyona-recaptcha';
    }

    if ( 'v3' === $version ) {
        return sprintf(
            '<div class="%1$s"><input type="hidden" name="g-recaptcha-response" value="" /></div>',
            esc_attr( $wrapper_class )
        );
    }

    $attributes = array(
        'data-sitekey' => $site_key,
    );

    if ( is_array( $widget_attributes ) ) {
        foreach ( $widget_attributes as $attr_name => $attr_value ) {
            $attr_name = strtolower( trim( (string) $attr_name ) );
            if ( '' === $attr_name || 0 !== strpos( $attr_name, 'data-' ) ) {
                continue;
            }

            $attributes[ $attr_name ] = trim( (string) $attr_value );
        }
    }

    $attributes_html = '';
    foreach ( $attributes as $attr_name => $attr_value ) {
        if ( '' === trim( (string) $attr_value ) ) {
            continue;
        }
        $attributes_html .= sprintf( ' %1$s="%2$s"', esc_attr( $attr_name ), esc_attr( $attr_value ) );
    }

    return sprintf(
        '<div class="%1$s"><div class="g-recaptcha"%2$s></div></div>',
        esc_attr( $wrapper_class ),
        $attributes_html
    );
}

/* ----- reCAPTCHA backend verification ----- */
function noyona_verify_recaptcha_token( $response_token, $remote_ip = '', $expected_action = '', $version = '' ) {
    $version        = noyona_resolve_recaptcha_version( $version );
    $secret_key     = noyona_get_recaptcha_secret_key( $version );
    $response_token = trim( (string) $response_token );
    $remote_ip      = trim( (string) $remote_ip );
    $expected_action = sanitize_key( (string) $expected_action );

    if ( '' === $secret_key ) {
        return new WP_Error( 'recaptcha_not_configured', 'Captcha is not configured.' );
    }

    if ( '' === $response_token ) {
        return new WP_Error( 'recaptcha_missing', 'Captcha response is required.' );
    }

    $body = array(
        'secret'   => $secret_key,
        'response' => $response_token,
    );

    if ( '' !== $remote_ip ) {
        $body['remoteip'] = $remote_ip;
    }

    $verify_response = wp_remote_post(
        'https://www.recaptcha.net/recaptcha/api/siteverify',
        array(
            'timeout' => 10,
            'body'    => $body,
        )
    );

    if ( is_wp_error( $verify_response ) ) {
        return new WP_Error(
            'recaptcha_request_failed',
            'Captcha verification request failed.',
            $verify_response
        );
    }

    $status_code = (int) wp_remote_retrieve_response_code( $verify_response );
    if ( $status_code < 200 || $status_code >= 300 ) {
        return new WP_Error( 'recaptcha_http_error', 'Captcha verification service is unavailable.' );
    }

    $payload = json_decode( (string) wp_remote_retrieve_body( $verify_response ), true );
    if ( ! is_array( $payload ) ) {
        return new WP_Error( 'recaptcha_invalid_response', 'Captcha verification response was invalid.' );
    }

    if ( empty( $payload['success'] ) ) {
        $error_codes = '';
        if ( isset( $payload['error-codes'] ) && is_array( $payload['error-codes'] ) ) {
            $error_codes = implode( ',', array_map( 'sanitize_key', $payload['error-codes'] ) );
        }

        return new WP_Error(
            'recaptcha_failed',
            'Captcha verification failed.',
            array( 'error_codes' => $error_codes )
        );
    }

    if ( 'v3' === $version ) {
        $response_action = isset( $payload['action'] ) ? sanitize_key( (string) $payload['action'] ) : '';
        $score           = isset( $payload['score'] ) ? (float) $payload['score'] : 0;
        $threshold       = noyona_get_recaptcha_score_threshold();

        if ( '' !== $expected_action && $expected_action !== $response_action ) {
            return new WP_Error( 'recaptcha_action_mismatch', 'Captcha action mismatch.' );
        }

        if ( $score < $threshold ) {
            return new WP_Error( 'recaptcha_low_score', 'Captcha score too low.', array( 'score' => $score ) );
        }
    }

    return true;
}

function noyona_verify_recaptcha_from_post( $field_name = 'g-recaptcha-response', $expected_action = '', $version = '' ) {
    $field_name = is_string( $field_name ) ? $field_name : 'g-recaptcha-response';
    $token      = isset( $_POST[ $field_name ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field_name ] ) ) : '';
    $remote_ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

    return noyona_verify_recaptcha_token( $token, $remote_ip, $expected_action, $version );
}
