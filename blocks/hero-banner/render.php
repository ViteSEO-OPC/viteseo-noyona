<?php
/**
 * Hero Banner block render.
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
    // ✅ default focal point to the right so the subject on the right won’t get chopped
    'backgroundPosition' => 'right center',

    // ✅ mobile-specific (optional)
    'backgroundImageMobile' => '',
    // Default mobile behavior: fill the hero (no pink bars). Override per-block if you need "contain".
    'backgroundSizeMobile' => 'cover',
    'backgroundPositionMobile' => 'right center',

    // ✅ prevents white gaps when using "contain"
    'backgroundColor' => '#f7d0d8',
);

$atts = wp_parse_args( $attributes, $defaults );

$bg_image = ! empty( $atts['backgroundImage'] )
    ? $atts['backgroundImage']
    : get_stylesheet_directory_uri() . '/assets/images/makeup.jpg';

$bg_image_mobile = ! empty( $atts['backgroundImageMobile'] )
    ? $atts['backgroundImageMobile']
    : $bg_image;

$bg_size = $atts['backgroundSize'] ?: 'cover';
$bg_position = $atts['backgroundPosition'] ?: 'right center';

$bg_size_mobile = $atts['backgroundSizeMobile'] ?: 'cover';
$bg_position_mobile = $atts['backgroundPositionMobile'] ?: 'right center';

$bg_color = $atts['backgroundColor'] ?: '#f7d0d8';
?>
<section
    class="wp-block-noyona-hero-banner hero-banner alignfull"
    style="
        --hero-banner-bg: url('<?php echo esc_url( $bg_image ); ?>');
        --hero-banner-bg-mobile: url('<?php echo esc_url( $bg_image_mobile ); ?>');

        --hero-banner-bg-size: <?php echo esc_attr( $bg_size ); ?>;
        --hero-banner-bg-position: <?php echo esc_attr( $bg_position ); ?>;

        --hero-banner-bg-size-mobile: <?php echo esc_attr( $bg_size_mobile ); ?>;
        --hero-banner-bg-position-mobile: <?php echo esc_attr( $bg_position_mobile ); ?>;

        --hero-banner-bg-color: <?php echo esc_attr( $bg_color ); ?>;
    "
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
                            'span' => array( 'class' => array() ),
                            'em' => array(),
                            'strong' => array(),
                        )
                    );
                    ?>
                </span>
            </h1>

            <p class="hero-banner__body"><?php echo esc_html( $atts['body'] ); ?></p>

            <?php if ( ! empty( $atts['buttonText'] ) && ! empty( $atts['buttonUrl'] ) ) : ?>
                <a class="hero-banner__cta" href="<?php echo esc_url( $atts['buttonUrl'] ); ?>">
                    <?php echo esc_html( $atts['buttonText'] ); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>
