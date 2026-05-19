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

/* ----- Detect: is this request the product search results page? ----- */
function noyona_is_product_search_request() {
    if ( is_admin() ) {
        return false;
    }
    $has_search = ! empty( $_GET['s'] );
    if ( function_exists( 'is_search' ) && is_search() ) {
        $has_search = true;
    }
    if ( ! $has_search ) {
        return false;
    }
    $post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : '';
    if ( '' === $post_type ) {
        $qv_post_type = get_query_var( 'post_type' );
        $qv_post_type = is_array( $qv_post_type ) ? reset( $qv_post_type ) : $qv_post_type;
        $post_type    = (string) $qv_post_type;
    }
    return 'product' === $post_type;
}

/* ----- Stop WP canonical redirect on the product search page ----- */
/**
 * WordPress' redirect_canonical can rewrite `?s=…&paged=N` into a pretty
 * `/page/N/?s=…` URL, which 404s because the front route has no rewrite for
 * that path. Disable canonical for product-search requests so the URL stays
 * exactly as we built it.
 */
add_filter( 'redirect_canonical', 'noyona_disable_canonical_redirect_on_product_search', 10, 2 );
function noyona_disable_canonical_redirect_on_product_search( $redirect_url, $requested_url = '' ) {
    if ( noyona_is_product_search_request() ) {
        return false;
    }
    return $redirect_url;
}

/* ----- Stop WooCommerce's single-search-result redirect on the search page ----- */
/**
 * WooCommerce's wc_template_redirect() (template_redirect priority 10) sends
 * the visitor to the matching product's PDP whenever a product search returns
 * exactly one result:
 *
 *   if ( is_search() && is_post_type_archive('product')
 *        && apply_filters('woocommerce_redirect_single_search_result', true)
 *        && 1 === absint( $wp_query->found_posts ) ) {
 *       wp_safe_redirect( get_permalink( $product->get_id() ), 302 );
 *       exit;
 *   }
 *
 * That's a different code path from redirect_canonical, so blocking canonical
 * alone isn't enough. We force the filter to false on our search route so a
 * narrow price/stock/category filter that leaves one product still renders as
 * a single card on the search results page instead of jumping to the PDP.
 * The filter is gated by noyona_is_product_search_request(), so the shop
 * archive, category pages, PDP, and header live search are unaffected.
 */
add_filter( 'woocommerce_redirect_single_search_result', 'noyona_disable_wc_single_search_redirect_on_product_search', 10, 1 );
function noyona_disable_wc_single_search_redirect_on_product_search( $enabled ) {
    if ( noyona_is_product_search_request() ) {
        return false;
    }
    return $enabled;
}

/* ----- Keep product_cat pills on the search route (don't trigger /shop/{slug}/ redirect) ----- */
/**
 * When the URL carries both `s=` and `product_cat=` with `post_type=product`, WordPress
 * resolves the main query as a `product_cat` taxonomy, which makes `is_tax('product_cat')`
 * true and triggers `noyona_redirect_old_product_category_base()` (in inc/rewrites.php),
 * sending the visitor to `/shop/{slug}/`. We don't want category pills on the search page
 * to leave the search route, so we strip `product_cat` from the parsed main-query vars
 * for that specific case. `$_GET['product_cat']` is untouched, so the shortcode still
 * reads the slug and applies it via its own WP_Query tax_query.
 */
add_filter( 'request', 'noyona_keep_product_search_route_when_filtering_by_category' );
function noyona_keep_product_search_route_when_filtering_by_category( $query_vars ) {
    if ( ! is_array( $query_vars ) ) {
        return $query_vars;
    }
    if ( empty( $query_vars['s'] ) ) {
        return $query_vars;
    }
    $post_type = isset( $query_vars['post_type'] ) ? (string) $query_vars['post_type'] : '';
    if ( 'product' !== $post_type ) {
        return $query_vars;
    }
    if ( isset( $query_vars['product_cat'] ) ) {
        unset( $query_vars['product_cat'] );
    }
    return $query_vars;
}

