<?php
/**
 * Frontend wp_ajax handlers.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ----- Favorite stores: list + toggle handler ----- */
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

/* ----- Product wishlist: user meta helpers + remove handler ----- */
function noyona_get_product_wishlist_meta_key() {
    return 'noyona_product_wishlist';
}

function noyona_get_product_wishlist_item_key( $product_id, $variation_id = 0 ) {
    return absint( $product_id ) . ':' . absint( $variation_id );
}

function noyona_normalize_product_wishlist_item( $item ) {
    if ( is_numeric( $item ) ) {
        $item = array( 'product_id' => absint( $item ) );
    }

    if ( ! is_array( $item ) ) {
        return null;
    }

    $product_id   = isset( $item['product_id'] ) ? absint( $item['product_id'] ) : 0;
    $variation_id = isset( $item['variation_id'] ) ? absint( $item['variation_id'] ) : 0;
    $attributes   = array();

    if ( isset( $item['attributes'] ) && is_array( $item['attributes'] ) ) {
        foreach ( $item['attributes'] as $attribute_key => $attribute_value ) {
            $attribute_key   = sanitize_key( (string) $attribute_key );
            $attribute_value = sanitize_text_field( (string) $attribute_value );
            if ( '' !== $attribute_key && '' !== $attribute_value ) {
                $attributes[ $attribute_key ] = $attribute_value;
            }
        }
    }

    if ( $variation_id > 0 && function_exists( 'wc_get_product' ) ) {
        $variation = wc_get_product( $variation_id );
        if ( $variation instanceof WC_Product_Variation ) {
            $product_id = absint( $variation->get_parent_id() );
            if ( empty( $attributes ) ) {
                $attributes = array_map( 'sanitize_text_field', (array) $variation->get_variation_attributes() );
            }
        } else {
            $variation_id = 0;
        }
    }

    if ( $product_id < 1 || ! function_exists( 'wc_get_product' ) ) {
        return null;
    }

    $product = wc_get_product( $product_id );
    if ( ! $product instanceof WC_Product ) {
        return null;
    }

    $variation_label = isset( $item['variation_label'] ) ? sanitize_text_field( (string) $item['variation_label'] ) : '';
    $added_at        = isset( $item['added_at'] ) ? absint( $item['added_at'] ) : 0;
    if ( $added_at < 1 ) {
        $added_at = time();
    }

    return array(
        'product_id'      => $product_id,
        'variation_id'    => $variation_id,
        'attributes'      => $attributes,
        'variation_label' => $variation_label,
        'added_at'        => $added_at,
    );
}

function noyona_get_product_wishlist_items( $user_id ) {
    $user_id = absint( $user_id );
    if ( $user_id < 1 ) {
        return array();
    }

    $stored_items = get_user_meta( $user_id, noyona_get_product_wishlist_meta_key(), true );
    if ( ! is_array( $stored_items ) ) {
        return array();
    }

    $items = array();
    $seen  = array();
    foreach ( $stored_items as $stored_item ) {
        $item = noyona_normalize_product_wishlist_item( $stored_item );
        if ( ! is_array( $item ) ) {
            continue;
        }

        $item_key = noyona_get_product_wishlist_item_key( $item['product_id'], $item['variation_id'] );
        if ( isset( $seen[ $item_key ] ) ) {
            continue;
        }

        $seen[ $item_key ] = true;
        $items[]           = $item;
    }

    if ( count( $items ) !== count( $stored_items ) ) {
        update_user_meta( $user_id, noyona_get_product_wishlist_meta_key(), $items );
    }

    return $items;
}

function noyona_product_wishlist_contains_item( $user_id, $product_id, $variation_id = 0 ) {
    $needle_key = noyona_get_product_wishlist_item_key( $product_id, $variation_id );
    foreach ( noyona_get_product_wishlist_items( $user_id ) as $item ) {
        if ( $needle_key === noyona_get_product_wishlist_item_key( $item['product_id'], $item['variation_id'] ) ) {
            return true;
        }
    }
    return false;
}

function noyona_update_product_wishlist_items( $user_id, $items ) {
    $user_id = absint( $user_id );
    if ( $user_id < 1 ) {
        return false;
    }

    $normalized = array();
    foreach ( (array) $items as $item ) {
        $item = noyona_normalize_product_wishlist_item( $item );
        if ( is_array( $item ) ) {
            $normalized[] = $item;
        }
    }

    update_user_meta( $user_id, noyona_get_product_wishlist_meta_key(), $normalized );
    return true;
}

function noyona_remove_product_wishlist_item( $user_id, $product_id, $variation_id = 0 ) {
    $user_id      = absint( $user_id );
    $product_id   = absint( $product_id );
    $variation_id = absint( $variation_id );
    if ( $user_id < 1 || $product_id < 1 ) {
        return false;
    }

    $items   = noyona_get_product_wishlist_items( $user_id );
    $removed = false;
    $items   = array_values(
        array_filter(
            $items,
            static function ( $item ) use ( $product_id, $variation_id, &$removed ) {
                $matches = absint( $item['product_id'] ) === $product_id && absint( $item['variation_id'] ) === $variation_id;
                if ( $matches ) {
                    $removed = true;
                    return false;
                }
                return true;
            }
        )
    );

    if ( $removed ) {
        noyona_update_product_wishlist_items( $user_id, $items );
    }

    return $removed;
}

