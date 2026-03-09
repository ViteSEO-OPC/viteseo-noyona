<?php
/**
 * Product Slide Block Template.
 */

$defaults = array(
    'heading' => 'Our Best Seller',
    'subheading' => 'Loved for skin-friendly formulas, flattering shades, and all-day wear.',
    'cardsToShow' => 3,
    'useWooProducts' => false,
    'wooOnlyFeatured' => true,
    'wooProductsLimit' => 8,
    'wooColorAttribute' => 'pa_color',
    'items' => array(),
);

$atts = wp_parse_args($attributes, $defaults);
$items = is_array($atts['items']) ? $atts['items'] : array();
$cards_to_show = max(1, intval($atts['cardsToShow']));
$use_woo_products = !empty($atts['useWooProducts']);
$woo_only_featured = !empty($atts['wooOnlyFeatured']);
$woo_products_limit = isset($atts['wooProductsLimit']) ? max(1, (int) $atts['wooProductsLimit']) : 8;
$woo_color_attribute = isset($atts['wooColorAttribute']) ? sanitize_title((string) $atts['wooColorAttribute']) : 'pa_color';

if (class_exists('WooCommerce')) {
    wp_enqueue_script('wc-add-to-cart');
    wp_enqueue_script('wc-cart-fragments');
}

if (!function_exists('noyona_ps_normalize_hex_color')) {
    function noyona_ps_normalize_hex_color($value)
    {
        $value = trim((string) $value);
        if ('' === $value) {
            return '';
        }
        $hex = sanitize_hex_color($value);
        if ($hex) {
            return $hex;
        }
        if (preg_match('/^[A-Fa-f0-9]{6}$/', $value)) {
            return '#' . $value;
        }
        if (preg_match('/^[A-Fa-f0-9]{3}$/', $value)) {
            return '#' . $value;
        }
        return '';
    }
}

