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
    'wooShowFeaturedBadge' => true,
    'wooProductsLimit' => 8,
    'wooProductCategories' => array(),
    'wooProductBrands' => array(),
    'wooBrandTaxonomy' => '',
    'wooColorAttribute' => 'pa_color',
    'mediaBackgroundColor' => '#F2A0A7',
    'cardBorderColor' => '#D91B61',
    'ctaBackgroundColor' => '#E199A4',
    'ctaTextColor' => '#D81B60',
    'ctaHoverBackgroundColor' => '#D81B60',
    'ctaHoverTextColor' => '#FFFFFF',
    'swatchSelectedColor' => '#D91B61',
    'items' => array(),
);

$atts = wp_parse_args($attributes, $defaults);
$items = is_array($atts['items']) ? $atts['items'] : array();
$cards_to_show = max(1, intval($atts['cardsToShow']));
$use_woo_products = !empty($atts['useWooProducts']);
$woo_only_featured = !empty($atts['wooOnlyFeatured']);
$woo_show_featured_badge = !empty($atts['wooShowFeaturedBadge']);
$woo_products_limit = isset($atts['wooProductsLimit']) ? max(1, (int) $atts['wooProductsLimit']) : 8;
$woo_product_categories = array();
if (isset($atts['wooProductCategories'])) {
    $raw_woo_product_categories = is_array($atts['wooProductCategories'])
        ? $atts['wooProductCategories']
        : preg_split('/[\s,]+/', (string) $atts['wooProductCategories']);
    if (is_array($raw_woo_product_categories)) {
        $woo_product_categories = array_values(array_unique(array_filter(array_map('sanitize_title', $raw_woo_product_categories))));
    }
}
$woo_product_brands = array();
if (isset($atts['wooProductBrands'])) {
    $raw_woo_product_brands = is_array($atts['wooProductBrands'])
        ? $atts['wooProductBrands']
        : preg_split('/[\s,]+/', (string) $atts['wooProductBrands']);
    if (is_array($raw_woo_product_brands)) {
        $woo_product_brands = array_values(array_unique(array_filter(array_map('sanitize_title', $raw_woo_product_brands))));
    }
}
$woo_brand_taxonomy = isset($atts['wooBrandTaxonomy']) ? sanitize_key((string) $atts['wooBrandTaxonomy']) : '';
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

if (!function_exists('noyona_ps_get_product_swatch_options')) {
    function noyona_ps_get_product_swatch_options($product_id, $attribute_taxonomy, $product = null)
    {
        $attribute_taxonomy = sanitize_title((string) $attribute_taxonomy);
        $candidate_taxonomies = array($attribute_taxonomy);
        $options = array();
        $seen = array();

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
                if (!$hex) {
                    continue;
                }
                $key = strtolower($hex . '|' . $term->slug);
                if (isset($seen[$key])) {
                    continue;
                }
                $options[] = array(
                    'value' => (string) $term->slug,
                    'label' => (string) $term->name,
                    'hex' => $hex,
                );
                $seen[$key] = true;
            }
            if (!empty($options)) {
                break;
            }
        }

        // Fallback: read product attributes and resolve options to terms/slugs.
        if (empty($options) && $product instanceof WC_Product) {
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

                    $attr_options = method_exists($product_attribute, 'get_options') ? $product_attribute->get_options() : array();
                    if (!is_array($attr_options)) {
                        $attr_options = array();
                    }
                    foreach ($attr_options as $option) {
                        if ($product_attribute->is_taxonomy()) {
                            $term = get_term_by('id', (int) $option, $name);
                            if (!$term) {
                                $term = get_term_by('slug', (string) $option, $name);
                            }
                            if (!$term || is_wp_error($term)) {
                                continue;
                            }
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
                            if (!$hex) {
                                continue;
                            }
                            $key = strtolower($hex . '|' . $term->slug);
                            if (isset($seen[$key])) {
                                continue;
                            }
                            $options[] = array(
                                'value' => (string) $term->slug,
                                'label' => (string) $term->name,
                                'hex' => $hex,
                            );
                            $seen[$key] = true;
                        } else {
                            $hex = noyona_ps_normalize_hex_color($option);
                            if (!$hex) {
                                continue;
                            }
                            $value = trim((string) $option);
                            if ('' === $value) {
                                continue;
                            }
                            $key = strtolower($hex . '|' . $value);
                            if (isset($seen[$key])) {
                                continue;
                            }
                            $options[] = array(
                                'value' => $value,
                                'label' => $value,
                                'hex' => $hex,
                            );
                            $seen[$key] = true;
                        }
                    }
                }
            }
        }

        // Final fallback: parse custom meta color lists if present.
        if (empty($options)) {
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
                        if (!$hex) {
                            continue;
                        }
                        $value = trim((string) $raw);
                        if ('' === $value) {
                            continue;
                        }
                        $key = strtolower($hex . '|' . $value);
                        if (isset($seen[$key])) {
                            continue;
                        }
                        $options[] = array(
                            'value' => $value,
                            'label' => $value,
                            'hex' => $hex,
                        );
                        $seen[$key] = true;
                    }
                } elseif (is_string($meta_value) && '' !== trim($meta_value)) {
                    $parts = preg_split('/[\s,|]+/', $meta_value);
                    if (is_array($parts)) {
                        foreach ($parts as $raw) {
                            $hex = noyona_ps_normalize_hex_color($raw);
                            if (!$hex) {
                                continue;
                            }
                            $value = trim((string) $raw);
                            if ('' === $value) {
                                continue;
                            }
                            $key = strtolower($hex . '|' . $value);
                            if (isset($seen[$key])) {
                                continue;
                            }
                            $options[] = array(
                                'value' => $value,
                                'label' => $value,
                                'hex' => $hex,
                            );
                            $seen[$key] = true;
                        }
                    }
                }
                if (!empty($options)) {
                    break;
                }
            }
        }

        return array_values($options);
    }
}

