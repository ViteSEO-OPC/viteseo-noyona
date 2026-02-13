<?php
/**
 * FAQ List Block Template.
 */

if (!defined('ABSPATH')) {
    exit;
}

$align_class = isset($attributes['align']) ? 'align' . $attributes['align'] : '';
$heading = isset($attributes['heading']) ? trim((string) $attributes['heading']) : '';
$searchPlaceholder = isset($attributes['searchPlaceholder']) ? trim((string) $attributes['searchPlaceholder']) : '';
$searchButtonLabel = isset($attributes['searchButtonLabel']) ? trim((string) $attributes['searchButtonLabel']) : '';
$title = isset($attributes['title']) ? trim((string) $attributes['title']) : '';
$subtitle = isset($attributes['subtitle']) ? trim((string) $attributes['subtitle']) : '';
$categories = isset($attributes['categories']) && is_array($attributes['categories']) ? $attributes['categories'] : array();
$items = isset($attributes['items']) && is_array($attributes['items']) ? $attributes['items'] : array();
$disclaimers = isset($attributes['disclaimers']) && is_array($attributes['disclaimers']) ? $attributes['disclaimers'] : array();
$emptyMessage = isset($attributes['emptyMessage']) ? trim((string) $attributes['emptyMessage']) : '';
$showCommunityCta = isset($attributes['showCommunityCta']) ? (bool) $attributes['showCommunityCta'] : false;
$communityHeading = isset($attributes['communityHeading']) ? trim((string) $attributes['communityHeading']) : '';
$communityText = isset($attributes['communityText']) ? trim((string) $attributes['communityText']) : '';
$communityButtonText = isset($attributes['communityButtonText']) ? trim((string) $attributes['communityButtonText']) : '';
$communityButtonUrl = isset($attributes['communityButtonUrl']) ? trim((string) $attributes['communityButtonUrl']) : '';
$section_classes = trim('wp-block-noyona-faq-list faq-list ' . $align_class);

$normalize_category = function ($value) {
    if (!is_string($value)) {
        return '';
    }

    $value = trim($value);
    if ('' === $value) {
        return '';
    }

    return sanitize_title($value);
};

$category_map = array();
if (!empty($categories)) {
    foreach ($categories as $category) {
        $label = isset($category['label']) ? (string) $category['label'] : '';
        $id = isset($category['id']) ? (string) $category['id'] : '';
        $key_source = '' !== trim($id) ? $id : $label;
        $normalized_key = $normalize_category($key_source);
        $label_key = $normalize_category($label);

        if ('' !== $label_key && '' !== $normalized_key) {
            $category_map[$label_key] = $normalized_key;
        }
    }
}

