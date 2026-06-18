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
 * Canonical category order for shop/search navigation (Face, Lips, Eyes, Hair, Body).
 * WooCommerce product_cat slugs; also used for Store API filtering on category landing pages.
 */
function noyona_get_shop_category_page_slugs() {
    return array( 'face', 'lips', 'eyes', 'hair', 'body' );
}

/**
 * Active product_cat slug for the current shop/search filter context.
 */
function noyona_get_shop_filter_brand_slugs() {
    return array( 'noyona', 'lovial' );
}

/**
 * Selected product_brand slugs for shop/search filters, or empty when none.
 *
 * @return string[]
 */
function noyona_get_selected_product_brand_slugs() {
    if ( ! taxonomy_exists( 'product_brand' ) ) {
        return array();
    }

    $brand_param = isset( $_GET['product_brand'] ) ? wp_unslash( $_GET['product_brand'] ) : array();
    if ( is_array( $brand_param ) ) {
        $brand_values = $brand_param;
    } else {
        $brand_values = '' === trim( (string) $brand_param )
            ? array()
            : preg_split( '/\s*,\s*/', (string) $brand_param );
    }

    $allowed  = noyona_get_shop_filter_brand_slugs();
    $selected = array();

    foreach ( $brand_values as $slug ) {
        $slug = sanitize_key( (string) $slug );
        if ( '' === $slug || ! in_array( $slug, $allowed, true ) ) {
            continue;
        }
        if ( ! term_exists( $slug, 'product_brand' ) ) {
            continue;
        }
        $selected[] = $slug;
    }

    return array_values( array_unique( $selected ) );
}

/**
 * Append product_brand slug filters to WP_Query or wc_get_products args.
 *
 * @param array        $args        Query args.
 * @param string|array $brand_slugs One slug or list of slugs; empty applies no filter.
 */
function noyona_apply_product_brand_to_query_args( $args, $brand_slugs ) {
    $brand_slugs = array_values(
        array_filter(
            array_map(
                static function ( $slug ) {
                    return sanitize_key( (string) $slug );
                },
                (array) $brand_slugs
            )
        )
    );

    if ( empty( $brand_slugs ) || ! taxonomy_exists( 'product_brand' ) ) {
        return $args;
    }

    if ( ! isset( $args['tax_query'] ) || ! is_array( $args['tax_query'] ) ) {
        $args['tax_query'] = array();
    }

    $args['tax_query'][] = array(
        'taxonomy' => 'product_brand',
        'field'    => 'slug',
        'terms'    => $brand_slugs,
        'operator' => 'IN',
    );

    if ( count( $args['tax_query'] ) > 1 ) {
        $args['tax_query']['relation'] = 'AND';
    }

    return $args;
}

function noyona_get_shop_route_category_slug_from_path() {
    $path = trim( (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ), '/' );
    if ( ! preg_match( '#^shop/([^/]+)(?:/page/\d+)?/?$#', $path, $matches ) ) {
        return '';
    }

    $candidate = sanitize_key( (string) $matches[1] );
    if ( in_array( $candidate, noyona_get_shop_category_page_slugs(), true ) ) {
        return $candidate;
    }

    return '';
}

function noyona_is_shop_route_request() {
    if ( ( function_exists( 'is_shop' ) && is_shop() ) || is_tax( 'product_cat' ) ) {
        return true;
    }

    $path = trim( (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ), '/' );
    if ( 'shop' === $path ) {
        return true;
    }

    return '' !== noyona_get_shop_route_category_slug_from_path();
}

function noyona_get_current_shop_filter_category_slug() {
    if ( is_tax( 'product_cat' ) ) {
        $term = get_queried_object();
        if ( $term instanceof WP_Term ) {
            return (string) $term->slug;
        }
    }

    if ( isset( $_GET['product_cat'] ) ) {
        $slug = sanitize_key( wp_unslash( $_GET['product_cat'] ) );
        if ( '' !== $slug && term_exists( $slug, 'product_cat' ) ) {
            return $slug;
        }
    }

    if ( is_page() ) {
        $post = get_queried_object();
        if ( $post instanceof WP_Post ) {
            $slug = strtolower( (string) $post->post_name );
            if ( in_array( $slug, noyona_get_shop_category_page_slugs(), true ) ) {
                return $slug;
            }
        }
    }

    return noyona_get_shop_route_category_slug_from_path();
}

/**
 * Whether any published products match a tag in the current shop filter context.
 *
 * @param string $tag_slug       Product tag slug.
 * @param string $category_slug  Optional product_cat slug.
 * @param string $search_query   Optional product search string.
 * @param float|null $min_price  Optional minimum price.
 * @param float|null $max_price  Optional maximum price.
 */
