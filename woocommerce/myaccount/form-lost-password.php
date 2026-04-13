<?php
/**
 * Lost password form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/form-lost-password.php.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.0.0
 */

defined( 'ABSPATH' ) || exit;

$account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' );
?>

<form method="post" class="woocommerce-ResetPassword lost_reset_password noyona-lost-password-form">
	<div class="noyona-lost-password-head">
		<a class="noyona-lost-password-back" href="<?php echo esc_url( $account_url ); ?>">
			<i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
			<span><?php esc_html_e( 'Forgot Password?', 'noyona-childtheme' ); ?></span>
		</a>
	</div>

	<p class="noyona-lost-password-copy">
		<?php esc_html_e( 'Enter the email address associated with your account and we will send you a link to reset your password.', 'noyona-childtheme' ); ?>
	</p>

	<p class="woocommerce-form-row woocommerce-form-row--first form-row form-row-first">
		<label for="user_login"><?php esc_html_e( 'Email Address', 'noyona-childtheme' ); ?></label>
		<input class="woocommerce-Input woocommerce-Input--text input-text" type="text" name="user_login" id="user_login" autocomplete="username" />
	</p>

	<div class="noyona-lost-password-actions">
		<a class="noyona-lost-password-cancel" href="<?php echo esc_url( $account_url ); ?>"><?php esc_html_e( 'Cancel', 'noyona-childtheme' ); ?></a>
		<button type="submit" class="woocommerce-Button button noyona-lost-password-submit"><?php esc_html_e( 'Send Reset Link', 'noyona-childtheme' ); ?></button>
	</div>

	<input type="hidden" name="wc_reset_password" value="true" />
	<?php wp_nonce_field( 'lost_password', 'woocommerce-lost-password-nonce' ); ?>
	<?php do_action( 'woocommerce_lostpassword_form' ); ?>

	<!-- <p class="noyona-lost-password-try">
		<a href="<?php echo esc_url( $account_url ); ?>"><?php esc_html_e( 'Try Another way', 'noyona-childtheme' ); ?></a>
	</p> -->
</form>
