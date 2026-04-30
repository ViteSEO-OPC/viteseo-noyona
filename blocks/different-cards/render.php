<?php
/**
 * Different Cards block render.
 *
 * @package viteseo-noyona
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$defaults = array(
	'title'    => 'What Makes Us <span class="different-cards__accent">Different</span>',
	'subtitle' => 'High-end beauty shouldn’t be exclusive. We bridge the gap between pro-performance and everyday value with ethical, inclusive products.',
	'cards'    => array(
		array(
			'image' => 'https://noyonacosmetics.com/wp-content/uploads/2026/02/made-for-you.webp',
			'title' => 'Made for You',
			'text'  => 'Shades and formulas designed to complement diverse skin tones.',
			'bg'    => '#f7d7ea',
		),
		array(
			'image' => 'https://noyonacosmetics.com/wp-content/uploads/2026/02/clean-beauty.webp',
			'title' => 'Clean Beauty',
			'text'  => 'No harsh chemicals, just pure, skin-loving goodness.',
			'bg'    => '#d9ecfb',
		),
		array(
			'image' => 'https://noyonacosmetics.com/wp-content/uploads/2026/02/affordable-luxury.webp',
			'title' => 'Affordable Luxury',
			'text'  => 'Premium quality without the premium price tag.',
			'bg'    => '#f6f2c5',
		),
	),
);

$atts  = wp_parse_args( is_array( $attributes ) ? $attributes : array(), $defaults );
$title = isset( $atts['title'] ) ? (string) $atts['title'] : '';
$sub   = isset( $atts['subtitle'] ) ? (string) $atts['subtitle'] : '';
$cards = isset( $atts['cards'] ) && is_array( $atts['cards'] ) ? $atts['cards'] : array();

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

// Provide uploads-hosted default images if none are set.
$fallback_images = array(
	0 => 'https://noyonacosmetics.com/wp-content/uploads/2026/02/made-for-you.webp',
	1 => 'https://noyonacosmetics.com/wp-content/uploads/2026/02/clean-beauty.webp',
	2 => 'https://noyonacosmetics.com/wp-content/uploads/2026/02/affordable-luxury.webp',
);

?>
<section class="wp-block-noyona-different-cards different-cards<?php echo esc_attr( $align ); ?>">
	<div class="different-cards__inner">
		<header class="different-cards__header">
			<?php if ( '' !== trim( $title ) ) : ?>
				<h2 class="different-cards__title"><?php echo wp_kses( $title, $allowed_title_html ); ?></h2>
			<?php endif; ?>

			<?php if ( '' !== trim( $sub ) ) : ?>
				<p class="different-cards__subtitle"><?php echo esc_html( $sub ); ?></p>
			<?php endif; ?>
		</header>

		<?php if ( ! empty( $cards ) ) : ?>
			<div class="different-cards__grid">
				<?php foreach ( $cards as $idx => $card ) : ?>
					<?php
					$card = is_array( $card ) ? $card : array();

					$image_id = isset( $card['imageId'] ) ? absint( $card['imageId'] ) : 0;
					$img = isset( $card['image'] ) ? (string) $card['image'] : '';
					if ( $image_id ) {
						$resolved_image = wp_get_attachment_image_url( $image_id, 'large' );
						if ( $resolved_image ) {
							$img = (string) $resolved_image;
						}
					} elseif ( '' !== trim( $img ) ) {
						$resolved_id = attachment_url_to_postid( $img );
						if ( $resolved_id ) {
							$resolved_image = wp_get_attachment_image_url( (int) $resolved_id, 'large' );
							if ( $resolved_image ) {
								$img = (string) $resolved_image;
							}
						}
					}
					if ( '' === trim( $img ) && isset( $fallback_images[ $idx ] ) ) {
						$img = (string) $fallback_images[ $idx ];
					}

					$card_title = isset( $card['title'] ) ? (string) $card['title'] : '';
					$card_text  = isset( $card['text'] ) ? (string) $card['text'] : '';
					$bg         = isset( $card['bg'] ) ? (string) $card['bg'] : '';

					$style_attr = $bg ? ' style="--diff-card-bg:' . esc_attr( $bg ) . ';"' : '';
					?>
					<article class="different-cards__card"<?php echo $style_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
						<?php if ( '' !== trim( $img ) ) : ?>
							<div class="different-cards__media">
								<img class="different-cards__image" src="<?php echo esc_url( $img ); ?>" alt="" width="600" height="600" loading="lazy" decoding="async" sizes="(max-width: 768px) 92vw, (max-width: 1280px) 33vw, 360px" />
							</div>
						<?php endif; ?>

						<div class="different-cards__body">
							<?php if ( '' !== trim( $card_title ) ) : ?>
								<h3 class="different-cards__card-title"><?php echo esc_html( $card_title ); ?></h3>
							<?php endif; ?>
							<?php if ( '' !== trim( $card_text ) ) : ?>
								<p class="different-cards__card-text"><?php echo esc_html( $card_text ); ?></p>
							<?php endif; ?>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>


