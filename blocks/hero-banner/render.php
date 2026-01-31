<?php
/**
 * Hero Banner block render.
 *
 * Provides a left-text, right-image hero layout with a gentle gradient overlay.
 *
 * @param array $attributes Block attributes.
 */

$defaults = array(
    'eyebrow'         => '',
    'titleLine'       => 'Radiate Natural,',
    'body'            => 'Noyona celebrates beauty that feels like you elevated by nature, crafted for everyday wear, and made to last. Discover effortless formulas that care for skin while enhancing your glow.',
    'searchEnabled'   => false,
    'searchPlaceholder' => 'Search articles',
    'searchPostType'  => '',
    'buttonText'      => 'Discover Your Glow',
    'buttonUrl'       => '/shop/',
    'backgroundImage' => '',
    'backgroundSize'  => 'cover',
    'backgroundPosition' => 'center',
);

$atts = wp_parse_args( $attributes, $defaults );
if ( empty( $atts['titleLine'] ) && ( ! empty( $atts['titleLine1'] ) || ! empty( $atts['titleLine2'] ) ) ) {
    $atts['titleLine'] = trim( $atts['titleLine1'] . ' ' . $atts['titleLine2'] );
}

$bg_image = $atts['backgroundImage'];

if ( empty( $bg_image ) ) {
    $bg_image = get_stylesheet_directory_uri() . '/assets/images/makeup.jpg';
}

    $bg_size = isset( $atts['backgroundSize'] ) ? $atts['backgroundSize'] : 'cover';
    $bg_position = isset( $atts['backgroundPosition'] ) ? $atts['backgroundPosition'] : 'center';
?>
<section
    class="wp-block-noyona-hero-banner hero-banner alignfull"
    style="--hero-banner-bg: url('<?php echo esc_url( $bg_image ); ?>'); --hero-banner-bg-size: <?php echo esc_attr( $bg_size ); ?>; --hero-banner-bg-position: <?php echo esc_attr( $bg_position ); ?>;"
>
    <div class="hero-banner__inner">
        <div class="hero-banner__content">
            <?php if ( ! empty( $atts['eyebrow'] ) ) : ?>
                <p class="hero-banner__eyebrow"><?php echo esc_html( $atts['eyebrow'] ); ?></p>
            <?php endif; ?>

            <h1 class="hero-banner__title">
                <span class="hero-banner__title-line">
                    <?php
                    echo wp_kses(
                        $atts['titleLine'],
                        array(
                            'span' => array(
                                'class' => array(),
                            ),
                            'em'   => array(),
                            'strong' => array(),
                        )
                    );
                    ?>
                </span>
            </h1>

            <p class="hero-banner__body">
                <?php echo esc_html( $atts['body'] ); ?>
            </p>

            <?php if ( ! empty( $atts['searchEnabled'] ) ) : ?>
                <?php $search_id = wp_unique_id( 'hero-search-' ); ?>
                <form class="hero-banner__search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                    <label class="screen-reader-text" for="<?php echo esc_attr( $search_id ); ?>">
                        <?php esc_html_e( 'Search', 'noyona-childtheme' ); ?>
                    </label>
                    <input
                        id="<?php echo esc_attr( $search_id ); ?>"
                        class="hero-banner__search-input"
                        type="search"
                        name="s"
                        placeholder="<?php echo esc_attr( $atts['searchPlaceholder'] ); ?>"
                    />
                    <?php if ( ! empty( $atts['searchPostType'] ) ) : ?>
                        <input type="hidden" name="post_type" value="<?php echo esc_attr( $atts['searchPostType'] ); ?>" />
                    <?php endif; ?>
                    <button class="hero-banner__search-btn" type="submit" aria-label="<?php esc_attr_e( 'Search', 'noyona-childtheme' ); ?>">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </form>
            <?php endif; ?>

            <?php if ( ! empty( $atts['buttonText'] ) && ! empty( $atts['buttonUrl'] ) ) : ?>
                <a class="hero-banner__cta" href="<?php echo esc_url( $atts['buttonUrl'] ); ?>">
                    <?php echo esc_html( $atts['buttonText'] ); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>