function noyona_shop_tag_has_products_in_context( $tag_slug, $category_slug = '', $search_query = '', $min_price = null, $max_price = null ) {
    $tag_slug = sanitize_key( (string) $tag_slug );
    if ( '' === $tag_slug || ! term_exists( $tag_slug, 'product_tag' ) ) {
        return false;
    }

    static $availability_cache = array();

    $cache_key = md5(
        wp_json_encode(
            array(
                'tag'      => $tag_slug,
                'category' => sanitize_key( (string) $category_slug ),
                'search'   => trim( (string) $search_query ),
                'min'      => null === $min_price ? null : (float) $min_price,
                'max'      => null === $max_price ? null : (float) $max_price,
            )
        )
    );

    if ( isset( $availability_cache[ $cache_key ] ) ) {
        return $availability_cache[ $cache_key ];
    }

    $tax_query = array(
        array(
            'taxonomy' => 'product_tag',
            'field'    => 'slug',
            'terms'    => array( $tag_slug ),
        ),
    );

    $category_slug = sanitize_key( (string) $category_slug );
    if ( '' !== $category_slug && term_exists( $category_slug, 'product_cat' ) ) {
        $tax_query[] = array(
            'taxonomy' => 'product_cat',
            'field'    => 'slug',
            'terms'    => array( $category_slug ),
        );
    }

    if ( count( $tax_query ) > 1 ) {
        $tax_query['relation'] = 'AND';
    }

    $query_args = array(
        'post_type'              => 'product',
        'post_status'            => 'publish',
        'posts_per_page'         => 1,
        'fields'                 => 'ids',
        'ignore_sticky_posts'    => true,
        'no_found_rows'          => false,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
        'tax_query'              => $tax_query,
    );

    $search_query = trim( (string) $search_query );
    if ( '' !== $search_query ) {
        $query_args['s'] = $search_query;
    }

    $query_args = noyona_apply_price_range_to_product_query_args( $query_args, $min_price, $max_price );

    $query = new WP_Query( $query_args );

    $availability_cache[ $cache_key ] = (int) $query->found_posts > 0;

    return $availability_cache[ $cache_key ];
}

/**
 * Whether any published products match a brand in the current shop filter context.
 */
function noyona_shop_brand_has_products_in_context( $brand_slug, $category_slug = '', $search_query = '', $min_price = null, $max_price = null ) {
    $brand_slug = sanitize_key( (string) $brand_slug );
    if ( '' === $brand_slug || ! term_exists( $brand_slug, 'product_brand' ) ) {
        return false;
    }

    static $availability_cache = array();

    $cache_key = md5(
        wp_json_encode(
            array(
                'brand'    => $brand_slug,
                'category' => sanitize_key( (string) $category_slug ),
                'search'   => trim( (string) $search_query ),
                'min'      => null === $min_price ? null : (float) $min_price,
                'max'      => null === $max_price ? null : (float) $max_price,
            )
        )
    );

    if ( isset( $availability_cache[ $cache_key ] ) ) {
        return $availability_cache[ $cache_key ];
    }

    $tax_query = array(
        array(
            'taxonomy' => 'product_brand',
            'field'    => 'slug',
            'terms'    => array( $brand_slug ),
        ),
    );

    $category_slug = sanitize_key( (string) $category_slug );
    if ( '' !== $category_slug && term_exists( $category_slug, 'product_cat' ) ) {
        $tax_query[] = array(
            'taxonomy' => 'product_cat',
            'field'    => 'slug',
            'terms'    => array( $category_slug ),
        );
    }

    if ( count( $tax_query ) > 1 ) {
        $tax_query['relation'] = 'AND';
    }

    $query_args = array(
        'post_type'              => 'product',
        'post_status'            => 'publish',
        'posts_per_page'         => 1,
        'fields'                 => 'ids',
        'ignore_sticky_posts'    => true,
        'no_found_rows'          => false,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
        'tax_query'              => $tax_query,
    );

    $search_query = trim( (string) $search_query );
    if ( '' !== $search_query ) {
        $query_args['s'] = $search_query;
    }

    $query_args = noyona_apply_price_range_to_product_query_args( $query_args, $min_price, $max_price );

    $query = new WP_Query( $query_args );

    $availability_cache[ $cache_key ] = (int) $query->found_posts > 0;

    return $availability_cache[ $cache_key ];
}

/**
 * Fixed brand options for the filter panel.
 *
 * @return array<int, array{slug:string, name:string, hasProducts:bool}>
 */
function noyona_get_shop_filter_product_brands() {
    if ( ! taxonomy_exists( 'product_brand' ) ) {
        return array();
    }

    $category_slug = noyona_get_current_shop_filter_category_slug();
    $search_query  = '';

    $request_post_type = get_query_var( 'post_type' );
    $request_post_type = is_array( $request_post_type ) ? reset( $request_post_type ) : $request_post_type;
    $is_product_search = is_search()
        && (
            'product' === (string) $request_post_type
            || ( isset( $_GET['post_type'] ) && 'product' === sanitize_key( wp_unslash( $_GET['post_type'] ) ) )
        );

    if ( $is_product_search ) {
        $search_query = get_search_query();
    }

    $min_price = isset( $_GET['min_price'] ) ? floatval( wp_unslash( $_GET['min_price'] ) ) : null;
    $max_price = isset( $_GET['max_price'] ) ? floatval( wp_unslash( $_GET['max_price'] ) ) : null;

    $brands = array();

    foreach ( noyona_get_shop_filter_brand_slugs() as $brand_slug ) {
        $term = get_term_by( 'slug', $brand_slug, 'product_brand' );
        if ( ! $term instanceof WP_Term ) {
            continue;
        }

        $brands[] = array(
            'slug'        => $brand_slug,
            'name'        => $term->name,
            'hasProducts' => noyona_shop_brand_has_products_in_context(
                $brand_slug,
                $category_slug,
                $search_query,
                $min_price,
                $max_price
            ),
        );
    }

    return $brands;
}

