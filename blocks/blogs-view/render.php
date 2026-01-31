<?php
/**
 * Blogs View Block Template.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$post_id = 0;
$requested_post_id = 0;
if ( isset( $_GET['post_id'] ) ) {
    $requested_post_id = absint( wp_unslash( $_GET['post_id'] ) );
}

if ( $requested_post_id ) {
    $requested_post = get_post( $requested_post_id );
    if ( $requested_post && 'post' === $requested_post->post_type ) {
        $status = get_post_status( $requested_post );
        if ( 'publish' === $status || ( 'private' === $status && current_user_can( 'read_post', $requested_post_id ) ) ) {
            $post_id = $requested_post_id;
        }
    }
}

if ( ! $post_id ) {
    $post_id = get_the_ID();
    if ( ! $post_id ) {
        $post_id = get_queried_object_id();
    }
}
if ( ! $post_id ) {
    return;
}

$current_post = get_post( $post_id );
if ( ! $current_post ) {
    return;
}

$title = get_the_title( $post_id );
$excerpt = has_excerpt( $post_id )
    ? get_the_excerpt( $post_id )
    : wp_trim_words( wp_strip_all_tags( $current_post->post_content ), 34 );
$header_excerpt = $excerpt;

$featured_id = get_post_thumbnail_id( $post_id );
$featured_caption = $featured_id ? wp_get_attachment_caption( $featured_id ) : '';
$featured_image = $featured_id
    ? wp_get_attachment_image( $featured_id, 'large', false, array( 'class' => 'blogs-view__hero-image' ) )
    : '';

$author_id = (int) $current_post->post_author;
$author_name = $author_id ? get_the_author_meta( 'display_name', $author_id ) : '';
$author_avatar = $author_id ? get_avatar_url( $author_id, array( 'size' => 48 ) ) : '';
$date = get_the_date( 'F j, Y', $post_id );

$home_url = home_url( '/' );
$posts_page_id = (int) get_option( 'page_for_posts' );
$blogs_url = $posts_page_id ? get_permalink( $posts_page_id ) : home_url( '/blogs/' );
$blogs_label = 'Blog';

$category_label = '';
$categories = get_the_category( $post_id );
if ( ! empty( $categories ) ) {
    $category_label = $categories[0]->name;
}

$content = '';
global $post;
$original_post = $post;
$post = $current_post;
setup_postdata( $post );
$content = apply_filters( 'the_content', $current_post->post_content );
wp_reset_postdata();
$post = $original_post;

$author_summary = '';
$toc_items = array();
if ( $content ) {
    $paragraph_removed = false;
    $content = preg_replace_callback(
        '/<p[^>]*>.*?<\/p>/is',
        function( $matches ) use ( &$author_summary, &$paragraph_removed ) {
            if ( $paragraph_removed ) {
                return $matches[0];
            }

            $summary_text = trim( wp_strip_all_tags( $matches[0] ) );
            if ( '' === $summary_text ) {
                return $matches[0];
            }

            $author_summary = html_entity_decode( $summary_text, ENT_QUOTES, get_bloginfo( 'charset' ) );
            $paragraph_removed = true;
            return '';
        },
        $content
    );

    $used_ids = array();
    $content = preg_replace_callback(
        '/<h([2-3])([^>]*)>(.*?)<\/h\1>/is',
        function( $matches ) use ( &$toc_items, &$used_ids ) {
            $level = (int) $matches[1];
            $attrs = $matches[2];
            $inner = $matches[3];
            $text = trim( wp_strip_all_tags( $inner ) );
            if ( '' === $text ) {
                return $matches[0];
            }

            $id = '';
            if ( preg_match( '/\sid=["\']([^"\']+)["\']/i', $attrs, $id_matches ) ) {
                $id = $id_matches[1];
            }

            if ( '' === $id ) {
                $base = sanitize_title( $text );
                if ( '' === $base ) {
                    $base = 'section';
                }
                $id = $base;
                $suffix = 2;
                while ( in_array( $id, $used_ids, true ) ) {
                    $id = $base . '-' . $suffix;
                    $suffix++;
                }
                if ( '' === $attrs ) {
                    $attrs = ' id="' . esc_attr( $id ) . '"';
                } else {
                    $attrs .= ' id="' . esc_attr( $id ) . '"';
                }
            } elseif ( in_array( $id, $used_ids, true ) ) {
                $base = $id;
                $suffix = 2;
                $new_id = $base;
                while ( in_array( $new_id, $used_ids, true ) ) {
                    $new_id = $base . '-' . $suffix;
                    $suffix++;
                }
                $attrs = preg_replace( '/\sid=["\']([^"\']+)["\']/i', ' id="' . esc_attr( $new_id ) . '"', $attrs, 1 );
                $id = $new_id;
            }

            $used_ids[] = $id;
            if ( 2 === $level ) {
                $toc_items[] = array(
                    'id'    => $id,
                    'text'  => $text,
                    'level' => $level,
                );
            }

            return '<h' . $level . $attrs . '>' . $inner . '</h' . $level . '>';
        },
        $content
    );
}

if ( empty( $title ) && empty( $content ) ) {
    if ( is_admin() ) {
        echo '<div class="blogs-view__placeholder">Select a post or add content to preview.</div>';
    }
    return;
}
?>
<section class="wp-block-noyona-blogs-view blogs-view alignfull" id="blogs-view-top">
    <div class="blogs-view__inner">
        <nav class="blogs-view__breadcrumbs" aria-label="Breadcrumb">
            <a href="<?php echo esc_url( $home_url ); ?>">Home</a>
            <span class="blogs-view__crumb-sep">&gt;</span>
            <a href="/blogs"><?php echo esc_html( $blogs_label ); ?></a>
            <span class="blogs-view__crumb-sep">&gt;</span>
            <span class="blogs-view__crumb-current"><?php echo esc_html( $title ); ?></span>
        </nav>

        <div class="blogs-view__layout">
            <article class="blogs-view__article">
                <header class="blogs-view__header">
                    <?php if ( ! empty( $title ) ) : ?>
                        <h1 class="blogs-view__title"><?php echo esc_html( $title ); ?></h1>
                    <?php endif; ?>
                    <?php if ( ! empty( $header_excerpt ) ) : ?>
                        <p class="blogs-view__excerpt"><?php echo esc_html( $header_excerpt ); ?></p>
                    <?php endif; ?>
                </header>

                <?php if ( $featured_image ) : ?>
                    <figure class="blogs-view__hero">
                        <?php echo $featured_image; ?>
                    </figure>
                    <?php if ( ! empty( $featured_caption ) ) : ?>
                        <p class="blogs-view__caption"><?php echo esc_html( $featured_caption ); ?></p>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="blogs-view__meta">
                    <div class="blogs-view__author">
                        <?php if ( ! empty( $author_avatar ) ) : ?>
                            <img src="<?php echo esc_url( $author_avatar ); ?>" alt="<?php echo esc_attr( $author_name ); ?>" />
                        <?php else : ?>
                            <span class="blogs-view__author-dot"></span>
                        <?php endif; ?>
                        <div class="blogs-view__author-info">
                            <span class="blogs-view__author-name"><?php echo esc_html( $author_name ); ?></span>
                            <?php if ( ! empty( $date ) ) : ?>
                                <span class="blogs-view__date"><?php echo esc_html( $date ); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ( ! empty( $author_summary ) ) : ?>
                        <p class="blogs-view__author-summary wp-block-paragraph"><?php echo esc_html( $author_summary ); ?></p>
                    <?php endif; ?>
                </div>

                <div class="blogs-view__content">
                    <?php echo $content; ?>
                </div>
            </article>

            <aside class="blogs-view__toc" aria-label="Table of contents">
                <div class="blogs-view__toc-card">
                    <span class="blogs-view__toc-title">Table of Contents</span>
                    <?php if ( ! empty( $toc_items ) ) : ?>
                        <ul class="blogs-view__toc-list">
                            <?php foreach ( $toc_items as $item ) : ?>
                                <?php $is_sub = ( 3 === (int) $item['level'] ); ?>
                                <li class="blogs-view__toc-item<?php echo $is_sub ? ' is-sub' : ''; ?>">
                                    <u>
                                        <a class="blogs-view__toc-link" href="#<?php echo esc_attr( $item['id'] ); ?>"><?php echo esc_html( $item['text'] ); ?></a>
                                    </u>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <p class="blogs-view__toc-empty">Add headings to build a table of contents.</p>
                    <?php endif; ?>
                </div>

                <div class="blogs-view__toc-actions">
                    <a class="blogs-view__pill blogs-view__pill--share" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
                        <i class="fa-solid fa-share-nodes" aria-hidden="true"></i>
                        Share
                    </a>
                    <a class="blogs-view__pill blogs-view__pill--icon" href="#blogs-view-top" aria-label="Back to top">
                        <i class="fa-solid fa-arrow-up" aria-hidden="true"></i>
                    </a>
                </div>
            </aside>
        </div>
    </div>
</section>
