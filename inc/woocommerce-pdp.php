<?php
/**
 * PDP: extra product fields, buy-now redirect, assets.
 *
 * @package viteseo-noyona
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Extra WooCommerce product editor fields (Ingredients, How to use, optional social proof).
 */
add_action( 'woocommerce_product_options_general_product_data', 'noyona_pdp_render_product_fields' );
function noyona_pdp_render_product_fields() {
	echo '<div class="options_group noyona-pdp-fields">';

	woocommerce_wp_text_input(
		array(
			'id'          => '_noyona_social_proof',
			'label'       => __( 'PDP social proof line', 'viteseo-noyona-childtheme' ),
			'description' => __( 'Optional. Example: A top-rated favorite. Shown above the product title.', 'viteseo-noyona-childtheme' ),
			'desc_tip'    => true,
		)
	);

	woocommerce_wp_textarea_input(
		array(
			'id'          => '_noyona_product_ingredients',
			'label'       => __( 'Ingredients (PDP tab)', 'viteseo-noyona-childtheme' ),
			'description' => __( 'Shown in the Ingredients tab on the product page.', 'viteseo-noyona-childtheme' ),
			'rows'        => 6,
		)
	);

	woocommerce_wp_textarea_input(
		array(
			'id'          => '_noyona_product_how_to_use',
			'label'       => __( 'How to use (PDP tab)', 'viteseo-noyona-childtheme' ),
			'description' => __( 'Shown in the How to use tab on the product page.', 'viteseo-noyona-childtheme' ),
			'rows'        => 6,
		)
	);

	echo '</div>';
}

add_action( 'woocommerce_process_product_meta', 'noyona_pdp_save_product_fields' );
function noyona_pdp_save_product_fields( $post_id ) {
	if ( ! isset( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) ) {
		return;
	}

	$proof = isset( $_POST['_noyona_social_proof'] ) ? sanitize_text_field( wp_unslash( $_POST['_noyona_social_proof'] ) ) : '';
	update_post_meta( $post_id, '_noyona_social_proof', $proof );

	$ingredients = isset( $_POST['_noyona_product_ingredients'] ) ? wp_kses_post( wp_unslash( $_POST['_noyona_product_ingredients'] ) ) : '';
	update_post_meta( $post_id, '_noyona_product_ingredients', $ingredients );

	$how = isset( $_POST['_noyona_product_how_to_use'] ) ? wp_kses_post( wp_unslash( $_POST['_noyona_product_how_to_use'] ) ) : '';
	update_post_meta( $post_id, '_noyona_product_how_to_use', $how );
}

/**
 * Redirect to cart after add to cart when Buy now was used.
 */