/* ----- Product search results page renderer ----- */
add_shortcode( 'noyona_product_search_page', 'noyona_render_product_search_page_shortcode' );
function noyona_render_product_search_page_shortcode() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return '';
    }

    $query_text     = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : get_search_query( false );
    $query_text     = trim( (string) $query_text );
    $selected_cat   = isset( $_GET['product_cat'] ) ? sanitize_key( wp_unslash( $_GET['product_cat'] ) ) : '';
    $selected_order = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'relevance';
    $min_price      = isset( $_GET['min_price'] ) && '' !== (string) $_GET['min_price'] ? floatval( wp_unslash( $_GET['min_price'] ) ) : null;
    $max_price      = isset( $_GET['max_price'] ) && '' !== (string) $_GET['max_price'] ? floatval( wp_unslash( $_GET['max_price'] ) ) : null;

    $stock_statuses = array();
    $stock_param    = isset( $_GET['stock_status'] ) ? wp_unslash( $_GET['stock_status'] ) : array();
    $stock_param    = is_array( $stock_param ) ? $stock_param : array( $stock_param );
    foreach ( $stock_param as $stock_status ) {
        $stock_status = sanitize_key( (string) $stock_status );
        if ( in_array( $stock_status, array( 'instock', 'outofstock' ), true ) ) {
            $stock_statuses[] = $stock_status;
        }
    }
    $stock_statuses = array_values( array_unique( $stock_statuses ) );

    $allowed_cat_slugs = function_exists( 'noyona_get_shop_category_page_slugs' )
        ? noyona_get_shop_category_page_slugs()
        : array( 'face', 'lips', 'eyes', 'hair', 'body' );
    if ( '' !== $selected_cat && ! in_array( $selected_cat, $allowed_cat_slugs, true ) ) {
        $selected_cat = '';
    }

    $per_page = 6;
    // Use a dedicated query var so WP's canonical/page rewrite doesn't turn
    // ?s=…&paged=N into /page/N/?s=… (which 404s on the front route).
    if ( isset( $_GET['search_page'] ) ) {
        $current_page = max( 1, absint( wp_unslash( $_GET['search_page'] ) ) );
    } elseif ( isset( $_GET['paged'] ) ) {
        $current_page = max( 1, absint( wp_unslash( $_GET['paged'] ) ) );
    } else {
        $current_page = max( 1, absint( get_query_var( 'paged', 1 ) ) );
    }

    $base_query_args = array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => $per_page,
        'paged'          => $current_page,
    );

    if ( '' !== $query_text ) {
        $base_query_args['s'] = $query_text;
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
        $base_query_args['meta_query'] = $meta_query;
    }

    switch ( $selected_order ) {
        case 'price':
            $base_query_args['meta_key'] = '_price';
            $base_query_args['orderby']  = 'meta_value_num';
            $base_query_args['order']    = 'ASC';
            break;
        case 'price-desc':
            $base_query_args['meta_key'] = '_price';
            $base_query_args['orderby']  = 'meta_value_num';
            $base_query_args['order']    = 'DESC';
            break;
        case 'date':
            $base_query_args['orderby'] = 'date';
            $base_query_args['order']   = 'DESC';
            break;
        case 'title':
            $base_query_args['orderby'] = 'title';
            $base_query_args['order']   = 'ASC';
            break;
        case 'relevance':
        default:
            $selected_order = 'relevance';
            break;
    }

    // Category availability is derived from the current search query only — never the whole site.
    // Filters and pagination are excluded so the pill can stay clickable even when current filters hide its products.
    $available_cat_slugs = array();
    if ( '' !== $query_text ) {
        $availability_query = new WP_Query(
            array(
                'post_type'      => 'product',
                'post_status'    => 'publish',
                'posts_per_page' => 500,
                'fields'         => 'ids',
                'no_found_rows'  => true,
                's'              => $query_text,
            )
        );
        if ( ! empty( $availability_query->posts ) ) {
            $terms = wp_get_object_terms( $availability_query->posts, 'product_cat', array( 'fields' => 'slugs' ) );
            if ( is_array( $terms ) && ! is_wp_error( $terms ) ) {
                $available_cat_slugs = array_values( array_unique( array_map( 'sanitize_key', $terms ) ) );
            }
        }
    }

    // Final displayed products query (adds category filter if a category pill is selected).
    $query_args = $base_query_args;
    if ( '' !== $selected_cat ) {
        $query_args['tax_query'] = array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => array( $selected_cat ),
            ),
        );
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
        <h1 class="noyona-search-hero__title"><?php esc_html_e( 'Search Results', 'noyona-childtheme' ); ?></h1>
        <p class="noyona-search-hero__count">
            <?php
            printf(
                esc_html__( '%1$d result(s) found for "%2$s"', 'noyona-childtheme' ),
                $result_count,
                esc_html( $query_text )
            );
            ?>
        </p>
        <form class="noyona-search-again-form" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" role="search">
            <span class="noyona-search-again-form__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            </span>
            <label class="screen-reader-text" for="noyona-search-again-input"><?php esc_html_e( 'Search products again', 'noyona-childtheme' ); ?></label>
            <input id="noyona-search-again-input" class="noyona-search-again-form__input" type="search" name="s" value="<?php echo esc_attr( $query_text ); ?>" placeholder="<?php esc_attr_e( 'Search products', 'noyona-childtheme' ); ?>" />
            <input type="hidden" name="post_type" value="product" />
            <button type="submit" class="noyona-search-again-form__submit"><?php esc_html_e( 'Search Again', 'noyona-childtheme' ); ?></button>
        </form>
    </section>

    <section class="noyona-search-products-head alignwide">
        <h2 class="noyona-search-products-head__title"><?php esc_html_e( 'All Products', 'noyona-childtheme' ); ?></h2>
        <nav class="noyona-search-pills" aria-label="<?php esc_attr_e( 'Product categories', 'noyona-childtheme' ); ?>">
            <?php echo noyona_render_product_search_category_pills( $query_text, $selected_cat, $base_params, $available_cat_slugs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </nav>
    </section>

    <div class="noyona-search-layout alignwide">
        <aside class="noyona-search-filters" aria-label="<?php esc_attr_e( 'Product filters', 'noyona-childtheme' ); ?>">
            <form class="noyona-search-filters__form" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
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

                <!-- <div class="noyona-search-filters__actions">
                    <button type="submit" class="noyona-search-filters__apply"><?php esc_html_e( 'Apply Filters', 'noyona-childtheme' ); ?></button>
                    <a class="noyona-search-filters__reset" href="<?php echo esc_url( add_query_arg( array( 's' => $query_text, 'post_type' => 'product' ), home_url( '/' ) ) ); ?>"><?php esc_html_e( 'Reset', 'noyona-childtheme' ); ?></a>
                </div> -->
            </form>
        </aside>

        <div class="noyona-search-results">
            <div class="noyona-search-toolbar">
                <p class="noyona-search-count noyona-shop-count">
                    <?php
                    printf(
                        esc_html( _n( '%d product found', '%d products found', $result_count, 'noyona-childtheme' ) ),
                        $result_count
                    );
                    ?>
                </p>
                <div class="noyona-search-toolbar__right noyona-shop-toolbar-right">
                    <div class="noyona-shop-view-wrapper">
                        <span class="noyona-shop-view-label"><?php esc_html_e( 'View As', 'noyona-childtheme' ); ?></span>
                        <div class="noyona-shop-view-toggle" aria-label="<?php esc_attr_e( 'View', 'noyona-childtheme' ); ?>">
                            <button type="button" class="is-active" data-shop-view="grid" aria-pressed="true">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3 3h8v8H3zM13 3h8v8h-8zM3 13h8v8H3zM13 13h8v8h-8z"/></svg>
                            </button>
                            <button type="button" data-shop-view="list" aria-pressed="false">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3 4h18v4H3zM3 10h18v4H3zM3 16h18v4H3z"/></svg>
                            </button>
                        </div>
                    </div>
                    <form class="noyona-search-sort noyona-shop-sort" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
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

            <div class="noyona-search-grid noyona-shop-products">
                <div class="wc-block-product-template">
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
                $total_pages    = max( 1, (int) $products_query->max_num_pages );
                $pagination_base_args = $base_params;
                unset( $pagination_base_args['paged'], $pagination_base_args['search_page'] );
                $build_page_url = static function ( $page ) use ( $pagination_base_args ) {
                    $args = $pagination_base_args;
                    if ( $page > 1 ) {
                        $args['search_page'] = (int) $page;
                    }
                    return add_query_arg( $args, home_url( '/' ) );
                };

                $page_links = paginate_links(
                    array(
                        'base'      => add_query_arg(
                            array_merge( $pagination_base_args, array( 'search_page' => '%#%' ) ),
                            home_url( '/' )
                        ),
                        'format'    => '',
                        'current'   => $current_page,
                        'total'     => $total_pages,
                        'type'      => 'array',
                        'prev_next' => false,
                        'mid_size'  => 1,
                        'end_size'  => 1,
                    )
                );

                if ( $total_pages > 1 ) :
                    ?>
                    <nav class="wp-block-query-pagination noyona-search-pagination" aria-label="<?php esc_attr_e( 'Product search pagination', 'noyona-childtheme' ); ?>">
                        <?php if ( $current_page > 1 ) : ?>
                            <a class="wp-block-query-pagination-previous page-numbers" href="<?php echo esc_url( $build_page_url( $current_page - 1 ) ); ?>" aria-label="<?php esc_attr_e( 'Previous page', 'noyona-childtheme' ); ?>"><?php esc_html_e( 'Previous', 'noyona-childtheme' ); ?></a>
                        <?php endif; ?>
                        <div class="wp-block-query-pagination-numbers">
                            <?php if ( is_array( $page_links ) ) : ?>
                                <?php echo wp_kses_post( implode( '', $page_links ) ); ?>
                            <?php endif; ?>
                        </div>
                        <?php if ( $current_page < $total_pages ) : ?>
                            <a class="wp-block-query-pagination-next page-numbers" href="<?php echo esc_url( $build_page_url( $current_page + 1 ) ); ?>" aria-label="<?php esc_attr_e( 'Next page', 'noyona-childtheme' ); ?>"><?php esc_html_e( 'Next', 'noyona-childtheme' ); ?></a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php

    return noyona_clean_product_search_markup( trim( (string) ob_get_clean() ) );
}

