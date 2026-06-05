<?php
/**
 * Lost password reset form (Noyona auth design parity).
 *
 * Overrides woocommerce/templates/myaccount/form-reset-password.php so the
 * "enter a new password" screen matches the existing Login / Register /
 * Forgot Password design (reuses the .noyona-lost-password-* card styles).
 *
 * Visual/UI only: the form fields (password_1, password_2), hidden inputs
 * (reset_key, reset_login, wc_reset_password), nonce and every WooCommerce
 * action hook are preserved exactly, so reset functionality is unchanged.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.2.0
 */

defined( 'ABSPATH' ) || exit;

$account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' );

do_action( 'woocommerce_before_reset_password_form' );
?>

<form method="post" class="woocommerce-ResetPassword lost_reset_password noyona-lost-password-form noyona-reset-password-form" novalidate>
	<div class="noyona-lost-password-head">
		<a class="noyona-lost-password-back" href="<?php echo esc_url( $account_url ); ?>">
			<i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
			<span><?php esc_html_e( 'Reset Password', 'noyona-childtheme' ); ?></span>
		</a>
	</div>

	<p class="noyona-lost-password-copy">
		<?php echo apply_filters( 'woocommerce_reset_password_message', esc_html__( 'Enter a new password below.', 'woocommerce' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</p>

	<p class="woocommerce-form-row woocommerce-form-row--first form-row form-row-first">
		<label for="password_1"><?php esc_html_e( 'New password', 'woocommerce' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e( 'Required', 'woocommerce' ); ?></span></label>
		<input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password_1" id="password_1" autocomplete="new-password" required aria-required="true" placeholder="<?php esc_attr_e( 'Enter your new password', 'noyona-childtheme' ); ?>" />
	</p>
	<p class="woocommerce-form-row woocommerce-form-row--last form-row form-row-last">
		<label for="password_2"><?php esc_html_e( 'Re-enter new password', 'woocommerce' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e( 'Required', 'woocommerce' ); ?></span></label>
		<input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password_2" id="password_2" autocomplete="new-password" required aria-required="true" placeholder="<?php esc_attr_e( 'Re-enter your new password', 'noyona-childtheme' ); ?>" />
	</p>

	<input type="hidden" name="reset_key" value="<?php echo esc_attr( $args['key'] ); ?>" />
	<input type="hidden" name="reset_login" value="<?php echo esc_attr( $args['login'] ); ?>" />

	<div class="clear"></div>

	<?php do_action( 'woocommerce_resetpassword_form' ); ?>

	<div class="noyona-lost-password-actions">
		<a class="noyona-lost-password-cancel" href="<?php echo esc_url( $account_url ); ?>"><?php esc_html_e( 'Cancel', 'noyona-childtheme' ); ?></a>
		<button type="submit" class="woocommerce-Button button noyona-lost-password-submit" value="<?php esc_attr_e( 'Save', 'woocommerce' ); ?>"><?php esc_html_e( 'Save', 'woocommerce' ); ?></button>
	</div>

	<input type="hidden" name="wc_reset_password" value="true" />
	<?php wp_nonce_field( 'reset_password', 'woocommerce-reset-password-nonce' ); ?>
</form>

<?php do_action( 'woocommerce_after_reset_password_form' ); ?>
