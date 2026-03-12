<?php
/**
 * Customer Reviews Grid Block Template.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$defaults = array(
    'heading'         => 'Customer Reviews',
    'writeReviewText' => 'Write a Review',
    'writeReviewUrl'  => '#',
    'readMoreText'    => 'Read More Reviews',
    'readMoreUrl'     => '#',
    'reviews'         => array(),
);

$atts = wp_parse_args( $attributes, $defaults );

$heading           = isset( $atts['heading'] ) ? trim( (string) $atts['heading'] ) : '';
$write_review_text = isset( $atts['writeReviewText'] ) ? trim( (string) $atts['writeReviewText'] ) : '';
$write_review_url  = isset( $atts['writeReviewUrl'] ) ? trim( (string) $atts['writeReviewUrl'] ) : '';
$read_more_text    = isset( $atts['readMoreText'] ) ? trim( (string) $atts['readMoreText'] ) : '';
$read_more_url     = isset( $atts['readMoreUrl'] ) ? trim( (string) $atts['readMoreUrl'] ) : '';
$reviews           = is_array( $atts['reviews'] ?? null ) ? $atts['reviews'] : array();
$default_media_url = 'https://placehold.co/96x96/e0e0e0/969696?text=Img';
$initial_visible   = 3;
$total_reviews     = count( $reviews );

if ( empty( $reviews ) ) {
    if ( is_admin() ) {
        echo '<div class="customer-reviews-grid-placeholder">Add review cards via the sidebar.</div>';
    }
    return;
}
?>

<section <?php echo get_block_wrapper_attributes( array( 'class' => 'customer-reviews-grid' ) ); ?>>
    <div class="customer-reviews-grid__header">
        <?php if ( '' !== $heading ) : ?>
            <h2 class="customer-reviews-grid__title"><?php echo esc_html( $heading ); ?></h2>
        <?php endif; ?>

        <?php if ( '' !== $write_review_text ) : ?>
            <a class="customer-reviews-grid__write-link" href="<?php echo esc_url( '' !== $write_review_url ? $write_review_url : '#' ); ?>">
                <i class="fa-solid fa-pencil" aria-hidden="true"></i>
                <span><?php echo esc_html( $write_review_text ); ?></span>
            </a>
        <?php endif; ?>
    </div>

    <div class="customer-reviews-grid__cards">
        <?php foreach ( $reviews as $review_index => $review ) : ?>
            <?php
            $name         = isset( $review['name'] ) ? trim( (string) $review['name'] ) : '';
            $time_ago     = isset( $review['timeAgo'] ) ? trim( (string) $review['timeAgo'] ) : '';
            $title        = isset( $review['title'] ) ? trim( (string) $review['title'] ) : '';
            $content      = isset( $review['content'] ) ? trim( (string) $review['content'] ) : '';
            $rating       = isset( $review['rating'] ) ? (int) $review['rating'] : 5;
            $verified     = ! empty( $review['verified'] );
            $helpful_text = isset( $review['helpfulText'] ) ? trim( (string) $review['helpfulText'] ) : 'Yes';
            $media_count  = isset( $review['mediaCount'] ) ? (int) $review['mediaCount'] : 2;
            $avatar_url   = isset( $review['avatarUrl'] ) ? trim( (string) $review['avatarUrl'] ) : '';

            if ( $rating < 0 ) {
                $rating = 0;
            } elseif ( $rating > 5 ) {
                $rating = 5;
            }

            if ( $media_count < 0 ) {
                $media_count = 0;
            } elseif ( $media_count > 6 ) {
                $media_count = 6;
            }

            $is_initially_hidden = $review_index >= $initial_visible;
            ?>
            <article class="customer-reviews-grid__card<?php echo $is_initially_hidden ? ' is-hidden' : ''; ?>"<?php echo $is_initially_hidden ? ' hidden' : ''; ?>>
                <header class="customer-reviews-grid__card-head">
                    <div class="customer-reviews-grid__user">
                        <span class="customer-reviews-grid__avatar" aria-hidden="true">
                            <?php if ( '' !== $avatar_url ) : ?>
                                <img src="<?php echo esc_url( $avatar_url ); ?>" alt="" loading="lazy" decoding="async" />
                            <?php else : ?>
                                <i class="fa-solid fa-circle-user"></i>
                            <?php endif; ?>
                        </span>
                        <span class="customer-reviews-grid__identity">
                            <span class="customer-reviews-grid__name"><?php echo esc_html( '' !== $name ? $name : 'Guest User' ); ?></span>
                            <?php if ( $verified ) : ?>
                                <span class="customer-reviews-grid__verified">
                                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                                    <span>Verified Buyer</span>
                                </span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php if ( '' !== $time_ago ) : ?>
                        <time class="customer-reviews-grid__time"><?php echo esc_html( $time_ago ); ?></time>
                    <?php endif; ?>
                </header>

                <div class="customer-reviews-grid__stars" aria-label="<?php echo esc_attr( sprintf( '%d out of 5 stars', $rating ) ); ?>">
                    <?php for ( $i = 0; $i < 5; $i++ ) : ?>
                        <i class="<?php echo $i < $rating ? 'fa-solid fa-star is-active' : 'fa-regular fa-star'; ?>" aria-hidden="true"></i>
                    <?php endfor; ?>
                </div>

                <?php if ( '' !== $title ) : ?>
                    <h3 class="customer-reviews-grid__review-title"><?php echo esc_html( $title ); ?></h3>
                <?php endif; ?>

                <?php if ( '' !== $content ) : ?>
                    <p class="customer-reviews-grid__review-copy"><?php echo esc_html( $content ); ?></p>
                <?php endif; ?>

                <?php
                $media_urls = array();
                if ( isset( $review['mediaUrls'] ) && is_array( $review['mediaUrls'] ) ) {
                    foreach ( $review['mediaUrls'] as $raw_media_url ) {
                        $raw_media_url = trim( (string) $raw_media_url );
                        if ( '' !== $raw_media_url ) {
                            $media_urls[] = $raw_media_url;
                        }
                    }
                }

                if ( empty( $media_urls ) && $media_count > 0 ) {
                    for ( $media_i = 0; $media_i < $media_count; $media_i++ ) {
                        $media_urls[] = $default_media_url;
                    }
                }
                ?>

                <?php if ( ! empty( $media_urls ) ) : ?>
                    <div class="customer-reviews-grid__media">
                        <?php foreach ( $media_urls as $media_url ) : ?>
                            <button
                                class="customer-reviews-grid__media-trigger"
                                type="button"
                                data-review-media
                                data-full-image="<?php echo esc_url( $media_url ); ?>"
                                aria-label="<?php esc_attr_e( 'Open review image', 'noyona-childtheme' ); ?>"
                            >
                                <img src="<?php echo esc_url( $media_url ); ?>" alt="" loading="lazy" decoding="async" />
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <footer class="customer-reviews-grid__helpful">
                    <span>Was this helpful?</span>
                    <span class="customer-reviews-grid__helpful-value">
                        <i class="fa-regular fa-thumbs-up" aria-hidden="true"></i>
                        <span><?php echo esc_html( '' !== $helpful_text ? $helpful_text : 'Yes' ); ?></span>
                    </span>
                </footer>
            </article>
        <?php endforeach; ?>
    </div>

    <?php if ( '' !== $read_more_text && $total_reviews > $initial_visible ) : ?>
        <div class="customer-reviews-grid__footer">
            <button
                class="customer-reviews-grid__read-more"
                type="button"
                data-read-more
                data-read-more-url="<?php echo esc_url( '' !== $read_more_url ? $read_more_url : '#' ); ?>"
                data-expand-label="<?php echo esc_attr( $read_more_text ); ?>"
                data-collapse-label="Collapse"
            >
                <span><?php echo esc_html( $read_more_text ); ?></span>
                <i class="fa-solid fa-angle-down customer-reviews-grid__read-more-icon" aria-hidden="true"></i>
            </button>
        </div>
    <?php endif; ?>

    <div class="customer-reviews-grid__modal" data-review-modal hidden>
        <button class="customer-reviews-grid__modal-backdrop" type="button" data-review-modal-close aria-label="<?php esc_attr_e( 'Close image preview', 'noyona-childtheme' ); ?>"></button>
        <div class="customer-reviews-grid__modal-dialog" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Review image preview', 'noyona-childtheme' ); ?>">
            <button class="customer-reviews-grid__modal-close" type="button" data-review-modal-close aria-label="<?php esc_attr_e( 'Close image preview', 'noyona-childtheme' ); ?>">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
            <img class="customer-reviews-grid__modal-image" src="" alt="" data-review-modal-image />
        </div>
    </div>
</section>
