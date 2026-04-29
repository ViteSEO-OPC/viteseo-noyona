<?php
/**
 * Checkout Form — Noyona two-column layout.
 *
 * Override of woocommerce/templates/checkout/form-checkout.php
 *
 * @version 9.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_before_checkout_form', $checkout );

if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) );
	return;
}

$is_done_preview = isset( $_GET['noyona_preview_done'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	&& function_exists( 'noyona_checkout_is_local_env' )
	&& noyona_checkout_is_local_env();

if ( $is_done_preview ) {
	wc_get_template(
		'checkout/thankyou.php',
		array(
			'order' => false,
		)
	);
	return;
}

$request_uri    = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
$request_path   = trim( (string) wp_parse_url( $request_uri, PHP_URL_PATH ), '/' );
$reviews_path   = trim( (string) wp_parse_url( home_url( '/reviews/' ), PHP_URL_PATH ), '/' );
$is_review_step = ( '' !== $reviews_path && $request_path === $reviews_path );
$saved_checkout_addresses = array();
$saved_checkout_payload   = array();
if ( is_user_logged_in() && function_exists( 'noyona_get_account_saved_addresses' ) ) {
	$stored_addresses = (array) noyona_get_account_saved_addresses( (int) get_current_user_id() );
	$address_index    = 1;
	foreach ( $stored_addresses as $stored_address ) {
		if ( ! is_array( $stored_address ) ) {
			continue;
		}

		$address_id   = isset( $stored_address['id'] ) ? sanitize_key( (string) $stored_address['id'] ) : sanitize_key( 'address_' . $address_index );
		$address_line = isset( $stored_address['address'] ) ? sanitize_text_field( (string) $stored_address['address'] ) : '';
		$city         = isset( $stored_address['city'] ) ? sanitize_text_field( (string) $stored_address['city'] ) : '';
		$province     = isset( $stored_address['province'] ) ? sanitize_text_field( (string) $stored_address['province'] ) : '';
		$zip_code     = isset( $stored_address['zip_code'] ) ? sanitize_text_field( (string) $stored_address['zip_code'] ) : '';
		$is_default   = ! empty( $stored_address['is_default'] );

		if ( '' === trim( $address_line . $city . $province . $zip_code ) ) {
			continue;
		}

		$summary_parts = array_filter(
			array(
				$city,
				$province,
				$zip_code,
			),
			static function ( $value ) {
				return '' !== trim( (string) $value );
			}
		);
		$summary       = implode( ', ', $summary_parts );
		if ( '' === trim( $summary ) ) {
			$summary = $address_line;
		}
		if ( function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' ) && mb_strlen( $summary ) > 42 ) {
			$summary = rtrim( mb_substr( $summary, 0, 42 ) ) . '...';
		} elseif ( strlen( $summary ) > 42 ) {
			$summary = rtrim( substr( $summary, 0, 42 ) ) . '...';
		}
		$label_prefix  = $is_default
			? __( 'Default', 'noyona' )
			: sprintf(
				/* translators: %d = address index */
				__( 'Address %d', 'noyona' ),
				$address_index
			);
		$option_label  = '' !== trim( $summary ) ? $label_prefix . ' - ' . $summary : $label_prefix;

		$saved_checkout_addresses[] = array(
			'id'    => $address_id,
			'label' => $option_label,
		);
		$saved_checkout_payload[] = array(
			'id'         => $address_id,
			'address'    => $address_line,
			'city'       => $city,
			'province'   => $province,
			'zip_code'   => $zip_code,
			'is_default' => $is_default ? 1 : 0,
		);
		$address_index++;
	}
}
?>

