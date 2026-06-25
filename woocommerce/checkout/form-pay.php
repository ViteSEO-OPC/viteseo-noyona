<?php
/**
 * Pay for order form — Noyona branded override.
 *
 * Overrides woocommerce/templates/checkout/form-pay.php so the "pay for an
 * existing order" page (My Account → Orders → Pay, i.e. the order-pay endpoint)
 * matches the custom checkout styling instead of WooCommerce's bare table +
 * default gateway list. Reuses the existing `.noyona-checkout-card`,
 * `.noyona-order-item*`, `.noyona-order-totals*`, `.noyona-checkout-form`
 * (payment method list) and `.noyona-checkout-btn` styles already loaded on the
 * checkout UI context.
 *
 * The customer's original payment method is pre-selected upstream by
 * noyona_order_pay_preselect_gateway() (inc/woocommerce-checkout.php), so this
 * page is effectively a one-click "Pay now".
 *
 * Based on WooCommerce core template @version 8.2.0 — keep the core hooks,
 * hidden fields and nonce in sync when WooCommerce bumps the template version.
 *
 * @package child-noyona
 */

defined( 'ABSPATH' ) || exit;

$totals = $order->get_order_item_totals(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
?>
<div class="noyona-order-pay">
	<form id="order_review" method="post" class="noyona-checkout-form noyona-order-pay__inner">

		<div class="noyona-checkout-card noyona-checkout-card--summary noyona-order-pay__summary">
			<h2 class="noyona-checkout-card__title">
				<i class="fa-solid fa-receipt" aria-hidden="true"></i>
				<?php esc_html_e( 'Order Summary', 'noyona' ); ?>
			</h2>

			<p class="noyona-order-pay__meta">
				<?php
				printf(
					/* translators: %s: order number. */
					esc_html__( 'Order #%s — awaiting payment', 'noyona' ),
					esc_html( $order->get_order_number() )
				);
				?>
			</p>

			<div class="noyona-order-items">
				<?php
				foreach ( $order->get_items() as $item_id => $item ) {
					if ( ! apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {
						continue;
					}
					$_product  = $item->get_product();
					$thumbnail = $_product ? $_product->get_image( 'woocommerce_gallery_thumbnail' ) : '';
					?>
					<div class="noyona-order-item">
						<div class="noyona-order-item__image"><?php echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
						<div class="noyona-order-item__details">
							<span class="noyona-order-item__name">
								<?php echo wp_kses_post( apply_filters( 'woocommerce_order_item_name', $item->get_name(), $item, false ) ); ?>
							</span>
							<?php
							do_action( 'woocommerce_order_item_meta_start', $item_id, $item, $order, false );
							wc_display_item_meta( $item );
							do_action( 'woocommerce_order_item_meta_end', $item_id, $item, $order, false );
							?>
							<span class="noyona-order-item__qty">&times; <?php echo esc_html( $item->get_quantity() ); ?></span>
						</div>
						<div class="noyona-order-item__total">
							<?php echo wp_kses_post( $order->get_formatted_line_subtotal( $item ) ); ?>
						</div>
					</div>
					<?php
				}
				?>
			</div>

			<?php if ( $totals ) : ?>
				<div class="noyona-order-totals noyona-order-totals--summary">
					<?php foreach ( $totals as $key => $total ) : ?>
						<div class="noyona-order-totals__row <?php echo ( 'order_total' === $key ) ? 'noyona-order-totals__row--total' : ''; ?>">
							<span class="noyona-order-totals__label"><?php echo wp_kses_post( $total['label'] ); ?></span>
							<span class="noyona-order-totals__value"><?php echo wp_kses_post( $total['value'] ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<?php
		/**
		 * Triggered from within the checkout/form-pay.php template, immediately before the payment section.
		 *
		 * @since 8.2.0
		 */
		do_action( 'woocommerce_pay_order_before_payment' );
		?>

		<div class="noyona-checkout-card noyona-checkout-card--payment noyona-order-pay__payment">
			<h2 class="noyona-checkout-card__title">
				<i class="fa-solid fa-credit-card" aria-hidden="true"></i>
				<?php esc_html_e( 'Payment Method', 'noyona' ); ?>
			</h2>

			<div id="payment">
				<?php if ( $order->needs_payment() ) : ?>
					<ul class="wc_payment_methods payment_methods methods">
						<?php
						if ( ! empty( $available_gateways ) ) {
							foreach ( $available_gateways as $gateway ) {
								wc_get_template( 'checkout/payment-method.php', array( 'gateway' => $gateway ) );
							}
						} else {
							echo '<li>';
							wc_print_notice( apply_filters( 'woocommerce_no_available_payment_methods_message', esc_html__( 'Sorry, it seems that there are no available payment methods. Please contact us if you require assistance or wish to make alternate arrangements.', 'woocommerce' ) ), 'notice' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
							echo '</li>';
						}
						?>
					</ul>
				<?php endif; ?>

				<div class="form-row noyona-order-pay__actions">
					<input type="hidden" name="woocommerce_pay" value="1" />

					<?php wc_get_template( 'checkout/terms.php' ); ?>

					<?php do_action( 'woocommerce_pay_order_before_submit' ); ?>

					<?php echo apply_filters( 'woocommerce_pay_order_button_html', '<button type="submit" class="button alt noyona-checkout-btn" id="place_order" value="' . esc_attr( $order_button_text ) . '" data-value="' . esc_attr( $order_button_text ) . '">' . esc_html( $order_button_text ) . '</button>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

					<?php do_action( 'woocommerce_pay_order_after_submit' ); ?>

					<?php wp_nonce_field( 'woocommerce-pay', 'woocommerce-pay-nonce' ); ?>
				</div>
			</div>
		</div>
	</form>
</div>
