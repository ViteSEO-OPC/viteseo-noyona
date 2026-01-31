<?php
/**
 * FAQ List Block Template.
 */

$defaults = array(
    'heading'            => 'How can we <span class="faq-list__accent">help you?</span>',
    'searchPlaceholder'  => 'Search here',
    'searchButtonLabel'  => 'Search',
    'title'              => 'Frequently Asked Questions',
    'subtitle'           => 'Quick answers to your questions about shipping, returns, and how to get the most out of our products',
    'categories'         => array(),
    'items'              => array(),
    'disclaimers'        => array(),
    'emptyMessage'       => 'No questions found.',
);

$atts = wp_parse_args( $attributes, $defaults );
$items = isset( $atts['items'] ) && is_array( $atts['items'] ) ? $atts['items'] : array();
$raw_categories = isset( $atts['categories'] ) && is_array( $atts['categories'] ) ? $atts['categories'] : array();
$disclaimers = isset( $atts['disclaimers'] ) && is_array( $atts['disclaimers'] ) ? $atts['disclaimers'] : array();
$block_id = 'faq-list-' . wp_rand( 1000, 9999 );

$category_list = array();
$category_id_map = array();
$category_label_map = array();

$add_category = function ( $label, $id = '' ) use ( &$category_list, &$category_id_map, &$category_label_map ) {
    $label = trim( (string) $label );
    if ( '' === $label ) {
        return;
    }

    $base_id = $id ? sanitize_title( $id ) : sanitize_title( $label );
    if ( '' === $base_id ) {
        $base_id = 'category-' . ( count( $category_list ) + 1 );
    }

    $unique_id = $base_id;
    $suffix = 2;
    while ( isset( $category_id_map[ $unique_id ] ) ) {
        $unique_id = $base_id . '-' . $suffix;
        $suffix++;
    }

    $category_id_map[ $unique_id ] = $label;
    $category_label_map[ sanitize_title( $label ) ] = $unique_id;
    $category_list[] = array(
        'id'    => $unique_id,
        'label' => $label,
    );
};

if ( ! empty( $raw_categories ) ) {
    foreach ( $raw_categories as $category ) {
        if ( is_string( $category ) ) {
            $add_category( $category );
            continue;
        }

        if ( is_array( $category ) ) {
            $label = isset( $category['label'] ) ? $category['label'] : ( isset( $category['title'] ) ? $category['title'] : '' );
            $id = isset( $category['id'] ) ? $category['id'] : '';
            $add_category( $label, $id );
        }
    }
}

if ( empty( $category_list ) ) {
    foreach ( $items as $item ) {
        if ( ! is_array( $item ) ) {
            continue;
        }
        $item_category = isset( $item['category'] ) ? $item['category'] : '';
        if ( '' !== trim( $item_category ) ) {
            $add_category( $item_category );
        }
    }
}

