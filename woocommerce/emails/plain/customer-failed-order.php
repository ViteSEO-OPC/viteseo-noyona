<?php
/**
 * Customer failed order email (plain text).
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Plain
 * @version 9.8.0
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
	$pay_url      = $order->get_checkout_payment_url();
} else {
	$first_name   = 'Ammarah';
	$order_number = 'NOY-10234';
	$pay_url      = home_url( '/checkout/order-pay/10234/' );
}

$reference = strtolower( substr( md5( $order_number . '-noyona' ), 0, 8 ) ) . '-noy-2026';

echo "Noyona Essentials\n";
echo "BEAUTY ROOTED IN NATURE\n\n";
echo esc_html__( 'Payment failed', 'noyona-childtheme' ) . "\n\n";

printf(
	/* translators: 1: Customer first name, 2: Order number. */
	esc_html__( "Hi %1$s, we couldn't process your payment for order %2$s, so it hasn't been placed yet. Don't worry — no charge was made.", 'noyona-childtheme' ),
	esc_html( $first_name ),
	esc_html( $order_number )
);
echo "\n\n";

echo esc_html__( 'This can happen if a card was declined or the checkout session timed out. You can try again with the same or a different payment method.', 'noyona-childtheme' ) . "\n\n";
echo esc_html__( 'Try payment again:', 'noyona-childtheme' ) . ' ' . esc_url( $pay_url ) . "\n\n";
echo esc_html__( 'Still having trouble? Email us at info@noyonacosmetics.com', 'noyona-childtheme' ) . "\n\n";
printf(
	/* translators: %s: Failure reference code. */
	esc_html__( 'This is a system-generated notification. Ref: %s', 'noyona-childtheme' ),
	esc_html( $reference )
);
echo "\n\n";

echo "----------------------------------------\n\n";
echo "noyona\n";
echo "Beauty Rooted in Nature\n\n";
echo 'Shop ' . esc_url( home_url( '/shop/' ) ) . ' | About ' . esc_url( home_url( '/about-us/' ) ) . ' | Contact ' . esc_url( home_url( '/contact/' ) ) . ' | FAQ ' . esc_url( home_url( '/faq/' ) ) . "\n";
echo "Facebook /Noyonacosmetics | Instagram /noyonacosmetics | TikTok @noyona_cosmetics | Shopee /noyona_official | Lazada /noyona-lovial-essentials\n\n";
echo esc_html__( 'We accept GCash, Maya, Mastercard, Visa.', 'noyona-childtheme' ) . "\n";
echo esc_html__( "You're receiving this because a payment was attempted for an order at Noyona Essentials.", 'noyona-childtheme' ) . "\n";
echo esc_html__( 'Questions? info@noyonacosmetics.com · Noyona Cosmetics & Skin Care Products OPC · Makati City, Philippines · © 2026 Noyona Essentials.', 'noyona-childtheme' );