function noyona_render_product_search_category_pills( $query_text, $selected_cat, $current_params = array(), $available_cat_slugs = array() ) {
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

    $available_lookup = array_flip( array_map( 'sanitize_key', (array) $available_cat_slugs ) );

    $all_classes = 'noyona-search-pill noyona-search-pill--all';
    if ( '' === $selected_cat ) {
        $all_classes .= ' is-active';
    }
    $html  = '<a class="' . esc_attr( $all_classes ) . '" href="' . esc_url( add_query_arg( $base_args, home_url( '/' ) ) ) . '">';
    $html .= esc_html__( 'All Products', 'noyona-childtheme' );
    $html .= '</a>';

    foreach ( $slugs as $slug ) {
        $slug         = sanitize_key( (string) $slug );
        $term         = get_term_by( 'slug', $slug, 'product_cat' );
        $name         = $term instanceof WP_Term ? $term->name : ucfirst( $slug );
        $is_available = isset( $available_lookup[ $slug ] );
        $is_active    = ( $selected_cat === $slug );

        $classes = 'noyona-search-pill';
        if ( $is_active ) {
            $classes .= ' is-active';
        }
        if ( ! $is_available && ! $is_active ) {
            $classes .= ' is-disabled';
        }

        if ( $is_available || $is_active ) {
            $url   = add_query_arg( array_merge( $base_args, array( 'product_cat' => $slug ) ), home_url( '/' ) );
            $html .= '<a class="' . esc_attr( $classes ) . '" href="' . esc_url( $url ) . '">' . esc_html( $name ) . '</a>';
        } else {
            $html .= '<span class="' . esc_attr( $classes ) . '" aria-disabled="true" tabindex="-1">' . esc_html( $name ) . '</span>';
        }
    }

    return $html;
}