add_filter( 'woocommerce_add_to_cart_redirect', 'noyona_pdp_buy_now_redirect', 99, 1 );
function noyona_pdp_buy_now_redirect( $url ) {
	if ( ! empty( $_REQUEST['noyona_buy_now'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( function_exists( 'wc_get_cart_url' ) ) {
			return wc_get_cart_url();
		}
	}
	return $url;
}

/**
 * Append "See all reviews" after the product rating block when reviews exist.
 *
 * @param string $block_content Rendered block HTML.
 * @param array  $block         Block data.
 * @return string
 */
add_filter( 'render_block', 'noyona_pdp_append_reviews_link_after_rating', 15, 2 );
function noyona_pdp_append_reviews_link_after_rating( $block_content, $block ) {
	if ( is_admin() || empty( $block['blockName'] ) || 'woocommerce/product-rating' !== $block['blockName'] ) {
		return $block_content;
	}
	if ( ! function_exists( 'wc_get_product' ) || ! is_singular( 'product' ) ) {
		return $block_content;
	}
	$product = wc_get_product( get_the_ID() );
	if ( ! $product ) {
		return $block_content;
	}
	$count = (int) $product->get_review_count();
	if ( $count < 1 ) {
		return $block_content;
	}
	$average = (float) $product->get_average_rating();
	if ( $average < 0 ) {
		$average = 0;
	}
	$average_label = number_format_i18n( $average, 1 );

	$rating_markup = '';
	if ( function_exists( 'wc_get_rating_html' ) ) {
		$rating_markup = wc_get_rating_html( $average, $count );
	}
	$url  = get_permalink( $product->get_id() ) . '#reviews';
	$text = sprintf(
		/* translators: %d: review count */
		_n( 'See all %d verified review', 'See all %d verified reviews', $count, 'viteseo-noyona-childtheme' ),
		$count
	);
	$link = '<a class="noyona-pdp-reviews-link" href="' . esc_url( $url ) . '">' . esc_html( $text ) . '</a>';

	// Render a stable custom rating row to avoid Woo default "(x customer review)" output styles.
	$row  = '<div class="wc-block-components-product-rating noyona-pdp-rating-row">';
	if ( $rating_markup ) {
		$row .= '<div class="noyona-pdp-rating-row__stars">';
		$row .= $rating_markup;
		$row .= '</div>';
	}
	$row .= '<span class="wc-block-components-product-rating__average">' . esc_html( $average_label ) . '</span>';
	$row .= $link;
	$row .= '</div>';

	return $row;
}

/**
 * Product IDs that count as "this product" for review eligibility (parent + variations).
 *
 * @param int $product_id Product or variation ID.
 * @return int[]
 */
function noyona_get_review_product_match_ids( $product_id ) {
	$product_id = absint( $product_id );
	if ( $product_id < 1 || ! function_exists( 'wc_get_product' ) ) {
		return array();
	}

	$product = wc_get_product( $product_id );
	if ( ! $product ) {
		return array();
	}

	$ids = array( $product_id );

	if ( $product->is_type( 'variable' ) ) {
		$ids = array_merge( $ids, $product->get_children() );
	} elseif ( $product->is_type( 'variation' ) ) {
		$parent_id = (int) $product->get_parent_id();
		if ( $parent_id > 0 ) {
			$ids[] = $parent_id;
			$parent = wc_get_product( $parent_id );
			if ( $parent && $parent->is_type( 'variable' ) ) {
				$ids = array_merge( $ids, $parent->get_children() );
			}
		}
	}

	return array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
}

/**
 * Whether the user may bypass the completed-order requirement (shop staff).
 *
 * @param int $user_id User ID.
 * @return bool
 */
function noyona_user_can_bypass_review_purchase_check( $user_id = 0 ) {
	$user_id = $user_id > 0 ? $user_id : get_current_user_id();
	if ( $user_id < 1 ) {
		return false;
	}

	return user_can( $user_id, 'manage_woocommerce' ) || user_can( $user_id, 'manage_options' );
}

/**
 * Days after order completion that a customer may leave a product review.
 *
 * @return int
 */
function noyona_get_review_window_days() {
	$days = (int) apply_filters( 'noyona_review_window_days', 14 );

	return max( 1, $days );
}

/**
 * Unix timestamp when the order was marked completed (received).
 *
 * @param WC_Order $order Order object.
 * @return int
 */
function noyona_get_order_received_timestamp( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return 0;
	}

	$completed = $order->get_date_completed();
	if ( $completed ) {
		return (int) $completed->getTimestamp();
	}

	if ( $order->has_status( 'completed' ) ) {
		$modified = $order->get_date_modified();
		if ( $modified ) {
			return (int) $modified->getTimestamp();
		}
	}

	return 0;
}

/**
 * Whether the review window is still open for a completed order.
 *
 * @param WC_Order $order Order object.
 * @return bool
 */
function noyona_order_is_within_review_window( $order ) {
	$received_at = noyona_get_order_received_timestamp( $order );
	if ( $received_at <= 0 ) {
		return false;
	}

	$deadline = $received_at + ( DAY_IN_SECONDS * noyona_get_review_window_days() );

	return (int) current_time( 'timestamp', true ) <= $deadline;
}

/**
 * Completed order linked to a product review comment.
 *
 * @param int $comment_id Comment ID.
 * @return WC_Order|null
 */
function noyona_get_review_order_for_comment( $comment_id ) {
	$comment_id = absint( $comment_id );
	if ( $comment_id < 1 ) {
		return null;
	}

	$order_id = (int) get_comment_meta( $comment_id, 'noyona_review_order_id', true );
	if ( $order_id > 0 ) {
		$order = wc_get_order( $order_id );
		return $order instanceof WC_Order ? $order : null;
	}

	$comment = get_comment( $comment_id );
	if ( ! $comment instanceof WP_Comment || (int) $comment->user_id < 1 ) {
		return null;
	}

	$orders = noyona_get_completed_order_ids_with_product( (int) $comment->user_id, (int) $comment->comment_post_ID );
	foreach ( $orders as $candidate_id ) {
		$order = wc_get_order( $candidate_id );
		if ( $order instanceof WC_Order ) {
			return $order;
		}
	}

	return null;
}

/**
 * Whether a user may edit their own product review (within the review window).
 *
 * @param int $comment_id Comment ID.
 * @param int $user_id    User ID.
 * @return bool
 */
function noyona_user_can_edit_product_review( $comment_id, $user_id = 0 ) {
	$comment_id = absint( $comment_id );
	$user_id    = $user_id > 0 ? absint( $user_id ) : get_current_user_id();

	if ( $comment_id < 1 || $user_id < 1 ) {
		return false;
	}

	$comment = get_comment( $comment_id );
	if ( ! $comment instanceof WP_Comment ) {
		return false;
	}

	if ( (int) $comment->user_id !== $user_id ) {
		return false;
	}

	if ( 'review' !== (string) $comment->comment_type || '1' !== (string) $comment->comment_approved ) {
		return false;
	}

	if ( 'product' !== get_post_type( (int) $comment->comment_post_ID ) ) {
		return false;
	}

	if ( noyona_user_can_bypass_review_purchase_check( $user_id ) ) {
		return true;
	}

	$order = noyona_get_review_order_for_comment( $comment_id );

	return $order instanceof WC_Order && noyona_order_is_within_review_window( $order );
}

/**
 * Whether the user has at least one editable review on this product.
 *
 * @param int $product_id Product ID.
 * @param int $user_id    User ID.
 * @return bool
 */
function noyona_user_has_editable_review_on_product( $product_id, $user_id = 0 ) {
	$product_id = absint( $product_id );
	$user_id    = $user_id > 0 ? absint( $user_id ) : get_current_user_id();

	if ( $product_id < 1 || $user_id < 1 ) {
		return false;
	}

	foreach ( noyona_get_user_product_review_comments( $user_id, $product_id ) as $comment ) {
		if ( $comment instanceof WP_Comment && noyona_user_can_edit_product_review( (int) $comment->comment_ID, $user_id ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Completed order IDs (newest first) that include this product for the user.
 *
 * @param int $user_id    User ID.
 * @param int $product_id Product ID.
 * @return int[]
 */
function noyona_get_completed_order_ids_with_product( $user_id, $product_id ) {
	$user_id    = absint( $user_id );
	$product_id = absint( $product_id );

	if ( $user_id < 1 || $product_id < 1 || ! function_exists( 'wc_get_orders' ) ) {
		return array();
	}

	$match_ids = noyona_get_review_product_match_ids( $product_id );
	if ( empty( $match_ids ) ) {
		return array();
	}

	$user  = get_user_by( 'id', $user_id );
	$email = ( $user && ! empty( $user->user_email ) ) ? (string) $user->user_email : '';

	$base_query = array(
		'status' => array( 'completed' ),
		'limit'  => -1,
		'return' => 'ids',
	);

	$order_ids = wc_get_orders( array_merge( $base_query, array( 'customer_id' => $user_id ) ) );

	if ( is_email( $email ) ) {
		$email_order_ids = wc_get_orders( array_merge( $base_query, array( 'billing_email' => $email ) ) );
		$order_ids       = array_values( array_unique( array_merge( (array) $order_ids, (array) $email_order_ids ) ) );
	}

	$matching_orders = array();

	foreach ( $order_ids as $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			continue;
		}

		$contains_product = false;
		foreach ( $order->get_items( 'line_item' ) as $item ) {
			if ( ! $item instanceof WC_Order_Item_Product ) {
				continue;
			}

			$item_product_id = (int) $item->get_product_id();
			if ( in_array( $item_product_id, $match_ids, true ) ) {
				$contains_product = true;
				break;
			}
		}

		if ( $contains_product ) {
			$matching_orders[] = $order;
		}
	}

	if ( empty( $matching_orders ) ) {
		return array();
	}

	usort(
		$matching_orders,
		static function ( $a, $b ) {
			$a_date = $a instanceof WC_Order ? $a->get_date_completed() : null;
			$b_date = $b instanceof WC_Order ? $b->get_date_completed() : null;
			$a_time = $a_date ? $a_date->getTimestamp() : 0;
			$b_time = $b_date ? $b_date->getTimestamp() : 0;
			return $b_time <=> $a_time;
		}
	);

	return array_values(
		array_map(
			static function ( $order ) {
				return $order instanceof WC_Order ? (int) $order->get_id() : 0;
			},
			$matching_orders
		)
	);
}

/**
 * Product reviews left by a user on a product (approved only).
 *
 * @param int $user_id    User ID.
 * @param int $product_id Product ID.
 * @return WP_Comment[]
 */
function noyona_get_user_product_review_comments( $user_id, $product_id ) {
	$user_id    = absint( $user_id );
	$product_id = absint( $product_id );

	if ( $user_id < 1 || $product_id < 1 ) {
		return array();
	}

	$comments = get_comments(
		array(
			'user_id' => $user_id,
			'post_id' => $product_id,
			'type'    => 'review',
			'status'  => 'approve',
			'number'  => 0,
			'orderby' => 'comment_date_gmt',
			'order'   => 'DESC',
		)
	);

	return is_array( $comments ) ? $comments : array();
}

/**
 * Completed order IDs this user already reviewed for this product.
 *
 * @param int $user_id    User ID.
 * @param int $product_id Product ID.
 * @return int[]
 */
function noyona_get_reviewed_order_ids_for_product( $user_id, $product_id ) {
	$reviewed = array();

	foreach ( noyona_get_user_product_review_comments( $user_id, $product_id ) as $comment ) {
		if ( ! $comment instanceof WP_Comment ) {
			continue;
		}

		$order_id = (int) get_comment_meta( $comment->comment_ID, 'noyona_review_order_id', true );
		if ( $order_id > 0 ) {
			$reviewed[] = $order_id;
		}
	}

	return array_values( array_unique( $reviewed ) );
}

/**
 * Completed orders for this product that do not yet have a review from this user.
 *
 * @param int $user_id    User ID.
 * @param int $product_id Product ID.
 * @return int[]
 */
function noyona_get_reviewable_order_ids( $user_id, $product_id ) {
	$user_id    = absint( $user_id );
	$product_id = absint( $product_id );

	if ( $user_id < 1 || $product_id < 1 ) {
		return array();
	}

	$orders   = noyona_get_completed_order_ids_with_product( $user_id, $product_id );
	$reviewed = noyona_get_reviewed_order_ids_for_product( $user_id, $product_id );

	if ( empty( $orders ) ) {
		return array();
	}

	$unlinked_reviews = 0;
	foreach ( noyona_get_user_product_review_comments( $user_id, $product_id ) as $comment ) {
		if ( ! $comment instanceof WP_Comment ) {
			continue;
		}
		if ( (int) get_comment_meta( $comment->comment_ID, 'noyona_review_order_id', true ) <= 0 ) {
			$unlinked_reviews++;
		}
	}

	$available = array();
	foreach ( $orders as $order_id ) {
		if ( in_array( (int) $order_id, $reviewed, true ) ) {
			continue;
		}
		if ( $unlinked_reviews > 0 ) {
			$unlinked_reviews--;
			continue;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order || ! noyona_order_is_within_review_window( $order ) ) {
			continue;
		}

		$available[] = (int) $order_id;
	}

	return $available;
}

/**
 * Whether the user has an unreviewed completed order past the review deadline.
 *
 * @param int $user_id    User ID.
 * @param int $product_id Product ID.
 * @return bool
 */
function noyona_user_has_expired_review_opportunity( $user_id, $product_id ) {
	$user_id    = absint( $user_id );
	$product_id = absint( $product_id );

	if ( $user_id < 1 || $product_id < 1 ) {
		return false;
	}

	$orders   = noyona_get_completed_order_ids_with_product( $user_id, $product_id );
	$reviewed = noyona_get_reviewed_order_ids_for_product( $user_id, $product_id );

	foreach ( $orders as $order_id ) {
		if ( in_array( (int) $order_id, $reviewed, true ) ) {
			continue;
		}

		$order = wc_get_order( $order_id );
		if ( $order && ! noyona_order_is_within_review_window( $order ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Pick the completed order this new review should be linked to.
 *
 * @param int $user_id    User ID.
 * @param int $product_id Product ID.
 * @return int Order ID, or 0 when not applicable (e.g. staff bypass).
 */
function noyona_pick_review_order_id( $user_id, $product_id ) {
	if ( noyona_user_can_bypass_review_purchase_check( $user_id ) ) {
		return 0;
	}

	$available = noyona_get_reviewable_order_ids( $user_id, $product_id );

	return ! empty( $available ) ? (int) $available[0] : 0;
}

/**
 * Whether the user has a completed order that includes this product (received).
 *
 * @param int $user_id    User ID.
 * @param int $product_id Product ID.
 * @return bool
 */
function noyona_user_has_received_product( $user_id, $product_id ) {
	return ! empty( noyona_get_completed_order_ids_with_product( $user_id, $product_id ) );
}

/**
 * Whether the current (or given) user may submit a review for this product.
 *
 * @param int $product_id Product ID.
 * @param int $user_id    User ID (defaults to current user).
 * @return bool
 */
function noyona_user_can_review_product( $product_id = 0, $user_id = 0 ) {
	$product_id = $product_id > 0 ? absint( $product_id ) : (int) get_the_ID();
	$user_id    = $user_id > 0 ? absint( $user_id ) : get_current_user_id();

	if ( $product_id < 1 || $user_id < 1 ) {
		return false;
	}

	/**
	 * Filter whether a user may submit a product review.
	 *
	 * @param bool $can_review   Default eligibility from completed orders.
	 * @param int  $product_id   Product ID.
	 * @param int  $user_id      User ID.
	 */
	$can_review = ! empty( noyona_get_reviewable_order_ids( $user_id, $product_id ) );
	if ( noyona_user_can_bypass_review_purchase_check( $user_id ) ) {
		$can_review = true;
	}

	return (bool) apply_filters(
		'noyona_user_can_review_product',
		$can_review,
		$product_id,
		$user_id
	);
}

add_action( 'pre_comment_on_post', 'noyona_pdp_validate_review_purchase', 5 );
/**
 * Block product reviews from users who have not received the product.
 *
 * @param int $comment_post_id Post ID.
 */
function noyona_pdp_validate_review_purchase( $comment_post_id ) {
	$comment_post_id = absint( $comment_post_id );
	if ( $comment_post_id < 1 || 'product' !== get_post_type( $comment_post_id ) ) {
		return;
	}

	if ( ! is_user_logged_in() ) {
		wp_die(
			esc_html__( 'You must be logged in to submit a review.', 'noyona-childtheme' ),
			esc_html__( 'Review not allowed', 'noyona-childtheme' ),
			array( 'response' => 403 )
		);
	}

	if ( noyona_user_can_review_product( $comment_post_id ) ) {
		$order_id = noyona_pick_review_order_id( get_current_user_id(), $comment_post_id );
		if ( $order_id > 0 ) {
			$GLOBALS['noyona_current_review_order_id'] = $order_id;
		}
		return;
	}

	$user_id = get_current_user_id();

	if ( ! noyona_user_has_received_product( $user_id, $comment_post_id ) ) {
		$message = esc_html__( 'Only customers who have purchased and received this product can leave a review.', 'noyona-childtheme' );
	} elseif ( noyona_user_has_expired_review_opportunity( $user_id, $comment_post_id ) ) {
		$message = sprintf(
			/* translators: %d: number of days to leave a review after delivery */
			esc_html__( 'The review period for this product has ended. Reviews must be submitted within %d days of receiving your order.', 'noyona-childtheme' ),
			noyona_get_review_window_days()
		);
	} else {
		$message = esc_html__( 'You have already reviewed this product for each of your completed orders.', 'noyona-childtheme' );
	}

	wp_die(
		$message,
		esc_html__( 'Review not allowed', 'noyona-childtheme' ),
		array( 'response' => 403 )
	);
}

add_filter( 'duplicate_comment_id', 'noyona_pdp_allow_review_per_order', 11, 2 );
/**
 * Allow another review on the same product when a different completed order is eligible.
 *
 * @param int|null            $dupe_id     Duplicate comment ID from core.
 * @param array<string,mixed> $commentdata Comment data.
 * @return int|null|false
 */
function noyona_pdp_allow_review_per_order( $dupe_id, $commentdata ) {
	if ( empty( $dupe_id ) || ! is_user_logged_in() ) {
		return $dupe_id;
	}

	$post_id = isset( $commentdata['comment_post_ID'] ) ? (int) $commentdata['comment_post_ID'] : 0;
	if ( $post_id < 1 || 'product' !== get_post_type( $post_id ) ) {
		return $dupe_id;
	}

	$user_id = get_current_user_id();
	if ( noyona_user_can_bypass_review_purchase_check( $user_id ) ) {
		return $dupe_id;
	}

	if ( ! empty( noyona_get_reviewable_order_ids( $user_id, $post_id ) ) ) {
		return null;
	}

	return $dupe_id;
}

add_filter( 'comments_open', 'noyona_pdp_comments_open_for_eligible_reviewers', 10, 2 );
/**
 * Gate product comments for logged-in users: allow new reviews or editing within the window.
 *
 * @param bool $open    Whether comments are open.
 * @param int  $post_id Post ID.
 * @return bool
 */
function noyona_pdp_comments_open_for_eligible_reviewers( $open, $post_id ) {
	if ( 'product' !== get_post_type( $post_id ) ) {
		return $open;
	}

	if ( ! is_user_logged_in() ) {
		return $open;
	}

	if ( noyona_user_can_review_product( $post_id ) || noyona_user_has_editable_review_on_product( $post_id ) ) {
		return true;
	}

	return false;
}

add_filter( 'pre_comment_approved', 'noyona_pdp_auto_approve_product_review', 20, 2 );
/**
 * Publish product reviews immediately for eligible verified buyers (and staff bypass).
 *
 * @param int|string|bool $approved     Approval status (0/1/'spam'/etc.).
 * @param array           $commentdata  Comment data.
 * @return int|string|bool
 */
function noyona_pdp_auto_approve_product_review( $approved, $commentdata ) {
	$post_id = isset( $commentdata['comment_post_ID'] ) ? (int) $commentdata['comment_post_ID'] : 0;
	if ( $post_id < 1 || 'product' !== get_post_type( $post_id ) ) {
		return $approved;
	}

	if ( ! is_user_logged_in() ) {
		return $approved;
	}

	if ( noyona_user_can_review_product( $post_id ) ) {
		return 1;
	}

	return $approved;
}

add_filter( 'comment_post_redirect', 'noyona_pdp_review_redirect_to_reviews', 10, 3 );
/**
 * Send reviewers back to the PDP reviews section after submit.
 *
 * @param string     $location Redirect URL.
 * @param WP_Comment $comment  New comment.
 * @return string
 */
function noyona_pdp_review_redirect_to_reviews( $location, $comment ) {
	if ( ! $comment instanceof WP_Comment || 'product' !== get_post_type( (int) $comment->comment_post_ID ) ) {
		return $location;
	}

	$permalink = get_permalink( (int) $comment->comment_post_ID );
	if ( ! $permalink ) {
		return $location;
	}

	return add_query_arg( 'review_submitted', '1', $permalink ) . '#reviews';
}

/**
 * Approved product reviews for a PDP.
 *
 * @param int $product_id Product ID.
 * @return WP_Comment[]
 */
function noyona_pdp_get_product_review_comments( $product_id ) {
	$product_id = absint( $product_id );
	if ( $product_id < 1 ) {
		return array();
	}

	$comments = get_comments(
		array(
			'post_id' => $product_id,
			'status'  => 'approve',
			'type'    => 'review',
			'number'  => 0,
			'orderby' => 'comment_date_gmt',
			'order'   => 'DESC',
		)
	);

	return is_array( $comments ) ? $comments : array();
}

add_action( 'comment_post', 'noyona_pdp_attach_review_order_id', 15, 3 );
/**
 * Link a product review to the completed order it is for (one review per order).
 *
 * @param int        $comment_id       Comment ID.
 * @param int|string $comment_approved Approval status.
 * @param array      $commentdata      Comment data.
 */
function noyona_pdp_attach_review_order_id( $comment_id, $comment_approved, $commentdata ) {
	unset( $comment_approved );

	if ( empty( $commentdata['comment_post_ID'] ) || 'product' !== get_post_type( (int) $commentdata['comment_post_ID'] ) ) {
		return;
	}

	if ( ! is_user_logged_in() ) {
		return;
	}

	$order_id = isset( $GLOBALS['noyona_current_review_order_id'] )
		? (int) $GLOBALS['noyona_current_review_order_id']
		: noyona_pick_review_order_id( get_current_user_id(), (int) $commentdata['comment_post_ID'] );

	unset( $GLOBALS['noyona_current_review_order_id'] );

	if ( $order_id > 0 ) {
		update_comment_meta( $comment_id, 'noyona_review_order_id', $order_id );
	}
}

add_action( 'wp_ajax_noyona_update_product_review', 'noyona_pdp_ajax_update_product_review' );
/**
 * Update the current user's product review (within the edit window).
 */
function noyona_pdp_ajax_update_product_review() {
	check_ajax_referer( 'noyona-review-edit', 'nonce' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => __( 'You must be logged in to edit a review.', 'noyona-childtheme' ) ), 403 );
	}

	$comment_id = isset( $_POST['comment_id'] ) ? absint( $_POST['comment_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( $comment_id < 1 || ! noyona_user_can_edit_product_review( $comment_id ) ) {
		wp_send_json_error(
			array(
				'message' => sprintf(
					/* translators: %d: number of days reviews can be edited after delivery */
					__( 'This review can no longer be edited. Reviews can only be changed within %d days of receiving your order.', 'noyona-childtheme' ),
					noyona_get_review_window_days()
				),
			),
			403
		);
	}

	$comment = get_comment( $comment_id );
	if ( ! $comment instanceof WP_Comment ) {
		wp_send_json_error( array( 'message' => __( 'Review not found.', 'noyona-childtheme' ) ), 404 );
	}

	$product_id = (int) $comment->comment_post_ID;
	$rating     = isset( $_POST['rating'] ) ? absint( $_POST['rating'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$title      = isset( $_POST['noyona_review_title'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['noyona_review_title'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$body       = isset( $_POST['comment'] ) ? sanitize_textarea_field( wp_unslash( (string) $_POST['comment'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

	if ( function_exists( 'wc_review_ratings_enabled' ) && wc_review_ratings_enabled() ) {
		if ( function_exists( 'wc_review_ratings_required' ) && wc_review_ratings_required() && ( $rating < 1 || $rating > 5 ) ) {
			wp_send_json_error( array( 'message' => __( 'Please select a rating.', 'noyona-childtheme' ) ), 400 );
		}
	} else {
		$rating = 0;
	}

	if ( '' === trim( $body ) && $rating > 0 ) {
		$body = '[noyona-rating-only]';
	}

	$updated = wp_update_comment(
		array(
			'comment_ID'      => $comment_id,
			'comment_content' => $body,
		),
		true
	);

	if ( is_wp_error( $updated ) ) {
		wp_send_json_error( array( 'message' => __( 'Could not save your review. Please try again.', 'noyona-childtheme' ) ), 500 );
	}

	if ( $rating > 0 && $rating <= 5 ) {
		update_comment_meta( $comment_id, 'rating', $rating );
	}

	if ( '' !== $title ) {
		update_comment_meta( $comment_id, 'review_title', $title );
	} else {
		delete_comment_meta( $comment_id, 'review_title' );
	}

	$new_image_ids = noyona_pdp_handle_review_image_uploads();
	if ( ! empty( $new_image_ids ) ) {
		$existing_raw = get_comment_meta( $comment_id, 'noyona_review_image_ids', true );
		$existing_ids = array();
		if ( is_array( $existing_raw ) ) {
			$existing_ids = array_map( 'absint', $existing_raw );
		} elseif ( is_string( $existing_raw ) && '' !== trim( $existing_raw ) ) {
			$existing_ids = array_map( 'absint', explode( ',', $existing_raw ) );
		}

		$merged_ids = array_values( array_unique( array_merge( $existing_ids, $new_image_ids ) ) );
		$merged_ids = array_slice( $merged_ids, 0, 4 );

		update_comment_meta( $comment_id, 'review_image_ids', implode( ',', $merged_ids ) );
		update_comment_meta( $comment_id, 'noyona_review_image_ids', $merged_ids );
	}

	if ( class_exists( 'WC_Comments' ) && $product_id > 0 ) {
		WC_Comments::clear_transients( $product_id );
	}

	$display_content = trim( wp_strip_all_tags( $body ) );
	if ( '[noyona-rating-only]' === $display_content ) {
		$display_content = '';
	}

	$media_urls = noyona_pdp_get_review_media_urls( $comment_id );

	wp_send_json_success(
		array(
			'commentId'  => $comment_id,
			'title'      => $title,
			'content'    => $display_content,
			'rating'     => $rating,
			'mediaUrls'  => $media_urls,
			'mediaCount' => count( $media_urls ),
		)
	);
}

/**
 * Resolve image URLs attached to a product review.
 *
 * @param int $comment_id Comment ID.
 * @return string[]
 */
function noyona_pdp_get_review_media_urls( $comment_id ) {
	$comment_id = absint( $comment_id );
	if ( $comment_id < 1 ) {
		return array();
	}

	if ( function_exists( 'noyona_cr_extract_media_urls' ) ) {
		return noyona_cr_extract_media_urls( $comment_id );
	}

	$urls = array();
	$raw  = get_comment_meta( $comment_id, 'noyona_review_image_ids', true );
	$ids  = array();

	if ( is_array( $raw ) ) {
		$ids = array_map( 'absint', $raw );
	} else {
		$string_ids = get_comment_meta( $comment_id, 'review_image_ids', true );
		if ( is_string( $string_ids ) && '' !== trim( $string_ids ) ) {
			$ids = array_map( 'absint', explode( ',', $string_ids ) );
		}
	}

	foreach ( $ids as $attachment_id ) {
		if ( $attachment_id < 1 ) {
			continue;
		}
		$url = wp_get_attachment_image_url( $attachment_id, 'full' );
		if ( ! $url ) {
			$url = wp_get_attachment_url( $attachment_id );
		}
		if ( $url ) {
			$urls[] = $url;
		}
	}

	return array_values( array_unique( $urls ) );
}

/**
 * Handle optional review image uploads from the custom review form.
 *
 * @return int[] Attachment IDs.
 */
function noyona_pdp_handle_review_image_uploads() {
	$attachment_ids = array();
	if ( empty( $_FILES['noyona_review_images'] ) || ! is_array( $_FILES['noyona_review_images'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return $attachment_ids;
	}

	$files = $_FILES['noyona_review_images']; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( empty( $files['name'] ) || ! is_array( $files['name'] ) ) {
		return $attachment_ids;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$allowed_mimes = array(
		'jpg|jpeg|jpe' => 'image/jpeg',
		'png'          => 'image/png',
		'gif'          => 'image/gif',
		'webp'         => 'image/webp',
	);

	$max_uploads = 4;
	$file_count  = min( count( $files['name'] ), $max_uploads );

	for ( $i = 0; $i < $file_count; $i++ ) {
		$error_code = isset( $files['error'][ $i ] ) ? (int) $files['error'][ $i ] : UPLOAD_ERR_NO_FILE;
		if ( UPLOAD_ERR_OK !== $error_code ) {
			continue;
		}

		$file_array = array(
			'name'     => isset( $files['name'][ $i ] ) ? sanitize_file_name( (string) $files['name'][ $i ] ) : '',
			'type'     => isset( $files['type'][ $i ] ) ? (string) $files['type'][ $i ] : '',
			'tmp_name' => isset( $files['tmp_name'][ $i ] ) ? (string) $files['tmp_name'][ $i ] : '',
			'error'    => $error_code,
			'size'     => isset( $files['size'][ $i ] ) ? (int) $files['size'][ $i ] : 0,
		);
		if ( '' === $file_array['name'] || '' === $file_array['tmp_name'] ) {
			continue;
		}

		$uploaded = wp_handle_upload(
			$file_array,
			array(
				'test_form' => false,
				'mimes'     => $allowed_mimes,
			)
		);
		if ( ! is_array( $uploaded ) || ! empty( $uploaded['error'] ) || empty( $uploaded['file'] ) ) {
			continue;
		}

		$attachment_id = wp_insert_attachment(
			array(
				'post_mime_type' => isset( $uploaded['type'] ) ? (string) $uploaded['type'] : 'image/jpeg',
				'post_title'     => sanitize_text_field( wp_basename( (string) $uploaded['file'] ) ),
				'post_content'   => '',
				'post_status'    => 'inherit',
			),
			(string) $uploaded['file']
		);
		if ( is_wp_error( $attachment_id ) || $attachment_id < 1 ) {
			continue;
		}

		$metadata = wp_generate_attachment_metadata( $attachment_id, (string) $uploaded['file'] );
		if ( is_array( $metadata ) && ! empty( $metadata ) ) {
			wp_update_attachment_metadata( $attachment_id, $metadata );
		}
		$attachment_ids[] = (int) $attachment_id;
	}

	return $attachment_ids;
}

add_action( 'init', 'noyona_pdp_allow_rating_only_review', 5 );
function noyona_pdp_allow_rating_only_review() {
	if ( empty( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) ) {
		return;
	}
	if ( empty( $_SERVER['REQUEST_URI'] ) || false === strpos( (string) $_SERVER['REQUEST_URI'], 'wp-comments-post.php' ) ) {
		return;
	}
	if ( empty( $_POST['noyona_review_extras_nonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return;
	}
	if ( ! isset( $_POST['rating'] ) || '' === trim( (string) wp_unslash( $_POST['rating'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return;
	}
	$post_id = isset( $_POST['comment_post_ID'] ) ? absint( wp_unslash( $_POST['comment_post_ID'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( $post_id <= 0 || 'product' !== get_post_type( $post_id ) ) {
		return;
	}
	$comment_value = isset( $_POST['comment'] ) ? trim( (string) wp_unslash( $_POST['comment'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( '' === $comment_value ) {
		$_POST['comment'] = '[noyona-rating-only]';
	}
}

add_action( 'comment_post', 'noyona_pdp_save_review_extras', 20, 3 );
function noyona_pdp_save_review_extras( $comment_id, $comment_approved, $commentdata ) {
	if ( empty( $commentdata['comment_post_ID'] ) || 'product' !== get_post_type( (int) $commentdata['comment_post_ID'] ) ) {
		return;
	}

	if ( empty( $_POST['noyona_review_extras_nonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return;
	}
	$nonce = sanitize_text_field( wp_unslash( (string) $_POST['noyona_review_extras_nonce'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( ! wp_verify_nonce( $nonce, 'noyona_review_extras' ) ) {
		return;
	}

	if ( isset( $_POST['noyona_review_shade'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$shade = sanitize_text_field( wp_unslash( (string) $_POST['noyona_review_shade'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( '' !== $shade ) {
			update_comment_meta( $comment_id, 'noyona_review_shade', $shade );
		}
	}

	if ( isset( $_POST['noyona_review_title'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$review_title = sanitize_text_field( wp_unslash( (string) $_POST['noyona_review_title'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( '' !== $review_title ) {
			update_comment_meta( $comment_id, 'review_title', $review_title );
		}
	}

	$image_ids = noyona_pdp_handle_review_image_uploads();
	if ( ! empty( $image_ids ) ) {
		update_comment_meta( $comment_id, 'review_image_ids', implode( ',', array_map( 'absint', $image_ids ) ) );
		update_comment_meta( $comment_id, 'noyona_review_image_ids', array_map( 'absint', $image_ids ) );
	}
}

add_action( 'wp_ajax_noyona_review_helpful_vote', 'noyona_pdp_ajax_review_helpful_vote' );
add_action( 'wp_ajax_nopriv_noyona_review_helpful_vote', 'noyona_pdp_ajax_review_helpful_vote' );
if ( ! function_exists( 'noyona_pdp_get_review_voter_key' ) ) {
	/**
	 * Resolve unique voter key for current viewer.
	 *
	 * @return string
	 */
	function noyona_pdp_get_review_voter_key() {
		$current_user_id = get_current_user_id();
		if ( $current_user_id > 0 ) {
			return 'u_' . $current_user_id;
		}

		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['HTTP_USER_AGENT'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		return 'g_' . hash( 'sha256', $ip . '|' . $ua );
	}
}

function noyona_pdp_ajax_review_helpful_vote() {
	check_ajax_referer( 'noyona-review-helpful', 'nonce' );

	$comment_id = isset( $_POST['comment_id'] ) ? absint( $_POST['comment_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( $comment_id < 1 ) {
		wp_send_json_error( array( 'message' => __( 'Invalid review.', 'noyona-childtheme' ) ), 400 );
	}

	$comment = get_comment( $comment_id );
	if ( ! $comment || ! $comment instanceof WP_Comment || 1 !== (int) $comment->comment_approved || 'review' !== (string) $comment->comment_type ) {
		wp_send_json_error( array( 'message' => __( 'Review not available.', 'noyona-childtheme' ) ), 404 );
	}

	$count = (int) get_comment_meta( $comment_id, 'noyona_review_helpful_count', true );
	if ( $count < 0 ) {
		$count = 0;
	}

	$voters = get_comment_meta( $comment_id, 'noyona_review_helpful_voters', true );
	if ( ! is_array( $voters ) ) {
		$voters = array();
	}

	$voter_key = noyona_pdp_get_review_voter_key();

	if ( in_array( $voter_key, $voters, true ) ) {
		$voters = array_values(
			array_filter(
				$voters,
				static function ( $stored_key ) use ( $voter_key ) {
					return (string) $stored_key !== $voter_key;
				}
			)
		);
		$count = max( 0, $count - 1 );
		update_comment_meta( $comment_id, 'noyona_review_helpful_voters', $voters );
		update_comment_meta( $comment_id, 'noyona_review_helpful_count', $count );

		wp_send_json_success(
			array(
				'count' => $count,
				'voted' => false,
			)
		);
	}

	$voters[] = $voter_key;
	$count++;
	update_comment_meta( $comment_id, 'noyona_review_helpful_voters', array_values( array_unique( $voters ) ) );
	update_comment_meta( $comment_id, 'noyona_review_helpful_count', $count );

	wp_send_json_success(
		array(
			'count' => $count,
			'voted' => true,
		)
	);
}

add_action( 'wp_enqueue_scripts', 'noyona_pdp_enqueue_assets', 20 );
function noyona_pdp_enqueue_assets() {
	if ( is_admin() || ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	// Required by Woo's variation templates (wp.template / _.template runtime).
	// Some optimization stacks defer these unexpectedly, so enforce them here.
	$variation_runtime_handles = array(
		'underscore',
		'wp-util',
		'wc-add-to-cart-variation',
	);
	foreach ( $variation_runtime_handles as $handle ) {
		if ( wp_script_is( $handle, 'registered' ) && ! wp_script_is( $handle, 'enqueued' ) ) {
			wp_enqueue_script( $handle );
		}
	}

	// Belt-and-suspenders: explicitly enqueue the WC gallery scripts. They are
	// registered by WC_Frontend_Scripts on every front-end load and *should*
	// auto-enqueue on PDPs, but block-theme detection plus production perf
	// plugins can race that. Calling enqueue is idempotent — only the
	// `is_registered && ! is_enqueued` branch ships extra work.
	$gallery_handles = array(
		'flexslider',
		'photoswipe',
		'photoswipe-ui-default',
		'zoom',
		'wc-single-product',
		'wc-add-to-cart-variation',
	);
	foreach ( $gallery_handles as $handle ) {
		if ( wp_script_is( $handle, 'registered' ) && ! wp_script_is( $handle, 'enqueued' ) ) {
			wp_enqueue_script( $handle );
		}
	}
	// PhotoSwipe stylesheet (lightbox) is registered alongside the JS.
	if ( wp_style_is( 'photoswipe-default-skin', 'registered' ) && ! wp_style_is( 'photoswipe-default-skin', 'enqueued' ) ) {
		wp_enqueue_style( 'photoswipe-default-skin' );
	}

	$theme_ver  = wp_get_theme()->get( 'Version' );
	$style_path = get_stylesheet_directory() . '/assets/css/single-product.css';
	$script_path = get_stylesheet_directory() . '/assets/js/single-product.js';
	$style_ver = file_exists( $style_path ) ? (string) filemtime( $style_path ) : $theme_ver;
	$script_ver = file_exists( $script_path ) ? (string) filemtime( $script_path ) : $theme_ver;

	wp_enqueue_style(
		'noyona-single-product',
		get_stylesheet_directory_uri() . '/assets/css/single-product.css',
		array( 'woocom-ct-style' ),
		$style_ver
	);

	// in_footer + strategy=defer is required: this script declares
	// wc-add-to-cart-variation as a dep, which WC registers with
	// strategy=defer. If we leave this script as blocking, WP 6.3+
	// propagates the blocking strategy upward and downgrades
	// wc-add-to-cart-variation -> woocommerce -> wc-cart-fragments, leaving
	// woocommerce.min.js without a real `defer` attribute. It then executes
	// before deferred jQuery and throws `ReferenceError: jQuery is not
	// defined`. single-product.js is IIFE-wrapped and all its jQuery
	// touches are inside event handlers behind typeof guards, so deferring
	// it is safe.
	wp_enqueue_script(
		'noyona-single-product',
		get_stylesheet_directory_uri() . '/assets/js/single-product.js',
		array( 'jquery', 'wp-util', 'underscore', 'wc-add-to-cart-variation' ),
		$script_ver,
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);

	wp_localize_script(
		'noyona-single-product',
		'noyonaPdp',
		array(
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'checkoutUrl' => function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : '',
			'wishlist'    => array(
				'nonce'    => wp_create_nonce( 'noyona_product_wishlist' ),
				'loginUrl' => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' ),
			),
			'i18n'        => array(
				'selectOptions'         => __( 'Please select all product options before continuing.', 'viteseo-noyona-childtheme' ),
				'buyNow'                => __( 'Buy now', 'viteseo-noyona-childtheme' ),
				'addToCart'             => __( 'Add to cart', 'viteseo-noyona-childtheme' ),
				'inStock'               => __( 'In stock', 'viteseo-noyona-childtheme' ),
				'inStockLeft'           => __( 'In stock (%d left)', 'viteseo-noyona-childtheme' ),
				'outOfStock'            => __( 'Out of stock', 'viteseo-noyona-childtheme' ),
				'outOfStockLeft'        => __( 'Out of stock (%d left)', 'viteseo-noyona-childtheme' ),
				'outOfStockCartError'   => __( 'This product is out of stock.', 'viteseo-noyona-childtheme' ),
				'cartError'             => __( 'This product cannot be added to cart right now.', 'viteseo-noyona-childtheme' ),
				/* translators: %d: available stock quantity. */
				'maxInCart'             => __( 'You already have all available stock (%d) of this item in your cart.', 'viteseo-noyona-childtheme' ),
				/* translators: %1$d: available stock quantity; %2$d: quantity already in cart. */
				'notEnoughStock'        => __( 'Only %1$d left in stock, and you already have %2$d in your cart.', 'viteseo-noyona-childtheme' ),
				'selectOptionsAvailability' => __( 'Select options to see availability', 'viteseo-noyona-childtheme' ),
				'selectShade'           => __( 'Select shade', 'viteseo-noyona-childtheme' ),
				'wishlistAdd'           => __( 'Add to wishlist', 'viteseo-noyona-childtheme' ),
				'wishlistRemove'        => __( 'Remove from wishlist', 'viteseo-noyona-childtheme' ),
				'wishlistSaved'         => __( 'Saved to your wishlist.', 'viteseo-noyona-childtheme' ),
				'wishlistRemoved'       => __( 'Removed from your wishlist.', 'viteseo-noyona-childtheme' ),
				'wishlistSelectOptions' => __( 'Please select a shade before saving this product.', 'viteseo-noyona-childtheme' ),
				'wishlistLoginTitle'    => __( 'Log in to save your wishlist', 'viteseo-noyona-childtheme' ),
				'wishlistLoginCopy'     => __( 'Please log in to save products and view them from My Account.', 'viteseo-noyona-childtheme' ),
				'wishlistError'         => __( 'Wishlist could not be updated. Please try again.', 'viteseo-noyona-childtheme' ),
			),
		)
	);
}

add_filter( 'woocommerce_available_variation', 'noyona_pdp_add_variation_stock_data', 10, 3 );
/**
 * Expose the actual managed stock quantity to the PDP variation UI.
 *
 * @param array                $variation_data Variation payload.
 * @param WC_Product_Variable  $product        Parent product.
 * @param WC_Product_Variation $variation      Variation product.
 * @return array
 */
function noyona_pdp_add_variation_stock_data( $variation_data, $product, $variation ) {
	unset( $product );

	if ( ! $variation instanceof WC_Product_Variation ) {
		return $variation_data;
	}

	$stock_quantity = $variation->get_stock_quantity();

	$variation_data['noyona_stock_quantity'] = null !== $stock_quantity
		? max( 0, (int) $stock_quantity )
		: null;

	return $variation_data;
}

function noyona_pdp_get_wishlist_button_html( $product ) {
	if ( ! $product instanceof WC_Product ) {
		return '';
	}

	$product_id     = absint( $product->get_id() );
	$user_id        = get_current_user_id();
	$saved_keys     = array();
	$is_saved       = false;
	$active_label   = __( 'Remove from wishlist', 'viteseo-noyona-childtheme' );
	$inactive_label = __( 'Add to wishlist', 'viteseo-noyona-childtheme' );

	if ( $user_id > 0 && function_exists( 'noyona_get_product_wishlist_items' ) ) {
		foreach ( noyona_get_product_wishlist_items( $user_id ) as $item ) {
			if ( absint( $item['product_id'] ) !== $product_id ) {
				continue;
			}

			$item_key     = function_exists( 'noyona_get_product_wishlist_item_key' )
				? noyona_get_product_wishlist_item_key( $item['product_id'], $item['variation_id'] )
				: absint( $item['product_id'] ) . ':' . absint( $item['variation_id'] );
			$saved_keys[] = $item_key;
			if ( 0 === absint( $item['variation_id'] ) ) {
				$is_saved = true;
			}
		}
	}

	$button_label = $is_saved ? $active_label : $inactive_label;
	$classes      = 'noyona-pdp-wishlist-button';
	if ( $is_saved ) {
		$classes .= ' is-active';
	}

	ob_start();
	?>
	<div class="noyona-pdp-wishlist" data-noyona-pdp-wishlist-wrap>
		<button
			class="<?php echo esc_attr( $classes ); ?>"
			type="button"
			aria-label="<?php echo esc_attr( $button_label ); ?>"
			aria-pressed="<?php echo $is_saved ? 'true' : 'false'; ?>"
			data-noyona-pdp-wishlist
			data-product-id="<?php echo esc_attr( (string) $product_id ); ?>"
			data-product-type="<?php echo esc_attr( $product->get_type() ); ?>"
			data-nonce="<?php echo esc_attr( wp_create_nonce( 'noyona_product_wishlist' ) ); ?>"
			data-saved-keys="<?php echo esc_attr( wp_json_encode( array_values( array_unique( $saved_keys ) ) ) ); ?>"
			data-label-add="<?php echo esc_attr( $inactive_label ); ?>"
			data-label-remove="<?php echo esc_attr( $active_label ); ?>"
		>
			<i class="fa-<?php echo $is_saved ? 'solid' : 'regular'; ?> fa-heart" aria-hidden="true"></i>
			<span class="screen-reader-text"><?php echo esc_html( $button_label ); ?></span>
		</button>
	</div>
	<?php
	return trim( ob_get_clean() );
}

add_filter( 'render_block_core/post-title', 'noyona_pdp_render_wishlist_button_before_title', 10, 2 );
function noyona_pdp_render_wishlist_button_before_title( $block_content, $block ) {
	static $rendered = false;

	if ( $rendered || is_admin() || ! function_exists( 'is_product' ) || ! is_product() || ! function_exists( 'wc_get_product' ) ) {
		return $block_content;
	}

	$product = wc_get_product( get_the_ID() );
	if ( ! $product instanceof WC_Product ) {
		return $block_content;
	}

	$rendered = true;
	return noyona_pdp_get_wishlist_button_html( $product ) . $block_content;
}

/**
 * PDP gallery badge: hide the default WooCommerce "Sale!" flash and, when the
 * product is marked Featured, render a single "BEST SELLER" pill as a
 * product-level overlay on the gallery container.
 *
 * The badge represents the product, not a specific gallery image, so it must
 * not live inside the first FlexSlider slide. Keeping it as a child of the
 * WooCommerce gallery container lets it stay visible as thumbnails, variation
 * images, zoom/lightbox, or the theme's fallback gallery swap the active image.
 */
add_action( 'wp', 'noyona_pdp_hide_sale_flash_badge' );
function noyona_pdp_hide_sale_flash_badge() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}
	remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );
}

function noyona_pdp_should_show_best_seller_badge() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return false;
	}

	global $product;
	if ( ! $product instanceof WC_Product ) {
		return false;
	}

	return $product->is_featured();
}

function noyona_pdp_get_best_seller_badge_html() {
	return '<span class="noyona-pdp-best-seller-badge">' . esc_html__( 'BEST SELLER', 'viteseo-noyona-childtheme' ) . '</span>';
}

add_filter( 'render_block_woocommerce/product-image-gallery', 'noyona_pdp_render_best_seller_gallery_badge', 10, 2 );
function noyona_pdp_render_best_seller_gallery_badge( $block_content, $block ) {
	if ( ! noyona_pdp_should_show_best_seller_badge() || strpos( $block_content, 'noyona-pdp-best-seller-badge' ) !== false ) {
		return $block_content;
	}

	$replacements = 0;
	$new_html     = preg_replace(
		'/(<div\b[^>]*class="[^"]*\bwoocommerce-product-gallery\b[^"]*"[^>]*>)/i',
		'$1' . noyona_pdp_get_best_seller_badge_html(),
		$block_content,
		1,
		$replacements
	);

	if ( $replacements > 0 && is_string( $new_html ) ) {
		return $new_html;
	}

	return $block_content;
}

/**
 * Mobile/tablet sticky buy bar + slide-up buy sheet shell.
 *
 * Renders a fixed bottom bar (Add to cart / Buy now) and an EMPTY slide-up
 * sheet on product pages. The sheet body is just a slot — single-product.js
 * relocates the real `form.cart` node into it on viewports <= 781px
 * (Strategy A: no form duplication, no cloned variation/add-to-cart logic) and
 * restores it to its original position on wider viewports.
 *
 * All visibility is CSS-gated to <= 781px, so desktop renders this markup
 * hidden and the desktop PDP is unchanged.
 */
add_action( 'wp_footer', 'noyona_pdp_render_buy_bar_and_sheet', 20 );
function noyona_pdp_render_buy_bar_and_sheet() {
	if ( is_admin() || ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	$product = function_exists( 'wc_get_product' ) ? wc_get_product( get_queried_object_id() ) : null;
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$add_label   = esc_html__( 'Add to cart', 'viteseo-noyona-childtheme' );
	$buy_label   = esc_html__( 'Buy now', 'viteseo-noyona-childtheme' );
	$close_label = esc_attr__( 'Close', 'viteseo-noyona-childtheme' );
	$sheet_label = esc_attr__( 'Add to cart options', 'viteseo-noyona-childtheme' );
	$region_label = esc_attr__( 'Purchase options', 'viteseo-noyona-childtheme' );

	$thumb_html = $product->get_image(
		'woocommerce_thumbnail',
		array(
			'class'   => 'noyona-pdp-buysheet__thumb-img',
			'loading' => 'lazy',
		)
	);
	$title = esc_html( $product->get_name() );
	?>
	<div class="noyona-pdp-buybar" data-noyona-buybar role="region" aria-label="<?php echo $region_label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
		<button type="button" class="noyona-pdp-buybar__btn noyona-pdp-buybar__btn--cart" data-noyona-buybar-add>
			<?php echo $add_label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</button>
		<button type="button" class="noyona-pdp-buybar__btn noyona-pdp-buybar__btn--buy" data-noyona-buybar-buy>
			<?php echo $buy_label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</button>
	</div>

	<div class="noyona-pdp-buysheet" data-noyona-buysheet hidden>
		<div class="noyona-pdp-buysheet__backdrop" data-noyona-buysheet-backdrop></div>
		<div class="noyona-pdp-buysheet__panel" role="dialog" aria-modal="true" aria-label="<?php echo $sheet_label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
			<button type="button" class="noyona-pdp-buysheet__close" data-noyona-buysheet-close aria-label="<?php echo $close_label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
				<i class="fa-solid fa-xmark" aria-hidden="true"></i>
			</button>
			<div class="noyona-pdp-buysheet__header">
				<div class="noyona-pdp-buysheet__thumb"><?php echo $thumb_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
				<div class="noyona-pdp-buysheet__meta">
					<div class="noyona-pdp-buysheet__title"><?php echo $title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
					<div class="noyona-pdp-buysheet__price" data-noyona-buysheet-price></div>
					<div class="noyona-pdp-buysheet__variant" data-noyona-buysheet-variant></div>
					<div class="noyona-pdp-buysheet__stock noyona-pdp-stock-shipping__stock" data-noyona-buysheet-stock aria-live="polite" hidden></div>
				</div>
			</div>
			<div class="noyona-pdp-buysheet__body" data-noyona-buysheet-form-slot></div>
		</div>
	</div>
	<?php
}
