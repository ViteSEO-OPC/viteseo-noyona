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
            $image    = isset($item['image']) ? (string) $item['image'] : '';
            $image_id = isset($item['imageId']) ? absint($item['imageId']) : 0;
            if (!$image_id && '' !== $image) {
                // Recover an attachment ID from a hand-edited URL so we still get srcset.
                $image_id = (int) attachment_url_to_postid($image);
            }
            $title = isset($item['title']) ? $item['title'] : '';
            $count = isset($item['count']) ? $item['count'] : '0 Products';
            $url = isset($item['url']) ? $item['url'] : '';
            // sizes: cards are flex 1 1 280px with max 400px, in a 3-up grid on
            // desktop, full-width on mobile. ~92vw under 768, otherwise ~33vw
            // capped at 400px.
            $img_sizes = '(max-width: 768px) 92vw, (max-width: 1280px) 33vw, 400px';

            if (function_exists('noyona_render_image')) {
                $img_html = noyona_render_image(array(
                    'id'     => $image_id,
                    'url'    => $image,
                    'size'   => 'large',
                    'class'  => 'collection-card__bg',
                    'alt'    => $title,
                    'sizes'  => $img_sizes,
                    'width'  => 800,  // intrinsic-hint fallback (square-ish portrait)
                    'height' => 933,
                ));
            } else {
                // Old-style fallback (helper not yet loaded).
                $img_html = '<img class="collection-card__bg" src="' . esc_url($image) . '" alt="' . esc_attr($title) . '" width="800" height="933" loading="lazy" decoding="async" />';
            }
            ?>
            <div class="collection-card">
                <?php echo $img_html; // safe: noyona_render_image / wp_get_attachment_image both escape internally ?>
                <div class="collection-card__overlay"></div>

                <div class="collection-card__content">
                    <div class="collection-card__pill">
                        <span class="collection-card__title"><?php echo esc_html($title); ?></span>
                    </div>
                    <div class="collection-card__hover-data">
                        <span class="collection-card__count"><?php echo esc_html($count); ?></span>
                    </div>
                </div>

                <a href="<?php echo esc_url($item['url']); ?>" class="collection-card__link" aria-label="View <?php echo esc_attr($title); ?>"></a>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="collection-grid__dots" aria-label="<?php echo esc_attr__('Collection slides', 'noyona-childtheme'); ?>"></div>

    <?php if ('' !== trim($button_text)): ?>
        <div class="collection-grid__cta collection-grid__cta--<?php echo esc_attr($button_align); ?>">
            <a class="collection-grid__button" href="<?php echo esc_url($button_url ? $button_url : '#'); ?>">
                <?php echo esc_html($button_text); ?>
            </a>
        </div>
    <?php endif; ?>
</div>

<?php
// Print the view script via wp_footer instead of inline in block output:
// when this <script> tag is part of the rendered block HTML it can get
// caught by the_content filter chain (wptexturize/wpautop), which under
// certain surrounding content escapes loose `&` to `&#038;` — turning
// `&&` into `&#038;&#038;` and breaking the JS parse. Output on wp_footer
// runs outside that pipeline so the operators survive verbatim.
if ( ! function_exists( 'noyona_collection_grid_print_view_script' ) ) {
    function noyona_collection_grid_print_view_script() {
        ?>
<script>
    (function () {
        function initCollectionGrid(block) {
            var track = block.querySelector('.collection-grid__items');
            var dotsContainer = block.querySelector('.collection-grid__dots');
            if (!track) return;

            var cards = Array.prototype.slice.call(track.children || []);
            if (cards.length < 2) return;

            var mq = window.matchMedia ? window.matchMedia('(max-width: 780px)') : null;
            if (!mq) return;
            var rafId = null;

            function scrollLeftForCard(card) {
                return card.offsetLeft - (track.clientWidth / 2) + (card.clientWidth / 2);
            }

            function setActiveDot(index) {
                if (!dotsContainer) return;

                Array.prototype.slice.call(dotsContainer.children || []).forEach(function (dot, dotIndex) {
                    var isActive = dotIndex === index;
                    dot.classList.toggle('is-active', isActive);
                    dot.setAttribute('aria-current', isActive ? 'true' : 'false');
                });
            }

            function getActiveIndex() {
                var viewportCenter = track.scrollLeft + (track.clientWidth / 2);
                var bestIndex = 0;
                var bestDistance = Infinity;

                cards.forEach(function (card, index) {
                    var cardCenter = card.offsetLeft + (card.clientWidth / 2);
                    var distance = Math.abs(cardCenter - viewportCenter);

                    if (distance < bestDistance) {
                        bestDistance = distance;
                        bestIndex = index;
                    }
                });

                return bestIndex;
            }

            function updateDots() {
                if (!dotsContainer) return;

                if (!mq.matches) {
                    dotsContainer.style.display = 'none';
                    return;
                }

                dotsContainer.style.display = '';
                setActiveDot(getActiveIndex());
            }

            function buildDots() {
                if (!dotsContainer) return;

                dotsContainer.innerHTML = '';
                cards.forEach(function (card, index) {
                    var dot = document.createElement('button');
                    dot.className = 'collection-grid__dot';
                    dot.type = 'button';
                    dot.setAttribute('aria-label', 'Go to collection ' + (index + 1));
                    dot.addEventListener('click', function () {
                        // Mobile (<=780px): dots are indicators only; users navigate
                        // by swipe / native scroll and the scroll listener keeps the
                        // active dot in sync. Dots only render at <=780px, so this
                        // fully disables click navigation where they are shown.
                        if (mq && mq.matches) return;
                        track.scrollTo({ left: Math.max(0, scrollLeftForCard(card)), behavior: 'smooth' });
                        setActiveDot(index);
                    });
                    dotsContainer.appendChild(dot);
                });

                updateDots();
            }

            function requestDotUpdate() {
                if (rafId) return;

                rafId = requestAnimationFrame(function () {
                    rafId = null;
                    updateDots();
                });
            }

            function centerToMiddle(force) {
                if (!mq.matches) return;
                if (!force && track.dataset.centeredForCarousel === '1') return;

                // Prefer the "left-middle" card for even counts (feels more natural)
                var idx = Math.floor((cards.length - 1) / 2);
                var target = cards[idx];
                if (!target) return;

                requestAnimationFrame(function () {
                    requestAnimationFrame(function () {
                        track.scrollTo({ left: Math.max(0, scrollLeftForCard(target)), behavior: 'auto' });
                        track.dataset.centeredForCarousel = '1';
                        updateDots();
                    });
                });
            }

            buildDots();

            // If we load already in carousel mode, start centered
            centerToMiddle(false);
            updateDots();
            track.addEventListener('scroll', requestDotUpdate, { passive: true });

            // When entering carousel mode, center once
            if (mq.addEventListener) {
                mq.addEventListener('change', function (e) {
                    track.dataset.centeredForCarousel = '0';
                    if (e.matches) {
                        centerToMiddle(true);
                    }
                    updateDots();
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
        <?php
    }
}
// add_action with a string callback is keyed by the callback name, so this
// is a no-op if the block renders more than once on the same page.
add_action( 'wp_footer', 'noyona_collection_grid_print_view_script' );