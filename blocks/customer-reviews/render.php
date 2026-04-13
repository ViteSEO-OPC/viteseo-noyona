<?php
/**
 * Customer Reviews Grid Block Template.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'noyona_cr_extract_media_urls' ) ) {
    /**
     * Extract review media URLs from common comment meta sources.
     *
     * @param int $comment_id Comment ID.
     * @return string[]
     */
    function noyona_cr_extract_media_urls( $comment_id ) {
        $urls = array();
        $meta_keys = array(
            'review_image_ids',
            'wc_review_image_ids',
            'ivole_review_image_ids',
            'review_images',
            'wc_review_images',
            'ivole_review_images',
            'noyona_review_image_ids',
        );

        foreach ( $meta_keys as $meta_key ) {
            $raw = get_comment_meta( $comment_id, $meta_key, true );
            if ( empty( $raw ) ) {
                continue;
            }

            $values = array();
            if ( is_array( $raw ) ) {
                $values = $raw;
            } elseif ( is_string( $raw ) ) {
                $trimmed = trim( $raw );
                $decoded = json_decode( $trimmed, true );
                if ( is_array( $decoded ) ) {
                    $values = $decoded;
                } elseif ( false !== strpos( $trimmed, ',' ) ) {
                    $values = array_map( 'trim', explode( ',', $trimmed ) );
                } else {
                    $values = array( $trimmed );
                }
            } else {
                $values = array( $raw );
            }

            foreach ( $values as $value ) {
                if ( is_array( $value ) ) {
                    if ( ! empty( $value['url'] ) ) {
                        $url = esc_url_raw( (string) $value['url'] );
                        if ( '' !== $url ) {
                            $urls[] = $url;
                        }
                    }
                    continue;
                }

                $string_value = trim( (string) $value );
                if ( '' === $string_value ) {
                    continue;
                }

                if ( preg_match( '#^https?://#i', $string_value ) ) {
                    $urls[] = esc_url_raw( $string_value );
                    continue;
                }

                if ( ctype_digit( $string_value ) ) {
                    $attachment_id = (int) $string_value;
                    $image_url     = wp_get_attachment_image_url( $attachment_id, 'full' );
                    if ( ! $image_url ) {
                        $image_url = wp_get_attachment_url( $attachment_id );
                    }
                    if ( $image_url ) {
                        $urls[] = $image_url;
                    }
                }
            }
        }

        return array_values( array_unique( array_filter( $urls ) ) );
    }
}

if ( ! function_exists( 'noyona_cr_extract_variant_label' ) ) {
    /**
     * Try to find a shade/color-like label from comment meta.
     *
     * @param int $comment_id Comment ID.
     * @return string
     */
    function noyona_cr_extract_variant_label( $comment_id ) {
        $all_meta = get_comment_meta( $comment_id );
        if ( ! is_array( $all_meta ) || empty( $all_meta ) ) {
            return '';
        }

        foreach ( $all_meta as $meta_key => $values ) {
            $normalized_key = strtolower( (string) $meta_key );
            if ( 'rating' === $normalized_key ) {
                continue;
            }

            if (
                false === strpos( $normalized_key, 'shade' ) &&
                false === strpos( $normalized_key, 'color' ) &&
                false === strpos( $normalized_key, 'colour' ) &&
                false === strpos( $normalized_key, 'variant' ) &&
                false === strpos( $normalized_key, 'tone' ) &&
                false === strpos( $normalized_key, 'tint' )
            ) {
                continue;
            }

            $value = '';
            if ( is_array( $values ) && ! empty( $values[0] ) ) {
                $value = (string) $values[0];
            } elseif ( is_scalar( $values ) ) {
                $value = (string) $values;
            }
            $value = trim( $value );
            if ( '' === $value ) {
                continue;
            }

            if ( 0 === strpos( $normalized_key, 'attribute_pa_' ) || 0 === strpos( $normalized_key, 'pa_' ) ) {
                $taxonomy = 0 === strpos( $normalized_key, 'attribute_' ) ? substr( $normalized_key, 10 ) : $normalized_key;
                if ( taxonomy_exists( $taxonomy ) ) {
                    $term = get_term_by( 'slug', $value, $taxonomy );
                    if ( $term && ! is_wp_error( $term ) ) {
                        return (string) $term->name;
                    }
                }
            }

            return $value;
        }

        return '';
    }
}

