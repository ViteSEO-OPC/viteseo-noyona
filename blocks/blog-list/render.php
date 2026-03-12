<?php
/**
 * Blog List Block Template.
 */

$defaults = array(
    'heading'     => 'Your Daily Glow with Noyona',
    'subheading'  => 'Find your perfect routine with our curated guides. Boost your confidence and glow with beauty rooted in nature.',
    'filters'     => array(),
    'sortLabel'   => 'Sort by:',
    'sortOptions' => array( 'Newest', 'Popular', 'Trending' ),
    'items'       => array(),
);

$atts = wp_parse_args( $attributes, $defaults );
$filters = is_array( $atts['filters'] ) ? $atts['filters'] : array();
$items = is_array( $atts['items'] ) ? $atts['items'] : array();
$sort_options = is_array( $atts['sortOptions'] ) ? $atts['sortOptions'] : array();

$cards = array();
$category_counts = array();
$category_labels = array();
$posts_query = new WP_Query(
    array(
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'posts_per_page'      => (int) get_option( 'posts_per_page' ),
        'ignore_sticky_posts' => true,
    )
);

if ( $posts_query->have_posts() ) {
    while ( $posts_query->have_posts() ) {
        $posts_query->the_post();

        $post_id = get_the_ID();
        $categories = get_the_category( $post_id );
        $tag = ! empty( $categories ) ? $categories[0]->name : '';
        $category_slugs = array();
        if ( ! empty( $categories ) ) {
            foreach ( $categories as $cat ) {
                if ( empty( $cat->slug ) ) {
                    continue;
                }
                $category_slugs[] = (string) $cat->slug;
                $category_counts[ $cat->slug ] = isset( $category_counts[ $cat->slug ] ) ? (int) $category_counts[ $cat->slug ] + 1 : 1;
                if ( ! isset( $category_labels[ $cat->slug ] ) ) {
                    $category_labels[ $cat->slug ] = (string) $cat->name;
                }
            }
        }
        $excerpt = wp_trim_words( wp_strip_all_tags( get_the_excerpt() ), 26 );
        $author_id = (int) get_the_author_meta( 'ID' );
        $author_avatar = $author_id ? get_avatar_url( $author_id, array( 'size' => 48 ) ) : '';
        $post_permalink = get_permalink( $post_id );
        $read_more_url = $post_permalink;

        $cards[] = array(
            'tag'          => $tag,
            'categorySlugs'=> $category_slugs,
            'title'        => get_the_title(),
            'excerpt'      => $excerpt,
            'image'        => get_the_post_thumbnail_url( $post_id, 'large' ),
            'dateDay'      => get_the_date( 'j', $post_id ),
            'dateLabel'    => get_the_date( 'M Y', $post_id ),
            'author'       => get_the_author_meta( 'display_name' ),
            'authorAvatar' => $author_avatar,
            'shareUrl'     => $post_permalink,
            'readMoreUrl'  => $read_more_url,
        );
    }
}
wp_reset_postdata();

if ( empty( $cards ) && ! empty( $items ) ) {
    $cards = $items;
}
$page_sizes = array();
if ( isset( $atts['pageSizes'] ) && is_array( $atts['pageSizes'] ) ) {
    foreach ( $atts['pageSizes'] as $size ) {
        $size = intval( $size );
        if ( $size > 0 ) {
            $page_sizes[] = $size;
        }
    }
}
$page_sizes_attr = '';
if ( ! empty( $page_sizes ) ) {
    $page_sizes_attr = ' data-page-sizes="' . esc_attr( implode( ',', $page_sizes ) ) . '"';
}

if ( empty( $cards ) ) {
    if ( is_admin() ) {
        echo '<div class="blog-list__placeholder">Add blog cards via the sidebar.</div>';
    }
    return;
}

// Build filter options:
// - Prefer dynamic categories from queried posts (WP admin)
// - Fall back to configured block filters when no WP posts are available
$filter_options = array();
$active_filter_value = '';

