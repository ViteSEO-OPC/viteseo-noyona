<?php
/**
 * Optional social proof line from _noyona_social_proof.
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

$line = get_post_meta( $product->get_id(), '_noyona_social_proof', true );
$line = is_string( $line ) ? trim( $line ) : '';

if ( '150+ sold in the last 2 days' === $line ) {
	$line = __( 'A top-rated favorite', 'viteseo-noyona-childtheme' );
}

if ( '' === $line ) {
	$line = apply_filters(
		'noyona_pdp_social_proof_default_line',
		__( 'A top-rated favorite', 'viteseo-noyona-childtheme' ),
		$product
	);
	$line = is_string( $line ) ? trim( $line ) : '';
	if ( '' === $line ) {
		return;
	}
}

?>
<p class="wp-block-noyona-pdp-social-proof noyona-pdp-social-proof">
	<span class="noyona-pdp-social-proof__pill"><?php echo esc_html( $line ); ?></span>
</p>
