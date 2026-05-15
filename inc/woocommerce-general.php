<?php
/**
 * Generic WooCommerce customisations: shop archive, product collection blocks, mini overrides.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ----- Products per page on shop/category archives ----- */
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

/* ----- Shop-category cookie (for Store API filtering) ----- */
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

/* ----- Replace landing page product-collection block ----- */
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

/* ----- Replace shop archive product cards ----- */
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

/* ----- Force product category on Store API queries ----- */
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

/* ----- Remove customer-account block from output ----- */
add_filter( 'render_block', 'woocom_ct_remove_customer_account_block', 10, 2 );
function woocom_ct_remove_customer_account_block( $block_content, $block ) {
    if ( ! empty( $block['blockName'] ) && 'woocommerce/customer-account' === $block['blockName'] ) {
        return '';
    }

    return $block_content;
}

/* ----- Strip <br> tags from Woo cart item data ----- */
add_filter( 'woocommerce_get_item_data', 'noyona_strip_br_from_woo_item_data', 10, 2 );
function noyona_strip_br_from_woo_item_data( $item_data, $cart_item ) {
    foreach ($item_data as &$item) {
        // Remove any <br> tags from display value
        if (isset($item['display'])) {
            $item['display'] = str_replace('<br>', '', $item['display']);
            $item['display'] = str_replace('<br/>', '', $item['display']);
            $item['display'] = str_replace('<br />', '', $item['display']);
        }
    }
    return $item_data;
}
