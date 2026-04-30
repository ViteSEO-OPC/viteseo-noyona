<?php
$noyona_pdp_inc = get_stylesheet_directory() . '/inc/woocommerce-pdp.php';
if ( is_readable( $noyona_pdp_inc ) ) {
	require_once $noyona_pdp_inc;
}

$noyona_cart_inc = get_stylesheet_directory() . '/inc/woocommerce-cart.php';
if ( is_readable( $noyona_cart_inc ) ) {
	require_once $noyona_cart_inc;
}

$noyona_checkout_inc = get_stylesheet_directory() . '/inc/woocommerce-checkout.php';
if ( is_readable( $noyona_checkout_inc ) ) {
	require_once $noyona_checkout_inc;
}

$noyona_shipping_inc = get_stylesheet_directory() . '/inc/woocommerce-shipping.php';
if ( is_readable( $noyona_shipping_inc ) ) {
	require_once $noyona_shipping_inc;
}

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

add_filter( 'get_the_archive_title', 'noyona_replace_shop_archive_title' );
function noyona_replace_shop_archive_title( $title ) {
	if ( ! function_exists( 'is_shop' ) || ! is_shop() ) {
		return $title;
	}

	$normalized_title = trim( wp_strip_all_tags( (string) $title ) );
	if ( 'Shop' === $normalized_title ) {
		return 'All Products';
	}

	return $title;
}

/**
 * Render a Media-Library-aware <img>. Resolves attachment ID from a URL when
 * needed so we get width/height + srcset for free; falls back to a plain <img>
 * with explicit width/height when the URL lives outside the library.
 *
 * @param array $args {
 *     @type int|string  $id       Attachment ID. Optional.
 *     @type string      $url      Image URL. Optional if $id given.
 *     @type string      $size     Registered image size. Default 'large'.
 *     @type string      $alt      Alt text.
 *     @type string      $class    CSS class for the <img>.
 *     @type string      $sizes    sizes="" attribute when using attachment image.
 *     @type string      $loading  'lazy' (default) or 'eager'.
 *     @type string      $decoding 'async' (default) or 'sync'.
 *     @type int         $width    Fallback intrinsic width (for non-library URLs).
 *     @type int         $height   Fallback intrinsic height.
 *     @type string      $fetchpriority Optional fetchpriority hint.
 * }
 */
function noyona_render_image( $args = array() ) {
    $args = wp_parse_args(
        $args,
        array(
            'id'            => 0,
            'url'           => '',
            'size'          => 'large',
            'alt'           => '',
            'class'         => '',
            'sizes'         => '',
            'loading'       => 'lazy',
            'decoding'      => 'async',
            'width'         => 0,
            'height'        => 0,
            'fetchpriority' => '',
        )
    );

    $id = (int) $args['id'];
    if ( ! $id && ! empty( $args['url'] ) ) {
        // attachment_url_to_postid is cached internally by WP, so this is cheap on repeat hits.
        $id = (int) attachment_url_to_postid( $args['url'] );
    }

    if ( $id ) {
        $attrs = array(
            'alt'      => $args['alt'],
            'decoding' => $args['decoding'],
        );
        if ( '' !== $args['class'] ) {
            $attrs['class'] = $args['class'];
        }
        if ( '' !== $args['sizes'] ) {
            $attrs['sizes'] = $args['sizes'];
        }
        if ( '' !== $args['loading'] ) {
            $attrs['loading'] = $args['loading'];
        }
        if ( '' !== $args['fetchpriority'] ) {
            $attrs['fetchpriority'] = $args['fetchpriority'];
        }
        return wp_get_attachment_image( $id, $args['size'], false, $attrs );
    }

    // URL-only fallback: emit a plain <img> with whatever intrinsic dims we know.
    if ( empty( $args['url'] ) ) {
        return '';
    }

    $w = (int) $args['width'];
    $h = (int) $args['height'];
    $html  = '<img src="' . esc_url( $args['url'] ) . '"';
    $html .= ' alt="' . esc_attr( $args['alt'] ) . '"';
    if ( '' !== $args['class'] ) {
        $html .= ' class="' . esc_attr( $args['class'] ) . '"';
    }
    if ( $w > 0 ) {
        $html .= ' width="' . $w . '"';
    }
    if ( $h > 0 ) {
        $html .= ' height="' . $h . '"';
    }
    if ( '' !== $args['loading'] ) {
        $html .= ' loading="' . esc_attr( $args['loading'] ) . '"';
    }
    if ( '' !== $args['decoding'] ) {
        $html .= ' decoding="' . esc_attr( $args['decoding'] ) . '"';
    }
    if ( '' !== $args['fetchpriority'] ) {
        $html .= ' fetchpriority="' . esc_attr( $args['fetchpriority'] ) . '"';
    }
    if ( '' !== $args['sizes'] ) {
        $html .= ' sizes="' . esc_attr( $args['sizes'] ) . '"';
    }
    $html .= ' />';
    return $html;
}

/**
 * Mark the theme's footer scripts as defer so they don't block parsing.
 * They're already in the footer with `in_footer=true`, but defer also lets
 * the browser keep parsing while the script downloads in parallel.
 */
add_filter( 'script_loader_tag', 'noyona_defer_theme_scripts', 10, 3 );
function noyona_defer_theme_scripts( $tag, $handle, $src ) {
    if ( is_admin() ) {
        return $tag;
    }
    $defer_handles = array(
        'woocom-ct-header',
        'noyona-account-modals',
        'woocom-ct-product-gatherer',
        'leaflet-js',
    );
    if ( ! in_array( $handle, $defer_handles, true ) ) {
        return $tag;
    }
    if ( false !== strpos( $tag, ' defer' ) || false !== strpos( $tag, ' async' ) ) {
        return $tag;
    }
    return preg_replace( '/<script /', '<script defer ', $tag, 1 );
}

add_filter( 'wp_preload_resources', 'noyona_preload_home_hero_image' );
function noyona_preload_home_hero_image( $preload_resources ) {
    $theme_uri = get_stylesheet_directory_uri();

    // Preload the body-text font so first paint isn't blocked on a CSS-discovered
    // font fetch. crossorigin is required for fonts even on the same origin.
    $preload_resources[] = array(
        'href'        => $theme_uri . '/assets/fonts/proximanova_regular.ttf',
        'as'          => 'font',
        'type'        => 'font/ttf',
        'crossorigin' => 'anonymous',
    );

    if ( ! is_front_page() ) {
        return $preload_resources;
    }

    $base = $theme_uri . '/assets/images';

    // Responsive preload: the browser picks the variant matching the viewport
    // from imagesrcset, so a phone fetches the 18 KB mobile webp instead of
    // the 75 KB desktop one. The href is the desktop file as a fallback for
    // user agents that don't honor imagesrcset.
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

add_action( 'wp_enqueue_scripts', 'noyona_trim_noncommerce_assets', 100 );
function noyona_trim_noncommerce_assets() {
    if ( is_admin() ) {
        return;
    }

    // Keep Woo assets when header mini-cart is present on a page.
    if ( has_block( 'woocommerce/mini-cart' ) ) {
        return;
    }

    if (
        is_cart()
        || is_checkout()
        || is_account_page()
        || is_woocommerce()
        || ( function_exists( 'noyona_is_checkout_ui_context' ) && noyona_is_checkout_ui_context() )
    ) {
        return;
    }

    wp_dequeue_style( 'woocommerce-layout' );
    wp_dequeue_style( 'woocommerce-smallscreen' );
    wp_dequeue_style( 'woocommerce-general' );
    wp_dequeue_style( 'woocommerce-blocktheme' );
    wp_dequeue_style( 'wc-blocks-style' );
    wp_dequeue_style( 'wc-blocks-vendors-style' );
    wp_dequeue_style( 'wc-blocks-packages-style' );

    // Keep these to avoid breaking header mini-cart drawer behavior.
    // wp_dequeue_script( 'woocommerce' );
    // wp_dequeue_script( 'wc-cart-fragments' );
    wp_dequeue_script( 'wc-add-to-cart' );
    wp_dequeue_script( 'jquery-blockui' );
    wp_dequeue_script( 'jquery-cookie' );
    wp_dequeue_script( 'js-cookie' );
    wp_dequeue_script( 'wc-order-attribution' );
    wp_dequeue_script( 'sourcebuster-js' );
}

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



// Make sure theme declares WooCommerce support
add_action( 'after_setup_theme', function() {
    add_theme_support( 'woocommerce' );
} );

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


add_filter( 'term_link', 'noyona_product_cat_term_link_to_shop_base', 10, 3 );
function noyona_product_cat_term_link_to_shop_base( $url, $term, $taxonomy ) {
    if ( 'product_cat' !== $taxonomy || ! $term instanceof WP_Term ) {
        return $url;
    }

    $path = 'shop/' . $term->slug;
    return home_url( user_trailingslashit( $path ) );
}

add_action( 'after_switch_theme', 'noyona_flush_rewrite_rules_on_switch' );
function noyona_flush_rewrite_rules_on_switch() {
    noyona_register_shop_category_rewrites();
    flush_rewrite_rules();
}

/**
 * One-time safeguard:
 * Remove stale DB-saved block-theme header template parts so the theme file
 * parts/header.html is used consistently (prevents production/local mismatch).
 */
add_action( 'init', 'noyona_one_time_reset_header_template_part_override', 30 );
function noyona_one_time_reset_header_template_part_override() {
    $safeguard_version = '2';
    $option_key        = 'noyona_header_template_part_safeguard_version';
    if ( $safeguard_version === get_option( $option_key, '' ) ) {
        return;
    }

    if ( ! post_type_exists( 'wp_template_part' ) ) {
        update_option( $option_key, $safeguard_version, false );
        return;
    }

    $theme_terms = array_values(
        array_unique(
            array_filter(
                array(
                    get_stylesheet(),
                    get_template(),
                )
            )
        )
    );

    $query_args = array(
        'post_type'      => 'wp_template_part',
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'name'           => 'header',
    );

    if ( ! empty( $theme_terms ) ) {
        $query_args['tax_query'] = array(
            array(
                'taxonomy' => 'wp_theme',
                'field'    => 'name',
                'terms'    => $theme_terms,
            ),
        );
    }

    $header_template_part_ids = get_posts( $query_args );
    if ( ! empty( $header_template_part_ids ) ) {
        foreach ( $header_template_part_ids as $template_part_id ) {
            wp_delete_post( (int) $template_part_id, true );
        }
    }

    update_option( $option_key, $safeguard_version, false );
}

/**
 * One-time safeguard:
 * Remove stale DB-saved order confirmation templates so the theme file
 * templates/order-confirmation.html is used consistently.
 */
add_action( 'init', 'noyona_one_time_reset_order_confirmation_template_override', 31 );
function noyona_one_time_reset_order_confirmation_template_override() {
    $safeguard_version = '1';
    $option_key        = 'noyona_order_confirmation_template_safeguard_version';
    if ( $safeguard_version === get_option( $option_key, '' ) ) {
        return;
    }

    if ( ! post_type_exists( 'wp_template' ) ) {
        update_option( $option_key, $safeguard_version, false );
        return;
    }

    $theme_terms = array_values(
        array_unique(
            array_filter(
                array(
                    get_stylesheet(),
                    get_template(),
                )
            )
        )
    );

    $query_args = array(
        'post_type'      => 'wp_template',
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'name'           => 'order-confirmation',
    );

    if ( ! empty( $theme_terms ) ) {
        $query_args['tax_query'] = array(
            array(
                'taxonomy' => 'wp_theme',
                'field'    => 'name',
                'terms'    => $theme_terms,
            ),
        );
    }

    $template_ids = get_posts( $query_args );
    if ( ! empty( $template_ids ) ) {
        foreach ( $template_ids as $template_id ) {
            wp_delete_post( (int) $template_id, true );
        }
    }

    update_option( $option_key, $safeguard_version, false );
}

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

function noyona_is_login_page_context() {
    if ( function_exists( 'is_account_page' ) && is_account_page() ) {
        return true;
    }

    if ( is_page( 'login' ) ) {
        return true;
    }

    $request_path = (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH );
    $home_path    = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
    $relative     = trim( preg_replace( '#^' . preg_quote( $home_path, '#' ) . '#', '', $request_path ), '/' );

    return 'login' === strtolower( (string) $relative );
}

function noyona_get_login_page_url() {
    $page = get_page_by_path( 'login' );
    if ( $page instanceof WP_Post && 'publish' === $page->post_status ) {
        return get_permalink( $page );
    }

    return home_url( '/login/' );
}

function noyona_get_account_page_url() {
    $account_url = function_exists( 'wc_get_page_permalink' )
        ? wc_get_page_permalink( 'myaccount' )
        : home_url( '/my-account/' );

    if ( '' === trim( (string) $account_url ) ) {
        $account_url = home_url( '/my-account/' );
    }

    return (string) $account_url;
}

/**
 * Frontend lost-password URL (Woo endpoint when available).
 *
 * @return string
 */
function noyona_get_lost_password_page_url() {
    $lost_url = function_exists( 'wc_lostpassword_url' )
        ? wc_lostpassword_url()
        : home_url( '/my-account/lost-password/' );

    if ( '' === trim( (string) $lost_url ) ) {
        $lost_url = home_url( '/my-account/lost-password/' );
    }

    return (string) $lost_url;
}

/**
 * Resolve Google social login URL (Nextend) with fallback.
 *
 * @param string $redirect_to Optional post-login redirect URL.
 * @return string
 */
function noyona_get_google_login_url( $redirect_to = '' ) {
    $redirect_to = is_string( $redirect_to ) ? trim( $redirect_to ) : '';
    $fallback    = wp_login_url( $redirect_to );

    if ( class_exists( 'NextendSocialLogin' ) ) {
        $fallback = add_query_arg( 'loginSocial', 'google', site_url( 'wp-login.php' ) );
        if ( '' !== $redirect_to ) {
            $fallback = add_query_arg( 'redirect', $redirect_to, $fallback );
        }
    }

    if ( ! class_exists( 'NextendSocialLogin' ) || ! method_exists( 'NextendSocialLogin', 'getProviderByProviderID' ) ) {
        return $fallback;
    }

    $provider = NextendSocialLogin::getProviderByProviderID( 'google' );
    if ( ! $provider || ! method_exists( $provider, 'getLoginUrl' ) ) {
        return $fallback;
    }

    $google_url = (string) $provider->getLoginUrl();
    if ( '' !== $redirect_to ) {
        $google_url = add_query_arg( 'redirect', $redirect_to, $google_url );
    }

    return $google_url;
}

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

add_filter( 'woocommerce_login_redirect', 'noyona_force_woo_login_redirect_to_account', 20, 2 );
function noyona_force_woo_login_redirect_to_account( $redirect, $user ) {
    if ( is_wp_error( $user ) ) {
        return $redirect;
    }

    return noyona_get_account_page_url();
}

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

add_action( 'template_redirect', 'noyona_render_virtual_login_page', 3 );
function noyona_render_virtual_login_page() {
    if ( is_admin() || is_user_logged_in() || is_page( 'login' ) ) {
        return;
    }

    // Only virtual-render the dedicated /login/ route; never hijack /my-account/ endpoints.
    $request_path = (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH );
    $home_path    = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
    $relative     = trim( preg_replace( '#^' . preg_quote( $home_path, '#' ) . '#', '', $request_path ), '/' );

    if ( 'login' !== strtolower( (string) $relative ) ) {
        return;
    }

    // Render login page markup even when a dedicated WP page with slug "login" does not exist.
    status_header( 200 );
    nocache_headers();

    echo do_blocks(
        '<!-- wp:template-part {"slug":"header"} /-->' .
        '<!-- wp:group {"tagName":"main","style":{"spacing":{"margin":{"top":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->' .
        '<main class="wp-block-group" style="margin-top:var(--wp--preset--spacing--60)">' .
        '<!-- wp:group {"style":{"spacing":{"padding":{"bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->' .
        '<div class="wp-block-group" style="padding-bottom:var(--wp--preset--spacing--60)">' .
        '<!-- wp:shortcode -->[woocommerce_my_account]<!-- /wp:shortcode -->' .
        '</div>' .
        '<!-- /wp:group -->' .
        '</main>' .
        '<!-- /wp:group -->' .
        '<!-- wp:template-part {"slug":"footer"} /-->'
    );
    exit;
}

add_filter( 'body_class', 'noyona_add_login_page_account_body_class' );
function noyona_add_login_page_account_body_class( $classes ) {
    if ( noyona_is_login_page_context() && ! in_array( 'woocommerce-account', $classes, true ) ) {
        $classes[] = 'woocommerce-account';
    }

    return $classes;
}

add_filter( 'the_content', 'noyona_render_login_page_content', 25 );
function noyona_render_login_page_content( $content ) {
    if ( is_admin() || ! is_page( 'login' ) || ! in_the_loop() || ! is_main_query() ) {
        return $content;
    }

    return do_shortcode( '[woocommerce_my_account]' );
}

add_action( 'woocommerce_login_form_start', 'noyona_account_login_form_intro' );
function noyona_account_login_form_intro() {
    if ( is_user_logged_in() || ! noyona_is_login_page_context() ) {
        return;
    }

    $logo_url = trailingslashit( get_stylesheet_directory_uri() ) . 'assets/images/noyona-mobile-logo.webp';
    $logo_markup = '<img src="' . esc_url( $logo_url ) . '" alt="' . esc_attr__( 'Noyona logo', 'noyona-childtheme' ) . '" loading="lazy" decoding="async" />';

    echo '<div class="noyona-account-login-brand-wrap">';
    echo '<span class="noyona-account-login-brand">' . wp_kses_post( $logo_markup ) . '</span>';
    echo '</div>';
    echo '<h3 class="noyona-account-login-title">' . esc_html__( 'Login', 'noyona-childtheme' ) . '</h3>';
    echo '<p class="noyona-account-login-intro">' . esc_html__( 'Enter your Email Address and Password to access your account', 'noyona-childtheme' ) . '</p>';
}

add_action( 'woocommerce_login_form_end', 'woocom_ct_add_register_link_to_login' );
function woocom_ct_add_register_link_to_login() {
    if ( ! noyona_is_login_page_context() ) {
        return;
    }

    $account_url = function_exists( 'wc_get_page_permalink' )
        ? wc_get_page_permalink( 'myaccount' )
        : home_url( '/my-account/' );
    $google_login_url = noyona_get_google_login_url( $account_url );
    $register_url = home_url( '/register/' );

    echo '<div class="noyona-login-form-footer">';
    echo '<a class="noyona-login-google-btn" href="' . esc_url( $google_login_url ) . '"><i class="fa-brands fa-google" aria-hidden="true"></i><span>' . esc_html__( 'Sign In with Google', 'noyona-childtheme' ) . '</span></a>';
    echo '<p class="noyona-login-register-link">' . esc_html__( "Don't have an account?", 'noyona-childtheme' ) . ' <a href="' . esc_url( $register_url ) . '">' . esc_html__( 'Create an Account', 'noyona-childtheme' ) . '</a></p>';
    echo '</div>';
}

add_filter( 'gettext', 'noyona_account_replace_lost_password_text', 20, 3 );
function noyona_account_replace_lost_password_text( $translated_text, $text, $domain ) {
    if ( is_admin() ) {
        return $translated_text;
    }

    if ( ! noyona_is_login_page_context() || is_user_logged_in() ) {
        return $translated_text;
    }

    if ( 'Lost your password?' === $text && 'woocommerce' === $domain ) {
        return __( 'Forgot password?', 'noyona-childtheme' );
    }

    if ( 'Username or email address' === $text && 'woocommerce' === $domain ) {
        return __( 'Email Address', 'noyona-childtheme' );
    }

    return $translated_text;
}

add_action( 'wp_footer', 'noyona_normalize_login_form_controls', 90 );
function noyona_normalize_login_form_controls() {
    if ( is_admin() || is_user_logged_in() || ! noyona_is_login_page_context() ) {
        return;
    }
    ?>
    <script>
    (function () {
      function normalizeLoginForm() {
        var form = document.querySelector('form.woocommerce-form-login.login');
        if (!form) return;

        var username = form.querySelector('#username');
        if (username && !username.getAttribute('placeholder')) {
          username.setAttribute('placeholder', 'Enter your email');
        }

        var password = form.querySelector('#password');
        if (password && !password.getAttribute('placeholder')) {
          password.setAttribute('placeholder', 'Enter your password');
        }

        if (!password) return;

        var usernameRow = username ? username.closest('.woocommerce-form-row') : null;
        var passwordRow = password.closest('.woocommerce-form-row');

        if (usernameRow) {
          usernameRow.classList.add('noyona-login-row--username');
        }
        if (passwordRow) {
          passwordRow.classList.add('noyona-login-row--password');
        }

        var wrapper = password.closest('.password-input');
        if (!wrapper) {
          wrapper = document.createElement('span');
          wrapper.className = 'password-input';
          password.parentNode.insertBefore(wrapper, password);
          wrapper.appendChild(password);
        }

        // Hide/remove third-party injected icon roots and Woo's default toggle in username row.
        if (usernameRow) {
          usernameRow.querySelectorAll('.show-password-input, [data-lastpass-icon-root], [data-lastpass-root]').forEach(function (el) {
            el.remove();
          });
        }

        // Remove Woo default toggle and extension icon roots in password row, then use our own stable toggle.
        if (passwordRow) {
          passwordRow.querySelectorAll('.show-password-input, [data-lastpass-icon-root], [data-lastpass-root]').forEach(function (el) {
            el.remove();
          });
        }

        var customToggle = wrapper.querySelector('.noyona-password-toggle');
        if (!customToggle) {
          customToggle = document.createElement('button');
          customToggle.type = 'button';
          customToggle.className = 'noyona-password-toggle';
          customToggle.setAttribute('aria-label', 'Show password');
          customToggle.innerHTML = '<i class="fa-regular fa-eye" aria-hidden="true"></i>';
          wrapper.appendChild(customToggle);

          customToggle.addEventListener('click', function () {
            var isHidden = password.getAttribute('type') === 'password';
            password.setAttribute('type', isHidden ? 'text' : 'password');
            customToggle.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
            customToggle.innerHTML = isHidden
              ? '<i class="fa-regular fa-eye-slash" aria-hidden="true"></i>'
              : '<i class="fa-regular fa-eye" aria-hidden="true"></i>';
          });
        }
      }

      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', normalizeLoginForm);
      } else {
        normalizeLoginForm();
      }
      setTimeout(normalizeLoginForm, 250);
      setTimeout(normalizeLoginForm, 850);

      if (typeof MutationObserver !== 'undefined') {
        var observer = new MutationObserver(function () {
          normalizeLoginForm();
        });
        observer.observe(document.documentElement, { childList: true, subtree: true });
      }
    })();
    </script>
    <?php
}

/**
 * Control products-per-page on shop/category archives.
 * Default is 14, with optional URL override for testing: ?ppp=2
 */
add_filter( 'loop_shop_per_page', 'noyona_loop_shop_per_page', 20 );
function noyona_loop_shop_per_page( $per_page ) {
    if ( is_page( array( 'face', 'lips', 'eyes', 'hair', 'body' ) ) ) {
        return 4;
    }

    $default_per_page = 14;

    if ( isset( $_GET['ppp'] ) ) {
        $override = absint( wp_unslash( $_GET['ppp'] ) );
        if ( $override >= 1 && $override <= 60 ) {
            return $override;
        }
    }

    return $default_per_page;
}

/**
 * Category page slugs (WooCommerce product_cat slugs) for Eyes, Face, Lips, Hair, Body.
 * Used to force Store API product queries to filter by this category when on the corresponding page.
 */
function noyona_get_shop_category_page_slugs() {
    return array( 'face', 'lips', 'eyes', 'hair', 'body' );
}

/**
 * Get the maximum current product price, optionally scoped to one product_cat slug.
 * Uses SQL directly so URL params (e.g. max_price=...) do not affect the ceiling.
 */
function noyona_get_max_product_price( $category_slug = '' ) {
    global $wpdb;

    $posts_table = $wpdb->posts;
    $meta_table  = $wpdb->postmeta;

    if ( '' !== $category_slug ) {
        $term_rel_table = $wpdb->term_relationships;
        $term_tax_table = $wpdb->term_taxonomy;
        $terms_table    = $wpdb->terms;

        $sql = "
            SELECT MAX(CAST(pm.meta_value AS DECIMAL(10,2)))
            FROM {$posts_table} p
            INNER JOIN {$meta_table} pm
                ON pm.post_id = p.ID
                AND pm.meta_key = '_price'
            INNER JOIN {$term_rel_table} tr
                ON tr.object_id = p.ID
            INNER JOIN {$term_tax_table} tt
                ON tt.term_taxonomy_id = tr.term_taxonomy_id
                AND tt.taxonomy = 'product_cat'
            INNER JOIN {$terms_table} t
                ON t.term_id = tt.term_id
            WHERE p.post_type = 'product'
                AND p.post_status = 'publish'
                AND pm.meta_value <> ''
                AND t.slug = %s
        ";

        $prepared = $wpdb->prepare( $sql, $category_slug );
        $max      = $wpdb->get_var( $prepared );
    } else {
        $sql = "
            SELECT MAX(CAST(pm.meta_value AS DECIMAL(10,2)))
            FROM {$posts_table} p
            INNER JOIN {$meta_table} pm
                ON pm.post_id = p.ID
                AND pm.meta_key = '_price'
            WHERE p.post_type = 'product'
                AND p.post_status = 'publish'
                AND pm.meta_value <> ''
        ";

        $max = $wpdb->get_var( $sql );
    }

    return is_null( $max ) ? 0.0 : (float) $max;
}

/**
 * Apply min/max price filter to wc_get_products query args.
 */
function noyona_apply_price_range_to_product_query_args( $args, $min_price, $max_price ) {
    if ( null !== $min_price && null !== $max_price && $max_price < $min_price ) {
        $swap      = $min_price;
        $min_price = $max_price;
        $max_price = $swap;
    }

    $has_min = null !== $min_price && $min_price >= 0;
    $has_max = null !== $max_price && $max_price >= 0;

    if ( ! $has_min && ! $has_max ) {
        return $args;
    }

    if ( ! isset( $args['meta_query'] ) || ! is_array( $args['meta_query'] ) ) {
        $args['meta_query'] = array();
    }

    if ( $has_min && $has_max ) {
        $args['meta_query']['relation'] = 'AND';
    }

    if ( $has_min ) {
        $args['meta_query'][] = array(
            'key'     => '_price',
            'value'   => (float) $min_price,
            'compare' => '>=',
            'type'    => 'NUMERIC',
        );
    }

    if ( $has_max ) {
        $args['meta_query'][] = array(
            'key'     => '_price',
            'value'   => (float) $max_price,
            'compare' => '<=',
            'type'    => 'NUMERIC',
        );
    }

    return $args;
}

/**
 * Final PHP-level guard for price filtering.
 */
function noyona_filter_products_by_price_range( $products, $min_price, $max_price ) {
    if ( ! is_array( $products ) || ( null === $min_price && null === $max_price ) ) {
        return $products;
    }

    if ( null !== $min_price && null !== $max_price && $max_price < $min_price ) {
        $swap      = $min_price;
        $min_price = $max_price;
        $max_price = $swap;
    }

    return array_values(
        array_filter(
            $products,
            static function ( $product ) use ( $min_price, $max_price ) {
                if ( ! is_object( $product ) || ! method_exists( $product, 'get_price' ) ) {
                    return false;
                }

                $price_raw = $product->get_price();
                $price     = '' === $price_raw ? 0.0 : (float) $price_raw;

                if ( null !== $min_price && $price < (float) $min_price ) {
                    return false;
                }

                if ( null !== $max_price && $price > (float) $max_price ) {
                    return false;
                }

                return true;
            }
        )
    );
}

/**
 * Set a short-lived cookie when visiting a category page so Store API can apply the category filter
 * even when the Referer header is missing. Clear the cookie when not on a category page.
 */
