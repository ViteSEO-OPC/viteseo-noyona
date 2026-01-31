<?php
/**
 * Blog Slide Block Template.
 */

$defaults = array(
    'heading'     => 'Related Blogs',
    'cardsToShow' => 3,
    'items'       => array(),
);

$atts = wp_parse_args( $attributes, $defaults );
$items = is_array( $atts['items'] ) ? $atts['items'] : array();
$cards_to_show = max( 1, intval( $atts['cardsToShow'] ) );

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
        $author_id = (int) get_the_author_meta( 'ID' );
        $author_avatar = $author_id ? get_avatar_url( $author_id, array( 'size' => 48 ) ) : '';
        $post_permalink = get_permalink( $post_id );
        $read_more_url = $view_page_url
            ? add_query_arg( 'post_id', $post_id, $view_page_url )
            : $post_permalink;

        $cards[] = array(
            'tag'          => $tag,
            'title'        => get_the_title(),
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

if ( empty( $cards ) ) {
    if ( is_admin() ) {
        echo '<div class="blog-slide__placeholder">Add blog cards via the sidebar.</div>';
    }
    return;
}
?>
<section class="wp-block-noyona-blog-slide blog-slide alignwide" data-cards-to-show="<?php echo esc_attr( $cards_to_show ); ?>">
    <div class="blog-slide__header">
        <?php if ( ! empty( $atts['heading'] ) ) : ?>
            <h2 class="blog-slide__title"><?php echo esc_html( $atts['heading'] ); ?></h2>
        <?php endif; ?>
    </div>

    <div class="blog-slide__carousel">
        <button class="bs-nav-btn bs-prev" type="button" aria-label="Previous">
            <i class="fa-solid fa-chevron-left"></i>
        </button>

        <div class="blog-slide__track-wrap">
            <div class="blog-slide__track" style="--cards-visible: <?php echo esc_attr( $cards_to_show ); ?>;">
                <?php foreach ( $cards as $item ) : ?>
                    <?php
                    $tag = isset( $item['tag'] ) ? $item['tag'] : '';
                    $title = isset( $item['title'] ) ? $item['title'] : '';
                    $image = isset( $item['image'] ) ? $item['image'] : '';
                    $date_day = isset( $item['dateDay'] ) ? $item['dateDay'] : '';
                    $date_label = isset( $item['dateLabel'] ) ? $item['dateLabel'] : '';
                    $author = isset( $item['author'] ) ? $item['author'] : '';
                    $author_avatar = isset( $item['authorAvatar'] ) ? $item['authorAvatar'] : '';
                    $share_url = isset( $item['shareUrl'] ) ? $item['shareUrl'] : '#';
                    $read_more_url = isset( $item['readMoreUrl'] ) ? $item['readMoreUrl'] : '#';
                    ?>
                    <article class="blog-slide__card">
                        <div class="blog-slide__card-inner">
                            <div class="blog-slide__media">
                                <?php if ( ! empty( $image ) ) : ?>
                                    <img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $title ); ?>" />
                                <?php endif; ?>
                                <?php if ( ! empty( $date_label ) || ! empty( $date_day ) ) : ?>
                                    <span class="blog-slide__date">
                                        <?php if ( ! empty( $date_day ) ) : ?>
                                            <span class="blog-slide__date-day"><?php echo esc_html( $date_day ); ?></span>
                                        <?php endif; ?>
                                        <?php if ( ! empty( $date_label ) ) : ?>
                                            <span class="blog-slide__date-label"><?php echo esc_html( $date_label ); ?></span>
                                        <?php endif; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="blog-slide__body">
                                <?php if ( ! empty( $tag ) ) : ?>
                                    <span class="blog-slide__tag"><?php echo esc_html( $tag ); ?></span>
                                <?php endif; ?>
                                <?php if ( ! empty( $title ) ) : ?>
                                    <h3 class="blog-slide__card-title"><?php echo esc_html( $title ); ?></h3>
                                <?php endif; ?>
                                <?php if ( ! empty( $author ) ) : ?>
                                    <div class="blog-slide__author">
                                        <?php if ( ! empty( $author_avatar ) ) : ?>
                                            <img src="<?php echo esc_url( $author_avatar ); ?>" alt="<?php echo esc_attr( $author ); ?>" />
                                        <?php else : ?>
                                            <span class="blog-slide__author-dot"></span>
                                        <?php endif; ?>
                                        <span><?php echo esc_html( $author ); ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="blog-slide__actions">
                                    <a class="blog-slide__read" href="<?php echo esc_url( $read_more_url ); ?>">Read More</a>
                                    <a class="blog-slide__share" href="<?php echo esc_url( $share_url ); ?>" aria-label="Share">
                                        <i class="fa-solid fa-share-nodes"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>

        <button class="bs-nav-btn bs-next" type="button" aria-label="Next">
            <i class="fa-solid fa-chevron-right"></i>
        </button>
    </div>

    <div class="blog-slide__dots"></div>
</section>
