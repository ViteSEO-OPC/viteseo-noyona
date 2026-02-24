<?php
/**
 * Product Slide Block Template.
 */

$defaults = array(
    'heading' => 'Our Best Seller',
    'subheading' => 'Loved for skin-friendly formulas, flattering shades, and all-day wear.',
    'cardsToShow' => 3,
    'items' => array(),
);

$atts = wp_parse_args($attributes, $defaults);
$items = $atts['items'];
$cards_to_show = max(1, intval($atts['cardsToShow']));

if (empty($items)) {
    if (is_admin()) {
        echo '<div class="product-slide-placeholder">Add products via the sidebar.</div>';
    }
    return;
}

$unique_id = 'ps-' . uniqid();
?>
<div class="wp-block-noyona-product-slide product-slide alignwide" id="<?php echo esc_attr($unique_id); ?>"
    data-cards-to-show="<?php echo esc_attr($cards_to_show); ?>">
    <div class="product-slide__header">
        <?php if ($atts['heading']): ?>
            <?php
            $heading = (string) $atts['heading'];
            $heading_html = esc_html($heading);

            $pos_best = stripos($heading, 'Best Seller');
            if ($pos_best !== false) {
                $len = strlen('Best Seller');
                $before = substr($heading, 0, $pos_best);
                $match = substr($heading, $pos_best, $len);
                $after = substr($heading, $pos_best + $len);
                $heading_html = esc_html($before) . '<span class="product-slide__heading-accent">' . esc_html($match) . '</span>' . esc_html($after);
            } else {
                $pos_best = stripos($heading, 'Best');
                if ($pos_best !== false) {
                    $before = substr($heading, 0, $pos_best);
                    $match = substr($heading, $pos_best, strlen('Best'));
                    $after = substr($heading, $pos_best + strlen('Best'));
                    $heading_html = esc_html($before) . '<span class="product-slide__heading-accent">' . esc_html($match) . '</span>' . esc_html($after);
                }
            }
            ?>
            <h2 class="product-slide__heading">
                <?php echo $heading_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </h2>
        <?php endif; ?>
        <?php if ($atts['subheading']): ?>
            <p class="product-slide__subheading"><?php echo esc_html($atts['subheading']); ?></p>
        <?php endif; ?>
    </div>

    <div class="product-slide__carousel">
        <button class="ps-nav-btn ps-prev" aria-label="Previous">
            <i class="fa-solid fa-chevron-left"></i>
        </button>

        <div class="product-slide__track-wrap">
            <div class="product-slide__track" style="--cards-visible: <?php echo esc_attr($cards_to_show); ?>;">
                <?php foreach ($items as $item): ?>
                    <?php
                    $title = isset($item['title']) ? $item['title'] : '';
                    $image = isset($item['image']) ? (string) $item['image'] : '';
                    $image_id = isset($item['imageId']) ? absint($item['imageId']) : 0;
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
                    $primary_text = !empty($item['primaryText']) ? $item['primaryText'] : 'Buy Now';
                    $primary_url = !empty($item['primaryUrl']) ? $item['primaryUrl'] : '#';
                    $primary_bg = !empty($item['primaryBg']) ? $item['primaryBg'] : '#E30B5D';
                    $cart_enabled = !empty($item['cartEnabled']);
                    $cart_url = !empty($item['cartUrl']) ? $item['cartUrl'] : '#';
                    $cart_bg = !empty($item['cartBg']) ? $item['cartBg'] : $primary_bg;
                    $media_bg = !empty($item['mediaBg']) ? $item['mediaBg'] : '';
                    $media_style = $media_bg ? ' style="--ps-media-bg: ' . esc_attr($media_bg) . ';"' : '';
                    ?>
                    <div class="product-slide__card">
                        <div class="ps-card">
                            <div class="ps-card__media" <?php echo $media_style; ?>>
                                <?php if (!empty($item['badge'])): ?>
                                    <span class="ps-card__badge"><?php echo esc_html($item['badge']); ?></span>
                                <?php endif; ?>

                                <?php if (!empty($image)): ?>
                                    <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($title); ?>"
                                        class="ps-card__image" loading="lazy" decoding="async" />
                                <?php endif; ?>
                            </div>

                            <div class="ps-card__body">
                                <?php if (!empty($item['colors']) && is_array($item['colors'])): ?>
                                    <div class="ps-card__swatches">
                                        <?php foreach ($item['colors'] as $color): ?>
                                            <span class="ps-swatch"
                                                style="background-color: <?php echo esc_attr($color); ?>;"></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($title): ?>
                                    <h3 class="ps-card__title"><?php echo esc_html($title); ?></h3>
                                <?php endif; ?>

                                <?php if (!empty($item['description'])): ?>
                                    <p class="ps-card__desc"><?php echo esc_html($item['description']); ?></p>
                                <?php endif; ?>

                                <div class="ps-card__meta-row">
                                    <div class="ps-card__price-row">
                                        <?php if (!empty($item['price'])): ?>
                                            <span class="ps-price"><?php echo esc_html($item['price']); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($item['compareAt'])): ?>
                                            <span class="ps-compare-price"><?php echo esc_html($item['compareAt']); ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- <div class="ps-card__rating">
                                        <?php
                                        $rating = isset($item['rating']) ? floatval($item['rating']) : 0;
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $rating) {
                                                echo '<i class="fa-solid fa-star"></i>';
                                            } elseif ($i - 0.5 <= $rating) {
                                                echo '<i class="fa-solid fa-star-half-stroke"></i>';
                                            } else {
                                                echo '<i class="fa-regular fa-star"></i>';
                                            }
                                        }
                                        ?>
                                        <?php if (!empty($item['ratingCount'])): ?>
                                            <span class="ps-rating-count"><?php echo esc_html('(' . $item['ratingCount'] . ')'); ?></span>
                                        <?php endif; ?>
                                    </div> -->
                                </div>

                                <div class="ps-card__actions">
                                    <a href="<?php echo esc_url($primary_url); ?>" class="ps-btn-primary"
                                        style="background-color: <?php echo esc_attr($primary_bg); ?>;">
                                        <?php echo esc_html($primary_text); ?>
                                    </a>
                                    <?php if ($cart_enabled): ?>
                                        <a href="/coming-soon" class="ps-btn-cart"
                                            style="background-color: <?php echo esc_attr($cart_bg); ?>;"
                                            aria-label="<?php echo esc_attr( 'Add ' . ( $title ? $title : 'product' ) . ' to cart' ); ?>">
                                            <i class="fa-solid fa-cart-shopping"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <button class="ps-nav-btn ps-next" aria-label="Next">
            <i class="fa-solid fa-chevron-right"></i>
        </button>
    </div>

    <div class="product-slide__dots"></div>
</div>