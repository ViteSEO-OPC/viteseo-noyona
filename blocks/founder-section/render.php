<?php
/**
 * Founder section block render callback.
 *
 * @package YourChildTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Image field can be IDs or direct URLs. Prefer IDs when numeric.
$image = isset( $attributes['image'] ) && is_array( $attributes['image'] ) ? $attributes['image'] : array();

$main_url = '';
if ( isset( $image['main'] ) ) {
	$main_value = $image['main'];
	if ( is_numeric( $main_value ) && $main_value > 0 ) {
		$main_url = wp_get_attachment_image_url( (int) $main_value, 'large' );
	} elseif ( is_string( $main_value ) && $main_value ) {
		$main_url = esc_url_raw( $main_value );
	}
}

$icon_url = '';
if ( isset( $image['icon'] ) ) {
	$icon_value = $image['icon'];
	if ( is_numeric( $icon_value ) && $icon_value > 0 ) {
		$icon_url = wp_get_attachment_image_url( (int) $icon_value, 'medium' );
	} elseif ( is_string( $icon_value ) && $icon_value ) {
		$icon_url = esc_url_raw( $icon_value );
	}
}

// Copy fields.
$title       = ! empty( $attributes['title'] ) ? $attributes['title'] : __( 'About the Founder', 'your-textdomain' );
$body        = ! empty( $attributes['body'] ) ? $attributes['body'] : '';
$button_text = ! empty( $attributes['buttonText'] ) ? $attributes['buttonText'] : __( 'Learn More', 'your-textdomain' );
$button_url  = ! empty( $attributes['buttonUrl'] ) ? $attributes['buttonUrl'] : '#';
?>
<section class="wp-block-noyona-founder-section noyona-founder-section">
	<div class="noyona-founder-section__image-column">
		<?php if ( $main_url ) : ?>
			<figure class="noyona-founder-section__image-main">
				<img src="<?php echo esc_url( $main_url ); ?>" alt="" loading="lazy" />
			</figure>
		<?php endif; ?>

		<?php if ( $icon_url ) : ?>
			<div class="noyona-founder-section__image-icon">
				<img src="<?php echo esc_url( $icon_url ); ?>" alt="" loading="lazy" />
			</div>
		<?php endif; ?>
	</div>

	<div class="noyona-founder-section__content-column">
		<?php if ( $title ) : ?>
			<h2 class="noyona-founder-section__title">
				<?php echo esc_html( $title ); ?>
			</h2>
		<?php endif; ?>

		<?php if ( $body ) : ?>
			<div class="noyona-founder-section__body">
				<?php echo wp_kses_post( wpautop( $body ) ); ?>
			</div>
		<?php endif; ?>

		<?php if ( $button_text ) : ?>
			<a class="noyona-founder-section__button" href="<?php echo esc_url( $button_url ); ?>">
				<span class="noyona-founder-section__button-label">
					<?php echo esc_html( $button_text ); ?>
				</span>
                <span class="noyona-founder-section__button-icon" aria-hidden="true">
                    <i class="fa-solid fa-arrow-right"></i>
                </span>			
            </a>
		<?php endif; ?>
	</div>
</section>
