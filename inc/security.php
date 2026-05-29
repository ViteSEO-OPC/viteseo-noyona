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

    $redirect_url = $account_url;
    if ( ( $is_login_route || $is_register_route ) && current_user_can( 'manage_options' ) ) {
        $redirect_url = admin_url();
    }

    wp_safe_redirect( $redirect_url );
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

/* ----- Enforce step order in cart/checkout flow ----- */
/**
 * Prevent logged-in users from jumping ahead in the multi-step checkout flow:
 *   - /checkout/  : requires a non-empty cart.
 *   - /preview/   : requires a non-empty cart AND that the details step was
 *                   actually completed (billing/shipping captured in session).
 *   - /thank-you/ : requires a valid order_id + key pair.
 *
 * Woo's own order-pay / order-received endpoints already verify ownership
 * via order key, so they are left untouched here.
 */
add_action( 'template_redirect', 'noyona_enforce_checkout_step_flow', 5 );
function noyona_enforce_checkout_step_flow() {
    if ( is_admin() || ! is_user_logged_in() || wp_doing_ajax() ) {
        return;
    }

    if ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) {
        return;
    }

    if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
        return;
    }

    // Only guard plain page navigations. POST submissions (place-order, etc.)
    // are handled by WooCommerce itself.
    $method = strtoupper( (string) ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) );
    if ( 'GET' !== $method ) {
        return;
    }

    // Leave Woo's payment/confirmation endpoints alone — they have their own
    // order-key validation.
    if ( function_exists( 'is_wc_endpoint_url' ) && ( is_wc_endpoint_url( 'order-received' ) || is_wc_endpoint_url( 'order-pay' ) ) ) {
        return;
    }

    $request_uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
    $request_path = untrailingslashit( (string) wp_parse_url( $request_uri, PHP_URL_PATH ) );

    $cart_url     = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' );
    $checkout_url = function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' );

    $checkout_path = untrailingslashit( (string) wp_parse_url( $checkout_url, PHP_URL_PATH ) );
    $preview_path  = untrailingslashit( (string) wp_parse_url( home_url( '/preview/' ), PHP_URL_PATH ) );
    $thankyou_path = untrailingslashit( (string) wp_parse_url( home_url( '/thank-you/' ), PHP_URL_PATH ) );

    $on_checkout_page = ( '' !== $checkout_path && $request_path === $checkout_path );
    $on_preview_page  = ( '' !== $preview_path && $request_path === $preview_path );
    $on_thankyou_page = ( '' !== $thankyou_path && ( $request_path === $thankyou_path || 0 === strpos( $request_path, $thankyou_path . '/' ) ) );

    if ( ! $on_checkout_page && ! $on_preview_page && ! $on_thankyou_page ) {
        return;
    }

    // Rule: /thank-you/ is only legitimate when arrived at from a real order.
    // A direct visit (no order_id/key, or mismatched) means the user is
    // jumping past payment — bounce back to cart.
    if ( $on_thankyou_page ) {
        $order_id  = isset( $_GET['order_id'] ) ? absint( wp_unslash( $_GET['order_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $order_key = ( isset( $_GET['key'] ) && function_exists( 'wc_clean' ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            ? wc_clean( wp_unslash( $_GET['key'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            : '';
        $order = ( $order_id > 0 && function_exists( 'wc_get_order' ) ) ? wc_get_order( $order_id ) : null;

        if ( ! $order instanceof WC_Order || $order->get_order_key() !== $order_key ) {
            wp_safe_redirect( $cart_url );
            exit;
        }
        return;
    }

    // Rule: /checkout/ and /preview/ both require a non-empty cart.
    $cart = ( function_exists( 'WC' ) && WC()->cart ) ? WC()->cart : null;
    if ( ! $cart || $cart->is_empty() ) {
        if ( function_exists( 'wc_add_notice' ) ) {
            wc_add_notice(
                __( 'Your cart is empty. Add items before continuing to checkout.', 'noyona' ),
                'notice'
            );
        }
        wp_safe_redirect( $cart_url );
        exit;
    }

    // Rule: /preview/ additionally requires that the details step (/checkout/)
    // has actually been completed. Both contact and shipping must be in the
    // customer session; any missing piece means the user is skipping ahead.
    if ( $on_preview_page ) {
        $customer = ( function_exists( 'WC' ) && WC()->customer ) ? WC()->customer : null;
        if ( ! $customer ) {
            wp_safe_redirect( $checkout_url );
            exit;
        }

        $required_values = array(
            trim( (string) $customer->get_billing_first_name() ),
            trim( (string) $customer->get_billing_last_name() ),
            trim( (string) $customer->get_billing_email() ),
            trim( (string) $customer->get_billing_phone() ),
            trim( (string) $customer->get_shipping_address_1() ),
            trim( (string) $customer->get_shipping_city() ),
            trim( (string) $customer->get_shipping_state() ),
            trim( (string) $customer->get_shipping_postcode() ),
        );

        $billing_email = trim( (string) $customer->get_billing_email() );
        $email_valid   = ( '' !== $billing_email && function_exists( 'is_email' ) && is_email( $billing_email ) );

        $has_blank_required = false;
        foreach ( $required_values as $value ) {
            if ( '' === $value ) {
                $has_blank_required = true;
                break;
            }
        }

        if ( $has_blank_required || ! $email_valid ) {
            // No wc_add_notice here — the client-side validation on /checkout/
            // already renders a woocommerce-error toast listing exactly which
            // field is missing, so a second generic banner would be redundant.
            wp_safe_redirect( $checkout_url );
            exit;
        }
    }
}

/* ----- Block guest access to cart/checkout flow routes ----- */
/**
 * Enforce login on all checkout-flow entry points so guests cannot bypass
 * frontend button/login-modal checks by manually entering URLs.
 */
add_action( 'template_redirect', 'noyona_require_login_for_cart_checkout_flow', 3 );
function noyona_require_login_for_cart_checkout_flow() {
    if ( is_admin() || is_user_logged_in() || wp_doing_ajax() ) {
        return;
    }

    if ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) {
        return;
    }

    if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
        return;
    }

    $request_uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
    $request_path = (string) wp_parse_url( $request_uri, PHP_URL_PATH );
    $request_path = untrailingslashit( $request_path );

    $cart_path     = function_exists( 'wc_get_cart_url' ) ? (string) wp_parse_url( wc_get_cart_url(), PHP_URL_PATH ) : '/cart/';
    $checkout_path = function_exists( 'wc_get_checkout_url' ) ? (string) wp_parse_url( wc_get_checkout_url(), PHP_URL_PATH ) : '/checkout/';
    $preview_path  = (string) wp_parse_url( home_url( '/preview/' ), PHP_URL_PATH );
    $thankyou_path = (string) wp_parse_url( home_url( '/thank-you/' ), PHP_URL_PATH );

    $cart_path     = untrailingslashit( $cart_path );
    $checkout_path = untrailingslashit( $checkout_path );
    $preview_path  = untrailingslashit( $preview_path );
    $thankyou_path = untrailingslashit( $thankyou_path );

    $is_checkout_endpoint = function_exists( 'is_wc_endpoint_url' ) && (
        is_wc_endpoint_url( 'order-pay' )
        || is_wc_endpoint_url( 'order-received' )
    );

    $is_checkout_flow_path = (
        ( '' !== $cart_path && $request_path === $cart_path )
        || ( '' !== $checkout_path && ( $request_path === $checkout_path || 0 === strpos( $request_path, $checkout_path . '/' ) ) )
        || ( '' !== $preview_path && ( $request_path === $preview_path || 0 === strpos( $request_path, $preview_path . '/' ) ) )
        || ( '' !== $thankyou_path && ( $request_path === $thankyou_path || 0 === strpos( $request_path, $thankyou_path . '/' ) ) )
    );

    if ( ! $is_checkout_endpoint && ! $is_checkout_flow_path ) {
        return;
    }

    $redirect_to = home_url( $request_uri );
    $target_url  = add_query_arg( 'redirect_to', rawurlencode( $redirect_to ), noyona_get_login_page_url() );

    wp_safe_redirect( $target_url );
    exit;
}

