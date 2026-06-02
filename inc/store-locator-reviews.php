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
