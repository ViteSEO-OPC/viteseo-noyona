<?php
/**
 * 404 block render.
 *
 * @param array $attributes Block attributes.
 */

$defaults = array(
    'titleNumber'     => '404',
    'titleText'       => 'Not Found',
    'message'         => "This page is missing! Like a good glow-up, we're working on it. Head back to Noyona Essentials for more beauty.",
    'buttonText'      => 'Return to Home',
    'buttonUrl'       => '',
    'backgroundImage' => 'http://noyona.local/wp-content/uploads/2026/01/Screenshot-from-2026-01-27-09-57-50.png',
);

$atts = wp_parse_args( $attributes, $defaults );

$background_image = $atts['backgroundImage'];
if ( empty( $background_image ) ) {
    $background_image = 'http://noyona.local/wp-content/uploads/2026/01/Screenshot-from-2026-01-27-09-57-50.png';
}

$button_url = ! empty( $atts['buttonUrl'] ) ? $atts['buttonUrl'] : home_url( '/' );
?>
<section
    class="wp-block-noyona-not-found noyona-404 alignfull"
    style="--noyona-404-watermark: url('<?php echo esc_url( $background_image ); ?>');"
>
    <div class="noyona-404__inner">
        <h1 class="noyona-404__title">
            <span class="noyona-404__title-number"><?php echo esc_html( $atts['titleNumber'] ); ?></span>
            <span class="noyona-404__title-text"><?php echo esc_html( $atts['titleText'] ); ?></span>
        </h1>
        <p class="noyona-404__copy"><?php echo esc_html( $atts['message'] ); ?></p>
        <?php if ( ! empty( $atts['buttonText'] ) ) : ?>
            <a class="noyona-404__cta" href="<?php echo esc_url( $button_url ); ?>">
                <?php echo esc_html( $atts['buttonText'] ); ?>
            </a>
        <?php endif; ?>
    </div>
</section>