if (!function_exists('noyona_ps_get_product_swatch_colors')) {
    function noyona_ps_get_product_swatch_colors($product_id, $attribute_taxonomy, $product = null)
    {
        $colors = array();
        $attribute_taxonomy = sanitize_title((string) $attribute_taxonomy);
        $candidate_taxonomies = array($attribute_taxonomy);

        // Allow either "color" or "pa_color" input in block attributes.
        if (0 === strpos($attribute_taxonomy, 'pa_')) {
            $candidate_taxonomies[] = substr($attribute_taxonomy, 3);
        } else {
            $candidate_taxonomies[] = 'pa_' . $attribute_taxonomy;
        }
        $candidate_taxonomies = array_values(array_unique(array_filter($candidate_taxonomies)));

        foreach ($candidate_taxonomies as $taxonomy) {
            if ('' === $taxonomy || !taxonomy_exists($taxonomy)) {
                continue;
            }
            $terms = get_the_terms($product_id, $taxonomy);
            if (!is_array($terms)) {
                continue;
            }
            foreach ($terms as $term) {
                $raw = get_term_meta($term->term_id, 'color', true);
                if ('' === (string) $raw) {
                    $raw = get_term_meta($term->term_id, 'value', true);
                }
                if ('' === (string) $raw) {
                    $raw = get_term_meta($term->term_id, 'swatch_color', true);
                }
                if ('' === (string) $raw) {
                    $raw = $term->slug;
                }
                $hex = noyona_ps_normalize_hex_color($raw);
                if ($hex) {
                    $colors[] = $hex;
                }
            }
            if (!empty($colors)) {
                break;
            }
        }

        // Fallback: read product attributes and resolve options to terms/slugs.
        if (empty($colors) && $product instanceof WC_Product) {
            $product_attributes = $product->get_attributes();
            if (is_array($product_attributes) && !empty($product_attributes)) {
                foreach ($product_attributes as $product_attribute) {
                    if (!$product_attribute || !method_exists($product_attribute, 'get_name')) {
                        continue;
                    }
                    $name = sanitize_title((string) $product_attribute->get_name());
                    if (!in_array($name, $candidate_taxonomies, true)) {
                        continue;
                    }

                    $options = method_exists($product_attribute, 'get_options') ? $product_attribute->get_options() : array();
                    if (!is_array($options)) {
                        $options = array();
                    }
                    foreach ($options as $option) {
                        if ($product_attribute->is_taxonomy()) {
                            $term = get_term_by('id', (int) $option, $name);
                            if (!$term) {
                                $term = get_term_by('slug', (string) $option, $name);
                            }
                            if ($term && !is_wp_error($term)) {
                                $raw = get_term_meta($term->term_id, 'color', true);
                                if ('' === (string) $raw) {
                                    $raw = get_term_meta($term->term_id, 'value', true);
                                }
                                if ('' === (string) $raw) {
                                    $raw = get_term_meta($term->term_id, 'swatch_color', true);
                                }
                                if ('' === (string) $raw) {
                                    $raw = $term->slug;
                                }
                                $hex = noyona_ps_normalize_hex_color($raw);
                                if ($hex) {
                                    $colors[] = $hex;
                                }
                            }
                        } else {
                            $hex = noyona_ps_normalize_hex_color($option);
                            if ($hex) {
                                $colors[] = $hex;
                            }
                        }
                    }
                }
            }
        }

        if (empty($colors)) {
            $meta_candidates = array(
                '_noyona_colors',
                'noyona_colors',
                '_product_colors',
                'product_colors',
            );
            foreach ($meta_candidates as $meta_key) {
                $meta_value = get_post_meta($product_id, $meta_key, true);
                if (is_array($meta_value)) {
                    foreach ($meta_value as $raw) {
                        $hex = noyona_ps_normalize_hex_color($raw);
                        if ($hex) {
                            $colors[] = $hex;
                        }
                    }
                } elseif (is_string($meta_value) && '' !== trim($meta_value)) {
                    $parts = preg_split('/[\s,|]+/', $meta_value);
                    if (is_array($parts)) {
                        foreach ($parts as $raw) {
                            $hex = noyona_ps_normalize_hex_color($raw);
                            if ($hex) {
                                $colors[] = $hex;
                            }
                        }
                    }
                }
                if (!empty($colors)) {
                    break;
                }
            }
        }

        return array_values(array_unique($colors));
    }
}

