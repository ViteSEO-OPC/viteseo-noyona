<?php
/**
 * 404 fallback template for the child theme.
 */

status_header( 404 );
nocache_headers();

$block_template_path = get_stylesheet_directory() . '/templates/404.html';

if ( file_exists( $block_template_path ) ) {
    $template_content = file_get_contents( $block_template_path );

    if ( false !== $template_content ) {
        global $_wp_current_template_content, $_wp_current_template_id;

        $_wp_current_template_content = $template_content;
        $_wp_current_template_id      = get_stylesheet() . '//404';

        add_action( 'wp_head', '_block_template_viewport_meta_tag', 0 );
        remove_action( 'wp_head', '_wp_render_title_tag', 1 );
        add_action( 'wp_head', '_block_template_render_title_tag', 1 );

        require ABSPATH . WPINC . '/template-canvas.php';
        exit;
    }
}

get_header();
?>
<main class="wp-block-group alignfull">
    <section class="noyona-404-fallback">
        <h1>404 Not Found</h1>
        <p>This page could not be found.</p>
    </section>
</main>
<?php
get_footer();
