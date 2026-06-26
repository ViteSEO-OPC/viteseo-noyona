<?php
/**
 * Email Addresses
 *
 * Noyona customization: renders billing / shipping in the branded
 * "noyona-customer-info" two-column block so it matches the rest of the
 * custom email designs. Only $order and $sent_to_admin are passed to this
 * template by WooCommerce.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 10.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$address       = $order->get_formatted_billing_address();
$shipping      = $order->get_formatted_shipping_address();
$show_shipping = ! wc_ship_to_billing_address_only() && $order->needs_shipping_address() && $shipping;
?>

<h2 class="noyona-email-section-title"><?php esc_html_e( 'DELIVERY DETAILS', 'noyona-childtheme' ); ?></h2>
<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation" class="noyona-customer-info">
	<tr>
		<td<?php echo $show_shipping ? '' : ' style="width: 100%;"'; ?>>
			<h3><?php esc_html_e( 'Billing address', 'noyona-childtheme' ); ?></h3>
			<p><?php echo wp_kses_post( $address ? $address : esc_html__( 'N/A', 'woocommerce' ) ); ?></p>
			<?php if ( $order->get_billing_phone() ) : ?>
				<p><?php echo esc_html( $order->get_billing_phone() ); ?></p>
			<?php endif; ?>
			<?php if ( $order->get_billing_email() ) : ?>
				<p><?php echo esc_html( $order->get_billing_email() ); ?></p>
			<?php endif; ?>
			<?php
			/**
			 * Fires after the core address fields in emails.
			 *
			 * @since 8.6.0
			 *
			 * @param string   $type          Address type. Either 'billing' or 'shipping'.
			 * @param WC_Order $order         Order instance.
			 * @param bool     $sent_to_admin If this email is being sent to the admin or not.
			 * @param bool     $plain_text    If this email is plain text or not.
			 */
			do_action( 'woocommerce_email_customer_address_section', 'billing', $order, $sent_to_admin, false );
			?>
		</td>
		<?php if ( $show_shipping ) : ?>
			<td>
				<h3><?php esc_html_e( 'Shipping address', 'noyona-childtheme' ); ?></h3>
				<p><?php echo wp_kses_post( $shipping ); ?></p>
				<?php if ( $order->get_shipping_phone() ) : ?>
					<p><?php echo esc_html( $order->get_shipping_phone() ); ?></p>
				<?php endif; ?>
				<?php
				/** This action is documented above. */
				do_action( 'woocommerce_email_customer_address_section', 'shipping', $order, $sent_to_admin, false );
				?>
			</td>
		<?php endif; ?>
	</tr>
</table>
