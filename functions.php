<?php
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

    // Header behavior (sticky / color change / wishlist toggle)
    wp_enqueue_script(
        'woocom-ct-header',
        get_stylesheet_directory_uri() . '/assets/js/header.js',
        array(),
        wp_get_theme()->get( 'Version' ),
        true
    );

    $logout_url = function_exists( 'wc_logout_url' ) && function_exists( 'wc_get_page_permalink' )
        ? wc_logout_url( wc_get_page_permalink( 'myaccount' ) )
        : wp_logout_url( home_url( '/' ) );

    $shop_price_step = 150;
    $shop_price_max  = 0;
    $is_shop_archive = function_exists( 'is_shop' ) && ( is_shop() || is_tax( 'product_cat' ) );
    if ( $is_shop_archive && class_exists( 'WooCommerce' ) && function_exists( 'wc_get_products' ) ) {
        $max_price_args = array(
            'limit'   => 1,
            'orderby' => 'price',
            'order'   => 'DESC',
            'status'  => 'publish',
            'return'  => 'ids',
        );

        if ( is_tax( 'product_cat' ) ) {
            $term = get_queried_object();
            if ( $term instanceof WP_Term ) {
                $max_price_args['category'] = array( $term->slug );
            }
        }

        $product_ids = wc_get_products( $max_price_args );

        if ( ! empty( $product_ids ) ) {
            $top_product = wc_get_product( (int) $product_ids[0] );
            if ( $top_product ) {
                $shop_price_max = (float) $top_product->get_price();
            }
        }
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
            'checkoutUrl'     => function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' ),
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

add_filter( 'wp_preload_resources', 'noyona_preload_home_hero_image' );
function noyona_preload_home_hero_image( $preload_resources ) {
    if ( ! is_front_page() ) {
        return $preload_resources;
    }

    $preload_resources[] = array(
        'href'          => 'https://noyonacosmetics.com/wp-content/uploads/2026/02/hero-banner.webp',
        'as'            => 'image',
        'fetchpriority' => 'high',
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

    if ( is_cart() || is_checkout() || is_account_page() || is_woocommerce() ) {
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
    $safeguard_version = '1';
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

add_action( 'woocommerce_login_form_end', 'woocom_ct_add_register_link_to_login' );
function woocom_ct_add_register_link_to_login() {
    echo '<p class="noyona-login-register-link"><a href="' . esc_url( home_url( '/register/' ) ) . '">Registration</a></p>';
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

    $products = wc_get_products( array(
        'status'   => 'publish',
        'limit'    => 4,
        'orderby'  => 'title',
        'order'    => 'ASC',
        'category' => array( $slug_lower ),
        'return'   => 'objects',
    ) );

    $has_more = count( wc_get_products( array(
        'status'   => 'publish',
        'limit'    => 5,
        'category' => array( $slug_lower ),
        'return'   => 'ids',
    ) ) ) > 4;

    if ( empty( $products ) ) {
        return '<div class="wp-block-woocommerce-product-collection noyona-shop-products"><div class="wc-block-product-template" style="display:grid;grid-template-columns:repeat(2,1fr);gap:18px;"><div class="wc-block-product" style="grid-column:1/-1;padding:32px;text-align:center;border-radius:16px;background:#f9f9f9;"><p class="has-text-align-center has-vivid-pink-cyan-color has-text-color" style="font-size:30px;font-weight:700">Coming Soon</p></div></div></div>';
    }

    $shop_category_url = home_url( user_trailingslashit( 'shop/' . $slug_lower ) );
    $category_label    = ucfirst( $slug_lower );

    ob_start();
    echo '<div class="wp-block-woocommerce-product-collection noyona-shop-products"><div class="wc-block-product-template" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;">';
    foreach ( $products as $product ) {
        $image_id = $product->get_image_id();
        $image    = $image_id ? wp_get_attachment_image( $image_id, 'woocommerce_thumbnail', false, array( 'class' => 'wc-block-components-product-image' ) ) : wc_placeholder_img( 'woocommerce_thumbnail' );
        $title    = $product->get_name();
        $link     = $product->get_permalink();
        $excerpt  = has_excerpt( $product->get_id() ) ? get_the_excerpt( $product->get_id() ) : '';
        if ( $excerpt ) {
            $excerpt = wp_trim_words( $excerpt, 22 );
        }
        $price_html = $product->get_price_html();
        $add_to_cart_url = $product->add_to_cart_url();
        ?>
        <div class="wc-block-product">
            <a href="<?php echo esc_url( $link ); ?>" class="wc-block-components-product-image"><?php echo $image; ?></a>
            <h3 class="wc-block-product-title wp-block-post-title" style="font-size:16px;font-weight:700;line-height:1.25;margin:0 0 8px;"><a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $title ); ?></a></h3>
            <?php if ( $excerpt ) : ?>
                <div class="wp-block-post-excerpt" style="font-size:13px;color:#666;margin:0 0 12px;"><p style="margin:0"><?php echo esc_html( $excerpt ); ?></p></div>
            <?php endif; ?>
            <div class="wc-block-components-product-price" style="font-size:15px;font-weight:700;margin:0 0 12px;"><?php echo $price_html; ?></div>
            <div class="wp-block-buttons"><div class="wp-block-button">
                <a href="<?php echo esc_url( $add_to_cart_url ); ?>" class="wp-block-button__link add_to_cart_button ajax_add_to_cart" data-product_id="<?php echo (int) $product->get_id(); ?>" data-product_sku="<?php echo esc_attr( $product->get_sku() ); ?>" style="width:44px;height:44px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;color:#fff;border:2px solid #e11a63;"><?php echo esc_html__( 'Add to cart', 'woocommerce' ); ?></a>
            </div></div>
        </div>
        <?php
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
function woocom_ct_register_form_shortcode() {
    $account_url = function_exists( 'wc_get_page_permalink' )
        ? wc_get_page_permalink( 'myaccount' )
        : home_url( '/my-account/' );

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
            <h2 class="noyona-register-title">Create Account</h2>

            <?php if ( $message ) : ?>
                <div class="noyona-register-message<?php echo esc_attr( $message_class ); ?>">
                    <?php echo esc_html( $message ); ?>
                </div>
            <?php endif; ?>

            <form class="noyona-register-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'noyona_register_action', 'noyona_register_nonce' ); ?>
                <input type="hidden" name="action" value="noyona_register">

                <div class="noyona-register-row">
                    <div class="noyona-register-field">
                        <label class="screen-reader-text" for="noyona-register-first-name">First Name</label>
                        <input id="noyona-register-first-name" type="text" name="first_name" autocomplete="given-name" placeholder="First name" required>
                    </div>

                    <div class="noyona-register-field">
                        <label class="screen-reader-text" for="noyona-register-middle-name">Middle Name</label>
                        <input id="noyona-register-middle-name" type="text" name="middle_name" autocomplete="additional-name" placeholder="Middle name" required>
                    </div>
                </div>

                <div class="noyona-register-row">
                    <div class="noyona-register-field">
                        <label class="screen-reader-text" for="noyona-register-last-name">Last Name</label>
                        <input id="noyona-register-last-name" type="text" name="last_name" autocomplete="family-name" placeholder="Last name" required>
                    </div>

                    <div class="noyona-register-field">
                        <label class="screen-reader-text" for="noyona-register-email">Email</label>
                        <input id="noyona-register-email" type="email" name="email" autocomplete="email" placeholder="Your email" required>
                    </div>
                </div>

                <div class="noyona-register-row">
                    <div class="noyona-register-field">
                        <label class="screen-reader-text" for="noyona-register-password">Password</label>
                        <input id="noyona-register-password" type="password" name="password" autocomplete="new-password" placeholder="Password" required>
                    </div>

                    <div class="noyona-register-field">
                        <label class="screen-reader-text" for="noyona-register-confirm">Confirm Password</label>
                        <input id="noyona-register-confirm" type="password" name="confirm_password" autocomplete="new-password" placeholder="Repeat your password" required>
                    </div>
                </div>

                <label class="noyona-register-terms">
                    <input type="checkbox" name="terms" value="1">
                    <span>I agree all statements in <a href="/terms-of-service/">Terms of service</a></span>
                </label>

                <button class="noyona-register-submit" type="submit">Sign Up</button>
            </form>

            <p class="noyona-register-login">
                Have already an account ? <a href="<?php echo esc_url( $account_url ); ?>">Login here</a>
            </p>
        </div>
    </div>
    <?php
    return ob_get_clean();
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

    $first_name = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
    $middle_name = isset( $_POST['middle_name'] ) ? sanitize_text_field( wp_unslash( $_POST['middle_name'] ) ) : '';
    $last_name = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
    $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
    $password = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';
    $confirm_password = isset( $_POST['confirm_password'] ) ? (string) wp_unslash( $_POST['confirm_password'] ) : '';

    if ( '' === $first_name || '' === $middle_name || '' === $last_name || '' === $email || '' === $password || '' === $confirm_password ) {
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
    $display_name = trim( $first_name . ' ' . $last_name );

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

    update_user_meta( $user_id, 'middle_name', $middle_name );
    if ( function_exists( 'wc_create_new_customer' ) ) {
        update_user_meta( $user_id, 'billing_first_name', $first_name );
        update_user_meta( $user_id, 'billing_last_name', $last_name );
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
        false === strpos( $block_content, 'site-logo-img--mobile' ) &&
        false === strpos( $block_content, 'header-cart-fallback' )
    ) {
        return $block_content;
    }

    // Remove unexpected cart fallback icon/link injected by upstream block output.
    $block_content = preg_replace(
        '#<a[^>]*class=(["\'])[^"\']*\bheader-cart-fallback\b[^"\']*\1[^>]*>.*?</a>#is',
        '',
        $block_content
    );

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
//         echo '<meta name="robots" content="noindex, nofollow, noarchive, nosnippet">';
//         return;
//     }
//     echo '<meta name="robots" content="index, follow">';
// }, 1);

/* =================================================
 * GLOBAL ROBOTS HEADERS
 * ================================================= */
// add_action('send_headers', function () {
//     if ( noyona_is_site_under_development() ) {
//         header('X-Robots-Tag: noindex, nofollow, noarchive, nosnippet', true);
//         return;
//     }
//     header('X-Robots-Tag: index, follow, archive, snippet', true);
// });

/* =================================================
 * SEO / CRAWL CONTROL
 * ================================================= */
function noyona_is_site_under_development() {
    return (bool) apply_filters( 'noyona_site_under_development', true );
}

/**
 * Disable WordPress core XML sitemaps.
 */
add_filter( 'wp_sitemaps_enabled', '__return_false' );

/**
 * Force a restrictive robots.txt from WordPress.
 * Note: this discourages crawlers, but does not secure URLs from direct browser access.
 */
add_filter( 'robots_txt', 'noyona_custom_robots_txt', 999, 2 );
function noyona_custom_robots_txt( $output, $public ) {
    if ( ! noyona_is_site_under_development() ) {
        return implode( "\n", array(
            'User-agent: *',
            'Allow: /',
        ) );
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
    if ( noyona_is_site_under_development() ) {
        echo '<meta name="robots" content="noindex, nofollow, noarchive, nosnippet, noimageindex, max-snippet:0, max-image-preview:none, max-video-preview:0">' . "\n";
        return;
    }

    echo '<meta name="robots" content="index, follow">' . "\n";
}

/**
 * Send X-Robots-Tag header.
 */
add_action( 'send_headers', 'noyona_send_robots_headers', 1 );
function noyona_send_robots_headers() {
    if ( noyona_is_site_under_development() ) {
        header( 'X-Robots-Tag: noindex, nofollow, noarchive, nosnippet, noimageindex, max-snippet:0, max-image-preview:none, max-video-preview:0', true );
        return;
    }

    header( 'X-Robots-Tag: index, follow, archive, snippet', true );
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