// Dynamic categories (sorted by count desc, then label)
if ( ! empty( $category_counts ) ) {
    $terms = array_keys( $category_counts );
    usort(
        $terms,
        function ( $a, $b ) use ( $category_counts, $category_labels ) {
            $ca = isset( $category_counts[ $a ] ) ? (int) $category_counts[ $a ] : 0;
            $cb = isset( $category_counts[ $b ] ) ? (int) $category_counts[ $b ] : 0;
            if ( $ca === $cb ) {
                $la = isset( $category_labels[ $a ] ) ? (string) $category_labels[ $a ] : (string) $a;
                $lb = isset( $category_labels[ $b ] ) ? (string) $category_labels[ $b ] : (string) $b;
                return strcasecmp( $la, $lb );
            }
            return $cb <=> $ca;
        }
    );

    foreach ( $terms as $slug ) {
        $label = isset( $category_labels[ $slug ] ) ? (string) $category_labels[ $slug ] : (string) $slug;
        $filter_options[] = array(
            'label' => $label,
            'value' => (string) $slug,
        );
    }

    if ( ! empty( $terms ) ) {
        $active_filter_value = (string) $terms[0];
    }
} elseif ( ! empty( $filters ) ) {
    foreach ( $filters as $filter ) {
        $label = isset( $filter['label'] ) ? (string) $filter['label'] : '';
        $active = ! empty( $filter['active'] );
        if ( '' === trim( $label ) ) {
            continue;
        }
        $value = sanitize_title( $label );
        $filter_options[] = array(
            'label' => $label,
            'value' => $value,
        );
        if ( $active && '' === $active_filter_value ) {
            $active_filter_value = $value;
        }
    }
}
?>
<section class="wp-block-noyona-blog-list blog-list alignfull"<?php echo $page_sizes_attr; ?>>
    <div class="blog-list__inner">
        <header class="blog-list__header">
            <?php if ( ! empty( $atts['heading'] ) ) : ?>
                <h2 class="blog-list__title"><?php echo esc_html( $atts['heading'] ); ?></h2>
            <?php endif; ?>
            <?php if ( ! empty( $atts['subheading'] ) ) : ?>
                <p class="blog-list__subtitle"><?php echo esc_html( $atts['subheading'] ); ?></p>
            <?php endif; ?>
        </header>

        <div class="blog-list__controls">
            <?php if ( ! empty( $filter_options ) ) : ?>
                <div class="blog-list__filters" role="tablist">
                    <?php foreach ( $filter_options as $opt_index => $opt ) : ?>
                        <?php
                        $label = isset( $opt['label'] ) ? (string) $opt['label'] : '';
                        $value = isset( $opt['value'] ) ? (string) $opt['value'] : '';
                        $active = ( '' !== trim( $active_filter_value ) )
                            ? ( $value === $active_filter_value )
                            : ( 0 === $opt_index );
                        if ( '' === trim( $label ) || '' === trim( $value ) ) {
                            continue;
                        }
                        ?>
                        <button class="blog-filter<?php echo $active ? ' is-active' : ''; ?>" type="button" role="tab"
                            data-blog-filter="<?php echo esc_attr( $value ); ?>"
                            aria-selected="<?php echo $active ? 'true' : 'false'; ?>">
                            <?php echo esc_html( $label ); ?>
                        </button>
                    <?php endforeach; ?> 
                </div>

                <label class="blog-list__filter-select-wrap" aria-label="Filter posts by category">
                    <select class="blog-list__filter-select">
                        <?php foreach ( $filter_options as $opt_index => $opt ) : ?>
                            <?php
                            $label = isset( $opt['label'] ) ? (string) $opt['label'] : '';
                            $value = isset( $opt['value'] ) ? (string) $opt['value'] : '';
                            $active = ( '' !== trim( $active_filter_value ) )
                                ? ( $value === $active_filter_value )
                                : ( 0 === $opt_index );
                            if ( '' === trim( $label ) || '' === trim( $value ) ) {
                                continue;
                            }
                            ?>
                            <option value="<?php echo esc_attr( $value ); ?>"<?php echo $active ? ' selected' : ''; ?>>
                                <?php echo esc_html( $label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            <?php endif; ?>

            <!-- <div class="blog-list__sort">
                <?php if ( ! empty( $atts['sortLabel'] ) ) : ?>
                    <span class="blog-list__sort-label"><?php echo esc_html( $atts['sortLabel'] ); ?></span>
                <?php endif; ?>
                <?php if ( ! empty( $sort_options ) ) : ?>
                    <select class="blog-list__sort-select" aria-label="Sort blog posts">
                        <?php foreach ( $sort_options as $option ) : ?>
                            <option value="<?php echo esc_attr( $option ); ?>"><?php echo esc_html( $option ); ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div> -->
        </div>

        <div class="blog-list__cards">
            <?php foreach ( $cards as $item ) : ?>
                <?php
                $tag = isset( $item['tag'] ) ? $item['tag'] : '';
                $title = isset( $item['title'] ) ? $item['title'] : '';
                $excerpt = isset( $item['excerpt'] ) ? $item['excerpt'] : '';
                $image = isset( $item['image'] ) ? $item['image'] : '';
                $date_day = isset( $item['dateDay'] ) ? $item['dateDay'] : '';
                $date_label = isset( $item['dateLabel'] ) ? $item['dateLabel'] : '';
                $author = isset( $item['author'] ) ? $item['author'] : '';
                $author_avatar = isset( $item['authorAvatar'] ) ? $item['authorAvatar'] : '';
                $share_url = isset( $item['shareUrl'] ) ? $item['shareUrl'] : '#';
                $read_more_url = isset( $item['readMoreUrl'] ) ? $item['readMoreUrl'] : '#';
                ?>
                <?php
                $cat_slugs = isset( $item['categorySlugs'] ) && is_array( $item['categorySlugs'] ) ? $item['categorySlugs'] : array();
                if ( empty( $cat_slugs ) && ! empty( $tag ) ) {
                    $cat_slugs = array( sanitize_title( (string) $tag ) );
                }
                $cats_attr = esc_attr( implode( ' ', array_map( 'sanitize_title', $cat_slugs ) ) );
                ?>
                <article class="blog-card" data-blog-cats="<?php echo $cats_attr; ?>">
                    <a class="blog-card__overlay-link" href="<?php echo esc_url( $read_more_url ); ?>" aria-label="Read <?php echo esc_attr( $title ); ?>"></a>
                    <div class="blog-card__media">
                        <?php if ( ! empty( $image ) ) : ?>
                            <img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $title ); ?>" />
                        <?php endif; ?>
                        <?php if ( ! empty( $date_label ) || ! empty( $date_day ) ) : ?>
                            <span class="blog-card__date">
                                <?php if ( ! empty( $date_day ) ) : ?>
                                    <span class="blog-card__date-day"><?php echo esc_html( $date_day ); ?></span>
                                <?php endif; ?>
                                <?php if ( ! empty( $date_label ) ) : ?>
                                    <span class="blog-card__date-label"><?php echo esc_html( $date_label ); ?></span>
                                <?php endif; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="blog-card__content">
                        <div class="blog-card__meta">
                            <?php if ( ! empty( $tag ) ) : ?>
                                <span class="blog-card__tag"><?php echo esc_html( $tag ); ?></span>
                            <?php endif; ?>
                            <button class="blog-card__save" type="button" data-share-url="<?php echo esc_url( $share_url ); ?>" aria-haspopup="dialog">
                                <i class="fa-solid fa-share-nodes"></i>
                                Share
                            </button>
                        </div>
                        <?php if ( ! empty( $title ) ) : ?>
                            <h3 class="blog-card__title"><?php echo esc_html( $title ); ?></h3>
                        <?php endif; ?>
                        <?php if ( ! empty( $excerpt ) ) : ?>
                            <p class="blog-card__excerpt"><?php echo esc_html( $excerpt ); ?></p>
                        <?php endif; ?>
                        <div class="blog-card__footer">
                            <div class="blog-card__author">
                                <?php if ( ! empty( $author_avatar ) ) : ?>
                                    <img src="<?php echo esc_url( $author_avatar ); ?>" alt="<?php echo esc_attr( $author ); ?>" />
                                <?php else : ?>
                                    <span class="blog-card__author-dot"></span>
                                <?php endif; ?>
                                <span><?php echo esc_html( $author ); ?></span>
                            </div>
                            <a class="blog-card__read" href="<?php echo esc_url( $read_more_url ); ?>">Read More</a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="blog-list__pager">
            <button class="blog-list__nav" type="button" aria-label="Previous">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
            <div class="blog-list__dots" role="presentation"></div>
            <button class="blog-list__nav" type="button" aria-label="Next">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>
    </div>

    <div class="blog-share-modal" hidden>
        <div class="blog-share-modal__backdrop" data-share-close></div>
        <div class="blog-share-modal__dialog" role="dialog" aria-modal="true" aria-label="Share this post">
            <button class="blog-share-modal__close" type="button" aria-label="Close share dialog" data-share-close>
                <i class="fa-solid fa-xmark"></i>
            </button>
            <h3 class="blog-share-modal__title">Share</h3>
            <div class="blog-share-modal__grid">
                <a class="blog-share-modal__item" data-share-platform="facebook" target="_blank" rel="noopener noreferrer">
                    <i class="fa-brands fa-facebook-f"></i>
                    <span>Facebook</span>
                </a>
                <button class="blog-share-modal__item" type="button" data-share-platform="instagram">
                    <i class="fa-brands fa-instagram"></i>
                    <span>Instagram</span>
                </button>
                <a class="blog-share-modal__item" data-share-platform="x" target="_blank" rel="noopener noreferrer">
                    <i class="fa-brands fa-x-twitter"></i>
                    <span>X</span>
                </a>
                <a class="blog-share-modal__item" data-share-platform="linkedin" target="_blank" rel="noopener noreferrer">
                    <i class="fa-brands fa-linkedin-in"></i>
                    <span>LinkedIn</span>
                </a>
                <button class="blog-share-modal__item" type="button" data-share-platform="copy">
                    <i class="fa-regular fa-copy"></i>
                    <span>Copy link</span>
                </button>
            </div>
            <p class="blog-share-modal__hint" aria-live="polite"></p>
        </div>
    </div>
</section>
