<?php
/**
 * Shared helper utilities used across the theme (images, URLs, price ranges, env).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ----- Media-Library-aware image renderer ----- */
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

/* ----- Login page context detection ----- */
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

/* ----- Login page URL resolver ----- */
function noyona_get_login_page_url() {
    $page = get_page_by_path( 'login' );
    if ( $page instanceof WP_Post && 'publish' === $page->post_status ) {
        return get_permalink( $page );
    }

    return home_url( '/login/' );
}

/* ----- Account page URL resolver ----- */
function noyona_get_account_page_url() {
    $account_url = function_exists( 'wc_get_page_permalink' )
        ? wc_get_page_permalink( 'myaccount' )
        : home_url( '/my-account/' );

    if ( '' === trim( (string) $account_url ) ) {
        $account_url = home_url( '/my-account/' );
    }

    return (string) $account_url;
}

/* ----- Lost-password page URL resolver ----- */
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

/* ----- Google login URL (Nextend Social Login with fallback) ----- */
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

/* ----- Shop category landing page slugs ----- */
/**
 * Category page slugs (WooCommerce product_cat slugs) for Eyes, Face, Lips, Hair, Body.
 * Used to force Store API product queries to filter by this category when on the corresponding page.
 */
function noyona_get_shop_category_page_slugs() {
    return array( 'face', 'lips', 'eyes', 'hair', 'body' );
}

/* ----- Maximum product price (optionally per category) ----- */
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

/* ----- Apply min/max price filter to wc_get_products args ----- */
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

/* ----- Final PHP-level guard for price filtering ----- */
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

/* ----- Render a single product card ----- */
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

