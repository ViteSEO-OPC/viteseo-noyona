<?php
/**
 * Founder section block render callback.
 *
 * @package YourChildTheme
 */

if (!defined('ABSPATH')) {
	exit;
}

// Image field can be IDs or direct URLs. Prefer IDs when numeric.
$image = isset($attributes['image']) && is_array($attributes['image']) ? $attributes['image'] : array();
$image_position = isset($attributes['imagePosition']) ? strtolower(trim((string) $attributes['imagePosition'])) : 'left';
if (!in_array($image_position, array('left', 'right'), true)) {
	$image_position = 'left';
}

$section_class = 'wp-block-noyona-founder-section noyona-founder-section noyona-founder-section--image-' . $image_position;

$main_url = '';
$main_id = 0;
if (isset($image['main'])) {
	$main_value = $image['main'];
	if (is_numeric($main_value) && $main_value > 0) {
		$main_id = (int) $main_value;
		$main_url = wp_get_attachment_image_url($main_id, 'large');
	} elseif (is_string($main_value) && $main_value) {
		$main_url = esc_url_raw($main_value);
		$resolved_main_id = attachment_url_to_postid($main_url);
		if ($resolved_main_id) {
			$main_id = (int) $resolved_main_id;
		}
	}
}

$icon_url = '';
$icon_id = 0;
if (isset($image['icon'])) {
	$icon_value = $image['icon'];
	if (is_numeric($icon_value) && $icon_value > 0) {
		$icon_id = (int) $icon_value;
		$icon_url = wp_get_attachment_image_url($icon_id, 'medium');
	} elseif (is_string($icon_value) && $icon_value) {
		$icon_url = esc_url_raw($icon_value);
		$resolved_icon_id = attachment_url_to_postid($icon_url);
		if ($resolved_icon_id) {
			$icon_id = (int) $resolved_icon_id;
		}
	}
}

// Copy fields.
$title = isset($attributes['title']) ? (string) $attributes['title'] : 'About the Founder';
$body = isset($attributes['body']) ? (string) $attributes['body'] : '';

$button_text = isset($attributes['buttonText']) ? (string) $attributes['buttonText'] : '';
$button_url = isset($attributes['buttonUrl']) ? (string) $attributes['buttonUrl'] : '';

?>
<section class="<?php echo esc_attr($section_class); ?>">
	<div class="noyona-founder-section__image-column">
		<?php if ($main_url): ?>
			<figure class="noyona-founder-section__image-main">
				<?php if ($main_id) : ?>
					<?php
					echo wp_get_attachment_image(
						$main_id,
						'large',
						false,
						array(
							'alt' => '',
							'loading' => 'lazy',
							'decoding' => 'async',
							'sizes' => '(max-width: 960px) 92vw, (max-width: 1499px) 720px, 588px',
						)
					);
					?>
				<?php else : ?>
					<img src="<?php echo esc_url($main_url); ?>" alt="" width="720" height="745" loading="lazy" decoding="async" sizes="(max-width: 960px) 92vw, (max-width: 1499px) 720px, 588px" />
				<?php endif; ?>
			</figure>
		<?php endif; ?>

		<?php if ($icon_url): ?>
			<div class="noyona-founder-section__image-icon">
				<?php if ($icon_id) : ?>
					<?php
					echo wp_get_attachment_image(
						$icon_id,
						'medium',
						false,
						array(
							'alt' => '',
							'loading' => 'lazy',
							'decoding' => 'async',
							'sizes' => '(max-width: 960px) 120px, 220px',
						)
					);
					?>
				<?php else : ?>
					<img src="<?php echo esc_url($icon_url); ?>" alt="" width="220" height="220" loading="lazy" decoding="async" sizes="(max-width: 960px) 120px, 220px" />
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>

	<div class="noyona-founder-section__content-column">
		<?php if ($title): ?>
			<h2 class="noyona-founder-section__title">
				<?php echo wp_kses_post($title); ?>
			</h2>
		<?php endif; ?>

		<?php if ($body): ?>
			<div class="noyona-founder-section__body">
				<?php echo wp_kses_post(wpautop($body)); ?>
			</div>
		<?php endif; ?>

		<?php if ('' !== trim($button_text)): ?>
			<a class="noyona-founder-section__button" href="<?php echo esc_url($button_url ? $button_url : '#'); ?>">
				<span class="noyona-founder-section__button-label"><?php echo esc_html($button_text); ?></span>
				<span class="noyona-founder-section__button-icon" aria-hidden="true">
					<i class="fa-solid fa-arrow-right"></i>
				</span>
			</a>
		<?php endif; ?>

	</div>
</section>