add_action( 'template_redirect', 'noyona_set_shop_category_cookie_on_category_page', 5 );
function noyona_set_shop_category_cookie_on_category_page() {
    $cookie_name = 'noyona_shop_cat';
    $page_slugs  = noyona_get_shop_category_page_slugs();
    $match_slug  = null;

    if ( is_page() ) {
        $post = get_queried_object();
        if ( $post instanceof WP_Post ) {
            $slug       = strtolower( $post->post_name );
            $match_slug = in_array( $slug, $page_slugs, true ) ? $slug : null;
        }
    }

    if ( ! headers_sent() ) {
        if ( $match_slug !== null ) {
            setcookie( $cookie_name, $match_slug, time() + 60, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
        } elseif ( isset( $_COOKIE[ $cookie_name ] ) ) {
            setcookie( $cookie_name, '', time() - 3600, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
        }
    }
}

/**
 * Render a single product card with the unified layout:
 * image → title → excerpt → footer (price left, buy-now right).
 */
function noyona_render_product_card( $product ) {
    $image_id   = $product->get_image_id();
    $image      = $image_id
        ? wp_get_attachment_image( $image_id, 'woocommerce_thumbnail', false, array( 'class' => 'wc-block-components-product-image' ) )
        : wc_placeholder_img( 'woocommerce_thumbnail' );
    $title      = $product->get_name();
    $link       = $product->get_permalink();
    $excerpt    = has_excerpt( $product->get_id() ) ? get_the_excerpt( $product->get_id() ) : '';
    if ( $excerpt ) {
        $excerpt = wp_trim_words( $excerpt, 22 );
    }
    $price_html    = $product->get_price_html();
    $buy_now_url   = $link;
    $buy_now_label = esc_html__( 'Buy Now!', 'noyona' );

    ob_start();
    ?>
    <div class="wc-block-product">
        <a href="<?php echo esc_url( $link ); ?>" class="wc-block-components-product-image"><?php echo $image; ?></a>
        <h3 class="wc-block-product-title wp-block-post-title"><a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $title ); ?></a></h3>
        <?php if ( $excerpt ) : ?>
            <div class="wp-block-post-excerpt"><p><?php echo esc_html( $excerpt ); ?></p></div>
        <?php endif; ?>
        <div class="noyona-product-card-footer">
            <div class="wc-block-components-product-price"><?php echo $price_html; ?></div>
            <a href="<?php echo $buy_now_url; ?>" class="noyona-buy-now-btn"><?php echo $buy_now_label; ?></a>
        </div>
    </div>
    <?php
    $html = ob_get_clean();

    // Guard against auto-formatters that inject empty <p> / <br> inside shortcode output.
    $html = preg_replace( '#<p>\s*</p>#i', '', (string) $html );
    $html = preg_replace( '#<br\s*/?>#i', '', (string) $html );
    $html = preg_replace( '#>\s+<#', '><', (string) $html );

    return trim( (string) $html );
}

/**
 * On landing pages (Face, Lips, Eyes, Hair, Body), replace the product-collection block with server-rendered
 * output so we always show exactly 4 products for that category (no Store API / inherit issues).
 */
add_filter( 'render_block', 'noyona_render_landing_page_products_block', 10, 2 );
function noyona_render_landing_page_products_block( $block_content, $block ) {
    if ( ! isset( $block['blockName'] ) || $block['blockName'] !== 'woocommerce/product-collection' ) {
        return $block_content;
    }

    $attrs = isset( $block['attrs'] ) ? $block['attrs'] : array();
    $class = isset( $attrs['className'] ) ? $attrs['className'] : '';
    if ( strpos( $class, 'noyona-shop-products' ) === false ) {
        return $block_content;
    }

    $post = get_queried_object();
    if ( ! $post instanceof WP_Post || $post->post_type !== 'page' ) {
        return $block_content;
    }

    $slug_lower = strtolower( $post->post_name );
    $page_slugs = noyona_get_shop_category_page_slugs();
    if ( ! in_array( $slug_lower, $page_slugs, true ) ) {
        return $block_content;
    }

    if ( ! function_exists( 'wc_get_products' ) ) {
        return $block_content;
    }

    $min_price = isset( $_GET['min_price'] ) ? floatval( wp_unslash( $_GET['min_price'] ) ) : null;
    $max_price = isset( $_GET['max_price'] ) ? floatval( wp_unslash( $_GET['max_price'] ) ) : null;

    $has_price_range = null !== $min_price || null !== $max_price;

    $query_args = array(
        'status'   => 'publish',
        'limit'    => $has_price_range ? -1 : 4,
        'orderby'  => 'title',
        'order'    => 'ASC',
        'category' => array( $slug_lower ),
        'return'   => 'objects',
    );

    $query_args = noyona_apply_price_range_to_product_query_args( $query_args, $min_price, $max_price );

    $products = wc_get_products( $query_args );
    $products = noyona_filter_products_by_price_range( $products, $min_price, $max_price );

    if ( $has_price_range ) {
        $products = array_slice( $products, 0, 4 );
    }

    if ( $has_price_range ) {
        $all_for_more = wc_get_products(
            array(
                'status'   => 'publish',
                'limit'    => -1,
                'orderby'  => 'title',
                'order'    => 'ASC',
                'category' => array( $slug_lower ),
                'return'   => 'objects',
            )
        );
        $all_for_more = noyona_filter_products_by_price_range( $all_for_more, $min_price, $max_price );
        $has_more     = count( $all_for_more ) > 4;
    } else {
        $has_more = count(
            wc_get_products(
                array(
                    'status'   => 'publish',
                    'limit'    => 5,
                    'category' => array( $slug_lower ),
                    'return'   => 'ids',
                )
            )
        ) > 4;
    }

    if ( empty( $products ) ) {
        if ( null !== $min_price || null !== $max_price ) {
            return '<div class="wp-block-woocommerce-product-collection noyona-shop-products"><div class="wc-block-product-template" style="display:grid;grid-template-columns:repeat(2,1fr);gap:18px;"><div class="wc-block-product" style="grid-column:1/-1;padding:32px;text-align:center;border-radius:16px;background:#f9f9f9;"><p class="has-text-align-center has-vivid-pink-cyan-color has-text-color" style="font-size:30px;font-weight:700">No products found in this price range</p></div></div></div>';
        }
        return '<div class="wp-block-woocommerce-product-collection noyona-shop-products"><div class="wc-block-product-template" style="display:grid;grid-template-columns:repeat(2,1fr);gap:18px;"><div class="wc-block-product" style="grid-column:1/-1;padding:32px;text-align:center;border-radius:16px;background:#f9f9f9;"><p class="has-text-align-center has-vivid-pink-cyan-color has-text-color" style="font-size:30px;font-weight:700">Coming Soon</p></div></div></div>';
    }

    $shop_category_url = home_url( user_trailingslashit( 'shop/' . $slug_lower ) );
    $category_label    = ucfirst( $slug_lower );

    ob_start();
    echo '<div class="wp-block-woocommerce-product-collection noyona-shop-products"><div class="wc-block-product-template" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;">';
    foreach ( $products as $product ) {
        echo noyona_render_product_card( $product );
    }
    echo '</div>';
    if ( $has_more ) {
        echo '<div class="noyona-landing-show-more">';
        echo '<a href="' . esc_url( $shop_category_url ) . '" class="noyona-landing-show-more-btn">';
        echo esc_html( 'Show more ' . $category_label . ' products' );
        echo '</a></div>';
    }
    echo '</div>';
    return ob_get_clean();
}

/**
 * On the shop archive, replace native product-collection card markup with
 * the unified noyona product card layout (image → title → excerpt → footer).
 */
add_filter( 'render_block', 'noyona_render_shop_archive_product_cards', 10, 2 );
function noyona_render_shop_archive_product_cards( $block_content, $block ) {
    if ( ! isset( $block['blockName'] ) || $block['blockName'] !== 'woocommerce/product-collection' ) {
        return $block_content;
    }

    $attrs = isset( $block['attrs'] ) ? $block['attrs'] : array();
    $class = isset( $attrs['className'] ) ? $attrs['className'] : '';
    if ( strpos( $class, 'noyona-shop-products' ) === false ) {
        return $block_content;
    }

    // Only run on product archive pages (shop, product_cat taxonomy).
    if ( ! is_shop() && ! is_product_taxonomy() ) {
        return $block_content;
    }

    if ( ! function_exists( 'wc_get_products' ) ) {
        return $block_content;
    }

    // Build query args from the block's query settings.
    $query   = isset( $attrs['query'] ) ? $attrs['query'] : array();
    $per_page = isset( $query['perPage'] ) ? (int) $query['perPage'] : 12;
    if ( $per_page < 1 ) {
        $per_page = 12;
    }
    $order    = isset( $query['order'] ) ? $query['order'] : 'ASC';
    $orderby  = isset( $query['orderBy'] ) ? $query['orderBy'] : 'title';

    $args = array(
        'status'  => 'publish',
        'limit'   => $per_page,
        'orderby' => $orderby,
        'order'   => $order,
        'return'  => 'objects',
    );

    // Apply category filter if on a product_cat archive.
    if ( is_product_category() ) {
        $term = get_queried_object();
        if ( $term && ! is_wp_error( $term ) ) {
            $args['category'] = array( $term->slug );
        }
    }

    // Apply tax query from block attributes.
    if ( ! empty( $query['taxQuery'] ) ) {
        foreach ( $query['taxQuery'] as $tq ) {
            if ( isset( $tq['taxonomy'] ) && $tq['taxonomy'] === 'product_cat' && ! empty( $tq['terms'] ) ) {
                $args['category'] = $tq['terms'];
                break;
            }
        }
    }

    $query_id  = isset( $attrs['queryId'] ) ? absint( $attrs['queryId'] ) : 0;
    $page_key  = 'query-' . $query_id . '-page';
    $paged     = 1;

    if ( isset( $_GET[ $page_key ] ) ) {
        $paged = max( 1, absint( wp_unslash( $_GET[ $page_key ] ) ) );
    } elseif ( isset( $_GET['paged'] ) ) {
        $paged = max( 1, absint( wp_unslash( $_GET['paged'] ) ) );
    } else {
        $paged = max( 1, absint( get_query_var( 'paged', 1 ) ) );
    }

    $args['offset'] = ( $paged - 1 ) * $per_page;

    $min_price = isset( $_GET['min_price'] ) ? floatval( wp_unslash( $_GET['min_price'] ) ) : null;
    $max_price = isset( $_GET['max_price'] ) ? floatval( wp_unslash( $_GET['max_price'] ) ) : null;

    $has_price_range = null !== $min_price || null !== $max_price;
    $args            = noyona_apply_price_range_to_product_query_args( $args, $min_price, $max_price );
    if ( $has_price_range ) {
        $args['limit'] = -1;
        unset( $args['offset'] );
    }

    $products = wc_get_products( $args );
    $products = noyona_filter_products_by_price_range( $products, $min_price, $max_price );
    if ( $has_price_range ) {
        $products = array_slice( $products, ( $paged - 1 ) * $per_page, $per_page );
    }

    if ( empty( $products ) ) {
        return '<div class="wp-block-woocommerce-product-collection noyona-shop-products"><div class="wc-block-product-template" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;"><div class="wc-block-product" style="grid-column:1/-1;padding:32px;text-align:center;border-radius:16px;background:#f9f9f9;"><p class="has-text-align-center has-vivid-pink-cyan-color has-text-color" style="font-size:30px;font-weight:700">No products found in this price range</p></div></div></div>';
    }

    ob_start();
    echo '<div class="wp-block-woocommerce-product-collection noyona-shop-products"><div class="wc-block-product-template" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;">';
    foreach ( $products as $product ) {
        echo noyona_render_product_card( $product );
    }
    echo '</div>';

    // Re-render pagination from the original block output.
    if ( preg_match( '/<nav[^>]*wp-block-query-pagination.*?<\/nav>/s', $block_content, $matches ) ) {
        echo $matches[0];
    }

    echo '</div>';
    return ob_get_clean();
}

/**
 * Force Store API product queries on category pages (face, lips, eyes, hair, body) to only return products in that category.
 * Uses HTTP Referer first; falls back to noyona_shop_cat cookie when referer is missing or doesn't match.
 */
add_filter( 'rest_request_before_callbacks', 'noyona_force_product_category_on_category_pages', 10, 3 );
function noyona_force_product_category_on_category_pages( $response, $handler, $request ) {
    $route = $request->get_route();
    if ( ! is_string( $route ) || strpos( $route, 'wc/store' ) === false || strpos( $route, 'products' ) === false ) {
        return $response;
    }

    $page_slugs = noyona_get_shop_category_page_slugs();
    $match_slug = null;

    // Prefer category from HTTP Referer (current page URL).
    $referer = isset( $_SERVER['HTTP_REFERER'] ) ? wp_unslash( $_SERVER['HTTP_REFERER'] ) : '';
    if ( $referer !== '' ) {
        $referer_path = wp_parse_url( $referer, PHP_URL_PATH );
        $referer_path = is_string( $referer_path ) ? trim( $referer_path, '/' ) : '';
        if ( $referer_path !== '' ) {
            $path_parts = explode( '/', $referer_path );
            foreach ( $path_parts as $segment ) {
                $segment_lower = strtolower( $segment );
                if ( in_array( $segment_lower, $page_slugs, true ) ) {
                    $match_slug = $segment_lower;
                    break;
                }
            }
        }
    }

    // Fallback: category page cookie (set on template_redirect when visiting Eyes, Face, Lips, etc.).
    if ( $match_slug === null && isset( $_COOKIE['noyona_shop_cat'] ) ) {
        $cookie_slug = strtolower( sanitize_text_field( wp_unslash( $_COOKIE['noyona_shop_cat'] ) ) );
        if ( in_array( $cookie_slug, $page_slugs, true ) ) {
            $match_slug = $cookie_slug;
        }
    }

    if ( $match_slug === null ) {
        return $response;
    }

    $request->set_param( 'category', array( $match_slug ) );
    return $response;
}

// Shortcode: [product_gatherer]
add_shortcode( 'product_gatherer', 'woocom_ct_product_gatherer_shortcode' );
function woocom_ct_product_gatherer_shortcode( $atts ) {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return '<p>WooCommerce is not active.</p>';
    }

    wp_enqueue_style( 'woocom-ct-product-gatherer' );
    wp_enqueue_script( 'woocom-ct-product-gatherer' );

    // Read filters from query string
    $search = isset( $_GET['pg_search'] ) ? sanitize_text_field( wp_unslash( $_GET['pg_search'] ) ) : '';
    $sort   = isset( $_GET['pg_sort'] ) ? sanitize_text_field( wp_unslash( $_GET['pg_sort'] ) ) : 'default';
    $cat    = isset( $_GET['pg_cat'] )  ? sanitize_text_field( wp_unslash( $_GET['pg_cat'] ) )  : '';

    // Sorting logic
    $orderby  = 'title';
    $order    = 'ASC';
    $meta_key = '';

    switch ( $sort ) {
        case 'price_asc':
            $orderby  = 'meta_value_num';
            $order    = 'ASC';
            $meta_key = '_price';
            break;
        case 'price_desc':
            $orderby  = 'meta_value_num';
            $order    = 'DESC';
            $meta_key = '_price';
            break;
        case 'latest':
            $orderby  = 'date';
            $order    = 'DESC';
            break;
        default:
            // title ASC
            break;
    }

    $paged = isset( $_GET['pg_page'] ) ? max( 1, (int) $_GET['pg_page'] ) : 1;

    $args = array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => 12, // 4 x 3 cards per page
        'paged'          => $paged,
        'orderby'        => $orderby,
        'order'          => $order,
    );

    if ( $meta_key ) {
        $args['meta_key'] = $meta_key;
    }

    if ( $search ) {
        $args['s'] = $search;
    }

    if ( $cat ) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => $cat,
            ),
        );
    }

    $query = new WP_Query( $args );

    // Get categories for dropdown
    $categories = get_terms(
        array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => true,
        )
    );

    ob_start();
    ?>

    <div class="pg-wrapper">
        <div class="pg-header">
            <div>
                <h2 class="pg-title">Products</h2>
                <p class="pg-subtitle">Discover our curated beauty products.</p>
            </div>

            <form class="pg-toolbar-form" method="get">
                <div class="pg-toolbar">

                    <div class="pg-search">
                        <input
                            type="text"
                            name="pg_search"
                            value="<?php echo esc_attr( $search ); ?>"
                            placeholder="Search products (e.g. lipstick)"
                        />
                        <button type="submit" class="pg-search-btn">
                            <i class="fa fa-search"></i>
                        </button>
                    </div>

                    <div class="pg-filters">
                        <select name="pg_sort" class="pg-select">
                            <option value="default" <?php selected( $sort, 'default' ); ?>>Sort: Default</option>
                            <option value="latest" <?php selected( $sort, 'latest' ); ?>>Newest</option>
                            <option value="price_asc" <?php selected( $sort, 'price_asc' ); ?>>Price: Low to High</option>
                            <option value="price_desc" <?php selected( $sort, 'price_desc' ); ?>>Price: High to Low</option>
                        </select>

                        <select name="pg_cat" class="pg-select">
                            <option value="">All categories</option>
                            <?php foreach ( $categories as $category ) : ?>
                                <option value="<?php echo esc_attr( $category->slug ); ?>" <?php selected( $cat, $category->slug ); ?>>
                                    <?php echo esc_html( $category->name ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </form>
        </div>

        <div class="pg-grid">
            <?php
            if ( $query->have_posts() ) :
                while ( $query->have_posts() ) :
                    $query->the_post();
                    $product = wc_get_product( get_the_ID() );
                    if ( ! $product ) {
                        continue;
                    }

                    $image_url    = get_the_post_thumbnail_url( get_the_ID(), 'medium' );
                    $price_html   = $product->get_price_html();
                    $rating_html  = wc_get_rating_html( $product->get_average_rating() );
                    $add_url      = esc_url( $product->add_to_cart_url() );
                    $add_text     = esc_html( $product->add_to_cart_text() );
                    $is_on_sale   = $product->is_on_sale();
                    ?>
                    <article class="pg-card">
                        <div class="pg-card-media">
                            <?php if ( $image_url ) : ?>
                                <a href="<?php the_permalink(); ?>">
                                    <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php the_title_attribute(); ?>" />
                                </a>
                            <?php endif; ?>

                            <?php if ( $is_on_sale ) : ?>
                                <span class="pg-badge-sale">SALE</span>
                            <?php endif; ?>

                            <button class="pg-wishlist-btn" type="button">
                                <i class="fa-regular fa-heart"></i>
                            </button>
                        </div>

                        <div class="pg-card-body">
                            <h3 class="pg-product-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h3>

                            <div class="pg-rating">
                                <?php echo $rating_html ? $rating_html : '<span class="pg-rating-placeholder">No reviews yet</span>'; ?>
                            </div>

                            <p class="pg-excerpt">
                                <?php echo wp_kses_post( wp_trim_words( get_the_excerpt(), 16 ) ); ?>
                            </p>

                            <div class="pg-card-footer">
                                <div class="pg-price">
                                    <?php echo wp_kses_post( $price_html ); ?>
                                </div>
                                <a
                                    href="<?php echo $add_url; ?>"
                                    data-product_id="<?php echo esc_attr( $product->get_id() ); ?>"
                                    class="pg-add-to-cart-button add_to_cart_button ajax_add_to_cart"
                                >
                                    <i class="fa fa-shopping-cart"></i>
                                    <?php echo $add_text; ?>
                                </a>
                            </div>
                        </div>
                    </article>
                    <?php
                endwhile;
            else :
                ?>
                <p class="pg-no-results">No products found for your filters.</p>
                <?php
            endif;
            ?>
        </div>

        <?php if ( $query->max_num_pages > 1 ) : ?>
            <div class="pg-pagination">
                <?php
                echo paginate_links(
                    array(
                        'current' => $paged,
                        'total'   => $query->max_num_pages,
                        'format'  => '&pg_page=%#%',
                        'type'    => 'list',
                    )
                );
                ?>
            </div>
        <?php endif; ?>

    </div><!-- .pg-wrapper -->

    <?php
    wp_reset_postdata();

    return ob_get_clean();
}

// Register custom blocks
add_action( 'init', 'woocom_ct_register_blocks' );
function woocom_ct_register_blocks() {
    register_block_type( get_stylesheet_directory() . '/blocks/search-expand' );
    register_block_type( get_stylesheet_directory() . '/blocks/hero-banner' );
    register_block_type( get_stylesheet_directory() . '/blocks/guides-pillars' );
    register_block_type( get_stylesheet_directory() . '/blocks/different-cards' );
    register_block_type( get_stylesheet_directory() . '/blocks/brand-carousel' );
    register_block_type( get_stylesheet_directory() . '/blocks/color-swatches' );
    register_block_type( get_stylesheet_directory() . '/blocks/product-highlight' );
    register_block_type( get_stylesheet_directory() . '/blocks/product-slide' );
    register_block_type( get_stylesheet_directory() . '/blocks/collection-grid' );
    register_block_type( get_stylesheet_directory() . '/blocks/phone-video-reviews' );
    register_block_type( get_stylesheet_directory() . '/blocks/discover-posts-carousel' );
    register_block_type( get_stylesheet_directory() . '/blocks/founder-section' );
    register_block_type( get_stylesheet_directory() . '/blocks/landing-feature-banner' );
    register_block_type( get_stylesheet_directory() . '/blocks/types-cards' );
    register_block_type( get_stylesheet_directory() . '/blocks/benefits-strip' );
    register_block_type( get_stylesheet_directory() . '/blocks/values-strip' );
    register_block_type( get_stylesheet_directory() . '/blocks/newsletter-strip' );
    register_block_type( get_stylesheet_directory() . '/blocks/faq' );
    register_block_type( get_stylesheet_directory() . '/blocks/faq-list' );
    register_block_type( get_stylesheet_directory() . '/blocks/contact' );
    register_block_type( get_stylesheet_directory() . '/blocks/location' );
    register_block_type( get_stylesheet_directory() . '/blocks/not-found' );
    register_block_type( get_stylesheet_directory() . '/blocks/contact-form' );
    register_block_type( get_stylesheet_directory() . '/blocks/blog-list' );
    register_block_type( get_stylesheet_directory() . '/blocks/blog-slide' );
    register_block_type( get_stylesheet_directory() . '/blocks/blogs-view' );
    register_block_type( get_stylesheet_directory() . '/blocks/term' );
    register_block_type( get_stylesheet_directory() . '/blocks/terms' );
    register_block_type( get_stylesheet_directory() . '/blocks/thank-you' );
    register_block_type( get_stylesheet_directory() . '/blocks/coming-soon' );
    register_block_type( get_stylesheet_directory() . '/blocks/find-us' );
    register_block_type( get_stylesheet_directory() . '/blocks/customer-reviews' );
    register_block_type( get_stylesheet_directory() . '/blocks/discover-face-banner' );
    register_block_type( get_stylesheet_directory() . '/blocks/product-breadcrumbs' );
    register_block_type( get_stylesheet_directory() . '/blocks/pdp-social-proof' );
    register_block_type( get_stylesheet_directory() . '/blocks/pdp-essentials' );
    register_block_type( get_stylesheet_directory() . '/blocks/pdp-stock-shipping' );
    register_block_type( get_stylesheet_directory() . '/blocks/pdp-trust-badges' );
    register_block_type( get_stylesheet_directory() . '/blocks/pdp-related-products' );
    register_block_type( get_stylesheet_directory() . '/blocks/product-tabs' );
}

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

add_shortcode( 'noyona_register_form', 'woocom_ct_register_form_shortcode' );
function noyona_clean_register_markup( $html ) {
    if ( '' === trim( (string) $html ) ) {
        return (string) $html;
    }

    $cleaned = (string) $html;
    $cleaned = preg_replace( '#<p>\s*</p>#i', '', $cleaned );
    $cleaned = preg_replace( '#<br\s*/?>#i', '', $cleaned );
    $cleaned = preg_replace( '#>\s+<#', '><', $cleaned );

    $cleaned = preg_replace_callback(
        '#<form\b[^>]*class=(["\'])[^"\']*\bnoyona-register-form\b[^"\']*\1[^>]*>.*?</form>#is',
        static function ( $matches ) {
            $form_html = (string) $matches[0];
            $form_html = preg_replace( '#<p>\s*</p>#i', '', $form_html );
            $form_html = preg_replace( '#</?p\b[^>]*>#i', '', $form_html );
            $form_html = preg_replace( '#<br\s*/?>#i', '', $form_html );
            $form_html = preg_replace( '#>\s+<#', '><', $form_html );
            return trim( $form_html );
        },
        $cleaned
    );

    $cleaned = preg_replace(
        '#<p\b[^>]*>\s*(<a\b[^>]*class=(["\'])[^"\']*\bnoyona-register-google\b[^"\']*\2[^>]*>.*?</a>)\s*</p>#is',
        '<div class="noyona-register-google-wrap">$1</div>',
        $cleaned
    );
    $cleaned = preg_replace(
        '#<p\b[^>]*>\s*(<div\b[^>]*class=(["\'])[^"\']*\bnoyona-register-google-wrap\b[^"\']*\2[^>]*>.*?</div>)\s*</p>#is',
        '$1',
        $cleaned
    );

    return trim( (string) $cleaned );
}

function woocom_ct_register_form_shortcode() {
    $account_url = function_exists( 'wc_get_page_permalink' )
        ? wc_get_page_permalink( 'myaccount' )
        : home_url( '/my-account/' );
    $google_login_url = noyona_get_google_login_url( $account_url );
    $logo_url = trailingslashit( get_stylesheet_directory_uri() ) . 'assets/images/noyona-mobile-logo.webp';

    if ( is_user_logged_in() ) {
        return '<div class="noyona-register-message">You are already logged in. <a href="' . esc_url( $account_url ) . '">Go to My Account</a>.</div>';
    }

    $error_code = isset( $_GET['register_error'] )
        ? sanitize_text_field( wp_unslash( $_GET['register_error'] ) )
        : '';
    $success = isset( $_GET['register_success'] );
    $message = '';
    $message_class = '';

    if ( $success ) {
        $message = 'Account created successfully. You can now log in.';
        $message_class = ' is-success';
    } elseif ( $error_code ) {
        switch ( $error_code ) {
            case 'missing_fields':
                $message = 'Please fill in all required fields.';
                break;
            case 'invalid_email':
                $message = 'Please enter a valid email address.';
                break;
            case 'email_exists':
                $message = 'This email is already registered.';
                break;
            case 'password_mismatch':
                $message = 'Passwords do not match.';
                break;
            case 'invalid_phone':
                $message = 'Please enter a valid phone number.';
                break;
            case 'terms_not_accepted':
                $message = 'Please agree to the Terms and Conditions.';
                break;
            case 'invalid_request':
                $message = 'Invalid registration request. Please try again.';
                break;
            default:
                $message = 'Registration failed. Please try again.';
                break;
        }
        $message_class = ' is-error';
    }

    ob_start();
    ?>
    <div class="noyona-register">
        <div class="noyona-register-card">
            <a class="noyona-register-back" href="/login/">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                <span><?php esc_html_e( 'Back', 'noyona-childtheme' ); ?></span>
            </a>

            <div class="noyona-register-brand">
                <img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php esc_attr_e( 'Noyona logo', 'noyona-childtheme' ); ?>" loading="lazy" decoding="async" />
            </div>

            <h2 class="noyona-register-title"><?php esc_html_e( 'Register', 'noyona-childtheme' ); ?></h2>
            <p class="noyona-register-subtitle"><?php esc_html_e( 'Enter name, email, and password to create your account.', 'noyona-childtheme' ); ?></p>

            <?php if ( $message ) : ?>
                <div class="noyona-register-message<?php echo esc_attr( $message_class ); ?>">
                    <?php echo esc_html( $message ); ?>
                </div>
            <?php endif; ?>

            <form class="noyona-register-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'noyona_register_action', 'noyona_register_nonce' ); ?>
                <input type="hidden" name="action" value="noyona_register">
                <div class="noyona-register-field">
                    <label for="noyona-register-full-name"><?php esc_html_e( 'Full Name:', 'noyona-childtheme' ); ?></label>
                    <input id="noyona-register-full-name" type="text" name="full_name" autocomplete="name" placeholder="<?php esc_attr_e( 'Jane Doe', 'noyona-childtheme' ); ?>" required>
                </div>

                <div class="noyona-register-field">
                    <label for="noyona-register-email"><?php esc_html_e( 'Email Address:', 'noyona-childtheme' ); ?></label>
                    <input id="noyona-register-email" type="email" name="email" autocomplete="email" placeholder="<?php esc_attr_e( 'janedoe@email.com', 'noyona-childtheme' ); ?>" required>
                </div>

                <div class="noyona-register-field">
                    <label for="noyona-register-phone"><?php esc_html_e( 'Phone Number:', 'noyona-childtheme' ); ?></label>
                    <input id="noyona-register-phone" type="text" name="phone" autocomplete="tel" placeholder="<?php esc_attr_e( '09123456789', 'noyona-childtheme' ); ?>" required>
                </div>

                <div class="noyona-register-field">
                    <label for="noyona-register-password"><?php esc_html_e( 'Password:', 'noyona-childtheme' ); ?></label>
                    <input id="noyona-register-password" type="password" name="password" autocomplete="new-password" placeholder="<?php esc_attr_e( 'Enter your password', 'noyona-childtheme' ); ?>" required>
                </div>

                <label class="noyona-register-terms">
                    <input type="checkbox" name="terms" value="1" required>
                    <span><?php esc_html_e( 'I agree to the', 'noyona-childtheme' ); ?> <a href="/terms-of-service/"><?php esc_html_e( 'Terms and Conditions', 'noyona-childtheme' ); ?></a></span>
                </label>

                <button class="noyona-register-submit" type="submit"><?php esc_html_e( 'Sign Up', 'noyona-childtheme' ); ?></button>
            </form>

            <div class="noyona-register-google-wrap">
                <a class="noyona-register-google" href="<?php echo esc_url( $google_login_url ); ?>">
                    <i class="fa-brands fa-google" aria-hidden="true"></i>
                    <span><?php esc_html_e( 'Sign Up with Google', 'noyona-childtheme' ); ?></span>
                </a>
            </div>

            <p class="noyona-register-login">
                <?php esc_html_e( 'Already have an account?', 'noyona-childtheme' ); ?> <a href="<?php echo esc_url( $account_url ); ?>"><?php esc_html_e( 'Log In', 'noyona-childtheme' ); ?></a>
            </p>
        </div>
    </div>
    <?php
    return noyona_clean_register_markup( ob_get_clean() );
}

add_filter( 'the_content', 'noyona_clean_register_form_artifacts', 30 );
function noyona_clean_register_form_artifacts( $content ) {
    if ( is_admin() || ! is_page( 'register' ) ) {
        return $content;
    }

    if ( false === strpos( (string) $content, 'noyona-register-form' ) ) {
        return $content;
    }

    return noyona_clean_register_markup( (string) $content );
}

