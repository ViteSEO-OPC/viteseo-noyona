<?php
/**
 * PDP tabs: product description + custom meta fields.
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

$pid = $product->get_id();

$raw_desc = get_post_field( 'post_content', $pid );
$desc     = is_string( $raw_desc ) ? trim( $raw_desc ) : '';
$desc_html = '';
if ( '' !== $raw_desc ) {
	$desc_html = apply_filters( 'the_content', $raw_desc );
}

$ing_raw = get_post_meta( $pid, '_noyona_product_ingredients', true );
$ing     = is_string( $ing_raw ) ? trim( $ing_raw ) : '';
$ing_html = $ing ? wpautop( wp_kses_post( $ing ) ) : '';

$how_raw = get_post_meta( $pid, '_noyona_product_how_to_use', true );
$how     = is_string( $how_raw ) ? trim( $how_raw ) : '';
$how_html = $how ? wpautop( wp_kses_post( $how ) ) : '';

$tabs = array();

$tabs[] = array(
	'id'      => 'description',
	'label'   => __( 'Description', 'viteseo-noyona-childtheme' ),
	'content' => '' !== $desc_html ? $desc_html : '<p class="noyona-pdp-tabs__empty">' . esc_html__( 'No detailed description yet.', 'viteseo-noyona-childtheme' ) . '</p>',
);

$tabs[] = array(
	'id'      => 'ingredients',
	'label'   => __( 'Ingredients', 'viteseo-noyona-childtheme' ),
	'content' => '' !== $ing_html ? $ing_html : '<p class="noyona-pdp-tabs__empty">' . esc_html__( 'No ingredients listed yet. Add them under Product data → General.', 'viteseo-noyona-childtheme' ) . '</p>',
);

$tabs[] = array(
	'id'      => 'how-to-use',
	'label'   => __( 'How to use', 'viteseo-noyona-childtheme' ),
	'content' => '' !== $how_html ? $how_html : '<p class="noyona-pdp-tabs__empty">' . esc_html__( 'No usage instructions yet. Add them under Product data → General.', 'viteseo-noyona-childtheme' ) . '</p>',
);

$uid = 'noyona-pdp-tabs-' . $pid;
?>
<div class="wp-block-noyona-product-tabs noyona-pdp-tabs alignwide" data-noyona-pdp-tabs="<?php echo esc_attr( $uid ); ?>">
	<div class="noyona-pdp-tabs__list" role="tablist" aria-label="<?php esc_attr_e( 'Product information', 'viteseo-noyona-childtheme' ); ?>">
		<?php foreach ( $tabs as $i => $tab ) : ?>
			<button
				type="button"
				class="noyona-pdp-tabs__tab<?php echo 0 === $i ? ' is-active' : ''; ?>"
				id="<?php echo esc_attr( $uid . '-tab-' . $tab['id'] ); ?>"
				role="tab"
				aria-selected="<?php echo 0 === $i ? 'true' : 'false'; ?>"
				aria-controls="<?php echo esc_attr( $uid . '-panel-' . $tab['id'] ); ?>"
				data-noyona-tab="<?php echo esc_attr( $tab['id'] ); ?>"
			><?php echo esc_html( $tab['label'] ); ?></button>
		<?php endforeach; ?>
	</div>
	<?php foreach ( $tabs as $i => $tab ) : ?>
		<div
			class="noyona-pdp-tabs__panel<?php echo 0 === $i ? ' is-active' : ''; ?>"
			id="<?php echo esc_attr( $uid . '-panel-' . $tab['id'] ); ?>"
			role="tabpanel"
			aria-labelledby="<?php echo esc_attr( $uid . '-tab-' . $tab['id'] ); ?>"
			<?php echo 0 === $i ? '' : ' hidden'; ?>
			data-noyona-panel="<?php echo esc_attr( $tab['id'] ); ?>"
		>
			<div class="noyona-pdp-tabs__panel-inner">
				<?php echo $tab['content']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- mixed HTML from the_content / wp_kses_post ?>
			</div>
		</div>
	<?php endforeach; ?>
</div>
