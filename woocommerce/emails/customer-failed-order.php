<?php
/**
 * Customer failed order email.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 10.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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

do_action( 'woocommerce_email_header', '', $email ); ?>

<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation" class="noyona-email-card noyona-payment-failed-email">
	<tr>
		<td align="center">
			<div class="noyona-failed-icon">!</div>
			<h1 class="noyona-email-title noyona-failed-title"><?php esc_html_e( 'Payment failed', 'noyona-childtheme' ); ?></h1>
			<p class="noyona-email-copy noyona-failed-copy">
				<?php
				printf(
					/* translators: 1: Customer first name, 2: Order number. */
					esc_html__( "Hi %1$s, we couldn't process your payment for order %2$s, so it hasn't been placed yet. Don't worry — no charge was made.", 'noyona-childtheme' ),
					esc_html( $first_name ),
					esc_html( $order_number )
				);
				?>
			</p>
			<p class="noyona-email-copy noyona-failed-copy"><?php esc_html_e( 'This can happen if a card was declined or the checkout session timed out. You can try again with the same or a different payment method.', 'noyona-childtheme' ); ?></p>

			<table border="0" cellpadding="0" cellspacing="0" role="presentation" class="noyona-email-button-wrap noyona-failed-actions">
				<tr>
					<td align="center">
						<a class="noyona-email-button" href="<?php echo esc_url( $pay_url ); ?>" target="_blank">
							<?php esc_html_e( 'Try payment again', 'noyona-childtheme' ); ?>
						</a>
					</td>
				</tr>
			</table>

			<p class="noyona-failed-support">
				<?php esc_html_e( 'Still having trouble? Email us at', 'noyona-childtheme' ); ?>
				<a href="mailto:info@noyonacosmetics.com">info@noyonacosmetics.com</a>
			</p>
			<p class="noyona-failed-ref">
				<?php
				printf(
					/* translators: %s: Failure reference code. */
					esc_html__( 'This is a system-generated notification. Ref: %s', 'noyona-childtheme' ),
					esc_html( $reference )
				);
				?>
			</p>
		</td>
	</tr>
</table>

<?php
do_action( 'woocommerce_email_footer', $email );
