<?php
/**
 * Values Strip block template.
 */

$defaults = array(
    'backgroundColor' => '#EFB5BE',
    'items' => array(
        array(
            'icon' => 'https://noyonacosmetics.com/wp-content/uploads/2026/02/cruelty-free-300x300.webp',
            'title' => 'Cruelty-free',
            'description' => 'Never tested on animals',
        ),
        array(
            'icon' => 'https://noyonacosmetics.com/wp-content/uploads/2026/02/sustainability-300x300.webp',
            'title' => 'Sustainably-sourced',
            'description' => 'Responsibly sourced ingredients',
        ),
        array(
            'icon' => 'https://noyonacosmetics.com/wp-content/uploads/2026/02/vegan-300x290.webp',
            'title' => 'Vegan',
            'description' => 'No animal-derived ingredients',
        ),
        array(
            'icon' => 'https://noyonacosmetics.com/wp-content/uploads/2026/02/paraben_free-300x300.webp',
            'title' => 'Paraben-free',
            'description' => 'Formulated without parabens',
        ),
    ),
);

$atts = wp_parse_args($attributes, $defaults);
$items = is_array($atts['items']) ? $atts['items'] : array();
$bg = sanitize_hex_color($atts['backgroundColor']);
if (!$bg) {
    $bg = '#EFB5BE';
}

if (empty($items)) {
    if (is_admin()) {
        echo '<div class="values-strip-placeholder">Add values via the block attributes.</div>';
    }
    return;
}
?>
<section class="wp-block-noyona-values-strip values-strip alignfull" style="--values-strip-bg: <?php echo esc_attr($bg); ?>;">
    <div class="values-strip__inner">
        <?php foreach ($items as $item) : ?>
            <?php
            $icon = isset($item['icon']) ? (string) $item['icon'] : '';
            $icon_id = isset($item['iconId']) ? absint($item['iconId']) : 0;
            if ($icon_id) {
                $resolved_icon = wp_get_attachment_image_url($icon_id, 'medium');
                if ($resolved_icon) {
                    $icon = (string) $resolved_icon;
                }
            } elseif (!empty($icon)) {
                $resolved_id = attachment_url_to_postid($icon);
                if ($resolved_id) {
                    $resolved_icon = wp_get_attachment_image_url((int) $resolved_id, 'medium');
                    if ($resolved_icon) {
                        $icon = (string) $resolved_icon;
                    }
                }
            }
            $title = isset($item['title']) ? (string) $item['title'] : '';
            $desc = isset($item['description']) ? (string) $item['description'] : '';
            ?>
            <div class="values-strip__item">
                <?php if (!empty($icon)) : ?>
                    <img src="<?php echo esc_url($icon); ?>" alt="<?php echo esc_attr($title ?: 'Value icon'); ?>" width="80" height="80" loading="lazy" decoding="async" />
                <?php endif; ?>
                <?php if (!empty($title)) : ?>
                    <span class="values-strip__title"><?php echo esc_html($title); ?></span>
                <?php endif; ?>
                <?php if (!empty($desc)) : ?>
                    <span class="values-strip__desc"><?php echo esc_html($desc); ?></span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>
