<?php
/**
 * Color Swatches block render.
 *
 * @param array $attributes Block attributes.
 */

$defaults = array(
    'colors' => array(
        array( 'label' => 'Rose', 'value' => '#e06c99' ),
        array( 'label' => 'Coral', 'value' => '#f58a8a' ),
        array( 'label' => 'Mauve', 'value' => '#b54f7c' ),
    ),
    'align' => '',
);

$atts = wp_parse_args( $attributes, $defaults );
$colors = is_array( $atts['colors'] ) ? $atts['colors'] : $defaults['colors'];
$align_class = isset( $atts['align'] ) && $atts['align'] ? 'align' . $atts['align'] : '';

if ( empty( $colors ) ) {
    $colors = $defaults['colors'];
}
?>
<div class="wp-block-noyona-color-swatches color-swatches <?php echo esc_attr( $align_class ); ?>">
    <div class="color-swatches__list" role="list">
        <?php foreach ( $colors as $color ) : ?>
            <?php
            $value = isset( $color['value'] ) ? $color['value'] : '';
            $label = isset( $color['label'] ) ? $color['label'] : '';
            if ( ! $value ) {
                continue;
            }
            ?>
            <div class="color-swatches__item" role="listitem" title="<?php echo esc_attr( $label ?: $value ); ?>">
                <span class="color-swatches__dot" style="background-color: <?php echo esc_attr( $value ); ?>;"></span>
                <?php if ( $label ) : ?>
                    <span class="color-swatches__label"><?php echo esc_html( $label ); ?></span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
