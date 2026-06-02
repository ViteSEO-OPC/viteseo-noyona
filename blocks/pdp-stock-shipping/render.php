<?php
/**
 * Stock status + shipping note row.
 *
 * @package viteseo-noyona
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$product = wc_get_product( get_the_ID() );
if ( ! $product ) {
	return;
}

$is_variable    = $product->is_type( 'variable' );
$is_in_stock    = $product->is_in_stock();
$stock_quantity = $product->get_stock_quantity();
$stock_count    = null !== $stock_quantity ? max( 0, (int) $stock_quantity ) : null;
$stock_class    = 'noyona-pdp-stock-shipping__stock';

if ( $is_variable ) {
	$stock_label = __( 'Select options to see availability', 'viteseo-noyona-childtheme' );
} elseif ( $is_in_stock ) {
	$stock_class .= ' noyona-pdp-stock-shipping__stock--in';
	$stock_label  = null !== $stock_count
		? sprintf(
			/* translators: %d: product stock quantity. */
			__( 'In stock (%d left)', 'viteseo-noyona-childtheme' ),
			$stock_count
		)
		: __( 'In stock', 'viteseo-noyona-childtheme' );
} else {
	$stock_class .= ' noyona-pdp-stock-shipping__stock--out';
	$stock_label  = sprintf(
		/* translators: %d: product stock quantity. */
		__( 'Out of stock (%d left)', 'viteseo-noyona-childtheme' ),
		0
	);
}

$shipping_note = apply_filters(
	'noyona_pdp_shipping_note',
	__( 'Shipping calculated at checkout', 'viteseo-noyona-childtheme' )
);

?>
<div class="wp-block-noyona-pdp-stock-shipping noyona-pdp-stock-shipping">
	<span
		class="<?php echo esc_attr( $stock_class ); ?>"
		data-noyona-product-type="<?php echo esc_attr( $is_variable ? 'variable' : 'simple' ); ?>"
		data-noyona-in-stock="<?php echo $is_in_stock ? '1' : '0'; ?>"
		data-noyona-stock-count="<?php echo null !== $stock_count ? esc_attr( (string) $stock_count ) : ''; ?>"
	><?php echo esc_html( $stock_label ); ?></span>
	<?php if ( is_string( $shipping_note ) && '' !== trim( $shipping_note ) ) : ?>
		<span class="noyona-pdp-stock-shipping__ship"><?php echo esc_html( $shipping_note ); ?></span>
	<?php endif; ?>
</div>
