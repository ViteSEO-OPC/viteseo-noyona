<?php
/**
 * Newsletter Strip block template.
 */

$defaults = array(
    'headingBefore' => 'Join the',
    'headingAccent' => 'Noyona',
    'headingAfter' => 'Family',
    'subtitle' => 'Get notified to our exclusive updates, discounts, and more!',
    'placeholder' => 'Your email address',
    'buttonText' => 'Notify',
    'formAction' => '#',
    'backgroundColor' => '#E8BCC3',
);

$atts = wp_parse_args( $attributes, $defaults );

$heading_before = isset( $atts['headingBefore'] ) ? (string) $atts['headingBefore'] : '';
$heading_accent = isset( $atts['headingAccent'] ) ? (string) $atts['headingAccent'] : '';
$heading_after  = isset( $atts['headingAfter'] ) ? (string) $atts['headingAfter'] : '';
$subtitle       = isset( $atts['subtitle'] ) ? (string) $atts['subtitle'] : '';
$placeholder    = isset( $atts['placeholder'] ) ? (string) $atts['placeholder'] : '';
$button_text    = isset( $atts['buttonText'] ) ? (string) $atts['buttonText'] : '';
$form_action    = isset( $atts['formAction'] ) ? (string) $atts['formAction'] : '#';
$bg             = sanitize_hex_color( $atts['backgroundColor'] );

if ( ! $bg ) {
    $bg = '#E8BCC3';
}
?>

<section class="wp-block-noyona-newsletter-strip newsletter-strip alignfull" style="--newsletter-strip-bg: <?php echo esc_attr( $bg ); ?>;">
    <div class="newsletter-strip__inner">
        <h2 class="newsletter-strip__title">
            <?php if ( '' !== trim( $heading_before ) ) : ?>
                <span><?php echo esc_html( $heading_before ); ?></span>
            <?php endif; ?>
            <?php if ( '' !== trim( $heading_accent ) ) : ?>
                <em><?php echo esc_html( $heading_accent ); ?></em>
            <?php endif; ?>
            <?php if ( '' !== trim( $heading_after ) ) : ?>
                <span><?php echo esc_html( $heading_after ); ?></span>
            <?php endif; ?>
        </h2>

        <?php if ( '' !== trim( $subtitle ) ) : ?>
            <p class="newsletter-strip__subtitle"><?php echo esc_html( $subtitle ); ?></p>
        <?php endif; ?>

        <form class="newsletter-strip__form" method="post" action="<?php echo esc_url( $form_action ); ?>">
            <input
                type="email"
                class="newsletter-strip__input"
                name="newsletter_email"
                placeholder="<?php echo esc_attr( $placeholder ); ?>"
                required
            />
            <button type="submit" class="newsletter-strip__button"><?php echo esc_html( $button_text ); ?></button>
        </form>
    </div>
</section>
