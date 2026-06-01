<?php
/**
 * Customer new account email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-new-account.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 10.4.0
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

/**
 * Fires to output the email header.
 *
 * @hooked WC_Emails::email_header()
 *
 * @since 3.7.0
 */
do_action( 'woocommerce_email_header', '', $email ); ?>

<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation" class="noyona-email-card">
	<tr>
		<td align="center">
			<h1 class="noyona-email-title">
				<?php
				printf(
					/* translators: %s: Customer first name. */
					esc_html__( 'Welcome to Noyona, %s!', 'noyona-childtheme' ),
					esc_html( $first_name )
				);
				?>
			</h1>
			<p class="noyona-email-greeting">
				<?php
				printf(
					/* translators: %s: Customer first name. */
					esc_html__( 'Hi %s,', 'noyona-childtheme' ),
					esc_html( $first_name )
				);
				?>
			</p>
			<p class="noyona-email-lede"><?php esc_html_e( 'Welcome to Noyona — beauty rooted in nature.', 'noyona-childtheme' ); ?></p>
			<p class="noyona-email-copy"><?php esc_html_e( 'Your account is all set. Next time you shop with us, just log in for a faster checkout, plus early access to new shades, restocks, and members-only treats.', 'noyona-childtheme' ); ?></p>

			<table border="0" cellpadding="0" cellspacing="0" role="presentation" class="noyona-email-button-wrap">
				<tr>
					<td align="center">
						<a class="noyona-email-button" href="<?php echo esc_url( $shop_url ); ?>" target="_blank">
							<?php esc_html_e( 'Start shopping', 'noyona-childtheme' ); ?>
						</a>
					</td>
				</tr>
			</table>

			<div class="noyona-email-pill"><?php esc_html_e( 'VEGAN · CRUELTY-FREE · PARABEN-FREE', 'noyona-childtheme' ); ?></div>

			<?php if ( $password_generated && $set_password_url ) : ?>
				<p class="noyona-email-small">
					<a href="<?php echo esc_url( $set_password_url ); ?>" target="_blank"><?php esc_html_e( 'Set your account password', 'noyona-childtheme' ); ?></a>
				</p>
			<?php endif; ?>
		</td>
	</tr>
</table>

<?php
/**
 * Fires to output the email footer.
 *
 * @hooked WC_Emails::email_footer()
 *
 * @since 3.7.0
 */
do_action( 'woocommerce_email_footer', $email );
