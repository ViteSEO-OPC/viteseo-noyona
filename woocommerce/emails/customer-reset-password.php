<?php
/**
 * Customer Reset Password email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-reset-password.php.
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

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

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

?>

<?php do_action( 'woocommerce_email_header', '', $email ); ?>

<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation" class="noyona-email-card">
	<tr>
		<td align="center">
			<h1 class="noyona-email-title"><?php esc_html_e( 'Reset your password', 'noyona-childtheme' ); ?></h1>
			<p class="noyona-email-greeting">
				<?php
				printf(
					/* translators: %s: Customer first name. */
					esc_html__( 'Hi %s,', 'noyona-childtheme' ),
					esc_html( $first_name )
				);
				?>
			</p>
			<p class="noyona-email-copy"><?php esc_html_e( 'We received a request to reset the password for your Noyona account. Use the link below to choose a new one. For your security, this link expires in 60 minutes.', 'noyona-childtheme' ); ?></p>

			<table border="0" cellpadding="0" cellspacing="0" role="presentation" class="noyona-email-button-wrap">
				<tr>
					<td align="center">
						<a class="noyona-email-button" href="<?php echo esc_url( $reset_url ); ?>" target="_blank">
							<?php esc_html_e( 'Reset your password', 'noyona-childtheme' ); ?>
						</a>
					</td>
				</tr>
			</table>

			<p class="noyona-email-store-link">
				<a href="<?php echo esc_url( $shop_url ); ?>" target="_blank"><?php esc_html_e( 'or visit our store →', 'noyona-childtheme' ); ?></a>
			</p>

			<p class="noyona-email-security-note"><?php esc_html_e( "Didn't request this? You can safely ignore this email — your password won't change until you create a new one.", 'noyona-childtheme' ); ?></p>
		</td>
	</tr>
</table>

<?php
do_action( 'woocommerce_email_footer', $email );