add_action( 'template_redirect', 'noyona_register_page_buffer_cleanup', 1 );
function noyona_register_page_buffer_cleanup() {
    if ( is_admin() || ! is_page( 'register' ) ) {
        return;
    }

    ob_start(
        static function ( $html ) {
            return noyona_clean_register_markup( (string) $html );
        }
    );
}

add_action( 'admin_post_nopriv_noyona_register', 'woocom_ct_handle_register_form' );
add_action( 'admin_post_noyona_register', 'woocom_ct_handle_register_form' );
function woocom_ct_handle_register_form() {
    $redirect = wp_get_referer();
    if ( ! $redirect ) {
        $redirect = home_url( '/register/' );
    }
    $redirect = remove_query_arg( array( 'register_error', 'register_success' ), $redirect );

    if ( ! isset( $_POST['noyona_register_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['noyona_register_nonce'] ) ), 'noyona_register_action' ) ) {
        wp_safe_redirect( add_query_arg( 'register_error', 'invalid_request', $redirect ) );
        exit;
    }

    if ( is_user_logged_in() ) {
        wp_safe_redirect( $redirect );
        exit;
    }

    $full_name = isset( $_POST['full_name'] ) ? sanitize_text_field( wp_unslash( $_POST['full_name'] ) ) : '';
    $first_name = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
    $middle_name = isset( $_POST['middle_name'] ) ? sanitize_text_field( wp_unslash( $_POST['middle_name'] ) ) : '';
    $last_name = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
    $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
    $phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
    $password = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';
    $confirm_password = isset( $_POST['confirm_password'] ) ? (string) wp_unslash( $_POST['confirm_password'] ) : $password;
    $terms_accepted = isset( $_POST['terms'] ) && '1' === (string) wp_unslash( $_POST['terms'] );

    if ( '' !== $full_name ) {
        $name_parts = preg_split( '/\s+/', trim( $full_name ) );
        if ( empty( $first_name ) && ! empty( $name_parts ) ) {
            $first_name = array_shift( $name_parts );
        }
        if ( empty( $last_name ) && ! empty( $name_parts ) ) {
            $last_name = trim( implode( ' ', $name_parts ) );
        }
    }

    if ( '' === $first_name && '' !== $last_name ) {
        $first_name = $last_name;
    }
    if ( '' === $last_name && '' !== $first_name ) {
        $last_name = $first_name;
    }

    if ( '' === $first_name || '' === $last_name || '' === $email || '' === $phone || '' === $password ) {
        wp_safe_redirect( add_query_arg( 'register_error', 'missing_fields', $redirect ) );
        exit;
    }

    if ( ! is_email( $email ) ) {
        wp_safe_redirect( add_query_arg( 'register_error', 'invalid_email', $redirect ) );
        exit;
    }

    if ( email_exists( $email ) ) {
        wp_safe_redirect( add_query_arg( 'register_error', 'email_exists', $redirect ) );
        exit;
    }

    if ( $password !== $confirm_password ) {
        wp_safe_redirect( add_query_arg( 'register_error', 'password_mismatch', $redirect ) );
        exit;
    }

    $normalized_phone = function_exists( 'noyona_normalize_phone' ) ? noyona_normalize_phone( $phone ) : $phone;
    if ( false === $normalized_phone || '' === trim( (string) $normalized_phone ) ) {
        wp_safe_redirect( add_query_arg( 'register_error', 'invalid_phone', $redirect ) );
        exit;
    }

    if ( ! $terms_accepted ) {
        wp_safe_redirect( add_query_arg( 'register_error', 'terms_not_accepted', $redirect ) );
        exit;
    }

    $email_parts = explode( '@', $email );
    $base_username = sanitize_user( $email_parts[0], true );
    if ( '' === $base_username ) {
        $base_username = 'customer';
    }
    $username = woocom_ct_unique_username( $base_username );
    if ( ! validate_username( $username ) ) {
        $username = woocom_ct_unique_username( 'customer' );
    }

    $role = get_role( 'customer' ) ? 'customer' : 'subscriber';
    $display_name = '' !== trim( $full_name ) ? trim( $full_name ) : trim( $first_name . ' ' . $last_name );

    if ( function_exists( 'wc_create_new_customer' ) ) {
        $user_id = wc_create_new_customer(
            $email,
            $username,
            $password,
            array(
                'first_name'   => $first_name,
                'last_name'    => $last_name,
                'display_name' => $display_name,
                'role'         => $role,
            )
        );
    } else {
        $user_id = wp_insert_user(
            array(
                'user_login'   => $username,
                'user_email'   => $email,
                'user_pass'    => $password,
                'first_name'   => $first_name,
                'last_name'    => $last_name,
                'display_name' => $display_name,
                'role'         => $role,
            )
        );
    }

    if ( is_wp_error( $user_id ) ) {
        wp_safe_redirect( add_query_arg( 'register_error', 'invalid_request', $redirect ) );
        exit;
    }

    if ( '' !== $middle_name ) {
        update_user_meta( $user_id, 'middle_name', $middle_name );
    }
    update_user_meta( $user_id, 'billing_phone', $normalized_phone );
    if ( function_exists( 'wc_create_new_customer' ) ) {
        update_user_meta( $user_id, 'billing_first_name', $first_name );
        update_user_meta( $user_id, 'billing_last_name', $last_name );
        update_user_meta( $user_id, 'billing_phone', $normalized_phone );
    }

    wp_set_current_user( $user_id );
    wp_set_auth_cookie( $user_id, true );
    $user = get_user_by( 'id', $user_id );
    if ( $user ) {
        do_action( 'wp_login', $user->user_login, $user );
    }

    $account_url = function_exists( 'wc_get_page_permalink' )
        ? wc_get_page_permalink( 'myaccount' )
        : home_url( '/my-account/' );
    if ( ! $account_url ) {
        $account_url = home_url( '/' );
    }

    wp_safe_redirect( $account_url );
    exit;
}

function woocom_ct_unique_username( $base_username ) {
    $base_username = sanitize_user( $base_username, true );
    if ( '' === $base_username ) {
        $base_username = 'customer';
    }
    $username = $base_username;
    $suffix = 1;

    while ( username_exists( $username ) ) {
        $username = $base_username . $suffix;
        $suffix++;
    }

    return $username;
}

function noyona_get_account_addresses_url() {
    if ( function_exists( 'wc_get_account_endpoint_url' ) ) {
        return (string) wc_get_account_endpoint_url( 'edit-address' );
    }

    return home_url( '/my-account/edit-address/' );
}

function noyona_normalize_account_addresses( $addresses ) {
    $normalized  = array();
    $seen_ids    = array();
    $has_default = false;

    foreach ( (array) $addresses as $address_data ) {
        if ( ! is_array( $address_data ) ) {
            continue;
        }

        $address_id = isset( $address_data['id'] ) ? sanitize_key( (string) $address_data['id'] ) : '';
        if ( '' === $address_id || isset( $seen_ids[ $address_id ] ) ) {
            $address_id = sanitize_key( 'addr_' . wp_generate_uuid4() );
        }
        $seen_ids[ $address_id ] = true;

        $address_line = isset( $address_data['address'] ) ? sanitize_text_field( wp_unslash( (string) $address_data['address'] ) ) : '';
        $city         = isset( $address_data['city'] ) ? sanitize_text_field( wp_unslash( (string) $address_data['city'] ) ) : '';
        $province_raw = isset( $address_data['province'] ) ? sanitize_text_field( wp_unslash( (string) $address_data['province'] ) ) : '';
        // Normalize free-text province values ("Metro Manila", "NCR", "Pasig City, Metro Manila")
        // to the WooCommerce PH state code ('00', 'ABR', …) so shipping zone matching works.
        $province     = class_exists( 'Noyona_Shipping' ) ? Noyona_Shipping::normalize_ph_state( $province_raw ) : $province_raw;
        $zip_code     = isset( $address_data['zip_code'] ) ? sanitize_text_field( wp_unslash( (string) $address_data['zip_code'] ) ) : '';

        if ( '' === $address_line && '' === $city && '' === $province && '' === $zip_code ) {
            continue;
        }

        $is_default = ! empty( $address_data['is_default'] );
        if ( $is_default && $has_default ) {
            $is_default = false;
        }

        if ( $is_default ) {
            $has_default = true;
        }

        $normalized[] = array(
            'id'         => $address_id,
            'address'    => $address_line,
            'city'       => $city,
            'province'   => $province,
            'zip_code'   => $zip_code,
            'is_default' => $is_default,
        );
    }

    if ( ! $has_default && ! empty( $normalized ) ) {
        $normalized[0]['is_default'] = true;
    }

    return $normalized;
}

function noyona_get_account_saved_addresses( $user_id ) {
    $user_id = absint( $user_id );
    if ( $user_id < 1 ) {
        return array();
    }

    $stored_addresses = get_user_meta( $user_id, 'noyona_account_addresses', true );
    if ( is_array( $stored_addresses ) && ! empty( $stored_addresses ) ) {
        return noyona_normalize_account_addresses( $stored_addresses );
    }

    $fallback_addresses = array();

    $billing_address = sanitize_text_field( (string) get_user_meta( $user_id, 'billing_address_1', true ) );
    $billing_city    = sanitize_text_field( (string) get_user_meta( $user_id, 'billing_city', true ) );
    $billing_state   = sanitize_text_field( (string) get_user_meta( $user_id, 'billing_state', true ) );
    $billing_zip     = sanitize_text_field( (string) get_user_meta( $user_id, 'billing_postcode', true ) );

    if ( '' !== trim( $billing_address . $billing_city . $billing_state . $billing_zip ) ) {
        $fallback_addresses[] = array(
            'id'         => sanitize_key( 'addr_' . wp_generate_uuid4() ),
            'address'    => $billing_address,
            'city'       => $billing_city,
            'province'   => $billing_state,
            'zip_code'   => $billing_zip,
            'is_default' => true,
        );
    }

    $shipping_address = sanitize_text_field( (string) get_user_meta( $user_id, 'shipping_address_1', true ) );
    $shipping_city    = sanitize_text_field( (string) get_user_meta( $user_id, 'shipping_city', true ) );
    $shipping_state   = sanitize_text_field( (string) get_user_meta( $user_id, 'shipping_state', true ) );
    $shipping_zip     = sanitize_text_field( (string) get_user_meta( $user_id, 'shipping_postcode', true ) );
    $shipping_parts   = array( $shipping_address, $shipping_city, $shipping_state, $shipping_zip );
    $billing_parts    = array( $billing_address, $billing_city, $billing_state, $billing_zip );

    if ( '' !== trim( implode( '', $shipping_parts ) ) ) {
        $shipping_signature = strtolower( implode( '|', array_map( 'trim', $shipping_parts ) ) );
        $billing_signature  = strtolower( implode( '|', array_map( 'trim', $billing_parts ) ) );
        if ( $shipping_signature !== $billing_signature ) {
            $fallback_addresses[] = array(
                'id'         => sanitize_key( 'addr_' . wp_generate_uuid4() ),
                'address'    => $shipping_address,
                'city'       => $shipping_city,
                'province'   => $shipping_state,
                'zip_code'   => $shipping_zip,
                'is_default' => empty( $fallback_addresses ),
            );
        }
    }

    $normalized_fallback = noyona_normalize_account_addresses( $fallback_addresses );
    if ( ! empty( $normalized_fallback ) ) {
        update_user_meta( $user_id, 'noyona_account_addresses', $normalized_fallback );
    }

    return $normalized_fallback;
}

function noyona_sync_default_account_address_to_woocommerce( $user_id, $addresses ) {
    $user_id   = absint( $user_id );
    $addresses = noyona_normalize_account_addresses( $addresses );

    if ( $user_id < 1 || empty( $addresses ) ) {
        return;
    }

    $default_address = $addresses[0];
    foreach ( $addresses as $address_data ) {
        if ( ! empty( $address_data['is_default'] ) ) {
            $default_address = $address_data;
            break;
        }
    }

    update_user_meta( $user_id, 'billing_address_1', $default_address['address'] );
    update_user_meta( $user_id, 'billing_city', $default_address['city'] );
    update_user_meta( $user_id, 'billing_state', $default_address['province'] );
    update_user_meta( $user_id, 'billing_postcode', $default_address['zip_code'] );

    update_user_meta( $user_id, 'shipping_address_1', $default_address['address'] );
    update_user_meta( $user_id, 'shipping_city', $default_address['city'] );
    update_user_meta( $user_id, 'shipping_state', $default_address['province'] );
    update_user_meta( $user_id, 'shipping_postcode', $default_address['zip_code'] );
}

function noyona_get_account_payments_url() {
    if ( function_exists( 'wc_get_account_endpoint_url' ) ) {
        return (string) wc_get_account_endpoint_url( 'payment-methods' );
    }

    return home_url( '/my-account/payment-methods/' );
}

function noyona_normalize_account_bank_accounts( $bank_accounts ) {
    $normalized = array();
    $seen_ids   = array();

    foreach ( (array) $bank_accounts as $bank_data ) {
        if ( ! is_array( $bank_data ) ) {
            continue;
        }

        $bank_id = isset( $bank_data['id'] ) ? sanitize_key( (string) $bank_data['id'] ) : '';
        if ( '' === $bank_id || isset( $seen_ids[ $bank_id ] ) ) {
            $bank_id = sanitize_key( 'bank_' . wp_generate_uuid4() );
        }
        $seen_ids[ $bank_id ] = true;

        $account_name = isset( $bank_data['account_name'] ) ? sanitize_text_field( wp_unslash( (string) $bank_data['account_name'] ) ) : '';
        $bank_name    = isset( $bank_data['bank_name'] ) ? sanitize_text_field( wp_unslash( (string) $bank_data['bank_name'] ) ) : '';
        $account_no   = isset( $bank_data['account_number'] ) ? sanitize_text_field( wp_unslash( (string) $bank_data['account_number'] ) ) : '';
        $account_no   = (string) preg_replace( '/[^0-9A-Za-z\-\s]/', '', $account_no );

        if ( '' === trim( $account_name . $bank_name . $account_no ) ) {
            continue;
        }

        $normalized[] = array(
            'id'             => $bank_id,
            'account_name'   => $account_name,
            'bank_name'      => $bank_name,
            'account_number' => $account_no,
        );
    }

    return $normalized;
}

function noyona_get_account_bank_accounts( $user_id ) {
    $user_id = absint( $user_id );
    if ( $user_id < 1 ) {
        return array();
    }

    $stored_data = get_user_meta( $user_id, 'noyona_account_bank_accounts', true );
    if ( ! is_array( $stored_data ) ) {
        return array();
    }

    return noyona_normalize_account_bank_accounts( $stored_data );
}

function noyona_mask_account_number( $raw_value ) {
    $raw_value = trim( (string) $raw_value );
    if ( '' === $raw_value ) {
        return '&mdash;';
    }

    $compact = preg_replace( '/\s+/', '', $raw_value );
    if ( ! is_string( $compact ) || '' === $compact ) {
        return esc_html( $raw_value );
    }

    if ( strlen( $compact ) <= 4 ) {
        return esc_html( $compact );
    }

    $masked = str_repeat( '*', max( 0, strlen( $compact ) - 4 ) ) . substr( $compact, -4 );

    return esc_html( trim( chunk_split( $masked, 4, ' ' ) ) );
}

function noyona_detect_card_brand( $card_number ) {
    $digits = preg_replace( '/\D+/', '', (string) $card_number );
    if ( ! is_string( $digits ) || '' === $digits ) {
        return 'card';
    }

    if ( 0 === strpos( $digits, '4' ) ) {
        return 'visa';
    }

    if ( preg_match( '/^(5[1-5]|2[2-7])/', $digits ) ) {
        return 'mastercard';
    }

    if ( preg_match( '/^(34|37)/', $digits ) ) {
        return 'amex';
    }

    return 'card';
}

function noyona_normalize_account_cards( $cards ) {
    $normalized = array();
    $seen_ids   = array();

    foreach ( (array) $cards as $card_data ) {
        if ( ! is_array( $card_data ) ) {
            continue;
        }

        $card_id = isset( $card_data['id'] ) ? sanitize_key( (string) $card_data['id'] ) : '';
        if ( '' === $card_id || isset( $seen_ids[ $card_id ] ) ) {
            $card_id = sanitize_key( 'card_' . wp_generate_uuid4() );
        }
        $seen_ids[ $card_id ] = true;

        $card_last4 = isset( $card_data['card_last4'] ) ? preg_replace( '/\D+/', '', (string) $card_data['card_last4'] ) : '';
        if ( '' === $card_last4 && isset( $card_data['card_number'] ) ) {
            $digits = preg_replace( '/\D+/', '', (string) $card_data['card_number'] );
            if ( is_string( $digits ) && '' !== $digits ) {
                $card_last4 = substr( $digits, -4 );
            }
        }

        if ( is_string( $card_last4 ) && strlen( $card_last4 ) > 4 ) {
            $card_last4 = substr( $card_last4, -4 );
        }

        $expiry_date  = isset( $card_data['expiry_date'] ) ? sanitize_text_field( wp_unslash( (string) $card_data['expiry_date'] ) ) : '';
        $name_on_card = isset( $card_data['name_on_card'] ) ? sanitize_text_field( wp_unslash( (string) $card_data['name_on_card'] ) ) : '';
        $card_brand   = isset( $card_data['card_brand'] ) ? sanitize_key( (string) $card_data['card_brand'] ) : '';

        if ( '' === trim( (string) $card_last4 . $expiry_date . $name_on_card ) ) {
            continue;
        }

        if ( '' === $card_brand ) {
            $card_brand = 'card';
        }

        $normalized[] = array(
            'id'           => $card_id,
            'card_last4'   => (string) $card_last4,
            'expiry_date'  => $expiry_date,
            'name_on_card' => $name_on_card,
            'card_brand'   => $card_brand,
        );
    }

    return $normalized;
}

function noyona_get_account_cards( $user_id ) {
    $user_id = absint( $user_id );
    if ( $user_id < 1 ) {
        return array();
    }

    $stored_data = get_user_meta( $user_id, 'noyona_account_cards', true );
    if ( ! is_array( $stored_data ) ) {
        return array();
    }

    return noyona_normalize_account_cards( $stored_data );
}

function noyona_format_masked_card_number( $card_last4 ) {
    $card_last4 = preg_replace( '/\D+/', '', (string) $card_last4 );
    if ( ! is_string( $card_last4 ) || '' === $card_last4 ) {
        return '&mdash;';
    }

    return esc_html( '**** **** **** ' . substr( $card_last4, -4 ) );
}

add_shortcode( 'noyona_account_page', 'noyona_render_account_page_shortcode' );
function noyona_render_account_page_shortcode() {
    if ( ! is_user_logged_in() ) {
        return do_shortcode( '[woocommerce_my_account]' );
    }

    $has_endpoint = false;
    $active_tab   = 'profile';
    // Temporary product decision: hide saved bank/card management from account UI.
    $show_payments_tab = false;
    if ( function_exists( 'WC' ) && WC() && isset( WC()->query ) && function_exists( 'is_wc_endpoint_url' ) ) {
        $endpoint_keys = array_keys( (array) WC()->query->get_query_vars() );
        foreach ( $endpoint_keys as $endpoint_key ) {
            if ( is_wc_endpoint_url( (string) $endpoint_key ) ) {
                $has_endpoint = true;
                break;
            }
        }
    }

    $is_orders_endpoint    = function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'orders' );
    $is_addresses_endpoint = function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'edit-address' );
    $is_payment_methods_endpoint = function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'payment-methods' );
    $is_payments_endpoint        = $show_payments_tab && $is_payment_methods_endpoint;
    if ( $is_orders_endpoint ) {
        $active_tab = 'orders';
    } elseif ( $is_addresses_endpoint ) {
        $active_tab = 'addresses';
    } elseif ( $is_payments_endpoint ) {
        $active_tab = 'payments';
    }

    // Keep /payment-methods reachable but render the profile tab while payments are disabled.
    if ( $is_payment_methods_endpoint && ! $show_payments_tab ) {
        $has_endpoint = false;
    }

    // Keep other Woo endpoint pages functional (addresses, payment methods, edit account, etc.).
    if ( $has_endpoint && ! $is_orders_endpoint && ! $is_addresses_endpoint && ! $is_payments_endpoint ) {
        return do_shortcode( '[woocommerce_my_account]' );
    }

    $current_user = wp_get_current_user();
    $full_name    = trim( (string) $current_user->display_name );
    if ( '' === $full_name ) {
        $full_name = trim( (string) $current_user->first_name . ' ' . (string) $current_user->last_name );
    }
    if ( '' === $full_name ) {
        $full_name = __( 'My Account', 'noyona-childtheme' );
    }

    $email = trim( (string) $current_user->user_email );
    $phone = trim( (string) get_user_meta( (int) $current_user->ID, 'billing_phone', true ) );

    $avatar_url       = get_avatar_url( (int) $current_user->ID, array( 'size' => 240 ) );
    $custom_avatar_id = absint( get_user_meta( (int) $current_user->ID, 'noyona_account_avatar_id', true ) );
    if ( $custom_avatar_id > 0 ) {
        $custom_avatar_url = wp_get_attachment_image_url( $custom_avatar_id, 'medium' );
        if ( is_string( $custom_avatar_url ) && '' !== trim( $custom_avatar_url ) ) {
            $avatar_url = $custom_avatar_url;
        }
    }
    if ( '' === trim( (string) $avatar_url ) ) {
        $avatar_url = trailingslashit( get_stylesheet_directory_uri() ) . 'assets/images/noyona-mobile-logo.webp';
    }

    $account_url = noyona_get_account_page_url();
    $orders_url = function_exists( 'wc_get_account_endpoint_url' ) ? wc_get_account_endpoint_url( 'orders' ) : home_url( '/my-account/orders/' );
    $addresses_url = noyona_get_account_addresses_url();
    $payments_url = noyona_get_account_payments_url();
    $logout_url = function_exists( 'wc_logout_url' ) ? wc_logout_url( $account_url ) : wp_logout_url( $account_url );
    $hero_edit_url = in_array( $active_tab, array( 'orders', 'addresses', 'payments' ), true )
        ? add_query_arg( 'noyona_modal', 'edit', $account_url )
        : '#noyona-account-edit-modal';
    $active_modal = isset( $_GET['noyona_modal'] ) ? sanitize_key( wp_unslash( $_GET['noyona_modal'] ) ) : '';
    $notice_code  = isset( $_GET['noyona_account_notice'] ) ? sanitize_key( wp_unslash( $_GET['noyona_account_notice'] ) ) : '';
    $notice_map   = array(
        'profile_updated'       => array( 'type' => 'success', 'message' => __( 'Profile details updated successfully.', 'noyona-childtheme' ) ),
        'password_updated'      => array( 'type' => 'success', 'message' => __( 'Password updated successfully.', 'noyona-childtheme' ) ),
        'invalid_nonce'         => array( 'type' => 'error', 'message' => __( 'Session expired. Please try again.', 'noyona-childtheme' ) ),
        'missing_fields'        => array( 'type' => 'error', 'message' => __( 'Please complete all required fields.', 'noyona-childtheme' ) ),
        'invalid_email'         => array( 'type' => 'error', 'message' => __( 'Please enter a valid email address.', 'noyona-childtheme' ) ),
        'email_exists'          => array( 'type' => 'error', 'message' => __( 'That email is already used by another account.', 'noyona-childtheme' ) ),
        'profile_update_failed' => array( 'type' => 'error', 'message' => __( 'Could not update your profile right now.', 'noyona-childtheme' ) ),
        'wrong_current_password' => array( 'type' => 'error', 'message' => __( 'Current password is incorrect.', 'noyona-childtheme' ) ),
        'password_too_short'    => array( 'type' => 'error', 'message' => __( 'New password must be at least 6 characters.', 'noyona-childtheme' ) ),
        'password_mismatch'     => array( 'type' => 'error', 'message' => __( 'New password and confirmation do not match.', 'noyona-childtheme' ) ),
        'password_update_failed'=> array( 'type' => 'error', 'message' => __( 'Could not update password right now.', 'noyona-childtheme' ) ),
        'address_saved'         => array( 'type' => 'success', 'message' => __( 'Address saved successfully.', 'noyona-childtheme' ) ),
        'address_deleted'       => array( 'type' => 'success', 'message' => __( 'Address deleted successfully.', 'noyona-childtheme' ) ),
        'address_not_found'     => array( 'type' => 'error', 'message' => __( 'Address could not be found.', 'noyona-childtheme' ) ),
        'bank_saved'            => array( 'type' => 'success', 'message' => __( 'Bank account saved successfully.', 'noyona-childtheme' ) ),
        'bank_deleted'          => array( 'type' => 'success', 'message' => __( 'Bank account deleted successfully.', 'noyona-childtheme' ) ),
        'bank_not_found'        => array( 'type' => 'error', 'message' => __( 'Bank account could not be found.', 'noyona-childtheme' ) ),
        'card_saved'            => array( 'type' => 'success', 'message' => __( 'Card saved successfully.', 'noyona-childtheme' ) ),
        'card_deleted'          => array( 'type' => 'success', 'message' => __( 'Card deleted successfully.', 'noyona-childtheme' ) ),
        'card_not_found'        => array( 'type' => 'error', 'message' => __( 'Card could not be found.', 'noyona-childtheme' ) ),
        'invalid_card'          => array( 'type' => 'error', 'message' => __( 'Please enter a valid card number.', 'noyona-childtheme' ) ),
    );

    $notice_type    = '';
    $notice_message = '';
    if ( isset( $notice_map[ $notice_code ] ) ) {
        $notice_type    = (string) $notice_map[ $notice_code ]['type'];
        $notice_message = (string) $notice_map[ $notice_code ]['message'];
    }

    $account_addresses = array();
    $address_form_data = array(
        'id'         => '',
        'address'    => '',
        'city'       => '',
        'province'   => '',
        'zip_code'   => '',
        'is_default' => false,
    );
    $address_modal_data   = $address_form_data;
    $is_address_modal_open = false;

    if ( 'addresses' === $active_tab ) {
        $account_addresses = noyona_get_account_saved_addresses( (int) $current_user->ID );
        $edit_address_id   = isset( $_GET['noyona_address_edit'] ) ? sanitize_key( wp_unslash( $_GET['noyona_address_edit'] ) ) : '';

        if ( empty( $account_addresses ) ) {
            $address_form_data['is_default'] = true;
        }

        if ( '' !== $edit_address_id ) {
            foreach ( $account_addresses as $account_address ) {
                if ( ! isset( $account_address['id'] ) || $edit_address_id !== (string) $account_address['id'] ) {
                    continue;
                }
                $address_modal_data = wp_parse_args( $account_address, $address_form_data );
                break;
            }
        }

        $is_address_modal_open = ( 'address' === $active_modal && '' !== (string) $address_modal_data['id'] );
    }

    $bank_accounts = array();
    $card_accounts = array();
    $bank_form_data = array(
        'account_name'   => $full_name,
        'bank_name'      => '',
        'account_number' => '',
    );
    $card_form_data = array(
        'card_number'  => '',
        'expiry_date'  => '',
        'ccv'          => '',
        'name_on_card' => $full_name,
    );
    $bank_modal_data = array(
        'id'             => '',
        'account_name'   => '',
        'bank_name'      => '',
        'account_number' => '',
    );
    $card_modal_data = array(
        'id'           => '',
        'card_number'  => '',
        'expiry_date'  => '',
        'ccv'          => '',
        'name_on_card' => '',
    );
    $is_bank_modal_open = false;
    $is_card_modal_open = false;

    if ( 'payments' === $active_tab ) {
        $bank_accounts = noyona_get_account_bank_accounts( (int) $current_user->ID );
        $card_accounts = noyona_get_account_cards( (int) $current_user->ID );

        $edit_bank_id = isset( $_GET['noyona_bank_edit'] ) ? sanitize_key( wp_unslash( $_GET['noyona_bank_edit'] ) ) : '';
        if ( '' !== $edit_bank_id ) {
            foreach ( $bank_accounts as $bank_account ) {
                if ( ! isset( $bank_account['id'] ) || $edit_bank_id !== (string) $bank_account['id'] ) {
                    continue;
                }

                $bank_modal_data = wp_parse_args( $bank_account, $bank_modal_data );
                break;
            }
        }

        $edit_card_id = isset( $_GET['noyona_card_edit'] ) ? sanitize_key( wp_unslash( $_GET['noyona_card_edit'] ) ) : '';
        if ( '' !== $edit_card_id ) {
            foreach ( $card_accounts as $card_account ) {
                if ( ! isset( $card_account['id'] ) || $edit_card_id !== (string) $card_account['id'] ) {
                    continue;
                }

                $card_modal_data = array(
                    'id'           => (string) $card_account['id'],
                    'card_number'  => '**** **** **** ' . (string) $card_account['card_last4'],
                    'expiry_date'  => isset( $card_account['expiry_date'] ) ? (string) $card_account['expiry_date'] : '',
                    'ccv'          => '',
                    'name_on_card' => isset( $card_account['name_on_card'] ) ? (string) $card_account['name_on_card'] : '',
                );
                break;
            }
        }

        $is_bank_modal_open = ( 'bank' === $active_modal && '' !== (string) $bank_modal_data['id'] );
        $is_card_modal_open = ( 'card' === $active_modal && '' !== (string) $card_modal_data['id'] );
    }

    $orders_per_page = 10;
    $orders_page     = isset( $_GET['orders_page'] ) ? absint( wp_unslash( $_GET['orders_page'] ) ) : 0;
    if ( $orders_page < 1 ) {
        $orders_page = absint( get_query_var( 'paged' ) );
    }
    if ( $orders_page < 1 ) {
        $orders_page = 1;
    }

    if ( function_exists( 'noyona_ot_get_order_status_filters' ) ) {
        $order_status_filters = (array) noyona_ot_get_order_status_filters();
    } else {
        $order_status_filters = array(
            'to-pay'        => array(
                'label'    => __( 'To Pay', 'noyona-childtheme' ),
                'icon'     => 'fa-regular fa-credit-card',
                'statuses' => array( 'pending', 'on-hold', 'failed' ),
            ),
            'to-ship'       => array(
                'label'    => __( 'To Ship', 'noyona-childtheme' ),
                'icon'     => 'fa-solid fa-box-open',
                'statuses' => array( 'processing', 'to-ship', 'to_ship' ),
            ),
            'to-receive'    => array(
                'label'    => __( 'To Receive', 'noyona-childtheme' ),
                'icon'     => 'fa-solid fa-truck-fast',
                'statuses' => array( 'to-receive', 'to_receive', 'shipped', 'in-transit', 'in_transit', 'out-for-delivery', 'out_for_delivery' ),
            ),
            'complete'      => array(
                'label'    => __( 'Complete', 'noyona-childtheme' ),
                'icon'     => 'fa-regular fa-circle-check',
                'statuses' => array( 'completed' ),
            ),
            'cancel-refund' => array(
                'label'    => __( 'Cancel / Refund', 'noyona-childtheme' ),
                'icon'     => 'fa-regular fa-circle-xmark',
                'statuses' => array( 'cancelled', 'refunded' ),
            ),
        );
    }

    if ( function_exists( 'noyona_ot_get_selected_filter' ) ) {
        $selected_order_filter = (string) noyona_ot_get_selected_filter( 'to-pay' );
    } else {
        $selected_order_filter = isset( $_GET['order_filter'] ) ? sanitize_key( wp_unslash( $_GET['order_filter'] ) ) : 'to-pay';
        if ( ! isset( $order_status_filters[ $selected_order_filter ] ) ) {
            $selected_order_filter = 'to-pay';
        }
    }

    if ( function_exists( 'noyona_ot_get_query_statuses_for_filter' ) ) {
        $selected_order_statuses = (array) noyona_ot_get_query_statuses_for_filter( $selected_order_filter );
    } else {
        $available_order_status_keys = function_exists( 'wc_get_order_statuses' ) ? array_keys( (array) wc_get_order_statuses() ) : array();
        $available_order_statuses    = array_map(
            static function ( $status_key ) {
                $status_key = (string) $status_key;
                return sanitize_key( 0 === strpos( $status_key, 'wc-' ) ? substr( $status_key, 3 ) : $status_key );
            },
            $available_order_status_keys
        );
        $selected_status_candidates = isset( $order_status_filters[ $selected_order_filter ]['statuses'] ) ? (array) $order_status_filters[ $selected_order_filter ]['statuses'] : array();
        $selected_order_statuses    = array_values(
            array_filter(
                array_map(
                    static function ( $status_slug ) {
                        return sanitize_key( (string) $status_slug );
                    },
                    $selected_status_candidates
                ),
                static function ( $status_slug ) use ( $available_order_statuses ) {
                    return in_array( $status_slug, $available_order_statuses, true );
                }
            )
        );

        // Fallback for stores without custom "to receive" statuses.
        if ( 'to-receive' === $selected_order_filter && empty( $selected_order_statuses ) && in_array( 'processing', $available_order_statuses, true ) ) {
            $selected_order_statuses = array( 'processing' );
        }
        if ( empty( $selected_order_statuses ) && in_array( 'pending', $available_order_statuses, true ) ) {
            $selected_order_statuses = array( 'pending' );
        }
    }

    $account_orders      = array();
    $account_total_pages = 1;
    if ( 'orders' === $active_tab && function_exists( 'wc_get_orders' ) ) {
        $orders_result = wc_get_orders(
            array(
                'customer_id' => (int) $current_user->ID,
                'status'      => $selected_order_statuses,
                'orderby'     => 'date',
                'order'       => 'DESC',
                'paginate'    => true,
                'limit'       => $orders_per_page,
                'page'        => $orders_page,
            )
        );

        if ( is_object( $orders_result ) && isset( $orders_result->orders ) ) {
            $account_orders = is_array( $orders_result->orders ) ? $orders_result->orders : array();
            if ( isset( $orders_result->max_num_pages ) ) {
                $account_total_pages = max( 1, (int) $orders_result->max_num_pages );
            }
        } elseif ( is_array( $orders_result ) ) {
            $account_orders = $orders_result;
        }
    }

    ob_start();
    ?>
    <div class="noyona-account-page">
        <section class="noyona-account-hero">
            <div class="noyona-account-hero__identity">
                <img class="noyona-account-avatar" src="<?php echo esc_url( $avatar_url ); ?>" alt="" loading="lazy" decoding="async" />
                <div class="noyona-account-hero__meta">
                    <h2><?php echo esc_html( $full_name ); ?></h2>
                    <p><?php echo esc_html( $email ); ?></p>
                </div>
            </div>
            <div class="noyona-account-hero__actions">
                <a class="noyona-account-btn noyona-account-btn--primary" href="<?php echo esc_url( $hero_edit_url ); ?>"><?php esc_html_e( 'Edit Profile', 'noyona-childtheme' ); ?></a>
                <a class="noyona-account-btn noyona-account-btn--ghost" href="<?php echo esc_url( $logout_url ); ?>"><?php esc_html_e( 'Log Out', 'noyona-childtheme' ); ?></a>
            </div>
        </section>

        <nav class="noyona-account-tabs" aria-label="<?php esc_attr_e( 'Account sections', 'noyona-childtheme' ); ?>">
            <a class="noyona-account-tab<?php echo ( 'orders' === $active_tab ) ? ' is-active' : ''; ?>" href="<?php echo esc_url( $orders_url ); ?>"<?php echo ( 'orders' === $active_tab ) ? ' aria-current="page"' : ''; ?>>
                <i class="fa-solid fa-clipboard-list" aria-hidden="true"></i>
                <span><?php esc_html_e( 'My Orders', 'noyona-childtheme' ); ?></span>
            </a>
            <a class="noyona-account-tab<?php echo ( 'addresses' === $active_tab ) ? ' is-active' : ''; ?>" href="<?php echo esc_url( $addresses_url ); ?>"<?php echo ( 'addresses' === $active_tab ) ? ' aria-current="page"' : ''; ?>>
                <i class="fa-solid fa-house" aria-hidden="true"></i>
                <span><?php esc_html_e( 'My Addresses', 'noyona-childtheme' ); ?></span>
            </a>
            <?php if ( $show_payments_tab ) : ?>
                <a class="noyona-account-tab<?php echo ( 'payments' === $active_tab ) ? ' is-active' : ''; ?>" href="<?php echo esc_url( $payments_url ); ?>"<?php echo ( 'payments' === $active_tab ) ? ' aria-current="page"' : ''; ?>>
                    <i class="fa-regular fa-credit-card" aria-hidden="true"></i>
                    <span><?php esc_html_e( 'Banks & Cards', 'noyona-childtheme' ); ?></span>
                </a>
            <?php endif; ?>
            <a class="noyona-account-tab<?php echo ( 'profile' === $active_tab ) ? ' is-active' : ''; ?>" href="<?php echo esc_url( $account_url ); ?>"<?php echo ( 'profile' === $active_tab ) ? ' aria-current="page"' : ''; ?>>
                <i class="fa-regular fa-user" aria-hidden="true"></i>
                <span><?php esc_html_e( 'My Profile', 'noyona-childtheme' ); ?></span>
            </a>
        </nav>

        <?php if ( 'orders' === $active_tab ) : ?>
        <section id="noyona-account-orders-panel" class="noyona-account-profile-card noyona-account-orders-card">
            <header class="noyona-account-profile-card__head noyona-account-orders-card__head">
                <div class="noyona-account-orders-card__title-wrap">
                    <h3><?php esc_html_e( 'My Orders', 'noyona-childtheme' ); ?></h3>
                    <p><?php esc_html_e( 'View and track recent orders', 'noyona-childtheme' ); ?></p>
                </div>
                <nav class="noyona-account-orders-filters" aria-label="<?php esc_attr_e( 'Order status filters', 'noyona-childtheme' ); ?>">
                    <?php foreach ( $order_status_filters as $filter_key => $filter_data ) : ?>
                        <?php
                        $is_filter_active = ( $filter_key === $selected_order_filter );
                        $filter_url       = add_query_arg(
                            array(
                                'order_filter' => $filter_key,
                                'orders_page'  => 1,
                            ),
                            $orders_url
                        );
                        $filter_icon = isset( $filter_data['icon'] ) ? (string) $filter_data['icon'] : 'fa-regular fa-circle';
                        $filter_label = isset( $filter_data['label'] ) ? (string) $filter_data['label'] : ucfirst( str_replace( '-', ' ', (string) $filter_key ) );
                        ?>
                        <a
                            class="noyona-account-orders-filter<?php echo $is_filter_active ? ' is-active' : ''; ?>"
                            href="<?php echo esc_url( $filter_url ); ?>"
                            <?php echo $is_filter_active ? 'aria-current="page"' : ''; ?>
                        >
                            <i class="<?php echo esc_attr( $filter_icon ); ?>" aria-hidden="true"></i>
                            <span><?php echo esc_html( $filter_label ); ?></span>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </header>

            <div class="noyona-account-orders-table-wrap">
                <table class="noyona-account-orders-table">
                    <thead>
                        <tr>
                            <th scope="col"><?php esc_html_e( 'Order #', 'noyona-childtheme' ); ?></th>
                            <th scope="col"><?php esc_html_e( 'Product Name', 'noyona-childtheme' ); ?></th>
                            <th scope="col"><?php esc_html_e( 'Qty', 'noyona-childtheme' ); ?></th>
                            <th scope="col"><?php esc_html_e( 'Date', 'noyona-childtheme' ); ?></th>
                            <th scope="col"><?php esc_html_e( 'Status', 'noyona-childtheme' ); ?></th>
                            <th scope="col"><?php esc_html_e( 'Total', 'noyona-childtheme' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $has_order_rows    = false;
                    $order_detail_modals = array();
                    if ( ! empty( $account_orders ) ) :
                        foreach ( $account_orders as $account_order ) :
                            if ( ! $account_order instanceof WC_Order ) {
                                continue;
                            }

                            $order_number = (string) $account_order->get_order_number();
                            $status_key   = sanitize_key( (string) $account_order->get_status() );
                            $status_label = wc_get_order_status_name( $status_key );
                            $status_class = 'noyona-account-order-status--' . sanitize_html_class( $status_key );
                            $order_date   = $account_order->get_date_created();
                            $order_items  = $account_order->get_items( 'line_item' );
                            if ( ! is_array( $order_items ) || empty( $order_items ) ) {
                                continue;
                            }

                            foreach ( $order_items as $order_item ) :
                                if ( ! $order_item instanceof WC_Order_Item_Product ) {
                                    continue;
                                }

                                $has_order_rows = true;
                                $item_product   = $order_item->get_product();
                                $item_name      = (string) $order_item->get_name();
                                $item_qty       = max( 1, (int) $order_item->get_quantity() );
                                $item_total     = $account_order->get_formatted_line_subtotal( $order_item );
                                $item_permalink = '';
                                if ( $item_product instanceof WC_Product ) {
                                    $item_permalink = (string) $item_product->get_permalink();
                                }

                                $date_label = '';
                                if ( $order_date instanceof WC_DateTime ) {
                                    $date_label = strtoupper( wp_date( 'M j, Y', $order_date->getTimestamp() ) );
                                }
                                if ( '' === $date_label ) {
                                    $date_label = '&mdash;';
                                }

                                $item_meta_data = $order_item->get_formatted_meta_data( '', true );
                                $variant_parts  = array();
                                if ( is_array( $item_meta_data ) ) {
                                    foreach ( $item_meta_data as $meta_data ) {
                                        if ( ! $meta_data instanceof WC_Meta_Data ) {
                                            continue;
                                        }
                                        if ( ! isset( $meta_data->display_value ) ) {
                                            continue;
                                        }
                                        $variant_value = trim( wp_strip_all_tags( (string) $meta_data->display_value ) );
                                        if ( '' !== $variant_value ) {
                                            $variant_parts[] = $variant_value;
                                        }
                                    }
                                }
                                $variant_label = ! empty( $variant_parts ) ? implode( ', ', array_unique( $variant_parts ) ) : '&mdash;';
                                $modal_id      = 'noyona-account-order-modal-' . absint( $account_order->get_id() ) . '-' . absint( $order_item->get_id() );
                                $modal_title   = sprintf( __( 'Order #%s', 'noyona-childtheme' ), $order_number );

                                $shipping_name = trim( (string) $account_order->get_shipping_first_name() . ' ' . (string) $account_order->get_shipping_last_name() );
                                if ( '' === $shipping_name ) {
                                    $shipping_name = trim( (string) $account_order->get_billing_first_name() . ' ' . (string) $account_order->get_billing_last_name() );
                                }
                                $shipping_phone = trim( (string) $account_order->get_billing_phone() );
                                $shipping_parts = array_filter(
                                    array(
                                        $account_order->get_shipping_address_1(),
                                        $account_order->get_shipping_city(),
                                        $account_order->get_shipping_state(),
                                        $account_order->get_shipping_postcode(),
                                    )
                                );
                                if ( empty( $shipping_parts ) ) {
                                    $shipping_parts = array_filter(
                                        array(
                                            $account_order->get_billing_address_1(),
                                            $account_order->get_billing_city(),
                                            $account_order->get_billing_state(),
                                            $account_order->get_billing_postcode(),
                                        )
                                    );
                                }
                                $shipping_text = implode( ', ', array_map( 'wc_clean', $shipping_parts ) );

                                $payment_label = trim( (string) $account_order->get_payment_method_title() );
                                if ( '' === $payment_label ) {
                                    $payment_label = (string) $account_order->get_payment_method();
                                }

                                $order_subtotal      = (float) $account_order->get_subtotal();
                                $order_shipping_total = (float) $account_order->get_shipping_total() + (float) $account_order->get_shipping_tax();
                                $order_discount_total = (float) $account_order->get_discount_total();
                                $timeline_rows = array();
                                if ( function_exists( 'noyona_ot_get_timeline_rows' ) ) {
                                    $timeline_rows = (array) noyona_ot_get_timeline_rows( $account_order );
                                }
                                if ( empty( $timeline_rows ) ) {
                                    $status_progress_map = array(
                                        'pending'    => 1,
                                        'on-hold'    => 1,
                                        'failed'     => 1,
                                        'cancelled'  => 1,
                                        'processing' => 2,
                                        'to-ship'    => 2,
                                        'to-receive' => 3,
                                        'at-hub'     => 4,
                                        'rider-assigned' => 5,
                                        'out-for-delivery' => 5,
                                        'shipped'    => 5,
                                        'in-transit' => 5,
                                        'completed'  => 6,
                                        'refunded'   => 6,
                                    );
                                    $current_progress_step = isset( $status_progress_map[ $status_key ] ) ? (int) $status_progress_map[ $status_key ] : 2;
                                    $progress_steps = array(
                                        1 => __( 'Order has been placed', 'noyona-childtheme' ),
                                        2 => __( 'Noyona is preparing to ship your order', 'noyona-childtheme' ),
                                        3 => __( 'Courier has picked up your order', 'noyona-childtheme' ),
                                        4 => __( 'Order arrived at delivery hub', 'noyona-childtheme' ),
                                        5 => __( 'Out for delivery', 'noyona-childtheme' ),
                                        6 => __( 'Parcel has been delivered', 'noyona-childtheme' ),
                                    );

                                    $placed_date_label = ( $order_date instanceof WC_DateTime )
                                        ? strtoupper( wp_date( 'M d, Y', $order_date->getTimestamp() ) )
                                        : strtoupper( wp_date( 'M d, Y' ) );
                                    $status_date_obj = $account_order->get_date_completed();
                                    if ( ! $status_date_obj instanceof WC_DateTime ) {
                                        $status_date_obj = $account_order->get_date_modified();
                                    }
                                    $status_date_label = ( $status_date_obj instanceof WC_DateTime )
                                        ? strtoupper( wp_date( 'M d, Y', $status_date_obj->getTimestamp() ) )
                                        : $placed_date_label;

                                    foreach ( $progress_steps as $step_index => $step_label ) {
                                        $step_state = ( $step_index < $current_progress_step ) ? 'is-complete' : ( ( $step_index === $current_progress_step ) ? 'is-current' : 'is-pending' );
                                        $step_date  = '';
                                        if ( 1 === (int) $step_index ) {
                                            $step_date = $placed_date_label;
                                        } elseif ( (int) $step_index <= $current_progress_step ) {
                                            $step_date = $status_date_label;
                                        }

                                        $timeline_rows[] = array(
                                            'label'      => (string) $step_label,
                                            'date_label' => (string) $step_date,
                                            'state'      => (string) $step_state,
                                        );
                                    }
                                }

                                $review_url = ( '' !== trim( $item_permalink ) )
                                    ? $item_permalink . '#reviews'
                                    : $account_order->get_view_order_url();
                                $contact_url = home_url( '/contact/' );
                                $invoice_url = wp_nonce_url(
                                    add_query_arg(
                                        array(
                                            'action'   => 'noyona_download_einvoice',
                                            'order_id' => (int) $account_order->get_id(),
                                        ),
                                        admin_url( 'admin-post.php' )
                                    ),
                                    'noyona_download_einvoice_' . (int) $account_order->get_id(),
                                    'noyona_download_nonce'
                                );
                                $buy_again_url = '';
                                if ( $item_product instanceof WC_Product ) {
                                    $buy_again_url = add_query_arg(
                                        array(
                                            'add-to-cart' => (int) $item_product->get_id(),
                                            'quantity'    => $item_qty,
                                        ),
                                        function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' )
                                    );
                                }
                                if ( '' === trim( (string) $buy_again_url ) ) {
                                    $buy_again_url = ( '' !== trim( $item_permalink ) )
                                        ? $item_permalink
                                        : ( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ) );
                                }
                                $is_to_pay_status = in_array( $status_key, array( 'pending', 'on-hold', 'failed', 'to-pay' ), true );
                                $pay_now_url      = '';
                                if ( method_exists( $account_order, 'get_checkout_payment_url' ) ) {
                                    $pay_now_url = (string) $account_order->get_checkout_payment_url();
                                    if ( '' !== trim( $pay_now_url ) ) {
                                        $pay_now_url = add_query_arg( 'noyona_auto_pay', '1', $pay_now_url );
                                    }
                                }
                                $show_pay_now = ( $is_to_pay_status && ! $account_order->is_paid() && '' !== trim( $pay_now_url ) );

                                $item_thumb = '';
                                if ( $item_product instanceof WC_Product ) {
                                    $item_thumb = $item_product->get_image(
                                        'thumbnail',
                                        array(
                                            'class'    => 'noyona-account-order-product-thumb',
                                            'loading'  => 'lazy',
                                            'decoding' => 'async',
                                        )
                                    );
                                }
                                if ( '' === trim( (string) $item_thumb ) ) {
                                    $item_thumb = wc_placeholder_img(
                                        'thumbnail',
                                        array(
                                            'class'    => 'noyona-account-order-product-thumb',
                                            'loading'  => 'lazy',
                                            'decoding' => 'async',
                                        )
                                    );
                                }

                                ob_start();
                                ?>
                                <div id="<?php echo esc_attr( $modal_id ); ?>" class="noyona-account-modal noyona-account-order-modal" aria-hidden="true">
                                    <a href="#noyona-account-orders-panel" class="noyona-account-modal-backdrop" aria-label="<?php esc_attr_e( 'Close order details', 'noyona-childtheme' ); ?>"></a>
                                    <div class="noyona-account-modal-dialog noyona-account-order-modal__dialog" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr( $modal_title ); ?>">
                                        <div class="noyona-account-order-modal__header">
                                            <a href="#noyona-account-orders-panel" class="noyona-account-modal-back" aria-label="<?php esc_attr_e( 'Close order details', 'noyona-childtheme' ); ?>">
                                                <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                                                <span><?php esc_html_e( 'Back', 'noyona-childtheme' ); ?></span>
                                            </a>
                                            <p class="noyona-account-order-modal__order-number"><?php echo esc_html( $modal_title ); ?></p>
                                        </div>

                                        <div class="noyona-account-order-modal__top">
                                            <section class="noyona-account-order-modal__ship">
                                                <h4><?php esc_html_e( 'Shipping Address', 'noyona-childtheme' ); ?></h4>
                                                <?php if ( '' !== $shipping_name ) : ?>
                                                    <p class="noyona-account-order-modal__ship-name"><?php echo esc_html( $shipping_name ); ?></p>
                                                <?php endif; ?>
                                                <?php if ( '' !== $shipping_phone ) : ?>
                                                    <p class="noyona-account-order-modal__ship-phone"><?php echo esc_html( $shipping_phone ); ?></p>
                                                <?php endif; ?>
                                                <?php if ( '' !== trim( $shipping_text ) ) : ?>
                                                    <p class="noyona-account-order-modal__ship-address"><?php echo esc_html( $shipping_text ); ?></p>
                                                <?php endif; ?>

                                                <?php
                                                $noyona_carrier_info = function_exists( 'noyona_ot_get_carrier_info' ) ? (array) noyona_ot_get_carrier_info( $account_order ) : array();
                                                $noyona_has_tracking = ! empty( $noyona_carrier_info['has_tracking'] );
                                                ?>
                                                <?php if ( $noyona_has_tracking ) : ?>
                                                    <div class="noyona-account-order-modal__track">
                                                        <p class="noyona-account-order-modal__track-label"><?php esc_html_e( 'Package Tracking', 'noyona-childtheme' ); ?></p>
                                                        <p class="noyona-account-order-modal__track-meta">
                                                            <?php
                                                            $noyona_carrier_label  = trim( (string) $noyona_carrier_info['label'] );
                                                            $noyona_carrier_number = trim( (string) $noyona_carrier_info['number'] );
                                                            $noyona_meta_parts     = array_filter( array( $noyona_carrier_label, $noyona_carrier_number ) );
                                                            echo esc_html( implode( ' — ', $noyona_meta_parts ) );
                                                            ?>
                                                        </p>
                                                        <a class="noyona-account-btn noyona-account-btn--primary noyona-account-order-modal__track-btn"
                                                            href="<?php echo esc_url( (string) $noyona_carrier_info['url'] ); ?>"
                                                            target="_blank" rel="noopener noreferrer">
                                                            <?php esc_html_e( 'Track Package', 'noyona-childtheme' ); ?>
                                                        </a>
                                                        <?php if ( ! empty( $noyona_carrier_info['note'] ) ) : ?>
                                                            <p class="noyona-account-order-modal__track-note">
                                                                <?php echo esc_html( (string) $noyona_carrier_info['note'] ); ?>
                                                            </p>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </section>

                                            <section class="noyona-account-order-modal__progress">
                                                <h4><?php esc_html_e( 'Order Progress', 'noyona-childtheme' ); ?></h4>
                                                <ol class="noyona-account-order-modal__timeline">
                                                    <?php foreach ( $timeline_rows as $timeline_row ) : ?>
                                                        <?php
                                                        $step_state = isset( $timeline_row['state'] ) ? sanitize_html_class( (string) $timeline_row['state'] ) : 'is-pending';
                                                        $step_date  = isset( $timeline_row['date_label'] ) ? (string) $timeline_row['date_label'] : '';
                                                        $step_label = isset( $timeline_row['label'] ) ? (string) $timeline_row['label'] : '';
                                                        ?>
                                                        <li class="noyona-account-order-modal__timeline-item <?php echo esc_attr( $step_state ); ?>">
                                                            <span class="noyona-account-order-modal__timeline-dot" aria-hidden="true"></span>
                                                            <span class="noyona-account-order-modal__timeline-copy">
                                                                <?php if ( '' !== $step_date ) : ?>
                                                                    <small><?php echo esc_html( $step_date ); ?></small>
                                                                <?php endif; ?>
                                                                <strong><?php echo esc_html( $step_label ); ?></strong>
                                                            </span>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ol>
                                            </section>
                                        </div>

                                        <section class="noyona-account-order-modal__item-card">
                                            <span class="noyona-account-order-modal__item-media"><?php echo $item_thumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                                            <div class="noyona-account-order-modal__item-meta">
                                                <h5><?php echo esc_html( $item_name ); ?></h5>
                                                <?php if ( ! empty( $variant_parts ) ) : ?>
                                                    <p><?php printf( esc_html__( 'Variation: %s', 'noyona-childtheme' ), esc_html( $variant_label ) ); ?></p>
                                                <?php endif; ?>
                                                <p><?php printf( esc_html__( 'Qty: %d', 'noyona-childtheme' ), (int) $item_qty ); ?></p>
                                            </div>
                                            <strong class="noyona-account-order-modal__item-price"><?php echo wp_kses_post( $item_total ); ?></strong>
                                        </section>

                                        <section class="noyona-account-order-modal__totals">
                                            <div class="noyona-account-order-modal__total-row">
                                                <span><?php esc_html_e( 'Merchandise Subtotal', 'noyona-childtheme' ); ?></span>
                                                <strong><?php echo wp_kses_post( wc_price( $order_subtotal ) ); ?></strong>
                                            </div>
                                            <div class="noyona-account-order-modal__total-row">
                                                <span><?php esc_html_e( 'Shipping Fee', 'noyona-childtheme' ); ?></span>
                                                <strong><?php echo wp_kses_post( wc_price( $order_shipping_total ) ); ?></strong>
                                            </div>
                                            <div class="noyona-account-order-modal__total-row">
                                                <span><?php esc_html_e( 'Shop Voucher Applied', 'noyona-childtheme' ); ?></span>
                                                <strong><?php echo wp_kses_post( $order_discount_total > 0 ? '-' . wc_price( $order_discount_total ) : wc_price( 0 ) ); ?></strong>
                                            </div>
                                            <div class="noyona-account-order-modal__total-row">
                                                <span><?php esc_html_e( 'Payment Method', 'noyona-childtheme' ); ?></span>
                                                <strong><?php echo esc_html( $payment_label ); ?></strong>
                                            </div>
                                            <div class="noyona-account-order-modal__total-row is-grand-total">
                                                <span><?php esc_html_e( 'Order Total', 'noyona-childtheme' ); ?></span>
                                                <strong><?php echo wp_kses_post( $account_order->get_formatted_order_total() ); ?></strong>
                                            </div>
                                        </section>

                                        <div class="noyona-account-order-modal__actions">
                                            <?php if ( $show_pay_now ) : ?>
                                                <a class="noyona-account-btn noyona-account-btn--primary" href="<?php echo esc_url( $pay_now_url ); ?>"><?php esc_html_e( 'Pay Now', 'noyona-childtheme' ); ?></a>
                                            <?php endif; ?>
                                            <a class="noyona-account-btn noyona-account-btn--ghost" href="<?php echo esc_url( $review_url ); ?>"><?php esc_html_e( 'Write a Review', 'noyona-childtheme' ); ?></a>
                                            <a class="noyona-account-btn noyona-account-btn--ghost" href="<?php echo esc_url( $invoice_url ); ?>" download><?php esc_html_e( 'Download E-invoice', 'noyona-childtheme' ); ?></a>
                                            <a class="noyona-account-btn noyona-account-btn--ghost" href="<?php echo esc_url( $contact_url ); ?>"><?php esc_html_e( 'Contact Us', 'noyona-childtheme' ); ?></a>
                                            <?php if ( ! $is_to_pay_status ) : ?>
                                                <a class="noyona-account-btn noyona-account-btn--primary" href="<?php echo esc_url( $buy_again_url ); ?>"><?php esc_html_e( 'Buy Again', 'noyona-childtheme' ); ?></a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php
                                $order_detail_modals[] = (string) ob_get_clean();
                                ?>
                                <tr>
                                    <td class="noyona-account-orders-table__order"><?php echo esc_html( $order_number ); ?></td>
                                    <td>
                                        <div class="noyona-account-order-product">
                                            <span class="noyona-account-order-product__media"><?php echo $item_thumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                                            <span class="noyona-account-order-product__name">
                                                <a class="noyona-account-order-product__open-modal" href="#<?php echo esc_attr( $modal_id ); ?>"><?php echo esc_html( $item_name ); ?></a>
                                            </span>
                                        </div>
                                    </td>
                                    <td><?php echo esc_html( (string) $item_qty ); ?></td>
                                    <td><?php echo wp_kses_post( $date_label ); ?></td>
                                    <td><span class="noyona-account-order-status <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $status_label ); ?></span></td>
                                    <td class="noyona-account-orders-table__price"><?php echo wp_kses_post( $item_total ); ?></td>
                                </tr>
                                <?php
                            endforeach;
                        endforeach;
                    endif;

                    if ( ! $has_order_rows ) :
                        ?>
                        <tr>
                            <td colspan="6" class="noyona-account-orders-table__empty">
                                <?php esc_html_e( 'No orders in this status yet.', 'noyona-childtheme' ); ?>
                            </td>
                        </tr>
                        <?php
                    endif;
                    ?>
                    </tbody>
                </table>
            </div>
            <?php if ( ! empty( $order_detail_modals ) ) : ?>
                <?php echo implode( '', $order_detail_modals ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php endif; ?>

            <?php
            if ( $account_total_pages > 1 ) :
                $orders_pagination_links = paginate_links(
                    array(
                        'base'      => add_query_arg(
                            array(
                                'orders_page'  => '%#%',
                                'order_filter' => $selected_order_filter,
                            ),
                            $orders_url
                        ),
                        'format'    => '',
                        'current'   => $orders_page,
                        'total'     => $account_total_pages,
                        'mid_size'  => 1,
                        'end_size'  => 1,
                        'prev_next' => true,
                        'prev_text' => '&lsaquo;',
                        'next_text' => '&rsaquo;',
                        'type'      => 'array',
                    )
                );
                if ( is_array( $orders_pagination_links ) && ! empty( $orders_pagination_links ) ) :
                    ?>
                    <nav class="noyona-account-orders-pagination" aria-label="<?php esc_attr_e( 'Orders pagination', 'noyona-childtheme' ); ?>">
                        <?php foreach ( $orders_pagination_links as $orders_link ) : ?>
                            <?php echo wp_kses_post( $orders_link ); ?>
                        <?php endforeach; ?>
                    </nav>
                    <?php
                endif;
            endif;
            ?>
        </section>
        <?php elseif ( 'addresses' === $active_tab ) : ?>
        <section class="noyona-account-profile-card noyona-account-addresses-card">
            <header class="noyona-account-profile-card__head noyona-account-addresses-card__head">
                <div>
                    <h3><?php esc_html_e( 'My Addresses', 'noyona-childtheme' ); ?></h3>
                    <p><?php esc_html_e( 'View and add your address here', 'noyona-childtheme' ); ?></p>
                </div>
                <a class="noyona-account-btn noyona-account-btn--primary noyona-account-addresses-card__new-btn" href="<?php echo esc_url( remove_query_arg( array( 'noyona_address_edit', 'noyona_modal' ), $addresses_url ) ); ?>">
                    <?php esc_html_e( 'Add New Address', 'noyona-childtheme' ); ?>
                </a>    
            </header>

            <?php if ( '' !== $notice_message && ! $is_address_modal_open ) : ?>
                <p class="noyona-account-addresses-notice<?php echo ( 'success' === $notice_type ) ? ' is-success' : ' is-error'; ?>">
                    <?php echo esc_html( $notice_message ); ?>
                </p>
            <?php endif; ?>

            <form class="noyona-account-address-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'noyona_upsert_account_address', 'noyona_account_address_nonce', false ); ?>
                <input type="hidden" name="action" value="noyona_upsert_account_address" />
                <input type="hidden" name="redirect_to" value="<?php echo esc_url( remove_query_arg( array( 'noyona_address_edit', 'noyona_modal' ), $addresses_url ) ); ?>" />
                <input type="hidden" name="address_id" value="" />

                <div class="noyona-account-address-form__grid">
                    <div class="noyona-account-address-form__fields">
                        <label for="noyona-account-address-line"><?php esc_html_e( 'Address', 'noyona-childtheme' ); ?></label>
                        <input id="noyona-account-address-line" type="text" name="address" value="<?php echo esc_attr( (string) $address_form_data['address'] ); ?>" required />

                        <div class="noyona-account-address-form__row">
                            <div class="noyona-account-address-form__col">
                                <label for="noyona-account-address-city"><?php esc_html_e( 'City', 'noyona-childtheme' ); ?></label>
                                <input id="noyona-account-address-city" type="text" name="city" value="<?php echo esc_attr( (string) $address_form_data['city'] ); ?>" required />
                            </div>
                            <div class="noyona-account-address-form__col">
                                <label for="noyona-account-address-province"><?php esc_html_e( 'Province', 'noyona-childtheme' ); ?></label>
                                <?php
                                $address_form_ph_states  = ( function_exists( 'WC' ) && WC()->countries ) ? (array) WC()->countries->get_states( 'PH' ) : array();
                                $address_form_province   = (string) $address_form_data['province'];
                                ?>
                                <select id="noyona-account-address-province" name="province" required>
                                    <option value=""><?php esc_html_e( 'Select a province / state', 'noyona-childtheme' ); ?></option>
                                    <?php foreach ( $address_form_ph_states as $address_form_code => $address_form_label ) : ?>
                                        <option value="<?php echo esc_attr( (string) $address_form_code ); ?>" <?php selected( $address_form_province, (string) $address_form_code ); ?>>
                                            <?php echo esc_html( (string) $address_form_label ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <label for="noyona-account-address-zip"><?php esc_html_e( 'ZIP Code', 'noyona-childtheme' ); ?></label>
                        <input id="noyona-account-address-zip" type="text" name="zip_code" value="<?php echo esc_attr( (string) $address_form_data['zip_code'] ); ?>" required />

                        <label class="noyona-account-address-form__default">
                            <input type="checkbox" name="is_default" value="1"<?php checked( ! empty( $address_form_data['is_default'] ) ); ?> />
                            <span><?php esc_html_e( 'Set as Default Address', 'noyona-childtheme' ); ?></span>
                        </label>
                    </div>

                    <div class="noyona-account-address-form__actions">
                        <button type="submit" class="noyona-account-btn noyona-account-btn--primary">
                            <?php esc_html_e( 'Add', 'noyona-childtheme' ); ?>
                        </button>
                        <a class="noyona-account-btn noyona-account-btn--ghost" href="<?php echo esc_url( remove_query_arg( array( 'noyona_address_edit', 'noyona_modal' ), $addresses_url ) ); ?>">
                            <?php esc_html_e( 'Cancel', 'noyona-childtheme' ); ?>
                        </a>
                    </div>
                </div>
            </form>

            <div class="noyona-account-address-list">
                <?php if ( ! empty( $account_addresses ) ) : ?>
                    <?php foreach ( $account_addresses as $account_address ) : ?>
                        <?php
                        $address_id = isset( $account_address['id'] ) ? sanitize_key( (string) $account_address['id'] ) : '';
                        if ( '' === $address_id ) {
                            continue;
                        }

                        // Province is stored as a WC state code (e.g. "00"); render the human label ("Metro Manila").
                        $province_code = isset( $account_address['province'] ) ? (string) $account_address['province'] : '';
                        $province_disp = class_exists( 'Noyona_Shipping' )
                            ? Noyona_Shipping::ph_state_label_for( $province_code )
                            : $province_code;

                        $address_parts = array(
                            isset( $account_address['address'] ) ? (string) $account_address['address'] : '',
                            isset( $account_address['city'] ) ? (string) $account_address['city'] : '',
                            $province_disp,
                            isset( $account_address['zip_code'] ) ? (string) $account_address['zip_code'] : '',
                        );
                        $address_parts = array_filter(
                            $address_parts,
                            static function ( $part ) {
                                return '' !== trim( (string) $part );
                            }
                        );

                        $address_line     = implode( ', ', $address_parts );
                        $edit_address_url = add_query_arg(
                            array(
                                'noyona_modal'        => 'address',
                                'noyona_address_edit' => $address_id,
                            ),
                            $addresses_url
                        );
                        $delete_address_url = wp_nonce_url(
                            add_query_arg(
                                array(
                                    'action'      => 'noyona_delete_account_address',
                                    'address_id'  => $address_id,
                                    'redirect_to' => $addresses_url,
                                ),
                                admin_url( 'admin-post.php' )
                            ),
                            'noyona_delete_account_address_' . $address_id
                        );
                        ?>
                        <article class="noyona-account-address-item">
                            <div class="noyona-account-address-item__meta">
                                <h4><?php echo esc_html( $full_name ); ?></h4>
                                <?php if ( '' !== $phone ) : ?>
                                    <p class="noyona-account-address-item__phone"><?php echo esc_html( $phone ); ?></p>
                                <?php endif; ?>
                                <p class="noyona-account-address-item__line"><?php echo esc_html( $address_line ); ?></p>
                                <?php if ( ! empty( $account_address['is_default'] ) ) : ?>
                                    <span class="noyona-account-address-item__badge"><?php esc_html_e( 'Default', 'noyona-childtheme' ); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="noyona-account-address-item__actions">
                                <a href="<?php echo esc_url( $edit_address_url ); ?>" aria-label="<?php esc_attr_e( 'Edit address', 'noyona-childtheme' ); ?>">
                                    <i class="fa-regular fa-pen-to-square" aria-hidden="true"></i>
                                    <span class="screen-reader-text"><?php esc_html_e( 'Edit address', 'noyona-childtheme' ); ?></span>
                                </a>
                                <a href="<?php echo esc_url( $delete_address_url ); ?>" onclick="return window.confirm('<?php echo esc_js( __( 'Delete this address?', 'noyona-childtheme' ) ); ?>');" aria-label="<?php esc_attr_e( 'Delete address', 'noyona-childtheme' ); ?>">
                                    <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
                                    <span class="screen-reader-text"><?php esc_html_e( 'Delete address', 'noyona-childtheme' ); ?></span>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p class="noyona-account-addresses-empty"><?php esc_html_e( 'No saved addresses yet.', 'noyona-childtheme' ); ?></p>
                <?php endif; ?>
            </div>

            <?php if ( $is_address_modal_open ) : ?>
                <div
                    id="noyona-account-address-modal"
                    class="noyona-account-modal is-open"
                    aria-hidden="false"
                >
                    <a href="<?php echo esc_url( remove_query_arg( array( 'noyona_modal', 'noyona_address_edit' ), $addresses_url ) ); ?>" class="noyona-account-modal-backdrop" aria-label="<?php esc_attr_e( 'Close modal', 'noyona-childtheme' ); ?>"></a>
                    <div class="noyona-account-modal-dialog" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Edit address', 'noyona-childtheme' ); ?>">
                        <a href="<?php echo esc_url( remove_query_arg( array( 'noyona_modal', 'noyona_address_edit' ), $addresses_url ) ); ?>" class="noyona-account-modal-back">
                            <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                        </a>
                        <h4 class="noyona-account-modal-title"><?php esc_html_e( 'Edit My Address', 'noyona-childtheme' ); ?></h4>

                        <?php if ( 'address' === $active_modal && '' !== $notice_message ) : ?>
                            <p class="noyona-account-modal-notice<?php echo ( 'success' === $notice_type ) ? ' is-success' : ' is-error'; ?>">
                                <?php echo esc_html( $notice_message ); ?>
                            </p>
                        <?php endif; ?>

                        <form class="noyona-account-modal-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                            <?php wp_nonce_field( 'noyona_upsert_account_address', 'noyona_account_address_nonce', false ); ?>
                            <input type="hidden" name="action" value="noyona_upsert_account_address" />
                            <input type="hidden" name="redirect_to" value="<?php echo esc_url( remove_query_arg( array( 'noyona_modal', 'noyona_address_edit' ), $addresses_url ) ); ?>" />
                            <input type="hidden" name="address_id" value="<?php echo esc_attr( (string) $address_modal_data['id'] ); ?>" />

                            <label for="noyona-account-edit-address-line"><?php esc_html_e( 'Address', 'noyona-childtheme' ); ?></label>
                            <input id="noyona-account-edit-address-line" type="text" name="address" value="<?php echo esc_attr( (string) $address_modal_data['address'] ); ?>" required />

                            <label for="noyona-account-edit-address-city"><?php esc_html_e( 'City', 'noyona-childtheme' ); ?></label>
                            <input id="noyona-account-edit-address-city" type="text" name="city" value="<?php echo esc_attr( (string) $address_modal_data['city'] ); ?>" required />

                            <label for="noyona-account-edit-address-province"><?php esc_html_e( 'Province', 'noyona-childtheme' ); ?></label>
                            <?php
                            $modal_ph_states = ( function_exists( 'WC' ) && WC()->countries ) ? (array) WC()->countries->get_states( 'PH' ) : array();
                            $modal_province  = (string) $address_modal_data['province'];
                            ?>
                            <select id="noyona-account-edit-address-province" name="province" required>
                                <option value=""><?php esc_html_e( 'Select a province / state', 'noyona-childtheme' ); ?></option>
                                <?php foreach ( $modal_ph_states as $modal_code => $modal_label ) : ?>
                                    <option value="<?php echo esc_attr( (string) $modal_code ); ?>" <?php selected( $modal_province, (string) $modal_code ); ?>>
                                        <?php echo esc_html( (string) $modal_label ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <label for="noyona-account-edit-address-zip"><?php esc_html_e( 'Zip Code', 'noyona-childtheme' ); ?></label>
                            <input id="noyona-account-edit-address-zip" type="text" name="zip_code" value="<?php echo esc_attr( (string) $address_modal_data['zip_code'] ); ?>" required />

                            <label class="noyona-account-address-form__default">
                                <input type="checkbox" name="is_default" value="1"<?php checked( ! empty( $address_modal_data['is_default'] ) ); ?> />
                                <span><?php esc_html_e( 'Set as Default Address', 'noyona-childtheme' ); ?></span>
                            </label>

                            <div class="noyona-account-modal-actions">
                                <a href="<?php echo esc_url( remove_query_arg( array( 'noyona_modal', 'noyona_address_edit' ), $addresses_url ) ); ?>" class="noyona-account-btn noyona-account-btn--ghost"><?php esc_html_e( 'Cancel', 'noyona-childtheme' ); ?></a>
                                <button type="submit" class="noyona-account-btn noyona-account-btn--primary"><?php esc_html_e( 'Save Changes', 'noyona-childtheme' ); ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </section>
        <?php elseif ( $show_payments_tab && 'payments' === $active_tab ) : ?>
        <div class="noyona-account-payments-wrap">
            <?php if ( '' !== $notice_message && ! $is_bank_modal_open && ! $is_card_modal_open ) : ?>
                <p class="noyona-account-addresses-notice<?php echo ( 'success' === $notice_type ) ? ' is-success' : ' is-error'; ?>">
                    <?php echo esc_html( $notice_message ); ?>
                </p>
            <?php endif; ?>

            <section class="noyona-account-profile-card noyona-account-payment-card">
                <header class="noyona-account-profile-card__head noyona-account-payment-card__head">
                    <div>
                        <h3><?php esc_html_e( 'Banks', 'noyona-childtheme' ); ?></h3>
                        <p><?php esc_html_e( 'Add and view your bank account here', 'noyona-childtheme' ); ?></p>
                    </div>
                    <a class="noyona-account-btn noyona-account-btn--primary noyona-account-payment-card__new-btn" href="<?php echo esc_url( remove_query_arg( array( 'noyona_bank_edit', 'noyona_card_edit', 'noyona_modal' ), $payments_url ) ); ?>">
                        <?php esc_html_e( 'Add New Bank Account', 'noyona-childtheme' ); ?>
                    </a>
                </header>

                <form class="noyona-account-payment-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <?php wp_nonce_field( 'noyona_upsert_bank_account', 'noyona_bank_nonce', false ); ?>
                    <input type="hidden" name="action" value="noyona_upsert_bank_account" />
                    <input type="hidden" name="redirect_to" value="<?php echo esc_url( remove_query_arg( array( 'noyona_bank_edit', 'noyona_card_edit', 'noyona_modal' ), $payments_url ) ); ?>" />
                    <input type="hidden" name="bank_id" value="" />

                    <div class="noyona-account-payment-form__grid">
                        <div class="noyona-account-payment-form__fields">
                            <label for="noyona-account-bank-full-name"><?php esc_html_e( 'Full Name (e.g. John Doe)', 'noyona-childtheme' ); ?></label>
                            <input id="noyona-account-bank-full-name" type="text" name="account_name" value="<?php echo esc_attr( (string) $bank_form_data['account_name'] ); ?>" required />

                            <label for="noyona-account-bank-name"><?php esc_html_e( 'Bank Name', 'noyona-childtheme' ); ?></label>
                            <input id="noyona-account-bank-name" type="text" name="bank_name" value="<?php echo esc_attr( (string) $bank_form_data['bank_name'] ); ?>" required />

                            <label for="noyona-account-bank-number"><?php esc_html_e( 'Account No.', 'noyona-childtheme' ); ?></label>
                            <input id="noyona-account-bank-number" type="text" name="account_number" value="<?php echo esc_attr( (string) $bank_form_data['account_number'] ); ?>" required />
                        </div>

                        <div class="noyona-account-payment-form__actions">
                            <button type="submit" class="noyona-account-btn noyona-account-btn--primary">
                                <?php esc_html_e( 'Add', 'noyona-childtheme' ); ?>
                            </button>
                            <a class="noyona-account-btn noyona-account-btn--ghost" href="<?php echo esc_url( remove_query_arg( array( 'noyona_bank_edit', 'noyona_card_edit', 'noyona_modal' ), $payments_url ) ); ?>">
                                <?php esc_html_e( 'Cancel', 'noyona-childtheme' ); ?>
                            </a>
                        </div>
                    </div>
                </form>

                <div class="noyona-account-payment-list">
                    <?php if ( ! empty( $bank_accounts ) ) : ?>
                        <?php foreach ( $bank_accounts as $bank_account ) : ?>
                            <?php
                            $bank_id = isset( $bank_account['id'] ) ? sanitize_key( (string) $bank_account['id'] ) : '';
                            if ( '' === $bank_id ) {
                                continue;
                            }

                            $edit_bank_url = add_query_arg(
                                array(
                                    'noyona_modal'    => 'bank',
                                    'noyona_bank_edit' => $bank_id,
                                ),
                                remove_query_arg( array( 'noyona_card_edit', 'noyona_modal' ), $payments_url )
                            );

                            $delete_bank_url = wp_nonce_url(
                                add_query_arg(
                                    array(
                                        'action'      => 'noyona_delete_bank_account',
                                        'bank_id'     => $bank_id,
                                        'redirect_to' => $payments_url,
                                    ),
                                    admin_url( 'admin-post.php' )
                                ),
                                'noyona_delete_bank_account_' . $bank_id
                            );
                            ?>
                            <article class="noyona-account-payment-item">
                                <div class="noyona-account-payment-item__meta">
                                    <h4><?php echo esc_html( (string) $bank_account['account_name'] ); ?></h4>
                                    <p><?php echo wp_kses_post( noyona_mask_account_number( (string) $bank_account['account_number'] ) ); ?></p>
                                    <p><?php echo esc_html( (string) $bank_account['bank_name'] ); ?></p>
                                </div>
                                <div class="noyona-account-payment-item__actions">
                                    <a href="<?php echo esc_url( $edit_bank_url ); ?>" aria-label="<?php esc_attr_e( 'Edit bank account', 'noyona-childtheme' ); ?>">
                                        <i class="fa-regular fa-pen-to-square" aria-hidden="true"></i>
                                        <span class="screen-reader-text"><?php esc_html_e( 'Edit bank account', 'noyona-childtheme' ); ?></span>
                                    </a>
                                    <a href="<?php echo esc_url( $delete_bank_url ); ?>" onclick="return window.confirm('<?php echo esc_js( __( 'Delete this bank account?', 'noyona-childtheme' ) ); ?>');" aria-label="<?php esc_attr_e( 'Delete bank account', 'noyona-childtheme' ); ?>">
                                        <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
                                        <span class="screen-reader-text"><?php esc_html_e( 'Delete bank account', 'noyona-childtheme' ); ?></span>
                                    </a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <p class="noyona-account-addresses-empty"><?php esc_html_e( 'No saved bank accounts yet.', 'noyona-childtheme' ); ?></p>
                    <?php endif; ?>
                </div>
            </section>

            <section class="noyona-account-profile-card noyona-account-payment-card">
                <header class="noyona-account-profile-card__head noyona-account-payment-card__head">
                    <div>
                        <h3><?php esc_html_e( 'Credit / Debit Cards', 'noyona-childtheme' ); ?></h3>
                        <p><?php esc_html_e( 'Add and view your credit or debit card here', 'noyona-childtheme' ); ?></p>
                    </div>
                    <a class="noyona-account-btn noyona-account-btn--primary noyona-account-payment-card__new-btn" href="<?php echo esc_url( remove_query_arg( array( 'noyona_bank_edit', 'noyona_card_edit', 'noyona_modal' ), $payments_url ) ); ?>">
                        <?php esc_html_e( 'Add New Card', 'noyona-childtheme' ); ?>
                    </a>
                </header>

                <form class="noyona-account-payment-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <?php wp_nonce_field( 'noyona_upsert_card', 'noyona_card_nonce', false ); ?>
                    <input type="hidden" name="action" value="noyona_upsert_card" />
                    <input type="hidden" name="redirect_to" value="<?php echo esc_url( remove_query_arg( array( 'noyona_bank_edit', 'noyona_card_edit', 'noyona_modal' ), $payments_url ) ); ?>" />
                    <input type="hidden" name="card_id" value="" />

                    <div class="noyona-account-payment-form__grid">
                        <div class="noyona-account-payment-form__fields">
                            <label for="noyona-account-card-number"><?php esc_html_e( 'Card Number', 'noyona-childtheme' ); ?></label>
                            <input id="noyona-account-card-number" type="text" name="card_number" value="<?php echo esc_attr( (string) $card_form_data['card_number'] ); ?>" required />

                            <div class="noyona-account-payment-form__row">
                                <div class="noyona-account-payment-form__col">
                                    <label for="noyona-account-card-expiry"><?php esc_html_e( 'Expiry Date', 'noyona-childtheme' ); ?></label>
                                    <input id="noyona-account-card-expiry" type="text" name="expiry_date" placeholder="MM/YY" value="<?php echo esc_attr( (string) $card_form_data['expiry_date'] ); ?>" required />
                                </div>
                                <div class="noyona-account-payment-form__col">
                                    <label for="noyona-account-card-ccv"><?php esc_html_e( 'CCV', 'noyona-childtheme' ); ?></label>
                                    <input id="noyona-account-card-ccv" type="text" name="ccv" maxlength="4" value="<?php echo esc_attr( (string) $card_form_data['ccv'] ); ?>" />
                                </div>
                            </div>

                            <label for="noyona-account-card-name"><?php esc_html_e( 'Name on Card', 'noyona-childtheme' ); ?></label>
                            <input id="noyona-account-card-name" type="text" name="name_on_card" value="<?php echo esc_attr( (string) $card_form_data['name_on_card'] ); ?>" required />
                        </div>

                        <div class="noyona-account-payment-form__actions">
                            <button type="submit" class="noyona-account-btn noyona-account-btn--primary">
                                <?php esc_html_e( 'Add', 'noyona-childtheme' ); ?>
                            </button>
                            <a class="noyona-account-btn noyona-account-btn--ghost" href="<?php echo esc_url( remove_query_arg( array( 'noyona_bank_edit', 'noyona_card_edit', 'noyona_modal' ), $payments_url ) ); ?>">
                                <?php esc_html_e( 'Cancel', 'noyona-childtheme' ); ?>
                            </a>
                        </div>
                    </div>
                </form>

                <div class="noyona-account-payment-list">
                    <?php if ( ! empty( $card_accounts ) ) : ?>
                        <?php foreach ( $card_accounts as $card_account ) : ?>
                            <?php
                            $card_id = isset( $card_account['id'] ) ? sanitize_key( (string) $card_account['id'] ) : '';
                            if ( '' === $card_id ) {
                                continue;
                            }

                            $edit_card_url = add_query_arg(
                                array(
                                    'noyona_modal'   => 'card',
                                    'noyona_card_edit' => $card_id,
                                ),
                                remove_query_arg( array( 'noyona_bank_edit', 'noyona_modal' ), $payments_url )
                            );

                            $delete_card_url = wp_nonce_url(
                                add_query_arg(
                                    array(
                                        'action'      => 'noyona_delete_card',
                                        'card_id'     => $card_id,
                                        'redirect_to' => $payments_url,
                                    ),
                                    admin_url( 'admin-post.php' )
                                ),
                                'noyona_delete_card_' . $card_id
                            );

                            $card_brand = isset( $card_account['card_brand'] ) ? sanitize_key( (string) $card_account['card_brand'] ) : 'card';
                            ?>
                            <article class="noyona-account-payment-item">
                                <div class="noyona-account-payment-item__meta">
                                    <h4><?php echo wp_kses_post( noyona_format_masked_card_number( (string) $card_account['card_last4'] ) ); ?></h4>
                                    <p>
                                        <span><?php echo esc_html( (string) $card_account['expiry_date'] ); ?></span>
                                        <span class="noyona-account-card-brand <?php echo esc_attr( 'is-' . $card_brand ); ?>"><?php echo esc_html( strtoupper( $card_brand ) ); ?></span>
                                    </p>
                                    <p><?php echo esc_html( (string) $card_account['name_on_card'] ); ?></p>
                                </div>
                                <div class="noyona-account-payment-item__actions">
                                    <a href="<?php echo esc_url( $edit_card_url ); ?>" aria-label="<?php esc_attr_e( 'Edit card', 'noyona-childtheme' ); ?>">
                                        <i class="fa-regular fa-pen-to-square" aria-hidden="true"></i>
                                        <span class="screen-reader-text"><?php esc_html_e( 'Edit card', 'noyona-childtheme' ); ?></span>
                                    </a>
                                    <a href="<?php echo esc_url( $delete_card_url ); ?>" onclick="return window.confirm('<?php echo esc_js( __( 'Delete this card?', 'noyona-childtheme' ) ); ?>');" aria-label="<?php esc_attr_e( 'Delete card', 'noyona-childtheme' ); ?>">
                                        <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
                                        <span class="screen-reader-text"><?php esc_html_e( 'Delete card', 'noyona-childtheme' ); ?></span>
                                    </a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <p class="noyona-account-addresses-empty"><?php esc_html_e( 'No saved cards yet.', 'noyona-childtheme' ); ?></p>
                    <?php endif; ?>
                </div>
            </section>

            <?php if ( $is_bank_modal_open ) : ?>
                <div
                    id="noyona-account-bank-modal"
                    class="noyona-account-modal is-open"
                    aria-hidden="false"
                >
                    <a href="<?php echo esc_url( remove_query_arg( array( 'noyona_modal', 'noyona_bank_edit' ), $payments_url ) ); ?>" class="noyona-account-modal-backdrop" aria-label="<?php esc_attr_e( 'Close modal', 'noyona-childtheme' ); ?>"></a>
                    <div class="noyona-account-modal-dialog" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Edit bank account', 'noyona-childtheme' ); ?>">
                        <a href="<?php echo esc_url( remove_query_arg( array( 'noyona_modal', 'noyona_bank_edit' ), $payments_url ) ); ?>" class="noyona-account-modal-back">
                            <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                        </a>
                        <h4 class="noyona-account-modal-title"><?php esc_html_e( 'Edit Bank Account', 'noyona-childtheme' ); ?></h4>

                        <?php if ( 'bank' === $active_modal && '' !== $notice_message ) : ?>
                            <p class="noyona-account-modal-notice<?php echo ( 'success' === $notice_type ) ? ' is-success' : ' is-error'; ?>">
                                <?php echo esc_html( $notice_message ); ?>
                            </p>
                        <?php endif; ?>

                        <form class="noyona-account-modal-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                            <?php wp_nonce_field( 'noyona_upsert_bank_account', 'noyona_bank_nonce', false ); ?>
                            <input type="hidden" name="action" value="noyona_upsert_bank_account" />
                            <input type="hidden" name="redirect_to" value="<?php echo esc_url( remove_query_arg( array( 'noyona_modal', 'noyona_bank_edit' ), $payments_url ) ); ?>" />
                            <input type="hidden" name="bank_id" value="<?php echo esc_attr( (string) $bank_modal_data['id'] ); ?>" />

                            <label for="noyona-account-edit-bank-full-name"><?php esc_html_e( 'Full Name', 'noyona-childtheme' ); ?></label>
                            <input id="noyona-account-edit-bank-full-name" type="text" name="account_name" value="<?php echo esc_attr( (string) $bank_modal_data['account_name'] ); ?>" required />

                            <label for="noyona-account-edit-bank-name"><?php esc_html_e( 'Bank Name', 'noyona-childtheme' ); ?></label>
                            <input id="noyona-account-edit-bank-name" type="text" name="bank_name" value="<?php echo esc_attr( (string) $bank_modal_data['bank_name'] ); ?>" required />

                            <label for="noyona-account-edit-bank-number"><?php esc_html_e( 'Account No.', 'noyona-childtheme' ); ?></label>
                            <input id="noyona-account-edit-bank-number" type="text" name="account_number" value="<?php echo esc_attr( (string) $bank_modal_data['account_number'] ); ?>" required />

                            <div class="noyona-account-modal-actions">
                                <a href="<?php echo esc_url( remove_query_arg( array( 'noyona_modal', 'noyona_bank_edit' ), $payments_url ) ); ?>" class="noyona-account-btn noyona-account-btn--ghost"><?php esc_html_e( 'Cancel', 'noyona-childtheme' ); ?></a>
                                <button type="submit" class="noyona-account-btn noyona-account-btn--primary"><?php esc_html_e( 'Save Changes', 'noyona-childtheme' ); ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ( $is_card_modal_open ) : ?>
                <div
                    id="noyona-account-card-modal"
                    class="noyona-account-modal is-open"
                    aria-hidden="false"
                >
                    <a href="<?php echo esc_url( remove_query_arg( array( 'noyona_modal', 'noyona_card_edit' ), $payments_url ) ); ?>" class="noyona-account-modal-backdrop" aria-label="<?php esc_attr_e( 'Close modal', 'noyona-childtheme' ); ?>"></a>
                    <div class="noyona-account-modal-dialog" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Edit credit or debit card', 'noyona-childtheme' ); ?>">
                        <a href="<?php echo esc_url( remove_query_arg( array( 'noyona_modal', 'noyona_card_edit' ), $payments_url ) ); ?>" class="noyona-account-modal-back">
                            <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                        </a>
                        <h4 class="noyona-account-modal-title"><?php esc_html_e( 'Edit Credit / Debit Card', 'noyona-childtheme' ); ?></h4>

                        <?php if ( 'card' === $active_modal && '' !== $notice_message ) : ?>
                            <p class="noyona-account-modal-notice<?php echo ( 'success' === $notice_type ) ? ' is-success' : ' is-error'; ?>">
                                <?php echo esc_html( $notice_message ); ?>
                            </p>
                        <?php endif; ?>

                        <form class="noyona-account-modal-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                            <?php wp_nonce_field( 'noyona_upsert_card', 'noyona_card_nonce', false ); ?>
                            <input type="hidden" name="action" value="noyona_upsert_card" />
                            <input type="hidden" name="redirect_to" value="<?php echo esc_url( remove_query_arg( array( 'noyona_modal', 'noyona_card_edit' ), $payments_url ) ); ?>" />
                            <input type="hidden" name="card_id" value="<?php echo esc_attr( (string) $card_modal_data['id'] ); ?>" />

                            <label for="noyona-account-edit-card-number"><?php esc_html_e( 'Card Number (MMYY)', 'noyona-childtheme' ); ?></label>
                            <input id="noyona-account-edit-card-number" type="text" name="card_number" value="<?php echo esc_attr( (string) $card_modal_data['card_number'] ); ?>" required />

                            <div class="noyona-account-modal-form__row">
                                <div class="noyona-account-modal-form__col">
                                    <label for="noyona-account-edit-card-expiry"><?php esc_html_e( 'Expiry Date', 'noyona-childtheme' ); ?></label>
                                    <input id="noyona-account-edit-card-expiry" type="text" name="expiry_date" value="<?php echo esc_attr( (string) $card_modal_data['expiry_date'] ); ?>" required />
                                </div>
                                <div class="noyona-account-modal-form__col">
                                    <label for="noyona-account-edit-card-ccv"><?php esc_html_e( 'CCV', 'noyona-childtheme' ); ?></label>
                                    <input id="noyona-account-edit-card-ccv" type="text" name="ccv" maxlength="4" value="<?php echo esc_attr( (string) $card_modal_data['ccv'] ); ?>" />
                                </div>
                            </div>

                            <label for="noyona-account-edit-card-name"><?php esc_html_e( 'Name on Card', 'noyona-childtheme' ); ?></label>
                            <input id="noyona-account-edit-card-name" type="text" name="name_on_card" value="<?php echo esc_attr( (string) $card_modal_data['name_on_card'] ); ?>" required />

                            <div class="noyona-account-modal-actions">
                                <a href="<?php echo esc_url( remove_query_arg( array( 'noyona_modal', 'noyona_card_edit' ), $payments_url ) ); ?>" class="noyona-account-btn noyona-account-btn--ghost"><?php esc_html_e( 'Cancel', 'noyona-childtheme' ); ?></a>
                                <button type="submit" class="noyona-account-btn noyona-account-btn--primary"><?php esc_html_e( 'Save Changes', 'noyona-childtheme' ); ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php else : ?>
        <section class="noyona-account-profile-card">
            <header class="noyona-account-profile-card__head">
                <h3><?php esc_html_e( 'My Profile', 'noyona-childtheme' ); ?></h3>
                <p><?php esc_html_e( 'Manage and protect your account', 'noyona-childtheme' ); ?></p>
            </header>

            <div class="noyona-account-profile-card__body">
                <div class="noyona-account-profile-fields">
                    <div class="noyona-account-field">
                        <label><?php esc_html_e( 'Full Name:', 'noyona-childtheme' ); ?></label>
                        <input type="text" value="<?php echo esc_attr( $full_name ); ?>" readonly />
                    </div>
                    <div class="noyona-account-field">
                        <label><?php esc_html_e( 'Email:', 'noyona-childtheme' ); ?></label>
                        <input type="text" value="<?php echo esc_attr( $email ); ?>" readonly />
                    </div>
                    <div class="noyona-account-field">
                        <label><?php esc_html_e( 'Phone #:', 'noyona-childtheme' ); ?></label>
                        <input type="text" value="<?php echo esc_attr( $phone ); ?>" readonly />
                    </div>
                    <div class="noyona-account-field">
                        <label><?php esc_html_e( 'Password:', 'noyona-childtheme' ); ?></label>
                        <input type="password" value="************" readonly />
                    </div>

                    <div class="noyona-account-profile-actions">
                        <a class="noyona-account-btn noyona-account-btn--ghost" href="#noyona-account-password-modal"><?php esc_html_e( 'Change Password', 'noyona-childtheme' ); ?></a>
                        <a class="noyona-account-btn noyona-account-btn--primary" href="#noyona-account-edit-modal"><?php esc_html_e( 'Edit Profile Details', 'noyona-childtheme' ); ?></a>
                    </div>
                </div>

                <aside class="noyona-account-profile-avatar">
                    <img src="<?php echo esc_url( $avatar_url ); ?>" alt="" loading="lazy" decoding="async" />
                    <form class="noyona-account-avatar-upload" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
                        <?php wp_nonce_field( 'noyona_upload_account_avatar', 'noyona_avatar_nonce', false ); ?>
                        <input type="hidden" name="action" value="noyona_upload_account_avatar" />
                        <input type="hidden" name="redirect_to" value="<?php echo esc_url( $account_url ); ?>" />
                        <input
                            id="noyona-account-avatar-input"
                            class="noyona-account-avatar-input"
                            type="file"
                            name="noyona_account_avatar"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            onchange="this.form.submit()"
                            required
                        />
                        <label for="noyona-account-avatar-input" class="noyona-account-btn noyona-account-btn--ghost">
                            <?php esc_html_e( 'Select Image', 'noyona-childtheme' ); ?>
                        </label>
                    </form>
                    <p><?php esc_html_e( 'File Size: maximum 1 MB', 'noyona-childtheme' ); ?></p>
                    <p><?php esc_html_e( 'File type: JPG, PNG, WEBP', 'noyona-childtheme' ); ?></p>
                </aside>
            </div>
        </section>

        <div
            id="noyona-account-edit-modal"
            class="noyona-account-modal<?php echo ( 'edit' === $active_modal ) ? ' is-open' : ''; ?>"
            aria-hidden="<?php echo ( 'edit' === $active_modal ) ? 'false' : 'true'; ?>"
        >
            <a href="#" class="noyona-account-modal-backdrop" aria-label="<?php esc_attr_e( 'Close modal', 'noyona-childtheme' ); ?>"></a>
            <div class="noyona-account-modal-dialog" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Edit profile details', 'noyona-childtheme' ); ?>">
                <a href="#" class="noyona-account-modal-back">
                    <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                </a>
                <h4 class="noyona-account-modal-title"><?php esc_html_e( 'Edit Profile Details', 'noyona-childtheme' ); ?></h4>

                <?php if ( 'edit' === $active_modal && '' !== $notice_message ) : ?>
                    <p class="noyona-account-modal-notice<?php echo ( 'success' === $notice_type ) ? ' is-success' : ' is-error'; ?>">
                        <?php echo esc_html( $notice_message ); ?>
                    </p>
                <?php endif; ?>

                <form class="noyona-account-modal-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <?php wp_nonce_field( 'noyona_update_account_profile', 'noyona_account_profile_nonce', false ); ?>
                    <input type="hidden" name="action" value="noyona_update_account_profile" />
                    <input type="hidden" name="redirect_to" value="<?php echo esc_url( $account_url ); ?>" />

                    <label for="noyona-account-modal-full-name"><?php esc_html_e( 'Full Name', 'noyona-childtheme' ); ?> <span aria-hidden="true">*</span></label>
                    <input id="noyona-account-modal-full-name" type="text" name="full_name" value="<?php echo esc_attr( $full_name ); ?>" required />

                    <label for="noyona-account-modal-email"><?php esc_html_e( 'Email', 'noyona-childtheme' ); ?> <span aria-hidden="true">*</span></label>
                    <input id="noyona-account-modal-email" type="email" name="email" value="<?php echo esc_attr( $email ); ?>" required />

                    <label for="noyona-account-modal-phone"><?php esc_html_e( 'Phone Number', 'noyona-childtheme' ); ?></label>
                    <input id="noyona-account-modal-phone" type="text" name="phone" value="<?php echo esc_attr( $phone ); ?>" />

                    <div class="noyona-account-modal-actions">
                        <a href="#" class="noyona-account-btn noyona-account-btn--ghost"><?php esc_html_e( 'Cancel', 'noyona-childtheme' ); ?></a>
                        <button type="submit" class="noyona-account-btn noyona-account-btn--primary"><?php esc_html_e( 'Save Changes', 'noyona-childtheme' ); ?></button>
                    </div>
                </form>
            </div>
        </div>

        <div
            id="noyona-account-password-modal"
            class="noyona-account-modal<?php echo ( 'password' === $active_modal ) ? ' is-open' : ''; ?>"
            aria-hidden="<?php echo ( 'password' === $active_modal ) ? 'false' : 'true'; ?>"
        >
            <a href="#" class="noyona-account-modal-backdrop" aria-label="<?php esc_attr_e( 'Close modal', 'noyona-childtheme' ); ?>"></a>
            <div class="noyona-account-modal-dialog" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Change password', 'noyona-childtheme' ); ?>">
                <a href="#" class="noyona-account-modal-back">
                    <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                </a>
                <h4 class="noyona-account-modal-title"><?php esc_html_e( 'Change Password', 'noyona-childtheme' ); ?></h4>

                <?php if ( 'password' === $active_modal && '' !== $notice_message ) : ?>
                    <p class="noyona-account-modal-notice<?php echo ( 'success' === $notice_type ) ? ' is-success' : ' is-error'; ?>">
                        <?php echo esc_html( $notice_message ); ?>
                    </p>
                <?php endif; ?>

                <form class="noyona-account-modal-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <?php wp_nonce_field( 'noyona_update_account_password', 'noyona_account_password_nonce', false ); ?>
                    <input type="hidden" name="action" value="noyona_update_account_password" />
                    <input type="hidden" name="redirect_to" value="<?php echo esc_url( $account_url ); ?>" />

                    <label for="noyona-account-modal-current-password"><?php esc_html_e( 'Current Password', 'noyona-childtheme' ); ?> <span aria-hidden="true">*</span></label>
                    <div class="noyona-account-modal-password-wrap">
                        <input id="noyona-account-modal-current-password" type="password" name="current_password" placeholder="<?php esc_attr_e( 'Enter current password', 'noyona-childtheme' ); ?>" required />
                        <button type="button" class="noyona-account-modal-password-toggle" data-toggle-password="#noyona-account-modal-current-password" onclick="return window.noyonaToggleAccountPassword(this);" aria-label="<?php esc_attr_e( 'Toggle password visibility', 'noyona-childtheme' ); ?>">
                            <i class="fa-regular fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>

                    <label for="noyona-account-modal-new-password"><?php esc_html_e( 'New Password', 'noyona-childtheme' ); ?> <span aria-hidden="true">*</span></label>
                    <div class="noyona-account-modal-password-wrap">
                        <input id="noyona-account-modal-new-password" type="password" name="new_password" placeholder="<?php esc_attr_e( 'Enter new password', 'noyona-childtheme' ); ?>" minlength="6" required />
                        <button type="button" class="noyona-account-modal-password-toggle" data-toggle-password="#noyona-account-modal-new-password" onclick="return window.noyonaToggleAccountPassword(this);" aria-label="<?php esc_attr_e( 'Toggle password visibility', 'noyona-childtheme' ); ?>">
                            <i class="fa-regular fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                    <small><?php esc_html_e( 'Must be at least 6 characters long', 'noyona-childtheme' ); ?></small>

                    <label for="noyona-account-modal-confirm-password"><?php esc_html_e( 'Confirm New Password', 'noyona-childtheme' ); ?> <span aria-hidden="true">*</span></label>
                    <div class="noyona-account-modal-password-wrap">
                        <input id="noyona-account-modal-confirm-password" type="password" name="confirm_password" placeholder="<?php esc_attr_e( 'Confirm new password', 'noyona-childtheme' ); ?>" minlength="6" required />
                        <button type="button" class="noyona-account-modal-password-toggle" data-toggle-password="#noyona-account-modal-confirm-password" onclick="return window.noyonaToggleAccountPassword(this);" aria-label="<?php esc_attr_e( 'Toggle password visibility', 'noyona-childtheme' ); ?>">
                            <i class="fa-regular fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>

                    <div class="noyona-account-modal-actions">
                        <a href="#" class="noyona-account-btn noyona-account-btn--ghost"><?php esc_html_e( 'Cancel', 'noyona-childtheme' ); ?></a>
                        <button type="submit" class="noyona-account-btn noyona-account-btn--primary"><?php esc_html_e( 'Update Password', 'noyona-childtheme' ); ?></button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php if ( 'profile' === $active_tab ) : ?>
    <script>
        (function () {
            if (window.noyonaAccountModalInit) {
                return;
            }
            window.noyonaAccountModalInit = true;

            window.noyonaToggleAccountPassword = function (toggle) {
                if (!toggle) {
                    return false;
                }

                var targetSelector = toggle.getAttribute('data-toggle-password');
                if (!targetSelector) {
                    return false;
                }

                var input = document.querySelector(targetSelector);
                if (!input) {
                    return false;
                }

                var isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                toggle.classList.toggle('is-visible', isPassword);

                var icon = toggle.querySelector('i');
                if (icon) {
                    icon.classList.remove('fa-eye', 'fa-eye-slash');
                    icon.classList.add(isPassword ? 'fa-eye-slash' : 'fa-eye');
                }

                return false;
            };

            var cleanModalArtifacts = function () {
                var dialogs = document.querySelectorAll('.noyona-account-modal-dialog');
                if (!dialogs.length) {
                    return;
                }

                dialogs.forEach(function (dialog) {
                    dialog.querySelectorAll('p').forEach(function (node) {
                        var text = (node.textContent || '').replace(/\u00a0/g, ' ').trim();
                        if (text === '' && node.children.length === 0) {
                            node.remove();
                        }
                    });

                    dialog.querySelectorAll('br, [data-lastpass-icon-root], [data-lastpass-root]').forEach(function (node) {
                        node.remove();
                    });
                });
            };

            cleanModalArtifacts();

            var observer = new MutationObserver(cleanModalArtifacts);
            observer.observe(document.body, { childList: true, subtree: true });

            document.addEventListener('click', function (event) {
                var toggle = event.target.closest('[data-toggle-password]');
                if (!toggle) {
                    return;
                }

                window.noyonaToggleAccountPassword(toggle);
                cleanModalArtifacts();
            });
        })();
    </script>
    <?php endif; ?>
    <?php

    $markup = trim( (string) ob_get_clean() );

    return noyona_clean_account_markup( $markup );
}

function noyona_clean_account_markup( $html ) {
    if ( ! is_string( $html ) || '' === trim( $html ) ) {
        return '';
    }

    // First-pass cleanup for WordPress/Woo auto-injected wrappers.
    $html = preg_replace( '/<br\s*\/?>/i', '', $html );
    $html = preg_replace( '/<p\b[^>]*>(?:\s|&nbsp;|&#160;|<br\s*\/?>)*<\/p>/i', '', $html );
    $html = preg_replace(
        '/<p>\s*(<(?:a|button|label|small)\b[^>]*>.*?<\/(?:a|button|label|small)>|<input\b[^>]*\/?>)\s*<\/p>/is',
        '$1',
        $html
    );
    $html = preg_replace(
        '/<p>\s*(<(?:div|section|article|form)\b[^>]*class=(["\'])[^"\']*\bnoyona-account-[^"\']*\2[^>]*>.*?<\/(?:div|section|article|form)>)\s*<\/p>/is',
        '$1',
        $html
    );

    $targets = array(
        '/(<nav\b[^>]*class=(["\'])[^"\']*\bnoyona-account-tabs\b[^"\']*\2[^>]*>)(.*?)(<\/nav>)/is',
        '/(<div\b[^>]*class=(["\'])[^"\']*\bnoyona-account-hero__actions\b[^"\']*\2[^>]*>)(.*?)(<\/div>)/is',
        '/(<form\b[^>]*class=(["\'])[^"\']*\bnoyona-account-avatar-upload\b[^"\']*\2[^>]*>)(.*?)(<\/form>)/is',
        '/(<form\b[^>]*class=(["\'])[^"\']*\bnoyona-account-address-form\b[^"\']*\2[^>]*>)(.*?)(<\/form>)/is',
        '/(<div\b[^>]*class=(["\'])[^"\']*\bnoyona-account-address-form__grid\b[^"\']*\2[^>]*>)(.*?)(<\/div>)/is',
        '/(<div\b[^>]*class=(["\'])[^"\']*\bnoyona-account-address-form__fields\b[^"\']*\2[^>]*>)(.*?)(<\/div>)/is',
        '/(<div\b[^>]*class=(["\'])[^"\']*\bnoyona-account-address-form__row\b[^"\']*\2[^>]*>)(.*?)(<\/div>)/is',
        '/(<div\b[^>]*class=(["\'])[^"\']*\bnoyona-account-address-form__actions\b[^"\']*\2[^>]*>)(.*?)(<\/div>)/is',
        '/(<form\b[^>]*class=(["\'])[^"\']*\bnoyona-account-payment-form\b[^"\']*\2[^>]*>)(.*?)(<\/form>)/is',
        '/(<div\b[^>]*class=(["\'])[^"\']*\bnoyona-account-payment-form__grid\b[^"\']*\2[^>]*>)(.*?)(<\/div>)/is',
        '/(<div\b[^>]*class=(["\'])[^"\']*\bnoyona-account-payment-form__fields\b[^"\']*\2[^>]*>)(.*?)(<\/div>)/is',
        '/(<div\b[^>]*class=(["\'])[^"\']*\bnoyona-account-payment-form__row\b[^"\']*\2[^>]*>)(.*?)(<\/div>)/is',
        '/(<div\b[^>]*class=(["\'])[^"\']*\bnoyona-account-payment-form__actions\b[^"\']*\2[^>]*>)(.*?)(<\/div>)/is',
        '/(<div\b[^>]*class=(["\'])[^"\']*\bnoyona-account-payment-item__actions\b[^"\']*\2[^>]*>)(.*?)(<\/div>)/is',
        '/(<form\b[^>]*class=(["\'])[^"\']*\bnoyona-account-modal-form\b[^"\']*\2[^>]*>)(.*?)(<\/form>)/is',
        '/(<div\b[^>]*class=(["\'])[^"\']*\bnoyona-account-modal-actions\b[^"\']*\2[^>]*>)(.*?)(<\/div>)/is',
        '/(<div\b[^>]*class=(["\'])[^"\']*\bnoyona-account-modal-dialog\b[^"\']*\2[^>]*>)(.*?)(<\/div>)/is',
    );

    foreach ( $targets as $pattern ) {
        $html = preg_replace_callback(
            $pattern,
            static function ( $matches ) {
                $inner = isset( $matches[3] ) ? (string) $matches[3] : '';
                $inner = preg_replace( '/<br\s*\/?>/i', '', $inner );
                $inner = preg_replace( '/<p>\s*<\/p>/i', '', $inner );
                $inner = preg_replace( '/<\/?p\b[^>]*>/i', '', $inner );

                return (string) $matches[1] . (string) $inner . (string) $matches[4];
            },
            (string) $html
        );
    }

    return (string) $html;
}

add_filter( 'the_content', 'noyona_clean_account_page_artifacts', 35 );
function noyona_clean_account_page_artifacts( $content ) {
    if ( is_admin() ) {
        return $content;
    }

    if ( false === strpos( (string) $content, 'noyona-account-page' ) ) {
        return $content;
    }

    return noyona_clean_account_markup( (string) $content );
}

function noyona_is_account_recovery_context() {
    if ( function_exists( 'is_wc_endpoint_url' ) && ( is_wc_endpoint_url( 'lost-password' ) || is_wc_endpoint_url( 'reset-password' ) ) ) {
        return true;
    }

    $request_path = (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH );
    $account_path = (string) wp_parse_url( noyona_get_account_page_url(), PHP_URL_PATH );
    $request_lc   = trim( strtolower( untrailingslashit( $request_path ) ), '/' );
    $account_lc   = trim( strtolower( untrailingslashit( $account_path ) ), '/' );

    if ( '' === $request_lc || '' === $account_lc ) {
        return false;
    }

    return 0 === strpos( $request_lc, $account_lc . '/lost-password' )
        || 0 === strpos( $request_lc, $account_lc . '/reset-password' );
}

function noyona_clean_lost_password_markup( $html ) {
    if ( ! is_string( $html ) || '' === trim( $html ) ) {
        return '';
    }

    return (string) preg_replace_callback(
        '/(<form\b[^>]*class=(["\'])[^"\']*\bnoyona-lost-password-form\b[^"\']*\2[^>]*>)(.*?)(<\/form>)/is',
        static function ( $matches ) {
            $inner = isset( $matches[3] ) ? (string) $matches[3] : '';

            $inner = preg_replace( '/<br\s*\/?>/i', '', $inner );
            $inner = preg_replace( '/<p\b[^>]*>(?:\s|&nbsp;|&#160;|<br\s*\/?>)*<\/p>/i', '', $inner );

            $inner = preg_replace_callback(
                '/(<div\b[^>]*class=(["\'])[^"\']*\bnoyona-lost-password-actions\b[^"\']*\2[^>]*>)(.*?)(<\/div>)/is',
                static function ( $action_matches ) {
                    $action_inner = isset( $action_matches[3] ) ? (string) $action_matches[3] : '';
                    $action_inner = preg_replace( '/<br\s*\/?>/i', '', $action_inner );
                    $action_inner = preg_replace( '/<p\b[^>]*>(?:\s|&nbsp;|&#160;|<br\s*\/?>)*<\/p>/i', '', $action_inner );
                    $action_inner = preg_replace(
                        '/<p>\s*(<(?:a|button)\b[^>]*>.*?<\/(?:a|button)>)\s*<\/p>/is',
                        '$1',
                        (string) $action_inner
                    );

                    return (string) $action_matches[1] . (string) $action_inner . (string) $action_matches[4];
                },
                (string) $inner
            );

            $inner = preg_replace_callback(
                '/(<p\b[^>]*class=(["\'])[^"\']*\bwoocommerce-form-row\b[^"\']*\2[^>]*>)(.*?)(<\/p>)/is',
                static function ( $row_matches ) {
                    $row_inner = isset( $row_matches[3] ) ? (string) $row_matches[3] : '';
                    $row_inner = preg_replace( '/<br\s*\/?>/i', '', $row_inner );
                    $row_inner = preg_replace( '/<p\b[^>]*>(?:\s|&nbsp;|&#160;|<br\s*\/?>)*<\/p>/i', '', $row_inner );

                    return (string) $row_matches[1] . (string) $row_inner . (string) $row_matches[4];
                },
                (string) $inner
            );

            return (string) $matches[1] . (string) $inner . (string) $matches[4];
        },
        (string) $html
    );
}

add_filter( 'the_content', 'noyona_clean_lost_password_artifacts', 41 );
function noyona_clean_lost_password_artifacts( $content ) {
    if ( is_admin() || ! noyona_is_account_recovery_context() ) {
        return $content;
    }

    if ( false === strpos( (string) $content, 'noyona-lost-password-form' ) ) {
        return $content;
    }

    return noyona_clean_lost_password_markup( (string) $content );
}

add_action( 'template_redirect', 'noyona_lost_password_buffer_cleanup', 1 );
function noyona_lost_password_buffer_cleanup() {
    if ( is_admin() || wp_doing_ajax() ) {
        return;
    }

    if ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) {
        return;
    }

    if ( ! noyona_is_account_recovery_context() ) {
        return;
    }

    ob_start(
        static function ( $html ) {
            return noyona_clean_lost_password_markup( (string) $html );
        }
    );
}

add_action( 'template_redirect', 'noyona_account_page_buffer_cleanup', 1 );
function noyona_account_page_buffer_cleanup() {
    if ( is_admin() || wp_doing_ajax() ) {
        return;
    }

    if ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) {
        return;
    }

    $request_uri     = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
    $is_account_path = false !== strpos( $request_uri, '/my-account' );
    $is_account_ctx  = ( function_exists( 'is_account_page' ) && is_account_page() ) || is_page( 'my-account' ) || $is_account_path;

    if ( ! is_user_logged_in() || ! $is_account_ctx ) {
        return;
    }

    ob_start(
        static function ( $html ) {
            return noyona_clean_account_markup( (string) $html );
        }
    );
}

