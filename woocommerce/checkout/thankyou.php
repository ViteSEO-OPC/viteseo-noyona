<?php
/**
 * Noyona custom thank-you page.
 *
 * Override of woocommerce/templates/checkout/thankyou.php.
 *
 * @version 8.1.0
 *
 * @var WC_Order $order
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="woocommerce-order noyona-order-done">
	<?php if ( $order ) : ?>
		<?php do_action( 'woocommerce_before_thankyou', $order->get_id() ); ?>
		<?php
		$is_done_order       = $order->is_paid();
		$is_awaiting_payment = ( ! $is_done_order && $order->has_status( array( 'pending', 'on-hold' ) ) );

		ob_start();
		do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() );
		do_action( 'woocommerce_thankyou', $order->get_id() );
		$thankyou_hook_markup = trim( (string) ob_get_clean() );
		?>

		<?php if ( $order->has_status( 'failed' ) ) : ?>
			<p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed">
				<?php esc_html_e( 'Unfortunately your order cannot be processed because the transaction was declined. Please try again.', 'noyona' ); ?>
			</p>
			<p class="woocommerce-thankyou-order-failed-actions">
				<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="button pay">
					<?php esc_html_e( 'Pay', 'woocommerce' ); ?>
				</a>
				<?php if ( is_user_logged_in() ) : ?>
					<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="button pay">
						<?php esc_html_e( 'My account', 'woocommerce' ); ?>
					</a>
				<?php endif; ?>
			</p>
		<?php else : ?>
			<?php
			$order_created = $order->get_date_created() ? $order->get_date_created()->getTimestamp() : current_time( 'timestamp' );
			$eta_from      = date_i18n( 'M j', strtotime( '+7 days', $order_created ) );
			$eta_to        = date_i18n( 'M j, Y', strtotime( '+10 days', $order_created ) );

			$ship_name = trim( $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name() );
			if ( '' === $ship_name ) {
				$ship_name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
			}

			$ship_parts = array_filter(
				array(
					$order->get_shipping_address_1(),
					$order->get_shipping_city(),
					$order->get_shipping_state(),
					$order->get_shipping_postcode(),
				)
			);
			if ( empty( $ship_parts ) ) {
				$ship_parts = array_filter(
					array(
						$order->get_billing_address_1(),
						$order->get_billing_city(),
						$order->get_billing_state(),
						$order->get_billing_postcode(),
					)
				);
			}
			$ship_address = implode( ', ', $ship_parts );

			$subtotal       = (float) $order->get_subtotal();
			$discount_total = (float) $order->get_discount_total();
			$shipping_total = (float) $order->get_shipping_total() + (float) $order->get_shipping_tax();
			$payment_label  = (string) $order->get_payment_method_title();
			if ( '' === trim( $payment_label ) ) {
				$payment_label = (string) $order->get_payment_method();
			}

			$shop_url  = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );

			// Track Order should open the custom order-details modal on the
			// My Account "Orders" panel rather than Woo's default view-order page.
			// noyona_get_account_order_modal_url() resolves the correct filter
			// tab + page + modal hash so the modal actually opens. Guests (who
			// can't see the account orders panel) fall back to the account page.
			$track_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' );
			if ( is_user_logged_in()
				&& (int) $order->get_user_id() === (int) get_current_user_id()
				&& function_exists( 'noyona_get_account_order_modal_url' )
			) {
				$modal_url = noyona_get_account_order_modal_url( $order );
				if ( '' !== $modal_url ) {
					$track_url = $modal_url;
				}
			}
			?>

			<?php if ( $is_awaiting_payment ) : ?>
				<div data-noyona-awaiting-payment="1"></div>
				<section class="noyona-pay-hero noyona-pay-hero--received">
					<div class="noyona-pay-hero__icon" aria-hidden="true">
						<i class="fa-solid fa-check"></i>
					</div>
					<h1 class="noyona-pay-hero__title"><?php esc_html_e( 'Order will be processed upon payment', 'noyona' ); ?></h1>
					<p class="noyona-pay-hero__subtitle">
						<?php esc_html_e( 'Thank you! Order has been received!', 'noyona' ); ?>
					</p>
				</section>

				<section class="noyona-pay-meta" aria-label="<?php esc_attr_e( 'Order summary', 'noyona' ); ?>">
					<div class="noyona-pay-meta__item">
						<span class="noyona-pay-meta__label"><?php esc_html_e( 'Order Number', 'noyona' ); ?></span>
						<strong class="noyona-pay-meta__value"><?php echo esc_html( $order->get_order_number() ); ?></strong>
					</div>
					<div class="noyona-pay-meta__item">
						<span class="noyona-pay-meta__label"><?php esc_html_e( 'Date', 'noyona' ); ?></span>
						<strong class="noyona-pay-meta__value"><?php echo esc_html( date_i18n( 'M j, Y', $order_created ) ); ?></strong>
					</div>
					<div class="noyona-pay-meta__item">
						<span class="noyona-pay-meta__label"><?php esc_html_e( 'Total', 'noyona' ); ?></span>
						<strong class="noyona-pay-meta__value"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></strong>
					</div>
					<div class="noyona-pay-meta__item">
						<span class="noyona-pay-meta__label"><?php esc_html_e( 'Email', 'noyona' ); ?></span>
						<strong class="noyona-pay-meta__value"><?php echo esc_html( (string) $order->get_billing_email() ); ?></strong>
					</div>
					<div class="noyona-pay-meta__item">
						<span class="noyona-pay-meta__label"><?php esc_html_e( 'Payment', 'noyona' ); ?></span>
						<strong class="noyona-pay-meta__value"><?php echo esc_html( $payment_label ); ?></strong>
					</div>
				</section>

				<section class="noyona-pay-card">
					<div class="noyona-pay-card__gateway">
						<?php if ( '' !== $thankyou_hook_markup ) : ?>
							<?php echo $thankyou_hook_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php else : ?>
							<p class="woocommerce-info"><?php esc_html_e( 'If the QR does not load, use the payment button below.', 'noyona' ); ?></p>
						<?php endif; ?>
					</div>
				</section>

				<script>
				(function() {
					var gateway = document.querySelector('.noyona-pay-card__gateway');
					if (!gateway) return;

					var defaultExpirySeconds = 30 * 60; // Standard fallback expiry window: 30 minutes.
					var activeTimers = [];

					function startCountdown(node) {
						if (!node || node.querySelector('.noyona-pay-expire-time')) {
							return;
						}

						var totalSeconds = defaultExpirySeconds;
						var expiryNode = node.querySelector('strong');
						if (expiryNode) {
							var expiryMs = Date.parse(String(expiryNode.textContent || '').trim());
							if (!isNaN(expiryMs)) {
								totalSeconds = Math.max(0, Math.floor((expiryMs - Date.now()) / 1000));
							}
						}
						var timerNode = document.createElement('strong');
						timerNode.className = 'noyona-pay-expire-time';
						node.appendChild(document.createTextNode(' '));
						node.appendChild(timerNode);

						function render() {
							var minutes = Math.floor(totalSeconds / 60);
							var seconds = totalSeconds % 60;
							timerNode.textContent = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
						}

						render();
						var intervalId = window.setInterval(function() {
							totalSeconds = Math.max(0, totalSeconds - 1);
							render();
							if (totalSeconds <= 0) {
								window.clearInterval(intervalId);
							}
						}, 1000);
						activeTimers.push(intervalId);
					}

					function applyFallbackExpiryTime() {
						var expiryLabelNodes = Array.prototype.filter.call(
							gateway.querySelectorAll('p, span, div, small, strong'),
							function(node) {
								var text = String(node.textContent || '').toLowerCase();
								return text.indexOf('this qr code expires on') !== -1;
							}
						);
						if (!expiryLabelNodes.length) return false;

						expiryLabelNodes.forEach(function(node) {
							startCountdown(node);
						});
						return true;
					}

					var foundExpiryLabel = applyFallbackExpiryTime();
					if (!foundExpiryLabel && window.MutationObserver) {
						var observer = new MutationObserver(function() {
							var applied = applyFallbackExpiryTime();
							if (applied) {
								window.setTimeout(function() {
									observer.disconnect();
								}, 2000);
							}
						});
						observer.observe(gateway, { childList: true, subtree: true, characterData: true });

						// Stop watching eventually to avoid long-lived observers.
						window.setTimeout(function() {
							observer.disconnect();
						}, 45000);
					}

					window.addEventListener('beforeunload', function() {
						activeTimers.forEach(function(id) {
							window.clearInterval(id);
						});
					});
				})();
				</script>

				<section class="noyona-checkout-card noyona-pay-order-details">
					<h2 class="noyona-checkout-card__title"><?php esc_html_e( 'Order Details', 'noyona' ); ?></h2>

					<div class="noyona-order-items">
						<?php foreach ( $order->get_items( 'line_item' ) as $item_id => $item ) : ?>
							<?php
							$product = $item->get_product();
							if ( ! $product ) {
								continue;
							}
							$thumbnail = $product->get_image( 'woocommerce_gallery_thumbnail' );
							$item_meta = wc_display_item_meta(
								$item,
								array(
									'before'    => '<dl class="variation">',
									'after'     => '</dl>',
									'separator' => '',
									'echo'      => false,
								)
							);
							?>
							<div class="noyona-order-item">
								<div class="noyona-order-item__image">
									<?php echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</div>
								<div class="noyona-order-item__details">
									<span class="noyona-order-item__name"><?php echo esc_html( $item->get_name() ); ?></span>
									<?php if ( ! empty( trim( wp_strip_all_tags( (string) $item_meta ) ) ) ) : ?>
										<?php echo wp_kses_post( $item_meta ); ?>
									<?php endif; ?>
									<span class="noyona-order-item__qty">&times; <?php echo esc_html( (string) $item->get_quantity() ); ?></span>
								</div>
								<div class="noyona-order-item__total">
									<?php echo wp_kses_post( $order->get_formatted_line_subtotal( $item ) ); ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>

					<div class="noyona-order-totals">
						<div class="noyona-order-totals__row">
							<span class="noyona-order-totals__label"><?php esc_html_e( 'Shipping', 'noyona' ); ?></span>
							<span class="noyona-order-totals__value <?php echo $shipping_total <= 0 ? 'noyona-order-totals__value--free' : ''; ?>">
								<?php echo $shipping_total > 0 ? wp_kses_post( wc_price( $shipping_total ) ) : esc_html__( 'FREE', 'woocommerce' ); ?>
							</span>
						</div>
						<div class="noyona-order-totals__row noyona-order-totals__row--total">
							<span class="noyona-order-totals__label"><?php esc_html_e( 'Total', 'noyona' ); ?></span>
							<span class="noyona-order-totals__value"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></span>
						</div>
					</div>

					<?php $customer_note = trim( (string) $order->get_customer_note() ); ?>
					<?php if ( '' !== $customer_note ) : ?>
						<div class="noyona-order-note">
							<span class="noyona-order-note__label"><?php esc_html_e( 'Order Notes', 'noyona' ); ?></span>
							<p class="noyona-order-note__text"><?php echo esc_html( $customer_note ); ?></p>
						</div>
					<?php endif; ?>
				</section>

			<?php elseif ( $is_done_order ) : ?>
				<div data-noyona-done-order="1"></div>
				<section class="noyona-done-hero">
					<div class="noyona-done-hero__icon" aria-hidden="true">
						<i class="fa-solid fa-check"></i>
					</div>
					<h1 class="noyona-done-hero__title"><?php esc_html_e( 'Order Confirmed!', 'noyona' ); ?></h1>
					<p class="noyona-done-hero__subtitle"><?php esc_html_e( 'Thank you for shopping with Noyona Essentials', 'noyona' ); ?></p>
				</section>

				<section class="noyona-done-order-number" aria-label="<?php esc_attr_e( 'Order number', 'noyona' ); ?>">
					<span class="noyona-done-order-number__label"><?php esc_html_e( 'Order Number', 'noyona' ); ?></span>
					<strong class="noyona-done-order-number__value"><?php echo esc_html( $order->get_order_number() ); ?></strong>
				</section>

				<section class="noyona-checkout-card noyona-done-summary-card">
					<h2 class="noyona-checkout-card__title"><?php esc_html_e( 'Order Summary', 'noyona' ); ?></h2>

					<div class="noyona-order-items">
						<?php foreach ( $order->get_items( 'line_item' ) as $item_id => $item ) : ?>
							<?php
							$product = $item->get_product();
							if ( ! $product ) {
								continue;
							}
							$thumbnail = $product->get_image( 'woocommerce_gallery_thumbnail' );
							$item_meta = wc_display_item_meta(
								$item,
								array(
									'before'    => '<dl class="variation">',
									'after'     => '</dl>',
									'separator' => '',
									'echo'      => false,
								)
							);
							?>
							<div class="noyona-order-item">
								<div class="noyona-order-item__image">
									<?php echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</div>
								<div class="noyona-order-item__details">
									<span class="noyona-order-item__name"><?php echo esc_html( $item->get_name() ); ?></span>
									<?php if ( ! empty( trim( wp_strip_all_tags( (string) $item_meta ) ) ) ) : ?>
										<?php echo wp_kses_post( $item_meta ); ?>
									<?php endif; ?>
									<span class="noyona-order-item__qty">&times; <?php echo esc_html( (string) $item->get_quantity() ); ?></span>
								</div>
								<div class="noyona-order-item__total">
									<?php echo wp_kses_post( $order->get_formatted_line_subtotal( $item ) ); ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>

					<div class="noyona-order-totals">
						<div class="noyona-order-totals__row">
							<span class="noyona-order-totals__label"><?php esc_html_e( 'Subtotal', 'noyona' ); ?></span>
							<span class="noyona-order-totals__value"><?php echo wp_kses_post( wc_price( $subtotal ) ); ?></span>
						</div>
						<?php if ( $discount_total > 0 ) : ?>
							<div class="noyona-order-totals__row noyona-order-totals__row--discount">
								<span class="noyona-order-totals__label"><?php esc_html_e( 'Discount', 'noyona' ); ?></span>
								<span class="noyona-order-totals__value">-<?php echo wp_kses_post( wc_price( $discount_total ) ); ?></span>
							</div>
						<?php endif; ?>
						<div class="noyona-order-totals__row">
							<span class="noyona-order-totals__label"><?php esc_html_e( 'Shipping', 'noyona' ); ?></span>
							<span class="noyona-order-totals__value <?php echo $shipping_total <= 0 ? 'noyona-order-totals__value--free' : ''; ?>">
								<?php echo $shipping_total > 0 ? wp_kses_post( wc_price( $shipping_total ) ) : esc_html__( 'FREE', 'woocommerce' ); ?>
							</span>
						</div>
						<div class="noyona-order-totals__row noyona-order-totals__row--total">
							<span class="noyona-order-totals__label"><?php esc_html_e( 'Total', 'noyona' ); ?></span>
							<span class="noyona-order-totals__value"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></span>
						</div>
					</div>

					<?php $customer_note = trim( (string) $order->get_customer_note() ); ?>
					<?php if ( '' !== $customer_note ) : ?>
						<div class="noyona-order-note">
							<span class="noyona-order-note__label"><?php esc_html_e( 'Order Notes', 'noyona' ); ?></span>
							<p class="noyona-order-note__text"><?php echo esc_html( $customer_note ); ?></p>
						</div>
					<?php endif; ?>
				</section>

				<div class="noyona-done-meta-grid">
					<section class="noyona-checkout-card noyona-done-info-card noyona-done-info-card--delivery">
						<div class="noyona-done-info-card__icon" aria-hidden="true">
							<i class="fa-solid fa-truck-fast"></i>
						</div>
						<h3 class="noyona-done-info-card__title"><?php esc_html_e( 'Estimated Delivery', 'noyona' ); ?></h3>
						<p class="noyona-done-info-card__primary"><?php echo esc_html( $eta_from . ' - ' . $eta_to ); ?></p>
						<p class="noyona-done-info-card__text">
							<?php echo esc_html( $ship_name ); ?><br>
							<?php echo esc_html( $ship_address ); ?>
						</p>
					</section>

					<section class="noyona-checkout-card noyona-done-info-card">
						<div class="noyona-done-info-card__icon" aria-hidden="true">
							<i class="fa-regular fa-envelope"></i>
						</div>
						<h3 class="noyona-done-info-card__title"><?php esc_html_e( 'Confirmation Sent', 'noyona' ); ?></h3>
						<p class="noyona-done-info-card__primary"><?php echo esc_html( $order->get_billing_email() ); ?></p>
						<p class="noyona-done-info-card__text"><?php esc_html_e( 'Check your inbox for details.', 'noyona' ); ?></p>
					</section>
				</div>

				<div class="noyona-done-actions">
					<a class="noyona-done-actions__btn noyona-done-actions__btn--primary" href="<?php echo esc_url( $shop_url ); ?>">
						<span class="noyona-done-actions__label"><?php esc_html_e( 'Continue Shopping', 'noyona' ); ?></span>
					</a>
					<a class="noyona-done-actions__btn noyona-done-actions__btn--outline" href="<?php echo esc_url( $track_url ); ?>">
						<i class="fa-solid fa-cube" aria-hidden="true"></i>
						<span class="noyona-done-actions__label"><?php esc_html_e( 'Track Order', 'noyona' ); ?></span>
					</a>
				</div>
			<?php elseif ( $order->has_status( 'cancelled' ) ) : ?>
				<section class="noyona-pay-hero noyona-pay-hero--received">
					<div class="noyona-pay-hero__icon" aria-hidden="true">
						<i class="fa-solid fa-circle-xmark"></i>
					</div>
					<h1 class="noyona-pay-hero__title"><?php esc_html_e( 'Order Cancelled', 'noyona' ); ?></h1>
					<p class="noyona-pay-hero__subtitle">
						<?php esc_html_e( 'This order was cancelled because the payment was not completed in time.', 'noyona' ); ?>
					</p>
				</section>

				<section class="noyona-pay-meta" aria-label="<?php esc_attr_e( 'Order summary', 'noyona' ); ?>">
					<div class="noyona-pay-meta__item">
						<span class="noyona-pay-meta__label"><?php esc_html_e( 'Order Number', 'noyona' ); ?></span>
						<strong class="noyona-pay-meta__value"><?php echo esc_html( $order->get_order_number() ); ?></strong>
					</div>
					<div class="noyona-pay-meta__item">
						<span class="noyona-pay-meta__label"><?php esc_html_e( 'Date', 'noyona' ); ?></span>
						<strong class="noyona-pay-meta__value"><?php echo esc_html( date_i18n( 'M j, Y', $order_created ) ); ?></strong>
					</div>
				</section>

				<div class="noyona-done-actions">
					<a class="noyona-done-actions__btn noyona-done-actions__btn--primary" href="<?php echo esc_url( $shop_url ); ?>">
						<span class="noyona-done-actions__label"><?php esc_html_e( 'Continue Shopping', 'noyona' ); ?></span>
					</a>
					<a class="noyona-done-actions__btn noyona-done-actions__btn--outline" href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' ) ); ?>">
						<span class="noyona-done-actions__label"><?php esc_html_e( 'My Account', 'noyona' ); ?></span>
					</a>
				</div>
			<?php else : ?>
				<section class="noyona-pay-hero noyona-pay-hero--received">
					<div class="noyona-pay-hero__icon" aria-hidden="true">
						<i class="fa-solid fa-clock"></i>
					</div>
					<h1 class="noyona-pay-hero__title"><?php esc_html_e( 'Payment Status Pending', 'noyona' ); ?></h1>
					<p class="noyona-pay-hero__subtitle">
						<?php esc_html_e( 'We received your order, but payment has not been confirmed yet. Please complete payment before this order is marked done.', 'noyona' ); ?>
					</p>
				</section>

				<section class="noyona-pay-meta" aria-label="<?php esc_attr_e( 'Order summary', 'noyona' ); ?>">
					<div class="noyona-pay-meta__item">
						<span class="noyona-pay-meta__label"><?php esc_html_e( 'Order Number', 'noyona' ); ?></span>
						<strong class="noyona-pay-meta__value"><?php echo esc_html( $order->get_order_number() ); ?></strong>
					</div>
					<div class="noyona-pay-meta__item">
						<span class="noyona-pay-meta__label"><?php esc_html_e( 'Status', 'noyona' ); ?></span>
						<strong class="noyona-pay-meta__value"><?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?></strong>
					</div>
					<div class="noyona-pay-meta__item">
						<span class="noyona-pay-meta__label"><?php esc_html_e( 'Total', 'noyona' ); ?></span>
						<strong class="noyona-pay-meta__value"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></strong>
					</div>
				</section>

				<section class="noyona-pay-card">
					<div class="noyona-pay-card__gateway">
						<?php if ( '' !== $thankyou_hook_markup ) : ?>
							<?php echo $thankyou_hook_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php else : ?>
							<p class="woocommerce-info"><?php esc_html_e( 'Payment instructions are not available right now. Please check your account order history or contact support before placing another order.', 'noyona' ); ?></p>
						<?php endif; ?>
					</div>
				</section>
			<?php endif; ?>
		<?php endif; ?>
	<?php else : ?>
		<?php
		$is_preview_mode = isset( $_GET['noyona_preview_done'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			&& function_exists( 'noyona_checkout_allow_done_preview_bypass' )
			&& noyona_checkout_allow_done_preview_bypass();
		?>
		<?php if ( $is_preview_mode ) : ?>
			<?php
			$cart = ( function_exists( 'WC' ) && WC()->cart ) ? WC()->cart : null;

			$preview_order_number = 'PREVIEW-' . strtoupper( wp_generate_password( 8, false, false ) );
			$preview_timestamp    = current_time( 'timestamp' );
			$eta_from             = date_i18n( 'M j', strtotime( '+7 days', $preview_timestamp ) );
			$eta_to               = date_i18n( 'M j, Y', strtotime( '+10 days', $preview_timestamp ) );

			$ship_name = '';
			$ship_to   = '';
			$email     = '';
			if ( function_exists( 'WC' ) && WC()->customer ) {
				$customer  = WC()->customer;
				$ship_name = trim( $customer->get_billing_first_name() . ' ' . $customer->get_billing_last_name() );
				$ship_to   = implode(
					', ',
					array_filter(
						array(
							$customer->get_shipping_address_1(),
							$customer->get_shipping_city(),
							$customer->get_shipping_state(),
							$customer->get_shipping_postcode(),
						)
					)
				);
				$email = (string) $customer->get_billing_email();
			}
			if ( '' === $ship_name ) {
				$ship_name = __( 'Sample Customer', 'noyona' );
			}
			if ( '' === $ship_to ) {
				$ship_to = __( 'Address from checkout details', 'noyona' );
			}
			if ( '' === $email ) {
				$email = __( 'email@example.com', 'noyona' );
			}

			$subtotal       = $cart ? (float) $cart->get_subtotal() : 0.0;
			$discount_total = $cart ? (float) $cart->get_discount_total() : 0.0;
			$shipping_total = 0.0;
			$total_html     = $cart ? $cart->get_total() : wc_price( 0 );
			if ( $cart ) {
				$shipping_total = (float) $cart->get_shipping_total() + (float) $cart->get_shipping_tax();
			}

			$shop_url  = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
			$track_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' );
			?>
			<section class="noyona-done-hero">
				<div class="noyona-done-hero__icon" aria-hidden="true">
					<i class="fa-solid fa-check"></i>
				</div>
				<h1 class="noyona-done-hero__title"><?php esc_html_e( 'Order Confirmed!', 'noyona' ); ?></h1>
				<p class="noyona-done-hero__subtitle"><?php esc_html_e( 'Preview mode (local) - payment is bypassed.', 'noyona' ); ?></p>
			</section>

			<section class="noyona-done-order-number" aria-label="<?php esc_attr_e( 'Order number', 'noyona' ); ?>">
				<span class="noyona-done-order-number__label"><?php esc_html_e( 'Order Number', 'noyona' ); ?></span>
				<strong class="noyona-done-order-number__value"><?php echo esc_html( $preview_order_number ); ?></strong>
			</section>

			<section class="noyona-checkout-card noyona-done-summary-card">
				<h2 class="noyona-checkout-card__title"><?php esc_html_e( 'Order Summary', 'noyona' ); ?></h2>

				<div class="noyona-order-items">
					<?php if ( $cart && ! empty( $cart->get_cart() ) ) : ?>
						<?php foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) : ?>
							<?php
							$_product = isset( $cart_item['data'] ) ? $cart_item['data'] : null;
							if ( ! $_product || ! is_a( $_product, 'WC_Product' ) ) {
								continue;
							}
							$thumbnail = $_product->get_image( 'woocommerce_gallery_thumbnail' );
							$item_meta = wc_get_formatted_cart_item_data( $cart_item );
							?>
							<div class="noyona-order-item">
								<div class="noyona-order-item__image">
									<?php echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</div>
								<div class="noyona-order-item__details">
									<span class="noyona-order-item__name"><?php echo esc_html( $_product->get_name() ); ?></span>
									<?php if ( ! empty( trim( wp_strip_all_tags( (string) $item_meta ) ) ) ) : ?>
										<?php echo wp_kses_post( $item_meta ); ?>
									<?php endif; ?>
									<span class="noyona-order-item__qty">&times; <?php echo esc_html( (string) $cart_item['quantity'] ); ?></span>
								</div>
								<div class="noyona-order-item__total">
									<?php echo wp_kses_post( $cart->get_product_subtotal( $_product, $cart_item['quantity'] ) ); ?>
								</div>
							</div>
						<?php endforeach; ?>
					<?php else : ?>
						<p class="woocommerce-info"><?php esc_html_e( 'No cart items found for preview.', 'noyona' ); ?></p>
					<?php endif; ?>
				</div>

				<div class="noyona-order-totals">
					<div class="noyona-order-totals__row">
						<span class="noyona-order-totals__label"><?php esc_html_e( 'Subtotal', 'noyona' ); ?></span>
						<span class="noyona-order-totals__value"><?php echo wp_kses_post( wc_price( $subtotal ) ); ?></span>
					</div>
					<?php if ( $discount_total > 0 ) : ?>
						<div class="noyona-order-totals__row noyona-order-totals__row--discount">
							<span class="noyona-order-totals__label"><?php esc_html_e( 'Discount', 'noyona' ); ?></span>
							<span class="noyona-order-totals__value">-<?php echo wp_kses_post( wc_price( $discount_total ) ); ?></span>
						</div>
					<?php endif; ?>
					<div class="noyona-order-totals__row">
						<span class="noyona-order-totals__label"><?php esc_html_e( 'Shipping', 'noyona' ); ?></span>
						<span class="noyona-order-totals__value <?php echo $shipping_total <= 0 ? 'noyona-order-totals__value--free' : ''; ?>">
							<?php echo $shipping_total > 0 ? wp_kses_post( wc_price( $shipping_total ) ) : esc_html__( 'FREE', 'woocommerce' ); ?>
						</span>
					</div>
					<div class="noyona-order-totals__row noyona-order-totals__row--total">
						<span class="noyona-order-totals__label"><?php esc_html_e( 'Total', 'noyona' ); ?></span>
						<span class="noyona-order-totals__value"><?php echo wp_kses_post( $total_html ); ?></span>
					</div>
				</div>
			</section>

			<div class="noyona-done-meta-grid">
				<section class="noyona-checkout-card noyona-done-info-card noyona-done-info-card--delivery">
					<div class="noyona-done-info-card__icon" aria-hidden="true">
						<i class="fa-solid fa-truck-fast"></i>
					</div>
					<h3 class="noyona-done-info-card__title"><?php esc_html_e( 'Estimated Delivery', 'noyona' ); ?></h3>
					<p class="noyona-done-info-card__primary"><?php echo esc_html( $eta_from . ' - ' . $eta_to ); ?></p>
					<p class="noyona-done-info-card__text">
						<?php echo esc_html( $ship_name ); ?><br>
						<?php echo esc_html( $ship_to ); ?>
					</p>
				</section>

				<section class="noyona-checkout-card noyona-done-info-card">
					<div class="noyona-done-info-card__icon" aria-hidden="true">
						<i class="fa-regular fa-envelope"></i>
					</div>
					<h3 class="noyona-done-info-card__title"><?php esc_html_e( 'Confirmation Sent', 'noyona' ); ?></h3>
					<p class="noyona-done-info-card__primary"><?php echo esc_html( $email ); ?></p>
					<p class="noyona-done-info-card__text"><?php esc_html_e( 'Check your inbox for details.', 'noyona' ); ?></p>
				</section>
			</div>

			<div class="noyona-done-actions">
				<a class="noyona-done-actions__btn noyona-done-actions__btn--primary" href="<?php echo esc_url( $shop_url ); ?>">
					<span class="noyona-done-actions__label"><?php esc_html_e( 'Continue Shopping', 'noyona' ); ?></span>
				</a>
				<a class="noyona-done-actions__btn noyona-done-actions__btn--outline" href="<?php echo esc_url( $track_url ); ?>">
					<i class="fa-solid fa-cube" aria-hidden="true"></i>
					<span class="noyona-done-actions__label"><?php esc_html_e( 'Track Order', 'noyona' ); ?></span>
				</a>
			</div>
		<?php else : ?>
			<p class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received">
				<?php esc_html_e( 'Thank you. Your order has been received.', 'woocommerce' ); ?>
			</p>
		<?php endif; ?>
	<?php endif; ?>
</div>
