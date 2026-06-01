<?php
/**
 * Customer processing order email.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 10.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$has_order = isset( $order ) && $order instanceof WC_Order;

if ( $has_order ) {
	$first_name = trim( (string) $order->get_billing_first_name() );
	if ( '' === $first_name ) {
		$first_name = trim( strtok( (string) $order->get_formatted_billing_full_name(), ' ' ) );
	}
	if ( '' === $first_name ) {
		$first_name = __( 'there', 'noyona-childtheme' );
	}

	$order_number    = $order->get_order_number();
	$order_date      = $order->get_date_created();
	$order_date_text = $order_date ? date_i18n( 'F j, Y', $order_date->getTimestamp() ) : '';
	$delivery_start  = $order_date ? date_i18n( 'M j', strtotime( '+6 days', $order_date->getTimestamp() ) ) : '';
	$delivery_end    = $order_date ? date_i18n( 'M j, Y', strtotime( '+8 days', $order_date->getTimestamp() ) ) : '';
	$payment_method  = $order->get_payment_method_title();

	$shipping_name = trim( $order->get_formatted_shipping_full_name() );
	if ( '' === $shipping_name ) {
		$shipping_name = trim( $order->get_formatted_billing_full_name() );
	}

	$shipping_address = $order->get_formatted_shipping_address();
	if ( ! $shipping_address ) {
		$shipping_address = $order->get_formatted_billing_address();
	}

	$shipping_phone = $order->get_shipping_phone();
	if ( ! $shipping_phone ) {
		$shipping_phone = $order->get_billing_phone();
	}
	$shipping_email = $order->get_billing_email();

	$order_items = array();
	foreach ( $order->get_items() as $item ) {
		$product_name  = $item->get_name();
		$order_items[] = array(
			'initial'  => strtoupper( substr( wp_strip_all_tags( $product_name ), 0, 1 ) ),
			'name'     => $product_name,
			'quantity' => $item->get_quantity(),
			'total'    => $order->get_formatted_line_subtotal( $item ),
		);
	}

	$subtotal = wc_price( $order->get_subtotal(), array( 'currency' => $order->get_currency() ) );
	$shipping = wc_price( (float) $order->get_shipping_total(), array( 'currency' => $order->get_currency() ) );
	$discount = wc_price( (float) $order->get_discount_total() * -1, array( 'currency' => $order->get_currency() ) );
	$total    = $order->get_formatted_order_total();
	if ( false === strpos( wp_strip_all_tags( $total ), $order->get_currency() ) ) {
		$total .= ' ' . esc_html( $order->get_currency() );
	}
} else {
	$first_name       = 'Ammarah';
	$order_number     = 'NOY-10234';
	$order_date_text  = 'May 29, 2026';
	$delivery_start   = 'Jun 4';
	$delivery_end     = 'Jun 6, 2026';
	$payment_method   = 'GCash';
	$shipping_name    = 'Ammarah Gail';
	$shipping_address = 'Burgundy Corporate Tower, 252 Sen. Gil Puyat Ave.,<br />Makati City, Metro Manila 1200';
	$shipping_phone   = '+63 917 555 0142';
	$shipping_email   = 'ammarah.gail@gmail.com';
	$order_items      = array(
		array(
			'initial'  => 'L',
			'name'     => 'Dewy Lip Color',
			'quantity' => 1,
			'total'    => '₱229.00',
		),
		array(
			'initial'  => 'C',
			'name'     => 'Soft Matte Concealer',
			'quantity' => 1,
			'total'    => '₱333.00',
		),
	);
	$subtotal = '₱562.00';
	$shipping = '₱51.00';
	$discount = '−₱0.00';
	$total    = '₱613.00 PHP';
}

$orders_url = wc_get_endpoint_url( 'orders', '', wc_get_page_permalink( 'myaccount' ) );
if ( ! $orders_url ) {
	$orders_url = home_url( '/my-account/orders/' );
}

do_action( 'woocommerce_email_header', '', $email ); ?>

<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation" class="noyona-email-card noyona-order-email">
	<tr>
		<td>
			<p class="noyona-order-kicker"><?php esc_html_e( 'ORDER IS BEING PROCESSED', 'noyona-childtheme' ); ?></p>
			<h1 class="noyona-email-title noyona-order-title"><?php esc_html_e( "We're preparing your order ✨", 'noyona-childtheme' ); ?></h1>
			<p class="noyona-email-copy noyona-order-copy">
				<?php
				printf(
					/* translators: 1: Customer first name, 2: Order number, 3: Order date, 4: Payment method. */
					esc_html__( "Hi %1$s, we received your order %2$s on %3$s and your payment via %4$s. We're carefully packing everything now and will email you tracking once it ships.", 'noyona-childtheme' ),
					esc_html( $first_name ),
					esc_html( $order_number ),
					esc_html( $order_date_text ),
					esc_html( $payment_method ? $payment_method : __( 'your selected payment method', 'noyona-childtheme' ) )
				);
				?>
			</p>

			<table border="0" cellpadding="0" cellspacing="0" role="presentation" class="noyona-email-button-wrap noyona-order-actions">
				<tr>
					<td>
						<a class="noyona-email-button" href="<?php echo esc_url( $orders_url ); ?>" target="_blank">
							<?php esc_html_e( 'Track your order', 'noyona-childtheme' ); ?>
						</a>
					</td>
				</tr>
			</table>

			<div class="noyona-delivery-card">
				<h2 class="noyona-email-section-title"><?php esc_html_e( 'DELIVERY DETAILS', 'noyona-childtheme' ); ?></h2>
				<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Name', 'noyona-childtheme' ); ?></th>
						<td><?php echo esc_html( $shipping_name ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Address', 'noyona-childtheme' ); ?></th>
						<td><?php echo wp_kses_post( $shipping_address ); ?></td>
					</tr>
					<?php if ( $shipping_phone ) : ?>
						<tr>
							<th scope="row"><?php esc_html_e( 'Phone', 'noyona-childtheme' ); ?></th>
							<td><?php echo esc_html( $shipping_phone ); ?></td>
						</tr>
					<?php endif; ?>
					<?php if ( $shipping_email ) : ?>
						<tr>
							<th scope="row"><?php esc_html_e( 'Email', 'noyona-childtheme' ); ?></th>
							<td><?php echo esc_html( $shipping_email ); ?></td>
						</tr>
					<?php endif; ?>
				</table>
			</div>

			<h2 class="noyona-email-section-title"><?php esc_html_e( 'YOUR PACKAGE', 'noyona-childtheme' ); ?></h2>
			<p class="noyona-delivery-estimate"><?php esc_html_e( 'Processing Time: 2–4 business days', 'noyona-childtheme' ); ?></p>
			<?php if ( $delivery_start && $delivery_end ) : ?>
				<p class="noyona-delivery-estimate">
					<?php
					printf(
						/* translators: 1: Delivery start date, 2: Delivery end date. */
						esc_html__( 'Estimated Delivery (1–3 days after dispatch): %1$s – %2$s', 'noyona-childtheme' ),
						esc_html( strtoupper( $delivery_start ) ),
						esc_html( strtoupper( $delivery_end ) )
					);
					?>
				</p>
			<?php endif; ?>

			<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation" class="noyona-order-summary">
				<?php foreach ( $order_items as $item ) : ?>
					<tr class="noyona-order-item">
						<td class="noyona-order-item-icon"><?php echo esc_html( $item['initial'] ); ?></td>
						<td class="noyona-order-item-name">
							<?php echo esc_html( $item['name'] ); ?>
							<span>× <?php echo esc_html( (string) $item['quantity'] ); ?></span>
						</td>
						<td class="noyona-order-item-total"><?php echo wp_kses_post( $item['total'] ); ?></td>
					</tr>
				<?php endforeach; ?>
			</table>

			<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation" class="noyona-order-totals">
				<tr>
					<th scope="row"><?php esc_html_e( 'Subtotal', 'noyona-childtheme' ); ?></th>
					<td><?php echo wp_kses_post( $subtotal ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Shipping', 'noyona-childtheme' ); ?></th>
					<td><?php echo wp_kses_post( $shipping ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Discount', 'noyona-childtheme' ); ?></th>
					<td><?php echo wp_kses_post( $discount ); ?></td>
				</tr>
				<tr class="noyona-order-total-row">
					<th scope="row"><?php esc_html_e( 'Total', 'noyona-childtheme' ); ?></th>
					<td><?php echo wp_kses_post( $total ); ?></td>
				</tr>
			</table>

			<div class="noyona-order-help">
				<h2 class="noyona-email-section-title"><?php esc_html_e( 'QUESTIONS ABOUT YOUR ORDER?', 'noyona-childtheme' ); ?></h2>
				<p>– <?php esc_html_e( 'How do I track my order?', 'noyona-childtheme' ); ?></p>
				<p>– <?php esc_html_e( 'Can I change my delivery details?', 'noyona-childtheme' ); ?></p>
				<p>– <?php esc_html_e( 'Our shipping & returns policy', 'noyona-childtheme' ); ?></p>
			</div>
		</td>
	</tr>
</table>

<?php
do_action( 'woocommerce_email_footer', $email );