add_action( 'wp_footer', 'noyona_account_modal_runtime_script', 95 );
function noyona_account_modal_runtime_script() {
    if ( is_admin() || ! is_user_logged_in() ) {
        return;
    }

    $request_uri     = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
    $is_account_path = false !== strpos( $request_uri, '/my-account' );
    $is_account_ctx  = ( function_exists( 'is_account_page' ) && is_account_page() ) || is_page( 'my-account' ) || $is_account_path;

    if ( ! $is_account_ctx ) {
        return;
    }
    ?>
    <script>
        (function () {
            if (window.noyonaAccountModalRuntimeInit) {
                return;
            }
            window.noyonaAccountModalRuntimeInit = true;

            window.noyonaToggleAccountPassword = function (toggle) {
                if (!toggle) {
                    return false;
                }

                var targetSelector = toggle.getAttribute('data-toggle-password');
                if (!targetSelector) {
                    return false;
                }

                var input = document.querySelector(targetSelector);
                if (!input) {
                    return false;
                }

                var isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                toggle.classList.toggle('is-visible', isPassword);

                var icon = toggle.querySelector('i');
                if (icon) {
                    icon.classList.remove('fa-eye', 'fa-eye-slash');
                    icon.classList.add(isPassword ? 'fa-eye-slash' : 'fa-eye');
                }

                return false;
            };

            var cleanModalArtifacts = function () {
                document.querySelectorAll('.noyona-account-modal-dialog').forEach(function (dialog) {
                    dialog.querySelectorAll('p').forEach(function (node) {
                        var text = (node.textContent || '').replace(/\u00a0/g, ' ').trim();
                        if (text === '' && node.children.length === 0) {
                            node.remove();
                        }
                    });

                    dialog.querySelectorAll('br, [data-lastpass-icon-root], [data-lastpass-root]').forEach(function (node) {
                        node.remove();
                    });
                });
            };

            cleanModalArtifacts();
            var observer = new MutationObserver(cleanModalArtifacts);
            observer.observe(document.body, { childList: true, subtree: true });

            document.addEventListener('click', function (event) {
                var toggle = event.target.closest('[data-toggle-password]');
                if (!toggle) {
                    return;
                }

                event.preventDefault();
                window.noyonaToggleAccountPassword(toggle);
            });
        })();
    </script>
    <?php
}

