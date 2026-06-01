<?php
/**
 * Customer cancelled order email.
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

	$order_number = $order->get_order_number();
	$order_items  = array();
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
	$total    = $order->get_formatted_order_total();
	if ( false === strpos( wp_strip_all_tags( $total ), $order->get_currency() ) ) {
		$total .= ' ' . esc_html( $order->get_currency() );
	}
} else {
	$first_name   = 'Ammarah';
	$order_number = 'NOY-10234';
	$order_items  = array(
		array(
			'initial'  => 'F',
			'name'     => 'Dual-Use Powder Foundation',
			'quantity' => 1,
			'total'    => '₱349.00',
		),
	);
	$subtotal = '₱349.00';
	$shipping = '₱51.00';
	$total    = '₱400.00 PHP';
}

$orders_url = wc_get_endpoint_url( 'orders', '', wc_get_page_permalink( 'myaccount' ) );
if ( ! $orders_url ) {
	$orders_url = home_url( '/my-account/orders/' );
}

do_action( 'woocommerce_email_header', '', $email ); ?>

<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation" class="noyona-email-card noyona-order-email noyona-cancelled-email">
	<tr>
		<td align="center">
			<p class="noyona-cancelled-status"><?php esc_html_e( 'CANCELLED', 'noyona-childtheme' ); ?></p>
			<h1 class="noyona-email-title noyona-cancelled-title"><?php esc_html_e( 'Your order has been cancelled', 'noyona-childtheme' ); ?></h1>
			<p class="noyona-email-copy noyona-cancelled-copy">
				<?php
				printf(
					/* translators: 1: Customer first name, 2: Order number. */
					esc_html__( "Hi %1$s, we've cancelled the order %2$s as requested. We're sorry this one didn't work out — we hope to see you again soon.", 'noyona-childtheme' ),
					esc_html( $first_name ),
					esc_html( $order_number )
				);
				?>
			</p>
			<p class="noyona-cancelled-refund-note"><?php esc_html_e( 'If you already paid, no worries — our team will contact you regarding your refund.', 'noyona-childtheme' ); ?></p>

			<table border="0" cellpadding="0" cellspacing="0" role="presentation" class="noyona-email-button-wrap noyona-order-actions noyona-cancelled-actions">
				<tr>
					<td>
						<a class="noyona-email-button" href="<?php echo esc_url( $orders_url ); ?>" target="_blank">
							<?php esc_html_e( 'View order', 'noyona-childtheme' ); ?>
						</a>
					</td>
					<td class="noyona-order-store-link-cell">
						<a class="noyona-order-store-link" href="<?php echo esc_url( home_url( '/shop/' ) ); ?>" target="_blank"><?php esc_html_e( 'or shop again', 'noyona-childtheme' ); ?></a>
					</td>
				</tr>
			</table>

			<h2 class="noyona-email-section-title"><?php esc_html_e( 'CANCELLED ITEMS', 'noyona-childtheme' ); ?></h2>
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
				<tr class="noyona-order-total-row">
					<th scope="row"><?php esc_html_e( 'Total', 'noyona-childtheme' ); ?></th>
					<td><?php echo wp_kses_post( $total ); ?></td>
				</tr>
			</table>

			<div class="noyona-order-help noyona-cancelled-help">
				<h2 class="noyona-email-section-title"><?php esc_html_e( 'NEED HELP?', 'noyona-childtheme' ); ?></h2>
				<p>– <?php esc_html_e( 'What are my refund options?', 'noyona-childtheme' ); ?></p>
				<p>– <?php esc_html_e( 'How soon will I receive my refund?', 'noyona-childtheme' ); ?></p>
				<p>– <?php esc_html_e( 'Can I reuse my voucher on a new order?', 'noyona-childtheme' ); ?></p>
			</div>
		</td>
	</tr>
</table>

<?php
do_action( 'woocommerce_email_footer', $email );
