<?php
/**
 * Brand / Top Reviews Carousel Template.
 *
 * Dynamic render callback for the noyona/brand-carousel block.
 */

$defaults = array(
    'items'          => array(),
    'speed'          => 40,
    'heading'        => '',
    'description'    => '',
    'mode'           => 'reviews', // "reviews" or "brands"
    'minRating'      => 0,

    // Design attributes
    'brandFontSize'  => '', // e.g. "1.1rem" or "clamp(1rem, 2vw, 1.4rem)"
    'brandFontWeight'=> '', // e.g. "600" or "700"
    'brandFontColor' => '', // e.g. "#333333"
    'paddingTop'     => '', // e.g. "3rem" or "40px"
    'paddingBottom'  => '', // e.g. "3rem" or "40px"
    'backgroundColor'=> '', // e.g. "#f3f3f3"
);

$atts = wp_parse_args( $attributes, $defaults );
$items = $atts['items'];
$heading = $atts['heading'];
$description = $atts['description'];
$mode = $atts['mode'];
$minRating = (float) $atts['minRating'];

$social_icon_map = array(
    'facebook'  => 'fa-brands fa-facebook-f',
    'tiktok'    => 'fa-brands fa-tiktok',
    'instagram' => 'fa-brands fa-instagram',
    'shopee'    => 'fa-solid fa-bag-shopping',
    'lazada'    => 'fa-solid fa-store',
    'other'     => 'fa-solid fa-globe',
);

$social_url_map = array(
    'facebook'  => 'https://www.facebook.com/Noyonacosmetics',
    'instagram' => 'https://www.instagram.com/noyonacosmetics/',
    'tiktok'    => 'https://www.tiktok.com/@noyona_cosmetics',
    'shopee'    => 'https://shopee.ph/noyona_official',
    'lazada'    => 'https://www.lazada.com.ph/shop/noyona-lovial-essentials/',
);

// Early exit if no items
if ( empty( $items ) ) {
    if ( is_admin() ) {
        echo '<div class="brand-carousel-placeholder">Add items to the carousel via the block sidebar.</div>';
    }
    return;
}

/**
 * If we’re in "reviews" mode and minRating > 0,
 * filter to only top-rated items.
 */
if ( 'reviews' === $mode && $minRating > 0 ) {
    $items = array_filter(
        $items,
        function ( $item ) use ( $minRating ) {
            if ( ! isset( $item['rating'] ) ) {
                return false;
            }
            return (float) $item['rating'] >= $minRating;
        }
    );
}

// If filtering removed everything, bail.
if ( empty( $items ) ) {
    return;
}

// Duplicate items to ensure smooth infinite scroll
$display_items = array_merge( $items, $items, $items, $items );

// Build CSS variable list, attached to wrapper as inline style
$style_rules = array();

if ( ! empty( $atts['brandFontSize'] ) ) {
    $style_rules[] = '--brand-font-size:' . $atts['brandFontSize'];
}
if ( ! empty( $atts['brandFontWeight'] ) ) {
    $style_rules[] = '--brand-font-weight:' . $atts['brandFontWeight'];
}
if ( ! empty( $atts['brandFontColor'] ) ) {
    $style_rules[] = '--brand-font-color:' . $atts['brandFontColor'];
}
if ( ! empty( $atts['paddingTop'] ) ) {
    $style_rules[] = '--brand-padding-top:' . $atts['paddingTop'];
}
if ( ! empty( $atts['paddingBottom'] ) ) {
    $style_rules[] = '--brand-padding-bottom:' . $atts['paddingBottom'];
}
if ( ! empty( $atts['backgroundColor'] ) ) {
    $style_rules[] = '--brand-bg-color:' . $atts['backgroundColor'];
}

$style_attr = $style_rules
    ? ' style="' . esc_attr( implode( ';', $style_rules ) . ';' ) . '"'
    : '';

