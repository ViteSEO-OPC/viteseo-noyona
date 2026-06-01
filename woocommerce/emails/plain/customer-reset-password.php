<?php
/**
 * Customer Reset Password email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/customer-reset-password.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Plain
 * @version 9.8.0
 */

defined( 'ABSPATH' ) || exit;

$reset_url = add_query_arg(
	array(
		'key'   => $reset_key,
		'id'    => $user_id,
		'login' => rawurlencode( $user_login ),
	),
	wc_get_endpoint_url( 'lost-password', '', wc_get_page_permalink( 'myaccount' ) )
);

$account_user = get_user_by( 'id', $user_id );
$first_name   = '';

if ( $account_user instanceof WP_User ) {
	$first_name = trim( (string) $account_user->first_name );
	if ( '' === $first_name ) {
		$first_name = trim( strtok( (string) $account_user->display_name, ' ' ) );
	}
}
if ( '' === $first_name ) {
	$first_name = trim( (string) $user_login );
}

$shop_url = wc_get_page_permalink( 'shop' );
if ( ! $shop_url ) {
	$shop_url = home_url( '/shop/' );
}

echo "Noyona Essentials\n";
echo "BEAUTY ROOTED IN NATURE\n\n";
echo esc_html__( 'Reset your password', 'noyona-childtheme' ) . "\n\n";

printf(
	/* translators: %s: Customer first name. */
	esc_html__( 'Hi %s,', 'noyona-childtheme' ),
	esc_html( $first_name )
);
echo "\n\n";

echo esc_html__( 'We received a request to reset the password for your Noyona account. Use the link below to choose a new one. For your security, this link expires in 60 minutes.', 'noyona-childtheme' ) . "\n\n";
echo esc_html__( 'Reset your password:', 'noyona-childtheme' ) . ' ' . esc_url( $reset_url ) . "\n\n";
echo esc_html__( 'Or visit our store:', 'noyona-childtheme' ) . ' ' . esc_url( $shop_url ) . "\n\n";
echo esc_html__( "Didn't request this? You can safely ignore this email — your password won't change until you create a new one.", 'noyona-childtheme' ) . "\n\n";

echo "----------------------------------------\n\n";
echo "noyona\n";
echo "Beauty Rooted in Nature\n\n";
echo "Facebook /Noyonacosmetics | Instagram /noyonacosmetics | TikTok @noyona_cosmetics | Shopee /noyona_official | Lazada /noyona-lovial-essentials\n\n";
echo esc_html__( 'We accept GCash, Maya, Mastercard, Visa.', 'noyona-childtheme' ) . "\n";
echo esc_html__( "You're receiving this because a password reset was requested for your Noyona account.", 'noyona-childtheme' ) . "\n";
echo esc_html__( 'Questions? info@noyonacosmetics.com · Noyona Cosmetics & Skin Care Products OPC · Makati City, Philippines · © 2026 Noyona Essential', 'noyona-childtheme' );