if (!function_exists('noyona_ps_get_variation_map_for_attribute')) {
    /**
     * Build a map of attribute value => variation ID for variable products.
     *
     * @param WC_Product $product        Product instance.
     * @param string     $attribute_param Attribute param key like attribute_pa_color.
     * @param array      $swatches       Swatch options from this block.
     * @return array<string,int>
     */
    function noyona_ps_get_variation_map_for_attribute($product, $attribute_param, $swatches = array())
    {
        if (!$product instanceof WC_Product || !$product->is_type('variable')) {
            return array();
        }

        $attribute_param = sanitize_key((string) $attribute_param);
        if ('' === $attribute_param) {
            return array();
        }

        $normalized_attribute = $attribute_param;
        if (0 === strpos($normalized_attribute, 'attribute_')) {
            $normalized_attribute = substr($normalized_attribute, 10);
        }
        if ('' === $normalized_attribute) {
            return array();
        }

        $allowed_values = array();
        if (is_array($swatches)) {
            foreach ($swatches as $swatch) {
                if (!is_array($swatch) || empty($swatch['value'])) {
                    continue;
                }
                $allowed_values[] = sanitize_title((string) $swatch['value']);
            }
        }
        $allowed_values = array_values(array_unique(array_filter($allowed_values)));

        $map = array();
        $variation_ids = method_exists($product, 'get_children') ? $product->get_children() : array();
        if (!is_array($variation_ids) || empty($variation_ids)) {
            return array();
        }

        foreach ($variation_ids as $variation_id) {
            $variation = wc_get_product((int) $variation_id);
            if (!$variation instanceof WC_Product_Variation) {
                continue;
            }
            if (!$variation->exists() || 'publish' !== $variation->get_status()) {
                continue;
            }

            $attributes = $variation->get_attributes();
            if (!is_array($attributes) || empty($attributes)) {
                continue;
            }

            $attribute_value = '';
            foreach ($attributes as $key => $value) {
                $normalized_key = sanitize_key((string) $key);
                if ($normalized_key === $normalized_attribute || $normalized_key === ('attribute_' . $normalized_attribute)) {
                    $attribute_value = sanitize_title((string) $value);
                    break;
                }
            }

            if ('' === $attribute_value) {
                continue;
            }
            if (!empty($allowed_values) && !in_array($attribute_value, $allowed_values, true)) {
                continue;
            }
            if (!isset($map[$attribute_value])) {
                $map[$attribute_value] = (int) $variation->get_id();
            }
        }

        return $map;
    }
}

