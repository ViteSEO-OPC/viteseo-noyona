<?php
/**
 * Awards Strip block template.
 *
 * Renders an auto-scrolling marquee carousel of award / badge logos.
 *
 * Data source (in priority order):
 *   1. An explicit `items` block attribute (legacy / manual override).
 *   2. The "Awards" custom post type — manage these from wp-admin → Awards
 *      (Featured Image = logo, title = optional caption, Order = sequence).
 *   3. The bundled default logos in assets/images/logo-awards/ (fallback so the
 *      strip is never empty before any Awards have been created).
 *
 * The track is duplicated so the CSS keyframe loop scrolls seamlessly.
 */

$image_base = trailingslashit( get_stylesheet_directory_uri() ) . 'assets/images/logo-awards/';

// Bundled fallback logos, used only when neither block items nor Awards exist.
$default_items = array(
    array( 'icon' => $image_base . 'award-1.webp', 'title' => '' ),
    array( 'icon' => $image_base . 'award-2.webp', 'title' => '' ),
    array( 'icon' => $image_base . 'award-3.webp', 'title' => '' ),
    array( 'icon' => $image_base . 'award-4.webp', 'title' => '' ),
    array( 'icon' => $image_base . 'award-5.webp', 'title' => '' ),
    array( 'icon' => $image_base . 'award-6.webp', 'title' => '' ),
    array( 'icon' => $image_base . 'award-7.webp', 'title' => '' ),
);

$defaults = array(
    'backgroundColor' => '',
    'items'           => array(),
    'speed'           => 40,
);

$atts = wp_parse_args( $attributes, $defaults );

/**
 * Resolve the items to render.
 */
$items = ( isset( $atts['items'] ) && is_array( $atts['items'] ) && ! empty( $atts['items'] ) )
    ? $atts['items']
    : array();

// Pull from the Awards CPT when no explicit items were passed.
if ( empty( $items ) ) {
    $awards = get_posts( array(
        'post_type'      => 'noyona_award',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'ASC' ),
        'no_found_rows'  => true,
    ) );

    foreach ( $awards as $award ) {
        $thumb = get_the_post_thumbnail_url( $award->ID, 'medium' );
        if ( ! $thumb ) {
            continue; // An award with no logo has nothing to show.
        }
        $items[] = array(
            'icon'  => (string) $thumb,
            'title' => get_the_title( $award->ID ),
        );
    }
}

// Final fallback: bundled logos.
if ( empty( $items ) ) {
    $items = $default_items;
}

// Only emit the inline override when a valid color is supplied; otherwise the
// stylesheet falls back to the shared pink-soft background.
$bg          = sanitize_hex_color( $atts['backgroundColor'] );
$speed       = max( 10, (int) $atts['speed'] );
$style_props = array( '--awards-strip-speed: ' . $speed . 's;' );
if ( $bg ) {
    $style_props[] = '--awards-strip-bg: ' . esc_attr( $bg ) . ';';
}
$style_attr = implode( ' ', $style_props );

if ( empty( $items ) ) {
    if ( is_admin() ) {
        echo '<div class="awards-strip-placeholder">Add awards via wp-admin &rarr; Awards.</div>';
    }
    return;
}

/**
 * Normalise items into a clean list of [icon, title] up front, so the marquee
 * duplication loop below stays simple. Attachment IDs / URLs are resolved to a
 * usable medium-size image URL.
 */
$resolved = array();
foreach ( $items as $index => $item ) {
    $icon    = isset( $item['icon'] ) ? (string) $item['icon'] : '';
    $icon_id = isset( $item['iconId'] ) ? absint( $item['iconId'] ) : 0;

    if ( $icon_id ) {
        $maybe = wp_get_attachment_image_url( $icon_id, 'medium' );
        if ( $maybe ) {
            $icon = (string) $maybe;
        }
    } elseif ( ! empty( $icon ) ) {
        $maybe_id = attachment_url_to_postid( $icon );
        if ( $maybe_id ) {
            $maybe = wp_get_attachment_image_url( (int) $maybe_id, 'medium' );
            if ( $maybe ) {
                $icon = (string) $maybe;
            }
        }
    }

    if ( empty( $icon ) ) {
        continue;
    }

    $title = isset( $item['title'] ) ? (string) $item['title'] : '';
    $resolved[] = array(
        'icon'  => $icon,
        'title' => $title,
        'alt'   => '' !== $title ? $title : sprintf( 'Award %d', (int) $index + 1 ),
    );
}

if ( empty( $resolved ) ) {
    return;
}

$original_count = count( $resolved );

// Build one "copy" wide enough to fill typical screens even when there are only
// a few awards, then render that copy twice. The keyframe translates the track
// by -50% (exactly one copy), so two identical copies loop seamlessly.
$min_per_copy = 8;
$repeat       = max( 1, (int) ceil( $min_per_copy / $original_count ) );
$one_copy     = array();
for ( $r = 0; $r < $repeat; $r++ ) {
    $one_copy = array_merge( $one_copy, $resolved );
}
$track_items = array_merge( $one_copy, $one_copy );
?>
<section class="wp-block-noyona-awards-strip awards-strip alignfull" style="<?php echo esc_attr( $style_attr ); ?>">
    <div class="awards-strip__inner">
        <h2 class="awards-strip__heading">AWARDS</h2>
        <div class="awards-strip__viewport">
            <div class="awards-strip__track" role="list">
            <?php foreach ( $track_items as $i => $item ) : ?>
                <?php
                // The second copy is purely decorative (for the loop); hide it
                // from assistive tech to avoid duplicate announcements.
                $is_clone = $i >= $original_count;
                ?>
                <div class="awards-strip__item"<?php echo $is_clone ? ' aria-hidden="true"' : ' role="listitem"'; ?>>
                    <img src="<?php echo esc_url( $item['icon'] ); ?>" alt="<?php echo $is_clone ? '' : esc_attr( $item['alt'] ); ?>" loading="lazy" decoding="async" />
                    <?php if ( '' !== $item['title'] ) : ?>
                        <span class="awards-strip__title"><?php echo esc_html( $item['title'] ); ?></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
