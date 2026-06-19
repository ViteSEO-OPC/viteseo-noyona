<?php
/**
 * Shop/listing buy sheet — shared shell, form fragment AJAX, asset enqueue.
 *
 * PDP buy bar + Strategy A relocation remain in woocommerce-pdp.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the buy-sheet listing feature is enabled.
 *
 * @return bool
 */
function noyona_buy_sheet_listing_enabled() {
	return (bool) apply_filters( 'noyona_buy_sheet_listing_enabled', true );
}

/**
 * Shop archive, category, or product search grid routes.
 *
 * @return bool
 */
function noyona_is_buy_sheet_listing_route() {
	if ( is_admin() ) {
		return false;
	}

	if ( function_exists( 'noyona_is_shop_route_request' ) && noyona_is_shop_route_request() ) {
		return true;
	}

	if ( function_exists( 'noyona_is_product_search_request' ) && noyona_is_product_search_request() ) {
		return true;
	}

	return false;
}

/**
 * Build header meta for the buy-sheet shell.
 *
 * @param WC_Product $product Product instance.
 * @return array<string, mixed>
 */
function noyona_buy_sheet_build_header_meta( WC_Product $product ) {
	$select_availability = __( 'Select options to see availability', 'viteseo-noyona-childtheme' );

	return array(
		'productId'    => $product->get_id(),
		'title'        => $product->get_name(),
		'thumbHtml'    => $product->get_image(
			'woocommerce_thumbnail',
			array(
				'class'   => 'noyona-pdp-buysheet__thumb-img',
				'loading' => 'lazy',
			)
		),
		'priceHtml'    => $product->get_price_html(),
		'stockHtml'    => $product->is_type( 'variable' ) ? $select_availability : '',
		'stockClass'   => 'noyona-pdp-stock-shipping__stock',
		'stockInStock' => null,
		'stockCount'   => null,
		'productType'  => $product->get_type(),
	);
}

/**
 * Render variable add-to-cart markup (same path as PDP / Phase 0 spike).
 *
 * @param int $product_id Product ID.
 * @return string
 */
function noyona_buy_sheet_render_variable_form_html( $product_id ) {
	if ( ! function_exists( 'wc_get_product' ) || ! function_exists( 'woocommerce_template_single_add_to_cart' ) ) {
		return '';
	}

	$product_id = absint( $product_id );
	$product    = wc_get_product( $product_id );

	if ( ! $product || ! $product->is_type( 'variable' ) ) {
		return '';
	}

	global $post;
	$prev_post    = $post;
	$prev_product = isset( $GLOBALS['product'] ) ? $GLOBALS['product'] : null;

	$post = get_post( $product_id );
	if ( ! $post ) {
		return '';
	}

	setup_postdata( $post );
	$GLOBALS['product'] = $product;

	ob_start();
	woocommerce_template_single_add_to_cart();
	$html = ob_get_clean();

	wp_reset_postdata();
	$GLOBALS['product'] = $prev_product;
	$post               = $prev_post;

	return is_string( $html ) ? $html : '';
}

/**
 * Shared buy-sheet shell markup.
 *
 * @param array<string, mixed> $args Shell arguments.
 * @return void
 */
