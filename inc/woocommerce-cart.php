<?php
/**
 * Cart page logic for Noyona.
 *
 * - Enqueues cart-only stylesheet
 * - Removes cross-sells from the cart page
 * - Auto-submits the cart form on quantity change (small inline script)
 *
 * Shipping costs are computed by Noyona_Shipping (J&T zone × weight matrix) in
 * inc/woocommerce-shipping.php. No filter here overrides them.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cart-only stylesheet.
 */
add_action( 'wp_enqueue_scripts', 'noyona_cart_enqueue_assets', 20 );
function noyona_cart_enqueue_assets() {
	if ( ! function_exists( 'is_cart' ) || ! is_cart() ) {
		return;
	}

	$cart_css_path = get_stylesheet_directory() . '/assets/css/noyona-cart.css';

	wp_enqueue_style(
		'noyona-cart',
		get_stylesheet_directory_uri() . '/assets/css/noyona-cart.css',
		array( 'woocom-ct-style', 'woocom-ct-header' ),
		file_exists( $cart_css_path ) ? (string) filemtime( $cart_css_path ) : wp_get_theme()->get( 'Version' )
	);
}

/**
 * Remove cross-sells from the cart collaterals.
 * Keeps the summary area clean.
 */
add_action( 'wp', 'noyona_remove_cart_cross_sells' );
function noyona_remove_cart_cross_sells() {
	if ( function_exists( 'is_cart' ) && is_cart() ) {
		remove_action( 'woocommerce_cart_collaterals', 'woocommerce_cross_sell_display' );
	}
}

/**
 * Change the "Proceed to checkout" button text.
 */
add_filter( 'woocommerce_order_button_text', 'noyona_checkout_button_text' );
function noyona_checkout_button_text( $text ) {
	return $text;
}

/**
 * Replace the default proceed-to-checkout button with a styled link.
 * We remove the default button and add our own via the same hook.
 */
remove_action( 'woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20 );
add_action( 'woocommerce_proceed_to_checkout', 'noyona_proceed_to_checkout_button', 20 );
function noyona_proceed_to_checkout_button() {
	?>
	<a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="noyona-checkout-btn">
		Continue to Shipping
	</a>
	<?php
}

/**
 * Auto-submit cart form on quantity change.
 * Small inline script — no external JS file needed.
 */
add_action( 'wp_footer', 'noyona_cart_auto_update_script', 40 );
function noyona_cart_auto_update_script() {
	if ( ! function_exists( 'is_cart' ) || ! is_cart() ) {
		return;
	}
	?>
	<script>
	(function () {
		var form = document.querySelector('.woocommerce-cart-form');
		if (!form) return;

		var timer;
		form.addEventListener('change', function (e) {
			if (e.target.matches('input.qty')) {
				clearTimeout(timer);
				timer = setTimeout(function () {
					var btn = form.querySelector('[name="update_cart"]');
					if (btn) {
						btn.disabled = false;
						btn.removeAttribute('aria-disabled');
						btn.click();
					}
				}, 600);
			}
		});
	})();
	</script>
	<?php
}

/* ----- Force mini-cart 'Checkout' link to /cart/ ----- */
add_filter( 'render_block', 'noyona_force_minicart_checkout_link', 30, 2 );
function noyona_force_minicart_checkout_link( $block_content, $block ) {
    if ( is_admin() || empty( $block['blockName'] ) ) {
        return $block_content;
    }

    if ( 'woocommerce/mini-cart-checkout-button-block' !== $block['blockName'] ) {
        return $block_content;
    }

    if ( false === strpos( (string) $block_content, 'wp-block-woocommerce-mini-cart-checkout-button-block' ) ) {
        return $block_content;
    }

    $checkout_url = esc_url( home_url( '/cart/' ) );
    $pattern      = '#<a\b[^>]*class=(["\'])[^"\']*\bwp-block-woocommerce-mini-cart-checkout-button-block\b[^"\']*\1[^>]*>#i';

    return preg_replace_callback(
        $pattern,
        function ( $matches ) use ( $checkout_url ) {
            $anchor = $matches[0];

            // Add our own data attribute so JS can always find this element
            // regardless of WC class name changes.
            if ( false === strpos( $anchor, 'data-noyona-checkout-btn' ) ) {
                $anchor = preg_replace( '#<a\b#i', '<a data-noyona-checkout-btn="1"', $anchor, 1 );
            }

            if ( preg_match( '#\bhref=(["\']).*?\1#i', $anchor ) ) {
                return preg_replace( '#\bhref=(["\']).*?\1#i', 'href="' . $checkout_url . '"', $anchor, 1 );
            }

            return preg_replace( '#<a\b#i', '<a href="' . $checkout_url . '"', $anchor, 1 );
        },
        (string) $block_content,
        1
    );
}