if (!function_exists('noyona_ps_get_variation_choice_map')) {
    /**
     * Build a resilient swatch value => variation payload map.
     *
     * This map does not assume a specific attribute taxonomy. It inspects all variation
     * attributes and links swatch values to their first matching variation.
     *
     * @param WC_Product $product  Product instance.
     * @param array      $swatches Swatch options from block data.
     * @return array<string,array{variationId:int,attributeParam:string,attributeValue:string}>
     */
    function noyona_ps_get_variation_choice_map($product, $swatches = array())
    {
        if (!$product instanceof WC_Product || !$product->is_type('variable')) {
            return array();
        }

        $allowed_values = array();
        $swatch_values_by_hex = array();
        $swatch_aliases = array();
        if (is_array($swatches)) {
            foreach ($swatches as $swatch) {
                if (!is_array($swatch) || empty($swatch['value'])) {
                    continue;
                }
                $swatch_value = sanitize_title((string) $swatch['value']);
                if ('' === $swatch_value) {
                    continue;
                }
                $allowed_values[] = $swatch_value;
                if (!isset($swatch_aliases[$swatch_value])) {
                    $swatch_aliases[$swatch_value] = array();
                }
                $swatch_aliases[$swatch_value][] = $swatch_value;

                if (!empty($swatch['label'])) {
                    $label_alias = sanitize_title((string) $swatch['label']);
                    if ('' !== $label_alias) {
                        $swatch_aliases[$swatch_value][] = $label_alias;
                    }
                }
                $swatch_hex = '';
                if (!empty($swatch['hex'])) {
                    $swatch_hex = noyona_ps_normalize_hex_color((string) $swatch['hex']);
                }
                if ($swatch_hex) {
                    if (!isset($swatch_values_by_hex[$swatch_hex])) {
                        $swatch_values_by_hex[$swatch_hex] = array();
                    }
                    $swatch_values_by_hex[$swatch_hex][] = $swatch_value;
                    $swatch_aliases[$swatch_value][] = sanitize_title(str_replace('#', '', (string) $swatch_hex));
                }
                $swatch_aliases[$swatch_value] = array_values(array_unique(array_filter($swatch_aliases[$swatch_value])));
            }
        }
        $allowed_values = array_values(array_unique(array_filter($allowed_values)));

        $map = array();
        $variation_ids = method_exists($product, 'get_children') ? $product->get_children() : array();
        if (!is_array($variation_ids) || empty($variation_ids)) {
            return array();
        }

        foreach ($variation_ids as $variation_id) {
            $variation = wc_get_product((int) $variation_id);
            if (!$variation instanceof WC_Product_Variation) {
                continue;
            }
            if (!$variation->exists() || 'publish' !== $variation->get_status()) {
                continue;
            }

            $attributes = $variation->get_attributes();
            if (!is_array($attributes) || empty($attributes)) {
                continue;
            }

            foreach ($attributes as $key => $value) {
                $attribute_value = sanitize_title((string) $value);
                if ('' === $attribute_value) {
                    continue;
                }

                $attribute_key_raw = sanitize_key((string) $key);
                if ('' === $attribute_key_raw) {
                    continue;
                }
                $attribute_key = $attribute_key_raw;
                if (0 === strpos($attribute_key, 'attribute_')) {
                    $attribute_key = substr($attribute_key, 10);
                }
                if ('' === $attribute_key) {
                    continue;
                }

                $target_swatch_values = array();
                if (empty($allowed_values) || in_array($attribute_value, $allowed_values, true)) {
                    $target_swatch_values[] = $attribute_value;
                }
                if (!empty($swatch_aliases)) {
                    foreach ($swatch_aliases as $swatch_key => $aliases) {
                        if (!is_array($aliases) || empty($aliases)) {
                            continue;
                        }
                        if (in_array($attribute_value, $aliases, true)) {
                            $target_swatch_values[] = (string) $swatch_key;
                        }
                    }
                }

                $variation_hex = '';
                if (taxonomy_exists($attribute_key)) {
                    $term = get_term_by('slug', $attribute_value, $attribute_key);
                    if ($term && !is_wp_error($term)) {
                        $variation_hex = noyona_ps_normalize_hex_color((string) get_term_meta($term->term_id, 'color', true));
                        if (!$variation_hex) {
                            $variation_hex = noyona_ps_normalize_hex_color((string) get_term_meta($term->term_id, 'value', true));
                        }
                        if (!$variation_hex) {
                            $variation_hex = noyona_ps_normalize_hex_color((string) get_term_meta($term->term_id, 'swatch_color', true));
                        }
                    }
                }
                if ($variation_hex && isset($swatch_values_by_hex[$variation_hex]) && is_array($swatch_values_by_hex[$variation_hex])) {
                    $target_swatch_values = array_merge($target_swatch_values, $swatch_values_by_hex[$variation_hex]);
                }

                $target_swatch_values = array_values(array_unique(array_filter($target_swatch_values)));
                foreach ($target_swatch_values as $target_swatch_value) {
                    if (isset($map[$target_swatch_value])) {
                        continue;
                    }
                    $map[$target_swatch_value] = array(
                        'variationId' => (int) $variation->get_id(),
                        'attributeParam' => 'attribute_' . $attribute_key,
                        'attributeValue' => $attribute_value,
                    );
                }
            }
        }

        return $map;
    }
}

if (!function_exists('noyona_ps_attribute_param_from_name')) {
    function noyona_ps_attribute_param_from_name($name)
    {
        $name = sanitize_title((string) $name);
        if ('' === $name) {
            return '';
        }
        return 'attribute_' . $name;
    }
}

if (!function_exists('noyona_ps_get_attribute_display_label')) {
    function noyona_ps_get_attribute_display_label($attribute_name)
    {
        $attribute_name = sanitize_title((string) $attribute_name);
        if ('' === $attribute_name) {
            return __('Option', 'viteseo-noyona-childtheme');
        }

        if (function_exists('wc_attribute_label')) {
            $label = wc_attribute_label($attribute_name);
            if ('' !== trim((string) $label)) {
                return (string) $label;
            }
        }

        $label = preg_replace('/^pa[_-]/', '', $attribute_name);
        $label = str_replace(array('-', '_'), ' ', (string) $label);
        return ucwords($label);
    }
}

