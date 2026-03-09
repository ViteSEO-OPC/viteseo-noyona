<?php
/**
 * Landing Feature Banner block render.
 *
 * @param array $attributes Block attributes.
 */

$defaults = array(
    'label'       => 'Noyona Essentials',
    'title'       => 'Best Face Makeup',
    'subheading'  => 'Discover Face Makeup Designed for Filipino Skin',
    'paragraph'   => 'Build your everyday glow with lightweight, skin-friendly formulas crafted for tropical weather and morena skin tones.',
    'textPosition'=> 'left',
);

$atts = wp_parse_args( $attributes, $defaults );

$allowed_positions = array( 'left', 'center', 'right' );
$text_position = in_array( $atts['textPosition'], $allowed_positions, true ) ? $atts['textPosition'] : 'left';
?>

<section class="wp-block-noyona-landing-feature-banner noyona-landing-feature-banner alignfull is-text-<?php echo esc_attr( $text_position ); ?>">
    <div class="noyona-landing-feature-banner__inner">
        <div class="noyona-landing-feature-banner__content">
            <?php if ( ! empty( $atts['label'] ) ) : ?>
                <p class="noyona-landing-feature-banner__label"><?php echo esc_html( $atts['label'] ); ?></p>
            <?php endif; ?>

            <?php if ( ! empty( $atts['title'] ) ) : ?>
                <h2 class="noyona-landing-feature-banner__title"><?php echo esc_html( $atts['title'] ); ?></h2>
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
