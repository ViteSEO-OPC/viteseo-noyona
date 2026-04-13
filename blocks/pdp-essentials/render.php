<?php
/**
 * PDP essentials section under social proof.
 *
 * @package viteseo-noyona
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$defaults = array(
	'heading' => __( 'Why Noyona Essentials?', 'viteseo-noyona-childtheme' ),
	'items'   => array(
		array(
			'icon'        => 'fa-solid fa-paw',
			'title'       => __( 'Cruelty-free', 'viteseo-noyona-childtheme' ),
			'description' => __( 'We love animals as much as you do. Never tested on friends with fur.', 'viteseo-noyona-childtheme' ),
		),
		array(
			'icon'        => 'fa-solid fa-leaf',
			'title'       => __( 'Sustainable', 'viteseo-noyona-childtheme' ),
			'description' => __( 'Responsibly sourced ingredients and eco-conscious packaging.', 'viteseo-noyona-childtheme' ),
		),
		array(
			'icon'        => 'fa-solid fa-seedling',
			'title'       => __( 'Vegan', 'viteseo-noyona-childtheme' ),
			'description' => __( '100% plant-based formulas without any animal-derived ingredients.', 'viteseo-noyona-childtheme' ),
		),
		array(
			'icon'        => 'fa-solid fa-ban',
			'title'       => __( 'Paraben-free', 'viteseo-noyona-childtheme' ),
			'description' => __( 'Formulated without harmful parabens or sulfates. Safe for sensitive skin.', 'viteseo-noyona-childtheme' ),
		),
	),
);

$atts    = wp_parse_args( is_array( $attributes ) ? $attributes : array(), $defaults );
$heading = isset( $atts['heading'] ) ? trim( (string) $atts['heading'] ) : '';
$items   = isset( $atts['items'] ) && is_array( $atts['items'] ) && ! empty( $atts['items'] ) ? $atts['items'] : $defaults['items'];

if ( empty( $items ) ) {
	return;
}
?>
<section class="wp-block-noyona-pdp-essentials noyona-pdp-essentials">
	<?php if ( '' !== $heading ) : ?>
		<h3 class="noyona-pdp-essentials__title"><?php echo esc_html( $heading ); ?></h3>
	<?php endif; ?>

	<div class="noyona-pdp-essentials__grid">
		<?php foreach ( $items as $item ) : ?>
			<?php
			$title       = isset( $item['title'] ) ? trim( (string) $item['title'] ) : '';
			$description = isset( $item['description'] ) ? trim( (string) $item['description'] ) : '';
			$icon_class  = isset( $item['icon'] ) ? trim( (string) $item['icon'] ) : '';

			if ( '' === $title && '' === $description ) {
				continue;
			}
			?>
			<article class="noyona-pdp-essentials__item">
				<?php if ( '' !== $icon_class ) : ?>
					<span class="noyona-pdp-essentials__icon-wrap" aria-hidden="true">
						<i class="<?php echo esc_attr( $icon_class ); ?>"></i>
					</span>
				<?php endif; ?>
				<?php if ( '' !== $title ) : ?>
					<h3 class="noyona-pdp-essentials__item-title"><?php echo esc_html( $title ); ?></h3>
				<?php endif; ?>
				<?php if ( '' !== $description ) : ?>
					<p class="noyona-pdp-essentials__item-desc"><?php echo esc_html( $description ); ?></p>
				<?php endif; ?>
			</article>
		<?php endforeach; ?>
	</div>
</section>
