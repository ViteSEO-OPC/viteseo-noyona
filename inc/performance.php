<?php
/**
 * Performance: resource hints, preloads, script-loader fixes, asset trimming.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ----- Hero image preload (home) ----- */
add_filter( 'wp_preload_resources', 'noyona_preload_home_hero_image' );
function noyona_preload_home_hero_image( $preload_resources ) {
    if ( ! is_front_page() ) {
        return $preload_resources;
    }

    $base = get_stylesheet_directory_uri() . '/assets/images';

    // Responsive preload: the browser picks the variant matching the viewport
    // from imagesrcset, so a phone fetches the 18 KB mobile webp instead of
    // the 75 KB desktop one. The href is the desktop file as a fallback for
    // user agents that don't honor imagesrcset.
    //
    // Note: do NOT preload the body font here. The local font already declares
    // `font-display: swap` so it never blocks paint, and a 184 KB TTF preload
    // competes with the LCP image on a constrained mobile connection — the
    // exact regression we ran into. Font preload only makes sense once the
    // font is converted to WOFF2 (~50 KB) and confirmed to be needed for FCP.
    $preload_resources[] = array(
        'href'          => $base . '/hp-desktop-1920x1080px.webp',
        'as'            => 'image',
        'fetchpriority' => 'high',
        'imagesrcset'   => $base . '/hp-mobile-375x812px.webp 375w, '
                         . $base . '/hp-tablet-768x1024px.webp 768w, '
                         . $base . '/hp-laptop-1280x720px.webp 1280w, '
                         . $base . '/hp-desktop-1920x1080px.webp 1920w',
        'imagesizes'    => '100vw',
    );

    return $preload_resources;
}

/* ----- Preconnect resource hints (fonts, CDN) ----- */
add_filter( 'wp_resource_hints', 'noyona_add_preconnect_hints', 10, 2 );
function noyona_add_preconnect_hints( $hints, $relation_type ) {
    if ( 'preconnect' !== $relation_type ) {
        return $hints;
    }

    $hints[] = 'https://fonts.googleapis.com';
    $hints[] = 'https://fonts.gstatic.com';
    $hints[] = 'https://cdnjs.cloudflare.com';

    return array_values( array_unique( $hints ) );
}

/* ----- Wordfence ajaxWatcher jQuery dependency fix ----- */
// Wordfence's `wordfenceAJAXjs` script (admin.ajaxWatcher.*.js) is loaded
// on logged-in frontend pages as a BLOCKING script and uses jQuery without
// declaring it as a dependency. Production has jQuery registered with
// `strategy=defer`, so jQuery executes after DOM parse — but Wordfence's
// script (blocking) runs synchronously at parse time, before deferred
// jQuery has executed. Result: `Uncaught ReferenceError: jQuery is not
// defined`.
//
// Tag order alone is not enough to fix this: even with `jquery` added to
// Wordfence's deps, WP prints jquery's <script> tag first but jquery still
// runs deferred while Wordfence runs immediately. So we ALSO set
// `strategy=defer` on Wordfence's handle, which:
//   - causes WP to emit a real `defer` attribute on Wordfence's <script>;
//   - makes Wordfence execute alongside deferred jQuery, after DOM parse;
//   - preserves Wordfence's inline `wordfenceAJAXjs-js-extra` (the
//     `WFAJAXWatcherVars` localized object) — `-extra` inlines execute
//     synchronously when the parser hits them, regardless of the main
//     script's strategy, so the nonce is set before the deferred main
//     script reads it.
// We hook at print time (not just wp_enqueue_scripts) because Wordfence
// may register the handle from later hooks. The function is idempotent
// (in_array check on deps; idempotent assignment on strategy). We do NOT
// touch Wordfence plugin files and we do NOT alter any other handle.
add_action( 'wp_print_scripts', 'noyona_fix_wordfence_ajaxwatcher_jquery_dep', 0 );
add_action( 'wp_print_footer_scripts', 'noyona_fix_wordfence_ajaxwatcher_jquery_dep', 0 );
function noyona_fix_wordfence_ajaxwatcher_jquery_dep() {
    if ( is_admin() ) {
        return;
    }

    $scripts = wp_scripts();
    if ( ! $scripts instanceof WP_Scripts ) {
        return;
    }

    if ( ! isset( $scripts->registered['wordfenceAJAXjs'] ) ) {
        return;
    }

    $script = $scripts->registered['wordfenceAJAXjs'];
    if ( ! is_array( $script->deps ) ) {
        $script->deps = array();
    }

    if ( ! in_array( 'jquery', $script->deps, true ) ) {
        $script->deps[] = 'jquery';
    }

    // Force defer so execution order matches deferred jQuery.
    if ( ! is_array( $script->extra ) ) {
        $script->extra = array();
    }
    $script->extra['strategy'] = 'defer';
}

/* ----- Restore defer attribute on woocommerce-js ----- */
// Google Site Kit's `googlesitekit-events-provider-woocommerce` handle
// attaches an inline `-before` script that sets up window._googlesitekit
// .wcdata. WP 6.3+ `WP_Scripts::is_delayable_node()` then treats Site Kit's
// handle as not-defer-eligible, and since Site Kit depends on `woocommerce`,
// the eligibility check propagates upward and downgrades `woocommerce-js`
// from defer to blocking. jQuery keeps real defer (its other dependents are
// all delayable). woocommerce.min.js then executes synchronously at parse
// time, before deferred jQuery runs -> `Uncaught ReferenceError: jQuery is
// not defined`. We restore the real `defer` attribute on the `woocommerce`
// handle's tag only. Site Kit's flow, jQuery's strategy, and every other
// handle are untouched.
add_filter( 'script_loader_tag', 'noyona_restore_defer_on_woocommerce_js', 99, 3 );
function noyona_restore_defer_on_woocommerce_js( $tag, $handle, $src ) {
    if ( 'woocommerce' !== $handle ) {
        return $tag;
    }
    if ( false !== strpos( $tag, ' defer' ) ) {
        return $tag;
    }
    return preg_replace( '/<script(\s+)(?=src=)/', '<script$1defer ', $tag, 1 );
}

/* ----- Trim non-commerce assets (noop guard, retained intent) ----- */
add_action( 'wp_enqueue_scripts', 'noyona_trim_noncommerce_assets', 100 );
function noyona_trim_noncommerce_assets() {
    if ( is_admin() ) {
        return;
    }

    // The WooCommerce mini-cart block lives in parts/header.html, so it is
    // present on every frontend page. has_block( 'woocommerce/mini-cart' )
    // does NOT reliably detect blocks inside block-theme template parts, so
    // the previous dequeue path could strip wc-blocks-style and friends on
    // most pages — causing the header cart badge to fail to hydrate and
    // disappear after normal navigation. Until the header part is rebuilt
    // without the block, the only safe behavior on the frontend is to keep
    // every Woo / WC Blocks asset enqueued.
    return;
}

