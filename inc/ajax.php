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