/**
 * Product tags for the filter panel: available tags first, then unavailable (alphabetical within each group).
 *
 * @return array<int, array{slug:string, name:string, hasProducts:bool}>
 */
function noyona_get_shop_filter_product_tags() {
    if ( ! taxonomy_exists( 'product_tag' ) ) {
        return array();
    }

    $category_slug = noyona_get_current_shop_filter_category_slug();
    $search_query  = '';

    $request_post_type = get_query_var( 'post_type' );
    $request_post_type = is_array( $request_post_type ) ? reset( $request_post_type ) : $request_post_type;
    $is_product_search = is_search()
        && (
            'product' === (string) $request_post_type
            || ( isset( $_GET['post_type'] ) && 'product' === sanitize_key( wp_unslash( $_GET['post_type'] ) ) )
        );

    if ( $is_product_search ) {
        $search_query = get_search_query();
    }

    $min_price = isset( $_GET['min_price'] ) ? floatval( wp_unslash( $_GET['min_price'] ) ) : null;
    $max_price = isset( $_GET['max_price'] ) ? floatval( wp_unslash( $_GET['max_price'] ) ) : null;

    $product_tag_terms = get_terms(
        array(
            'taxonomy'   => 'product_tag',
            'hide_empty' => true,
            'orderby'    => 'name',
            'order'      => 'ASC',
        )
    );

    if ( is_wp_error( $product_tag_terms ) || empty( $product_tag_terms ) ) {
        return array();
    }

    $available = array();
    $disabled  = array();

    foreach ( $product_tag_terms as $product_tag_term ) {
        if ( ! $product_tag_term instanceof WP_Term ) {
            continue;
        }

        $has_products = noyona_shop_tag_has_products_in_context(
            $product_tag_term->slug,
            $category_slug,
            $search_query,
            $min_price,
            $max_price
        );

        $item = array(
            'slug'        => $product_tag_term->slug,
            'name'        => $product_tag_term->name,
            'hasProducts' => $has_products,
        );

        if ( $has_products ) {
            $available[] = $item;
        } else {
            $disabled[] = $item;
        }
    }

    return array_merge( $available, $disabled );
}

/**
 * Empty-state message for shop/search product grids (not a product card).
 */
function noyona_get_shop_no_products_markup() {
    return '<div class="noyona-shop-no-products" role="status">'
        . '<p>' . esc_html__( 'No Products found', 'noyona-childtheme' ) . '</p>'
        . '</div>';
}

/**
 * Product collection wrapper when the current filters return zero products.
 *
 * @param array $options {
 *     @type bool   $infinite Use infinite-scroll collection class.
 *     @type string $columns  CSS grid-template-columns value.
 * }
 */
function noyona_render_shop_empty_product_collection( $options = array() ) {
    $options = wp_parse_args(
        $options,
        array(
            'infinite' => false,
            'columns'  => 'repeat(4,minmax(0,1fr))',
        )
    );

    $classes = array( 'wp-block-woocommerce-product-collection', 'noyona-shop-products' );
    if ( ! empty( $options['infinite'] ) ) {
        $classes[] = 'noyona-shop-products-infinite';
    }

    $grid_style = sprintf(
        'display:grid;grid-template-columns:%s;gap:18px;',
        (string) $options['columns']
    );

    return '<div class="' . esc_attr( implode( ' ', $classes ) ) . '">'
        . '<div class="wc-block-product-template" style="' . esc_attr( $grid_style ) . '">'
        . noyona_get_shop_no_products_markup()
        . '</div></div>';
}

/**
 * Ordered category list markup shared by shop archive and product search.
 *
 * @param string   $selected_slug Active product_cat slug, or '' when none is selected.
 * @param callable $url_builder   Receives sanitized slug and WP_Term|null; returns category URL.
 */
