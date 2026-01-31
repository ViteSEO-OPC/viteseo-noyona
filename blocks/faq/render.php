<?php
/**
 * FAQ Block Template.
 */

$defaults = array(
    'layout'     => 'simple',
    'heading'    => 'Frequently Asked Questions',
    'subheading' => 'Quick answers to your questions about shipping, returns, and how to get the most out of our products',
    'items'      => array(),
    'buttonText' => 'View FAQs',
    'buttonUrl'  => '/faq/',
);

$atts = wp_parse_args( $attributes, $defaults );
$layout = isset( $atts['layout'] ) ? $atts['layout'] : 'simple';
if ( ! in_array( $layout, array( 'simple', 'dropdown' ), true ) ) {
    $layout = 'simple';
}
$items = isset( $atts['items'] ) && is_array( $atts['items'] ) ? $atts['items'] : array();
$wrapper_class = 'wp-block-noyona-faq faq-block alignfull faq-block--' . $layout;
?>
<section class="<?php echo esc_attr( $wrapper_class ); ?>">
    <div class="faq-block__inner">
        <?php if ( ! empty( $atts['heading'] ) ) : ?>
            <h2 class="faq-block__title"><?php echo esc_html( $atts['heading'] ); ?></h2>
        <?php endif; ?>

        <?php if ( ! empty( $atts['subheading'] ) ) : ?>
            <p class="faq-block__subtitle"><?php echo esc_html( $atts['subheading'] ); ?></p>
        <?php endif; ?>

        <?php if ( 'dropdown' === $layout ) : ?>
            <?php if ( empty( $items ) ) : ?>
                <?php if ( is_admin() ) : ?>
                    <div class="faq-block__placeholder">Add FAQ items via the sidebar.</div>
                <?php endif; ?>
            <?php else : ?>
                <div class="faq-block__accordion">
                    <?php foreach ( $items as $index => $item ) : ?>
                        <?php
                        $question = isset( $item['question'] ) ? $item['question'] : '';
                        $answer = isset( $item['answer'] ) ? $item['answer'] : '';
                        ?>
                        <details class="faq-accordion__item" <?php echo 0 === $index ? 'open' : ''; ?>>
                            <summary class="faq-accordion__summary">
                                <span class="faq-accordion__question"><?php echo esc_html( $question ); ?></span>
                                <span class="faq-accordion__icon" aria-hidden="true"></span>
                            </summary>
                            <?php if ( '' !== $answer ) : ?>
                                <div class="faq-accordion__answer">
                                    <p><?php echo esc_html( $answer ); ?></p>
                                </div>
                            <?php endif; ?>
                        </details>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ( ! empty( $atts['buttonText'] ) && ! empty( $atts['buttonUrl'] ) ) : ?>
            <a class="faq-block__button" href="<?php echo esc_url( $atts['buttonUrl'] ); ?>">
                <?php echo esc_html( $atts['buttonText'] ); ?>
            </a>
        <?php endif; ?>
    </div>
</section>
