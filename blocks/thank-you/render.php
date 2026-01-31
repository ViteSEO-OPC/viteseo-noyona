<?php
/**
 * Thank You block render.
 *
 * @param array $attributes Block attributes.
 */

$defaults = array(
    'titlePrefix'     => 'Your',
    'titleHighlight'  => 'Glow-Up',
    'titleSuffix'     => 'is on the Way!',
    'message'         => "Your order is confirmed and we're getting your Noyona Essentials favorites ready for their new home. Thank you for supporting local, natural beauty!",
    'buttonText'      => 'Continue Shopping',
    'buttonUrl'       => '/shop/',
    'backgroundImage' => 'http://noyona.local/wp-content/uploads/2026/01/Rectangle-1-scaled.png',
);

$atts = wp_parse_args( $attributes, $defaults );

$background_image = $atts['backgroundImage'];
if ( empty( $background_image ) ) {
    $background_image = 'http://noyona.local/wp-content/uploads/2026/01/Rectangle-1-scaled.png';
}
?>
<section
    class="wp-block-noyona-thank-you noyona-thank-you alignfull"
    style="--noyona-thank-you-watermark: url('<?php echo esc_url( $background_image ); ?>');"
>
    <div class="noyona-thank-you__inner">
        <h1 class="noyona-thank-you__title">
            <span class="noyona-thank-you__title-prefix"><?php echo esc_html( $atts['titlePrefix'] ); ?></span>
            <span class="noyona-thank-you__title-highlight"><?php echo esc_html( $atts['titleHighlight'] ); ?></span>
            <span class="noyona-thank-you__title-suffix"><?php echo esc_html( $atts['titleSuffix'] ); ?></span>
        </h1>
        <p class="noyona-thank-you__copy"><?php echo esc_html( $atts['message'] ); ?></p>
        <?php if ( ! empty( $atts['buttonText'] ) && ! empty( $atts['buttonUrl'] ) ) : ?>
            <a class="noyona-thank-you__cta" href="<?php echo esc_url( $atts['buttonUrl'] ); ?>">
                <?php echo esc_html( $atts['buttonText'] ); ?>
            </a>
        <?php endif; ?>
    </div>
</section>