<form name="checkout" method="post" class="checkout woocommerce-checkout noyona-checkout-form"
      action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data"
      aria-label="<?php echo esc_attr__( 'Checkout', 'woocommerce' ); ?>">
	<input type="hidden" name="ship_to_different_address" value="1" />
	<input type="hidden" name="shipping_country" value="PH" />

	<div class="noyona-checkout-columns">

		<!-- ─── Left column ─── -->
		<div class="noyona-checkout-col-left">

			<?php if ( $checkout->get_checkout_fields() ) : ?>

				<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

				<div class="noyona-checkout-card noyona-checkout-card--contact" id="customer_details">
					<h2 class="noyona-checkout-card__title">
						<i class="fa-solid fa-envelope" aria-hidden="true"></i>
						<?php esc_html_e( 'Contact Information', 'noyona' ); ?>
					</h2>
					<div class="noyona-checkout-fields-wrap">
						<?php do_action( 'woocommerce_checkout_billing' ); ?>
					</div>
					<div class="noyona-checkout-optin">
						<label class="noyona-checkout-optin__label">
							<input type="checkbox" name="noyona_newsletter" value="1" class="noyona-checkout-optin__input">
							<span><?php esc_html_e( 'Email me with news and offers', 'noyona' ); ?></span>
						</label>
					</div>
				</div>

				<div class="noyona-checkout-card noyona-checkout-card--shipping">
					<div class="noyona-checkout-card__header">
						<h2 class="noyona-checkout-card__title">
							<i class="fa-solid fa-file-lines" aria-hidden="true"></i>
							<?php esc_html_e( 'Shipping Address', 'noyona' ); ?>
						</h2>
						<button type="button" class="noyona-use-location" id="noyona-use-location">
							<i class="fa-solid fa-location-dot" aria-hidden="true"></i>
							<?php esc_html_e( 'Use current address', 'noyona' ); ?>
						</button>
					</div>
					<?php if ( ! empty( $saved_checkout_addresses ) ) : ?>
						<div class="noyona-saved-address-picker form-row form-row-wide">
							<label for="noyona-saved-address-select"><?php esc_html_e( 'Saved addresses', 'noyona' ); ?></label>
							<select id="noyona-saved-address-select" class="noyona-saved-address-picker__select">
								<option value=""><?php esc_html_e( 'Choose saved address', 'noyona' ); ?></option>
								<?php foreach ( $saved_checkout_addresses as $saved_address_option ) : ?>
									<option value="<?php echo esc_attr( (string) $saved_address_option['id'] ); ?>"><?php echo esc_html( (string) $saved_address_option['label'] ); ?></option>
								<?php endforeach; ?>
							</select>
							<p class="noyona-saved-address-picker__hint"><?php esc_html_e( 'Select one address to auto-fill shipping fields.', 'noyona' ); ?></p>
						</div>
						<script id="noyona-saved-addresses-data" type="application/json"><?php echo wp_json_encode( $saved_checkout_payload ); ?></script>
					<?php endif; ?>
					<div class="noyona-checkout-fields-wrap">
						<?php
						ob_start();
						do_action( 'woocommerce_checkout_shipping' );
						$shipping_markup = (string) ob_get_clean();
						$has_shipping_inputs = (bool) preg_match( '/name=(["\'])shipping_[^"\']+\1/i', $shipping_markup );

						if ( $has_shipping_inputs ) {
							echo $shipping_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						} else {
							$shipping_fields = (array) $checkout->get_checkout_fields( 'shipping' );

							// Server fallback: if Woo suppresses shipping fields, render a minimal address set.
							if ( empty( $shipping_fields ) ) {
								$default_country = (string) $checkout->get_value( 'shipping_country' );
								if ( '' === $default_country ) {
									$default_country = (string) $checkout->get_value( 'billing_country' );
								}
								if ( '' === $default_country && function_exists( 'WC' ) && WC()->countries ) {
									$default_country = (string) WC()->countries->get_base_country();
								}

								$states = array();
								if ( function_exists( 'WC' ) && WC()->countries && '' !== $default_country ) {
									$states = (array) WC()->countries->get_states( $default_country );
								}

								$shipping_fields = array(
									'shipping_address_1' => array(
										'type'         => 'text',
										'label'        => __( 'Address', 'noyona' ),
										'required'     => true,
										'class'        => array( 'form-row-wide' ),
										'priority'     => 10,
										'autocomplete' => 'address-line1',
									),
									'shipping_city' => array(
										'type'         => 'text',
										'label'        => __( 'City', 'noyona' ),
										'required'     => true,
										'class'        => array( 'form-row-first' ),
										'priority'     => 20,
										'autocomplete' => 'address-level2',
									),
									'shipping_state' => array(
										'type'         => ! empty( $states ) ? 'select' : 'text',
										'label'        => __( 'State / Province', 'noyona' ),
										'required'     => true,
										'class'        => array( 'form-row-last' ),
										'priority'     => 30,
										'options'      => ! empty( $states ) ? array( '' => __( 'Select an option&hellip;', 'woocommerce' ) ) + $states : array(),
										'autocomplete' => 'address-level1',
									),
									'shipping_postcode' => array(
										'type'         => 'text',
										'label'        => __( 'ZIP Code', 'noyona' ),
										'required'     => true,
										'class'        => array( 'form-row-wide' ),
										'priority'     => 40,
										'autocomplete' => 'postal-code',
									),
								);
							}

							uasort(
								$shipping_fields,
								static function ( $a, $b ) {
									$priority_a = isset( $a['priority'] ) ? (int) $a['priority'] : 0;
									$priority_b = isset( $b['priority'] ) ? (int) $b['priority'] : 0;
									return $priority_a <=> $priority_b;
								}
							);

							foreach ( $shipping_fields as $field_key => $field_args ) {
								woocommerce_form_field( $field_key, $field_args, $checkout->get_value( $field_key ) );
							}
						}
						?>
					</div>
					<div class="noyona-delivery-bar">
						<i class="fa-solid fa-truck-fast" aria-hidden="true"></i>
						<span><?php
							printf(
								esc_html__( 'Estimated delivery: %1$s – %2$s', 'noyona' ),
								'<strong>' . esc_html( date_i18n( 'M j', strtotime( '+7 days' ) ) ) . '</strong>',
								'<strong>' . esc_html( date_i18n( 'M j, Y', strtotime( '+10 days' ) ) ) . '</strong>'
							);
						?></span>
					</div>
				</div>

				<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>

			<?php endif; ?>

			<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>

			<div class="noyona-checkout-card noyona-checkout-card--payment">
				<h2 class="noyona-checkout-card__title">
					<i class="fa-solid fa-credit-card" aria-hidden="true"></i>
					<?php esc_html_e( 'Payment Method', 'noyona' ); ?>
				</h2>
				<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>
				<div id="order_review" class="woocommerce-checkout-review-order noyona-checkout-order-review">
					<?php do_action( 'woocommerce_checkout_order_review' ); ?>
				</div>
				<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
			</div>

		</div><!-- /.noyona-checkout-col-left -->

		<!-- ─── Right column ─── -->
		<div class="noyona-checkout-col-right">

			<?php if ( apply_filters( 'woocommerce_enable_order_notes_field', 'yes' === get_option( 'woocommerce_enable_order_comments', 'yes' ) ) ) : ?>
			<div class="noyona-checkout-card noyona-checkout-card--notes noyona-checkout-card--sidebar">
				<h2 class="noyona-checkout-card__title noyona-checkout-card__title--amber">
					<i class="fa-solid fa-comment-dots" aria-hidden="true"></i>
					<?php esc_html_e( 'Order Notes', 'noyona' ); ?>
				</h2>
				<p class="noyona-checkout-card__subtitle"><?php esc_html_e( 'Special Instructions', 'noyona' ); ?></p>
				<?php foreach ( $checkout->get_checkout_fields( 'order' ) as $key => $field ) : ?>
					<?php woocommerce_form_field( $key, $field, $checkout->get_value( $key ) ); ?>
				<?php endforeach; ?>
				<p class="noyona-checkout-card__hint"><?php esc_html_e( 'e.g. "Leave at the gate" or "Call upon arrival"', 'noyona' ); ?></p>
				<div class="noyona-checkout-optin noyona-checkout-optin--gift">
					<label class="noyona-checkout-optin__label">
						<input type="checkbox" name="noyona_gift_order" value="1" class="noyona-checkout-optin__input">
						<i class="fa-solid fa-gift noyona-checkout-optin__icon--gift" aria-hidden="true"></i>
						<span><?php esc_html_e( 'This order is a gift', 'noyona' ); ?></span>
					</label>
				</div>
			</div>
			<?php endif; ?>

			<div class="noyona-checkout-card noyona-checkout-card--summary noyona-checkout-card--sidebar <?php echo $is_review_step ? 'noyona-checkout-card--review-items' : ''; ?>">
				<h2 class="noyona-checkout-card__title <?php echo $is_review_step ? 'noyona-review-heading' : ''; ?>">
					<?php if ( ! $is_review_step ) : ?>
						<i class="fa-solid fa-receipt" aria-hidden="true"></i>
					<?php endif; ?>
					<?php echo $is_review_step ? esc_html__( 'Preview Your Order', 'noyona' ) : esc_html__( 'Order Summary', 'noyona' ); ?>
				</h2>
				<div class="noyona-order-items">
					<?php
					foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
						$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
						if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
							$thumbnail = $_product->get_image( 'woocommerce_gallery_thumbnail' );
							?>
							<div class="noyona-order-item">
								<div class="noyona-order-item__image"><?php echo $thumbnail; ?></div>
								<div class="noyona-order-item__details">
									<span class="noyona-order-item__name">
										<?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key ) ); ?>
									</span>
									<?php
										$item_data_html = wc_get_formatted_cart_item_data( $cart_item );

										// Remove stray line breaks and empty wrappers.
										$item_data_html = preg_replace( '#<br\s*/?>#i', '', $item_data_html );
										$item_data_html = trim( $item_data_html );

										// Only print if there is real content left.
										if ( '' !== wp_strip_all_tags( $item_data_html ) ) {
											echo wp_kses_post( $item_data_html );
										}
									?>
									<span class="noyona-order-item__qty">&times; <?php echo esc_html( $cart_item['quantity'] ); ?></span>
							    </div>
								<div class="noyona-order-item__total">
									<?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); ?>
								</div>
							</div>
							<?php
						}
					}
					?>
				</div>
				<?php if ( ! $is_review_step ) : ?>
					<div class="noyona-order-totals">
						<div class="noyona-order-totals__row">
							<span class="noyona-order-totals__label"><?php esc_html_e( 'Subtotal', 'noyona' ); ?></span>
							<span class="noyona-order-totals__value"><?php wc_cart_totals_subtotal_html(); ?></span>
						</div>
						<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
							<div class="noyona-order-totals__row noyona-order-totals__row--discount">
								<span class="noyona-order-totals__label"><?php wc_cart_totals_coupon_label( $coupon ); ?></span>
								<span class="noyona-order-totals__value"><?php wc_cart_totals_coupon_html( $coupon ); ?></span>
							</div>
						<?php endforeach; ?>
						<?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>
							<?php
							$packages = WC()->shipping()->get_packages();
							foreach ( $packages as $i => $package ) {
								$chosen_method     = isset( WC()->session->chosen_shipping_methods[ $i ] ) ? WC()->session->chosen_shipping_methods[ $i ] : '';
								$available_methods = $package['rates'];
								foreach ( $available_methods as $method ) {
									if ( $method->get_id() === $chosen_method || count( $available_methods ) === 1 ) {
										$shipping_label = $method->get_label();
										$shipping_cost  = (float) $method->get_cost();
										?>
										<div class="noyona-order-totals__row">
											<span class="noyona-order-totals__label"><?php echo esc_html( $shipping_label ); ?></span>
											<span class="noyona-order-totals__value <?php echo $shipping_cost <= 0 ? 'noyona-order-totals__value--free' : ''; ?>">
												<?php echo $shipping_cost > 0 ? wc_price( $shipping_cost ) : esc_html__( 'FREE', 'woocommerce' ); ?>
											</span>
										</div>
										<?php
										break;
									}
								}
							}
							?>
						<?php endif; ?>
						<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
							<div class="noyona-order-totals__row">
								<span class="noyona-order-totals__label"><?php echo esc_html( $fee->name ); ?></span>
								<span class="noyona-order-totals__value"><?php wc_cart_totals_fee_html( $fee ); ?></span>
							</div>
						<?php endforeach; ?>
						<div class="noyona-order-totals__row noyona-order-totals__row--total">
							<span class="noyona-order-totals__label"><?php esc_html_e( 'Total', 'noyona' ); ?></span>
							<span class="noyona-order-totals__value"><?php wc_cart_totals_order_total_html(); ?></span>
						</div>
					</div>
				<?php endif; ?>
			</div>
			<?php if ( $is_review_step ) : ?>
				<?php
				$ship_name  = trim( $checkout->get_value( 'billing_first_name' ) . ' ' . $checkout->get_value( 'billing_last_name' ) );
				$ship_lines = array_filter(
					array(
						$checkout->get_value( 'shipping_address_1' ),
						$checkout->get_value( 'shipping_city' ),
						$checkout->get_value( 'shipping_state' ),
						$checkout->get_value( 'shipping_postcode' ),
					)
				);
				$ship_to = implode( ', ', $ship_lines );

				$billing_email = $checkout->get_value( 'billing_email' );
				$billing_phone = $checkout->get_value( 'billing_phone' );

				$chosen_payment = WC()->session ? (string) WC()->session->get( 'chosen_payment_method' ) : '';
				$payment_label  = __( 'Payment Method', 'noyona' );
				$gateway_list   = WC()->payment_gateways() ? WC()->payment_gateways()->payment_gateways() : array();
				if ( $chosen_payment && isset( $gateway_list[ $chosen_payment ] ) ) {
					$payment_label = $gateway_list[ $chosen_payment ]->get_title();
				}
				?>
				<div class="noyona-checkout-card noyona-checkout-card--review-meta noyona-checkout-card--sidebar">
					<div class="noyona-review-meta">
						<div class="noyona-review-meta__col">
							<h3 class="noyona-review-meta__title"><?php esc_html_e( 'Ship To', 'noyona' ); ?></h3>
							<p class="noyona-review-meta__name" data-review-ship-name><?php echo esc_html( $ship_name ?: __( 'Customer', 'noyona' ) ); ?></p>
							<p class="noyona-review-meta__text" data-review-ship-address><?php echo esc_html( $ship_to ?: __( 'Address details will appear here.', 'noyona' ) ); ?></p>
						</div>
						<div class="noyona-review-meta__col">
							<h3 class="noyona-review-meta__title"><?php esc_html_e( 'Payment', 'noyona' ); ?></h3>
							<p class="noyona-review-meta__name" data-review-payment-method><?php echo esc_html( $payment_label ); ?></p>
							<p class="noyona-review-meta__text" data-review-email><?php echo esc_html( $billing_email ?: __( 'Email will appear here.', 'noyona' ) ); ?></p>
							<p class="noyona-review-meta__text" data-review-phone><?php echo esc_html( $billing_phone ?: __( 'Phone will appear here.', 'noyona' ) ); ?></p>
						</div>
					</div>
					<div class="noyona-delivery-bar">
						<i class="fa-solid fa-circle-info" aria-hidden="true"></i>
						<span><?php
							printf(
								esc_html__( 'Estimated delivery: %1$s – %2$s', 'noyona' ),
								'<strong>' . esc_html( date_i18n( 'M j', strtotime( '+7 days' ) ) ) . '</strong>',
								'<strong>' . esc_html( date_i18n( 'M j, Y', strtotime( '+10 days' ) ) ) . '</strong>'
							);
						?></span>
					</div>
				</div>

				<div class="noyona-checkout-card noyona-checkout-card--review-totals noyona-checkout-card--sidebar">
					<div class="noyona-order-totals">
						<div class="noyona-order-totals__row">
							<span class="noyona-order-totals__label"><?php esc_html_e( 'Subtotal', 'noyona' ); ?></span>
							<span class="noyona-order-totals__value"><?php wc_cart_totals_subtotal_html(); ?></span>
						</div>
						<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
							<div class="noyona-order-totals__row noyona-order-totals__row--discount">
								<span class="noyona-order-totals__label"><?php wc_cart_totals_coupon_label( $coupon ); ?></span>
								<span class="noyona-order-totals__value"><?php wc_cart_totals_coupon_html( $coupon ); ?></span>
							</div>
						<?php endforeach; ?>
						<?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>
							<?php
							$packages = WC()->shipping()->get_packages();
							foreach ( $packages as $i => $package ) {
								$chosen_method     = isset( WC()->session->chosen_shipping_methods[ $i ] ) ? WC()->session->chosen_shipping_methods[ $i ] : '';
								$available_methods = $package['rates'];
								foreach ( $available_methods as $method ) {
									if ( $method->get_id() === $chosen_method || count( $available_methods ) === 1 ) {
										$shipping_label = $method->get_label();
										$shipping_cost  = (float) $method->get_cost();
										?>
										<div class="noyona-order-totals__row">
											<span class="noyona-order-totals__label"><?php echo esc_html( $shipping_label ); ?></span>
											<span class="noyona-order-totals__value <?php echo $shipping_cost <= 0 ? 'noyona-order-totals__value--free' : ''; ?>">
												<?php echo $shipping_cost > 0 ? wc_price( $shipping_cost ) : esc_html__( 'FREE', 'woocommerce' ); ?>
											</span>
										</div>
										<?php
										break;
									}
								}
							}
							?>
						<?php endif; ?>
						<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
							<div class="noyona-order-totals__row">
								<span class="noyona-order-totals__label"><?php echo esc_html( $fee->name ); ?></span>
								<span class="noyona-order-totals__value"><?php wc_cart_totals_fee_html( $fee ); ?></span>
							</div>
						<?php endforeach; ?>
						<div class="noyona-order-totals__row noyona-order-totals__row--total">
							<span class="noyona-order-totals__label"><?php esc_html_e( 'Total', 'noyona' ); ?></span>
							<span class="noyona-order-totals__value"><?php wc_cart_totals_order_total_html(); ?></span>
						</div>
					</div>
				</div>
			<?php endif; ?>

			<div class="noyona-checkout-actions">
				<a href="/cart" class="noyona-checkout-actions__back">
					<i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
					<?php esc_html_e( 'Back', 'noyona' ); ?>
				</a>
				<button type="button" class="noyona-checkout-actions__submit" id="noyona-review-order">
					<?php esc_html_e( 'PREVIEW ORDER', 'noyona' ); ?>
					<i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
				</button>
			</div>

			<div class="noyona-pay-confirm-modal" id="noyona-pay-confirm-modal" hidden>
				<button
					type="button"
					class="noyona-pay-confirm-modal__backdrop"
					data-pay-confirm-close
					aria-label="<?php esc_attr_e( 'Close payment reminder', 'noyona' ); ?>"
				></button>
				<div class="noyona-pay-confirm-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="noyona-pay-confirm-title">
					<button
						type="button"
						class="noyona-pay-confirm-modal__close"
						data-pay-confirm-close
						aria-label="<?php esc_attr_e( 'Close payment reminder', 'noyona' ); ?>"
					>
						<i class="fa-solid fa-xmark" aria-hidden="true"></i>
					</button>
					<h3 id="noyona-pay-confirm-title" class="noyona-pay-confirm-modal__title">
						<?php esc_html_e( 'Complete payment to finish your order', 'noyona' ); ?>
					</h3>
					<p class="noyona-pay-confirm-modal__copy">
						<?php esc_html_e( 'After clicking Place Order, you will see a QR payment page. Please scan and complete payment (GCash, Maya, GoTyme, and other supported wallets). Your order will be marked Done after payment succeeds.', 'noyona' ); ?>
					</p>
					<div class="noyona-pay-confirm-modal__actions">
						<button type="button" class="noyona-pay-confirm-modal__btn noyona-pay-confirm-modal__btn--ghost" data-pay-confirm-close>
							<?php esc_html_e( 'Cancel', 'noyona' ); ?>
						</button>
						<button type="button" class="noyona-pay-confirm-modal__btn noyona-pay-confirm-modal__btn--primary" id="noyona-pay-confirm-proceed">
							<?php esc_html_e( 'Continue to payment', 'noyona' ); ?>
						</button>
					</div>
				</div>
			</div>

			<?php if ( $is_review_step ) : ?>
				<?php
				$terms_url = home_url( '/terms-of-services/' );
				?>
				<div class="noyona-review-terms">
					<label class="noyona-review-terms__label" for="noyona-review-terms">
						<input type="checkbox" id="noyona-review-terms" class="noyona-review-terms__checkbox">
						<span class="noyona-review-terms__text">
							<?php esc_html_e( 'I agree to the', 'noyona' ); ?>
							<?php if ( ! empty( $terms_url ) ) : ?>
								<a href="<?php echo esc_url( $terms_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Terms of Service, Shipping Policy, and Refund Policy', 'noyona' ); ?></a>
							<?php else : ?>
								<?php esc_html_e( 'Terms of Service, Shipping Policy, and Refund Policy', 'noyona' ); ?>
							<?php endif; ?>
							<?php esc_html_e( '. I understand that this order is final.', 'noyona' ); ?>
						</span>
					</label>
				</div>
			<?php endif; ?>

			<div class="noyona-trust-badges">
				<div class="noyona-trust-badge">
					<i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
					<span><?php esc_html_e( 'Secure Checkout', 'noyona' ); ?></span>
				</div>
				<div class="noyona-trust-badge">
					<i class="fa-solid fa-truck-fast" aria-hidden="true"></i>
					<span><?php esc_html_e( 'Fast Shipping', 'noyona' ); ?></span>
				</div>
				<div class="noyona-trust-badge">
					<i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
					<span><?php esc_html_e( 'Easy Returns', 'noyona' ); ?></span>
				</div>
			</div>

		</div><!-- /.noyona-checkout-col-right -->

	</div><!-- /.noyona-checkout-columns -->

</form>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