if (!function_exists('noyona_ps_get_product_choice_groups')) {
    function noyona_ps_get_product_choice_groups($product, $exclude_attribute = '')
    {
        if (!$product instanceof WC_Product) {
            return array();
        }

        $exclude_attribute = sanitize_title((string) $exclude_attribute);
        $exclude_candidates = array_filter(array_unique(array(
            $exclude_attribute,
            0 === strpos($exclude_attribute, 'pa_') ? substr($exclude_attribute, 3) : 'pa_' . $exclude_attribute,
        )));

        $groups = array();
        $attributes = $product->get_attributes();
        if (!is_array($attributes) || empty($attributes)) {
            return array();
        }

        foreach ($attributes as $product_attribute) {
            if (!$product_attribute || !method_exists($product_attribute, 'get_name')) {
                continue;
            }

            if (method_exists($product_attribute, 'get_variation') && !$product_attribute->get_variation()) {
                continue;
            }

            $name = sanitize_title((string) $product_attribute->get_name());
            if ('' === $name || in_array($name, $exclude_candidates, true)) {
                continue;
            }

            $options = method_exists($product_attribute, 'get_options') ? $product_attribute->get_options() : array();
            if (!is_array($options) || empty($options)) {
                continue;
            }

            $choices = array();
            $seen = array();
            foreach ($options as $option) {
                $value = '';
                $label = '';

                if ($product_attribute->is_taxonomy()) {
                    $term = get_term_by('id', (int) $option, $name);
                    if (!$term) {
                        $term = get_term_by('slug', (string) $option, $name);
                    }
                    if (!$term || is_wp_error($term)) {
                        continue;
                    }
                    $value = (string) $term->slug;
                    $label = (string) $term->name;
                } else {
                    $value = trim((string) $option);
                    $label = $value;
                }

                $key = sanitize_title($value);
                if ('' === $key || isset($seen[$key])) {
                    continue;
                }

                $choices[] = array(
                    'value' => $key,
                    'label' => '' !== trim($label) ? $label : $value,
                );
                $seen[$key] = true;
            }

            if (empty($choices)) {
                continue;
            }

            $groups[] = array(
                'name' => $name,
                'param' => noyona_ps_attribute_param_from_name($name),
                'label' => noyona_ps_get_attribute_display_label($name),
                'options' => $choices,
            );
        }

        return $groups;
    }
}

if (!function_exists('noyona_ps_get_variation_combinations')) {
    function noyona_ps_get_variation_combinations($product)
    {
        if (!$product instanceof WC_Product || !$product->is_type('variable')) {
            return array();
        }

        $combinations = array();
        $variation_ids = method_exists($product, 'get_children') ? $product->get_children() : array();
        if (!is_array($variation_ids) || empty($variation_ids)) {
            return array();
        }

        foreach ($variation_ids as $variation_id) {
            $variation = wc_get_product((int) $variation_id);
            if (!$variation instanceof WC_Product_Variation || !$variation->exists() || 'publish' !== $variation->get_status()) {
                continue;
            }

            $attributes = array();
            foreach ((array) $variation->get_attributes() as $key => $value) {
                $key = sanitize_key((string) $key);
                $value = sanitize_title((string) $value);
                if ('' === $key || '' === $value) {
                    continue;
                }
                if (0 === strpos($key, 'attribute_')) {
                    $key = substr($key, 10);
                }
                $attributes['attribute_' . $key] = $value;
            }

            if (!empty($attributes)) {
                $combinations[] = array(
                    'variationId' => (int) $variation->get_id(),
                    'attributes' => $attributes,
                );
            }
        }

        return $combinations;
    }
}

$media_background_color = noyona_ps_normalize_hex_color($atts['mediaBackgroundColor'] ?? '') ?: '#F2A0A7';
$card_border_color = noyona_ps_normalize_hex_color($atts['cardBorderColor'] ?? '') ?: '#D91B61';
$cta_background_color = noyona_ps_normalize_hex_color($atts['ctaBackgroundColor'] ?? '') ?: '#E199A4';
$cta_text_color = noyona_ps_normalize_hex_color($atts['ctaTextColor'] ?? '') ?: '#D81B60';
$cta_hover_background_color = noyona_ps_normalize_hex_color($atts['ctaHoverBackgroundColor'] ?? '') ?: '#D81B60';
$cta_hover_text_color = noyona_ps_normalize_hex_color($atts['ctaHoverTextColor'] ?? '') ?: '#FFFFFF';
$swatch_selected_color = noyona_ps_normalize_hex_color($atts['swatchSelectedColor'] ?? '') ?: '#D91B61';