if ( ! function_exists( 'noyona_cr_build_dynamic_reviews' ) ) {
    /**
     * Build normalized review card data from WP comments.
     *
     * @param WP_Comment[] $comments Comment objects.
     * @return array<int, array<string, mixed>>
     */
    function noyona_cr_build_dynamic_reviews( $comments ) {
        $results = array();
        if ( ! is_array( $comments ) ) {
            return $results;
        }

        foreach ( $comments as $comment ) {
            if ( ! $comment instanceof WP_Comment ) {
                continue;
            }

            $rating = (int) get_comment_meta( $comment->comment_ID, 'rating', true );
            if ( $rating < 0 ) {
                $rating = 0;
            } elseif ( $rating > 5 ) {
                $rating = 5;
            }

            $content = trim( wp_strip_all_tags( (string) $comment->comment_content ) );
            if ( '' === $content ) {
                continue;
            }

            $title = (string) get_comment_meta( $comment->comment_ID, 'review_title', true );
            $title = trim( wp_strip_all_tags( $title ) );
            if ( '' === $title ) {
                $title = wp_trim_words( $content, 7, '...' );
            }

            $product_id    = (int) $comment->comment_post_ID;
            $product_title = get_the_title( $product_id );
            $product_url   = get_permalink( $product_id );
            $variant_label = noyona_cr_extract_variant_label( $comment->comment_ID );
            $media_urls    = noyona_cr_extract_media_urls( $comment->comment_ID );

            $timestamp = strtotime( (string) $comment->comment_date_gmt );
            if ( ! $timestamp ) {
                $timestamp = strtotime( (string) $comment->comment_date );
            }
            $time_ago = $timestamp ? human_time_diff( $timestamp, current_time( 'timestamp', true ) ) . ' ago' : '';

            $is_verified = false;
            if ( function_exists( 'wc_review_is_from_verified_owner' ) ) {
                $is_verified = (bool) wc_review_is_from_verified_owner( $comment->comment_ID );
            }
            $viewer_has_voted = false;
            if ( function_exists( 'noyona_pdp_get_review_voter_key' ) ) {
                $voter_key = noyona_pdp_get_review_voter_key();
                $voters    = get_comment_meta( $comment->comment_ID, 'noyona_review_helpful_voters', true );
                if ( is_array( $voters ) && '' !== $voter_key ) {
                    $viewer_has_voted = in_array( $voter_key, $voters, true );
                }
            }

            $results[] = array(
                'commentId'    => (int) $comment->comment_ID,
                'name'         => trim( (string) $comment->comment_author ),
                'timeAgo'      => $time_ago,
                'title'        => $title,
                'content'      => $content,
                'rating'       => $rating,
                'timestamp'    => (int) $timestamp,
                'verified'     => $is_verified,
                'helpfulText'  => 'Yes',
                'helpfulCount' => (int) get_comment_meta( $comment->comment_ID, 'noyona_review_helpful_count', true ),
                'viewerVoted'  => $viewer_has_voted,
                'mediaCount'   => count( $media_urls ),
                'mediaUrls'    => $media_urls,
                'avatarUrl'    => get_avatar_url( $comment->comment_author_email, array( 'size' => 96 ) ),
                'productTitle' => (string) $product_title,
                'productUrl'   => $product_url ? (string) $product_url : '',
                'variantLabel' => $variant_label,
            );
        }

        return $results;
    }
}

$defaults = array(
    'heading'         => 'Customer Reviews',
    'writeReviewText' => 'Write a Review',
    'writeReviewUrl'  => '#',
    'readMoreText'    => 'Read More Reviews',
    'readMoreUrl'     => '#',
    'dataSource'      => 'auto',
    'sourceCategories' => array(),
    'postsPerPage'    => 9,
    'initialVisible'  => 3,
    'showWriteReviewCta' => true,
    'showReadMore'    => true,
    'showProductMeta' => true,
    'showReviewForm'  => true,
    'emptyMessage'    => 'No reviews yet.',
    'reviews'         => array(),
);

$atts = wp_parse_args( $attributes, $defaults );