add_action( 'admin_post_noyona_download_einvoice', 'noyona_download_einvoice_handler' );
function noyona_download_einvoice_handler() {
    if ( ! is_user_logged_in() ) {
        wp_safe_redirect( noyona_get_login_page_url() );
        exit;
    }

    $order_id = isset( $_GET['order_id'] ) ? absint( wp_unslash( $_GET['order_id'] ) ) : 0;
    $nonce    = isset( $_GET['noyona_download_nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['noyona_download_nonce'] ) ) : '';

    if ( $order_id < 1 || '' === $nonce || ! wp_verify_nonce( $nonce, 'noyona_download_einvoice_' . $order_id ) ) {
        wp_die( esc_html__( 'Invalid e-invoice request.', 'noyona-childtheme' ), esc_html__( 'Unauthorized', 'noyona-childtheme' ), array( 'response' => 403 ) );
    }

    if ( ! function_exists( 'wc_get_order' ) ) {
        wp_die( esc_html__( 'WooCommerce is required to download invoices.', 'noyona-childtheme' ) );
    }

    $order = wc_get_order( $order_id );
    if ( ! $order instanceof WC_Order ) {
        wp_die( esc_html__( 'Order not found.', 'noyona-childtheme' ), esc_html__( 'Not found', 'noyona-childtheme' ), array( 'response' => 404 ) );
    }

    $current_user_id = get_current_user_id();
    $can_manage      = current_user_can( 'manage_woocommerce' ) || current_user_can( 'edit_shop_orders' );
    if ( ! $can_manage && (int) $order->get_user_id() !== (int) $current_user_id ) {
        wp_die( esc_html__( 'You are not allowed to download this invoice.', 'noyona-childtheme' ), esc_html__( 'Unauthorized', 'noyona-childtheme' ), array( 'response' => 403 ) );
    }

    $order_created = $order->get_date_created();
    $order_date    = ( $order_created instanceof WC_DateTime ) ? $order_created->date_i18n( get_option( 'date_format' ) ) : '';

    $shipping_parts = array_filter(
        array(
            $order->get_shipping_address_1(),
            $order->get_shipping_city(),
            $order->get_shipping_state(),
            $order->get_shipping_postcode(),
        )
    );
    if ( empty( $shipping_parts ) ) {
        $shipping_parts = array_filter(
            array(
                $order->get_billing_address_1(),
                $order->get_billing_city(),
                $order->get_billing_state(),
                $order->get_billing_postcode(),
            )
        );
    }
    $shipping_text = implode( ', ', array_map( 'wc_clean', $shipping_parts ) );

    $payment_label = trim( (string) $order->get_payment_method_title() );
    if ( '' === $payment_label ) {
        $payment_label = (string) $order->get_payment_method();
    }

    $items_html = '';
    foreach ( $order->get_items( 'line_item' ) as $order_item ) {
        if ( ! $order_item instanceof WC_Order_Item_Product ) {
            continue;
        }

        $items_html .= '<tr>';
        $items_html .= '<td style="padding:8px;border-bottom:1px solid #ececec;">' . esc_html( $order_item->get_name() ) . '</td>';
        $items_html .= '<td style="padding:8px;border-bottom:1px solid #ececec;text-align:center;">' . esc_html( (string) $order_item->get_quantity() ) . '</td>';
        $items_html .= '<td style="padding:8px;border-bottom:1px solid #ececec;text-align:right;">' . wp_kses_post( $order->get_formatted_line_subtotal( $order_item ) ) . '</td>';
        $items_html .= '</tr>';
    }

    $totals_html = '';
    foreach ( (array) $order->get_order_item_totals() as $total_row ) {
        $label = isset( $total_row['label'] ) ? wp_strip_all_tags( (string) $total_row['label'] ) : '';
        $value = isset( $total_row['value'] ) ? (string) $total_row['value'] : '';

        $totals_html .= '<tr>';
        $totals_html .= '<td style="padding:8px;border-bottom:1px solid #ececec;">' . esc_html( $label ) . '</td>';
        $totals_html .= '<td style="padding:8px;border-bottom:1px solid #ececec;text-align:right;">' . wp_kses_post( $value ) . '</td>';
        $totals_html .= '</tr>';
    }

    $invoice_html  = '<!doctype html><html><head><meta charset="' . esc_attr( get_bloginfo( 'charset' ) ) . '"><meta http-equiv="Content-Type" content="text/html; charset=' . esc_attr( get_bloginfo( 'charset' ) ) . '"><title>' . esc_html__( 'Noyona E-invoice', 'noyona-childtheme' ) . '</title></head><body style="font-family:Arial,sans-serif;color:#1d1d1d;line-height:1.45;padding:28px;">';
    $invoice_html .= '<h1 style="margin:0 0 4px;">' . esc_html__( 'Noyona E-invoice', 'noyona-childtheme' ) . '</h1>';
    $invoice_html .= '<p style="margin:0 0 16px;">' . esc_html__( 'Order', 'noyona-childtheme' ) . ' #' . esc_html( $order->get_order_number() ) . '</p>';
    $invoice_html .= '<p style="margin:0 0 6px;"><strong>' . esc_html__( 'Order Date:', 'noyona-childtheme' ) . '</strong> ' . esc_html( $order_date ) . '</p>';
    $invoice_html .= '<p style="margin:0 0 6px;"><strong>' . esc_html__( 'Customer:', 'noyona-childtheme' ) . '</strong> ' . esc_html( trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ) ) . '</p>';
    $invoice_html .= '<p style="margin:0 0 6px;"><strong>' . esc_html__( 'Shipping Address:', 'noyona-childtheme' ) . '</strong> ' . esc_html( $shipping_text ) . '</p>';
    $invoice_html .= '<p style="margin:0 0 16px;"><strong>' . esc_html__( 'Payment Method:', 'noyona-childtheme' ) . '</strong> ' . esc_html( $payment_label ) . '</p>';
    $invoice_html .= '<table style="width:100%;border-collapse:collapse;margin-bottom:16px;"><thead><tr><th style="text-align:left;padding:8px;border-bottom:2px solid #d8d8d8;">' . esc_html__( 'Item', 'noyona-childtheme' ) . '</th><th style="text-align:center;padding:8px;border-bottom:2px solid #d8d8d8;">' . esc_html__( 'Qty', 'noyona-childtheme' ) . '</th><th style="text-align:right;padding:8px;border-bottom:2px solid #d8d8d8;">' . esc_html__( 'Total', 'noyona-childtheme' ) . '</th></tr></thead><tbody>' . $items_html . '</tbody></table>';
    $invoice_html .= '<table style="width:100%;max-width:420px;border-collapse:collapse;margin-left:auto;"><tbody>' . $totals_html . '</tbody></table>';
    $invoice_html .= '</body></html>';

    if ( function_exists( 'nocache_headers' ) ) {
        nocache_headers();
    }

    $filename = sanitize_file_name( 'noyona-einvoice-order-' . $order->get_order_number() . '.doc' );
    header( 'Content-Type: application/msword; charset=' . get_bloginfo( 'charset' ) );
    header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
    header( 'X-Content-Type-Options: nosniff' );

    echo $invoice_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    exit;
}

add_action( 'admin_post_noyona_upload_account_avatar', 'noyona_upload_account_avatar_handler' );
function noyona_upload_account_avatar_handler() {
    $account_url = noyona_get_account_page_url();

    if ( ! is_user_logged_in() ) {
        wp_safe_redirect( noyona_get_login_page_url() );
        exit;
    }

    $nonce = isset( $_POST['noyona_avatar_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['noyona_avatar_nonce'] ) ) : '';
    if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'noyona_upload_account_avatar' ) ) {
        wp_safe_redirect( add_query_arg( 'noyona_avatar', 'invalid_nonce', $account_url ) );
        exit;
    }

    if ( empty( $_FILES['noyona_account_avatar']['name'] ) || ! isset( $_FILES['noyona_account_avatar']['tmp_name'] ) ) {
        wp_safe_redirect( add_query_arg( 'noyona_avatar', 'missing_file', $account_url ) );
        exit;
    }

    $file      = $_FILES['noyona_account_avatar'];
    $file_size = isset( $file['size'] ) ? (int) $file['size'] : 0;
    if ( $file_size <= 0 || $file_size > 1048576 ) {
        wp_safe_redirect( add_query_arg( 'noyona_avatar', 'invalid_size', $account_url ) );
        exit;
    }

    $file_type = wp_check_filetype_and_ext(
        isset( $file['tmp_name'] ) ? (string) $file['tmp_name'] : '',
        isset( $file['name'] ) ? (string) $file['name'] : ''
    );
    $allowed_types = array( 'image/jpeg', 'image/png', 'image/webp' );
    if ( empty( $file_type['type'] ) || ! in_array( $file_type['type'], $allowed_types, true ) ) {
        wp_safe_redirect( add_query_arg( 'noyona_avatar', 'invalid_type', $account_url ) );
        exit;
    }

    if ( ! function_exists( 'media_handle_upload' ) ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
    }

    $attachment_id = media_handle_upload( 'noyona_account_avatar', 0 );
    if ( is_wp_error( $attachment_id ) ) {
        wp_safe_redirect( add_query_arg( 'noyona_avatar', 'upload_error', $account_url ) );
        exit;
    }

    update_user_meta( get_current_user_id(), 'noyona_account_avatar_id', absint( $attachment_id ) );

    $redirect_to = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : '';
    if ( '' === $redirect_to ) {
        $redirect_to = $account_url;
    }

    wp_safe_redirect( add_query_arg( 'noyona_avatar', 'updated', $redirect_to ) );
    exit;
}

add_action( 'admin_post_noyona_update_account_profile', 'noyona_update_account_profile_handler' );
function noyona_update_account_profile_handler() {
    $account_url = noyona_get_account_page_url();

    if ( ! is_user_logged_in() ) {
        wp_safe_redirect( noyona_get_login_page_url() );
        exit;
    }

    $redirect_to = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : '';
    if ( '' === $redirect_to ) {
        $redirect_to = $account_url;
    }

    $nonce = isset( $_POST['noyona_account_profile_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['noyona_account_profile_nonce'] ) ) : '';
    if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'noyona_update_account_profile' ) ) {
        wp_safe_redirect( add_query_arg( array( 'noyona_modal' => 'edit', 'noyona_account_notice' => 'invalid_nonce' ), $redirect_to ) );
        exit;
    }

    $full_name = isset( $_POST['full_name'] ) ? sanitize_text_field( wp_unslash( $_POST['full_name'] ) ) : '';
    $email     = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
    $phone     = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';

    if ( '' === $full_name || '' === $email ) {
        wp_safe_redirect( add_query_arg( array( 'noyona_modal' => 'edit', 'noyona_account_notice' => 'missing_fields' ), $redirect_to ) );
        exit;
    }

    if ( ! is_email( $email ) ) {
        wp_safe_redirect( add_query_arg( array( 'noyona_modal' => 'edit', 'noyona_account_notice' => 'invalid_email' ), $redirect_to ) );
        exit;
    }

    $user_id          = get_current_user_id();
    $existing_user_id = email_exists( $email );
    if ( $existing_user_id && (int) $existing_user_id !== (int) $user_id ) {
        wp_safe_redirect( add_query_arg( array( 'noyona_modal' => 'edit', 'noyona_account_notice' => 'email_exists' ), $redirect_to ) );
        exit;
    }

    $name_parts = preg_split( '/\s+/', trim( $full_name ) );
    $first_name = '';
    $last_name  = '';
    if ( is_array( $name_parts ) && ! empty( $name_parts ) ) {
        $first_name = (string) array_shift( $name_parts );
        $last_name  = trim( implode( ' ', $name_parts ) );
    }

    $updated_user = wp_update_user(
        array(
            'ID'           => $user_id,
            'display_name' => $full_name,
            'user_email'   => $email,
            'first_name'   => $first_name,
            'last_name'    => $last_name,
        )
    );

    if ( is_wp_error( $updated_user ) ) {
        wp_safe_redirect( add_query_arg( array( 'noyona_modal' => 'edit', 'noyona_account_notice' => 'profile_update_failed' ), $redirect_to ) );
        exit;
    }

    update_user_meta( $user_id, 'billing_phone', $phone );

    wp_safe_redirect( add_query_arg( array( 'noyona_modal' => 'edit', 'noyona_account_notice' => 'profile_updated' ), $redirect_to ) );
    exit;
}

add_action( 'admin_post_noyona_update_account_password', 'noyona_update_account_password_handler' );
function noyona_update_account_password_handler() {
    $account_url = noyona_get_account_page_url();

    if ( ! is_user_logged_in() ) {
        wp_safe_redirect( noyona_get_login_page_url() );
        exit;
    }

    $redirect_to = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : '';
    if ( '' === $redirect_to ) {
        $redirect_to = $account_url;
    }

    $nonce = isset( $_POST['noyona_account_password_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['noyona_account_password_nonce'] ) ) : '';
    if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'noyona_update_account_password' ) ) {
        wp_safe_redirect( add_query_arg( array( 'noyona_modal' => 'password', 'noyona_account_notice' => 'invalid_nonce' ), $redirect_to ) );
        exit;
    }

    $current_password = isset( $_POST['current_password'] ) ? (string) wp_unslash( $_POST['current_password'] ) : '';
    $new_password     = isset( $_POST['new_password'] ) ? (string) wp_unslash( $_POST['new_password'] ) : '';
    $confirm_password = isset( $_POST['confirm_password'] ) ? (string) wp_unslash( $_POST['confirm_password'] ) : '';

    if ( '' === $current_password || '' === $new_password || '' === $confirm_password ) {
        wp_safe_redirect( add_query_arg( array( 'noyona_modal' => 'password', 'noyona_account_notice' => 'missing_fields' ), $redirect_to ) );
        exit;
    }

    if ( strlen( $new_password ) < 6 ) {
        wp_safe_redirect( add_query_arg( array( 'noyona_modal' => 'password', 'noyona_account_notice' => 'password_too_short' ), $redirect_to ) );
        exit;
    }

    if ( $new_password !== $confirm_password ) {
        wp_safe_redirect( add_query_arg( array( 'noyona_modal' => 'password', 'noyona_account_notice' => 'password_mismatch' ), $redirect_to ) );
        exit;
    }

    $user = wp_get_current_user();
    if ( ! $user || ! $user->exists() || ! wp_check_password( $current_password, (string) $user->user_pass, (int) $user->ID ) ) {
        wp_safe_redirect( add_query_arg( array( 'noyona_modal' => 'password', 'noyona_account_notice' => 'wrong_current_password' ), $redirect_to ) );
        exit;
    }

    $updated_user = wp_update_user(
        array(
            'ID'        => (int) $user->ID,
            'user_pass' => $new_password,
        )
    );

    if ( is_wp_error( $updated_user ) ) {
        wp_safe_redirect( add_query_arg( array( 'noyona_modal' => 'password', 'noyona_account_notice' => 'password_update_failed' ), $redirect_to ) );
        exit;
    }

    wp_set_current_user( (int) $user->ID );
    wp_set_auth_cookie( (int) $user->ID, true );

    wp_safe_redirect( add_query_arg( array( 'noyona_modal' => 'password', 'noyona_account_notice' => 'password_updated' ), $redirect_to ) );
    exit;
}

add_action( 'admin_post_noyona_upsert_account_address', 'noyona_upsert_account_address_handler' );
function noyona_upsert_account_address_handler() {
    if ( ! is_user_logged_in() ) {
        wp_safe_redirect( noyona_get_login_page_url() );
        exit;
    }

    $addresses_url = noyona_get_account_addresses_url();
    $redirect_to   = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : '';
    if ( '' === $redirect_to ) {
        $redirect_to = $addresses_url;
    }
    $redirect_to = remove_query_arg( array( 'noyona_address_edit', 'noyona_modal' ), $redirect_to );

    $nonce = isset( $_POST['noyona_account_address_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['noyona_account_address_nonce'] ) ) : '';
    $address_id = isset( $_POST['address_id'] ) ? sanitize_key( wp_unslash( $_POST['address_id'] ) ) : '';
    $is_edit_request = '' !== $address_id;
    $edit_redirect_args = array();
    if ( $is_edit_request ) {
        $edit_redirect_args = array(
            'noyona_modal'        => 'address',
            'noyona_address_edit' => $address_id,
        );
    }

    if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'noyona_upsert_account_address' ) ) {
        wp_safe_redirect( add_query_arg( array_merge( $edit_redirect_args, array( 'noyona_account_notice' => 'invalid_nonce' ) ), $redirect_to ) );
        exit;
    }

    $address_line = isset( $_POST['address'] ) ? sanitize_text_field( wp_unslash( $_POST['address'] ) ) : '';
    $city         = isset( $_POST['city'] ) ? sanitize_text_field( wp_unslash( $_POST['city'] ) ) : '';
    $province     = isset( $_POST['province'] ) ? sanitize_text_field( wp_unslash( $_POST['province'] ) ) : '';
    $zip_code     = isset( $_POST['zip_code'] ) ? sanitize_text_field( wp_unslash( $_POST['zip_code'] ) ) : '';
    $is_default   = ! empty( $_POST['is_default'] );

    if ( '' === $address_line || '' === $city || '' === $province || '' === $zip_code ) {
        wp_safe_redirect( add_query_arg( array_merge( $edit_redirect_args, array( 'noyona_account_notice' => 'missing_fields' ) ), $redirect_to ) );
        exit;
    }

    $user_id          = get_current_user_id();
    $account_addresses = noyona_get_account_saved_addresses( $user_id );
    $address_updated  = false;

    if ( '' !== $address_id ) {
        foreach ( $account_addresses as $index => $existing_address ) {
            if ( ! isset( $existing_address['id'] ) || $address_id !== (string) $existing_address['id'] ) {
                continue;
            }

            $account_addresses[ $index ]['address']    = $address_line;
            $account_addresses[ $index ]['city']       = $city;
            $account_addresses[ $index ]['province']   = $province;
            $account_addresses[ $index ]['zip_code']   = $zip_code;
            $account_addresses[ $index ]['is_default'] = $is_default;
            $address_updated                            = true;
            break;
        }

        if ( ! $address_updated ) {
            wp_safe_redirect( add_query_arg( array_merge( $edit_redirect_args, array( 'noyona_account_notice' => 'address_not_found' ) ), $redirect_to ) );
            exit;
        }
    }

    if ( ! $address_updated ) {
        $account_addresses[] = array(
            'id'         => sanitize_key( 'addr_' . wp_generate_uuid4() ),
            'address'    => $address_line,
            'city'       => $city,
            'province'   => $province,
            'zip_code'   => $zip_code,
            'is_default' => $is_default,
        );
    }

    $account_addresses = noyona_normalize_account_addresses( $account_addresses );
    update_user_meta( $user_id, 'noyona_account_addresses', $account_addresses );
    noyona_sync_default_account_address_to_woocommerce( $user_id, $account_addresses );

    wp_safe_redirect( add_query_arg( 'noyona_account_notice', 'address_saved', $redirect_to ) );
    exit;
}

add_action( 'admin_post_noyona_delete_account_address', 'noyona_delete_account_address_handler' );
function noyona_delete_account_address_handler() {
    if ( ! is_user_logged_in() ) {
        wp_safe_redirect( noyona_get_login_page_url() );
        exit;
    }

    $addresses_url = noyona_get_account_addresses_url();
    $redirect_to   = isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ) : '';
    if ( '' === $redirect_to ) {
        $redirect_to = $addresses_url;
    }
    $redirect_to = remove_query_arg( array( 'noyona_address_edit', 'noyona_modal' ), $redirect_to );

    $address_id = isset( $_REQUEST['address_id'] ) ? sanitize_key( wp_unslash( $_REQUEST['address_id'] ) ) : '';
    if ( '' === $address_id ) {
        wp_safe_redirect( add_query_arg( 'noyona_account_notice', 'address_not_found', $redirect_to ) );
        exit;
    }

    $nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';
    if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'noyona_delete_account_address_' . $address_id ) ) {
        wp_safe_redirect( add_query_arg( 'noyona_account_notice', 'invalid_nonce', $redirect_to ) );
        exit;
    }

    $user_id          = get_current_user_id();
    $account_addresses = noyona_get_account_saved_addresses( $user_id );
    $filtered_addresses = array_values(
        array_filter(
            $account_addresses,
            static function ( $existing_address ) use ( $address_id ) {
                if ( ! is_array( $existing_address ) || ! isset( $existing_address['id'] ) ) {
                    return false;
                }
                return $address_id !== (string) $existing_address['id'];
            }
        )
    );

    if ( count( $filtered_addresses ) === count( $account_addresses ) ) {
        wp_safe_redirect( add_query_arg( 'noyona_account_notice', 'address_not_found', $redirect_to ) );
        exit;
    }

    $filtered_addresses = noyona_normalize_account_addresses( $filtered_addresses );
    update_user_meta( $user_id, 'noyona_account_addresses', $filtered_addresses );
    noyona_sync_default_account_address_to_woocommerce( $user_id, $filtered_addresses );

    wp_safe_redirect( add_query_arg( 'noyona_account_notice', 'address_deleted', $redirect_to ) );
    exit;
}