if ($use_woo_products && class_exists('WooCommerce')) {
    $tax_query = array();
    $args = array(
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => $woo_products_limit,
        'orderby' => 'date',
        'order' => 'DESC',
    );

    if ($woo_only_featured) {
        $tax_query[] = array(
            'taxonomy' => 'product_visibility',
            'field' => 'name',
            'terms' => array('featured'),
            'operator' => 'IN',
        );
    }

    if (!empty($woo_product_categories)) {
        $tax_query[] = array(
            'taxonomy' => 'product_cat',
            'field' => 'slug',
            'terms' => $woo_product_categories,
            'operator' => 'IN',
        );
    }

    if (!empty($woo_product_brands)) {
        $brand_taxonomy_candidates = array_filter(array_unique(array(
            $woo_brand_taxonomy,
            'product_brand',
            'pwb-brand',
            'yith_product_brand',
            'pa_brand',
        )));
        foreach ($brand_taxonomy_candidates as $brand_taxonomy) {
            if (taxonomy_exists($brand_taxonomy)) {
                $tax_query[] = array(
                    'taxonomy' => $brand_taxonomy,
                    'field' => 'slug',
                    'terms' => $woo_product_brands,
                    'operator' => 'IN',
                );
                break;
            }
        }
    }

    if (!empty($tax_query)) {
        if (count($tax_query) > 1) {
            $tax_query['relation'] = 'AND';
        }
        $args['tax_query'] = $tax_query;
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

            $swatches = noyona_ps_get_product_swatch_options($product_id, $woo_color_attribute, $product);
            $swatch_colors = array();
            if (is_array($swatches)) {
                foreach ($swatches as $swatch) {
                    if (!is_array($swatch) || empty($swatch['hex'])) {
                        continue;
                    }
                    $swatch_colors[] = (string) $swatch['hex'];
                }
            }
            $attribute_param = 'attribute_' . (0 === strpos($woo_color_attribute, 'pa_') ? $woo_color_attribute : 'pa_' . $woo_color_attribute);
            $choice_groups = noyona_ps_get_product_choice_groups($product, $woo_color_attribute);
            $variation_map = noyona_ps_get_variation_map_for_attribute($product, $attribute_param, $swatches);
            $variation_choice_map = noyona_ps_get_variation_choice_map($product, $swatches);
            $variation_combinations = noyona_ps_get_variation_combinations($product);
            $can_ajax_cart = $product->supports('ajax_add_to_cart') || !empty($variation_map) || !empty($variation_choice_map) || !empty($variation_combinations);
            $is_featured_product = has_term('featured', 'product_visibility', $product_id);

            $woo_items[] = array(
                'badge' => ($woo_show_featured_badge && $is_featured_product) ? 'BEST' : '',
                'imageId' => get_post_thumbnail_id($product_id),
                'title' => get_the_title($product_id),
                'description' => wp_trim_words(wp_strip_all_tags($product->get_short_description() ?: get_the_excerpt($product_id)), 18),
                'price' => wp_strip_all_tags(wc_price((float) $product->get_price())),
                'compareAt' => $product->is_on_sale() && $product->get_regular_price() !== '' ? wp_strip_all_tags(wc_price((float) $product->get_regular_price())) : '',
                'rating' => (float) $product->get_average_rating(),
                'ratingCount' => (int) $product->get_rating_count(),
                'colors' => array_values(array_unique($swatch_colors)),
                'swatches' => $swatches,
                'choiceGroups' => $choice_groups,
                'attributeParam' => $attribute_param,
                'primaryText' => 'BUY NOW!',
                'primaryUrl' => get_permalink($product_id),
                'primaryBg' => $cta_background_color,
                'cartEnabled' => true,
                'cartUrl' => $product->add_to_cart_url(),
                'cartAjax' => $can_ajax_cart,
                'cartBg' => $cta_background_color,
                'productId' => $product_id,
                'productSku' => $product->get_sku(),
                'productType' => $product->get_type(),
                'variationMap' => $variation_map,
                'variationChoiceMap' => $variation_choice_map,
                'variationCombinations' => $variation_combinations,
            );
        }
        wp_reset_postdata();
    }

    if (!empty($woo_items)) {
        $items = $woo_items;
    } elseif (!empty($woo_product_categories) || !empty($woo_product_brands)) {
        $items = array();
    }
}

if (empty($items)) {
    if (is_admin()) {
        echo '<div class="product-slide-placeholder">Add products via the sidebar.</div>';
    }
    return;
}

