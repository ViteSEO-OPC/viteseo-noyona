<?php
/**
 * Awards Strip block template.
 *
 * Displays a centered grid of award / badge logos.
 * - Desktop: single row.
 * - Tablet: 3 per row (e.g. 3 / 3 / 1), trailing odd item centered.
 * - Mobile: 2 per row (e.g. 2 / 2 / 2 / 1), trailing odd item centered.
 */

$image_base = trailingslashit(get_stylesheet_directory_uri()) . 'assets/images/';

// Default award logos, used when no `items` are supplied via block attributes.
// Note: block.json declares `items` with a default of [], which WordPress injects
// into $attributes. wp_parse_args() won't restore these defaults for an existing
// (empty) key, so we explicitly fall back to them when items is empty.
$default_items = array(
    array('icon' => $image_base . 'award-1.webp', 'title' => ''),
    array('icon' => $image_base . 'award-2.webp', 'title' => ''),
    array('icon' => $image_base . 'award-3.webp', 'title' => ''),
    array('icon' => $image_base . 'award-4.webp', 'title' => ''),
    array('icon' => $image_base . 'award-5.webp', 'title' => ''),
    array('icon' => $image_base . 'award-6.webp', 'title' => ''),
    array('icon' => $image_base . 'award-7.webp', 'title' => ''),
);

$defaults = array(
    'backgroundColor' => '',
    'items' => $default_items,
);

$atts = wp_parse_args($attributes, $defaults);
$items = (isset($atts['items']) && is_array($atts['items']) && !empty($atts['items']))
    ? $atts['items']
    : $default_items;

// Only emit the inline override when a valid color is supplied; otherwise the
// stylesheet falls back to the shared pink-soft background.
$bg = sanitize_hex_color($atts['backgroundColor']);
$style_attr = $bg ? '--awards-strip-bg: ' . esc_attr($bg) . ';' : '';

if (empty($items)) {
    if (is_admin()) {
        echo '<div class="awards-strip-placeholder">Add awards via the block attributes.</div>';
    }
    return;
}
?>
<section class="wp-block-noyona-awards-strip awards-strip alignfull"<?php echo '' !== $style_attr ? ' style="' . esc_attr($style_attr) . '"' : ''; ?>>
    <div class="awards-strip__inner">
        <h2 class="awards-strip__heading">AWARDS</h2>
        <div class="awards-strip__grid">
        <?php foreach ($items as $index => $item) : ?>
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
            $alt = '' !== $title ? $title : sprintf('Award %d', (int) $index + 1);
            if (empty($icon)) {
                continue;
            }
            ?>
            <div class="awards-strip__item awards-strip__item--<?php echo (int) $index + 1; ?>">
                <img src="<?php echo esc_url($icon); ?>" alt="<?php echo esc_attr($alt); ?>" width="140" height="140" loading="lazy" decoding="async" />
                <?php if (!empty($title)) : ?>
                    <span class="awards-strip__title"><?php echo esc_html($title); ?></span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
</section>