if ($use_woo_products && class_exists('WooCommerce')) {
    $args = array(
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => $woo_products_limit,
        'orderby' => 'date',
        'order' => 'DESC',
    );

    if ($woo_only_featured) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'product_visibility',
                'field' => 'name',
                'terms' => array('featured'),
                'operator' => 'IN',
            ),
        );
    }

    $woo_query = new WP_Query($args);
    $woo_items = array();

    if ($woo_query->have_posts()) {
        while ($woo_query->have_posts()) {
            $woo_query->the_post();
            $product_id = get_the_ID();
            $product = wc_get_product($product_id);
            if (!$product) {
                continue;
            }

            $swatches = noyona_ps_get_product_swatch_colors($product_id, $woo_color_attribute, $product);

            $woo_items[] = array(
                'badge' => $woo_only_featured ? 'BEST' : '',
                'imageId' => get_post_thumbnail_id($product_id),
                'title' => get_the_title($product_id),
                'description' => wp_trim_words(wp_strip_all_tags($product->get_short_description() ?: get_the_excerpt($product_id)), 18),
                'price' => wp_strip_all_tags(wc_price((float) $product->get_price())),
                'compareAt' => $product->is_on_sale() && $product->get_regular_price() !== '' ? wp_strip_all_tags(wc_price((float) $product->get_regular_price())) : '',
                'rating' => (float) $product->get_average_rating(),
                'ratingCount' => (int) $product->get_rating_count(),
                'colors' => $swatches,
                'primaryText' => 'BUY NOW!',
                'primaryUrl' => get_permalink($product_id),
                'primaryBg' => '#E199A4',
                'cartEnabled' => true,
                'cartUrl' => $product->add_to_cart_url(),
                'cartAjax' => $product->supports('ajax_add_to_cart'),
                'cartBg' => '#E199A4',
                'productId' => $product_id,
            );
        }
        wp_reset_postdata();
    }

    if (!empty($woo_items)) {
        $items = $woo_items;
    }
}

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
                    $image_alt = $title ? $title : 'Product image';
                    $primary_text = !empty($item['primaryText']) ? $item['primaryText'] : 'Buy Now';
                    $primary_url = !empty($item['primaryUrl']) ? $item['primaryUrl'] : '#';
                    $primary_bg = !empty($item['primaryBg']) ? $item['primaryBg'] : '#E199A4';
                    $cart_enabled = !empty($item['cartEnabled']);
                    $cart_url = !empty($item['cartUrl']) ? $item['cartUrl'] : '#';
                    $cart_ajax = !empty($item['cartAjax']);
                    $cart_bg = !empty($item['cartBg']) ? $item['cartBg'] : $primary_bg;
                    $media_bg = !empty($item['mediaBg']) ? $item['mediaBg'] : '';
                    $media_style = $media_bg ? ' style="--ps-media-bg: ' . esc_attr($media_bg) . ';"' : '';
                    $product_id = isset($item['productId']) ? absint($item['productId']) : 0;
                    ?>
                    <div class="product-slide__card">
                        <div class="ps-card">
                            <div class="ps-card__media" <?php echo $media_style; ?>>
                                <?php if (!empty($item['badge'])): ?>
                                    <span class="ps-card__badge"><?php echo esc_html($item['badge']); ?></span>
                                <?php endif; ?>

                                <?php if (!empty($image)): ?>
                                    <?php if ($image_id): ?>
                                        <?php
                                        echo wp_get_attachment_image(
                                            $image_id,
                                            'large',
                                            false,
                                            array(
                                                'class' => 'ps-card__image',
                                                'alt' => $image_alt,
                                                'loading' => 'lazy',
                                                'decoding' => 'async',
                                                'sizes' => '(max-width: 780px) 90vw, (max-width: 1280px) 50vw, 406px',
                                            )
                                        );
                                        ?>
                                    <?php else: ?>
                                        <img
                                            src="<?php echo esc_url($image); ?>"
                                            alt="<?php echo esc_attr($image_alt); ?>"
                                            class="ps-card__image"
                                            loading="lazy"
                                            decoding="async"
                                            sizes="(max-width: 780px) 90vw, (max-width: 1280px) 50vw, 406px"
                                        />
                                    <?php endif; ?>
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
                                    <!-- <p class="ps-card__desc"><?php echo esc_html($item['description']); ?></p> -->
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
                                        <?php if ($product_id > 0 && $cart_ajax): ?>
                                            <button
                                                type="button"
                                                class="ps-btn-cart add_to_cart_button ajax_add_to_cart"
                                                data-product_id="<?php echo esc_attr($product_id); ?>"
                                                data-quantity="1"
                                                data-cart-url="<?php echo esc_url($cart_url); ?>"
                                                style="background-color: <?php echo esc_attr($cart_bg); ?>;"
                                                aria-label="<?php echo esc_attr('Add ' . ($title ? $title : 'product') . ' to cart'); ?>">
                                                <i class="fa-solid fa-cart-shopping"></i>
                                            </button>
                                        <?php else: ?>
                                            <a href="<?php echo esc_url($cart_url); ?>" class="ps-btn-cart"
                                                style="background-color: <?php echo esc_attr($cart_bg); ?>;"
                                                aria-label="<?php echo esc_attr('Add ' . ($title ? $title : 'product') . ' to cart'); ?>">
                                                <i class="fa-solid fa-cart-shopping"></i>
                                            </a>
                                        <?php endif; ?>
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