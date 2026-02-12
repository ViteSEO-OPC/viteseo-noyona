<?php
/**
 * Terms Policy Block Template.
 */

$defaults = array(
    'heading'             => 'Terms & Policy',
    'subheading'          => 'At Noyona Cosmetics and Skin Care Products OPC, we prioritize transparency and trust by providing clear guidelines on how we handle your orders, protect your rights, and ensure a seamless shopping experience.',
    'breadcrumbRootLabel' => 'Terms & Policy',
    'breadcrumbRootUrl'   => '/terms/',
    'breadcrumbCurrent'   => '',
    'tocTitle'            => '',
    'tocItems'            => array(),
    'contentTitle'        => 'Terms of Service',
    'intro'               => array(),
    'sections'            => array(),
);

$atts = wp_parse_args( $attributes, $defaults );

$sections  = is_array( $atts['sections'] ) ? $atts['sections'] : array();
$toc_items = is_array( $atts['tocItems'] ) ? $atts['tocItems'] : array();
$intro     = is_array( $atts['intro'] ) ? $atts['intro'] : array();
$allowed_inline_html = array(
    'a'      => array(
        'href'   => array(),
        'title'  => array(),
        'target' => array(),
        'rel'    => array(),
    ),
    'strong' => array(),
    'em'     => array(),
    'br'     => array(),
);

$used_ids = array();
$computed_toc = array();

if ( ! empty( $sections ) ) {
    foreach ( $sections as $index => $section ) {
        $label   = isset( $section['title'] ) ? $section['title'] : '';
        $raw_id  = isset( $section['id'] ) ? $section['id'] : '';
        $base_id = $raw_id ? sanitize_title( $raw_id ) : sanitize_title( $label );

        if ( '' === $base_id ) {
            $base_id = 'section-' . ( $index + 1 );
        }

        $resolved_id = $base_id;
        $suffix = 2;
        while ( in_array( $resolved_id, $used_ids, true ) ) {
            $resolved_id = $base_id . '-' . $suffix;
            $suffix++;
        }

        $used_ids[] = $resolved_id;
        $sections[ $index ]['resolvedId'] = $resolved_id;

        $computed_toc[] = array(
            'id'    => $resolved_id,
            'label' => $label ? $label : 'Section ' . ( $index + 1 ),
        );
    }
}

if ( empty( $toc_items ) && ! empty( $computed_toc ) ) {
    $toc_items = $computed_toc;
}

$breadcrumb_current = trim( (string) $atts['breadcrumbCurrent'] );
if ( '' === $breadcrumb_current ) {
    $breadcrumb_current = $atts['contentTitle'];
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
        <nav class="terms__breadcrumbs" aria-label="Breadcrumb">
            <a class="terms__crumb-link" href="/">Home</a>
            <span class="terms__crumb-sep" aria-hidden="true">&gt;</span>
            <?php if ( ! empty( $atts['breadcrumbRootLabel'] ) ) : ?>
                <a class="terms__crumb-link" href="<?php echo esc_url( $atts['breadcrumbRootUrl'] ); ?>">
                    <?php echo esc_html( $atts['breadcrumbRootLabel'] ); ?>
                </a>
            <?php endif; ?>
            <span class="terms__crumb-sep" aria-hidden="true">&gt;</span>
            <span class="terms__crumb-current"><?php echo esc_html( $breadcrumb_current ); ?></span>
        </nav>

        <div class="terms__layout">
            <aside class="terms__toc" aria-label="Table of contents">
                <?php if ( ! empty( $atts['tocTitle'] ) ) : ?>
                    <span class="terms__toc-pill"><?php echo esc_html( $atts['tocTitle'] ); ?></span>
                <?php endif; ?>

                <div class="terms__toc-card">
                    <?php if ( ! empty( $toc_items ) ) : ?>
                        <ul class="terms__toc-list">
                            <?php foreach ( $toc_items as $item ) : ?>
                                <?php
                                $label = isset( $item['label'] ) ? $item['label'] : '';
                                $id    = isset( $item['id'] ) ? $item['id'] : '';
                                if ( '' === $label || '' === $id ) {
                                    continue;
                                }
                                ?>
                                <li class="terms__toc-item">
                                    <a class="terms__toc-link" href="#<?php echo esc_attr( $id ); ?>">
                                        <?php echo esc_html( $label ); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <p class="terms__toc-empty">Add sections to build the table of contents.</p>
                    <?php endif; ?>
                </div>
            </aside>

            <div class="terms__content">
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
                            $section_id    = isset( $section['resolvedId'] ) ? $section['resolvedId'] : '';
                            $paragraphs    = isset( $section['paragraphs'] ) && is_array( $section['paragraphs'] ) ? $section['paragraphs'] : array();
                            $list_items    = isset( $section['listItems'] ) && is_array( $section['listItems'] ) ? $section['listItems'] : array();
                            $footer_text   = isset( $section['footerText'] ) ? trim( (string) $section['footerText'] ) : '';

                            if ( '' === $section_title && empty( $paragraphs ) && empty( $list_items ) && '' === $footer_text ) {
                                continue;
                            }
                            ?>
                            <section class="terms__section" id="<?php echo esc_attr( $section_id ); ?>">
                                <?php if ( $section_title ) : ?>
                                    <h3 class="terms__section-title"><?php echo esc_html( $section_title ); ?></h3>
                                <?php endif; ?>

                                <?php foreach ( $paragraphs as $paragraph ) : ?>
                                    <?php if ( '' !== trim( $paragraph ) ) : ?>
                                    <?php
                                        // Normalize real newlines
                                        $paragraph = str_replace( array( "\r\n", "\r" ), "\n", $paragraph );
                                        // If your JSON contains literal \n (backslash+n), also normalize those:
                                        $paragraph = str_replace( array("\\r\\n", "\\n", "\\r"), "\n", $paragraph );
                                    ?>
                                    <p><?php echo wp_kses( $paragraph, $allowed_inline_html ); ?></p>
                                    <?php endif; ?>
                                <?php endforeach; ?>

                                <?php if ( ! empty( $list_items ) ) : ?>
                                    <ul class="terms__section-list">
                                    <?php foreach ( $list_items as $item ) : ?>
                                        <?php if ( '' !== trim( (string) $item ) ) : ?>
                                        <?php
                                            $item = (string) $item;
                                            $item = str_replace( array( "\r\n", "\r" ), "\n", $item );
                                            $item = str_replace( array("\\r\\n", "\\n", "\\r"), "\n", $item );
                                        ?>
                                        <li><?php echo wp_kses( $item, $allowed_inline_html ); ?></li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>

                                <?php if ( '' !== $footer_text ) : ?>
                                    <?php
                                    $footer_text = str_replace( array( "\r\n", "\r" ), "\n", $footer_text );
                                    $footer_text = str_replace( array("\\r\\n", "\\n", "\\r"), "\n", $footer_text );
                                    ?>
                                    <p class="terms__section-footer"><?php echo wp_kses( $footer_text, $allowed_inline_html ); ?></p>
                                <?php endif; ?>
                            </section>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

