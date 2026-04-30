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
			'description' => __( 'Optional. Example: 150+ sold in the last 2 days. Shown above the product title.', 'viteseo-noyona-childtheme' ),
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
	$row .= $rating_markup;
	$row .= '<span class="wc-block-components-product-rating__average">' . esc_html( $average_label ) . '</span>';
	$row .= $link;
	$row .= '</div>';

	return $row;
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

	wp_enqueue_script(
		'noyona-single-product',
		get_stylesheet_directory_uri() . '/assets/js/single-product.js',
		array( 'jquery', 'wc-single-product' ),
		$script_ver,
		true
	);

	wp_localize_script(
		'noyona-single-product',
		'noyonaPdp',
		array(
			'checkoutUrl' => function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : '',
			'i18n'        => array(
				'selectOptions' => __( 'Please select all product options before continuing.', 'viteseo-noyona-childtheme' ),
				'buyNow'        => __( 'Buy now', 'viteseo-noyona-childtheme' ),
				'selectShade'   => __( 'Select shade', 'viteseo-noyona-childtheme' ),
			),
		)
	);
}