/* ----- Product search page markup cleanup (wpautop artifacts) ----- */
/**
 * Strip empty <p></p>, <p><br></p>, and <p>-wrappers that wpautop injects
 * around our search-page block-level markup. Scoped to the search page —
 * only wrappers whose class begins with `noyona-search-` or
 * `noyona-product-search-` are touched, so product card excerpts (which use
 * <p> text content) are not affected.
 */
function noyona_clean_product_search_markup( $html ) {
    if ( ! is_string( $html ) || '' === trim( $html ) ) {
        return (string) $html;
    }

    // Remove empty paragraphs and paragraphs that only contain <br>/&nbsp;/whitespace.
    $html = preg_replace( '/<p\b[^>]*>(?:\s|&nbsp;|&#160;|<br\s*\/?>)*<\/p>/i', '', $html );

    // Strip <p> wrappers that wpautop puts around our block-level search elements.
    $html = preg_replace(
        '/<p>\s*(<(?:section|div|aside|form|nav|button|h[1-6])\b[^>]*class=(["\'])[^"\']*\b(?:noyona-(?:product-)?search-[\w-]+)\b[^"\']*\2[^>]*>)/i',
        '$1',
        $html
    );
    $html = preg_replace(
        '/(<\/(?:section|div|aside|form|nav|button|h[1-6])>)\s*<\/p>/i',
        '$1',
        $html
    );

    // Inside the structural wrappers (NOT the product grid), drop stray <br> and
    // empty <p></p>. The grid wrapper is excluded so product description
    // <br>/<p> text inside cards stays untouched.
    $wrappers = array(
        '/(<section\b[^>]*class=(["\'])[^"\']*\bnoyona-search-hero\b[^"\']*\2[^>]*>)(.*?)(<\/section>)/is',
        '/(<form\b[^>]*class=(["\'])[^"\']*\bnoyona-search-again-form\b[^"\']*\2[^>]*>)(.*?)(<\/form>)/is',
        '/(<section\b[^>]*class=(["\'])[^"\']*\bnoyona-search-products-head\b[^"\']*\2[^>]*>)(.*?)(<\/section>)/is',
        '/(<nav\b[^>]*class=(["\'])[^"\']*\bnoyona-search-pills\b[^"\']*\2[^>]*>)(.*?)(<\/nav>)/is',
        '/(<aside\b[^>]*class=(["\'])[^"\']*\bnoyona-search-filters\b[^"\']*\2[^>]*>)(.*?)(<\/aside>)/is',
        '/(<form\b[^>]*class=(["\'])[^"\']*\bnoyona-search-filters__form\b[^"\']*\2[^>]*>)(.*?)(<\/form>)/is',
        '/(<div\b[^>]*class=(["\'])[^"\']*\bnoyona-search-toolbar\b[^"\']*\2[^>]*>)(.*?)(<\/div>)/is',
        '/(<form\b[^>]*class=(["\'])[^"\']*\bnoyona-search-sort\b[^"\']*\2[^>]*>)(.*?)(<\/form>)/is',
    );

    foreach ( $wrappers as $pattern ) {
        $html = preg_replace_callback(
            $pattern,
            static function ( $matches ) {
                $inner = isset( $matches[3] ) ? (string) $matches[3] : '';
                $inner = preg_replace( '/<br\s*\/?>/i', '', $inner );
                $inner = preg_replace( '/<p\b[^>]*>(?:\s|&nbsp;|&#160;)*<\/p>/i', '', $inner );
                return (string) $matches[1] . (string) $inner . (string) $matches[4];
            },
            (string) $html
        );
    }

    // Final pass to clean anything left at the top level.
    $html = preg_replace( '/<p\b[^>]*>(?:\s|&nbsp;|&#160;|<br\s*\/?>)*<\/p>/i', '', (string) $html );

    return (string) $html;
}

add_filter( 'render_block_core/shortcode', 'noyona_clean_product_search_shortcode_block_artifacts', 20, 3 );
function noyona_clean_product_search_shortcode_block_artifacts( $block_content, $block = array(), $instance = null ) {
    if ( false === strpos( (string) $block_content, 'noyona-product-search-page' )
        && false === strpos( (string) $block_content, 'noyona-search-hero' ) ) {
        return $block_content;
    }

    return noyona_clean_product_search_markup( (string) $block_content );
}

add_filter( 'the_content', 'noyona_clean_product_search_page_artifacts', 35 );
function noyona_clean_product_search_page_artifacts( $content ) {
    if ( is_admin() ) {
        return $content;
    }

    if ( false === strpos( (string) $content, 'noyona-product-search-page' )
        && false === strpos( (string) $content, 'noyona-search-hero' ) ) {
        return $content;
    }

    return noyona_clean_product_search_markup( (string) $content );
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

