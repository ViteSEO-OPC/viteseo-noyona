<?php
/**
 * Security & hardening: wp-login redirects, account-gate enforcement, cache-control headers.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ----- No-cache headers for cart endpoints ----- */
/**
 * Prevent stale cart state from cached AJAX/REST responses.
 */
add_action( 'send_headers', 'noyona_disable_cart_endpoint_cache_headers', 20 );
function noyona_disable_cart_endpoint_cache_headers() {
    if ( is_admin() ) {
        return;
    }

    $wc_ajax = '';
    if ( isset( $_GET['wc-ajax'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $wc_ajax = sanitize_key( wp_unslash( $_GET['wc-ajax'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    }

    if ( 'add_to_cart' === $wc_ajax || 'get_refreshed_fragments' === $wc_ajax ) {
        nocache_headers();
    }
}

/* ----- No-cache headers for Store API responses ----- */
add_filter( 'rest_post_dispatch', 'noyona_disable_store_api_cache_headers', 10, 3 );
function noyona_disable_store_api_cache_headers( $result, $server, $request ) {
    if ( ! $request instanceof WP_REST_Request ) {
        return $result;
    }

    $route = (string) $request->get_route();
    if ( 0 !== strpos( $route, '/wc/store/' ) ) {
        return $result;
    }

    if ( $result instanceof WP_REST_Response ) {
        $headers = $result->get_headers();
        $headers['Cache-Control'] = 'no-store, no-cache, must-revalidate, max-age=0';
        $headers['Pragma']        = 'no-cache';
        $headers['Expires']       = 'Wed, 11 Jan 1984 05:00:00 GMT';
        $result->set_headers( $headers );
    }

    return $result;
}

/* ----- Redirect public wp-login.php traffic to branded auth pages ----- */
/**
 * Redirect public requests away from wp-login.php to branded auth pages.
 * Keep POST requests untouched so existing login submissions still work.
 */
add_action( 'login_init', 'noyona_redirect_wp_login_to_frontend_auth', 1 );
function noyona_redirect_wp_login_to_frontend_auth() {
    if ( is_admin() || wp_doing_ajax() ) {
        return;
    }

    $method = strtoupper( (string) ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) );
    if ( 'GET' !== $method ) {
        return;
    }

    // Keep iframe/modal login flows intact.
    if ( isset( $_GET['interim-login'] ) || isset( $_GET['interim_login'] ) ) {
        return;
    }

    // Allow social login providers (e.g. Nextend) to complete callback on wp-login.php.
    if ( isset( $_REQUEST['loginSocial'] ) ) {
        return;
    }

    $redirect_to = isset( $_REQUEST['redirect_to'] ) ? (string) wp_unslash( $_REQUEST['redirect_to'] ) : '';

    $action = isset( $_REQUEST['action'] ) ? sanitize_key( (string) wp_unslash( $_REQUEST['action'] ) ) : 'login';

    // Preserve core logout and post-password behavior.
    if ( in_array( $action, array( 'logout', 'postpass' ), true ) ) {
        return;
    }

    $target_url = noyona_get_login_page_url();

    if ( in_array( $action, array( 'lostpassword', 'retrievepassword', 'rp', 'resetpass' ), true ) ) {
        $target_url = noyona_get_lost_password_page_url();

        $forward_keys = array( 'checkemail', 'key', 'login', 'id', 'error' );
        foreach ( $forward_keys as $query_key ) {
            if ( ! isset( $_GET[ $query_key ] ) ) {
                continue;
            }

            $raw_value = wp_unslash( $_GET[ $query_key ] );
            if ( ! is_scalar( $raw_value ) ) {
                continue;
            }

            $target_url = add_query_arg(
                $query_key,
                sanitize_text_field( (string) $raw_value ),
                $target_url
            );
        }
    } elseif ( '' !== $redirect_to ) {
        $target_url = add_query_arg(
            'redirect_to',
            $redirect_to,
            $target_url
        );
    }

    wp_safe_redirect( $target_url, 302 );
    exit;
}

/* ----- Redirect logged-in users away from login/register routes ----- */
add_action( 'template_redirect', 'noyona_redirect_logged_in_auth_routes_to_account', 1 );
function noyona_redirect_logged_in_auth_routes_to_account() {
    if ( is_admin() || ! is_user_logged_in() ) {
        return;
    }

    $account_url  = noyona_get_account_page_url();
    $account_path = (string) wp_parse_url( $account_url, PHP_URL_PATH );
    $request_path = (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH );
    $home_path    = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
    $relative     = trim( preg_replace( '#^' . preg_quote( $home_path, '#' ) . '#', '', $request_path ), '/' );
    $relative_lc  = strtolower( (string) $relative );

    $is_login_route    = is_page( 'login' ) || 'login' === $relative_lc;
    $is_register_route = is_page( 'register' ) || 'register' === $relative_lc;
    $is_recovery_route = ( function_exists( 'is_wc_endpoint_url' ) && ( is_wc_endpoint_url( 'lost-password' ) || is_wc_endpoint_url( 'reset-password' ) ) )
        || 0 === strpos( $relative_lc, trim( strtolower( untrailingslashit( (string) $account_path ) ), '/' ) . '/lost-password' )
        || 0 === strpos( $relative_lc, trim( strtolower( untrailingslashit( (string) $account_path ) ), '/' ) . '/reset-password' );

    if ( ! $is_login_route && ! $is_register_route && ! $is_recovery_route ) {
        return;
    }

    if ( untrailingslashit( $request_path ) === untrailingslashit( (string) $account_path ) ) {
        return;
    }

    wp_safe_redirect( $account_url );
    exit;
}

/* ----- Redirect guests requesting /my-account/ to login page ----- */
add_action( 'template_redirect', 'noyona_redirect_guest_account_to_login_page', 2 );
function noyona_redirect_guest_account_to_login_page() {
    if ( is_admin() || is_user_logged_in() ) {
        return;
    }

    // Keep account recovery endpoints working on /my-account/.
    if ( function_exists( 'is_wc_endpoint_url' ) && ( is_wc_endpoint_url( 'lost-password' ) || is_wc_endpoint_url( 'reset-password' ) ) ) {
        return;
    }

    $account_url = noyona_get_account_page_url();

    $current_path = (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH );
    $account_path = (string) wp_parse_url( (string) $account_url, PHP_URL_PATH );

    if ( '' === trim( $account_path ) ) {
        return;
    }

    // Redirect only the base /my-account/ path.
    if ( untrailingslashit( $current_path ) !== untrailingslashit( $account_path ) ) {
        return;
    }

    wp_safe_redirect( noyona_get_login_page_url() );
    exit;
}

