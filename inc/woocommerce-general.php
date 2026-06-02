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
 *
 * IMPORTANT: this value drives the MAIN WP/Woo query — i.e. the result count
 * ("Showing 1-N of TOTAL"), the WP query pagination block, and the per-page
 * pages cap (max_num_pages). It MUST match the per-page value used by the
 * custom shop renderer in `noyona_render_shop_archive_product_cards()` and by
 * the block templates' `perPage` attribute, otherwise the result count, the
 * visible card count, and the paginator will all disagree (and infinite
 * scroll's "next page" link can disappear when the main query collapses to
 * one page while the renderer thinks there should still be more).
 *
 * Locked at 12 to mirror the 3-col × 4-row grid used by the renderer and the
 * infinite-scroll batch size. No URL override — production behaviour is a
 * uniform 12 cards per infinite-scroll batch (or fewer for the final batch
 * if the remaining product count is less than 12).
 */
add_filter( 'loop_shop_per_page', 'noyona_loop_shop_per_page', 20 );
function noyona_loop_shop_per_page( $per_page ) {
    if ( is_page( array( 'face', 'lips', 'eyes', 'hair', 'body' ) ) ) {
        return 4;
    }

    return 12;
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
    $selected_tag = isset( $_GET['product_tag'] ) ? sanitize_key( wp_unslash( $_GET['product_tag'] ) ) : '';
    if ( '' !== $selected_tag && ! term_exists( $selected_tag, 'product_tag' ) ) {
        $selected_tag = '';
    }

    $has_price_range = null !== $min_price || null !== $max_price;

    $selected_orderby = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'menu_order';
    $orderby          = 'menu_order';
    $order            = 'ASC';

    // Keep dedicated category pages aligned with the shop archive sorter.
    switch ( $selected_orderby ) {
        case 'popularity':
            $orderby = 'popularity';
            $order   = 'DESC';
            break;
        case 'rating':
            $orderby = 'rating';
            $order   = 'DESC';
            break;
        case 'date':
            $orderby = 'date';
            $order   = 'DESC';
            break;
        case 'price':
            $orderby = 'price';
            $order   = 'ASC';
            break;
        case 'price-desc':
            $orderby = 'price';
            $order   = 'DESC';
            break;
        case 'title':
            $orderby = $selected_orderby;
            break;
        case 'menu_order':
        default:
            $selected_orderby = 'menu_order';
            $orderby          = 'menu_order';
            break;
    }

    $query_args = array(
        'status'   => 'publish',
        'limit'    => $has_price_range ? -1 : 4,
        'orderby'  => $orderby,
        'order'    => $order,
        'category' => array( $slug_lower ),
        'return'   => 'objects',
    );
    if ( '' !== $selected_tag ) {
        $query_args['tag'] = array( $selected_tag );
    }

    $query_args = noyona_apply_price_range_to_product_query_args( $query_args, $min_price, $max_price );

    $products = wc_get_products( $query_args );
    $products = noyona_filter_products_by_price_range( $products, $min_price, $max_price );

    // Ensure visual order follows effective current price like the shop archive.
    if ( in_array( $selected_orderby, array( 'price', 'price-desc' ), true ) ) {
        usort(
            $products,
            static function ( $a, $b ) use ( $selected_orderby ) {
                $a_price = ( is_object( $a ) && method_exists( $a, 'get_price' ) ) ? (float) $a->get_price() : 0.0;
                $b_price = ( is_object( $b ) && method_exists( $b, 'get_price' ) ) ? (float) $b->get_price() : 0.0;

                if ( $a_price === $b_price ) {
                    $a_name = ( is_object( $a ) && method_exists( $a, 'get_name' ) ) ? (string) $a->get_name() : '';
                    $b_name = ( is_object( $b ) && method_exists( $b, 'get_name' ) ) ? (string) $b->get_name() : '';
                    return strcasecmp( $a_name, $b_name );
                }

                if ( 'price-desc' === $selected_orderby ) {
                    return $b_price <=> $a_price;
                }

                return $a_price <=> $b_price;
            }
        );
    }

    if ( $has_price_range ) {
        $products = array_slice( $products, 0, 4 );
    }

    if ( $has_price_range ) {
        $all_for_more_args = array(
            'status'   => 'publish',
            'limit'    => -1,
            'orderby'  => 'title',
            'order'    => 'ASC',
            'category' => array( $slug_lower ),
            'return'   => 'objects',
        );
        if ( '' !== $selected_tag ) {
            $all_for_more_args['tag'] = array( $selected_tag );
        }
        $all_for_more = wc_get_products( $all_for_more_args );
        $all_for_more = noyona_filter_products_by_price_range( $all_for_more, $min_price, $max_price );
        $has_more     = count( $all_for_more ) > 4;
    } else {
        $has_more = count(
            wc_get_products(
                array_filter(
                    array(
                        'status'   => 'publish',
                        'limit'    => 5,
                        'category' => array( $slug_lower ),
                        'tag'      => '' !== $selected_tag ? array( $selected_tag ) : null,
                        'return'   => 'ids',
                    )
                )
            )
        ) > 4;
    }

    if ( empty( $products ) ) {
        if ( null !== $min_price || null !== $max_price ) {
            return noyona_render_shop_empty_product_collection(
                array(
                    'columns' => 'repeat(2,minmax(0,1fr))',
                )
            );
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

/* ----- Render toolbar blocks on dedicated category pages ----- */
/**
 * Dedicated pages (face/lips/eyes/hair/body) are regular pages, not archive
 * queries, so Woo blocks like product-results-count and catalog-sorting can
 * render empty. Provide explicit renderers so the toolbar matches shop pages.
 */
add_filter( 'render_block', 'noyona_render_landing_page_toolbar_blocks', 11, 2 );
function noyona_render_landing_page_toolbar_blocks( $block_content, $block ) {
    if ( ! isset( $block['blockName'] ) ) {
        return $block_content;
    }

    $block_name = $block['blockName'];
    if ( 'woocommerce/product-results-count' !== $block_name && 'woocommerce/catalog-sorting' !== $block_name ) {
        return $block_content;
    }

    $post = get_queried_object();
    if ( ! $post instanceof WP_Post || 'page' !== $post->post_type ) {
        return $block_content;
    }

    $slug      = strtolower( $post->post_name );
    $page_slugs = noyona_get_shop_category_page_slugs();
    if ( ! in_array( $slug, $page_slugs, true ) ) {
        return $block_content;
    }

    if ( ! function_exists( 'wc_get_products' ) ) {
        return $block_content;
    }

    $attrs     = isset( $block['attrs'] ) ? $block['attrs'] : array();
    $class     = isset( $attrs['className'] ) ? trim( (string) $attrs['className'] ) : '';
    $min_price = isset( $_GET['min_price'] ) ? floatval( wp_unslash( $_GET['min_price'] ) ) : null;
    $max_price = isset( $_GET['max_price'] ) ? floatval( wp_unslash( $_GET['max_price'] ) ) : null;

    if ( 'woocommerce/product-results-count' === $block_name ) {
        $count_args = array(
            'status'   => 'publish',
            'limit'    => -1,
            'category' => array( $slug ),
            'return'   => 'ids',
        );
        $count_args = noyona_apply_price_range_to_product_query_args( $count_args, $min_price, $max_price );
        $total      = count( wc_get_products( $count_args ) );
        $per_page   = 4;

        if ( $total < 1 ) {
            $count_text = __( 'Showing 0 results', 'noyona-childtheme' );
        } elseif ( $total <= $per_page ) {
            $count_text = sprintf(
                /* translators: %d: total matching products. */
                _n( 'Showing all %d result', 'Showing all %d results', $total, 'noyona-childtheme' ),
                $total
            );
        } else {
            $count_text = sprintf(
                /* translators: 1: visible products on first page, 2: total matching products. */
                __( 'Showing 1-%1$d of %2$d results', 'noyona-childtheme' ),
                $per_page,
                $total
            );
        }

        $classes = trim( 'woocommerce-result-count ' . $class );
        return '<p class="' . esc_attr( $classes ) . '">' . esc_html( $count_text ) . '</p>';
    }

    $ordering_options = apply_filters(
        'woocommerce_catalog_orderby',
        array(
            'menu_order' => __( 'Default sorting', 'woocommerce' ),
            'popularity' => __( 'Sort by popularity', 'woocommerce' ),
            'rating'     => __( 'Sort by average rating', 'woocommerce' ),
            'date'       => __( 'Sort by latest', 'woocommerce' ),
            'price'      => __( 'Sort by price: low to high', 'woocommerce' ),
            'price-desc' => __( 'Sort by price: high to low', 'woocommerce' ),
        )
    );

    $selected_orderby = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : '';
    if ( ! isset( $ordering_options[ $selected_orderby ] ) ) {
        $selected_orderby = 'menu_order';
    }

    $classes = trim( 'woocommerce-ordering ' . $class );
    $html    = '<form class="' . esc_attr( $classes ) . '" method="get">';
    $html   .= '<select name="orderby" class="orderby" aria-label="' . esc_attr__( 'Shop order', 'woocommerce' ) . '" onchange="this.form.submit()">';
    foreach ( $ordering_options as $value => $label ) {
        $html .= '<option value="' . esc_attr( $value ) . '"' . selected( $selected_orderby, $value, false ) . '>' . esc_html( $label ) . '</option>';
    }
    $html .= '</select>';

    foreach ( $_GET as $key => $value ) {
        if ( in_array( $key, array( 'orderby', 'submit', 'paged', 'product-page' ), true ) ) {
            continue;
        }

        if ( is_array( $value ) ) {
            foreach ( $value as $single_value ) {
                $html .= '<input type="hidden" name="' . esc_attr( $key ) . '[]" value="' . esc_attr( wp_unslash( (string) $single_value ) ) . '" />';
            }
            continue;
        }

        $html .= '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( wp_unslash( (string) $value ) ) . '" />';
    }

    $html .= '</form>';
    return $html;
}

/* ----- Shop archive category order (Face → Lips → Eyes → Hair → Body) ----- */
/**
 * WooCommerce's product-categories block sorts terms by name/id. Replace it on
 * shop and product_cat archives so pills match the canonical Noyona sequence.
 */
add_filter( 'render_block', 'noyona_render_ordered_shop_product_categories_block', 9, 2 );
function noyona_render_ordered_shop_product_categories_block( $block_content, $block ) {
    if ( ! isset( $block['blockName'] ) || 'woocommerce/product-categories' !== $block['blockName'] ) {
        return $block_content;
    }

    if ( ! function_exists( 'noyona_render_ordered_shop_category_list' ) ) {
        return $block_content;
    }

    if ( ! is_shop() && ! is_product_category() ) {
        return $block_content;
    }

    $selected_slug = '';
    if ( is_product_category() ) {
        $term = get_queried_object();
        if ( $term instanceof WP_Term && in_array( $term->slug, noyona_get_shop_category_page_slugs(), true ) ) {
            $selected_slug = $term->slug;
        }
    }

    $list = noyona_render_ordered_shop_category_list(
        $selected_slug,
        static function ( $slug, $term ) {
            if ( $term instanceof WP_Term ) {
                $link = get_term_link( $term );
                if ( ! is_wp_error( $link ) ) {
                    return $link;
                }
            }

            return home_url( user_trailingslashit( 'shop/' . $slug ) );
        }
    );

    return '<div class="wp-block-woocommerce-product-categories wc-block-product-categories">' . $list . '</div>';
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
    // Use the MAIN shop query's per-page (which respects the loop_shop_per_page
    // filter and the ?ppp= override). Fall back to the block's perPage attr,
    // and finally to a sane default. This is the single source of truth: the
    // result count, the renderer, and the infinite-scroll pagination all use
    // it, so they can never disagree.
    $main_per_page = (int) get_query_var( 'posts_per_page' );
    if ( $main_per_page < 1 ) {
        $main_per_page = isset( $query['perPage'] ) ? (int) $query['perPage'] : 12;
    }
    $per_page = $main_per_page > 0 ? $main_per_page : 12;
    $order    = isset( $query['order'] ) ? $query['order'] : 'ASC';
    $orderby  = 'menu_order';

    // Honor the Woo catalog sorter (e.g. ?orderby=price, popularity, rating, date, price-desc).
    $selected_orderby = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'menu_order';
    switch ( $selected_orderby ) {
        case 'popularity':
            $orderby = 'popularity';
            $order   = 'DESC';
            break;
        case 'rating':
            $orderby = 'rating';
            $order   = 'DESC';
            break;
        case 'date':
            $orderby = 'date';
            $order   = 'DESC';
            break;
        case 'price':
            $orderby = 'price';
            $order   = 'ASC';
            break;
        case 'price-desc':
            $orderby = 'price';
            $order   = 'DESC';
            break;
        case 'title':
            $orderby = $selected_orderby;
            break;
        case 'menu_order':
        default:
            $selected_orderby = 'menu_order';
            $orderby          = 'menu_order';
            break;
    }

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
    $selected_tag = isset( $_GET['product_tag'] ) ? sanitize_key( wp_unslash( $_GET['product_tag'] ) ) : '';
    if ( '' !== $selected_tag && ! term_exists( $selected_tag, 'product_tag' ) ) {
        $selected_tag = '';
    }

    $has_price_range = null !== $min_price || null !== $max_price;
    if ( '' !== $selected_tag ) {
        $args['tag'] = array( $selected_tag );
    }
    $args            = noyona_apply_price_range_to_product_query_args( $args, $min_price, $max_price );
    if ( $has_price_range ) {
        $args['limit'] = -1;
        unset( $args['offset'] );
    }

    // We need total + max_pages in addition to the page's products so that we
    // can build our own infinite-scroll "next page" URL without depending on
    // WP's pagination block (which sometimes collapses to nothing on short
    // result sets / when block context is missing).
    $total_results = 0;
    $max_pages     = 1;

    if ( $has_price_range ) {
        $products      = wc_get_products( $args );
        $products      = noyona_filter_products_by_price_range( $products, $min_price, $max_price );
        $total_results = count( $products );
        $max_pages     = $per_page > 0 ? max( 1, (int) ceil( $total_results / $per_page ) ) : 1;
    } else {
        $paginate_args             = $args;
        $paginate_args['paginate'] = true;
        $paginate_args['page']     = $paged;
        unset( $paginate_args['offset'] );

        $result        = wc_get_products( $paginate_args );
        $products      = is_object( $result ) && isset( $result->products ) ? $result->products : array();
        $total_results = is_object( $result ) && isset( $result->total ) ? (int) $result->total : count( $products );
        $max_pages     = is_object( $result ) && isset( $result->max_num_pages ) ? (int) $result->max_num_pages : 1;
    }

    // Ensure visual card order strictly matches the selected catalog sort.
    // For price sorts, use each product's current effective price (sale/current),
    // so cards with old/regular price crossed out still sort by the live price.
    if ( in_array( $selected_orderby, array( 'price', 'price-desc' ), true ) ) {
        usort(
            $products,
            static function ( $a, $b ) use ( $selected_orderby ) {
                $a_price = ( is_object( $a ) && method_exists( $a, 'get_price' ) ) ? (float) $a->get_price() : 0.0;
                $b_price = ( is_object( $b ) && method_exists( $b, 'get_price' ) ) ? (float) $b->get_price() : 0.0;

                if ( $a_price === $b_price ) {
                    $a_name = ( is_object( $a ) && method_exists( $a, 'get_name' ) ) ? (string) $a->get_name() : '';
                    $b_name = ( is_object( $b ) && method_exists( $b, 'get_name' ) ) ? (string) $b->get_name() : '';
                    return strcasecmp( $a_name, $b_name );
                }

                if ( 'price-desc' === $selected_orderby ) {
                    return $b_price <=> $a_price;
                }

                return $a_price <=> $b_price;
            }
        );
    }

    if ( $has_price_range ) {
        $products = array_slice( $products, ( $paged - 1 ) * $per_page, $per_page );
    }

    // Build the "next page" URL ourselves so infinite-scroll has a guaranteed,
    // unambiguous source-of-truth. We always prefer ?paged=N over WP's
    // ?query-{ID}-page=N since the main shop archive uses the main query.
    $next_url = '';
    if ( $paged < $max_pages ) {
        $base_url = home_url( add_query_arg( null, null ) );
        // Strip both pagination query vars so we never end up with duplicates.
        $base_url = remove_query_arg( array( 'paged', $page_key ), $base_url );
        $next_url = add_query_arg( 'paged', $paged + 1, $base_url );
    }

    if ( empty( $products ) ) {
        return noyona_render_shop_empty_product_collection(
            array(
                'infinite' => true,
            )
        );
    }

    ob_start();
    echo '<div class="wp-block-woocommerce-product-collection noyona-shop-products noyona-shop-products-infinite"'
        . ' data-per-page="' . esc_attr( (string) $per_page ) . '"'
        . ' data-current-page="' . esc_attr( (string) $paged ) . '"'
        . ' data-max-pages="' . esc_attr( (string) $max_pages ) . '"'
        . ' data-total="' . esc_attr( (string) $total_results ) . '">'
        . '<div class="wc-block-product-template" style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:18px;">';
    foreach ( $products as $product ) {
        echo noyona_render_product_card( $product );
    }
    echo '</div>';

    // Authoritative pagination meta for the JS infinite-scroll loader. This is
    // independent of the WP query-pagination block (which can render
    // inconsistently when inner block context is missing for the
    // product-collection block).
    echo '<div class="noyona-shop-infinite-meta"'
        . ' data-next-url="' . esc_url( $next_url ) . '"'
        . ' data-current-page="' . esc_attr( (string) $paged ) . '"'
        . ' data-max-pages="' . esc_attr( (string) $max_pages ) . '"'
        . ' data-total="' . esc_attr( (string) $total_results ) . '"'
        . ' hidden></div>';

    // Re-render pagination from the original block output too, when present.
    // It is hidden by CSS when JS is active but still works as a graceful
    // no-JS fallback.
    if ( preg_match( '/<nav[^>]*wp-block-query-pagination.*?<\/nav>/s', $block_content, $matches ) ) {
        echo $matches[0];
    }

    echo '</div>';
    return ob_get_clean();
}

/**
 * Replace Woo's default no-results pattern on shop/search with the theme empty state.
 */
add_filter( 'render_block', 'noyona_render_shop_product_collection_no_results', 10, 2 );
function noyona_render_shop_product_collection_no_results( $block_content, $block ) {
    if ( ! isset( $block['blockName'] ) || 'woocommerce/product-collection-no-results' !== $block['blockName'] ) {
        return $block_content;
    }

    if ( ! function_exists( 'noyona_get_shop_no_products_markup' ) ) {
        return $block_content;
    }

    $is_shop_context = ( function_exists( 'is_shop' ) && is_shop() )
        || ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() )
        || ( function_exists( 'noyona_is_product_search_request' ) && noyona_is_product_search_request() );

    if ( ! $is_shop_context ) {
        return $block_content;
    }

    return noyona_get_shop_no_products_markup();
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

/* ----- Branded WooCommerce account email subject ----- */
add_filter( 'woocommerce_email_subject_customer_new_account', 'noyona_customer_new_account_email_subject', 10, 3 );
function noyona_customer_new_account_email_subject( $subject, $user, $email ) {
    $first_name = '';

    if ( $user instanceof WP_User ) {
        $first_name = trim( (string) $user->first_name );
        if ( '' === $first_name ) {
            $first_name = trim( strtok( (string) $user->display_name, ' ' ) );
        }
    } elseif ( $email && ! empty( $email->object ) && $email->object instanceof WP_User ) {
        $first_name = trim( (string) $email->object->first_name );
        if ( '' === $first_name ) {
            $first_name = trim( strtok( (string) $email->object->display_name, ' ' ) );
        }
    }

    if ( '' === $first_name ) {
        $first_name = __( 'there', 'noyona-childtheme' );
    }

    return sprintf(
        /* translators: %s: Customer first name. */
        __( 'Welcome to Noyona, %s!', 'noyona-childtheme' ),
        $first_name
    );
}

/* ----- Branded WooCommerce password reset email subject ----- */
add_filter( 'woocommerce_email_subject_customer_reset_password', 'noyona_customer_reset_password_email_subject', 10, 3 );
function noyona_customer_reset_password_email_subject( $subject, $user, $email ) {
    return __( 'Reset your Noyona password', 'noyona-childtheme' );
}

/* ----- Branded WooCommerce processing order email subject ----- */
add_filter( 'woocommerce_email_subject_customer_processing_order', 'noyona_customer_processing_order_email_subject', 10, 3 );
function noyona_customer_processing_order_email_subject( $subject, $order, $email ) {
    if ( $order instanceof WC_Order ) {
        return sprintf(
            /* translators: %s: Order number. */
            __( "We're preparing your Noyona order %s", 'noyona-childtheme' ),
            $order->get_order_number()
        );
    }

    return __( "We're preparing your Noyona order", 'noyona-childtheme' );
}

/* ----- Branded WooCommerce delivered order email subject ----- */
add_filter( 'woocommerce_email_subject_customer_completed_order', 'noyona_customer_completed_order_email_subject', 10, 3 );
function noyona_customer_completed_order_email_subject( $subject, $order, $email ) {
    if ( $order instanceof WC_Order ) {
        return sprintf(
            /* translators: %s: Order number. */
            __( 'Your Noyona order %s has been delivered', 'noyona-childtheme' ),
            $order->get_order_number()
        );
    }

    return __( 'Your Noyona order has been delivered', 'noyona-childtheme' );
}

/* ----- Branded WooCommerce failed payment email subject ----- */
add_filter( 'woocommerce_email_subject_customer_failed_order', 'noyona_customer_failed_order_email_subject', 10, 3 );
function noyona_customer_failed_order_email_subject( $subject, $order, $email ) {
    if ( $order instanceof WC_Order ) {
        return sprintf(
            /* translators: %s: Order number. */
            __( 'Payment failed for your Noyona order %s', 'noyona-childtheme' ),
            $order->get_order_number()
        );
    }

    return __( 'Payment failed for your Noyona order', 'noyona-childtheme' );
}

/* ----- Branded WooCommerce cancelled order email subject ----- */
add_filter( 'woocommerce_email_subject_customer_cancelled_order', 'noyona_customer_cancelled_order_email_subject', 10, 3 );
function noyona_customer_cancelled_order_email_subject( $subject, $order, $email ) {
    if ( $order instanceof WC_Order ) {
        return sprintf(
            /* translators: %s: Order number. */
            __( 'Your Noyona order %s has been cancelled', 'noyona-childtheme' ),
            $order->get_order_number()
        );
    }

    return __( 'Your Noyona order has been cancelled', 'noyona-childtheme' );
}
