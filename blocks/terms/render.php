<?php
/**
 * Terms Block Template.
 */

$defaults = array(
    'heading'      => 'Terms & Policy',
    'subheading'   => 'At Noyona Cosmetics and Skin Care Products OPC, we prioritize transparency and trust by providing clear guidelines on how we handle your orders, protect your rights, and ensure a seamless shopping experience.',
    'tocTitle'     => 'Terms of Service',
    'tocItems'     => array(),
    'contentTitle' => 'Terms of Service',
    'intro'        => array(),
    'sections'     => array(),
    'policies'     => array(),
    'tabs'         => array(),
);

$atts = wp_parse_args( $attributes, $defaults );
$toc_items = is_array( $atts['tocItems'] ) ? $atts['tocItems'] : array();
$sections = is_array( $atts['sections'] ) ? $atts['sections'] : array();
$intro = is_array( $atts['intro'] ) ? $atts['intro'] : array();
$policies = is_array( $atts['policies'] ) ? $atts['policies'] : array();
$tabs = is_array( $atts['tabs'] ) ? $atts['tabs'] : array();

$has_tabs = ! empty( $tabs );
$tab_panels = array();

if ( $has_tabs ) {
    $tab_entries = array();
    $base_label = $atts['tocTitle'] ? $atts['tocTitle'] : ( $atts['contentTitle'] ? $atts['contentTitle'] : 'Terms' );
    $base_id = sanitize_title( $base_label );
    if ( '' === $base_id ) {
        $base_id = 'terms';
    }

    $tab_entries[] = array(
        'id'           => $base_id,
        'label'        => $base_label,
        'tocTitle'     => $atts['tocTitle'],
        'contentTitle' => $atts['contentTitle'],
        'tocItems'     => $toc_items,
        'intro'        => $intro,
        'sections'     => $sections,
    );

    foreach ( $tabs as $index => $tab ) {
        if ( ! is_array( $tab ) ) {
            continue;
        }

        $tab_label = isset( $tab['label'] ) ? $tab['label'] : '';
        $fallback_label = $tab_label;
        if ( '' === $fallback_label && isset( $tab['tocTitle'] ) ) {
            $fallback_label = $tab['tocTitle'];
        }
        if ( '' === $fallback_label && isset( $tab['contentTitle'] ) ) {
            $fallback_label = $tab['contentTitle'];
        }
        if ( '' === $fallback_label ) {
            $fallback_label = 'Tab ' . ( $index + 1 );
        }

        $tab_id = isset( $tab['id'] ) ? sanitize_title( $tab['id'] ) : '';
        if ( '' === $tab_id ) {
            $tab_id = sanitize_title( $fallback_label );
        }
        if ( '' === $tab_id ) {
            $tab_id = 'tab-' . ( $index + 1 );
        }

        $tab_entries[] = array(
            'id'           => $tab_id,
            'label'        => $fallback_label,
            'tocTitle'     => isset( $tab['tocTitle'] ) ? $tab['tocTitle'] : '',
            'contentTitle' => isset( $tab['contentTitle'] ) ? $tab['contentTitle'] : '',
            'tocItems'     => isset( $tab['tocItems'] ) ? $tab['tocItems'] : array(),
            'intro'        => isset( $tab['intro'] ) ? $tab['intro'] : array(),
            'sections'     => isset( $tab['sections'] ) ? $tab['sections'] : array(),
        );
    }

    $prefix_ids = count( $tab_entries ) > 1;
    $used_ids = array();

    foreach ( $tab_entries as $tab_entry ) {
        $tab_id = $tab_entry['id'];
        $tab_label = $tab_entry['label'];
        $tab_toc_items = is_array( $tab_entry['tocItems'] ) ? $tab_entry['tocItems'] : array();
        $tab_sections = is_array( $tab_entry['sections'] ) ? $tab_entry['sections'] : array();
        $tab_intro = is_array( $tab_entry['intro'] ) ? $tab_entry['intro'] : array();

        if ( $prefix_ids && ! empty( $tab_toc_items ) ) {
            foreach ( $tab_toc_items as $item_index => $item ) {
                $item_id = isset( $item['id'] ) ? $item['id'] : '';
                if ( '' === $item_id ) {
                    continue;
                }
                $base_id = sanitize_title( $item_id );
                if ( '' === $base_id ) {
                    continue;
                }
                $tab_toc_items[ $item_index ]['id'] = $tab_id . '-' . $base_id;
            }
        }

        $computed_toc = array();

        if ( ! empty( $tab_sections ) ) {
            foreach ( $tab_sections as $section_index => $section ) {
                $section_label = isset( $section['title'] ) ? $section['title'] : '';
                $raw_id = isset( $section['id'] ) ? $section['id'] : '';
                $base_id = $raw_id ? sanitize_title( $raw_id ) : sanitize_title( $section_label );
                if ( '' === $base_id ) {
                    $base_id = 'section-' . ( $section_index + 1 );
                }
                if ( $prefix_ids ) {
                    $base_id = $tab_id . '-' . $base_id;
                }

                $resolved_id = $base_id;
                $suffix = 2;
                while ( in_array( $resolved_id, $used_ids, true ) ) {
                    $resolved_id = $base_id . '-' . $suffix;
                    $suffix++;
                }

                $used_ids[] = $resolved_id;
                $tab_sections[ $section_index ]['resolvedId'] = $resolved_id;

                $toc_label = $section_label ? $section_label : 'Section ' . ( $section_index + 1 );
                $computed_toc[] = array(
                    'id'    => $resolved_id,
                    'label' => $toc_label,
                );
            }
        }

        if ( empty( $tab_toc_items ) && ! empty( $computed_toc ) ) {
            $tab_toc_items = $computed_toc;
        }

        $tab_panels[] = array(
            'id'           => $tab_id,
            'label'        => $tab_label,
            'tocTitle'     => $tab_entry['tocTitle'],
            'contentTitle' => $tab_entry['contentTitle'],
            'tocItems'     => $tab_toc_items,
            'intro'        => $tab_intro,
            'sections'     => $tab_sections,
        );
    }
} else {
    $used_ids = array();
    $computed_toc = array();

    if ( ! empty( $sections ) ) {
        foreach ( $sections as $index => $section ) {
            $label = isset( $section['title'] ) ? $section['title'] : '';
            $raw_id = isset( $section['id'] ) ? $section['id'] : '';
            $base_id = $raw_id ? sanitize_title( $raw_id ) : sanitize_title( $label );
            if ( '' === $base_id ) {
                $base_id = 'section-' . ( $index + 1 );
            }

            $id = $base_id;
            $suffix = 2;
            while ( in_array( $id, $used_ids, true ) ) {
                $id = $base_id . '-' . $suffix;
                $suffix++;
            }

            $used_ids[] = $id;
            $sections[ $index ]['resolvedId'] = $id;

            $toc_label = $label ? $label : 'Section ' . ( $index + 1 );
            $computed_toc[] = array(
                'id'    => $id,
                'label' => $toc_label,
            );
        }
    }

    if ( empty( $toc_items ) && ! empty( $computed_toc ) ) {
        $toc_items = $computed_toc;
    }
}
?>
<section class="wp-block-noyona-terms terms alignfull">
    <div class="terms__hero">
        <div class="terms__hero-inner">
            <?php if ( ! empty( $atts['heading'] ) ) : ?>
                <h1 class="terms__hero-title"><?php echo esc_html( $atts['heading'] ); ?></h1>
            <?php endif; ?>
            <?php if ( ! empty( $atts['subheading'] ) ) : ?>
                <p class="terms__hero-subtitle"><?php echo esc_html( $atts['subheading'] ); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="terms__body">
        <div class="terms__layout">
            <aside class="terms__toc" aria-label="Table of contents">
                <?php if ( $has_tabs ) : ?>
                    <?php foreach ( $tab_panels as $index => $tab ) : ?>
                        <button
                            class="terms__toc-button terms__tab-button<?php echo 0 === $index ? ' is-active' : ''; ?>"
                            type="button"
                            data-tab="<?php echo esc_attr( $tab['id'] ); ?>"
                            aria-selected="<?php echo 0 === $index ? 'true' : 'false'; ?>"
                            aria-expanded="<?php echo 0 === $index ? 'true' : 'false'; ?>"
                        >
                            <?php echo esc_html( $tab['label'] ); ?>
                            <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                        </button>
                        <div
                            class="terms__toc-panel<?php echo 0 === $index ? ' is-active' : ''; ?>"
                            data-tab="<?php echo esc_attr( $tab['id'] ); ?>"
                            <?php echo 0 === $index ? '' : 'hidden="hidden"'; ?>
                        >
                            <div class="terms__toc-card">
                                <?php if ( ! empty( $tab['tocItems'] ) ) : ?>
                                    <ul class="terms__toc-list">
                                        <?php foreach ( $tab['tocItems'] as $item ) : ?>
                                            <?php
                                            $label = isset( $item['label'] ) ? $item['label'] : '';
                                            $id = isset( $item['id'] ) ? $item['id'] : '';
                                            if ( '' === $label || '' === $id ) {
                                                continue;
                                            }
                                            ?>
                                            <li class="terms__toc-item">
                                                <a class="terms__toc-link" href="#<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else : ?>
                                    <p class="terms__toc-empty">Add sections to build the table of contents.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <?php if ( ! empty( $atts['tocTitle'] ) ) : ?>
                        <span class="terms__toc-pill"><?php echo esc_html( $atts['tocTitle'] ); ?></span>
                    <?php endif; ?>

                    <div class="terms__toc-card">
                        <?php if ( ! empty( $toc_items ) ) : ?>
                            <ul class="terms__toc-list">
                                <?php foreach ( $toc_items as $item ) : ?>
                                    <?php
                                    $label = isset( $item['label'] ) ? $item['label'] : '';
                                    $id = isset( $item['id'] ) ? $item['id'] : '';
                                    if ( '' === $label || '' === $id ) {
                                        continue;
                                    }
                                    ?>
                                    <li class="terms__toc-item">
                                        <a class="terms__toc-link" href="#<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else : ?>
                            <p class="terms__toc-empty">Add sections to build the table of contents.</p>
                        <?php endif; ?>
                    </div>

                    <?php if ( ! empty( $policies ) ) : ?>
                        <div class="terms__toc-actions">
                            <?php foreach ( $policies as $policy ) : ?>
                                <?php
                                $policy_label = isset( $policy['label'] ) ? $policy['label'] : '';
                                $policy_url = isset( $policy['url'] ) ? $policy['url'] : '#';
                                if ( '' === $policy_label ) {
                                    continue;
                                }
                                ?>
                                <a class="terms__toc-button" href="<?php echo esc_url( $policy_url ); ?>">
                                    <?php echo esc_html( $policy_label ); ?>
                                    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </aside>

            <div class="terms__content">
                <?php if ( $has_tabs ) : ?>
                    <?php foreach ( $tab_panels as $index => $tab ) : ?>
                        <div
                            class="terms__content-panel<?php echo 0 === $index ? ' is-active' : ''; ?>"
                            data-tab="<?php echo esc_attr( $tab['id'] ); ?>"
                            role="tabpanel"
                            <?php echo 0 === $index ? '' : 'hidden="hidden"'; ?>
                        >
                            <?php if ( ! empty( $tab['contentTitle'] ) ) : ?>
                                <h2 class="terms__content-title"><?php echo esc_html( $tab['contentTitle'] ); ?></h2>
                            <?php endif; ?>

                            <?php if ( ! empty( $tab['intro'] ) ) : ?>
                                <div class="terms__intro">
                                    <?php foreach ( $tab['intro'] as $paragraph ) : ?>
                                        <?php if ( '' !== trim( $paragraph ) ) : ?>
                                            <p><?php echo esc_html( $paragraph ); ?></p>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ( ! empty( $tab['sections'] ) ) : ?>
                                <div class="terms__sections">
                                    <?php foreach ( $tab['sections'] as $section ) : ?>
                                        <?php
                                        $section_title = isset( $section['title'] ) ? $section['title'] : '';
                                        $section_id = isset( $section['resolvedId'] ) ? $section['resolvedId'] : '';
                                        $paragraphs = isset( $section['paragraphs'] ) && is_array( $section['paragraphs'] ) ? $section['paragraphs'] : array();
                                        if ( '' === $section_title && empty( $paragraphs ) ) {
                                            continue;
                                        }
                                        ?>
                                        <section class="terms__section" id="<?php echo esc_attr( $section_id ); ?>">
                                            <?php if ( $section_title ) : ?>
                                                <h3 class="terms__section-title"><?php echo esc_html( $section_title ); ?></h3>
                                            <?php endif; ?>
                                            <?php foreach ( $paragraphs as $paragraph ) : ?>
                                                <?php if ( '' !== trim( $paragraph ) ) : ?>
                                                    <p><?php echo esc_html( $paragraph ); ?></p>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </section>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <?php if ( ! empty( $atts['contentTitle'] ) ) : ?>
                        <h2 class="terms__content-title"><?php echo esc_html( $atts['contentTitle'] ); ?></h2>
                    <?php endif; ?>

                    <?php if ( ! empty( $intro ) ) : ?>
                        <div class="terms__intro">
                            <?php foreach ( $intro as $paragraph ) : ?>
                                <?php if ( '' !== trim( $paragraph ) ) : ?>
                                    <p><?php echo esc_html( $paragraph ); ?></p>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $sections ) ) : ?>
                        <div class="terms__sections">
                            <?php foreach ( $sections as $section ) : ?>
                                <?php
                                $section_title = isset( $section['title'] ) ? $section['title'] : '';
                                $section_id = isset( $section['resolvedId'] ) ? $section['resolvedId'] : '';
                                $paragraphs = isset( $section['paragraphs'] ) && is_array( $section['paragraphs'] ) ? $section['paragraphs'] : array();
                                if ( '' === $section_title && empty( $paragraphs ) ) {
                                    continue;
                                }
                                ?>
                                <section class="terms__section" id="<?php echo esc_attr( $section_id ); ?>">
                                    <?php if ( $section_title ) : ?>
                                        <h3 class="terms__section-title"><?php echo esc_html( $section_title ); ?></h3>
                                    <?php endif; ?>
                                    <?php foreach ( $paragraphs as $paragraph ) : ?>
                                        <?php if ( '' !== trim( $paragraph ) ) : ?>
                                            <p><?php echo esc_html( $paragraph ); ?></p>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </section>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
