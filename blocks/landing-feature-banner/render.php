<?php
/**
 * Landing Feature Banner block render.
 *
 * @param array $attributes Block attributes.
 */

$defaults = array(
    'label'                  => 'Noyona Essentials',
    'title'                  => 'Best Face Makeup',
    'subheading'             => 'Discover Face Makeup Designed for Filipino Skin',
    'paragraph'              => 'Build your everyday glow with lightweight, skin-friendly formulas crafted for tropical weather and morena skin tones.',
    'backgroundImageDesktop' => '/wp-content/themes/viteseo-noyona/assets/images/lp_products-desktop-1920x780px.webp',
    'backgroundImageLaptop'  => '/wp-content/themes/viteseo-noyona/assets/images/lp_products-hero-laptop-1280x780px.webp',
    'backgroundImageTablet'  => '/wp-content/themes/viteseo-noyona/assets/images/lp_products-hero-tablet-768x780px.webp',
    'backgroundImageMobile'  => '/wp-content/themes/viteseo-noyona/assets/images/lp_products-hero-mobile-375x610px.webp',
    'textPosition'           => 'left',
);

$atts = wp_parse_args( $attributes, $defaults );

$allowed_positions = array( 'left', 'center', 'right' );
$text_position = in_array( $atts['textPosition'], $allowed_positions, true ) ? $atts['textPosition'] : 'left';

$background_image_desktop = trim( (string) $atts['backgroundImageDesktop'] );
$background_image_laptop  = trim( (string) $atts['backgroundImageLaptop'] );
$background_image_tablet  = trim( (string) $atts['backgroundImageTablet'] );
$background_image_mobile  = trim( (string) $atts['backgroundImageMobile'] );

$style_vars = array();
if ( '' !== $background_image_desktop ) {
    $style_vars[] = '--lfb-bg-desktop: url("' . esc_url_raw( $background_image_desktop ) . '")';
}
if ( '' !== $background_image_laptop ) {
    $style_vars[] = '--lfb-bg-laptop: url("' . esc_url_raw( $background_image_laptop ) . '")';
}
if ( '' !== $background_image_tablet ) {
    $style_vars[] = '--lfb-bg-tablet: url("' . esc_url_raw( $background_image_tablet ) . '")';
}
if ( '' !== $background_image_mobile ) {
    $style_vars[] = '--lfb-bg-mobile: url("' . esc_url_raw( $background_image_mobile ) . '")';
}

$style_attr = ! empty( $style_vars ) ? ' style="' . esc_attr( implode( '; ', $style_vars ) ) . '"' : '';
?>

<section class="wp-block-noyona-landing-feature-banner noyona-landing-feature-banner alignfull is-text-<?php echo esc_attr( $text_position ); ?>"<?php echo $style_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
    <div class="noyona-landing-feature-banner__inner">
        <div class="noyona-landing-feature-banner__content">
            <?php if ( ! empty( $atts['label'] ) ) : ?>
                <p class="noyona-landing-feature-banner__label"><?php echo esc_html( $atts['label'] ); ?></p>
            <?php endif; ?>

            <?php if ( ! empty( $atts['title'] ) ) : ?>
                <h1 class="noyona-landing-feature-banner__title"><?php echo esc_html( $atts['title'] ); ?></h1>
            <?php endif; ?>

            <?php if ( ! empty( $atts['subheading'] ) ) : ?>
                <h3 class="noyona-landing-feature-banner__subheading">
                    <?php
                    echo wp_kses(
                        $atts['subheading'],
                        array(
                            'span' => array(
                                'class' => array(),
                            ),
                            'em'   => array(),
                            'strong' => array(),
                            'br'   => array(),
                        )
                    );
                    ?>
                </h3>
            <?php endif; ?>

            <?php if ( ! empty( $atts['paragraph'] ) ) : ?>
                <p class="noyona-landing-feature-banner__paragraph">
                    <?php echo nl2br( esc_html( $atts['paragraph'] ) ); ?>
                </p>
            <?php endif; ?>
        </div>
    </div>
</section>
