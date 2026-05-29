<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$heading         = isset( $attributes['heading'] ) ? trim( (string) $attributes['heading'] ) : '';
$description     = isset( $attributes['description'] ) ? trim( (string) $attributes['description'] ) : '';
$supporting_text = isset( $attributes['supportingText'] ) ? trim( (string) $attributes['supportingText'] ) : '';
$button_text     = isset( $attributes['buttonText'] ) ? trim( (string) $attributes['buttonText'] ) : '';
$button_url      = isset( $attributes['buttonUrl'] ) ? trim( (string) $attributes['buttonUrl'] ) : '';
$image_url       = isset( $attributes['imageUrl'] ) ? trim( (string) $attributes['imageUrl'] ) : '';
$image_alt       = isset( $attributes['imageAlt'] ) ? trim( (string) $attributes['imageAlt'] ) : '';
$align_class     = isset( $attributes['align'] ) ? 'align' . sanitize_html_class( (string) $attributes['align'] ) : '';

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => trim( 'store-locator-section ' . $align_class . ' child-block' ),
	)
);
?>

<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="store-locator-section__inner">
		<div class="store-locator-section__content">
			<?php if ( '' !== $heading ) : ?>
				<h2 class="store-locator-section__heading"><?php echo wp_kses_post( $heading ); ?></h2>
			<?php endif; ?>

			<?php if ( '' !== $description ) : ?>
				<p class="store-locator-section__text"><?php echo wp_kses_post( $description ); ?></p>
			<?php endif; ?>

			<?php if ( '' !== $supporting_text ) : ?>
				<p class="store-locator-section__text"><?php echo wp_kses_post( $supporting_text ); ?></p>
			<?php endif; ?>

			<?php if ( '' !== $button_text ) : ?>
				<a class="store-locator-section__button" href="<?php echo esc_url( '' !== $button_url ? $button_url : '#' ); ?>">
					<i class="fa-solid fa-location-dot" aria-hidden="true"></i>
					<span><?php echo esc_html( $button_text ); ?></span>
				</a>
			<?php endif; ?>
		</div>

		<?php if ( '' !== $image_url ) : ?>
			<figure class="store-locator-section__media">
				<img
					class="store-locator-section__image"
					src="<?php echo esc_url( $image_url ); ?>"
					alt="<?php echo esc_attr( $image_alt ); ?>"
					loading="lazy"
					decoding="async"
				/>
			</figure>
		<?php endif; ?>
	</div>
</section>