?>
<div class="wp-block-noyona-brand-carousel brand-carousel alignfull"<?php echo $style_attr; ?>>

    <?php if ( $heading || $description ) : ?>
        <div class="brand-carousel__header">
            <?php if ( $heading ) : ?>
                <h2 class="brand-carousel__heading">
                    <?php echo esc_html( $heading ); ?>
                </h2>
            <?php endif; ?>
            <?php if ( $description ) : ?>
                <p class="brand-carousel__description">
                    <?php echo esc_html( $description ); ?>
                </p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="brand-carousel__track" style="--scroll-speed: <?php echo intval( $atts['speed'] ); ?>s;">
        <?php foreach ( $display_items as $item ) : ?>
            <?php
            $quote     = isset( $item['quote'] )   ? $item['quote']   : '';
            $author    = isset( $item['author'] )  ? $item['author']  : '';
            $product   = isset( $item['product'] ) ? $item['product'] : '';
            $rating    = isset( $item['rating'] )  ? (float) $item['rating'] : 0;
            $social    = isset( $item['social'] )  ? sanitize_key( (string) $item['social'] ) : 'none';
            $show_rating = isset( $item['rating'] ) && $item['rating'] !== null && $item['rating'] !== '';
            $social_icon_class = isset( $social_icon_map[ $social ] ) ? $social_icon_map[ $social ] : 'fa-solid fa-user';
            $avatar_social_class = 'review-card__avatar--' . sanitize_html_class( $social ?: 'none' );
            $card_url = isset( $social_url_map[ $social ] ) ? $social_url_map[ $social ] : '';

            // For brand mode
            $brandName = isset( $item['brand'] ) ? $item['brand'] : '';
            $logo      = isset( $item['logo'] )  ? $item['logo']  : '';
            $logo_id   = isset( $item['logoId'] ) ? absint( $item['logoId'] ) : 0;
            if ( $logo_id ) {
                $resolved_logo = wp_get_attachment_image_url( $logo_id, 'medium' );
                if ( $resolved_logo ) {
                    $logo = (string) $resolved_logo;
                }
            } elseif ( ! empty( $logo ) ) {
                $resolved_logo_id = attachment_url_to_postid( $logo );
                if ( $resolved_logo_id ) {
                    $resolved_logo = wp_get_attachment_image_url( (int) $resolved_logo_id, 'medium' );
                    if ( $resolved_logo ) {
                        $logo = (string) $resolved_logo;
                    }
                }
            }
            ?>

            <?php if ( 'brands' === $mode ) : ?>

                <!-- BRAND / PARTNER ITEM -->
                <div class="brand-carousel__item">

                    <?php if ( $logo ) : ?>
                        <div class="brand-carousel__image-wrapper">
                            <img
                                class="brand-carousel__image"
                                src="<?php echo esc_url( $logo ); ?>"
                                alt="<?php echo esc_attr( $brandName ?: 'Partner/Brand' ); ?>"
                            />
                        </div>
                    <?php endif; ?>

                    <div class="brand-carousel__text">
                        <?php echo esc_html( $brandName ?: 'PARTNER/BRAND' ); ?>
                    </div>
                </div>

            <?php else : ?>

                <!-- REVIEW CARD ITEM -->
                <?php if ( ! empty( $card_url ) ) : ?>
                    <a class="review-card review-card--link" href="<?php echo esc_url( $card_url ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( ucfirst( $social ) . ' review by ' . $author ); ?>">
                <?php else : ?>
                    <div class="review-card">
                <?php endif; ?>
                        <div class="review-card__quote-icon">
                            <i class="fas fa-quote-left"></i>
                        </div>

                        <p class="review-card__text">
                            <?php echo esc_html( $quote ); ?>
                        </p>

                        <?php if ( $show_rating ) : ?>
                            <div class="review-card__rating">
                                <?php for ( $i = 0; $i < 5; $i++ ) : ?>
                                    <?php if ( $i < round( $rating ) ) : ?>
                                        <i class="fas fa-star"></i>
                                    <?php else : ?>
                                        <i class="far fa-star"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                        <?php endif; ?>

                        <div class="review-card__footer">
                            <div class="review-card__avatar <?php echo esc_attr( $avatar_social_class ); ?>">
                                <i class="<?php echo esc_attr( $social_icon_class ); ?>" aria-hidden="true"></i>
                            </div>
                            <div class="review-card__meta">
                                <span class="review-card__author-wrap">
                                    <span class="review-card__author">
                                        <?php echo esc_html( $author ); ?>
                                    </span>
                                </span>
                                <span class="review-card__product">
                                    <?php echo esc_html( $product ); ?>
                                </span>
                            </div>
                        </div>
                <?php if ( ! empty( $card_url ) ) : ?>
                    </a>
                <?php else : ?>
                    </div>
                <?php endif; ?>

            <?php endif; ?>

        <?php endforeach; ?>
    </div>
</div>
