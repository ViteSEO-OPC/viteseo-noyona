<?php
/**
 * Find Us Block Template.
 */

$defaults = array(
    'image' => '/wp-content/themes/viteseo-noyona/assets/images/brand-logo.webp',
    'heading' => 'Where to <span class="find-us__accent">Find Us</span>',
    'description' => 'Visit our stores to experience Noyona products in person. Our beauty advisors are ready to help you find your perfect match.',
    'storeName' => 'Noyona Essentials',
    'address' => 'Burgundy Corporate Tower 252 Sen. Gil Puyat Ave., Makati City, Metro Manila, Philippines',
);

$atts = wp_parse_args($attributes, $defaults);
$image = !empty($atts['image']) ? (string) $atts['image'] : '';
$image_id = isset($atts['imageId']) ? absint($atts['imageId']) : 0;
if ($image_id) {
    $resolved_image = wp_get_attachment_image_url($image_id, 'large');
    if ($resolved_image) {
        $image = (string) $resolved_image;
    }
} elseif (!empty($image)) {
    $resolved_id = attachment_url_to_postid($image);
    if ($resolved_id) {
        $resolved_image = wp_get_attachment_image_url((int) $resolved_id, 'large');
        if ($resolved_image) {
            $image = (string) $resolved_image;
        }
    }
}

$allowed_html = array(
    'span' => array(
        'class' => array(),
    ),
);
?>
<section <?php echo get_block_wrapper_attributes(array('class' => 'find-us')); ?>>
    <div class="find-us__container">
        <div class="find-us__image-col">
            <div class="find-us__image-box">
                <?php if (!empty($image)): ?>
                    <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($atts['storeName']); ?>" loading="lazy" decoding="async">
                <?php endif; ?>
            </div>
        </div>

        <div class="find-us__content-col">
            <?php if (!empty($atts['heading'])): ?>
                <h2 class="find-us__title">
                    <?php echo wp_kses($atts['heading'], $allowed_html); ?>
                </h2>
            <?php endif; ?>

            <?php if (!empty($atts['description'])): ?>
                <p class="find-us__description">
                    <?php echo esc_html($atts['description']); ?>
                </p>
            <?php endif; ?>

            <div class="find-us__info">
                <?php if (!empty($atts['storeName'])): ?>
                    <h3 class="find-us__store-name">
                        <?php echo esc_html($atts['storeName']); ?>
                    </h3>
                <?php endif; ?>

                <?php if (!empty($atts['address'])): ?>
                    <div class="find-us__address-row">
                        <div class="find-us__icon">
                            <i class="fa-solid fa-location-dot"></i>
                        </div>
                        <p class="find-us__address">
                            <?php echo esc_html($atts['address']); ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>