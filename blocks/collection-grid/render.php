<?php
/**
 * Collection Grid Block Template.
 */

$defaults = array(
    'heading' => '',
    'description' => '',
    'headingAlign' => 'center',
    'descriptionAlign' => 'center',
    'buttonText' => '',
    'buttonUrl' => '',
    'buttonAlign' => 'center',
    'items' => array(),
);

$atts = wp_parse_args($attributes, $defaults);
$items = $atts['items'];
$allowed_align = array('left', 'center', 'right');
$heading_align = in_array($atts['headingAlign'], $allowed_align, true) ? $atts['headingAlign'] : 'center';
$description_align = in_array($atts['descriptionAlign'], $allowed_align, true) ? $atts['descriptionAlign'] : 'center';
$button_align = in_array($atts['buttonAlign'], $allowed_align, true) ? $atts['buttonAlign'] : 'center';
$button_text = isset($atts['buttonText']) ? (string) $atts['buttonText'] : '';
$button_url = isset($atts['buttonUrl']) ? (string) $atts['buttonUrl'] : '';

if (empty($items)) {
    if (is_admin()) {
        echo '<div class="collection-grid-placeholder">Add collections via the sidebar.</div>';
    }
    return;
}
?>
<div class="wp-block-noyona-collection-grid collection-grid alignwide">

    <div class="collection-grid__header collection-grid__header--heading-<?php echo esc_attr($heading_align); ?> collection-grid__header--desc-<?php echo esc_attr($description_align); ?>">
        <?php if ($atts['heading']): ?>
            <?php
            $heading = (string) $atts['heading'];
            $heading_html = esc_html($heading);

            $accent_words = array('Collections', 'Products');
            foreach ($accent_words as $accent_word) {
                $pos = stripos($heading, $accent_word);
                if ($pos !== false) {
                    $before = substr($heading, 0, $pos);
                    $match = substr($heading, $pos, strlen($accent_word));
                    $after = substr($heading, $pos + strlen($accent_word));
                    $heading_html = esc_html($before) . '<span class="collection-grid__heading-accent">' . esc_html($match) . '</span>' . esc_html($after);
                    break;
                }
            }
            ?>
            <h2 class="collection-grid__heading">
                <?php echo $heading_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
        <?php endif; ?>
        <?php if ($atts['description']): ?>
            <p class="collection-grid__description"><?php echo esc_html($atts['description']); ?></p>
        <?php endif; ?>
    </div>

    <div class="collection-grid__items">
        <?php foreach ($items as $item): ?>
            <?php
            $image = isset($item['image']) ? $item['image'] : '';
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
            $title = isset($item['title']) ? $item['title'] : '';
            $count = isset($item['count']) ? $item['count'] : '0 Products';
            ?>
            <div class="collection-card">
                <div class="collection-card__bg" style="background-image: url('<?php echo esc_url($image); ?>');"></div>
                <div class="collection-card__overlay"></div>

                <div class="collection-card__content">
                    <div class="collection-card__pill">
                        <span class="collection-card__title"><?php echo esc_html($title); ?></span>
                    </div>
                    <div class="collection-card__hover-data">
                        <span class="collection-card__count"><?php echo esc_html($count); ?></span>
                    </div>
                </div>

                <a href="coming-soon" class="collection-card__link" aria-label="View <?php echo esc_attr($title); ?>"></a>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ('' !== trim($button_text)): ?>
        <div class="collection-grid__cta collection-grid__cta--<?php echo esc_attr($button_align); ?>">
            <a class="collection-grid__button" href="<?php echo esc_url($button_url ? $button_url : '#'); ?>">
                <?php echo esc_html($button_text); ?>
            </a>
        </div>
    <?php endif; ?>
</div>

<script>
    (function () {
        function initCollectionGrid(block) {
            var track = block.querySelector('.collection-grid__items');
            if (!track) return;

            var cards = Array.prototype.slice.call(track.children || []);
            if (cards.length < 2) return;

            var mq = window.matchMedia ? window.matchMedia('(max-width: 768px)') : null;
            if (!mq) return;

            function centerToMiddle(force) {
                if (!mq.matches) return;
                if (!force && track.dataset.centeredForCarousel === '1') return;

                // Prefer the "left-middle" card for even counts (feels more natural)
                var idx = Math.floor((cards.length - 1) / 2);
                var target = cards[idx];
                if (!target) return;

                requestAnimationFrame(function () {
                    requestAnimationFrame(function () {
                        var left = target.offsetLeft - (track.clientWidth / 2) + (target.clientWidth / 2);
                        track.scrollTo({ left: Math.max(0, left), behavior: 'auto' });
                        track.dataset.centeredForCarousel = '1';
                    });
                });
            }

            // If we load already in carousel mode, start centered
            centerToMiddle(false);

            // When entering carousel mode, center once
            if (mq.addEventListener) {
                mq.addEventListener('change', function (e) {
                    track.dataset.centeredForCarousel = '0';
                    if (e.matches) {
                        centerToMiddle(true);
                    }
                });
            }
        }

        function boot() {
            document.querySelectorAll('.wp-block-noyona-collection-grid').forEach(initCollectionGrid);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', boot);
        } else {
            boot();
        }
    })();
</script>