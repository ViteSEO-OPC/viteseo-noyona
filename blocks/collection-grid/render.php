<?php
/**
 * Collection Grid Block Template.
 */

$defaults = array(
    'heading'     => '',
    'description' => '',
    'items'       => array(),
);

$atts = wp_parse_args( $attributes, $defaults );
$items = $atts['items'];

if ( empty( $items ) ) {
    if ( is_admin() ) {
        echo '<div class="collection-grid-placeholder">Add collections via the sidebar.</div>';
    }
    return;
}
?>
<div class="wp-block-noyona-collection-grid collection-grid alignwide">
    
    <div class="collection-grid__header">
        <?php if ( $atts['heading'] ) : ?>
            <h2 class="collection-grid__heading"><?php echo esc_html( $atts['heading'] ); ?></h2>
        <?php endif; ?>
        <?php if ( $atts['description'] ) : ?>
            <p class="collection-grid__description"><?php echo esc_html( $atts['description'] ); ?></p>
        <?php endif; ?>
    </div>

    <div class="collection-grid__items">
        <?php foreach ( $items as $item ) : ?>
            <?php 
                $image = isset($item['image']) ? $item['image'] : '';
                $title = isset($item['title']) ? $item['title'] : '';
                $count = isset($item['count']) ? $item['count'] : '0 Products';
            ?>
            <div class="collection-card">
                <div class="collection-card__bg" style="background-image: url('<?php echo esc_url( $image ); ?>');"></div>
                <div class="collection-card__overlay"></div>
                
                <div class="collection-card__content">
                    <div class="collection-card__pill">
                        <span class="collection-card__title"><?php echo esc_html( $title ); ?></span>
                    </div>
                    <div class="collection-card__hover-data">
                        <span class="collection-card__count"><?php echo esc_html( $count ); ?></span>
                    </div>
                </div>
                
                <a href="#" class="collection-card__link" aria-label="View <?php echo esc_attr( $title ); ?>"></a>
            </div>
        <?php endforeach; ?>
    </div>
</div>