/* ----- Product search results page renderer ----- */
add_shortcode( 'noyona_product_search_page', 'noyona_render_product_search_page_shortcode' );
function noyona_render_product_search_page_shortcode() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return '';
    }

    $query_text      = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : get_search_query( false );
    $query_text      = trim( (string) $query_text );
    $selected_cat    = isset( $_GET['product_cat'] ) ? sanitize_key( wp_unslash( $_GET['product_cat'] ) ) : '';
    $selected_order  = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'relevance';
    $min_price       = isset( $_GET['min_price'] ) && '' !== (string) $_GET['min_price'] ? floatval( wp_unslash( $_GET['min_price'] ) ) : null;
    $max_price       = isset( $_GET['max_price'] ) && '' !== (string) $_GET['max_price'] ? floatval( wp_unslash( $_GET['max_price'] ) ) : null;
    $stock_statuses  = array();
    $stock_param     = isset( $_GET['stock_status'] ) ? wp_unslash( $_GET['stock_status'] ) : array();
    $stock_param     = is_array( $stock_param ) ? $stock_param : array( $stock_param );
    foreach ( $stock_param as $stock_status ) {
        $stock_status = sanitize_key( (string) $stock_status );
        if ( in_array( $stock_status, array( 'instock', 'outofstock' ), true ) ) {
            $stock_statuses[] = $stock_status;
        }
    }
    $stock_statuses = array_values( array_unique( $stock_statuses ) );

    $per_page     = 12;
    $current_page = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : max( 1, absint( get_query_var( 'paged', 1 ) ) );

    $query_args = array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => $per_page,
        'paged'          => $current_page,
    );

    if ( '' !== $query_text ) {
        $query_args['s'] = $query_text;
    }

    if ( '' !== $selected_cat ) {
        $term = get_term_by( 'slug', $selected_cat, 'product_cat' );
        if ( $term instanceof WP_Term ) {
            $query_args['tax_query'] = array(
                array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'slug',
                    'terms'    => array( $selected_cat ),
                ),
            );
        } else {
            $selected_cat = '';
        }
    }

    $meta_query = array();
    if ( ! empty( $stock_statuses ) ) {
        $meta_query[] = array(
            'key'     => '_stock_status',
            'value'   => $stock_statuses,
            'compare' => 'IN',
        );
    }

    if ( null !== $min_price || null !== $max_price ) {
        if ( null !== $min_price && null !== $max_price && $max_price < $min_price ) {
            $swap      = $min_price;
            $min_price = $max_price;
            $max_price = $swap;
        }

        if ( null !== $min_price ) {
            $meta_query[] = array(
                'key'     => '_price',
                'value'   => $min_price,
                'compare' => '>=',
                'type'    => 'NUMERIC',
            );
        }

        if ( null !== $max_price ) {
            $meta_query[] = array(
                'key'     => '_price',
                'value'   => $max_price,
                'compare' => '<=',
                'type'    => 'NUMERIC',
            );
        }
    }

    if ( ! empty( $meta_query ) ) {
        if ( count( $meta_query ) > 1 ) {
            $meta_query['relation'] = 'AND';
        }
        $query_args['meta_query'] = $meta_query;
    }

    switch ( $selected_order ) {
        case 'price':
            $query_args['meta_key'] = '_price';
            $query_args['orderby']  = 'meta_value_num';
            $query_args['order']    = 'ASC';
            break;
        case 'price-desc':
            $query_args['meta_key'] = '_price';
            $query_args['orderby']  = 'meta_value_num';
            $query_args['order']    = 'DESC';
            break;
        case 'date':
            $query_args['orderby'] = 'date';
            $query_args['order']   = 'DESC';
            break;
        case 'title':
            $query_args['orderby'] = 'title';
            $query_args['order']   = 'ASC';
            break;
        case 'relevance':
        default:
            $selected_order = 'relevance';
            break;
    }

    $products_query = new WP_Query( $query_args );
    $result_count   = (int) $products_query->found_posts;

    $base_params = array(
        's'         => $query_text,
        'post_type' => 'product',
    );
    if ( '' !== $selected_cat ) {
        $base_params['product_cat'] = $selected_cat;
    }
    if ( 'relevance' !== $selected_order ) {
        $base_params['orderby'] = $selected_order;
    }
    if ( null !== $min_price ) {
        $base_params['min_price'] = (string) $min_price;
    }
    if ( null !== $max_price ) {
        $base_params['max_price'] = (string) $max_price;
    }
    foreach ( $stock_statuses as $index => $stock_status ) {
        $base_params[ 'stock_status[' . $index . ']' ] = $stock_status;
    }

    ob_start();
    ?>
    <section class="noyona-search-hero alignwide">
        <div class="noyona-search-hero__copy">
            <p class="noyona-search-hero__eyebrow"><?php esc_html_e( 'Search Results', 'noyona-childtheme' ); ?></p>
            <h1><?php esc_html_e( 'Search Results', 'noyona-childtheme' ); ?></h1>
            <p class="noyona-search-hero__count">
                <?php
                printf(
                    esc_html__( '%1$d result(s) found for "%2$s"', 'noyona-childtheme' ),
                    $result_count,
                    esc_html( $query_text )
                );
                ?>
            </p>
        </div>
        <form class="noyona-search-again" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" role="search">
            <label class="screen-reader-text" for="noyona-search-again-input"><?php esc_html_e( 'Search products again', 'noyona-childtheme' ); ?></label>
            <input id="noyona-search-again-input" type="search" name="s" value="<?php echo esc_attr( $query_text ); ?>" placeholder="<?php esc_attr_e( 'Search products', 'noyona-childtheme' ); ?>" />
            <input type="hidden" name="post_type" value="product" />
            <button type="submit"><?php esc_html_e( 'Search Again', 'noyona-childtheme' ); ?></button>
        </form>
    </section>

    <section class="alignwide noyona-shop-top noyona-search-products-head">
        <h2 class="noyona-shop-title"><?php esc_html_e( 'All Products', 'noyona-childtheme' ); ?></h2>
        <nav class="noyona-shop-categories noyona-search-categories" aria-label="<?php esc_attr_e( 'Product categories', 'noyona-childtheme' ); ?>">
            <?php echo noyona_render_product_search_category_pills( $query_text, $selected_cat, $base_params ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </nav>
    </section>

    <div class="wp-block-columns alignwide noyona-shop-layout">
        <div class="wp-block-column noyona-shop-sidebar" style="flex-basis:28%">
            <button type="button" class="noyona-shop-filter-overlay" aria-label="<?php esc_attr_e( 'Close filter panel', 'noyona-childtheme' ); ?>"></button>
            <aside id="noyona-shop-filter-panel" class="wp-block-group noyona-shop-filters" aria-label="<?php esc_attr_e( 'Product filters', 'noyona-childtheme' ); ?>">
                <button type="button" class="noyona-shop-filter-close" aria-label="<?php esc_attr_e( 'Close filters', 'noyona-childtheme' ); ?>">✕</button>
                <form class="noyona-search-filter-form" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                    <input type="hidden" name="s" value="<?php echo esc_attr( $query_text ); ?>" />
                    <input type="hidden" name="post_type" value="product" />
                    <?php if ( '' !== $selected_cat ) : ?>
                        <input type="hidden" name="product_cat" value="<?php echo esc_attr( $selected_cat ); ?>" />
                    <?php endif; ?>
                    <?php if ( 'relevance' !== $selected_order ) : ?>
                        <input type="hidden" name="orderby" value="<?php echo esc_attr( $selected_order ); ?>" />
                    <?php endif; ?>

                    <div class="noyona-shop-filter-sections">
                        <section class="noyona-shop-filter-section">
                            <h5><?php esc_html_e( 'Stock Status', 'noyona-childtheme' ); ?></h5>
                            <label>
                                <input type="checkbox" name="stock_status[]" value="instock" <?php checked( in_array( 'instock', $stock_statuses, true ) ); ?> />
                                <?php esc_html_e( 'In Stock', 'noyona-childtheme' ); ?>
                            </label>
                            <label>
                                <input type="checkbox" name="stock_status[]" value="outofstock" <?php checked( in_array( 'outofstock', $stock_statuses, true ) ); ?> />
                                <?php esc_html_e( 'Out of Stock', 'noyona-childtheme' ); ?>
                            </label>
                        </section>

                        <section class="noyona-shop-filter-section noyona-shop-filter-section-price">
                            <h5 class="noyona-shop-filter-price-title"><?php esc_html_e( 'Price', 'noyona-childtheme' ); ?></h5>
                            <div class="noyona-shop-filter-price" data-min="0" data-step="50">
                                <div class="noyona-shop-filter-price-slider" role="group" aria-label="<?php esc_attr_e( 'Price range', 'noyona-childtheme' ); ?>">
                                    <div class="noyona-shop-filter-price-track"></div>
                                    <div class="noyona-shop-filter-price-track-fill"></div>
                                    <button type="button" class="noyona-shop-filter-price-thumb is-min" aria-label="<?php esc_attr_e( 'Minimum price handle', 'noyona-childtheme' ); ?>"></button>
                                    <button type="button" class="noyona-shop-filter-price-thumb is-max" aria-label="<?php esc_attr_e( 'Maximum price handle', 'noyona-childtheme' ); ?>"></button>
                                </div>
                                <div class="noyona-shop-filter-price-inputs">
                                    <label class="noyona-shop-filter-price-input">
                                        <span aria-hidden="true">₱</span>
                                        <input type="number" inputmode="numeric" min="0" step="1" value="<?php echo esc_attr( null !== $min_price ? (string) $min_price : '' ); ?>" name="min_price" placeholder="0" aria-label="<?php esc_attr_e( 'Minimum price', 'noyona-childtheme' ); ?>" />
                                    </label>
                                    <span class="noyona-shop-filter-price-separator" aria-hidden="true">-</span>
                                    <label class="noyona-shop-filter-price-input">
                                        <span aria-hidden="true">₱</span>
                                        <input type="number" inputmode="numeric" min="0" step="1" value="<?php echo esc_attr( null !== $max_price ? (string) $max_price : '' ); ?>" name="max_price" placeholder="0" aria-label="<?php esc_attr_e( 'Maximum price', 'noyona-childtheme' ); ?>" />
                                    </label>
                                </div>
                                <div class="noyona-shop-filter-price-labels" aria-hidden="true"></div>
                            </div>
                        </section>
                    </div>

                    <div class="noyona-search-filter-actions">
                        <button type="submit"><?php esc_html_e( 'Apply Filters', 'noyona-childtheme' ); ?></button>
                        <a href="<?php echo esc_url( add_query_arg( array( 's' => $query_text, 'post_type' => 'product' ), home_url( '/' ) ) ); ?>"><?php esc_html_e( 'Reset', 'noyona-childtheme' ); ?></a>
                    </div>
                </form>
            </aside>
        </div>

        <div class="wp-block-column noyona-shop-products-column" style="flex-basis:72%">
            <div class="wp-block-group noyona-shop-toolbar">
                <p class="noyona-shop-count">
                    <?php
                    printf(
                        esc_html( _n( '%d product found', '%d products found', $result_count, 'noyona-childtheme' ) ),
                        $result_count
                    );
                    ?>
                </p>
                <div class="wp-block-group noyona-shop-toolbar-right">
                    <div class="noyona-shop-view-wrapper">
                        <span class="noyona-shop-view-label"><?php esc_html_e( 'View As', 'noyona-childtheme' ); ?></span>
                        <div class="noyona-shop-view-toggle" aria-label="<?php esc_attr_e( 'View', 'noyona-childtheme' ); ?>">
                            <button type="button" class="is-active" data-shop-view="grid" aria-pressed="true">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3 3h8v8H3zM13 3h8v8h-8zM3 13h8v8H3zM13 13h8v8h-8z"/></svg>
                            </button>
                            <button type="button" data-shop-view="list" aria-pressed="false">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3 4h18v4H3zM3 10h18v4H3zM3 16h18v4H3z"/></svg>
                            </button>
                        </div>
                    </div>
                    <button type="button" class="noyona-shop-filter-toggle" aria-expanded="false" aria-controls="noyona-shop-filter-panel"><?php esc_html_e( 'Filter', 'noyona-childtheme' ); ?></button>
                    <form class="noyona-shop-sort noyona-search-sort" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                        <input type="hidden" name="s" value="<?php echo esc_attr( $query_text ); ?>" />
                        <input type="hidden" name="post_type" value="product" />
                        <?php if ( '' !== $selected_cat ) : ?>
                            <input type="hidden" name="product_cat" value="<?php echo esc_attr( $selected_cat ); ?>" />
                        <?php endif; ?>
                        <?php foreach ( $stock_statuses as $stock_status ) : ?>
                            <input type="hidden" name="stock_status[]" value="<?php echo esc_attr( $stock_status ); ?>" />
                        <?php endforeach; ?>
                        <?php if ( null !== $min_price ) : ?>
                            <input type="hidden" name="min_price" value="<?php echo esc_attr( (string) $min_price ); ?>" />
                        <?php endif; ?>
                        <?php if ( null !== $max_price ) : ?>
                            <input type="hidden" name="max_price" value="<?php echo esc_attr( (string) $max_price ); ?>" />
                        <?php endif; ?>
                        <label class="screen-reader-text" for="noyona-product-search-sort"><?php esc_html_e( 'Sort products', 'noyona-childtheme' ); ?></label>
                        <select id="noyona-product-search-sort" name="orderby" onchange="this.form.submit()">
                            <option value="relevance" <?php selected( $selected_order, 'relevance' ); ?>><?php esc_html_e( 'Sort by relevance', 'noyona-childtheme' ); ?></option>
                            <option value="date" <?php selected( $selected_order, 'date' ); ?>><?php esc_html_e( 'Sort by latest', 'noyona-childtheme' ); ?></option>
                            <option value="title" <?php selected( $selected_order, 'title' ); ?>><?php esc_html_e( 'Sort by name', 'noyona-childtheme' ); ?></option>
                            <option value="price" <?php selected( $selected_order, 'price' ); ?>><?php esc_html_e( 'Sort by price: low to high', 'noyona-childtheme' ); ?></option>
                            <option value="price-desc" <?php selected( $selected_order, 'price-desc' ); ?>><?php esc_html_e( 'Sort by price: high to low', 'noyona-childtheme' ); ?></option>
                        </select>
                    </form>
                </div>
            </div>

            <div class="wp-block-woocommerce-product-collection noyona-shop-products">
                <div class="wc-block-product-template" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;">
                    <?php if ( $products_query->have_posts() ) : ?>
                        <?php
                        while ( $products_query->have_posts() ) :
                            $products_query->the_post();
                            $product = wc_get_product( get_the_ID() );
                            if ( $product instanceof WC_Product ) {
                                echo noyona_render_product_card( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            }
                        endwhile;
                        wp_reset_postdata();
                        ?>
                    <?php else : ?>
                        <div class="wc-block-product noyona-search-no-products">
                            <p><?php esc_html_e( 'No products found. Try another search or adjust your filters.', 'noyona-childtheme' ); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                <?php
                $pagination = paginate_links(
                    array(
                        'base'      => add_query_arg( array_merge( $base_params, array( 'paged' => '%#%' ) ), home_url( '/' ) ),
                        'format'    => '',
                        'current'   => $current_page,
                        'total'     => max( 1, (int) $products_query->max_num_pages ),
                        'type'      => 'array',
                        'prev_text' => __( 'Previous', 'noyona-childtheme' ),
                        'next_text' => __( 'Next', 'noyona-childtheme' ),
                    )
                );
                if ( is_array( $pagination ) && count( $pagination ) > 1 ) :
                    ?>
                    <nav class="wp-block-query-pagination" aria-label="<?php esc_attr_e( 'Product search pagination', 'noyona-childtheme' ); ?>">
                        <div class="wp-block-query-pagination-numbers">
                            <?php echo wp_kses_post( implode( "\n", $pagination ) ); ?>
                        </div>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php

    return trim( ob_get_clean() );
}

function noyona_render_product_search_category_pills( $query_text, $selected_cat, $current_params = array() ) {
    $slugs = function_exists( 'noyona_get_shop_category_page_slugs' )
        ? noyona_get_shop_category_page_slugs()
        : array( 'face', 'lips', 'eyes', 'hair', 'body' );
    $base_args = wp_parse_args(
        (array) $current_params,
        array(
            's'         => (string) $query_text,
            'post_type' => 'product',
        )
    );
    $base_args['s']         = (string) $query_text;
    $base_args['post_type'] = 'product';
    unset( $base_args['product_cat'], $base_args['paged'] );

    $html  = '<a class="noyona-shop-category-all' . ( '' === $selected_cat ? ' is-active' : '' ) . '" href="' . esc_url( add_query_arg( $base_args, home_url( '/' ) ) ) . '">';
    $html .= esc_html__( 'All Products', 'noyona-childtheme' );
    $html .= '</a>';
    $html .= '<ul class="wc-block-product-categories-list">';

    foreach ( $slugs as $slug ) {
        $slug = sanitize_key( (string) $slug );
        $term = get_term_by( 'slug', $slug, 'product_cat' );
        $name = $term instanceof WP_Term ? $term->name : ucfirst( $slug );
        $url  = add_query_arg( array_merge( $base_args, array( 'product_cat' => $slug ) ), home_url( '/' ) );
        $html .= '<li class="wc-block-product-categories-list-item' . ( $selected_cat === $slug ? ' is-active' : '' ) . '">';
        $html .= '<a href="' . esc_url( $url ) . '">' . esc_html( $name ) . '</a>';
        $html .= '</li>';
    }

    $html .= '</ul>';

    return $html;
}

/* ----- Detect site under development (controls SEO behavior) ----- */
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