?>
<section class="<?php echo esc_attr($section_classes); ?>">
    <div class="faq-list__inner">
        <div class="faq-list__hero">
            <?php if (!empty($heading)): ?>
                <h1 class="faq-list__heading">
                    <?php echo wp_kses_post($heading); ?>
                </h1>
            <?php endif; ?>

            <div class="faq-list__search" role="search">
                <label class="screen-reader-text" for="faq-list-search">Search FAQs</label>
                <div class="faq-list__search-field">
                    <input id="faq-list-search" class="faq-list__search-input" type="search"
                        placeholder="<?php echo esc_attr($searchPlaceholder); ?>" data-faq-search />
                    <?php if (!empty($searchButtonLabel)): ?>
                        <button class="faq-list__search-button" type="button" data-faq-search-button>
                            <?php echo esc_html($searchButtonLabel); ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (!empty($title)): ?>
                <h2 class="faq-list__title">
                    <?php echo esc_html($title); ?>
                </h2>
            <?php endif; ?>

            <?php if (!empty($subtitle)): ?>
                <p class="faq-list__subtitle">
                    <?php echo esc_html($subtitle); ?>
                </p>
            <?php endif; ?>
        </div>

        <div class="faq-list__grid">
            <aside class="faq-list__sidebar">
                <?php if (!empty($categories)): ?>
                    <div class="faq-list__categories" role="tablist">
                        <?php foreach ($categories as $index => $category): ?>
                            <?php
                            $category_label = isset($category['label']) ? (string) $category['label'] : '';
                            $category_id = isset($category['id']) ? (string) $category['id'] : '';
                            $category_key_source = '' !== trim($category_id) ? $category_id : $category_label;
                            $category_key = $normalize_category($category_key_source);
                            ?>
                            <button class="faq-list__category<?php echo 0 === $index ? ' is-active' : ''; ?>" type="button"
                                data-faq-category-button="<?php echo esc_attr($category_key); ?>"
                                aria-pressed="<?php echo 0 === $index ? 'true' : 'false'; ?>"> 
                                <span class="faq-list__category-label">
                                    <?php echo esc_html($category_label); ?>
                                </span>
                                <span class="faq-list__category-icon" aria-hidden="true"></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($showCommunityCta)): ?>
                    <div class="faq-community faq-community--sidebar">
                        <div class="faq-community__inner">
                            <div class="faq-community__icon" aria-hidden="true">
                                <i class="fa-regular fa-envelope"></i>
                            </div>

                            <?php if (!empty($communityHeading)): ?>
                                <h2 class="faq-community__title">
                                    <?php echo wp_kses_post($communityHeading); ?>
                                </h2>
                            <?php endif; ?>

                            <?php if (!empty($communityText)): ?>
                                <p class="faq-community__text">
                                    <?php echo esc_html($communityText); ?>
                                </p>
                            <?php endif; ?>

                            <div class="faq-community__form" aria-label="Community signup">
                                <input class="faq-community__input" type="email" placeholder="Your email address" />
                                <?php if (!empty($communityButtonText) && !empty($communityButtonUrl)): ?>
                                    <a class="faq-community__submit" href="<?php echo esc_url($communityButtonUrl); ?>">
                                        <?php echo esc_html($communityButtonText); ?>
                                    </a>
                                <?php else: ?>
                                    <button class="faq-community__submit" type="button">Subscribe</button>
                                <?php endif; ?>
                            </div>

                            <p class="faq-community__fineprint">No spam, unsubscribe anytime.</p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($disclaimers)): ?>
                <div class="faq-list__disclaimers faq-list__disclaimers-panel">
                    <?php foreach ($disclaimers as $disclaimer): ?>
                        <?php
                        $title = isset($disclaimer['title']) ? $disclaimer['title'] : '';
                        $body = isset($disclaimer['body']) ? $disclaimer['body'] : '';
                        if ('' === trim($title) && '' === trim($body)) {
                            continue;
                        }
                        ?>
                        <div class="faq-list__disclaimer">
                            <?php if ('' !== trim($disclaimer['title'] ?? '')): ?>
                                <h3 class="faq-list__disclaimer-title">
                                    <?php echo esc_html($disclaimer['title'] ?? ''); ?>
                                </h3>
                            <?php endif; ?>
                            <?php if ('' !== trim($disclaimer['body'] ?? '')): ?>
                                <div class="faq-list__disclaimer-body">
                                    <?php echo wp_kses_post(wpautop($disclaimer['body'] ?? '')); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            </aside>

            <div class="faq-list__content">
                <?php $accordion_id = uniqid('faq-list-accordion-'); ?>
                <div class="faq-list__items">
                    <?php if (empty($items)): ?>
                        <?php if (is_admin()): ?>
                            <p class="faq-list__empty">Add FAQ items in the sidebar.</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php foreach ($items as $item): ?>
                            <?php
                            $question = isset($item['question']) ? (string) $item['question'] : '';
                            $answer = isset($item['answer']) ? (string) $item['answer'] : '';
                            $category = isset($item['category']) ? (string) $item['category'] : '';
                            $category_key = $normalize_category($category);
                            if ('' !== $category_key && isset($category_map[$category_key])) {
                                $category_key = $category_map[$category_key];
                            }
                            $search_text = strtolower(trim(wp_strip_all_tags($question . ' ' . $answer . ' ' . $category)));
                            ?>
                            <details class="faq-list__item" name="<?php echo esc_attr($accordion_id); ?>" data-faq-item
                                data-faq-category="<?php echo esc_attr($category_key); ?>"
                                data-faq-search-text="<?php echo esc_attr($search_text); ?>">
                                <summary class="faq-list__summary">
                                    <span class="faq-list__question">
                                        <?php echo esc_html($question); ?>
                                    </span>
                                    <span class="faq-list__icon" aria-hidden="true"></span>
                                </summary>
                                <div class="faq-list__answer">
                                    <p>
                                        <?php echo esc_html($answer); ?>
                                    </p>
                                </div>
                            </details>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <p class="faq-list__empty" hidden>
                    <?php echo esc_html($emptyMessage); ?>
                </p>
            </div>

           
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.wp-block-noyona-faq-list').forEach(function (block) {
                var wrap = block.querySelector('.faq-list__items');
                if (!wrap) return;

                wrap.addEventListener('toggle', function (e) {
                    var t = e.target;
                    if (!t || t.tagName !== 'DETAILS') return;
                    if (!t.open) return;

                    wrap.querySelectorAll('details.faq-list__item[open]').forEach(function (d) {
                        if (d !== t) d.open = false;
                    });
                }, true);
            });
        });
    </script>

</section>