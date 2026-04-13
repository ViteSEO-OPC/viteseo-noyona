<?php
/**
 * Fixed trust badges for PDP.
 *
 * @package viteseo-noyona
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$items = apply_filters(
	'noyona_pdp_trust_badges',
	array(
		array(
			'label' => __( 'Secure Checkout', 'viteseo-noyona-childtheme' ),
			'icon'  => 'fa-solid fa-shield-halved',
			'color' => '#1a7a3e',
		),
		array(
			'label' => __( 'Fast Shipping', 'viteseo-noyona-childtheme' ),
			'icon'  => 'fa-solid fa-truck-fast',
			'color' => '#1d4ed8',
		),
		array(
			'label' => __( 'Easy Returns', 'viteseo-noyona-childtheme' ),
			'icon'  => 'fa-solid fa-arrow-rotate-left',
			'color' => '#c2410c',
		),
	)
);

if ( ! is_array( $items ) || empty( $items ) ) {
	return;
}

?>
<div class="wp-block-noyona-pdp-trust-badges noyona-pdp-trust">
	<?php foreach ( $items as $item ) : ?>
		<?php
		if ( ! is_array( $item ) || empty( $item['label'] ) ) {
			continue;
		}
		$icon_class = isset( $item['icon'] ) ? (string) $item['icon'] : 'fa-solid fa-circle-check';
		$icon_color = isset( $item['color'] ) ? (string) $item['color'] : '#E199A4';
		?>
		<div class="noyona-pdp-trust__card">
			<span class="noyona-pdp-trust__icon-wrap" style="--noyona-trust-icon-color: <?php echo esc_attr( $icon_color ); ?>">
				<i class="<?php echo esc_attr( $icon_class ); ?>" aria-hidden="true"></i>
			</span>
			<span class="noyona-pdp-trust__label"><?php echo esc_html( $item['label'] ); ?></span>
		</div>
	<?php endforeach; ?>
</div>
