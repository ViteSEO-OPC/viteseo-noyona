<?php
/**
 * Guides Pillars block render.
 *
 * Expected variables when included by WP:
 * - $attributes (array)
 * - $content (string)
 * - $block (array)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'noyona_guides_pillars_icon_svg' ) ) {
	/**
	 * Return inline SVG for a given icon slug.
	 *
	 * @param string $slug Icon slug.
	 * @return string SVG markup.
	 */
	function noyona_guides_pillars_icon_svg( $slug ) {
		$slug = (string) $slug;

		// All icons use currentColor stroke; CSS controls size + color.
		if ( 'choices' === $slug ) {
			return '<svg viewBox="0 0 64 64" role="img" aria-hidden="true">
				<path d="M10 38c6 0 10-6 16-6s10 6 16 6 10-6 16-6" />
				<path d="M10 38v8c0 4 3 8 8 8h6" />
				<path d="M54 38v8c0 4-3 8-8 8h-6" />
				<path d="M32 18c-4-6-14-2-12 6 2 8 12 12 12 12s10-4 12-12c2-8-8-12-12-6z" />
				<path d="M32 36c0 6-5 10-12 12" />
				<path d="M32 36c0 6 5 10 12 12" />
			</svg>';
		}

		if ( 'everyday' === $slug ) {
			return '<svg viewBox="0 0 64 64" role="img" aria-hidden="true">
				<circle cx="26" cy="24" r="8" />
				<path d="M18 50c1-10 6-16 16-16s15 6 16 16" />
				<path d="M33 16c4 0 7 3 7 7" />
				<path d="M46 22h10" />
				<path d="M48 22v-4c0-2 2-3 3-3s3 1 3 3v4" />
				<path d="M48 22v14c0 2 2 3 3 3s3-1 3-3V22" />
				<path d="M42 28h8" />
				<path d="M44 28v-6h4v6" />
				<path d="M44 28v10c0 2 2 3 2 3s2-1 2-3V28" />
			</svg>';
		}

		// Default: "purpose".
		return '<svg viewBox="0 0 64 64" role="img" aria-hidden="true">
			<circle cx="32" cy="12" r="6" />
			<path d="M32 2v4" /><path d="M22 12h4" /><path d="M38 12h4" /><path d="M25 5l3 3" /><path d="M39 5l-3 3" />
			<path d="M24 20h16v8l10 20a8 8 0 0 1-7 12H21a8 8 0 0 1-7-12l10-20v-8z" />
			<path d="M24 28h16" />
			<path d="M30 44c4-6 10-6 14 0-6 6-12 6-14 0z" />
			<path d="M30 44c0 6-4 10-10 12" />
		</svg>';
	}
}

$defaults = array(
	'title'    => 'What <span class="guides-pillars__accent">Guides Everything</span> We Do',
	'subtitle' => 'We move with purpose in every step, creating beauty products that are intentional, responsible, and designed to support real lifestyles.',
	'items'    => array(
		array(
			'icon'  => 'purpose',
			'title' => 'Purposeful Creation',
			'text'  => 'Carefully formulated with a focus on quality, safety, and intention.',
		),
		array(
			'icon'  => 'choices',
			'title' => 'Conscious Choices',
			'text'  => 'Mindful practices that support ethical standards and sustainability.',
		),
		array(
			'icon'  => 'everyday',
			'title' => 'Beauty for Everyday Life',
			'text'  => 'Simple, effective products designed for everyday confidence.',
		),
	),
);

$atts  = wp_parse_args( is_array( $attributes ) ? $attributes : array(), $defaults );
$title = isset( $atts['title'] ) ? (string) $atts['title'] : '';
$sub   = isset( $atts['subtitle'] ) ? (string) $atts['subtitle'] : '';
$items = isset( $atts['items'] ) && is_array( $atts['items'] ) ? $atts['items'] : array();

$align = '';
if ( isset( $block ) && is_array( $block ) && ! empty( $block['align'] ) ) {
	$align_value = (string) $block['align'];
	if ( 'full' === $align_value ) {
		$align = ' alignfull';
	} elseif ( 'wide' === $align_value ) {
		$align = ' alignwide';
	}
}

$allowed_title_html = array(
	'span'   => array( 'class' => array() ),
	'em'     => array(),
	'strong' => array(),
	'br'     => array(),
);

?>
<section class="wp-block-noyona-guides-pillars guides-pillars<?php echo esc_attr( $align ); ?>">
	<div class="guides-pillars__inner">
		<header class="guides-pillars__header">
			<?php if ( '' !== trim( $title ) ) : ?>
				<h2 class="guides-pillars__title"><?php echo wp_kses( $title, $allowed_title_html ); ?></h2>
			<?php endif; ?>

			<?php if ( '' !== trim( $sub ) ) : ?>
				<p class="guides-pillars__subtitle"><?php echo esc_html( $sub ); ?></p>
			<?php endif; ?>
		</header>

		<?php if ( ! empty( $items ) ) : ?>
			<div class="guides-pillars__grid">
				<?php foreach ( $items as $item ) : ?>
					<?php
					$icon  = isset( $item['icon'] ) ? (string) $item['icon'] : 'purpose';
					$it_t  = isset( $item['title'] ) ? (string) $item['title'] : '';
					$it_tx = isset( $item['text'] ) ? (string) $item['text'] : '';
					?>
					<div class="guides-pillars__item">
						<div class="guides-pillars__icon">
							<?php echo noyona_guides_pillars_icon_svg( $icon ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>

						<?php if ( '' !== trim( $it_t ) ) : ?>
							<h3 class="guides-pillars__item-title"><?php echo esc_html( $it_t ); ?></h3>
						<?php endif; ?>

						<?php if ( '' !== trim( $it_tx ) ) : ?>
							<p class="guides-pillars__item-text"><?php echo esc_html( $it_tx ); ?></p>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>


