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

$stock_html = '';
if ( $product->is_in_stock() ) {
	$stock_html = '<span class="noyona-pdp-stock-shipping__stock noyona-pdp-stock-shipping__stock--in">' . esc_html__( 'In stock', 'viteseo-noyona-childtheme' ) . '</span>';
} else {
	$stock_html = '<span class="noyona-pdp-stock-shipping__stock noyona-pdp-stock-shipping__stock--out">' . esc_html__( 'Out of stock', 'viteseo-noyona-childtheme' ) . '</span>';
}

$shipping_note = apply_filters(
	'noyona_pdp_shipping_note',
	__( 'Shipping calculated at checkout', 'viteseo-noyona-childtheme' )
);

?>
<div class="wp-block-noyona-pdp-stock-shipping noyona-pdp-stock-shipping">
	<?php echo wp_kses_post( $stock_html ); ?>
	<?php if ( is_string( $shipping_note ) && '' !== trim( $shipping_note ) ) : ?>
		<span class="noyona-pdp-stock-shipping__ship"><?php echo esc_html( $shipping_note ); ?></span>
	<?php endif; ?>
</div>