function noyona_render_buy_sheet_shell( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'product'  => null,
			'context'  => 'listing',
			'show_bar' => false,
		)
	);

	$product = $args['product'] instanceof WC_Product ? $args['product'] : null;
	$context = sanitize_key( (string) $args['context'] );
	$context = in_array( $context, array( 'pdp', 'listing' ), true ) ? $context : 'listing';

	$close_label  = esc_attr__( 'Close', 'viteseo-noyona-childtheme' );
	$sheet_label  = esc_attr__( 'Add to cart options', 'viteseo-noyona-childtheme' );
	$title      = '';
	$thumb_html = '';
	$price_html = '';

	if ( $product ) {
		$title      = esc_html( $product->get_name() );
		$thumb_html = $product->get_image(
			'woocommerce_thumbnail',
			array(
				'class'   => 'noyona-pdp-buysheet__thumb-img',
				'loading' => 'lazy',
			)
		);
		// PDP header price is synced from the main price node by JS — keep empty.
		if ( 'listing' === $context ) {
			$price_html = '';
		}
	}

	$sheet_attrs = array(
		'class'                         => 'noyona-pdp-buysheet',
		'data-noyona-buysheet'          => '',
		'data-noyona-buysheet-context'  => $context,
		'hidden'                        => 'hidden',
	);
	?>
	<div <?php echo noyona_buy_sheet_render_html_attributes( $sheet_attrs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<div class="noyona-pdp-buysheet__backdrop" data-noyona-buysheet-backdrop></div>
		<div class="noyona-pdp-buysheet__panel" role="dialog" aria-modal="true" aria-label="<?php echo $sheet_label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
			<button type="button" class="noyona-pdp-buysheet__close" data-noyona-buysheet-close aria-label="<?php echo $close_label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
				<i class="fa-solid fa-xmark" aria-hidden="true"></i>
			</button>
			<div class="noyona-pdp-buysheet__header">
				<div class="noyona-pdp-buysheet__thumb" data-noyona-buysheet-thumb><?php echo $thumb_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
				<div class="noyona-pdp-buysheet__meta">
					<div class="noyona-pdp-buysheet__title" data-noyona-buysheet-title><?php echo $title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
					<div class="noyona-pdp-buysheet__price" data-noyona-buysheet-price><?php echo $price_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
					<div class="noyona-pdp-buysheet__variant" data-noyona-buysheet-variant></div>
					<div class="noyona-pdp-buysheet__stock noyona-pdp-stock-shipping__stock" data-noyona-buysheet-stock aria-live="polite" hidden></div>
				</div>
			</div>
			<div class="noyona-pdp-buysheet__body" data-noyona-buysheet-form-slot>
				<div class="noyona-pdp-buysheet__loading" data-noyona-buysheet-loading hidden aria-live="polite">
					<?php esc_html_e( 'Loading options…', 'viteseo-noyona-childtheme' ); ?>
				</div>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Render HTML attributes from an associative array.
 *
 * @param array<string, string> $attributes Attributes.
 * @return string
 */
function noyona_buy_sheet_render_html_attributes( $attributes ) {
	$parts = array();

	foreach ( $attributes as $name => $value ) {
		if ( '' === $value && 'class' !== $name ) {
			$parts[] = esc_attr( $name );
			continue;
		}
		$parts[] = sprintf( '%1$s="%2$s"', esc_attr( $name ), esc_attr( $value ) );
	}

	return implode( ' ', $parts );
}

/**
 * Render listing buy-sheet shell in the footer.
 */
function noyona_buy_sheet_render_listing_shell() {
	if ( ! noyona_buy_sheet_listing_enabled() || ! noyona_is_buy_sheet_listing_route() ) {
		return;
	}

	noyona_render_buy_sheet_shell(
		array(
			'product'  => null,
			'context'  => 'listing',
			'show_bar' => false,
		)
	);
}
add_action( 'wp_footer', 'noyona_buy_sheet_render_listing_shell', 20 );

/**
 * Production AJAX: variable form fragment for listing buy sheet.
 */
function noyona_buy_sheet_variable_form_ajax() {
	if ( ! noyona_buy_sheet_listing_enabled() ) {
		wp_send_json_error(
			array(
				'code'    => 'feature_disabled',
				'message' => __( 'This feature is not available right now.', 'viteseo-noyona-childtheme' ),
			),
			503
		);
	}

	if ( ! function_exists( 'wc_get_product' ) ) {
		wp_send_json_error(
			array(
				'code'    => 'woocommerce_unavailable',
				'message' => __( 'The store is unavailable right now. Please try again.', 'viteseo-noyona-childtheme' ),
			),
			503
		);
	}

	check_ajax_referer( 'noyona_buy_sheet_variable_form', 'nonce' );

	$product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
	if ( $product_id < 1 ) {
		wp_send_json_error(
			array(
				'code'    => 'invalid_product_id',
				'message' => __( 'Product not found.', 'viteseo-noyona-childtheme' ),
			),
			400
		);
	}

	$product = wc_get_product( $product_id );
	if ( ! $product instanceof WC_Product ) {
		wp_send_json_error(
			array(
				'code'    => 'product_not_found',
				'message' => __( 'Product not found.', 'viteseo-noyona-childtheme' ),
			),
			404
		);
	}

	if ( ! $product->is_type( 'variable' ) ) {
		wp_send_json_error(
			array(
				'code'    => 'not_variable',
				'message' => __( 'This product requires options on the product page.', 'viteseo-noyona-childtheme' ),
			),
			422
		);
	}

	if ( ! $product->is_purchasable() ) {
		wp_send_json_error(
			array(
				'code'    => 'not_purchasable',
				'message' => __( 'This product cannot be purchased right now.', 'viteseo-noyona-childtheme' ),
			),
			422
		);
	}

	$form_html = noyona_buy_sheet_render_variable_form_html( $product_id );
	if ( '' === $form_html ) {
		wp_send_json_error(
			array(
				'code'    => 'form_render_failed',
				'message' => __( 'Unable to load product options. Please try again.', 'viteseo-noyona-childtheme' ),
			),
			500
		);
	}

	wp_send_json_success(
		array(
			'productId' => $product_id,
			'formHtml'  => $form_html,
			'header'    => noyona_buy_sheet_build_header_meta( $product ),
		)
	);
}
add_action( 'wp_ajax_noyona_buy_sheet_variable_form', 'noyona_buy_sheet_variable_form_ajax' );
add_action( 'wp_ajax_nopriv_noyona_buy_sheet_variable_form', 'noyona_buy_sheet_variable_form_ajax' );

/**
 * Shared single-product asset enqueue for PDP and listing buy sheet.
 *
 * @param array<string, mixed> $args Enqueue arguments.
 * @return void
 */
function noyona_buy_sheet_enqueue_single_product_assets( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'include_gallery' => false,
			'listing_context' => false,
		)
	);

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

	if ( ! empty( $args['include_gallery'] ) ) {
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
		if ( wp_style_is( 'photoswipe-default-skin', 'registered' ) && ! wp_style_is( 'photoswipe-default-skin', 'enqueued' ) ) {
			wp_enqueue_style( 'photoswipe-default-skin' );
		}
	}

	$theme_ver   = wp_get_theme()->get( 'Version' );
	$style_path  = get_stylesheet_directory() . '/assets/css/single-product.css';
	$script_path = get_stylesheet_directory() . '/assets/js/single-product.js';
	$style_ver   = file_exists( $style_path ) ? (string) filemtime( $style_path ) : $theme_ver;
	$script_ver  = file_exists( $script_path ) ? (string) filemtime( $script_path ) : $theme_ver;

	wp_enqueue_style(
		'noyona-single-product',
		get_stylesheet_directory_uri() . '/assets/css/single-product.css',
		array( 'woocom-ct-style' ),
		$style_ver
	);

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

	$localize = array(
		'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
		'checkoutUrl' => function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : '',
		'wishlist'    => array(
			'nonce'    => wp_create_nonce( 'noyona_product_wishlist' ),
			'loginUrl' => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' ),
		),
		'i18n'        => noyona_buy_sheet_get_i18n_strings(),
	);

	if ( ! empty( $args['listing_context'] ) && noyona_buy_sheet_listing_enabled() ) {
		$localize['buySheet'] = array(
			'enabled' => true,
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'action'  => 'noyona_buy_sheet_variable_form',
			'nonce'   => wp_create_nonce( 'noyona_buy_sheet_variable_form' ),
		);
	}

	wp_localize_script( 'noyona-single-product', 'noyonaPdp', $localize );
}

