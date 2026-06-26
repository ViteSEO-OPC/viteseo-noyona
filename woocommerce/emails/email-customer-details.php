<?php
/**
 * Additional Customer Details
 *
 * Noyona customization: renders the extra customer fields in the branded
 * "noyona-customer-info" block to match the rest of the email designs. Only
 * $fields is passed to this template by WooCommerce.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 9.7.0
 */

defined( 'ABSPATH' ) || exit;
?>
<?php if ( ! empty( $fields ) ) : ?>
	<h2 class="noyona-email-section-title"><?php esc_html_e( 'CUSTOMER DETAILS', 'noyona-childtheme' ); ?></h2>
	<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation" class="noyona-customer-info">
		<tr>
			<td style="width: 100%;">
				<?php foreach ( $fields as $field ) : ?>
					<p><strong><?php echo wp_kses_post( $field['label'] ); ?>:</strong> <?php echo wp_kses_post( $field['value'] ); ?></p>
				<?php endforeach; ?>
			</td>
		</tr>
	</table>
<?php endif; ?>