function noyona_toggle_product_wishlist_item( $user_id, $item ) {
    $item = noyona_normalize_product_wishlist_item( $item );
    if ( ! is_array( $item ) ) {
        return false;
    }

    $user_id = absint( $user_id );
    $items   = noyona_get_product_wishlist_items( $user_id );
    $exists  = false;
    foreach ( $items as $index => $stored_item ) {
        if (
            absint( $stored_item['product_id'] ) === absint( $item['product_id'] )
            && absint( $stored_item['variation_id'] ) === absint( $item['variation_id'] )
        ) {
            unset( $items[ $index ] );
            $exists = true;
            break;
        }
    }

    if ( ! $exists ) {
        array_unshift( $items, $item );
    }

    noyona_update_product_wishlist_items( $user_id, array_values( $items ) );
    return ! $exists;
}

add_action( 'wp_ajax_noyona_toggle_product_wishlist', 'noyona_toggle_product_wishlist_ajax_handler' );
add_action( 'wp_ajax_nopriv_noyona_toggle_product_wishlist', 'noyona_toggle_product_wishlist_ajax_handler' );
function noyona_toggle_product_wishlist_ajax_handler() {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error(
            array(
                'message'   => 'not_logged_in',
                'login_url' => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' ),
            ),
            401
        );
    }

    $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
    if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'noyona_product_wishlist' ) ) {
        wp_send_json_error( array( 'message' => 'invalid_nonce' ), 403 );
    }

    $product_id   = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
    $variation_id = isset( $_POST['variation_id'] ) ? absint( wp_unslash( $_POST['variation_id'] ) ) : 0;
    if ( $product_id < 1 || ! function_exists( 'wc_get_product' ) ) {
        wp_send_json_error( array( 'message' => 'invalid_product' ), 400 );
    }

    $product = wc_get_product( $product_id );
    if ( ! $product instanceof WC_Product ) {
        wp_send_json_error( array( 'message' => 'invalid_product' ), 400 );
    }

    if ( $variation_id > 0 ) {
        $variation = wc_get_product( $variation_id );
        if ( ! $variation instanceof WC_Product_Variation || absint( $variation->get_parent_id() ) !== $product_id ) {
            wp_send_json_error( array( 'message' => 'invalid_variation' ), 400 );
        }
    }

    $attributes = array();
    if ( isset( $_POST['attributes'] ) && is_array( $_POST['attributes'] ) ) {
        $attributes = wp_unslash( $_POST['attributes'] );
    }

    $variation_label = isset( $_POST['variation_label'] ) ? sanitize_text_field( wp_unslash( $_POST['variation_label'] ) ) : '';
    $saved           = noyona_toggle_product_wishlist_item(
        get_current_user_id(),
        array(
            'product_id'      => $product_id,
            'variation_id'    => $variation_id,
            'attributes'      => $attributes,
            'variation_label' => $variation_label,
        )
    );
    $items           = noyona_get_product_wishlist_items( get_current_user_id() );

    wp_send_json_success(
        array(
            'saved'     => (bool) $saved,
            'item_key'  => noyona_get_product_wishlist_item_key( $product_id, $variation_id ),
            'count'     => count( $items ),
            'message'   => $saved ? 'saved' : 'removed',
            'productId' => $product_id,
            'variationId' => $variation_id,
        )
    );
}

add_action( 'admin_post_noyona_remove_wishlist_item', 'noyona_remove_wishlist_item_handler' );
function noyona_remove_wishlist_item_handler() {
    $account_url = function_exists( 'wc_get_account_endpoint_url' )
        ? wc_get_account_endpoint_url( 'wishlist' )
        : home_url( '/my-account/wishlist/' );

    if ( ! is_user_logged_in() ) {
        wp_safe_redirect( $account_url );
        exit;
    }

    $product_id   = isset( $_REQUEST['product_id'] ) ? absint( wp_unslash( $_REQUEST['product_id'] ) ) : 0;
    $variation_id = isset( $_REQUEST['variation_id'] ) ? absint( wp_unslash( $_REQUEST['variation_id'] ) ) : 0;
    $redirect_to  = isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ) : '';
    if ( '' === $redirect_to ) {
        $redirect_to = $account_url;
    }
    $redirect_to = remove_query_arg( array( 'action', 'product_id', 'variation_id', '_wpnonce' ), $redirect_to );

    $nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';
    if ( $product_id < 1 || '' === $nonce || ! wp_verify_nonce( $nonce, 'noyona_remove_wishlist_item_' . $product_id . '_' . $variation_id ) ) {
        wp_safe_redirect( add_query_arg( 'noyona_wishlist_notice', 'invalid_nonce', $redirect_to ) );
        exit;
    }

    $removed = noyona_remove_product_wishlist_item( get_current_user_id(), $product_id, $variation_id );
    wp_safe_redirect( add_query_arg( 'noyona_wishlist_notice', $removed ? 'removed' : 'not_found', $redirect_to ) );
    exit;
}

