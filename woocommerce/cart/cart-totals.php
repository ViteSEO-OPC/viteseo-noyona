<?php
/**
 * Noyona — custom cart totals / summary section.
 *
 * Overrides: woocommerce/templates/cart/cart-totals.php
 * Always renders: Subtotal, Discount (if coupon), Shipping, Total.
 * Includes a coupon form so users can apply promo codes in the summary area.
 *
 * @see https://woocommerce.com/document/template-structure/
 */

defined( 'ABSPATH' ) || exit;

$cart       = WC()->cart;
$item_count = $cart->get_cart_contents_count();
$subtotal   = (float) $cart->get_subtotal();
$discount   = (float) $cart->get_discount_total();
$coupons    = $cart->get_applied_coupons();
$has_coupon = ! empty( $coupons ) && $discount > 0;

// Shipping — read directly from cart totals (driven by Noyona_Shipping J&T matrix in inc/woocommerce-shipping.php).
//
// `chosen_shipping_methods` can contain array(0 => '') even when no rate matched, so we
// gate display on the actual cost being > 0 instead. With J&T-only there is no genuine
// free-shipping path: a real rate is always non-zero, so 0.00 ⇔ no rate yet → "Calculated at checkout".
$needs_shipping   = WC()->cart->needs_shipping();
$shipping_cost    = $needs_shipping ? (float) $cart->get_shipping_total() : 0.0;
$has_real_rate    = $needs_shipping && $shipping_cost > 0;
$is_free_shipping = ! $needs_shipping;

// Total — only include shipping when a real rate is present.
$total = $subtotal - $discount + ( $has_real_rate ? $shipping_cost : 0.0 );
?>

<div class="noyona-cart-summary">
	<?php do_action( 'woocommerce_before_cart_totals' ); ?>

	<?php if ( wc_coupons_enabled() ) : ?>
		<div class="noyona-coupon-section">
			<div class="noyona-coupon-header">
				<i class="fa-solid fa-tag" aria-hidden="true"></i>
				<span>Have a promo code?</span>
			</div>

			<form class="noyona-coupon-form" method="post" action="<?php echo esc_url( wc_get_cart_url() ); ?>">
				<input type="text" name="coupon_code" id="noyona_coupon_code" class="noyona-coupon-input" placeholder="Enter promo code" value="" />
				<button type="submit" name="apply_coupon" class="noyona-coupon-apply" value="<?php esc_attr_e( 'Apply coupon', 'woocommerce' ); ?>">
					<?php esc_html_e( 'Apply', 'woocommerce' ); ?>
				</button>
				<?php do_action( 'woocommerce_cart_coupon' ); ?>
			</form>

			<?php if ( ! empty( $coupons ) ) : ?>
				<div class="noyona-applied-coupons">
					<?php foreach ( $coupons as $coupon_code ) : ?>
						<span class="noyona-coupon-chip">
							<?php echo esc_html( $coupon_code ); ?>
							<a href="<?php echo esc_url( add_query_arg( array( 'remove_coupon' => rawurlencode( $coupon_code ), '_wpnonce' => wp_create_nonce( 'woocommerce-cart' ) ), wc_get_cart_url() ) ); ?>" class="noyona-coupon-remove" aria-label="<?php echo esc_attr( sprintf( __( 'Remove coupon %s', 'woocommerce' ), $coupon_code ) ); ?>">&times;</a>
						</span>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<div class="noyona-summary-rows">
		<!-- Subtotal — always visible -->
		<div class="noyona-summary-row noyona-summary-row--subtotal">
			<span class="noyona-summary-row__label">
				<?php
				printf(
					/* translators: %d: number of items */
					_n( 'Subtotal (%d item)', 'Subtotal (%d items)', $item_count, 'woocommerce' ),
					$item_count
				);
				?>
			</span>
			<span class="noyona-summary-row__value"><?php echo wp_kses_post( wc_price( $subtotal ) ); ?></span>
		</div>

		<?php if ( $has_coupon ) : ?>
			<!-- Discount — only when a coupon is applied -->
			<div class="noyona-summary-row noyona-summary-row--discount">
				<span class="noyona-summary-row__label">Discount</span>
				<span class="noyona-summary-row__value noyona-summary-row__value--discount">
					&minus;<?php echo wp_kses_post( wc_price( $discount ) ); ?>
				</span>
			</div>
		<?php endif; ?>

		<!-- Shipping — always visible -->
		<div class="noyona-summary-row noyona-summary-row--shipping">
			<span class="noyona-summary-row__label">Shipping</span>
			<span class="noyona-summary-row__value <?php echo $is_free_shipping ? 'noyona-summary-row__value--free' : ''; ?>">
				<?php
				if ( ! $needs_shipping ) {
					esc_html_e( 'Free', 'woocommerce' );
				} elseif ( $has_real_rate ) {
					echo wp_kses_post( wc_price( $shipping_cost ) );
				} else {
					esc_html_e( 'Calculated at checkout', 'viteseo-noyona-childtheme' );
				}
				?>
			</span>
		</div>
	</div>

	<!-- Total — always visible -->
	<div class="noyona-summary-total">
		<span class="noyona-summary-total__label">Total</span>
		<span class="noyona-summary-total__value"><?php echo wp_kses_post( wc_price( $total ) ); ?></span>
	</div>

	<div class="noyona-cart-checkout">
		<?php do_action( 'woocommerce_proceed_to_checkout' ); ?>
	</div>

	<?php do_action( 'woocommerce_after_cart_totals' ); ?>
</div>
