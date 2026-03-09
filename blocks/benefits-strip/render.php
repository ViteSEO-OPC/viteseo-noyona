<?php
/**
 * Benefits Strip block render.
 *
 * @param array $attributes Block attributes.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$defaults = array(
    'title'           => 'Why Noyona Face Makeup Work For You',
    'backgroundColor' => '#E9A3AD',
    'items'           => array(),
);

$atts  = wp_parse_args( is_array( $attributes ) ? $attributes : array(), $defaults );
$title = isset( $atts['title'] ) ? (string) $atts['title'] : '';
$bg    = sanitize_hex_color( isset( $atts['backgroundColor'] ) ? (string) $atts['backgroundColor'] : '' );
$items = isset( $atts['items'] ) && is_array( $atts['items'] ) ? $atts['items'] : array();

if ( ! $bg ) {
    $bg = '#E9A3AD';
}

$align = '';
$align_value = '';
if ( isset( $atts['align'] ) && is_string( $atts['align'] ) ) {
    $align_value = $atts['align'];
} elseif ( isset( $block ) && is_array( $block ) && ! empty( $block['align'] ) ) {
    $align_value = (string) $block['align'];
}

if ( 'full' === $align_value ) {
    $align = ' alignfull';
} elseif ( 'wide' === $align_value ) {
    $align = ' alignwide';
}
?>
<section class="wp-block-noyona-benefits-strip noyona-benefits-strip<?php echo esc_attr( $align ); ?>" style="--noyona-benefits-bg: <?php echo esc_attr( $bg ); ?>;">
    <div class="noyona-benefits-strip__inner">
        <?php if ( '' !== trim( $title ) ) : ?>
            <h2 class="noyona-benefits-strip__title"><?php echo esc_html( $title ); ?></h2>
        <?php endif; ?>

        <?php if ( ! empty( $items ) ) : ?>
            <div class="noyona-benefits-strip__grid">
                <?php foreach ( $items as $item ) : ?>
                    <?php
                    $item = is_array( $item ) ? $item : array();
                    $icon_class = isset( $item['iconClass'] ) ? (string) $item['iconClass'] : '';
                    $icon_text = isset( $item['iconText'] ) ? (string) $item['iconText'] : '';
                    $item_title = isset( $item['title'] ) ? (string) $item['title'] : '';
                    $item_desc  = isset( $item['description'] ) ? (string) $item['description'] : '';
                    ?>
                    <article class="noyona-benefits-strip__item">
                        <div class="noyona-benefits-strip__icon" aria-hidden="true">
                            <?php if ( '' !== trim( $icon_class ) ) : ?>
                                <i class="<?php echo esc_attr( $icon_class ); ?>"></i>
                            <?php else : ?>
                                <span><?php echo esc_html( $icon_text ); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ( '' !== trim( $item_title ) ) : ?>
                            <h3 class="noyona-benefits-strip__item-title"><?php echo esc_html( $item_title ); ?></h3>
                        <?php endif; ?>
                        <?php if ( '' !== trim( $item_desc ) ) : ?>
                            <p class="noyona-benefits-strip__item-desc"><?php echo esc_html( $item_desc ); ?></p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
