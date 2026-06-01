<?php
/**
 * Customer processing order email (plain text).
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Plain
 * @version 9.9.0
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
		$order_items[] = array(
			'name'     => $item->get_name(),
			'quantity' => $item->get_quantity(),
			'total'    => wp_strip_all_tags( $order->get_formatted_line_subtotal( $item ) ),
		);
	}

	$subtotal = wp_strip_all_tags( wc_price( $order->get_subtotal(), array( 'currency' => $order->get_currency() ) ) );
	$shipping = wp_strip_all_tags( wc_price( (float) $order->get_shipping_total(), array( 'currency' => $order->get_currency() ) ) );
	$discount = wp_strip_all_tags( wc_price( (float) $order->get_discount_total() * -1, array( 'currency' => $order->get_currency() ) ) );
	$total    = wp_strip_all_tags( $order->get_formatted_order_total() );
	if ( false === strpos( $total, $order->get_currency() ) ) {
		$total .= ' ' . $order->get_currency();
	}
} else {
	$first_name       = 'Ammarah';
	$order_number     = 'NOY-10234';
	$order_date_text  = 'May 29, 2026';
	$delivery_start   = 'Jun 4';
	$delivery_end     = 'Jun 6, 2026';
	$payment_method   = 'GCash';
	$shipping_name    = 'Ammarah Gail';
	$shipping_address = 'Burgundy Corporate Tower, 252 Sen. Gil Puyat Ave., Makati City, Metro Manila 1200';
	$shipping_phone   = '+63 917 555 0142';
	$shipping_email   = 'ammarah.gail@gmail.com';
	$order_items      = array(
		array(
			'name'     => 'Dewy Lip Color',
			'quantity' => 1,
			'total'    => '₱229.00',
		),
		array(
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

echo "Noyona Essentials\n";
echo "BEAUTY ROOTED IN NATURE\n\n";

echo esc_html__( 'ORDER IS BEING PROCESSED', 'noyona-childtheme' ) . "\n";
echo esc_html__( "We're preparing your order", 'noyona-childtheme' ) . "\n\n";

printf(
	/* translators: 1: Customer first name, 2: Order number, 3: Order date, 4: Payment method. */
	esc_html__( "Hi %1$s, we received your order %2$s on %3$s and your payment via %4$s. We're carefully packing everything now and will email you tracking once it ships.", 'noyona-childtheme' ),
	esc_html( $first_name ),
	esc_html( $order_number ),
	esc_html( $order_date_text ),
	esc_html( $payment_method ? $payment_method : __( 'your selected payment method', 'noyona-childtheme' ) )
);
echo "\n\n";

echo esc_html__( 'Track your order:', 'noyona-childtheme' ) . ' ' . esc_url( $orders_url ) . "\n\n";

echo esc_html__( 'DELIVERY DETAILS', 'noyona-childtheme' ) . "\n";
echo esc_html__( 'Name', 'noyona-childtheme' ) . ': ' . esc_html( $shipping_name ) . "\n";
echo esc_html__( 'Address', 'noyona-childtheme' ) . ': ' . wp_strip_all_tags( $shipping_address ) . "\n";
if ( $shipping_phone ) {
	echo esc_html__( 'Phone', 'noyona-childtheme' ) . ': ' . esc_html( $shipping_phone ) . "\n";
}
if ( $shipping_email ) {
	echo esc_html__( 'Email', 'noyona-childtheme' ) . ': ' . esc_html( $shipping_email ) . "\n";
}
echo "\n";

echo esc_html__( 'YOUR PACKAGE', 'noyona-childtheme' ) . "\n";
echo esc_html__( 'Processing Time: 2–4 business days', 'noyona-childtheme' ) . "\n";
if ( $delivery_start && $delivery_end ) {
	printf(
		/* translators: 1: Delivery start date, 2: Delivery end date. */
		esc_html__( 'Estimated Delivery (1–3 days after dispatch): %1$s – %2$s', 'noyona-childtheme' ),
		esc_html( strtoupper( $delivery_start ) ),
		esc_html( strtoupper( $delivery_end ) )
	);
	echo "\n\n";
}

foreach ( $order_items as $item ) {
	echo esc_html( $item['name'] ) . ' x ' . esc_html( (string) $item['quantity'] ) . "\n";
	echo wp_kses_post( $item['total'] ) . "\n\n";
}

echo esc_html__( 'Subtotal', 'noyona-childtheme' ) . "\n";
echo wp_kses_post( $subtotal ) . "\n";
echo esc_html__( 'Shipping', 'noyona-childtheme' ) . "\n";
echo wp_kses_post( $shipping ) . "\n";
echo esc_html__( 'Discount', 'noyona-childtheme' ) . "\n";
echo wp_kses_post( $discount ) . "\n";
echo esc_html__( 'Total', 'noyona-childtheme' ) . "\n";
echo wp_kses_post( $total ) . "\n\n";

echo esc_html__( 'QUESTIONS ABOUT YOUR ORDER?', 'noyona-childtheme' ) . "\n";
echo '– ' . esc_html__( 'How do I track my order?', 'noyona-childtheme' ) . "\n";
echo '– ' . esc_html__( 'Can I change my delivery details?', 'noyona-childtheme' ) . "\n";
echo '– ' . esc_html__( 'Our shipping & returns policy', 'noyona-childtheme' ) . "\n\n";

echo "----------------------------------------\n\n";
echo "noyona\n";
echo "Beauty Rooted in Nature\n\n";
echo 'Shop ' . esc_url( home_url( '/shop/' ) ) . ' | About ' . esc_url( home_url( '/about-us/' ) ) . ' | Contact ' . esc_url( home_url( '/contact/' ) ) . ' | FAQ ' . esc_url( home_url( '/faq/' ) ) . "\n\n";
echo esc_html__( 'We accept GCash, Maya, Mastercard, Visa.', 'noyona-childtheme' ) . "\n";
echo esc_html__( "You're receiving this because you placed an order with Noyona Essentials.", 'noyona-childtheme' ) . "\n";
echo esc_html__( 'Questions? info@noyonacosmetics.com · Noyona Cosmetics & Skin Care Products OPC · Makati City, Philippines · © 2026 Noyona Essentials.', 'noyona-childtheme' );
