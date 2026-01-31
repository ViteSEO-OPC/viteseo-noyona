<?php
/**
 * Product Highlight Block Template.
 */

$defaults = array(
    'heading'     => '',
    'subheading'  => '',
    'cardsToShow' => 3,
    'items'       => array(),
);

$atts = wp_parse_args( $attributes, $defaults );
$items = $atts['items'];
$cards_to_show = max( 1, intval( $atts['cardsToShow'] ) );
$total_items = count( $items );

if ( empty( $items ) ) {
    if ( is_admin() ) {
        echo '<div class="product-highlight-placeholder">Add products via the sidebar.</div>';
    }
    return;
}

$unique_id = 'ph-' . uniqid();
?>
<div class="wp-block-noyona-product-highlight product-highlight alignwide" id="<?php echo esc_attr( $unique_id ); ?>" data-cards-to-show="<?php echo esc_attr( $cards_to_show ); ?>">
    
    <div class="product-highlight__header">
        <?php if ( $atts['heading'] ) : ?>
            <h2 class="product-highlight__heading"><?php echo esc_html( $atts['heading'] ); ?></h2>
        <?php endif; ?>
        <?php if ( $atts['subheading'] ) : ?>
            <p class="product-highlight__subheading"><?php echo esc_html( $atts['subheading'] ); ?></p>
        <?php endif; ?>
    </div>

    <div class="product-highlight__carousel-wrapper">
        <button class="ph-nav-btn ph-prev" aria-label="Previous" disabled>
            <i class="fa-solid fa-chevron-left"></i>
        </button>

        <div class="product-highlight__track-container">
            <div class="product-highlight__track" style="--cards-visible: <?php echo $cards_to_show; ?>;">
                <?php foreach ( $items as $item ) : ?>
                    <div class="product-highlight__card">
                        <div class="ph-card__media">
                            <?php if ( ! empty( $item['badge'] ) ) : ?>
                                <span class="ph-card__badge"><?php echo esc_html( $item['badge'] ); ?></span>
                            <?php endif; ?>
                            
                            <?php if ( ! empty( $item['image'] ) ) : ?>
                                <img src="<?php echo esc_url( $item['image'] ); ?>" alt="<?php echo esc_attr( $item['title'] ); ?>" class="ph-card__image" />
                            <?php endif; ?>
                        </div>

                        <div class="ph-card__body">
                            <?php if ( ! empty( $item['colors'] ) ) : ?>
                                <div class="ph-card__swatches">
                                    <?php foreach ( $item['colors'] as $color ) : ?>
                                        <span class="ph-swatch" style="background-color: <?php echo esc_attr( $color ); ?>;"></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <h3 class="ph-card__title"><?php echo esc_html( $item['title'] ); ?></h3>
                            
                            <?php if ( ! empty( $item['description'] ) ) : ?>
                                <p class="ph-card__desc"><?php echo esc_html( $item['description'] ); ?></p>
                            <?php endif; ?>

                            <div class="ph-card__price-row">
                                <span class="ph-price"><?php echo esc_html( $item['price'] ); ?></span>
                                <?php if ( ! empty( $item['compareAt'] ) ) : ?>
                                    <span class="ph-compare-price"><?php echo esc_html( $item['compareAt'] ); ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="ph-card__rating">
                                <?php 
                                $rating = isset($item['rating']) ? floatval($item['rating']) : 0;
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $rating) {
                                        echo '<i class="fa-solid fa-star"></i>';
                                    } elseif ($i - 0.5 <= $rating) {
                                        echo '<i class="fa-solid fa-star-half-stroke"></i>';
                                    } else {
                                        echo '<i class="fa-regular fa-star"></i>';
                                    }
                                }
                                ?>
                                <?php if ( ! empty( $item['ratingCount'] ) ) : ?>
                                    <span class="ph-rating-count">(<?php echo esc_html( $item['ratingCount'] ); ?>)</span>
                                <?php endif; ?>
                            </div>

                            <div class="ph-card__actions">
                                <a href="<?php echo esc_url( $item['primaryUrl'] ); ?>" class="ph-btn-primary" style="background-color: <?php echo esc_attr( $item['primaryBg'] ); ?>;">
                                    <?php echo esc_html( $item['primaryText'] ); ?>
                                </a>
                                <?php if ( ! empty( $item['cartEnabled'] ) ) : ?>
                                    <a href="<?php echo esc_url( $item['cartUrl'] ); ?>" class="ph-btn-cart" style="background-color: <?php echo esc_attr( $item['cartBg'] ); ?>;">
                                        <i class="fa-solid fa-cart-shopping"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <button class="ph-nav-btn ph-next" aria-label="Next">
            <i class="fa-solid fa-chevron-right"></i>
        </button>
    </div>

    <div class="product-highlight__dots"></div>
</div>
