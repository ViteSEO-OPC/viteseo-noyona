<?php
/**
 * Customer cancelled order email (plain text).
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Plain
 * @version 10.0.0
 */

defined( 'ABSPATH' ) || exit;

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
		$order_items[] = array(
			'name'     => $item->get_name(),
			'quantity' => $item->get_quantity(),
			'total'    => wp_strip_all_tags( $order->get_formatted_line_subtotal( $item ) ),
		);
	}

	$subtotal = wp_strip_all_tags( wc_price( $order->get_subtotal(), array( 'currency' => $order->get_currency() ) ) );
	$shipping = wp_strip_all_tags( wc_price( (float) $order->get_shipping_total(), array( 'currency' => $order->get_currency() ) ) );
	$total    = wp_strip_all_tags( $order->get_formatted_order_total() );
	if ( false === strpos( $total, $order->get_currency() ) ) {
		$total .= ' ' . $order->get_currency();
	}
} else {
	$first_name   = 'Ammarah';
	$order_number = 'NOY-10234';
	$order_items  = array(
		array(
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

echo "Noyona Essentials\n";
echo "BEAUTY ROOTED IN NATURE\n\n";
echo esc_html__( 'CANCELLED', 'noyona-childtheme' ) . "\n";
echo esc_html__( 'Your order has been cancelled', 'noyona-childtheme' ) . "\n\n";

printf(
	/* translators: 1: Customer first name, 2: Order number. */
	esc_html__( "Hi %1$s, we've cancelled the order %2$s as requested. We're sorry this one didn't work out — we hope to see you again soon.", 'noyona-childtheme' ),
	esc_html( $first_name ),
	esc_html( $order_number )
);
echo "\n\n";

echo esc_html__( 'If you already paid, no worries — our team will contact you regarding your refund.', 'noyona-childtheme' ) . "\n\n";
echo esc_html__( 'View order:', 'noyona-childtheme' ) . ' ' . esc_url( $orders_url ) . "\n\n";

echo esc_html__( 'CANCELLED ITEMS', 'noyona-childtheme' ) . "\n";
foreach ( $order_items as $item ) {
	echo esc_html( $item['name'] ) . ' x ' . esc_html( (string) $item['quantity'] ) . "\n";
	echo wp_kses_post( $item['total'] ) . "\n\n";
}

echo esc_html__( 'Subtotal', 'noyona-childtheme' ) . "\n";
echo wp_kses_post( $subtotal ) . "\n";
echo esc_html__( 'Shipping', 'noyona-childtheme' ) . "\n";
echo wp_kses_post( $shipping ) . "\n";
echo esc_html__( 'Total', 'noyona-childtheme' ) . "\n";
echo wp_kses_post( $total ) . "\n\n";

echo esc_html__( 'NEED HELP?', 'noyona-childtheme' ) . "\n";
echo '– ' . esc_html__( 'What are my refund options?', 'noyona-childtheme' ) . "\n";
echo '– ' . esc_html__( 'How soon will I receive my refund?', 'noyona-childtheme' ) . "\n";
echo '– ' . esc_html__( 'Can I reuse my voucher on a new order?', 'noyona-childtheme' ) . "\n\n";

echo "----------------------------------------\n\n";
echo "noyona\n";
echo "Beauty Rooted in Nature\n\n";
echo 'Shop ' . esc_url( home_url( '/shop/' ) ) . ' | About ' . esc_url( home_url( '/about-us/' ) ) . ' | Contact ' . esc_url( home_url( '/contact/' ) ) . ' | FAQ ' . esc_url( home_url( '/faq/' ) ) . "\n\n";
echo esc_html__( 'We accept GCash, Maya, Mastercard, Visa.', 'noyona-childtheme' ) . "\n";
echo esc_html__( "You're receiving this because an order at Noyona Essentials was cancelled.", 'noyona-childtheme' ) . "\n";
echo esc_html__( 'Questions? info@noyonacosmetics.com · Noyona Cosmetics & Skin Care Products OPC · Makati City, Philippines · © 2026 Noyona Essentials.', 'noyona-childtheme' );