function noyona_render_ordered_shop_category_list( $selected_slug, $url_builder ) {
    $slugs         = noyona_get_shop_category_page_slugs();
    $selected_slug = sanitize_key( (string) $selected_slug );

    $html = '<ul class="wc-block-product-categories-list wc-block-product-categories-list--depth-0">';

    foreach ( $slugs as $slug ) {
        $slug = sanitize_key( (string) $slug );
        $term = get_term_by( 'slug', $slug, 'product_cat' );
        $name = $term instanceof WP_Term ? $term->name : ucfirst( $slug );

        $url = call_user_func( $url_builder, $slug, $term instanceof WP_Term ? $term : null );
        if ( ! is_string( $url ) || '' === $url ) {
            continue;
        }

        $is_active    = ( $selected_slug === $slug );
        $li_classes   = 'wc-block-product-categories-list-item';
        $aria_current = '';

        if ( $is_active ) {
            $li_classes  .= ' current-cat current-product_cat';
            $aria_current = ' aria-current="page"';
        }

        $html .= '<li class="' . esc_attr( $li_classes ) . '">';
        $html .= '<a' . $aria_current . ' href="' . esc_url( $url ) . '">' . esc_html( $name ) . '</a>';
        $html .= '</li>';
    }

    $html .= '</ul>';

    return $html;
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

/* ----- Product card helpers (shop, search, landing grids) ----- */
/**
 * Primary product category label for card chrome (skips "uncategorized").
 *
 * @param WC_Product $product Product object.
 * @return string
 */
function noyona_get_product_card_category_name( $product ) {
    if ( ! $product instanceof WC_Product ) {
        return '';
    }

    $terms = get_the_terms( $product->get_id(), 'product_cat' );
    if ( empty( $terms ) || is_wp_error( $terms ) ) {
        return '';
    }

    foreach ( $terms as $term ) {
        if ( $term instanceof WP_Term && 'uncategorized' !== $term->slug ) {
            return (string) $term->name;
        }
    }

    $first = reset( $terms );
    return $first instanceof WP_Term ? (string) $first->name : '';
}

/**
 * Sale discount percentage for simple/variable min prices (0 when not on sale).
 *
 * @param WC_Product $product Product object.
 * @return int
 */
function noyona_get_product_card_discount_percent( $product ) {
    if ( ! $product instanceof WC_Product || ! $product->is_on_sale() ) {
        return 0;
    }

    if ( $product->is_type( 'variable' ) ) {
        $regular = (float) $product->get_variation_regular_price( 'min', true );
        $sale    = (float) $product->get_variation_sale_price( 'min', true );
    } else {
        $regular = (float) $product->get_regular_price();
        $sale    = (float) $product->get_sale_price();
    }

    if ( $sale <= 0 ) {
        $sale = (float) $product->get_price();
    }
    if ( $regular <= 0 ) {
        $regular = $sale;
    }

    if ( $regular <= 0 || $sale <= 0 || $sale >= $regular ) {
        return 0;
    }

    return (int) round( ( ( $regular - $sale ) / $regular ) * 100 );
}

/**
 * Rating row for product cards.
 *
 * @param WC_Product $product Product object.
 * @return string
 */
function noyona_get_product_card_rating_html( $product ) {
    if ( ! $product instanceof WC_Product ) {
        return '';
    }

    $rating       = (float) $product->get_average_rating();
    $review_count = (int) $product->get_rating_count();

    if ( $review_count < 1 ) {
        return '';
    }

    $rating_html = wc_get_rating_html( $rating, $review_count );
    if ( ! $rating_html ) {
        return '';
    }

    ob_start();
    ?>
    <div class="noyona-product-card-meta__rating">
        <?php echo $rating_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        <span class="noyona-product-card-meta__rating-count">
            <?php
            echo esc_html(
                sprintf(
                    /* translators: %d: number of reviews */
                    _n( '(%d review)', '(%d reviews)', $review_count, 'noyona-childtheme' ),
                    $review_count
                )
            );
            ?>
        </span>
    </div>
    <?php
    return trim( (string) ob_get_clean() );
}

/**
 * Units sold label for product cards.
 *
 * @param WC_Product $product Product object.
 * @return string
 */
function noyona_get_product_card_sold_html( $product ) {
    if ( ! $product instanceof WC_Product ) {
        return '';
    }

    $sold = max( 0, (int) $product->get_total_sales() );
    if ( $sold < 1 ) {
        return '';
    }

    return sprintf(
        '<span class="noyona-product-card-meta__sold">%s</span>',
        esc_html(
            sprintf(
                /* translators: %d: number of units sold */
                _n( '%d sold', '%d sold', $sold, 'noyona-childtheme' ),
                $sold
            )
        )
    );
}

/**
 * Meta row: category, rating, and sold count.
 *
 * @param WC_Product $product Product object.
 * @return string
 */
function noyona_get_product_card_meta_html( $product ) {
    if ( ! $product instanceof WC_Product ) {
        return '';
    }

    $parts = array();

    $category = noyona_get_product_card_category_name( $product );
    if ( '' !== $category ) {
        $parts[] = '<span class="noyona-product-card-meta__category">' . esc_html( $category ) . '</span>';
    }

    $rating_html = noyona_get_product_card_rating_html( $product );
    if ( '' !== $rating_html ) {
        $parts[] = $rating_html;
    }

    $sold_html = noyona_get_product_card_sold_html( $product );
    if ( '' !== $sold_html ) {
        $parts[] = $sold_html;
    }

    if ( empty( $parts ) ) {
        return '';
    }

    return '<div class="noyona-product-card-meta">' . implode( '', $parts ) . '</div>';
}

function noyona_get_product_card_category_html( $product ) {
    $category = noyona_get_product_card_category_name( $product );

    if ( '' === $category ) {
        return '';
    }

    return '<span class="noyona-product-card-meta__category">' . esc_html( $category ) . '</span>';
}

/**
 * Price block with optional discount badge and smaller struck-through regular price.
 *
 * @param WC_Product $product Product object.
 * @return string
 */
function noyona_get_product_card_price_html( $product ) {
    if ( ! $product instanceof WC_Product ) {
        return '';
    }

    $discount_pct = noyona_get_product_card_discount_percent( $product );

    if ( $product->is_type( 'variable' ) ) {
        $min_sale    = (float) $product->get_variation_sale_price( 'min', true );
        $max_sale    = (float) $product->get_variation_sale_price( 'max', true );
        $min_regular = (float) $product->get_variation_regular_price( 'min', true );
        $max_regular = (float) $product->get_variation_regular_price( 'max', true );

        if ( $min_sale <= 0 ) {
            $min_sale = (float) $product->get_price();
        }
        if ( $max_sale <= 0 ) {
            $max_sale = $min_sale;
        }

        $has_range = ( $min_sale !== $max_sale ) || ( $min_regular !== $max_regular && $min_regular > 0 );

        if ( $has_range ) {
            return '<div class="noyona-product-card-price wc-block-components-product-price">' . $product->get_price_html() . '</div>';
        }

        $regular = $min_regular > 0 ? $min_regular : $min_sale;
        $sale    = $min_sale;
    } else {
        $regular = (float) $product->get_regular_price();
        $sale    = (float) $product->get_sale_price();
        if ( $sale <= 0 ) {
            $sale = (float) $product->get_price();
        }
        if ( $regular <= 0 ) {
            $regular = $sale;
        }
    }

    ob_start();
    ?>
    <div class="noyona-product-card-price wc-block-components-product-price">
        <?php if ( $discount_pct > 0 ) : ?>
            <ins class="noyona-product-card-price__current"><?php echo wp_kses_post( wc_price( $sale ) ); ?></ins>
            <del class="noyona-product-card-price__old"><?php echo wp_kses_post( wc_price( $regular ) ); ?></del>
            <span class="noyona-product-card-price__badge">-<?php echo esc_html( (string) $discount_pct ); ?>%</span>
        <?php else : ?>
            <span class="noyona-product-card-price__current"><?php echo wp_kses_post( wc_price( $sale ) ); ?></span>
        <?php endif; ?>
    </div>
    <?php
    return trim( (string) ob_get_clean() );
}

/**
 * Cart control markup for unified product cards.
 *
 * @param WC_Product $product Product instance.
 * @return string Cart button HTML.
 */
function noyona_get_product_card_cart_control_html( $product ) {
    if ( ! $product instanceof WC_Product ) {
        return '';
    }

    $product_id   = $product->get_id();
    $product_type = $product->get_type();
    $product_name = $product->get_name();
    $permalink    = $product->get_permalink();

    $cart_action = 'navigate';
    $product_url = $permalink;
    $aria_label  = '';
    $is_disabled = false;

    if ( $product->is_type( 'simple' ) ) {
        if ( $product->is_purchasable() && $product->is_in_stock() ) {
            $cart_action = 'ajax';
            $aria_label  = sprintf(
                /* translators: %s: product name */
                __( 'Add %s to cart', 'noyona-childtheme' ),
                $product_name
            );
        } else {
            $cart_action = 'disabled';
            $is_disabled = true;
            $aria_label  = sprintf(
                /* translators: %s: product name */
                __( '%s is out of stock', 'noyona-childtheme' ),
                $product_name
            );
        }
    } elseif ( $product->is_type( 'variable' ) || $product->is_type( 'grouped' ) ) {
        $aria_label = sprintf(
            /* translators: %s: product name */
            __( 'Select options for %s', 'noyona-childtheme' ),
            $product_name
        );
    } elseif ( $product->is_type( 'external' ) ) {
        $external_url = $product->get_product_url();
        $product_url  = $external_url ? $external_url : $permalink;
        $aria_label   = sprintf(
            /* translators: %s: product name */
            __( 'View %s', 'noyona-childtheme' ),
            $product_name
        );
    } else {
        $aria_label = sprintf(
            /* translators: %s: product name */
            __( 'View %s', 'noyona-childtheme' ),
            $product_name
        );
    }

    $classes = array( 'noyona-product-card-cart' );
    if ( $is_disabled ) {
        $classes[] = 'is-disabled';
    }

    ob_start();
    ?>
    <button
        type="button"
        class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
        data-noyona-card-cart="1"
        data-product-id="<?php echo esc_attr( (string) $product_id ); ?>"
        data-product-type="<?php echo esc_attr( $product_type ); ?>"
        data-cart-action="<?php echo esc_attr( $cart_action ); ?>"
        <?php if ( 'navigate' === $cart_action ) : ?>
            data-product-url="<?php echo esc_url( $product_url ); ?>"
        <?php endif; ?>
        aria-label="<?php echo esc_attr( $aria_label ); ?>"
        <?php if ( $is_disabled ) : ?>
            disabled
            aria-disabled="true"
        <?php endif; ?>
    >
        <i class="fa-solid fa-cart-shopping" aria-hidden="true"></i>
    </button>
    <?php
    return trim( (string) ob_get_clean() );
}

/* ----- Render a single product card ----- */
/**
 * Render a single product card with the unified layout:
 * image → title → meta → excerpt → footer (price + cart).
 */
function noyona_render_product_card( $product ) {
    if ( ! $product instanceof WC_Product ) {
        return '';
    }

    $image_id      = $product->get_image_id();
    $image         = $image_id
        ? wp_get_attachment_image( $image_id, 'woocommerce_thumbnail', false, array( 'class' => 'wc-block-components-product-image' ) )
        : wc_placeholder_img( 'woocommerce_thumbnail' );
    $title         = $product->get_name();
    $link          = $product->get_permalink();
    $excerpt       = has_excerpt( $product->get_id() ) ? get_the_excerpt( $product->get_id() ) : '';

    $price_html    = noyona_get_product_card_price_html( $product );
    $category_html = noyona_get_product_card_category_html( $product );

    /** Card Rating and Sold Count */
    $rating = (float) $product->get_average_rating();
    $review_count = (int) $product->get_rating_count();

    $rating_text = ( $review_count > 0 && $rating > 0 )
        ? number_format( $rating, 1 ) . ' ★'
        : 'No reviews yet';

    $sold = max( 0, (int) $product->get_total_sales() );
    $sold_text = $sold . ' sold';

    if ( $excerpt ) {
        $excerpt = wp_trim_words( $excerpt, 22 );
    }

    ob_start();
    ?>
    <div class="wc-block-product" data-product-id="<?php echo esc_attr( (string) $product->get_id() ); ?>">
        <a href="<?php echo esc_url( $link ); ?>" class="wc-block-components-product-image">
        <?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

            <?php if ( '' !== $category_html ) : ?>
                <?php echo $category_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php endif; ?>
            </a>
        <h3 class="wc-block-product-title wp-block-post-title"><a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $title ); ?></a></h3>
        <!-- <?php if ( '' !== $meta_html ) : ?>
            <?php echo $meta_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        <?php endif; ?> -->
        <!-- <?php if ( $excerpt ) : ?>
            <div class="wp-block-post-excerpt"><p><?php echo esc_html( $excerpt ); ?></p></div>
        <?php endif; ?> -->
        <div class="noyona-product-card-footer">
        <div class="noyona-product-card-footer__copy">

            <?php echo $price_html; ?>

            <div class="noyona-product-card-footer-meta">
                <span class="noyona-product-card-footer-meta__rating">
                    <?php echo esc_html( $rating_text ); ?>
                </span>

                <span class="noyona-product-card-footer-meta__separator">|</span>

                <span class="noyona-product-card-footer-meta__sold">
                    <?php echo esc_html( $sold_text ); ?>
                </span>

                <?php echo noyona_get_product_card_cart_control_html( $product ); ?>
            </div>

        </div>
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
    if ( isset( $query_vars['product_tag'] ) ) {
        unset( $query_vars['product_tag'] );
    }
    if ( isset( $query_vars['product_brand'] ) ) {
        unset( $query_vars['product_brand'] );
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
    $selected_cat    = isset( $_GET['product_cat'] ) ? sanitize_key( wp_unslash( $_GET['product_cat'] ) ) : '';
    $selected_tag    = isset( $_GET['product_tag'] ) ? sanitize_key( wp_unslash( $_GET['product_tag'] ) ) : '';
    $selected_brand  = noyona_get_selected_product_brand_slugs();
    $selected_order = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'menu_order';
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
    if ( '' !== $selected_tag && ! term_exists( $selected_tag, 'product_tag' ) ) {
        $selected_tag = '';
    }

    $per_page = 12;
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
        case 'popularity':
            $base_query_args['meta_key'] = 'total_sales';
            $base_query_args['orderby']  = 'meta_value_num';
            $base_query_args['order']    = 'DESC';
            break;
        case 'rating':
            $base_query_args['meta_key'] = '_wc_average_rating';
            $base_query_args['orderby']  = 'meta_value_num';
            $base_query_args['order']    = 'DESC';
            break;
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
        case 'menu_order':
        default:
            $selected_order = 'menu_order';
            $base_query_args['orderby'] = 'menu_order title';
            $base_query_args['order']   = 'ASC';
            break;
    }

    // Final displayed products query (adds category/tag filters if selected).
    $query_args = $base_query_args;
    $tax_query  = array();
    if ( '' !== $selected_cat ) {
        $tax_query[] = array(
            'taxonomy' => 'product_cat',
            'field'    => 'slug',
            'terms'    => array( $selected_cat ),
        );
    }
    if ( '' !== $selected_tag ) {
        $tax_query[] = array(
            'taxonomy' => 'product_tag',
            'field'    => 'slug',
            'terms'    => array( $selected_tag ),
        );
    }
    if ( ! empty( $selected_brand ) ) {
        $tax_query[] = array(
            'taxonomy' => 'product_brand',
            'field'    => 'slug',
            'terms'    => $selected_brand,
            'operator' => 'IN',
        );
    }
    if ( ! empty( $tax_query ) ) {
        if ( count( $tax_query ) > 1 ) {
            $tax_query['relation'] = 'AND';
        }
        $query_args['tax_query'] = $tax_query;
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
    if ( '' !== $selected_tag ) {
        $base_params['product_tag'] = $selected_tag;
    }
    if ( ! empty( $selected_brand ) ) {
        $base_params['product_brand'] = implode( ',', $selected_brand );
    }
    if ( 'menu_order' !== $selected_order ) {
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
    <div class="wp-block-group alignwide noyona-shop-top">
        <h1 class="wp-block-query-title noyona-shop-title"><?php esc_html_e( 'Search Results', 'noyona-childtheme' ); ?></h1>

        <div class="wp-block-group noyona-search-top-controls">
            <form class="noyona-search-again-form noyona-shop-search-form" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" role="search">
                <span class="noyona-search-again-form__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </span>
                <label class="screen-reader-text" for="noyona-search-again-input"><?php esc_html_e( 'Search products', 'noyona-childtheme' ); ?></label>
                <input id="noyona-search-again-input" class="noyona-search-again-form__input" type="search" name="s" value="<?php echo esc_attr( $query_text ); ?>" placeholder="<?php esc_attr_e( 'Search products', 'noyona-childtheme' ); ?>" />
                <input type="hidden" name="post_type" value="product" />
                <button type="submit" class="noyona-search-again-form__submit"><?php esc_html_e( 'Search', 'noyona-childtheme' ); ?></button>
            </form>

            <div class="wp-block-group noyona-shop-categories">
                <?php echo noyona_render_product_search_shop_categories( $query_text, $selected_cat, $base_params ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
        </div>
    </div>

    <div class="wp-block-columns alignwide noyona-shop-layout">
        <div class="wp-block-column noyona-shop-sidebar" style="flex-basis:28%">
            <button type="button" class="noyona-shop-filter-overlay" aria-label="<?php esc_attr_e( 'Close filter panel', 'noyona-childtheme' ); ?>"></button>

            <div id="noyona-shop-filter-panel" class="wp-block-group noyona-shop-filters">
                <button type="button" class="noyona-shop-filter-close" aria-label="<?php esc_attr_e( 'Close filters', 'noyona-childtheme' ); ?>">✕</button>

                <div class="noyona-shop-filter-sections" aria-hidden="true"></div>

                <div class="wp-block-group noyona-shop-filter-blocks">
                    <div class="wp-block-woocommerce-product-filter-active"></div>
                    <div class="wp-block-woocommerce-product-filter-stock-status"></div>
                    <div class="wp-block-woocommerce-product-filter-price"></div>
                    <div class="wp-block-woocommerce-product-filter-rating"></div>
                </div>
            </div>
        </div>

        <div class="wp-block-column noyona-shop-products-column" style="flex-basis:72%">
            <div class="wp-block-group noyona-shop-toolbar">
                <p class="woocommerce-result-count noyona-shop-count">
                    <?php echo esc_html( noyona_get_product_search_result_count_text( $result_count, $current_page, $per_page ) ); ?>
                </p>

                <div class="wp-block-group noyona-shop-toolbar-right">
                    <details class="noyona-shop-price-dropdown">
                        <summary class="noyona-shop-price-dropdown__summary"><?php esc_html_e( 'Price range', 'noyona-childtheme' ); ?></summary>
                        <div class="noyona-shop-price-dropdown__panel">
                            <section class="noyona-shop-filter-section noyona-shop-filter-section-price">
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
                                        <input type="number" inputmode="numeric" min="0" step="1" value="<?php echo esc_attr( null !== $min_price ? (string) $min_price : '0' ); ?>" name="min_price" aria-label="<?php esc_attr_e( 'Minimum price', 'noyona-childtheme' ); ?>" />
                                    </label>
                                    <span class="noyona-shop-filter-price-separator" aria-hidden="true">-</span>
                                    <label class="noyona-shop-filter-price-input">
                                        <span aria-hidden="true">₱</span>
                                        <input type="number" inputmode="numeric" min="0" step="1" value="<?php echo esc_attr( null !== $max_price ? (string) $max_price : '0' ); ?>" name="max_price" aria-label="<?php esc_attr_e( 'Maximum price', 'noyona-childtheme' ); ?>" />
                                    </label>
                                </div>
                                <div class="noyona-shop-filter-price-labels" aria-hidden="true"></div>
                            </div>
                            </section>
                        </div>
                    </details>

                    <label class="screen-reader-text" for="noyona-shop-sort-select"><?php esc_html_e( 'Sort products', 'noyona-childtheme' ); ?></label>
                    <select id="noyona-shop-sort-select" class="noyona-shop-sort-select" aria-label="<?php esc_attr_e( 'Sort products', 'noyona-childtheme' ); ?>">
                        <option value="menu_order"><?php esc_html_e( 'Default sorting', 'woocommerce' ); ?></option>
                        <option value="popularity"><?php esc_html_e( 'Popularity', 'noyona-childtheme' ); ?></option>
                        <option value="rating"><?php esc_html_e( 'Average rating', 'noyona-childtheme' ); ?></option>
                        <option value="date"><?php esc_html_e( 'Latest', 'noyona-childtheme' ); ?></option>
                        <option value="price"><?php esc_html_e( 'Price: low to high', 'noyona-childtheme' ); ?></option>
                        <option value="price-desc"><?php esc_html_e( 'Price: high to low', 'noyona-childtheme' ); ?></option>
                    </select>

                    <button type="button" class="noyona-shop-filter-toggle" aria-expanded="false" aria-controls="noyona-shop-filter-panel" aria-label="<?php esc_attr_e( 'Open filters', 'noyona-childtheme' ); ?>">
                        <svg class="icon-filter" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3 5h18l-7 8v5.5l-4 2V13L3 5z"/></svg>
                        <span class="screen-reader-text"><?php esc_html_e( 'Filter', 'noyona-childtheme' ); ?></span>
                    </button>

                    <div class="noyona-shop-view-wrapper" style="display:flex; align-items:center; gap:8px;">
                        <div class="noyona-shop-view-toggle" aria-label="<?php esc_attr_e( 'Toggle view mode', 'noyona-childtheme' ); ?>">
                            <button type="button" class="is-active" data-shop-view-toggle="1" aria-label="<?php esc_attr_e( 'Switch to list view', 'noyona-childtheme' ); ?>" aria-pressed="false">
                                <svg class="icon-grid" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3 3h8v8H3zM13 3h8v8h-8zM3 13h8v8H3zM13 13h8v8h-8z"/></svg>
                                <svg class="icon-list" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" style="display:none;"><path d="M3 4h18v4H3zM3 10h18v4H3zM3 16h18v4H3z"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="wp-block-woocommerce-product-collection noyona-shop-products noyona-shop-products-infinite"
                data-per-page="<?php echo esc_attr( (string) $per_page ); ?>"
                data-current-page="<?php echo esc_attr( (string) $current_page ); ?>"
                data-max-pages="<?php echo esc_attr( (string) max( 1, (int) $products_query->max_num_pages ) ); ?>"
                data-total="<?php echo esc_attr( (string) $result_count ); ?>">
                <div class="wc-block-product-template" style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));column-gap:18px;row-gap:18px;">
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
                        <?php echo noyona_get_shop_no_products_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
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
                $next_url = $current_page < $total_pages ? $build_page_url( $current_page + 1 ) : '';

                echo '<div class="noyona-shop-infinite-meta"'
                    . ' data-next-url="' . esc_url( $next_url ) . '"'
                    . ' data-current-page="' . esc_attr( (string) $current_page ) . '"'
                    . ' data-max-pages="' . esc_attr( (string) $total_pages ) . '"'
                    . ' data-total="' . esc_attr( (string) $result_count ) . '"'
                    . ' hidden></div>';

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

function noyona_get_product_search_result_count_text( $total, $current_page, $per_page ) {
    $total        = max( 0, (int) $total );
    $current_page = max( 1, (int) $current_page );
    $per_page     = max( 1, (int) $per_page );

    if ( $total < 1 ) {
        return __( 'Showing 0 results', 'noyona-childtheme' );
    }

    if ( $total <= $per_page ) {
        return sprintf(
            /* translators: %d: total matching products. */
            _n( 'Showing all %d result', 'Showing all %d results', $total, 'noyona-childtheme' ),
            $total
        );
    }

    $first = ( ( $current_page - 1 ) * $per_page ) + 1;
    $last  = min( $total, $current_page * $per_page );

    return sprintf(
        /* translators: 1: first visible product number, 2: last visible product number, 3: total matching products. */
        __( 'Showing %1$d-%2$d of %3$d results', 'noyona-childtheme' ),
        $first,
        $last,
        $total
    );
}

function noyona_render_product_search_shop_categories( $query_text, $selected_cat, $current_params = array() ) {
    $base_args = wp_parse_args(
        (array) $current_params,
        array(
            's'         => (string) $query_text,
            'post_type' => 'product',
        )
    );
    $base_args['s']         = (string) $query_text;
    $base_args['post_type'] = 'product';
    unset( $base_args['product_cat'], $base_args['paged'], $base_args['search_page'] );

    $html  = '<a class="noyona-shop-category-all' . ( '' === $selected_cat ? ' is-active' : '' ) . '" href="' . esc_url( add_query_arg( $base_args, home_url( '/' ) ) ) . '">';
    $html .= esc_html__( 'All Products', 'noyona-childtheme' );
    $html .= '</a>';

    $html .= noyona_render_ordered_shop_category_list(
        $selected_cat,
        static function ( $slug ) use ( $base_args ) {
            return add_query_arg( array_merge( $base_args, array( 'product_cat' => $slug ) ), home_url( '/' ) );
        }
    );

    return $html;
}

function noyona_render_product_search_category_pills( $query_text, $selected_cat, $current_params = array(), $available_cat_slugs = array() ) {
    $slugs = noyona_get_shop_category_page_slugs();

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
        '/<p>\s*(<(?:section|div|aside|form|nav|button|details|summary|h[1-6])\b[^>]*class=(["\'])[^"\']*\b(?:noyona-(?:(?:product-)?search|shop)-[\w-]+)\b[^"\']*\2[^>]*>)/i',
        '$1',
        $html
    );
    $html = preg_replace(
        '/(<\/(?:section|div|aside|form|nav|button|details|summary|h[1-6])>)\s*<\/p>/i',
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
        '/(<div\b[^>]*class=(["\'])[^"\']*\bnoyona-shop-top\b[^"\']*\2[^>]*>)(.*?)(<\/div>)/is',
        '/(<form\b[^>]*class=(["\'])[^"\']*\bnoyona-shop-search-form\b[^"\']*\2[^>]*>)(.*?)(<\/form>)/is',
        '/(<div\b[^>]*class=(["\'])[^"\']*\bnoyona-shop-toolbar\b[^"\']*\2[^>]*>)(.*?)(<\/div>)/is',
        '/(<div\b[^>]*class=(["\'])[^"\']*\bnoyona-shop-filters\b[^"\']*\2[^>]*>)(.*?)(<\/div>)/is',
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