add_action( 'admin_post_noyona_upsert_bank_account', 'noyona_upsert_bank_account_handler' );
function noyona_upsert_bank_account_handler() {
    if ( ! is_user_logged_in() ) {
        wp_safe_redirect( noyona_get_login_page_url() );
        exit;
    }

    $payments_url = noyona_get_account_payments_url();
    $redirect_to  = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : '';
    if ( '' === $redirect_to ) {
        $redirect_to = $payments_url;
    }
    $redirect_to = remove_query_arg( array( 'noyona_bank_edit', 'noyona_card_edit', 'noyona_modal' ), $redirect_to );

    $bank_id         = isset( $_POST['bank_id'] ) ? sanitize_key( wp_unslash( $_POST['bank_id'] ) ) : '';
    $nonce = isset( $_POST['noyona_bank_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['noyona_bank_nonce'] ) ) : '';
    $edit_redirect_args = array();
    if ( '' !== $bank_id ) {
        $edit_redirect_args = array(
            'noyona_modal'    => 'bank',
            'noyona_bank_edit' => $bank_id,
        );
    }
    if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'noyona_upsert_bank_account' ) ) {
        wp_safe_redirect( add_query_arg( array_merge( $edit_redirect_args, array( 'noyona_account_notice' => 'invalid_nonce' ) ), $redirect_to ) );
        exit;
    }

    $account_name    = isset( $_POST['account_name'] ) ? sanitize_text_field( wp_unslash( $_POST['account_name'] ) ) : '';
    $bank_name       = isset( $_POST['bank_name'] ) ? sanitize_text_field( wp_unslash( $_POST['bank_name'] ) ) : '';
    $account_number  = isset( $_POST['account_number'] ) ? sanitize_text_field( wp_unslash( $_POST['account_number'] ) ) : '';
    $account_number  = (string) preg_replace( '/[^0-9A-Za-z\-\s]/', '', $account_number );
    $is_edit_request = '' !== $bank_id;

    if ( '' === $account_name || '' === $bank_name || '' === $account_number ) {
        wp_safe_redirect( add_query_arg( array_merge( $edit_redirect_args, array( 'noyona_account_notice' => 'missing_fields' ) ), $redirect_to ) );
        exit;
    }

    $user_id       = get_current_user_id();
    $bank_accounts = noyona_get_account_bank_accounts( $user_id );
    $is_updated    = false;

    if ( $is_edit_request ) {
        foreach ( $bank_accounts as $index => $bank_account ) {
            if ( ! isset( $bank_account['id'] ) || $bank_id !== (string) $bank_account['id'] ) {
                continue;
            }

            $bank_accounts[ $index ]['account_name']   = $account_name;
            $bank_accounts[ $index ]['bank_name']      = $bank_name;
            $bank_accounts[ $index ]['account_number'] = $account_number;
            $is_updated = true;
            break;
        }

        if ( ! $is_updated ) {
            wp_safe_redirect( add_query_arg( array_merge( $edit_redirect_args, array( 'noyona_account_notice' => 'bank_not_found' ) ), $redirect_to ) );
            exit;
        }
    }

    if ( ! $is_updated ) {
        $bank_accounts[] = array(
            'id'             => sanitize_key( 'bank_' . wp_generate_uuid4() ),
            'account_name'   => $account_name,
            'bank_name'      => $bank_name,
            'account_number' => $account_number,
        );
    }

    $bank_accounts = noyona_normalize_account_bank_accounts( $bank_accounts );
    update_user_meta( $user_id, 'noyona_account_bank_accounts', $bank_accounts );

    wp_safe_redirect( add_query_arg( 'noyona_account_notice', 'bank_saved', $redirect_to ) );
    exit;
}

