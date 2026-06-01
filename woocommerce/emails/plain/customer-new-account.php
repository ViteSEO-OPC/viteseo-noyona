<?php
/**
 * Customer new account email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/customer-new-account.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Plain
 * @version 10.0.0
 */

defined( 'ABSPATH' ) || exit;

$account_user = null;
if ( $email && ! empty( $email->object ) && $email->object instanceof WP_User ) {
	$account_user = $email->object;
} elseif ( ! empty( $user_email ) ) {
	$account_user = get_user_by( 'email', $user_email );
} elseif ( ! empty( $user_login ) ) {
	$account_user = get_user_by( 'login', $user_login );
}

$first_name = '';
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

printf(
	/* translators: %s: Customer first name. */
	esc_html__( 'Welcome to Noyona, %s!', 'noyona-childtheme' ),
	esc_html( $first_name )
);
echo "\n\n";

printf(
	/* translators: %s: Customer first name. */
	esc_html__( 'Hi %s,', 'noyona-childtheme' ),
	esc_html( $first_name )
);
echo "\n\n";

echo esc_html__( 'Welcome to Noyona — beauty rooted in nature.', 'noyona-childtheme' ) . "\n\n";
echo esc_html__( 'Your account is all set. Next time you shop with us, just log in for a faster checkout, plus early access to new shades, restocks, and members-only treats.', 'noyona-childtheme' ) . "\n\n";
echo esc_html__( 'Start shopping:', 'noyona-childtheme' ) . ' ' . esc_url( $shop_url ) . "\n\n";
echo esc_html__( 'Vegan · Cruelty-free · Paraben-free', 'noyona-childtheme' ) . "\n\n";

// Only send the set new password link if the user hasn't set their password during sign-up.
if ( $password_generated && $set_password_url ) {
	echo esc_html__( 'Set your account password:', 'noyona-childtheme' ) . ' ' . esc_url( $set_password_url ) . "\n\n";
}

echo "\n\n----------------------------------------\n\n";
echo 'Shop ' . esc_url( home_url( '/shop/' ) ) . ' | About ' . esc_url( home_url( '/about-us/' ) ) . ' | Contact ' . esc_url( home_url( '/contact/' ) ) . ' | FAQ ' . esc_url( home_url( '/faq/' ) ) . "\n\n";
echo esc_html__( 'We accept GCash, Maya, Mastercard, Visa.', 'noyona-childtheme' ) . "\n";
echo esc_html__( "You're receiving this because an account was created with this email at Noyona Essentials.", 'noyona-childtheme' ) . "\n";
echo esc_html__( 'Questions? info@noyonacosmetics.com · Noyona Cosmetics & Skin Care Products OPC · Makati City, Philippines · © 2026 Noyona Essentials.', 'noyona-childtheme' );
