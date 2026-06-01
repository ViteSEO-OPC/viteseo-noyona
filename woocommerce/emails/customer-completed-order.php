<?php
/**
 * Customer completed order email.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 10.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$first_name = trim( (string) $order->get_billing_first_name() );
if ( '' === $first_name ) {
	$first_name = trim( strtok( (string) $order->get_formatted_billing_full_name(), ' ' ) );
}
if ( '' === $first_name ) {
	$first_name = __( 'there', 'noyona-childtheme' );
}

$orders_url = wc_get_endpoint_url( 'orders', '', wc_get_page_permalink( 'myaccount' ) );
if ( ! $orders_url ) {
	$orders_url = home_url( '/my-account/orders/' );
}

$returns_url    = home_url( '/terms-and-policies/' );
$order_date     = $order->get_date_created();
$completed_date = $order->get_date_completed();
if ( ! $completed_date ) {
	$completed_date = $order->get_date_modified();
}
if ( ! $completed_date ) {
	$completed_date = $order_date;
}

$order_date_text     = $order_date ? date_i18n( 'F j, Y', $order_date->getTimestamp() ) : '';
$completed_date_text = $completed_date ? date_i18n( 'F j, Y', $completed_date->getTimestamp() ) : '';

$order_total = $order->get_formatted_order_total();
if ( false === strpos( wp_strip_all_tags( $order_total ), $order->get_currency() ) ) {
	$order_total .= ' ' . esc_html( $order->get_currency() );
}

do_action( 'woocommerce_email_header', '', $email ); ?>

<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation" class="noyona-email-card noyona-order-email noyona-delivered-email">
	<tr>
		<td align="center">
			<div class="noyona-delivered-check">✓</div>
			<div class="noyona-delivered-status-container"><p class="noyona-delivered-status"><?php esc_html_e( 'DELIVERED', 'noyona-childtheme' ); ?></p></div>
			<h1 class="noyona-email-title noyona-delivered-title"><?php esc_html_e( 'Your order has been delivered 🌿', 'noyona-childtheme' ); ?></h1>
			<p class="noyona-email-copy noyona-delivered-copy">
				<?php
				printf(
					/* translators: 1: Customer first name, 2: Order number, 3: Delivered date. */
					esc_html__( 'Hi %1$s, your order %2$s was delivered on %3$s. We hope you love every piece — your glow is rooted in nature.', 'noyona-childtheme' ),
					esc_html( $first_name ),
					esc_html( $order->get_order_number() ),
					esc_html( $completed_date_text )
				);
				?>
			</p>

			<table border="0" cellpadding="0" cellspacing="0" role="presentation" class="noyona-email-button-wrap noyona-order-actions noyona-delivered-actions">
				<tr>
					<td>
						<a class="noyona-email-button" href="<?php echo esc_url( $orders_url ); ?>" target="_blank">
							<?php esc_html_e( 'Write a review', 'noyona-childtheme' ); ?>
						</a>
					</td>
					<td class="noyona-order-store-link-cell">
						<a class="noyona-order-store-link" href="<?php echo esc_url( home_url( '/shop/' ) ); ?>" target="_blank"><?php esc_html_e( 'or shop again', 'noyona-childtheme' ); ?></a>
					</td>
				</tr>
			</table>

			<h2 class="noyona-email-section-title"><?php esc_html_e( 'ORDER DETAILS', 'noyona-childtheme' ); ?></h2>
			<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation" class="noyona-delivered-details">
				<tr>
					<th scope="row"><?php esc_html_e( 'Order ID', 'noyona-childtheme' ); ?></th>
					<td><?php echo esc_html( $order->get_order_number() ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Order date', 'noyona-childtheme' ); ?></th>
					<td><?php echo esc_html( $order_date_text ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Delivered', 'noyona-childtheme' ); ?></th>
					<td><?php echo esc_html( $completed_date_text ); ?></td>
				</tr>
			</table>

			<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation" class="noyona-order-summary">
				<?php foreach ( $order->get_items() as $item ) : ?>
					<?php
					$product_name = $item->get_name();
					$initial      = strtoupper( substr( wp_strip_all_tags( $product_name ), 0, 1 ) );
					?>
					<tr class="noyona-order-item">
						<td class="noyona-order-item-icon"><?php echo esc_html( $initial ); ?></td>
						<td class="noyona-order-item-name">
							<?php echo esc_html( $product_name ); ?>
							<span>× <?php echo esc_html( $item->get_quantity() ); ?></span>
						</td>
						<td class="noyona-order-item-total"><?php echo wp_kses_post( $order->get_formatted_line_subtotal( $item ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</table>

			<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation" class="noyona-order-totals">
				<tr>
					<th scope="row"><?php esc_html_e( 'Subtotal', 'noyona-childtheme' ); ?></th>
					<td><?php echo wp_kses_post( wc_price( $order->get_subtotal(), array( 'currency' => $order->get_currency() ) ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Shipping', 'noyona-childtheme' ); ?></th>
					<td><?php echo wp_kses_post( wc_price( (float) $order->get_shipping_total(), array( 'currency' => $order->get_currency() ) ) ); ?></td>
				</tr>
				<tr class="noyona-order-total-row">
					<th scope="row"><?php esc_html_e( 'Total', 'noyona-childtheme' ); ?></th>
					<td><?php echo wp_kses_post( $order_total ); ?></td>
				</tr>
			</table>

			<div class="noyona-whats-next">
				<h2 class="noyona-email-section-title"><?php esc_html_e( "WHAT'S NEXT?", 'noyona-childtheme' ); ?></h2>
				<p><?php esc_html_e( 'Not quite right? You can request a return or refund within our returns window, as long as items are unused and in original packaging.', 'noyona-childtheme' ); ?></p>
				<p class="noyona-whats-next-link"><a href="<?php echo esc_url( $returns_url ); ?>" target="_blank"><?php esc_html_e( 'Start a return or refund →', 'noyona-childtheme' ); ?></a></p>
			</div>
		</td>
	</tr>
</table>

<?php
do_action( 'woocommerce_email_footer', $email );
