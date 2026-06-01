<?php
/**
 * Customer completed order email (plain text).
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Plain
 * @version 9.9.0
 */

defined( 'ABSPATH' ) || exit;

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

$order_total = wp_strip_all_tags( $order->get_formatted_order_total() );
if ( false === strpos( $order_total, $order->get_currency() ) ) {
	$order_total .= ' ' . $order->get_currency();
}

echo "Noyona Essentials\n";
echo "BEAUTY ROOTED IN NATURE\n\n";
echo esc_html__( 'DELIVERED', 'noyona-childtheme' ) . "\n";
echo esc_html__( 'Your order has been delivered', 'noyona-childtheme' ) . "\n\n";

printf(
	/* translators: 1: Customer first name, 2: Order number, 3: Delivered date. */
	esc_html__( 'Hi %1$s, your order %2$s was delivered on %3$s. We hope you love every piece — your glow is rooted in nature.', 'noyona-childtheme' ),
	esc_html( $first_name ),
	esc_html( $order->get_order_number() ),
	esc_html( $completed_date_text )
);
echo "\n\n";

echo esc_html__( 'Write a review:', 'noyona-childtheme' ) . ' ' . esc_url( $orders_url ) . "\n\n";

echo esc_html__( 'ORDER DETAILS', 'noyona-childtheme' ) . "\n";
echo esc_html__( 'Order ID', 'noyona-childtheme' ) . "\n";
echo esc_html( $order->get_order_number() ) . "\n";
echo esc_html__( 'Order date', 'noyona-childtheme' ) . "\n";
echo esc_html( $order_date_text ) . "\n";
echo esc_html__( 'Delivered', 'noyona-childtheme' ) . "\n";
echo esc_html( $completed_date_text ) . "\n\n";

foreach ( $order->get_items() as $item ) {
	echo esc_html( $item->get_name() ) . ' x ' . esc_html( $item->get_quantity() ) . "\n";
	echo wp_strip_all_tags( $order->get_formatted_line_subtotal( $item ) ) . "\n\n";
}

echo esc_html__( 'Subtotal', 'noyona-childtheme' ) . "\n";
echo wp_strip_all_tags( wc_price( $order->get_subtotal(), array( 'currency' => $order->get_currency() ) ) ) . "\n";
echo esc_html__( 'Shipping', 'noyona-childtheme' ) . "\n";
echo wp_strip_all_tags( wc_price( (float) $order->get_shipping_total(), array( 'currency' => $order->get_currency() ) ) ) . "\n";
echo esc_html__( 'Total', 'noyona-childtheme' ) . "\n";
echo esc_html( $order_total ) . "\n\n";

echo esc_html__( "WHAT'S NEXT?", 'noyona-childtheme' ) . "\n";
echo esc_html__( 'Not quite right? You can request a return or refund within our returns window, as long as items are unused and in original packaging.', 'noyona-childtheme' ) . "\n";
echo esc_html__( 'Start a return or refund:', 'noyona-childtheme' ) . ' ' . esc_url( $returns_url ) . "\n\n";

echo "----------------------------------------\n\n";
echo "noyona\n";
echo "Beauty Rooted in Nature\n\n";
echo 'Shop ' . esc_url( home_url( '/shop/' ) ) . ' | About ' . esc_url( home_url( '/about-us/' ) ) . ' | Contact ' . esc_url( home_url( '/contact/' ) ) . ' | FAQ ' . esc_url( home_url( '/faq/' ) ) . "\n\n";
echo esc_html__( 'We accept GCash, Maya, Mastercard, Visa.', 'noyona-childtheme' ) . "\n";
echo esc_html__( "You're receiving this because your Noyona Essentials order was delivered.", 'noyona-childtheme' ) . "\n";
echo esc_html__( 'Questions? info@noyonacosmetics.com · Noyona Cosmetics & Skin Care Products OPC · Makati City, Philippines · © 2026 Noyona Essentials.', 'noyona-childtheme' );
