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

        if ( $order->has_status( array( 'cancelled', 'refunded' ) ) ) {
            wp_safe_redirect( wc_get_page_permalink( 'myaccount' ) );
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

    // The standalone /preview/ page is retired — the review step is now an
    // in-page panel on /checkout/, and /preview/ is redirected to /checkout/
    // before this runs (see noyona_redirect_preview_to_checkout). No separate
    // preview-step session check is required here anymore.
}

/* ----- Guard the order-received (thank-you) endpoint ----- */
/**
 * Lock down Woo's `order-received` endpoint.
 *
 * WooCommerce core only validates the order KEY on this endpoint (see
 * WC_Shortcode_Checkout::order_received) — it never checks that the current
 * user actually owns the order. That means:
 *   - A bare /checkout/order-received/ (no order id/key) renders a generic
 *     "Thank you. Your order has been received." success line (HTTP 200).
 *   - Any logged-in account holding a valid order link can view someone
 *     else's confirmation (QR, address, email, items, etc.).
 *
 * This guard converts both cases into a genuine 404:
 *   1. Missing order id, invalid/mismatched key  -> 404.
 *   2. Order owned by a specific user, viewed by a different account -> 404.
 *
 * The real buyer is always logged in as the owner immediately after checkout,
 * so their QRPH / thank-you flow is untouched. Guest/legacy orders (no
 * user_id) keep working via the order key, and shop managers can still view
 * any order for support. This does not touch order-pay or any other endpoint.
 */
add_action( 'template_redirect', 'noyona_guard_order_received_endpoint', 6 );
function noyona_guard_order_received_endpoint() {
    if ( is_admin() || wp_doing_ajax() ) {
        return;
    }

    if ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) {
        return;
    }

    if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
        return;
    }

    if ( ! function_exists( 'is_wc_endpoint_url' ) || ! is_wc_endpoint_url( 'order-received' ) ) {
        return;
    }

    global $wp;
    $order_id  = isset( $wp->query_vars['order-received'] ) ? absint( $wp->query_vars['order-received'] ) : 0;
    $order_key = ( isset( $_GET['key'] ) && function_exists( 'wc_clean' ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        ? wc_clean( wp_unslash( $_GET['key'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        : '';

    $order = ( $order_id > 0 && function_exists( 'wc_get_order' ) ) ? wc_get_order( $order_id ) : null;

    // Case 1: no resolvable order, or a missing/mismatched key.
    if ( ! $order instanceof WC_Order || '' === $order_key || ! hash_equals( $order->get_order_key(), $order_key ) ) {
        noyona_render_404_and_exit();
    }

    // Case 2: ownership. Only enforce when the order actually belongs to a
    // registered user (guest/legacy orders have no owner and stay key-gated).
    $owner_id = (int) $order->get_user_id();
    if ( $owner_id > 0
        && (int) get_current_user_id() !== $owner_id
        && ! current_user_can( 'manage_woocommerce' )
    ) {
        noyona_render_404_and_exit();
    }

    // Case 3: expired/cancelled orders. The received page's cancelled view is a
    // styling dead end, so send the owner to their My Account "Orders" panel
    // with this order's modal pre-opened — they'll see the cancelled status in
    // the proper styled UI. Only redirect the actual owner (the account whose
    // orders panel this URL targets); shop managers viewing another customer's
    // order still see the page in place.
    if ( $owner_id > 0
        && (int) get_current_user_id() === $owner_id
        && $order->has_status( 'cancelled' )
        && function_exists( 'noyona_get_account_order_modal_url' )
    ) {
        $modal_url = noyona_get_account_order_modal_url( $order );
        if ( '' !== $modal_url ) {
            wp_safe_redirect( $modal_url );
            exit;
        }
    }
}

/**
 * Force the current request to render the theme's 404 template and stop.
 */
function noyona_render_404_and_exit() {
    global $wp_query;

    if ( $wp_query instanceof WP_Query ) {
        $wp_query->set_404();
    }

    status_header( 404 );
    nocache_headers();

    $template = get_404_template();
    if ( $template ) {
        include $template;
    }

    exit;
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

