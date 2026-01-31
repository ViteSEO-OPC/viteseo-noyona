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
$view_page_url = '';
$view_page_id = 0;
$template_candidates = array( 'page-blogs-view.html', 'page-blogs-view', 'templates/page-blogs-view.html' );
foreach ( $template_candidates as $template_candidate ) {
    $template_pages = get_posts(
        array(
            'post_type'      => 'page',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_key'       => '_wp_page_template',
            'meta_value'     => $template_candidate,
        )
    );
    if ( ! empty( $template_pages ) ) {
        $view_page_id = (int) $template_pages[0];
        break;
    }
}

if ( ! $view_page_id ) {
    $template_page = get_page_by_path( 'blogs-view' );
    if ( $template_page ) {
        $view_page_id = (int) $template_page->ID;
    }
}

if ( $view_page_id ) {
    $view_page_url = get_permalink( $view_page_id );
}
if ( ! $view_page_url ) {
    $view_page_url = home_url( '/blogs-view/' );
}

$cards = array();
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
        $excerpt = wp_trim_words( wp_strip_all_tags( get_the_excerpt() ), 26 );
        $author_id = (int) get_the_author_meta( 'ID' );
        $author_avatar = $author_id ? get_avatar_url( $author_id, array( 'size' => 48 ) ) : '';
        $post_permalink = get_permalink( $post_id );
        $read_more_url = $view_page_url
            ? add_query_arg( 'post_id', $post_id, $view_page_url )
            : $post_permalink;

        $cards[] = array(
            'tag'          => $tag,
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
            <?php if ( ! empty( $filters ) ) : ?>
                <div class="blog-list__filters" role="tablist">
                    <?php foreach ( $filters as $filter ) : ?>
                        <?php
                        $label = isset( $filter['label'] ) ? $filter['label'] : '';
                        $active = ! empty( $filter['active'] );
                        if ( '' === $label ) {
                            continue;
                        }
                        ?>
                        <button class="blog-filter<?php echo $active ? ' is-active' : ''; ?>" type="button" role="tab" aria-selected="<?php echo $active ? 'true' : 'false'; ?>">
                            <?php echo esc_html( $label ); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="blog-list__sort">
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
            </div>
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
                <article class="blog-card">
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
                            <a class="blog-card__save" href="<?php echo esc_url( $share_url ); ?>">
                                <i class="fa-solid fa-share-nodes"></i>
                                Share
                            </a>
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
</section>
