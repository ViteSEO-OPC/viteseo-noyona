<?php
/**
 * Order details table shown in emails.
 *
 * Noyona customization: instead of the default bordered WooCommerce order
 * table, this renders the branded "noyona-order-summary" / "noyona-order-totals"
 * markup so that every email which uses the shared order-details partial
 * (on-hold, refunded, invoice, customer note and the admin order emails) matches
 * the look of the custom processing / completed / cancelled emails.
 *
 * The dynamic data is still read straight from the order, and the
 * woocommerce_email_before/after_order_table hooks are preserved so plugins and
 * structured data continue to work.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 10.7.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Action hook to add custom content before order details in email.
 *
 * @param WC_Order $order Order object.
 * @param bool     $sent_to_admin Whether it's sent to admin or customer.
 * @param bool     $plain_text Whether it's a plain text email.
 * @param WC_Email $email Email object.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_before_order_table', $order, $sent_to_admin, $plain_text, $email );

$noyona_items       = $order->get_items();
$noyona_totals      = $order->get_order_item_totals();
$noyona_total_count = is_array( $noyona_totals ) ? count( $noyona_totals ) : 0;
$noyona_date        = $order->get_date_created();
?>

<h2 class="noyona-email-section-title"><?php esc_html_e( 'ORDER SUMMARY', 'noyona-childtheme' ); ?></h2>
<p class="noyona-order-meta-line">
	<?php
	printf(
		/* translators: 1: Order number, 2: Order date. */
		esc_html__( 'Order #%1$s · %2$s', 'noyona-childtheme' ),
		esc_html( $order->get_order_number() ),
		esc_html( $noyona_date ? wc_format_datetime( $noyona_date ) : '' )
	);
	?>
</p>

<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation" class="noyona-order-summary">
	<?php
	foreach ( $noyona_items as $item_id => $item ) :
		if ( ! apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {
			continue;
		}
		$product      = $item->get_product();
		$product_name = $item->get_name();
		$initial      = strtoupper( substr( wp_strip_all_tags( $product_name ), 0, 1 ) );
		$sku          = ( $sent_to_admin && $product && $product->get_sku() ) ? $product->get_sku() : '';
		?>
		<tr class="noyona-order-item">
			<td class="noyona-order-item-icon"><?php echo esc_html( $initial ); ?></td>
			<td class="noyona-order-item-name">
				<?php echo esc_html( $product_name ); ?>
				<?php if ( $sku ) : ?>
					<span class="noyona-order-item-sku">(#<?php echo esc_html( $sku ); ?>)</span>
				<?php endif; ?>
				<span>× <?php echo esc_html( (string) $item->get_quantity() ); ?></span>
			</td>
			<td class="noyona-order-item-total"><?php echo wp_kses_post( $order->get_formatted_line_subtotal( $item ) ); ?></td>
		</tr>
	<?php endforeach; ?>
</table>

<?php if ( $noyona_totals ) : ?>
	<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation" class="noyona-order-totals">
		<?php
		$i = 0;
		foreach ( $noyona_totals as $total ) :
			++$i;
			$is_last = ( $i === $noyona_total_count );
			?>
			<tr class="<?php echo $is_last ? 'noyona-order-total-row' : ''; ?>">
				<th scope="row"><?php echo wp_kses_post( $total['label'] ); ?></th>
				<td><?php echo wp_kses_post( $total['value'] ); ?></td>
			</tr>
		<?php endforeach; ?>
	</table>
<?php endif; ?>

<?php if ( $order->get_customer_note() ) : ?>
	<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation" class="noyona-order-note">
		<tr>
			<td>
				<h2 class="noyona-email-section-title"><?php esc_html_e( 'ORDER NOTE', 'noyona-childtheme' ); ?></h2>
				<p><?php echo wp_kses( nl2br( wc_wptexturize_order_note( $order->get_customer_note() ) ), array( 'br' => array() ) ); ?></p>
			</td>
		</tr>
	</table>
<?php endif; ?>

<?php
/**
 * Action hook to add custom content after order details in email.
 *
 * @param WC_Order $order Order object.
 * @param bool     $sent_to_admin Whether it's sent to admin or customer.
 * @param bool     $plain_text Whether it's a plain text email.
 * @param WC_Email $email Email object.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_after_order_table', $order, $sent_to_admin, $plain_text, $email );