$heading           = isset( $atts['heading'] ) ? trim( (string) $atts['heading'] ) : '';
$write_review_text = isset( $atts['writeReviewText'] ) ? trim( (string) $atts['writeReviewText'] ) : '';
$read_more_text    = isset( $atts['readMoreText'] ) ? trim( (string) $atts['readMoreText'] ) : '';
$read_more_url     = isset( $atts['readMoreUrl'] ) ? trim( (string) $atts['readMoreUrl'] ) : '';
$fallback_reviews  = is_array( $atts['reviews'] ?? null ) ? $atts['reviews'] : array();
$data_source       = isset( $atts['dataSource'] ) ? sanitize_key( (string) $atts['dataSource'] ) : 'auto';
$source_categories = isset( $atts['sourceCategories'] ) && is_array( $atts['sourceCategories'] ) ? array_map( 'sanitize_title', $atts['sourceCategories'] ) : array();
$posts_per_page    = isset( $atts['postsPerPage'] ) ? (int) $atts['postsPerPage'] : 9;
$initial_visible   = isset( $atts['initialVisible'] ) ? (int) $atts['initialVisible'] : 3;
$show_write_cta    = ! empty( $atts['showWriteReviewCta'] );
$show_read_more    = ! empty( $atts['showReadMore'] );
$show_product_meta = ! empty( $atts['showProductMeta'] );
$show_review_form  = ! empty( $atts['showReviewForm'] );
$default_media_url = 'https://placehold.co/96x96/e0e0e0/969696?text=Img';

if ( $posts_per_page < 1 ) {
    $posts_per_page = 9;
}
if ( $initial_visible < 1 ) {
    $initial_visible = 3;
}

$is_product_page = function_exists( 'is_product' ) && is_product();
$reviews         = array();

if ( class_exists( 'WooCommerce' ) && 'static' !== $data_source ) {
    $query_args = array(
        'status'  => 'approve',
        'type'    => 'review',
        'number'  => $posts_per_page,
        'orderby' => 'comment_date_gmt',
        'order'   => 'DESC',
    );

    $resolved_source = $data_source;
    if ( 'auto' === $resolved_source ) {
        if ( $is_product_page ) {
            $resolved_source = 'product';
        } elseif ( ! empty( $source_categories ) ) {
            $resolved_source = 'category';
        } elseif ( is_tax( 'product_cat' ) ) {
            $resolved_source = 'category';
        } else {
            $resolved_source = 'all';
        }
    }

    if ( 'product' === $resolved_source && $is_product_page ) {
        $query_args['post_id'] = get_the_ID();
        $query_args['number']  = 0; // PDP needs complete set for accurate summary/filtering.
    } elseif ( 'category' === $resolved_source ) {
        if ( empty( $source_categories ) && is_tax( 'product_cat' ) ) {
            $term = get_queried_object();
            if ( $term instanceof WP_Term ) {
                $source_categories = array( $term->slug );
            }
        }

        if ( ! empty( $source_categories ) && function_exists( 'wc_get_products' ) ) {
            $product_ids = wc_get_products(
                array(
                    'limit'    => 200,
                    'status'   => 'publish',
                    'return'   => 'ids',
                    'category' => $source_categories,
                )
            );
            $query_args['post__in'] = ! empty( $product_ids ) ? array_map( 'absint', $product_ids ) : array( 0 );
        } else {
            $query_args['post__in'] = array( 0 );
        }
    }

    $dynamic_comments = get_comments( $query_args );
    $reviews          = noyona_cr_build_dynamic_reviews( $dynamic_comments );
}

// Keep static cards only as an editor fallback.
if ( empty( $reviews ) && is_admin() && ! empty( $fallback_reviews ) ) {
    $reviews = $fallback_reviews;
}

$total_reviews = count( $reviews );
$wrapper_args = array(
    'class' => 'customer-reviews-grid',
);
if ( $is_product_page ) {
    $wrapper_args['id'] = 'reviews';
}
$wrapper_args['data-review-helpful-endpoint'] = admin_url( 'admin-ajax.php' );
$wrapper_args['data-review-helpful-nonce']    = wp_create_nonce( 'noyona-review-helpful' );
$wrapper_attrs = get_block_wrapper_attributes( $wrapper_args );

$show_review_form = $show_review_form && $is_product_page;
$show_write_cta = $show_write_cta && $show_review_form;
$reviewer_logged_in = is_user_logged_in();
$has_reviews      = $total_reviews > 0;
$summary_average = 0.0;
$summary_count   = $total_reviews;