$unique_id = 'ps-' . uniqid();
$carousel_classes = array('product-slide__carousel');
if (count($items) > 4) {
    $carousel_classes[] = 'product-slide__carousel--has-nav-space';
} else {
    $carousel_classes[] = 'product-slide__carousel--centered';
}
$block_style = sprintf(
    '--ps-media-bg:%1$s;--ps-card-border-color:%2$s;--ps-cta-bg:%3$s;--ps-cta-color:%4$s;--ps-cta-hover-bg:%5$s;--ps-cta-hover-color:%6$s;--ps-swatch-selected-color:%7$s;',
    esc_attr($media_background_color),
    esc_attr($card_border_color),
    esc_attr($cta_background_color),
    esc_attr($cta_text_color),
    esc_attr($cta_hover_background_color),
    esc_attr($cta_hover_text_color),
    esc_attr($swatch_selected_color)
);
?>
<div class="wp-block-noyona-product-slide product-slide alignwide" id="<?php echo esc_attr($unique_id); ?>" style="<?php echo esc_attr($block_style); ?>"
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

    <div class="<?php echo esc_attr(implode(' ', $carousel_classes)); ?>">
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
                    $primary_bg = noyona_ps_normalize_hex_color($item['primaryBg'] ?? '') ?: $cta_background_color;
                    $cart_enabled = !empty($item['cartEnabled']);
                    $cart_url = !empty($item['cartUrl']) ? $item['cartUrl'] : '#';
                    $cart_ajax = !empty($item['cartAjax']);
                    $cart_bg = noyona_ps_normalize_hex_color($item['cartBg'] ?? '') ?: $primary_bg;
                    $media_bg = noyona_ps_normalize_hex_color($item['mediaBg'] ?? '');
                    $media_style = $media_bg ? ' style="--ps-media-bg: ' . esc_attr($media_bg) . ';"' : '';
                    $product_id = isset($item['productId']) ? absint($item['productId']) : 0;
                    $ajax_cart_url = $product_id > 0 ? add_query_arg('add-to-cart', $product_id, home_url('/')) : $cart_url;
                    $product_sku = !empty($item['productSku']) ? sanitize_text_field((string) $item['productSku']) : '';
                    $product_type = !empty($item['productType']) ? sanitize_key((string) $item['productType']) : '';
                    $swatches = (!empty($item['swatches']) && is_array($item['swatches'])) ? $item['swatches'] : array();
                    $choice_groups = (!empty($item['choiceGroups']) && is_array($item['choiceGroups'])) ? $item['choiceGroups'] : array();
                    $attribute_param = !empty($item['attributeParam']) ? sanitize_key((string) $item['attributeParam']) : '';
                    $variation_map = (!empty($item['variationMap']) && is_array($item['variationMap'])) ? $item['variationMap'] : array();
                    $variation_choice_map = (!empty($item['variationChoiceMap']) && is_array($item['variationChoiceMap'])) ? $item['variationChoiceMap'] : array();
                    $variation_combinations = (!empty($item['variationCombinations']) && is_array($item['variationCombinations'])) ? $item['variationCombinations'] : array();
                    $variation_map_safe = array();
                    if (!empty($variation_map)) {
                        foreach ($variation_map as $value => $variation_id) {
                            $key = sanitize_title((string) $value);
                            $vid = absint($variation_id);
                            if ('' !== $key && $vid > 0) {
                                $variation_map_safe[$key] = $vid;
                            }
                        }
                    }
                    $variation_choice_map_safe = array();
                    if (!empty($variation_choice_map)) {
                        foreach ($variation_choice_map as $value => $choice) {
                            $key = sanitize_title((string) $value);
                            if ('' === $key || !is_array($choice)) {
                                continue;
                            }
                            $choice_variation_id = !empty($choice['variationId']) ? absint($choice['variationId']) : 0;
                            $choice_attribute_param = !empty($choice['attributeParam']) ? sanitize_key((string) $choice['attributeParam']) : '';
                            $choice_attribute_value = !empty($choice['attributeValue']) ? sanitize_title((string) $choice['attributeValue']) : '';
                            if ($choice_variation_id < 1 || '' === $choice_attribute_param || '' === $choice_attribute_value) {
                                continue;
                            }
                            $variation_choice_map_safe[$key] = array(
                                'variationId' => $choice_variation_id,
                                'attributeParam' => $choice_attribute_param,
                                'attributeValue' => $choice_attribute_value,
                            );
                        }
                    }
                    $variation_combinations_safe = array();
                    if (!empty($variation_combinations)) {
                        foreach ($variation_combinations as $combination) {
                            if (!is_array($combination) || empty($combination['variationId']) || empty($combination['attributes']) || !is_array($combination['attributes'])) {
                                continue;
                            }
                            $combination_attributes = array();
                            foreach ($combination['attributes'] as $param => $value) {
                                $param = sanitize_key((string) $param);
                                $value = sanitize_title((string) $value);
                                if ('' !== $param && '' !== $value) {
                                    $combination_attributes[$param] = $value;
                                }
                            }
                            if (!empty($combination_attributes)) {
                                $variation_combinations_safe[] = array(
                                    'variationId' => absint($combination['variationId']),
                                    'attributes' => $combination_attributes,
                                );
                            }
                        }
                    }
                    $selected_swatch_value = '';
                    if (!empty($swatches) && !empty($swatches[0]['value'])) {
                        $selected_swatch_value = sanitize_title((string) $swatches[0]['value']);
                    }
                    $selected_attributes = array();
                    if ($attribute_param && $selected_swatch_value) {
                        $selected_attributes[$attribute_param] = $selected_swatch_value;
                    }
                    if (!empty($choice_groups)) {
                        foreach ($choice_groups as $group) {
                            if (empty($group['param']) || empty($group['options']) || !is_array($group['options'])) {
                                continue;
                            }
                            $group_param = sanitize_key((string) $group['param']);
                            $first_option = reset($group['options']);
                            if ($group_param && is_array($first_option) && !empty($first_option['value'])) {
                                $selected_attributes[$group_param] = sanitize_title((string) $first_option['value']);
                            }
                        }
                    }
                    $selected_attr_param = $attribute_param;
                    $selected_attr_value = $selected_swatch_value;
                    $selected_variation_id = 0;
                    if (!empty($selected_swatch_value) && isset($variation_choice_map_safe[$selected_swatch_value])) {
                        $selected_choice = $variation_choice_map_safe[$selected_swatch_value];
                        $selected_attr_param = !empty($selected_choice['attributeParam']) ? (string) $selected_choice['attributeParam'] : $selected_attr_param;
                        $selected_attr_value = !empty($selected_choice['attributeValue']) ? (string) $selected_choice['attributeValue'] : $selected_attr_value;
                        $selected_variation_id = !empty($selected_choice['variationId']) ? absint($selected_choice['variationId']) : 0;
                    } elseif (!empty($selected_swatch_value) && isset($variation_map_safe[$selected_swatch_value])) {
                        $selected_variation_id = absint($variation_map_safe[$selected_swatch_value]);
                    }
                    if (!empty($selected_attributes) && !empty($variation_combinations_safe)) {
                        foreach ($variation_combinations_safe as $combination) {
                            $combination_attrs = isset($combination['attributes']) && is_array($combination['attributes']) ? $combination['attributes'] : array();
                            $matches = true;
                            foreach ($combination_attrs as $param => $value) {
                                if (isset($selected_attributes[$param]) && $selected_attributes[$param] !== $value) {
                                    $matches = false;
                                    break;
                                }
                            }
                            if ($matches && !empty($combination['variationId'])) {
                                $selected_variation_id = absint($combination['variationId']);
                                break;
                            }
                        }
                    }
                    if ($selected_attr_param && $selected_attr_value) {
                        if (!empty($primary_url) && '#' !== $primary_url) {
                            $primary_url = add_query_arg($selected_attr_param, $selected_attr_value, $primary_url);
                        }
                        if (!empty($cart_url) && '#' !== $cart_url) {
                            $cart_url = add_query_arg($selected_attr_param, $selected_attr_value, $cart_url);
                        }
                    }
                    if (!empty($selected_attributes)) {
                        foreach ($selected_attributes as $param => $value) {
                            if (!$param || !$value) {
                                continue;
                            }
                            if (!empty($primary_url) && '#' !== $primary_url) {
                                $primary_url = add_query_arg($param, $value, $primary_url);
                            }
                            if (!empty($cart_url) && '#' !== $cart_url) {
                                $cart_url = add_query_arg($param, $value, $cart_url);
                            }
                        }
                    }
                    ?>
                    <div class="product-slide__card" data-attribute-param="<?php echo esc_attr($attribute_param); ?>">
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
                                        <?php
                                        echo function_exists('noyona_render_image')
                                            ? noyona_render_image(array(
                                                'url'    => $image,
                                                'alt'    => $image_alt,
                                                'class'  => 'ps-card__image',
                                                'sizes'  => '(max-width: 780px) 90vw, (max-width: 1280px) 50vw, 406px',
                                                'width'  => 600,
                                                'height' => 600,
                                            ))
                                            : '<img src="' . esc_url($image) . '" alt="' . esc_attr($image_alt) . '" class="ps-card__image" width="600" height="600" loading="lazy" decoding="async" sizes="(max-width: 780px) 90vw, (max-width: 1280px) 50vw, 406px" />';
                                        ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>

                            <div class="ps-card__body">
                                <?php if (!empty($swatches) && is_array($swatches)): ?>
                                    <div class="ps-card__swatches" role="radiogroup" aria-label="<?php echo esc_attr__('Select shade', 'viteseo-noyona-childtheme'); ?>">
                                        <?php foreach ($swatches as $index => $swatch): ?>
                                            <?php
                                            if (!is_array($swatch) || empty($swatch['hex'])) {
                                                continue;
                                            }
                                            $swatch_value = isset($swatch['value']) ? (string) $swatch['value'] : '';
                                            $swatch_value_key = sanitize_title($swatch_value);
                                            $swatch_label = isset($swatch['label']) ? (string) $swatch['label'] : $swatch_value;
                                            $is_selected = (0 === (int) $index);
                                            $swatch_cart_attribute_param = $attribute_param;
                                            $swatch_cart_attribute_value = $swatch_value_key;
                                            $swatch_variation_id = 0;
                                            if ($swatch_value_key && isset($variation_choice_map_safe[$swatch_value_key]) && is_array($variation_choice_map_safe[$swatch_value_key])) {
                                                $choice = $variation_choice_map_safe[$swatch_value_key];
                                                if (!empty($choice['attributeParam'])) {
                                                    $swatch_cart_attribute_param = sanitize_key((string) $choice['attributeParam']);
                                                }
                                                if (!empty($choice['attributeValue'])) {
                                                    $swatch_cart_attribute_value = sanitize_title((string) $choice['attributeValue']);
                                                }
                                                if (!empty($choice['variationId'])) {
                                                    $swatch_variation_id = absint($choice['variationId']);
                                                }
                                            } elseif ($swatch_value_key && isset($variation_map_safe[$swatch_value_key])) {
                                                $swatch_variation_id = absint($variation_map_safe[$swatch_value_key]);
                                            }
                                            ?>
                                            <button
                                                type="button"
                                                class="ps-swatch ps-swatch--option<?php echo $is_selected ? ' is-selected' : ''; ?>"
                                                style="background-color: <?php echo esc_attr($swatch['hex']); ?>;"
                                                data-swatch-value="<?php echo esc_attr($swatch_value); ?>"
                                                data-attribute-param="<?php echo esc_attr($attribute_param); ?>"
                                                data-cart-attribute-param="<?php echo esc_attr($swatch_cart_attribute_param); ?>"
                                                data-cart-attribute-value="<?php echo esc_attr($swatch_cart_attribute_value); ?>"
                                                <?php if ($swatch_variation_id > 0): ?>
                                                    data-variation-id="<?php echo esc_attr($swatch_variation_id); ?>"
                                                <?php endif; ?>
                                                role="radio"
                                                aria-label="<?php echo esc_attr($swatch_label); ?>"
                                                aria-checked="<?php echo $is_selected ? 'true' : 'false'; ?>">
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                <?php elseif (!empty($item['colors']) && is_array($item['colors'])): ?>
                                    <div class="ps-card__swatches">
                                        <?php foreach ($item['colors'] as $color): ?>
                                            <span class="ps-swatch"
                                                style="background-color: <?php echo esc_attr($color); ?>;"></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($choice_groups)): ?>
                                    <div class="ps-card__choices" aria-label="<?php echo esc_attr__('Select product options', 'viteseo-noyona-childtheme'); ?>">
                                        <?php foreach ($choice_groups as $group): ?>
                                            <?php
                                            if (empty($group['param']) || empty($group['options']) || !is_array($group['options'])) {
                                                continue;
                                            }
                                            $group_param = sanitize_key((string) $group['param']);
                                            $group_label = !empty($group['label']) ? (string) $group['label'] : __('Option', 'viteseo-noyona-childtheme');
                                            ?>
                                            <div class="ps-choice-group" role="radiogroup" aria-label="<?php echo esc_attr($group_label); ?>">
                                                <span class="ps-choice-group__label"><?php echo esc_html($group_label); ?></span>
                                                <div class="ps-choice-group__options">
                                                    <?php foreach ($group['options'] as $option_index => $option): ?>
                                                        <?php
                                                        if (!is_array($option) || empty($option['value'])) {
                                                            continue;
                                                        }
                                                        $option_value = sanitize_title((string) $option['value']);
                                                        $option_label = !empty($option['label']) ? (string) $option['label'] : $option_value;
                                                        $is_selected = 0 === (int) $option_index;
                                                        ?>
                                                        <button
                                                            type="button"
                                                            class="ps-choice ps-choice--option<?php echo $is_selected ? ' is-selected' : ''; ?>"
                                                            data-choice-value="<?php echo esc_attr($option_value); ?>"
                                                            data-cart-attribute-param="<?php echo esc_attr($group_param); ?>"
                                                            data-cart-attribute-value="<?php echo esc_attr($option_value); ?>"
                                                            role="radio"
                                                            aria-label="<?php echo esc_attr($group_label . ': ' . $option_label); ?>"
                                                            aria-checked="<?php echo $is_selected ? 'true' : 'false'; ?>">
                                                            <?php echo esc_html($option_label); ?>
                                                        </button>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($title): ?>
                                    <h5 class="ps-card__title"><?php echo esc_html($title); ?></h5>
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
                                        data-base-url="<?php echo esc_url(!empty($item['primaryUrl']) ? $item['primaryUrl'] : '#'); ?>"
                                        data-attribute-param="<?php echo esc_attr($attribute_param); ?>"
                                        <?php if (!empty($selected_attributes)): ?>
                                            data-selected-attributes="<?php echo esc_attr(wp_json_encode($selected_attributes)); ?>"
                                        <?php endif; ?>
                                        style="background-color: <?php echo esc_attr($primary_bg); ?>;">
                                        <?php echo esc_html($primary_text); ?>
                                    </a>
                                    <?php if ($cart_enabled): ?>
                                        <?php if ($product_id > 0 && ($cart_ajax || !empty($variation_map_safe))): ?>
                                            <a
                                                href="<?php echo esc_url($ajax_cart_url); ?>"
                                                class="ps-btn-cart add_to_cart_button ajax_add_to_cart"
                                                data-product_id="<?php echo esc_attr($product_id); ?>"
                                                data-product_sku="<?php echo esc_attr($product_sku); ?>"
                                                data-product-type="<?php echo esc_attr($product_type); ?>"
                                                data-quantity="1"
                                                data-cart-url="<?php echo esc_url($ajax_cart_url); ?>"
                                                data-base-cart-url="<?php echo esc_url($ajax_cart_url); ?>"
                                                data-attribute-param="<?php echo esc_attr($attribute_param); ?>"
                                                data-selected-attribute-param="<?php echo esc_attr($selected_attr_param); ?>"
                                                data-selected-attribute-value="<?php echo esc_attr($selected_attr_value); ?>"
                                                <?php if (!empty($selected_attributes)): ?>
                                                    data-selected-attributes="<?php echo esc_attr(wp_json_encode($selected_attributes)); ?>"
                                                <?php endif; ?>
                                                <?php if ($selected_variation_id > 0): ?>
                                                    data-variation_id="<?php echo esc_attr($selected_variation_id); ?>"
                                                <?php endif; ?>
                                                <?php if (!empty($variation_map_safe)): ?>
                                                    data-variation-map="<?php echo esc_attr(wp_json_encode($variation_map_safe)); ?>"
                                                <?php endif; ?>
                                                <?php if (!empty($variation_choice_map_safe)): ?>
                                                    data-variation-choice-map="<?php echo esc_attr(wp_json_encode($variation_choice_map_safe)); ?>"
                                                <?php endif; ?>
                                                <?php if (!empty($variation_combinations_safe)): ?>
                                                    data-variation-combinations="<?php echo esc_attr(wp_json_encode($variation_combinations_safe)); ?>"
                                                <?php endif; ?>
                                                style="background-color: <?php echo esc_attr($cart_bg); ?>;"
                                                aria-label="<?php echo esc_attr('Add ' . ($title ? $title : 'product') . ' to cart'); ?>"
                                                rel="nofollow">
                                                <i class="fa-solid fa-cart-shopping"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="<?php echo esc_url($cart_url); ?>" class="ps-btn-cart"
                                                data-base-url="<?php echo esc_url(!empty($item['cartUrl']) ? $item['cartUrl'] : '#'); ?>"
                                                data-attribute-param="<?php echo esc_attr($attribute_param); ?>"
                                                <?php if (!empty($selected_attributes)): ?>
                                                    data-selected-attributes="<?php echo esc_attr(wp_json_encode($selected_attributes)); ?>"
                                                <?php endif; ?>
                                                style="background-color: <?php echo esc_attr($cart_bg); ?>;"
                                                aria-label="<?php echo esc_attr('Add ' . ($title ? $title : 'product') . ' to cart'); ?>"
                                                rel="nofollow">
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