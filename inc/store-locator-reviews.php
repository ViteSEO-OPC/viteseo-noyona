<?php
/**
 * Store Locator (Noyona) front-end review handling.
 *
 * Keeps the public store-review rating logic out of theme-setup.php.
 * Registered globally via functions.php so the hook is attached on every
 * request — including the /wp-comments-post.php submission, where the
 * location block render template does NOT run.
 *
 * @package ViteSEO_Noyona
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ----- Location review submit: require a logged-in user (server-side gate) ----- */
add_filter( 'preprocess_comment', 'noyona_location_review_require_login', 5 );
/**
 * Block guests from submitting store reviews via direct POST.
 *
 * Scoped strictly to the location store-review form so it never affects
 * product reviews, blog comments, or any other comment type. Authoritative
 * counterpart to the front-end button/modal hiding.
 *
 * @param array $commentdata Incoming comment data.
 * @return array
 */
function noyona_location_review_require_login( $commentdata ) {
    $is_location_store_review = isset( $_POST['nsl_v2_store_review'] ) && '1' === (string) wp_unslash( $_POST['nsl_v2_store_review'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
    if ( ! $is_location_store_review ) {
        return $commentdata;
    }

    $nonce = isset( $_POST['noyona_location_review_nonce'] ) ? sanitize_text_field( (string) wp_unslash( $_POST['noyona_location_review_nonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
    if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'noyona_location_review_submit' ) ) {
        return $commentdata;
    }

    $post_id = isset( $commentdata['comment_post_ID'] ) ? (int) $commentdata['comment_post_ID'] : 0;
    if ( $post_id <= 0 || 'store' !== get_post_type( $post_id ) ) {
        return $commentdata;
    }

    if ( ! is_user_logged_in() ) {
        wp_die(
            esc_html__( 'You must be logged in to submit a store review.', 'noyona' ),
            esc_html__( 'Login required', 'noyona' ),
            array(
                'response'  => 403,
                'back_link' => true,
            )
        );
    }

    // One review per user per store. Approved or pending reviews count;
    // spam/trash are excluded so a removed review frees the slot.
    if ( noyona_location_user_has_store_review( get_current_user_id(), $post_id ) ) {
        wp_die(
            esc_html__( 'You have already submitted a review for this store.', 'noyona' ),
            esc_html__( 'Review already submitted', 'noyona' ),
            array(
                'response'  => 403,
                'back_link' => true,
            )
        );
    }

    return $commentdata;
}

/**
 * Whether a user already has an approved or pending review for a given store.
 *
 * Counts comment_approved IN ('1' approved, '0' pending); excludes spam/trash.
 *
 * @param int $user_id User ID.
 * @param int $store_id Store post ID.
 * @return bool
 */
function noyona_location_user_has_store_review( $user_id, $store_id ) {
    $user_id  = (int) $user_id;
    $store_id = (int) $store_id;
    if ( $user_id <= 0 || $store_id <= 0 ) {
        return false;
    }

    $existing = get_comments(
        array(
            'user_id' => $user_id,
            'post_id' => $store_id,
            'type'    => 'comment',
            'status'  => 'all',
            'fields'  => 'all',
        )
    );

    foreach ( $existing as $existing_comment ) {
        $approved = (string) $existing_comment->comment_approved;
        if ( '1' === $approved || '0' === $approved ) {
            return true;
        }
    }

    return false;
}

/* ----- Location review submit: persist the customer's star rating ----- */
add_action( 'comment_post', 'noyona_location_review_save_rating', 25, 3 );
/**
 * Save the optional 1-5 star rating submitted with a store review.
 *
 * Behavior agreed with the team:
 * - A submitted rating (1-5) is stored as comment meta `rating`.
 * - No rating submitted => nothing is stored; the front-end intentionally
 *   falls back to 5 stars on display (see blocks/location/render.php).
 *
 * @param int        $comment_id       New comment ID.
 * @param int|string $comment_approved Approval status (unused).
 * @param array      $commentdata      Comment data.
 * @return void
 */
function noyona_location_review_save_rating( $comment_id, $comment_approved, $commentdata ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
    $is_location_store_review = isset( $_POST['nsl_v2_store_review'] ) && '1' === (string) wp_unslash( $_POST['nsl_v2_store_review'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
    if ( ! $is_location_store_review ) {
        return;
    }

    $nonce = isset( $_POST['noyona_location_review_nonce'] ) ? sanitize_text_field( (string) wp_unslash( $_POST['noyona_location_review_nonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
    if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'noyona_location_review_submit' ) ) {
        return;
    }

    $post_id = isset( $commentdata['comment_post_ID'] ) ? (int) $commentdata['comment_post_ID'] : 0;
    if ( $post_id <= 0 || 'store' !== get_post_type( $post_id ) ) {
        return;
    }

    if ( ! isset( $_POST['nsl_comment_rating'] ) ) {
        return;
    }

    $rating = (int) wp_unslash( $_POST['nsl_comment_rating'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
    $rating = max( 1, min( 5, $rating ) );

    update_comment_meta( (int) $comment_id, 'rating', $rating );
}