/**
 * i18n strings shared by PDP and listing buy sheet.
 *
 * @return array<string, string>
 */
function noyona_buy_sheet_get_i18n_strings() {
	return array(
		'selectOptions'             => __( 'Please select all product options before continuing.', 'viteseo-noyona-childtheme' ),
		'buyNow'                    => __( 'Buy now', 'viteseo-noyona-childtheme' ),
		'addToCart'                 => __( 'Add to cart', 'viteseo-noyona-childtheme' ),
		'inStock'                   => __( 'In stock', 'viteseo-noyona-childtheme' ),
		'inStockLeft'               => __( 'In stock (%d left)', 'viteseo-noyona-childtheme' ),
		'outOfStock'                => __( 'Out of stock', 'viteseo-noyona-childtheme' ),
		'outOfStockLeft'            => __( 'Out of stock (%d left)', 'viteseo-noyona-childtheme' ),
		'outOfStockCartError'       => __( 'This product is out of stock.', 'viteseo-noyona-childtheme' ),
		'cartError'                 => __( 'This product cannot be added to cart right now.', 'viteseo-noyona-childtheme' ),
		'maxInCart'                 => __( 'You already have all available stock (%d) of this item in your cart.', 'viteseo-noyona-childtheme' ),
		'notEnoughStock'            => __( 'Only %1$d left in stock, and you already have %2$d in your cart.', 'viteseo-noyona-childtheme' ),
		'selectOptionsAvailability' => __( 'Select options to see availability', 'viteseo-noyona-childtheme' ),
		'selectShade'               => __( 'Select shade', 'viteseo-noyona-childtheme' ),
		'wishlistAdd'               => __( 'Add to wishlist', 'viteseo-noyona-childtheme' ),
		'wishlistRemove'            => __( 'Remove from wishlist', 'viteseo-noyona-childtheme' ),
		'wishlistSaved'             => __( 'Saved to your wishlist.', 'viteseo-noyona-childtheme' ),
		'wishlistRemoved'           => __( 'Removed from your wishlist.', 'viteseo-noyona-childtheme' ),
		'wishlistSelectOptions'     => __( 'Please select a shade before saving this product.', 'viteseo-noyona-childtheme' ),
		'wishlistLoginTitle'        => __( 'Log in to save your wishlist', 'viteseo-noyona-childtheme' ),
		'wishlistLoginCopy'         => __( 'Please log in to save products and view them from My Account.', 'viteseo-noyona-childtheme' ),
		'wishlistError'             => __( 'Wishlist could not be updated. Please try again.', 'viteseo-noyona-childtheme' ),
		'buySheetLoadError'         => __( 'Unable to load product options. Please try again.', 'viteseo-noyona-childtheme' ),
		'buySheetRetry'             => __( 'Please refresh the page and try again.', 'viteseo-noyona-childtheme' ),
	);
}

/**
 * Enqueue buy-sheet assets on listing routes.
 */
function noyona_buy_sheet_enqueue_listing_assets() {
	if ( is_admin() || ! noyona_buy_sheet_listing_enabled() || ! noyona_is_buy_sheet_listing_route() ) {
		return;
	}

	noyona_buy_sheet_enqueue_single_product_assets(
		array(
			'include_gallery' => false,
			'listing_context' => true,
		)
	);
}
add_action( 'wp_enqueue_scripts', 'noyona_buy_sheet_enqueue_listing_assets', 20 );
