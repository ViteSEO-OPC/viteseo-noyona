<?php
/**
 * Frontend asset enqueues (theme styles, scripts, localisations).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ----- Theme styles & scripts (parent + child, fonts, header/footer CSS, header JS, FA) ----- */
// Load parent + child styles and our custom assets
add_action( 'wp_enqueue_scripts', 'woocom_ct_enqueue_assets' );
function woocom_ct_enqueue_assets() {
    // Local font faces (self-hosted Proxima Nova).
    wp_enqueue_style(
        'noyona-fonts-local',
        get_stylesheet_directory_uri() . '/assets/css/fonts.css',
        array(),
        wp_get_theme()->get( 'Version' )
    );

    // Web fonts (only families used by this theme)
    wp_enqueue_style(
        'noyona-fonts',
        'https://fonts.googleapis.com/css2?family=Noto+Serif+SemiCondensed:ital,wght@0,400;0,600;0,700;1,400;1,600&display=swap',
        array(),
        null
    );

    // Parent theme CSS
    wp_enqueue_style(
        'twentytwentyfive-parent-style',
        get_template_directory_uri() . '/style.css'
    );

    // Child theme CSS
    wp_enqueue_style(
        'woocom-ct-style',
        get_stylesheet_directory_uri() . '/style.css',
        array( 'twentytwentyfive-parent-style' ),
        wp_get_theme()->get( 'Version' )
    );

    // Header CSS (assets/css/header.css)
    wp_enqueue_style(
        'woocom-ct-header',
        get_stylesheet_directory_uri() . '/assets/css/header.css',
        array( 'woocom-ct-style' ),
        wp_get_theme()->get( 'Version' )
    );

    // Footer CSS (assets/css/footer.css)
    wp_enqueue_style(
        'woocom-ct-footer',
        get_stylesheet_directory_uri() . '/assets/css/footer.css',
        array( 'woocom-ct-style' ),
        wp_get_theme()->get( 'Version' )
    );

    // Register Product-gatherer assets and load only where needed.
    wp_register_style(
        'woocom-ct-product-gatherer',
        get_stylesheet_directory_uri() . '/assets/css/product-gatherer.css',
        array( 'woocom-ct-style', 'woocom-ct-header' ),
        wp_get_theme()->get( 'Version' )
    );

    // Register Font Awesome for conditional loading.
    wp_register_style(
        'font-awesome-6',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css',
        array(),
        '6.5.2'
    );

    // Register Leaflet assets and load only where needed.
    wp_register_style(
        'leaflet-css',
        'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
        array(),
        '1.9.4'
    );

    wp_register_script(
        'leaflet-js',
        'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
        array(),
        '1.9.4',
        true
    );

    // Header behavior (sticky / color change / wishlist toggle).
    $header_js_path = get_stylesheet_directory() . '/assets/js/header.js';
    wp_enqueue_script(
        'woocom-ct-header',
        get_stylesheet_directory_uri() . '/assets/js/header.js',
        array(),
        file_exists( $header_js_path ) ? (string) filemtime( $header_js_path ) : wp_get_theme()->get( 'Version' ),
        true
    );

    $request_path = (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH );
    $account_path = (string) wp_parse_url(
        function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' ),
        PHP_URL_PATH
    );
    $request_lc = trim( strtolower( untrailingslashit( $request_path ) ), '/' );
    $account_lc = trim( strtolower( untrailingslashit( $account_path ) ), '/' );
    $is_account_frontend = ( function_exists( 'is_account_page' ) && is_account_page() )
        || is_page( 'my-account' )
        || ( '' !== $account_lc && 0 === strpos( $request_lc, $account_lc ) );

    if ( $is_account_frontend ) {
        $account_modals_path = get_stylesheet_directory() . '/assets/js/account-modals.js';
        wp_enqueue_script(
            'noyona-account-modals',
            get_stylesheet_directory_uri() . '/assets/js/account-modals.js',
            array(),
            file_exists( $account_modals_path ) ? (string) filemtime( $account_modals_path ) : wp_get_theme()->get( 'Version' ),
            true
        );
    }

    $logout_url = function_exists( 'wc_logout_url' ) && function_exists( 'wc_get_page_permalink' )
        ? wc_logout_url( wc_get_page_permalink( 'myaccount' ) )
        : wp_logout_url( home_url( '/' ) );

    $shop_price_step          = 50;
    $shop_price_max           = 0;
    $shop_price_category_slug = '';
    if ( is_tax( 'product_cat' ) ) {
        $term = get_queried_object();
        if ( $term instanceof WP_Term ) {
            $shop_price_category_slug = (string) $term->slug;
        }
    } elseif ( is_page() ) {
        $post = get_queried_object();
        if ( $post instanceof WP_Post ) {
            $slug       = strtolower( $post->post_name );
            $page_slugs = noyona_get_shop_category_page_slugs();
            if ( in_array( $slug, $page_slugs, true ) ) {
                $shop_price_category_slug = $slug;
            }
        }
    }

    $is_shop_archive = ( function_exists( 'is_shop' ) && is_shop() )
        || is_tax( 'product_cat' )
        || '' !== $shop_price_category_slug;

    if ( $is_shop_archive && class_exists( 'WooCommerce' ) ) {
        $shop_price_max = noyona_get_max_product_price( $shop_price_category_slug );
    }

    if ( $shop_price_max < 0 ) {
        $shop_price_max = 0;
    }

    $shop_price_ceiling = (int) ( ceil( $shop_price_max / $shop_price_step ) * $shop_price_step );
    if ( $shop_price_ceiling <= 0 ) {
        $shop_price_ceiling = $shop_price_step;
    }

    wp_localize_script(
        'woocom-ct-header',
        'noyonaHeader',
        array(
            'logoutUrl'       => $logout_url,
            'cartUrl'         => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' ),
            'checkoutUrl'     => function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' ),
            'accountUrl'      => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' ),
            'loginUrl'        => wp_login_url(),
            'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
            'themeUri'        => untrailingslashit( get_stylesheet_directory_uri() ),
            'shopPriceFilter' => array(
                'step'     => $shop_price_step,
                'maxPrice' => $shop_price_ceiling,
            ),
        )
    );

    wp_register_script(
        'woocom-ct-product-gatherer',
        get_stylesheet_directory_uri() . '/assets/js/product-gatherer.js',
        array(),
        wp_get_theme()->get( 'Version' ),
        true
    );

    // Most templates render icon-based header/footer controls.
    // Keep Font Awesome off only for dedicated icon-free templates.
    $template_slug = '';
    global $_wp_current_template_id;
    if ( is_string( $_wp_current_template_id ) && false !== strpos( $_wp_current_template_id, '//' ) ) {
        $template_parts = explode( '//', $_wp_current_template_id );
        $template_slug  = (string) end( $template_parts );
    }
    if ( 'test' !== $template_slug ) {
        wp_enqueue_style( 'font-awesome-6' );
    }
}