if ( empty( $category_list ) ) {
    $add_category( 'General' );
}
?>
<section class="wp-block-noyona-faq-list faq-list alignfull">
    <div class="faq-list__inner">
        <div class="faq-list__hero">
            <?php if ( ! empty( $atts['heading'] ) ) : ?>
                <h1 class="faq-list__heading"><?php echo wp_kses_post( $atts['heading'] ); ?></h1>
            <?php endif; ?>

            <div class="faq-list__search" role="search">
                <label class="screen-reader-text" for="<?php echo esc_attr( $block_id ); ?>">Search FAQs</label>
                <div class="faq-list__search-field">
                    <input
                        id="<?php echo esc_attr( $block_id ); ?>"
                        class="faq-list__search-input"
                        type="search"
                        placeholder="<?php echo esc_attr( $atts['searchPlaceholder'] ); ?>"
                        data-faq-search
                    />
                    <?php if ( ! empty( $atts['searchButtonLabel'] ) ) : ?>
                        <button class="faq-list__search-button" type="button" data-faq-search-button>
                            <?php echo esc_html( $atts['searchButtonLabel'] ); ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="faq-list__grid">
            <aside class="faq-list__sidebar">
                <?php if ( ! empty( $category_list ) ) : ?>
                    <div class="faq-list__categories" role="tablist">
                        <?php foreach ( $category_list as $index => $category ) : ?>
                            <button
                                class="faq-list__category<?php echo 0 === $index ? ' is-active' : ''; ?>"
                                type="button"
                                data-faq-category-button="<?php echo esc_attr( $category['id'] ); ?>"
                                aria-pressed="<?php echo 0 === $index ? 'true' : 'false'; ?>"
                            >
                                <span class="faq-list__category-label"><?php echo esc_html( $category['label'] ); ?></span>
                                <span class="faq-list__category-icon" aria-hidden="true">></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ( ! empty( $disclaimers ) ) : ?>
                    <div class="faq-list__disclaimers">
                        <?php foreach ( $disclaimers as $disclaimer ) : ?>
                            <?php
                            $title = isset( $disclaimer['title'] ) ? $disclaimer['title'] : '';
                            $body = isset( $disclaimer['body'] ) ? $disclaimer['body'] : '';
                            if ( '' === trim( $title ) && '' === trim( $body ) ) {
                                continue;
                            }
                            ?>
                            <div class="faq-list__disclaimer">
                                <?php if ( '' !== trim( $title ) ) : ?>
                                    <h3 class="faq-list__disclaimer-title"><?php echo esc_html( $title ); ?></h3>
                                <?php endif; ?>
                                <?php if ( '' !== trim( $body ) ) : ?>
                                    <div class="faq-list__disclaimer-body">
                                        <?php echo wp_kses_post( wpautop( $body ) ); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </aside>

            <div class="faq-list__content">
                <?php if ( ! empty( $atts['title'] ) ) : ?>
                    <h2 class="faq-list__title"><?php echo esc_html( $atts['title'] ); ?></h2>
                <?php endif; ?>

                <?php if ( ! empty( $atts['subtitle'] ) ) : ?>
                    <p class="faq-list__subtitle"><?php echo esc_html( $atts['subtitle'] ); ?></p>
                <?php endif; ?>

                <div class="faq-list__items">
                    <?php if ( empty( $items ) ) : ?>
                        <?php if ( is_admin() ) : ?>
                            <p class="faq-list__empty">Add FAQ items in the sidebar.</p>
                        <?php endif; ?>
                    <?php else : ?>
                        <?php foreach ( $items as $item ) : ?>
                            <?php
                            $question = isset( $item['question'] ) ? $item['question'] : '';
                            $answer = isset( $item['answer'] ) ? $item['answer'] : '';
                            $answer_text = trim( $answer );
                            if ( '' === $answer_text ) {
                                $answer_text = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.';
                            }
                            $raw_category = isset( $item['category'] ) ? $item['category'] : ( isset( $item['categoryId'] ) ? $item['categoryId'] : '' );
                            $category_key = '';
                            if ( '' !== trim( $raw_category ) ) {
                                $normalized = sanitize_title( $raw_category );
                                if ( isset( $category_id_map[ $normalized ] ) ) {
                                    $category_key = $normalized;
                                } elseif ( isset( $category_label_map[ $normalized ] ) ) {
                                    $category_key = $category_label_map[ $normalized ];
                                } else {
                                    $category_key = $normalized;
                                }
                            }
                            if ( '' === $category_key && ! empty( $category_list ) ) {
                                $category_key = $category_list[0]['id'];
                            }

                            $search_text = strtolower( wp_strip_all_tags( $question . ' ' . $answer ) );
                            ?>
                            <details
                                class="faq-list__item"
                                data-faq-item
                                data-faq-category="<?php echo esc_attr( $category_key ); ?>"
                                data-faq-search-text="<?php echo esc_attr( $search_text ); ?>"
                            >
                                <summary class="faq-list__summary">
                                    <span class="faq-list__question"><?php echo esc_html( $question ); ?></span>
                                    <span class="faq-list__icon" aria-hidden="true"></span>
                                </summary>
                                <div class="faq-list__answer">
                                    <p><?php echo esc_html( $answer_text ); ?></p>
                                </div>
                            </details>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <p class="faq-list__empty" hidden><?php echo esc_html( $atts['emptyMessage'] ); ?></p>
            </div>
        </div>
    </div>
</section>