add_action( 'admin_post_noyona_delete_bank_account', 'noyona_delete_bank_account_handler' );
function noyona_delete_bank_account_handler() {
    if ( ! is_user_logged_in() ) {
        wp_safe_redirect( noyona_get_login_page_url() );
        exit;
    }

    $payments_url = noyona_get_account_payments_url();
    $redirect_to  = isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ) : '';
    if ( '' === $redirect_to ) {
        $redirect_to = $payments_url;
    }
    $redirect_to = remove_query_arg( array( 'noyona_bank_edit', 'noyona_card_edit', 'noyona_modal' ), $redirect_to );

    $bank_id = isset( $_REQUEST['bank_id'] ) ? sanitize_key( wp_unslash( $_REQUEST['bank_id'] ) ) : '';
    if ( '' === $bank_id ) {
        wp_safe_redirect( add_query_arg( 'noyona_account_notice', 'bank_not_found', $redirect_to ) );
        exit;
    }

    $nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';
    if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'noyona_delete_bank_account_' . $bank_id ) ) {
        wp_safe_redirect( add_query_arg( 'noyona_account_notice', 'invalid_nonce', $redirect_to ) );
        exit;
    }

    $user_id       = get_current_user_id();
    $bank_accounts = noyona_get_account_bank_accounts( $user_id );
    $filtered      = array_values(
        array_filter(
            $bank_accounts,
            static function ( $bank_account ) use ( $bank_id ) {
                return is_array( $bank_account ) && isset( $bank_account['id'] ) && $bank_id !== (string) $bank_account['id'];
            }
        )
    );

    if ( count( $filtered ) === count( $bank_accounts ) ) {
        wp_safe_redirect( add_query_arg( 'noyona_account_notice', 'bank_not_found', $redirect_to ) );
        exit;
    }

    update_user_meta( $user_id, 'noyona_account_bank_accounts', noyona_normalize_account_bank_accounts( $filtered ) );
    wp_safe_redirect( add_query_arg( 'noyona_account_notice', 'bank_deleted', $redirect_to ) );
    exit;
}

add_action( 'admin_post_noyona_upsert_card', 'noyona_upsert_card_handler' );
function noyona_upsert_card_handler() {
    if ( ! is_user_logged_in() ) {
        wp_safe_redirect( noyona_get_login_page_url() );
        exit;
    }

    $payments_url = noyona_get_account_payments_url();
    $redirect_to  = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : '';
    if ( '' === $redirect_to ) {
        $redirect_to = $payments_url;
    }
    $redirect_to = remove_query_arg( array( 'noyona_bank_edit', 'noyona_card_edit', 'noyona_modal' ), $redirect_to );

    $card_id       = isset( $_POST['card_id'] ) ? sanitize_key( wp_unslash( $_POST['card_id'] ) ) : '';
    $nonce = isset( $_POST['noyona_card_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['noyona_card_nonce'] ) ) : '';
    $edit_redirect_args = array();
    if ( '' !== $card_id ) {
        $edit_redirect_args = array(
            'noyona_modal'   => 'card',
            'noyona_card_edit' => $card_id,
        );
    }
    if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'noyona_upsert_card' ) ) {
        wp_safe_redirect( add_query_arg( array_merge( $edit_redirect_args, array( 'noyona_account_notice' => 'invalid_nonce' ) ), $redirect_to ) );
        exit;
    }

    $card_number   = isset( $_POST['card_number'] ) ? sanitize_text_field( wp_unslash( $_POST['card_number'] ) ) : '';
    $expiry_date   = isset( $_POST['expiry_date'] ) ? sanitize_text_field( wp_unslash( $_POST['expiry_date'] ) ) : '';
    $name_on_card  = isset( $_POST['name_on_card'] ) ? sanitize_text_field( wp_unslash( $_POST['name_on_card'] ) ) : '';
    $is_edit       = '' !== $card_id;
    $card_digits   = preg_replace( '/\D+/', '', (string) $card_number );

    if ( '' === $expiry_date || '' === $name_on_card ) {
        wp_safe_redirect( add_query_arg( array_merge( $edit_redirect_args, array( 'noyona_account_notice' => 'missing_fields' ) ), $redirect_to ) );
        exit;
    }

    $user_id      = get_current_user_id();
    $saved_cards  = noyona_get_account_cards( $user_id );
    $is_updated   = false;
    $card_last4   = '';
    $card_brand   = 'card';

    if ( is_string( $card_digits ) && strlen( $card_digits ) >= 4 ) {
        $card_last4 = substr( $card_digits, -4 );
        $card_brand = noyona_detect_card_brand( $card_digits );
    }

    if ( $is_edit ) {
        foreach ( $saved_cards as $index => $saved_card ) {
            if ( ! isset( $saved_card['id'] ) || $card_id !== (string) $saved_card['id'] ) {
                continue;
            }

            if ( '' === $card_last4 && isset( $saved_card['card_last4'] ) ) {
                $card_last4 = (string) $saved_card['card_last4'];
            }
            if ( 'card' === $card_brand && isset( $saved_card['card_brand'] ) ) {
                $card_brand = sanitize_key( (string) $saved_card['card_brand'] );
            }

            $saved_cards[ $index ]['card_last4']   = $card_last4;
            $saved_cards[ $index ]['card_brand']   = $card_brand;
            $saved_cards[ $index ]['expiry_date']  = $expiry_date;
            $saved_cards[ $index ]['name_on_card'] = $name_on_card;
            $is_updated = true;
            break;
        }

        if ( ! $is_updated ) {
            wp_safe_redirect( add_query_arg( array_merge( $edit_redirect_args, array( 'noyona_account_notice' => 'card_not_found' ) ), $redirect_to ) );
            exit;
        }
    } else {
        if ( '' === $card_last4 ) {
            wp_safe_redirect( add_query_arg( array_merge( $edit_redirect_args, array( 'noyona_account_notice' => 'invalid_card' ) ), $redirect_to ) );
            exit;
        }

        $saved_cards[] = array(
            'id'           => sanitize_key( 'card_' . wp_generate_uuid4() ),
            'card_last4'   => $card_last4,
            'card_brand'   => $card_brand,
            'expiry_date'  => $expiry_date,
            'name_on_card' => $name_on_card,
        );
    }

    $saved_cards = noyona_normalize_account_cards( $saved_cards );
    update_user_meta( $user_id, 'noyona_account_cards', $saved_cards );

    wp_safe_redirect( add_query_arg( 'noyona_account_notice', 'card_saved', $redirect_to ) );
    exit;
}

add_action( 'admin_post_noyona_delete_card', 'noyona_delete_card_handler' );
function noyona_delete_card_handler() {
    if ( ! is_user_logged_in() ) {
        wp_safe_redirect( noyona_get_login_page_url() );
        exit;
    }

    $payments_url = noyona_get_account_payments_url();
    $redirect_to  = isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ) : '';
    if ( '' === $redirect_to ) {
        $redirect_to = $payments_url;
    }
    $redirect_to = remove_query_arg( array( 'noyona_bank_edit', 'noyona_card_edit', 'noyona_modal' ), $redirect_to );

    $card_id = isset( $_REQUEST['card_id'] ) ? sanitize_key( wp_unslash( $_REQUEST['card_id'] ) ) : '';
    if ( '' === $card_id ) {
        wp_safe_redirect( add_query_arg( 'noyona_account_notice', 'card_not_found', $redirect_to ) );
        exit;
    }

    $nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';
    if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'noyona_delete_card_' . $card_id ) ) {
        wp_safe_redirect( add_query_arg( 'noyona_account_notice', 'invalid_nonce', $redirect_to ) );
        exit;
    }

    $user_id     = get_current_user_id();
    $saved_cards = noyona_get_account_cards( $user_id );
    $filtered    = array_values(
        array_filter(
            $saved_cards,
            static function ( $saved_card ) use ( $card_id ) {
                return is_array( $saved_card ) && isset( $saved_card['id'] ) && $card_id !== (string) $saved_card['id'];
            }
        )
    );

    if ( count( $filtered ) === count( $saved_cards ) ) {
        wp_safe_redirect( add_query_arg( 'noyona_account_notice', 'card_not_found', $redirect_to ) );
        exit;
    }

    update_user_meta( $user_id, 'noyona_account_cards', noyona_normalize_account_cards( $filtered ) );
    wp_safe_redirect( add_query_arg( 'noyona_account_notice', 'card_deleted', $redirect_to ) );
    exit;
}

