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

if ( ! function_exists( 'noyona_guides_pillars_icon_url' ) ) {
	/**
	 * Return icon image URL for a given icon slug.
	 *
	 * @param string $slug Icon slug.
	 * @return string Icon URL.
	 */
	function noyona_guides_pillars_icon_url( $slug ) {
		$slug = sanitize_key( (string) $slug );

		$icon_map = array(
			'purpose'  => content_url( 'uploads/2026/02/purposeful-creation.webp' ),
			'choices'  => content_url( 'uploads/2026/02/concious-choice.webp' ),
			'everyday' => content_url( 'uploads/2026/02/beautiful-for-everday-life.webp' ),
		);

		if ( isset( $icon_map[ $slug ] ) ) {
			return $icon_map[ $slug ];
		}

		return $icon_map['purpose'];
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
					$icon_url = noyona_guides_pillars_icon_url( $icon );
					?>
					<div class="guides-pillars__item">
						<div class="guides-pillars__icon">
							<?php if ( '' !== $icon_url ) : ?>
								<img src="<?php echo esc_url( $icon_url ); ?>" alt="<?php echo esc_attr( $it_t ); ?>" width="80" height="80" loading="lazy" decoding="async" />
							<?php endif; ?>
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