if ( $is_product_page && function_exists( 'wc_get_product' ) ) {
    $summary_product = wc_get_product( get_the_ID() );
    if ( $summary_product ) {
        $summary_average = (float) $summary_product->get_average_rating();
        $summary_count   = (int) $summary_product->get_review_count();
    }
}
if ( $summary_count < 1 ) {
    $summary_count = $total_reviews;
}
if ( $summary_average <= 0 && $total_reviews > 0 ) {
    $rating_sum = 0.0;
    foreach ( $reviews as $summary_review ) {
        $rating_sum += isset( $summary_review['rating'] ) ? (float) $summary_review['rating'] : 0;
    }
    $summary_average = $rating_sum / max( 1, $total_reviews );
}
if ( $summary_average < 0 ) {
    $summary_average = 0;
} elseif ( $summary_average > 5 ) {
    $summary_average = 5;
}
$summary_average_label = number_format_i18n( $summary_average, 1 );
$initial_shown_count   = min( $initial_visible, $total_reviews );

if ( ! $has_reviews && ! $show_write_cta && ! is_admin() ) {
    return;
}

?>

<section <?php echo $wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
    <?php if ( ( $has_reviews && '' !== $heading ) || ( $show_write_cta && '' !== $write_review_text ) ) : ?>
        <div class="customer-reviews-grid__header">
            <?php if ( $has_reviews && '' !== $heading ) : ?>
                <h2 class="customer-reviews-grid__title"><?php echo esc_html( $heading ); ?></h2>
            <?php endif; ?>

            <?php if ( $show_write_cta && '' !== $write_review_text ) : ?>
                <button class="customer-reviews-grid__write-link" type="button" <?php echo $reviewer_logged_in ? 'data-review-form-open' : 'data-review-login-open'; ?>>
                    <i class="fa-solid fa-pencil" aria-hidden="true"></i>
                    <span><?php echo esc_html( $write_review_text ); ?></span>
                </button>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ( $has_reviews && $is_product_page ) : ?>
        <div class="customer-reviews-grid__summary">
            <div class="customer-reviews-grid__summary-rating" aria-label="<?php echo esc_attr( sprintf( '%s out of 5 stars', $summary_average_label ) ); ?>">
                <span class="customer-reviews-grid__summary-stars" aria-hidden="true">
                    <?php for ( $summary_i = 1; $summary_i <= 5; $summary_i++ ) : ?>
                        <?php if ( $summary_average >= $summary_i ) : ?>
                            <i class="fa-solid fa-star"></i>
                        <?php elseif ( $summary_average >= ( $summary_i - 0.5 ) ) : ?>
                            <i class="fa-solid fa-star-half-stroke"></i>
                        <?php else : ?>
                            <i class="fa-regular fa-star"></i>
                        <?php endif; ?>
                    <?php endfor; ?>
                </span>
                <span class="customer-reviews-grid__summary-text">
                    <?php
                    echo esc_html(
                        sprintf(
                            /* translators: 1: average rating 2: review count */
                            __( '%1$s out of 5 | Based on %2$d reviews', 'noyona-childtheme' ),
                            $summary_average_label,
                            $summary_count
                        )
                    );
                    ?>
                </span>
            </div>
            <div class="customer-reviews-grid__summary-controls">
                <div class="customer-reviews-grid__filters">
                    <label class="customer-reviews-grid__filter-select-wrap" for="customer-reviews-sort-<?php echo esc_attr( (string) get_the_ID() ); ?>">
                        <select id="customer-reviews-sort-<?php echo esc_attr( (string) get_the_ID() ); ?>" class="customer-reviews-grid__filter-select" data-review-sort>
                            <option value="recent"><?php esc_html_e( 'Most Recent', 'noyona-childtheme' ); ?></option>
                            <option value="trustable"><?php esc_html_e( 'Most Trustable', 'noyona-childtheme' ); ?></option>
                            <option value="highest"><?php esc_html_e( 'Highest Rating', 'noyona-childtheme' ); ?></option>
                            <option value="lowest"><?php esc_html_e( 'Lowest Rating', 'noyona-childtheme' ); ?></option>
                        </select>
                    </label>
                    <button class="customer-reviews-grid__filter-photos" type="button" data-review-with-photos aria-pressed="false">
                        <i class="fa-regular fa-images" aria-hidden="true"></i>
                        <span><?php esc_html_e( 'With Photos', 'noyona-childtheme' ); ?></span>
                    </button>
                </div>
                <p class="customer-reviews-grid__showing" data-review-showing data-total="<?php echo esc_attr( (string) $summary_count ); ?>" data-review-word="<?php esc_attr_e( 'reviews', 'noyona-childtheme' ); ?>">
                    <?php
                    echo esc_html(
                        sprintf(
                            /* translators: 1: shown count 2: total count */
                            __( 'Showing %1$d of %2$d reviews', 'noyona-childtheme' ),
                            $initial_shown_count,
                            $summary_count
                        )
                    );
                    ?>
                </p>
            </div>
        </div>
    <?php endif; ?>

    <?php if ( $has_reviews ) : ?>
        <div class="customer-reviews-grid__cards">
            <?php foreach ( $reviews as $review_index => $review ) : ?>
                <?php
                $name          = isset( $review['name'] ) ? trim( (string) $review['name'] ) : '';
                $time_ago      = isset( $review['timeAgo'] ) ? trim( (string) $review['timeAgo'] ) : '';
                $title         = isset( $review['title'] ) ? trim( (string) $review['title'] ) : '';
                $content       = isset( $review['content'] ) ? trim( (string) $review['content'] ) : '';
                $rating        = isset( $review['rating'] ) ? (int) $review['rating'] : 5;
                $verified      = ! empty( $review['verified'] );
                $helpful_text  = isset( $review['helpfulText'] ) ? trim( (string) $review['helpfulText'] ) : 'Yes';
                $helpful_count = isset( $review['helpfulCount'] ) ? (int) $review['helpfulCount'] : 0;
                $viewer_voted  = ! empty( $review['viewerVoted'] );
                $comment_id    = isset( $review['commentId'] ) ? (int) $review['commentId'] : 0;
                $media_count   = isset( $review['mediaCount'] ) ? (int) $review['mediaCount'] : 2;
                $avatar_url    = isset( $review['avatarUrl'] ) ? trim( (string) $review['avatarUrl'] ) : '';
                $product_title = isset( $review['productTitle'] ) ? trim( (string) $review['productTitle'] ) : '';
                $product_url   = isset( $review['productUrl'] ) ? trim( (string) $review['productUrl'] ) : '';
                $variant_label = isset( $review['variantLabel'] ) ? trim( (string) $review['variantLabel'] ) : '';
                $timestamp_raw = isset( $review['timestamp'] ) ? (int) $review['timestamp'] : 0;

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
                $has_photo_hint      = $media_count > 0;
                ?>
                <article class="customer-reviews-grid__card<?php echo $is_initially_hidden ? ' is-hidden' : ''; ?>" data-review-rating="<?php echo esc_attr( (string) $rating ); ?>" data-review-timestamp="<?php echo esc_attr( (string) $timestamp_raw ); ?>" data-review-helpful-count="<?php echo esc_attr( (string) $helpful_count ); ?>" data-review-has-photos="<?php echo $has_photo_hint ? '1' : '0'; ?>"<?php echo $is_initially_hidden ? ' hidden' : ''; ?>>
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

                    <?php if ( $show_product_meta && '' !== $product_title ) : ?>
                        <p class="customer-reviews-grid__product-meta">
                            <span class="customer-reviews-grid__product-label">Reviewed:</span>
                            <?php if ( '' !== $product_url ) : ?>
                                <a class="customer-reviews-grid__product-link" href="<?php echo esc_url( $product_url ); ?>"><?php echo esc_html( $product_title ); ?></a>
                            <?php else : ?>
                                <span class="customer-reviews-grid__product-link"><?php echo esc_html( $product_title ); ?></span>
                            <?php endif; ?>
                            <?php if ( '' !== $variant_label ) : ?>
                                <span class="customer-reviews-grid__variant">- <?php echo esc_html( $variant_label ); ?></span>
                            <?php endif; ?>
                        </p>
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

                    <div class="customer-reviews-grid__media<?php echo empty( $media_urls ) ? ' is-empty' : ''; ?>">
                        <?php if ( ! empty( $media_urls ) ) : ?>
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
                        <?php endif; ?>
                    </div>

                    <footer class="customer-reviews-grid__helpful">
                        <span>Was this helpful?</span>
                        <button class="customer-reviews-grid__helpful-btn<?php echo $viewer_voted ? ' is-voted' : ''; ?>" type="button" aria-pressed="<?php echo $viewer_voted ? 'true' : 'false'; ?>" data-review-helpful <?php if ( $comment_id > 0 ) : ?>data-review-comment-id="<?php echo esc_attr( (string) $comment_id ); ?>"<?php else : ?>disabled<?php endif; ?>>
                            <i class="fa-regular fa-thumbs-up" aria-hidden="true"></i>
                            <span data-review-helpful-count<?php echo $helpful_count < 1 ? ' hidden' : ''; ?>><?php echo esc_html( (string) max( 0, $helpful_count ) ); ?></span>
                            <span><?php echo esc_html( '' !== $helpful_text ? $helpful_text : 'Helpful' ); ?></span>
                        </button>
                    </footer>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ( $has_reviews && $show_read_more && '' !== $read_more_text && $total_reviews > $initial_visible ) : ?>
        <div class="customer-reviews-grid__footer">
            <button
                class="customer-reviews-grid__read-more"
                type="button"
                data-read-more
                data-initial-visible="<?php echo esc_attr( (string) $initial_visible ); ?>"
                data-read-more-url="<?php echo esc_url( '' !== $read_more_url ? $read_more_url : '#' ); ?>"
                data-expand-label="<?php echo esc_attr( $read_more_text ); ?>"
                data-collapse-label="Collapse"
            >
                <span><?php echo esc_html( $read_more_text ); ?></span>
                <i class="fa-solid fa-angle-down customer-reviews-grid__read-more-icon" aria-hidden="true"></i>
            </button>
        </div>
    <?php endif; ?>

    <?php if ( $show_review_form ) : ?>
        <?php
        $product_id           = get_the_ID();
        $redirect_after_login = get_permalink( $product_id ) . '#reviews';
        $login_action_url     = wp_login_url();
        $register_url         = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_registration_url();
        ?>

        <?php if ( ! $reviewer_logged_in ) : ?>
            <div class="customer-reviews-grid__login-modal" data-review-login-modal hidden>
                <button class="customer-reviews-grid__modal-backdrop" type="button" data-review-login-close aria-label="<?php esc_attr_e( 'Close login modal', 'noyona-childtheme' ); ?>"></button>
                <div class="customer-reviews-grid__login-dialog" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Login to write a review', 'noyona-childtheme' ); ?>">
                    <button class="customer-reviews-grid__modal-close" type="button" data-review-login-close aria-label="<?php esc_attr_e( 'Close login modal', 'noyona-childtheme' ); ?>">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                    <div class="customer-reviews-grid__login-icon" aria-hidden="true">
                        <i class="fa-solid fa-lock"></i>
                    </div>
                    <h3 class="customer-reviews-grid__login-title"><?php esc_html_e( 'Log In to write a review', 'noyona-childtheme' ); ?></h3>
                    <p class="customer-reviews-grid__login-copy"><?php esc_html_e( 'Please log in to your account so we can verify your purchase and publish your review.', 'noyona-childtheme' ); ?></p>

                    <form class="customer-reviews-grid__login-form" method="post" action="<?php echo esc_url( $login_action_url ); ?>">
                        <label for="noyona-review-login-email"><?php esc_html_e( 'Email', 'noyona-childtheme' ); ?></label>
                        <input id="noyona-review-login-email" name="log" type="text" required placeholder="<?php esc_attr_e( 'your@email.com', 'noyona-childtheme' ); ?>" />

                        <label for="noyona-review-login-password"><?php esc_html_e( 'Password', 'noyona-childtheme' ); ?></label>
                        <input id="noyona-review-login-password" name="pwd" type="password" required placeholder="<?php esc_attr_e( 'Enter your password', 'noyona-childtheme' ); ?>" />

                        <input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_after_login ); ?>" />
                        <button type="submit" class="customer-reviews-grid__login-submit"><?php esc_html_e( 'Log In', 'noyona-childtheme' ); ?></button>
                    </form>

                    <div class="customer-reviews-grid__login-separator" aria-hidden="true">
                        <span></span><em><?php esc_html_e( 'or', 'noyona-childtheme' ); ?></em><span></span>
                    </div>

                    <a class="customer-reviews-grid__login-google" href="<?php echo esc_url( $login_action_url ); ?>">
                        <i class="fa-brands fa-google" aria-hidden="true"></i>
                        <span><?php esc_html_e( 'Login with Google', 'noyona-childtheme' ); ?></span>
                    </a>

                    <a class="customer-reviews-grid__login-register" href="<?php echo esc_url( $register_url ); ?>">
                        <?php esc_html_e( 'Create an Account', 'noyona-childtheme' ); ?>
                    </a>

                    <p class="customer-reviews-grid__login-note"><?php esc_html_e( 'You must be logged in to submit a review.', 'noyona-childtheme' ); ?></p>
                </div>
            </div>
        <?php else : ?>
            <div class="customer-reviews-grid__form-modal" data-review-form-modal hidden>
                <button class="customer-reviews-grid__modal-backdrop" type="button" data-review-form-close aria-label="<?php esc_attr_e( 'Close review form', 'noyona-childtheme' ); ?>"></button>
                <div class="customer-reviews-grid__form-dialog" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Write a review', 'noyona-childtheme' ); ?>">
                    <button class="customer-reviews-grid__modal-close" type="button" data-review-form-close aria-label="<?php esc_attr_e( 'Close review form', 'noyona-childtheme' ); ?>">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                    <div id="noyona-review-form" class="customer-reviews-grid__review-form-wrap">
                        <?php
                        if ( ! comments_open( $product_id ) ) :
                            ?>
                            <p class="customer-reviews-grid__review-form-note">
                                <?php esc_html_e( 'Reviews are closed for this product.', 'noyona-childtheme' ); ?>
                            </p>
                            <?php
                        else :
                            $current_user        = wp_get_current_user();
                            $reviewer_name       = trim( (string) $current_user->display_name );
                            $reviewer_email      = trim( (string) $current_user->user_email );
                            $reviewer_avatar_url = get_avatar_url( $current_user->ID, array( 'size' => 80 ) );
                            $posted_review_title = isset( $_POST['noyona_review_title'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['noyona_review_title'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
                            $posted_review_body  = isset( $_POST['comment'] ) ? sanitize_textarea_field( wp_unslash( (string) $_POST['comment'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

                            if ( '' === $reviewer_name ) {
                                $reviewer_name = __( 'Customer', 'noyona-childtheme' );
                            }

                            $reviewer_html = '<div class="customer-reviews-grid__reviewer">' .
                                '<span class="customer-reviews-grid__reviewer-avatar" aria-hidden="true">';
                            if ( '' !== $reviewer_avatar_url ) {
                                $reviewer_html .= '<img src="' . esc_url( $reviewer_avatar_url ) . '" alt="" loading="lazy" decoding="async" />';
                            } else {
                                $reviewer_html .= '<i class="fa-solid fa-circle-user"></i>';
                            }
                            $reviewer_html .= '</span>' .
                                '<span class="customer-reviews-grid__reviewer-meta">' .
                                '<strong class="customer-reviews-grid__reviewer-name">' . esc_html( $reviewer_name ) . '</strong>' .
                                '<span class="customer-reviews-grid__reviewer-email">' .
                                esc_html( sprintf( __( 'Posting as %s', 'noyona-childtheme' ), $reviewer_email ) ) .
                                '</span>' .
                                '</span>' .
                                '</div>';

                            $comment_form_args = array(
                                'title_reply'          => esc_html__( 'Write a Review', 'noyona-childtheme' ),
                                'title_reply_before'   => '<h3 class="customer-reviews-grid__review-form-title" id="noyona-review-form-title">',
                                'title_reply_after'    => '</h3>' . $reviewer_html,
                                'label_submit'         => esc_html__( 'Submit Review', 'noyona-childtheme' ),
                                'logged_in_as'         => '',
                                'comment_notes_before' => '',
                                'comment_notes_after'  => '',
                                'class_submit'         => 'customer-reviews-grid__submit',
                                'fields'               => array(),
                                'comment_field'        => '',
                            );

                            if ( function_exists( 'wc_review_ratings_enabled' ) && wc_review_ratings_enabled() ) {
                                $comment_form_args['comment_field'] .=
                                    '<p class="customer-reviews-grid__field customer-reviews-grid__field--rating"><label for="rating">' . esc_html__( 'Overall Rating', 'noyona-childtheme' ) . ' <span class="required">*</span></label>' .
                                    '<span class="customer-reviews-grid__rating-stars-input" data-review-rating-stars role="radiogroup" aria-label="' . esc_attr__( 'Overall Rating', 'noyona-childtheme' ) . '">' .
                                    '<button type="button" class="customer-reviews-grid__rating-star" data-rating-value="1" aria-label="' . esc_attr__( 'Rate 1 star', 'noyona-childtheme' ) . '"><i class="fa-solid fa-star" aria-hidden="true"></i></button>' .
                                    '<button type="button" class="customer-reviews-grid__rating-star" data-rating-value="2" aria-label="' . esc_attr__( 'Rate 2 stars', 'noyona-childtheme' ) . '"><i class="fa-solid fa-star" aria-hidden="true"></i></button>' .
                                    '<button type="button" class="customer-reviews-grid__rating-star" data-rating-value="3" aria-label="' . esc_attr__( 'Rate 3 stars', 'noyona-childtheme' ) . '"><i class="fa-solid fa-star" aria-hidden="true"></i></button>' .
                                    '<button type="button" class="customer-reviews-grid__rating-star" data-rating-value="4" aria-label="' . esc_attr__( 'Rate 4 stars', 'noyona-childtheme' ) . '"><i class="fa-solid fa-star" aria-hidden="true"></i></button>' .
                                    '<button type="button" class="customer-reviews-grid__rating-star" data-rating-value="5" aria-label="' . esc_attr__( 'Rate 5 stars', 'noyona-childtheme' ) . '"><i class="fa-solid fa-star" aria-hidden="true"></i></button>' .
                                    '</span>' .
                                    '<select name="rating" id="rating" required class="customer-reviews-grid__rating-select">' .
                                    '<option value="">' . esc_html__( 'Rate...', 'noyona-childtheme' ) . '</option>' .
                                    '<option value="5">' . esc_html__( 'Perfect', 'noyona-childtheme' ) . '</option>' .
                                    '<option value="4">' . esc_html__( 'Good', 'noyona-childtheme' ) . '</option>' .
                                    '<option value="3">' . esc_html__( 'Average', 'noyona-childtheme' ) . '</option>' .
                                    '<option value="2">' . esc_html__( 'Not that bad', 'noyona-childtheme' ) . '</option>' .
                                    '<option value="1">' . esc_html__( 'Very poor', 'noyona-childtheme' ) . '</option>' .
                                    '</select></p>';
                            }

                            $comment_form_args['comment_field'] .=
                                '<p class="comment-form-noyona-title customer-reviews-grid__field"><label for="noyona_review_title">' . esc_html__( 'Review Title', 'noyona-childtheme' ) . ' <span class="required">*</span></label>' .
                                '<input id="noyona_review_title" name="noyona_review_title" type="text" value="' . esc_attr( $posted_review_title ) . '" required placeholder="' . esc_attr__( 'Summarize your experience', 'noyona-childtheme' ) . '" /></p>' .
                                '<p class="comment-form-comment customer-reviews-grid__field"><label for="comment">' . esc_html__( 'Your Review', 'noyona-childtheme' ) . ' <span class="required">*</span></label>' .
                                '<textarea id="comment" name="comment" cols="45" rows="6" required placeholder="' . esc_attr__( 'Tell others about your experience with this product...', 'noyona-childtheme' ) . '">' . esc_textarea( $posted_review_body ) . '</textarea></p>';

                            $comment_form_args['comment_field'] .= wp_nonce_field( 'noyona_review_extras', 'noyona_review_extras_nonce', true, false );
                            $comment_form_args['comment_field'] .=
                                '<p class="comment-form-noyona-images customer-reviews-grid__field"><label for="noyona_review_images">' . esc_html__( 'Add Photos', 'noyona-childtheme' ) . ' <span class="customer-reviews-grid__optional">(' . esc_html__( 'optional', 'noyona-childtheme' ) . ')</span></label>' .
                                '<label class="customer-reviews-grid__upload-field" for="noyona_review_images">' .
                                '<i class="fa-solid fa-camera" aria-hidden="true"></i>' .
                                '<span data-review-upload-label>' . esc_html__( 'Upload photos of your purchase', 'noyona-childtheme' ) . '</span>' .
                                '</label>' .
                                '<input id="noyona_review_images" name="noyona_review_images[]" type="file" accept="image/*" multiple /></p>';
                            comment_form( apply_filters( 'woocommerce_product_review_comment_form_args', $comment_form_args ), $product_id );
                        endif;
                        ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
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