function woocom_ct_get_favorite_store_ids( $user_id ) {
    $favorites = get_user_meta( $user_id, 'noyona_store_favorites', true );
    if ( ! is_array( $favorites ) ) {
        $favorites = [];
    }
    $favorites = array_values( array_unique( array_filter( array_map( 'absint', $favorites ) ) ) );
    return $favorites;
}

add_action( 'wp_ajax_noyona_toggle_favorite', 'woocom_ct_handle_toggle_favorite' );
function woocom_ct_handle_toggle_favorite() {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'not_logged_in' ), 401 );
    }

    check_ajax_referer( 'noyona_favorites', 'nonce' );

    $store_id = isset( $_POST['store_id'] ) ? absint( wp_unslash( $_POST['store_id'] ) ) : 0;
    $mode = isset( $_POST['mode'] ) ? sanitize_text_field( wp_unslash( $_POST['mode'] ) ) : 'toggle';

    if ( ! $store_id ) {
        wp_send_json_error( array( 'message' => 'invalid_store' ), 400 );
    }

    $store = get_post( $store_id );
    if ( ! $store || 'store' !== $store->post_type ) {
        wp_send_json_error( array( 'message' => 'store_not_found' ), 404 );
    }

    $user_id = get_current_user_id();
    $favorites = woocom_ct_get_favorite_store_ids( $user_id );

    if ( 'add' === $mode ) {
        if ( ! in_array( $store_id, $favorites, true ) ) {
            $favorites[] = $store_id;
        }
    } elseif ( 'remove' === $mode ) {
        $favorites = array_values( array_diff( $favorites, array( $store_id ) ) );
    } else {
        if ( in_array( $store_id, $favorites, true ) ) {
            $favorites = array_values( array_diff( $favorites, array( $store_id ) ) );
        } else {
            $favorites[] = $store_id;
        }
    }

    update_user_meta( $user_id, 'noyona_store_favorites', $favorites );

    wp_send_json_success( array( 'favorites' => $favorites ) );
}

add_action( 'woocommerce_account_dashboard', 'woocom_ct_render_account_favorites', 15 );
function woocom_ct_render_account_favorites() {
    if ( ! is_user_logged_in() ) {
        return;
    }

    $favorites = woocom_ct_get_favorite_store_ids( get_current_user_id() );

    echo '<div class="noyona-account-favorites">';
    echo '<h3>Favorite Stores</h3>';

    if ( empty( $favorites ) ) {
        echo '<p class="noyona-account-favorites-empty">No favorite stores yet.</p>';
        echo '</div>';
        return;
    }

    $stores = get_posts(
        array(
            'post_type' => 'store',
            'post__in' => $favorites,
            'orderby' => 'post__in',
            'posts_per_page' => -1,
        )
    );

    if ( empty( $stores ) ) {
        echo '<p class="noyona-account-favorites-empty">No favorite stores yet.</p>';
        echo '</div>';
        return;
    }

    echo '<ul class="noyona-account-favorites-list">';
    foreach ( $stores as $store ) {
        echo '<li><a href="' . esc_url( get_permalink( $store ) ) . '">' . esc_html( get_the_title( $store ) ) . '</a></li>';
    }
    echo '</ul>';
    echo '</div>';
}

add_filter( 'render_block', 'woocom_ct_remove_customer_account_block', 10, 2 );
function woocom_ct_remove_customer_account_block( $block_content, $block ) {
    if ( ! empty( $block['blockName'] ) && 'woocommerce/customer-account' === $block['blockName'] ) {
        return '';
    }

    return $block_content;
}

add_filter( 'render_block', 'noyona_normalize_header_rendered_markup', 20, 2 );
function noyona_normalize_header_rendered_markup( $block_content, $block ) {
    if ( is_admin() || '' === trim( (string) $block_content ) ) {
        return $block_content;
    }

    if (
        false === strpos( $block_content, 'site-logo-img--desktop' ) &&
        false === strpos( $block_content, 'site-logo-img--mobile' )
    ) {
        return $block_content;
    }

    $theme_uri     = untrailingslashit( get_stylesheet_directory_uri() );
    $desktop_logo  = esc_url( $theme_uri . '/assets/images/noyona-logo.webp' );
    $mobile_logo   = esc_url( $theme_uri . '/assets/images/noyona-mobile-logo.webp' );

    $block_content = preg_replace_callback(
        '#<img\b[^>]*>#i',
        function ( $matches ) use ( $desktop_logo, $mobile_logo ) {
            $img_html = $matches[0];

            if ( false === stripos( $img_html, 'site-logo-img--desktop' ) && false === stripos( $img_html, 'site-logo-img--mobile' ) ) {
                return $img_html;
            }

            $target_src = false !== stripos( $img_html, 'site-logo-img--desktop' ) ? $desktop_logo : $mobile_logo;

            // Remove any srcset that could reselect old media-library images.
            $img_html = preg_replace( '#\s+srcset=(["\']).*?\1#i', '', $img_html );

            // Replace existing src, or inject one if missing.
            $src_replaced = 0;
            $img_html     = preg_replace( '#\s+src=(["\']).*?\1#i', ' src="' . $target_src . '"', $img_html, 1, $src_replaced );
            if ( 0 === (int) $src_replaced ) {
                $img_html = preg_replace( '#<img\b#i', '<img src="' . $target_src . '"', $img_html, 1 );
            }

            return $img_html;
        },
        $block_content
    );

    return $block_content;
}

add_filter( 'render_block', 'noyona_force_minicart_checkout_link', 30, 2 );
function noyona_force_minicart_checkout_link( $block_content, $block ) {
    if ( is_admin() || empty( $block['blockName'] ) ) {
        return $block_content;
    }

    if ( 'woocommerce/mini-cart-checkout-button-block' !== $block['blockName'] ) {
        return $block_content;
    }

    if ( false === strpos( (string) $block_content, 'wp-block-woocommerce-mini-cart-checkout-button-block' ) ) {
        return $block_content;
    }

    $checkout_url = esc_url( home_url( '/cart/' ) );
    $pattern      = '#<a\b[^>]*class=(["\'])[^"\']*\bwp-block-woocommerce-mini-cart-checkout-button-block\b[^"\']*\1[^>]*>#i';

    return preg_replace_callback(
        $pattern,
        function ( $matches ) use ( $checkout_url ) {
            $anchor = $matches[0];

            // Add our own data attribute so JS can always find this element
            // regardless of WC class name changes.
            if ( false === strpos( $anchor, 'data-noyona-checkout-btn' ) ) {
                $anchor = preg_replace( '#<a\b#i', '<a data-noyona-checkout-btn="1"', $anchor, 1 );
            }

            if ( preg_match( '#\bhref=(["\']).*?\1#i', $anchor ) ) {
                return preg_replace( '#\bhref=(["\']).*?\1#i', 'href="' . $checkout_url . '"', $anchor, 1 );
            }

            return preg_replace( '#<a\b#i', '<a href="' . $checkout_url . '"', $anchor, 1 );
        },
        (string) $block_content,
        1
    );
}

add_filter( 'render_block', 'noyona_strip_checkout_pay_meta_breaks', 35, 2 );
function noyona_strip_checkout_pay_meta_breaks( $block_content, $block ) {
    if ( is_admin() || '' === trim( (string) $block_content ) ) {
        return $block_content;
    }

    if ( false === strpos( (string) $block_content, 'noyona-pay-meta' ) ) {
        return $block_content;
    }

    return preg_replace_callback(
        '/(<section\b[^>]*class=(["\'])[^"\']*\bnoyona-pay-meta\b[^"\']*\2[^>]*>)(.*?)(<\/section>)/is',
        function ( $matches ) {
            $inner = preg_replace( '/\s*<br\s*\/?>\s*/i', '', (string) $matches[3] );
            return $matches[1] . $inner . $matches[4];
        },
        (string) $block_content
    );
}

/* =================================================
 * UX / MISC
 * ================================================= */
add_filter('show_admin_bar', '__return_false');

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

/* =================================================
 * SEO: ROBOTS META
 * ================================================= */
// function noyona_is_site_under_development() {
    // Set to false (or override via filter) when ready for production indexing.
//     return (bool) apply_filters( 'noyona_site_under_development', true );
// }

// add_action('wp_head', function () {
//     if ( noyona_is_site_under_development() ) {
//         echo '<meta name="robots" content="index, follow, noarchive, nosnippet">';
//         return;
//     }
//     echo '<meta name="robots" content="index, follow">';
// }, 1);

/* =================================================
 * GLOBAL ROBOTS HEADERS
 * ================================================= */
// add_action('send_headers', function () {
//     if ( noyona_is_site_under_development() ) {
//         header('X-Robots-Tag: index, follow, noarchive, nosnippet', true);
//         return;
//     }
//     header('X-Robots-Tag: index, follow, archive, snippet', true);
// });

/* =================================================
 * SEO / CRAWL CONTROL
 * ================================================= */
function noyona_is_site_under_development() {
    $is_dev = false;

    if ( function_exists( 'wp_get_environment_type' ) ) {
        $env_type = (string) wp_get_environment_type();
        if ( in_array( $env_type, array( 'local', 'development', 'staging' ), true ) ) {
            $is_dev = true;
        }
    }

    // Respect core "Discourage search engines" setting.
    if ( '0' === (string) get_option( 'blog_public', '1' ) ) {
        $is_dev = true;
    }

    return (bool) apply_filters( 'noyona_site_under_development', $is_dev );
}

/**
 * Disable WordPress core XML sitemaps.
 */
add_filter(
    'wp_sitemaps_enabled',
    function( $enabled ) {
        if ( noyona_is_site_under_development() ) {
            return false;
        }

        return $enabled;
    }
);

/**
 * Force a restrictive robots.txt from WordPress.
 * Note: this discourages crawlers, but does not secure URLs from direct browser access.
 */
add_filter( 'robots_txt', 'noyona_custom_robots_txt', 999, 2 );
function noyona_custom_robots_txt( $output, $public ) {
    if ( ! noyona_is_site_under_development() ) {
        // In production, do not override plugin/core robots.txt output.
        return $output;
    }

    return implode( "\n", array(
        'User-agent: *',
        'Disallow: /',
        'Disallow: /wp-sitemap.xml',
        'Disallow: /sitemap.xml',
        'Disallow: /sitemap1.xml',
        'Disallow: /sitemap_index.xml',
        'Disallow: /product-sitemap.xml',
        'Disallow: /page-sitemap.xml',
        'Disallow: /post-sitemap.xml',
        'Disallow: /category-sitemap.xml',
        'Disallow: /product_cat-sitemap.xml',
        'Disallow: /product_tag-sitemap.xml',
    ) );
}

/**
 * Output robots meta tag.
 */
add_action( 'wp_head', 'noyona_output_robots_meta', 1 );
function noyona_output_robots_meta() {
    // In production, allow SEO plugins/core to manage robots directives.
    if ( ! noyona_is_site_under_development() ) {
        return;
    }

    echo '<meta name="robots" content="noindex, nofollow, noarchive, nosnippet, noimageindex">' . "\n";
}

/**
 * Send X-Robots-Tag header.
 */
add_action( 'send_headers', 'noyona_send_robots_headers', 1 );
function noyona_send_robots_headers() {
    // In production, avoid overriding robots headers set by SEO plugins/server.
    if ( ! noyona_is_site_under_development() ) {
        return;
    }

    header( 'X-Robots-Tag: noindex, nofollow, noarchive, nosnippet, noimageindex', true );
}



/* =================================================
 * SEO: DOCUMENT TITLES
 * ================================================= */
add_filter('pre_get_document_title', function ($title) {
    /* ---- STATIC PAGE TITLES ---- */
    if (is_front_page()) return 'Noyona Essentials | Filipino Vegan Cosmetics Rooted in Nature';
    if (is_page('shop')) return 'Noyona Essentials | Shop Vegan Filipino Cosmetics – Keratin, Foundation & Lip Color';
    if (is_page('face-makeup')) return 'Face Makeup & Cosmetics: Foundation, Powder, & Concealer | Noyona Cosmetics and Skin Care Products OPC';
    if (is_page('lip-makeup')) return 'Lip Makeup: Matte, Liquid & Creamy Lipsticks | Noyona Cosmetics and Skin Care Products OPC';
    if (is_page('eye-makeup')) return 'Eye Makeup: Eyeliner, Brows & Brushes | Noyona Cosmetics and Skin Care Products OPC';
    if (is_page('hair')) return 'Hair Care & Styling Products | Noyona Cosmetics and Skin Care Products OPC';
    if (is_page('body')) return 'Body Care: Cleansers & Moisturizers | Noyona Cosmetics and Skin Care Products OPC';
    if (is_page('partner-brands')) return 'Noyona & Lovial | Partner Brands in Filipino Cosmetics & Malaysian Body Care';
    if (is_page('partner-brands/noyona')) return 'Noyona | Vegan, Cruelty-Free Filipino Cosmetics';
    if (is_page('partner-brands/lovial')) return 'Lovial | Trusted Malaysian Body Care Brand';
    if (is_page('about-noyona-essentials')) return 'About Noyona Essentials: Filipino Vegan & Cruelty-Free Cosmetics | Ethical Beauty PH';
    if (is_page('store-locations')) return 'Noyona Essentials Store Locations | Filipino Vegan Cosmetics Near You';
    if (is_page('careers')) return 'Noyona Essentials Careers | Join Our Filipino Cosmetics Team';
    if (is_page('blogs')) return 'Noyona Essentials Blogs | Insights on Filipino Vegan Cosmetics';
    if (is_page('contact')) return 'Noyona Essentials Contact Us | Customer Care & Support';
    if (is_page('faq')) return 'Noyona Essentials FAQs | Product, Safety & Customer Support';
    if (is_page('shipping-policy')) return 'Noyona Essentials Shipping Policy | Delivery & Orders';
    if (is_page('refund-policy')) return 'Noyona Essentials Refund Policy | Returns & Customer Care';
    if (is_page('products')) return 'Noyona Essentials Products | Vegan Filipino Cosmetics for Face, Lips, Eyes & Hair';
    if (is_page(array('accounts', 'account'))) return 'Noyona Essentials Account | Manage Your Cosmetics Profile';
    if (is_page(array('terms-of-service', 'terms-of-services'))) return 'Noyona Essentials Terms of Service | Policies & Customer Rights';
    return $title;
});

add_filter( 'woocommerce_get_checkout_url', function ( $url ) {
    return esc_url_raw( home_url( '/checkout/' ) );
}, 99 );

/**
 * Detect "order-pay" pages opened via our My Account "Pay Now" CTA.
 *
 * @return bool
 */
function noyona_is_order_pay_autostart_request() {
    if ( is_admin() || wp_doing_ajax() ) {
        return false;
    }

    if ( ! function_exists( 'is_wc_endpoint_url' ) ) {
        return false;
    }

    if ( ! is_wc_endpoint_url( 'order-pay' ) ) {
        return false;
    }

    $auto_flag = isset( $_GET['noyona_auto_pay'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['noyona_auto_pay'] ) ) : '';
    if ( '1' !== $auto_flag ) {
        return false;
    }

    return true;
}

/**
 * Hide order-pay page chrome while we auto-submit payment form.
 */
add_action( 'wp_head', 'noyona_render_order_pay_autostart_head', 99 );
function noyona_render_order_pay_autostart_head() {
    if ( ! noyona_is_order_pay_autostart_request() ) {
        return;
    }
    ?>
    <script>
    document.documentElement.classList.add('noyona-order-pay-autostart');
    </script>
    <style>
    html.noyona-order-pay-autostart body.woocommerce-order-pay .wp-site-blocks main,
    html.noyona-order-pay-autostart body.woocommerce-order-pay .wp-site-blocks .noyona-checkout-shell,
    html.noyona-order-pay-autostart body.woocommerce-order-pay .wp-site-blocks .woocommerce {
        opacity: 0;
        pointer-events: none;
    }
    html.noyona-order-pay-autostart body.woocommerce-order-pay.noyona-order-pay-autostart-ready .wp-site-blocks main,
    html.noyona-order-pay-autostart body.woocommerce-order-pay.noyona-order-pay-autostart-ready .wp-site-blocks .noyona-checkout-shell,
    html.noyona-order-pay-autostart body.woocommerce-order-pay.noyona-order-pay-autostart-ready .wp-site-blocks .woocommerce {
        opacity: 1;
        pointer-events: auto;
    }
    html.noyona-order-pay-autostart body.woocommerce-order-pay::before {
        content: "Opening payment...";
        position: fixed;
        inset: 0;
        z-index: 999999;
        display: grid;
        place-items: center;
        background: rgba(255, 255, 255, 0.96);
        color: #1f2933;
        font-weight: 600;
        letter-spacing: 0.02em;
    }
    html.noyona-order-pay-autostart body.woocommerce-order-pay.noyona-order-pay-autostart-ready::before {
        display: none;
    }
    </style>
    <?php
}

/**
 * Auto-submit WooCommerce order-pay form so users skip manual "Pay for order".
 */
add_action( 'wp_footer', 'noyona_render_order_pay_autostart_script', 120 );
function noyona_render_order_pay_autostart_script() {
    if ( ! noyona_is_order_pay_autostart_request() ) {
        return;
    }
    ?>
    <script>
    (function () {
        function revealManualScreen() {
            if (document.body) {
                document.body.classList.add('noyona-order-pay-autostart-ready');
            }
        }

        function autoSubmitOrderPay() {
            var form = document.querySelector('form#order_review, form.woocommerce-order-pay, form.woocommerce-checkout');
            if (!form) {
                revealManualScreen();
                return;
            }

            var orderMatch = window.location.pathname.match(/\/order-pay\/(\d+)/);
            var orderId = orderMatch && orderMatch[1] ? String(orderMatch[1]) : 'unknown';
            var orderKey = '';
            try {
                orderKey = String(new URLSearchParams(window.location.search || '').get('key') || '');
            } catch (e) {
                orderKey = '';
            }

            var storageKey = 'noyonaOrderPayAutoSubmit:' + orderId + ':' + orderKey;
            if (window.sessionStorage) {
                try {
                    if (window.sessionStorage.getItem(storageKey) === '1') {
                        revealManualScreen();
                        return;
                    }
                    window.sessionStorage.setItem(storageKey, '1');
                } catch (e) {
                    // Ignore storage failures.
                }
            }

            var methodRadios = form.querySelectorAll('input[name="payment_method"]');
            var checkedMethod = form.querySelector('input[name="payment_method"]:checked');
            if (!checkedMethod && methodRadios.length > 0) {
                methodRadios[0].checked = true;
                methodRadios[0].dispatchEvent(new Event('change', { bubbles: true }));
            }

            var terms = form.querySelector('#terms');
            if (terms && !terms.checked) {
                terms.checked = true;
                terms.dispatchEvent(new Event('change', { bubbles: true }));
            }

            var payButton = form.querySelector('#place_order, button[name="woocommerce_pay"], input[type="submit"][name="woocommerce_pay"], .form-row button[type="submit"]');
            if (!payButton || payButton.disabled) {
                revealManualScreen();
                return;
            }

            window.setTimeout(function () {
                try {
                    if (window.jQuery) {
                        window.jQuery(form).trigger('submit');
                    } else if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit(payButton);
                    } else {
                        payButton.click();
                    }
                } catch (error) {
                    revealManualScreen();
                }
            }, 180);

            window.setTimeout(function () {
                // If still on this page after a short delay, allow manual fallback.
                revealManualScreen();
            }, 4500);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', autoSubmitOrderPay);
        } else {
            autoSubmitOrderPay();
        }
    })();
    </script>
    <?php
}

/* =================================================
 * SEO: META DESCRIPTIONS
 * ================================================= */
add_action('wp_head', function () {

    if (is_front_page()) {
        echo '<meta name="description" content="Discover Noyona Essentials, a Filipino cosmetics brand rooted in nature. Vegan, cruelty-free products crafted for lasting beauty and everyday confidence.">';
        return;
    }

    if (is_page('shop')) {
        echo '<meta name="description" content="Explore Noyona Essentials cosmetics collection. Vegan, cruelty-free Filipino beauty rooted in nature. Shop keratin care, powder foundation, and dewy lip color.">';
        return;    
    }
    if (is_page('face-makeup')) {
        echo '<meta name="description" content="Shop face makeup essentials including foundation, concealer, and powder to achieve your perfect finish. Explore shades designed to complement a wide range of skin tones.">';
        return; 
    }
    if (is_page('lip-makeup')) {
        echo '<meta name="description" content="Shop Noyona Essentials lip makeup for morena skin, including matte, liquid, and creamy lipsticks in nude, red, plum, and pink shades, perfect for long-lasting, tropical-ready wear.">';
        return; 
    }
    if (is_page('eye-makeup')) {
        echo '<meta name="description" content="Define your look with waterproof eyeliner, brow products, and precision brushes. Explore lashes to create styles from soft and natural to bold and dramatic.">';
        return; 
    }
    if (is_page('hair')) {
        echo '<meta name="description" content="Nourish and style your hair with shampoos, conditioners, and treatments for healthy shine. Explore styling products from Noyona Essentials to achieve looks from sleek and polished to voluminous and textured.">';
        return; 
    }
    if (is_page('body')) {
        echo '<meta name="description" content="Pamper your skin with hydrating lotions and nourishing moisturizers. Explore gentle treatments from Noyona Essentials designed to deliver effective hydration and leave your skin feeling soft, smooth, and radiant.">';
        return; 
    }
    if (is_page('partner-brands')) {
        echo '<meta name="description" content="Explore Noyona and Lovial partner brands. Noyona offers vegan, cruelty-free Filipino cosmetics, while Lovial delivers trusted Malaysian body care products.">';
        return; 
    }
    if (is_page('partner-brands/noyona')) {
        echo '<meta name="description" content="Discover Noyona, a Filipino cosmetics brand offering vegan, cruelty-free makeup and hair care. Shop trusted products for face, lips, eyes, and hair.">';
        return; 
    }
    if (is_page('partner-brands/lovial')) {
        echo '<meta name="description" content="Discover Lovial, a trusted Malaysian body care brand. Offering nourishing, hydrating, and brightening products designed to keep your skin soft, smooth, and radiant.">';
        return; 
    }
    if (is_page('about-noyona-essentials')) {
        echo '<meta name="description" content="Learn about Noyona, a Filipino cosmetics brand offering vegan, cruelty-free beauty rooted in nature. Discover our story, mission, and values.">';
        return; 
    }
    if (is_page('store-locations')) {
        echo '<meta name="description" content="Find Noyona Essentials store locations. Explore vegan, cruelty-free Filipino cosmetics rooted in nature, available at select outlets for face, lips, eyes, and hair.">';
        return; 
    }
    if (is_page('careers')) {
        echo '<meta name="description" content="Explore career opportunities at Noyona Essentials. Join a Filipino cosmetics brand rooted in nature, offering roles in beauty, marketing, and product innovation.">';
        return; 
    }
    if (is_page('blogs')) {
        echo '<meta name="description" content="Read Noyona Essentials blogs for insights on vegan Filipino cosmetics, beauty trends, product tips, and updates from our cruelty-free brand.">';
        return; 
    }
    if (is_page('contact')) {
        echo '<meta name="description" content="Get in touch with Noyona Essentials. Contact our customer care team for inquiries, product support, shipping details, and trusted service in Filipino vegan cosmetics.">';
        return; 
    }
    if (is_page('faq')) {
        echo '<meta name="description" content="Find answers in the Noyona Essentials FAQs. Learn about vegan Filipino cosmetics, product safety, usage, certifications, shipping, returns, and customer support.">';
        return; 
    }
    if (is_page('shipping-policy')) {
        echo '<meta name="description" content="Review the Noyona Essentials Shipping Policy. Learn about delivery options, order processing times, shipping fees, and trusted service for Filipino vegan cosmetics.">';
        return; 
    }
    if (is_page('refund-policy')) {
        echo '<meta name="description" content="Review the Noyona Essentials Refund Policy. Learn about returns, refunds, and customer care support for vegan Filipino cosmetics rooted in nature.">';
        return; 
    }
    if (is_page('products')) {
        echo '<meta name="description" content="Shop Noyona Essentials products. Explore vegan, cruelty-free Filipino cosmetics rooted in nature, with trusted collections for face, lips, eyes, and hair care.">';
        return; 
    }
    if (is_page(array('accounts', 'account'))) {
        echo '<meta name="description" content="Access your Noyona Essentials account. Manage orders, track shipping, update details, and enjoy personalized service for vegan Filipino cosmetics rooted in nature.">';
        return; 
    }
    if (is_page(array('terms-of-service', 'terms-of-services'))) {
        echo '<meta name="description" content="Review the Noyona Essentials Terms of Service. Learn about customer rights, policies, and trusted guidelines for shopping vegan Filipino cosmetics rooted in nature.">';
        return; 
    }
});

add_action('wp_footer', function () {
    if (!is_checkout()) return;
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const paragraphs = document.querySelectorAll('p');

        paragraphs.forEach(function (p) {
            // Ignore important WooCommerce rows
            if (p.classList.contains('form-row')) return;

            // Check if empty (no text and no children except whitespace)
            if (
                p.textContent.trim() === '' &&
                p.children.length === 0
            ) {
                p.remove();
            }
        });
    });
    </script>
    <?php
});

add_action( 'wp_footer', 'noyona_render_global_checkout_login_modal', 40 );
function noyona_render_global_checkout_login_modal() {
    if ( is_admin() || is_user_logged_in() ) {
        return;
    }

    $login_action_url = wp_login_url();
    $register_url     = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_registration_url();
    $redirect_to      = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' );
    $google_login_url = noyona_get_google_login_url( $redirect_to );
    ?>
    <div
        id="noyona-global-checkout-login-modal"
        class="noyona-mini-cart-login-modal"
        data-mini-cart-login-modal-global
        hidden
    >
        <button class="noyona-mini-cart-login-backdrop" type="button" data-mini-cart-login-close aria-label="<?php esc_attr_e( 'Close login modal', 'noyona-childtheme' ); ?>"></button>
        <div class="noyona-mini-cart-login-dialog" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Log in before checkout', 'noyona-childtheme' ); ?>">
            <button class="noyona-mini-cart-login-close" type="button" data-mini-cart-login-close aria-label="<?php esc_attr_e( 'Close login modal', 'noyona-childtheme' ); ?>">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
            <div class="noyona-mini-cart-login-icon" aria-hidden="true">
                <i class="fa-solid fa-lock"></i>
            </div>

            <h3 class="noyona-mini-cart-login-title"><?php esc_html_e( 'Log In to continue checkout', 'noyona-childtheme' ); ?></h3>
            <p class="noyona-mini-cart-login-copy"><?php esc_html_e( 'Please log in to your account before checking out.', 'noyona-childtheme' ); ?></p>

            <form class="noyona-mini-cart-login-form" method="post" action="<?php echo esc_url( $login_action_url ); ?>">
                <label for="noyona-mini-cart-login-email"><?php esc_html_e( 'Email', 'noyona-childtheme' ); ?></label>
                <input id="noyona-mini-cart-login-email" name="log" type="text" required placeholder="<?php esc_attr_e( 'your@email.com', 'noyona-childtheme' ); ?>" />

                <label for="noyona-mini-cart-login-password"><?php esc_html_e( 'Password', 'noyona-childtheme' ); ?></label>
                <input id="noyona-mini-cart-login-password" name="pwd" type="password" required placeholder="<?php esc_attr_e( 'Enter your password', 'noyona-childtheme' ); ?>" />

                <input type="hidden" name="redirect_to" data-mini-cart-login-redirect value="<?php echo esc_url( $redirect_to ); ?>" />
                <button type="submit" class="noyona-mini-cart-login-submit"><?php esc_html_e( 'Log In', 'noyona-childtheme' ); ?></button>
            </form>

            <div class="noyona-mini-cart-login-separator" aria-hidden="true">
                <span></span><em><?php esc_html_e( 'or', 'noyona-childtheme' ); ?></em><span></span>
            </div>

            <a class="noyona-mini-cart-login-google" data-mini-cart-login-action href="<?php echo esc_url( $google_login_url ); ?>">
                <i class="fa-brands fa-google" aria-hidden="true"></i>
                <span><?php esc_html_e( 'Login with Google', 'noyona-childtheme' ); ?></span>
            </a>

            <a class="noyona-mini-cart-login-register" data-mini-cart-register-action href="<?php echo esc_url( $register_url ); ?>">
                <?php esc_html_e( 'Create an Account', 'noyona-childtheme' ); ?>
            </a>

            <p class="noyona-mini-cart-login-note"><?php esc_html_e( 'You must be logged in to proceed to checkout.', 'noyona-childtheme' ); ?></p>
        </div>
    </div>
    <?php
}

add_filter('woocommerce_get_item_data', function($item_data, $cart_item) {
    foreach ($item_data as &$item) {
        // Remove any <br> tags from display value
        if (isset($item['display'])) {
            $item['display'] = str_replace('<br>', '', $item['display']);
            $item['display'] = str_replace('<br/>', '', $item['display']);
            $item['display'] = str_replace('<br />', '', $item['display']);
        }
    }
    return $item_data;
}, 10, 2);