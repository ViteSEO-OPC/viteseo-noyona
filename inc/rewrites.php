<?php
/**
 * URL rewrite rules, term-link rewrites, rewrite flushes, redirects, and 404 enforcement.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ----- Product category URLs under /shop/{slug}/ ----- */
/**
 * Product category URLs under /shop/{slug}/ to avoid collision with regular pages like /eyes/.
 */
add_action( 'init', 'noyona_register_shop_category_rewrites' );
function noyona_register_shop_category_rewrites() {
    add_rewrite_rule(
        '^shop/([^/]+)/?$',
        'index.php?product_cat=$matches[1]',
        'top'
    );

    add_rewrite_rule(
        '^shop/([^/]+)/page/([0-9]{1,})/?$',
        'index.php?product_cat=$matches[1]&paged=$matches[2]',
        'top'
    );
}

/* ----- Force product_cat permalinks to /shop/{slug}/ ----- */
add_filter( 'term_link', 'noyona_product_cat_term_link_to_shop_base', 10, 3 );
function noyona_product_cat_term_link_to_shop_base( $url, $term, $taxonomy ) {
    if ( 'product_cat' !== $taxonomy || ! $term instanceof WP_Term ) {
        return $url;
    }

    $path = 'shop/' . $term->slug;
    return home_url( user_trailingslashit( $path ) );
}

/* ----- Flush rewrite rules on theme switch ----- */
add_action( 'after_switch_theme', 'noyona_flush_rewrite_rules_on_switch' );
function noyona_flush_rewrite_rules_on_switch() {
    noyona_register_shop_category_rewrites();
    flush_rewrite_rules();
}

/* ----- One-time flush for shop category rewrites ----- */
add_action( 'init', 'noyona_maybe_flush_shop_category_rewrites', 20 );
function noyona_maybe_flush_shop_category_rewrites() {
    $version = get_option( 'noyona_shop_category_rewrite_version', '' );
    if ( '1' === $version ) {
        return;
    }

    noyona_register_shop_category_rewrites();
    flush_rewrite_rules( false );
    update_option( 'noyona_shop_category_rewrite_version', '1', false );
}

/* ----- Redirect old product_cat base to /shop/{slug}/ ----- */
add_action( 'template_redirect', 'noyona_redirect_old_product_category_base', 0 );
function noyona_redirect_old_product_category_base() {
    if ( ! is_tax( 'product_cat' ) ) {
        return;
    }

    $term = get_queried_object();
    if ( ! ( $term instanceof WP_Term ) ) {
        return;
    }

    $request_uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
    $request_path = (string) wp_parse_url( $request_uri, PHP_URL_PATH );
    $trimmed_path = trim( $request_path, '/' );
    if ( 0 === strpos( $trimmed_path, 'shop/' ) ) {
        return;
    }

    $target = home_url( user_trailingslashit( 'shop/' . $term->slug ) );
    wp_safe_redirect( $target, 301 );
    exit;
}

/* ----- Force 404 only on truly unknown routes ----- */
// Force 404 only on true unknown routes (avoid clobbering valid page templates).
add_action( 'template_redirect', 'noyona_force_404_for_unknown_routes', 1 );
function noyona_force_404_for_unknown_routes() {
    if ( is_admin() || wp_doing_ajax() ) {
        return;
    }

    if ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) {
        return;
    }

    if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
        return;
    }

    if ( is_404() ) {
        return;
    }

    // Never interfere with valid resolved requests.
    if (
        is_singular() ||
        is_page() ||
        is_archive() ||
        is_search() ||
        is_tax() ||
        is_category() ||
        is_tag() ||
        is_author() ||
        is_date() ||
        is_post_type_archive()
    ) {
        return;
    }

    global $wp, $wp_query;
    if ( ! isset( $wp ) ) {
        return;
    }

    $request = trim( $wp->request );
    if ( '' === $request ) {
        return;
    }

    // $ignore_prefixes = array(
    //     'wp-admin',
    //     'wp-login.php',
    //     'wp-login',
    //     'wp-cron.php',
    //     'wp-json',
    //     'xmlrpc.php',
    //     'robots.txt',
    //     'favicon.ico',
    //     'sitemap.xml',
    // );

    $ignore_prefixes = array(
        'login',
        'wp-admin',
        'wp-login.php',
        'wp-login',
        'wp-cron.php',
        'wp-json',
        'xmlrpc.php',
        'robots.txt',
        'favicon.ico',
        'wp-sitemap.xml',
        'sitemap.xml',
        'sitemap1.xml',
        'sitemap_index.xml',
        'product-sitemap.xml',
        'page-sitemap.xml',
        'post-sitemap.xml',
        'category-sitemap.xml',
        'product_cat-sitemap.xml',
        'product_tag-sitemap.xml',
    );

    foreach ( $ignore_prefixes as $prefix ) {
        if ( 0 === strpos( $request, $prefix ) ) {
            return;
        }
    }

    if ( is_home() || is_front_page() ) {
        $wp_query->set_404();
        status_header( 404 );
        nocache_headers();
    }
}

