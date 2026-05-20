<?php
/**
 * Noyona — TEMPORARY: allow duplicate product reviews for authorized admins only.
 *
 * Purpose:
 *   The SEO specialist needs to submit multiple product ratings/reviews on the
 *   same product (including rating-only submissions, which all share the same
 *   placeholder body) during the current SEO data-entry window. WordPress core
 *   (wp_allow_comment in wp-includes/comment.php) blocks these as duplicates.
 *   This file bypasses that core duplicate check, but ONLY for:
 *     - logged-in users
 *     - with manage_options OR manage_woocommerce capability
 *     - on product post-type reviews
 *     - while the feature flag below is true
 *
 * Safety boundaries:
 *   - Public/anonymous users: still blocked by core duplicate check.
 *   - Normal customers (no admin/shop-manager cap): still blocked.
 *   - Non-product comments (blog posts, pages): never affected.
 *   - Nonce, comment flood, disallowed-keys, WooCommerce verified-owner
 *     setting, and all other security gates are unchanged.
 *
 * ============================================================================
 *  DISABLE AFTER SEO RATING DATA ENTRY IS FINISHED
 * ============================================================================
 *  Primary disable (one-line edit below):
 *      Change:
 *          define( 'NOYONA_ALLOW_DUPLICATE_REVIEWS_TEMP', true );
 *      To:
 *          define( 'NOYONA_ALLOW_DUPLICATE_REVIEWS_TEMP', false );
 *
 *  Backup disable:
 *      Remove or comment out the line
 *          'review-duplicates-temporary.php',
 *      from the $theme_inc_files array in functions.php.
 *      You can also delete this entire file.
 * ============================================================================
 *
 * @package viteseo-noyona
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ---------------------------------------------------------------------------
// FEATURE FLAG — flip to false to immediately re-block duplicate reviews.
// ---------------------------------------------------------------------------
if ( ! defined( 'NOYONA_ALLOW_DUPLICATE_REVIEWS_TEMP' ) ) {
	define( 'NOYONA_ALLOW_DUPLICATE_REVIEWS_TEMP', true );
}

add_filter( 'duplicate_comment_id', 'noyona_maybe_allow_duplicate_product_review', 10, 2 );
/**
 * Bypass WP core's duplicate-comment block for authorized product reviews only.
 *
 * @param int|null            $dupe_id     Existing duplicate comment ID found by core (truthy = duplicate).
 * @param array<string,mixed> $commentdata Comment data being submitted.
 * @return int|null|false                 Null/false to allow submission; original $dupe_id otherwise.
 */
function noyona_maybe_allow_duplicate_product_review( $dupe_id, $commentdata ) {
	// No duplicate detected by core → nothing to do.
	if ( empty( $dupe_id ) ) {
		return $dupe_id;
	}

	// Feature flag must be explicitly on.
	if ( ! defined( 'NOYONA_ALLOW_DUPLICATE_REVIEWS_TEMP' ) || ! NOYONA_ALLOW_DUPLICATE_REVIEWS_TEMP ) {
		return $dupe_id;
	}

	// Must be logged in.
	if ( ! is_user_logged_in() ) {
		return $dupe_id;
	}

	// Must hold an administrator or shop-manager capability.
	if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'manage_woocommerce' ) ) {
		return $dupe_id;
	}

	// Must target a product post.
	$post_id = isset( $commentdata['comment_post_ID'] ) ? (int) $commentdata['comment_post_ID'] : 0;
	if ( $post_id <= 0 || 'product' !== get_post_type( $post_id ) ) {
		return $dupe_id;
	}

	// Must be a review/standard comment on that product (exclude pingbacks/trackbacks).
	$comment_type = isset( $commentdata['comment_type'] ) ? (string) $commentdata['comment_type'] : '';
	if ( '' !== $comment_type && 'comment' !== $comment_type && 'review' !== $comment_type ) {
		return $dupe_id;
	}

	// All gates passed — allow this duplicate review.
	return null;
}
