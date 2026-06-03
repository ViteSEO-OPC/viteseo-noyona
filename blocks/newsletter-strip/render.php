<?php
/**
 * Newsletter Strip block template.
 */

$defaults = array(
    'heading' => '',
    'headingBefore' => 'Join the',
    'headingAccent' => 'Noyona',
    'headingAfter' => 'Family',
    'subtitle' => 'Get notified to our exclusive updates, discounts, and more!',
    'placeholder' => 'Your email address',
    'buttonText' => 'Notify',
    'showEmailInput' => true,
    'formAction' => '#',
    'backgroundColor' => '#E8BCC3',
    'innerBorderColor' => '',
    'innerBorderWidth' => '0',
    'innerBorderStyle' => 'all',
    'innerHeight' => '',
    'ctaTextColor' => '#FFFFFF',
    'ctaBackgroundColor' => '#D91B61',
    'ctaBorderColor' => '#D91B61',
    'ctaHoverTextColor' => '#D91B61',
    'ctaHoverBackgroundColor' => '#FBDDE2',
);

$atts = wp_parse_args( $attributes, $defaults );

$heading        = isset( $atts['heading'] ) ? (string) $atts['heading'] : '';
$heading_before = isset( $atts['headingBefore'] ) ? (string) $atts['headingBefore'] : '';
$heading_accent = isset( $atts['headingAccent'] ) ? (string) $atts['headingAccent'] : '';
$heading_after  = isset( $atts['headingAfter'] ) ? (string) $atts['headingAfter'] : '';
$subtitle       = isset( $atts['subtitle'] ) ? (string) $atts['subtitle'] : '';
$placeholder    = isset( $atts['placeholder'] ) ? (string) $atts['placeholder'] : '';
$button_text    = isset( $atts['buttonText'] ) ? (string) $atts['buttonText'] : '';
$show_email     = ! empty( $atts['showEmailInput'] );
$form_action    = isset( $atts['formAction'] ) ? (string) $atts['formAction'] : '#';
$bg             = sanitize_hex_color( $atts['backgroundColor'] );
$border_color   = sanitize_hex_color( $atts['innerBorderColor'] );
$border_width   = isset( $atts['innerBorderWidth'] ) ? trim( (string) $atts['innerBorderWidth'] ) : '0';
$border_style   = isset( $atts['innerBorderStyle'] ) ? sanitize_key( (string) $atts['innerBorderStyle'] ) : 'all';
$inner_height   = isset( $atts['innerHeight'] ) ? trim( (string) $atts['innerHeight'] ) : '';
$cta_text       = sanitize_hex_color( $atts['ctaTextColor'] );
$cta_bg         = sanitize_hex_color( $atts['ctaBackgroundColor'] );
$cta_border     = sanitize_hex_color( $atts['ctaBorderColor'] );
$cta_hover_text = sanitize_hex_color( $atts['ctaHoverTextColor'] );
$cta_hover_bg   = sanitize_hex_color( $atts['ctaHoverBackgroundColor'] );
$captcha_markup = '';

if ( ! $bg ) {
    $bg = '#E8BCC3';
}
if ( ! $border_color ) {
    $border_color = 'transparent';
}
if ( ! preg_match( '/^\d+(\.\d+)?(px|rem|em|%)$/', $border_width ) ) {
    $border_width = '0';
}
if ( ! in_array( $border_style, array( 'all', 'top-sides' ), true ) ) {
    $border_style = 'all';
}
if ( '' !== $inner_height && ! preg_match( '/^(auto|\d+(\.\d+)?(px|rem|em|vh|svh|dvh|%)|clamp\([^)]+\))$/', $inner_height ) ) {
    $inner_height = '';
}
if ( ! $cta_text ) {
    $cta_text = '#FFFFFF';
}
if ( ! $cta_bg ) {
    $cta_bg = '#D91B61';
}
if ( ! $cta_border ) {
    $cta_border = '#D91B61';
}
if ( ! $cta_hover_text ) {
    $cta_hover_text = '#D91B61';
}
if ( ! $cta_hover_bg ) {
    $cta_hover_bg = '#FBDDE2';
}

$style_vars = array(
    '--newsletter-strip-bg: ' . $bg,
    '--newsletter-strip-border-color: ' . $border_color,
    '--newsletter-strip-border-width: ' . $border_width,
    '--newsletter-strip-border-bottom-width: ' . ( 'top-sides' === $border_style ? '0' : $border_width ),
    '--newsletter-strip-cta-color: ' . $cta_text,
    '--newsletter-strip-cta-bg: ' . $cta_bg,
    '--newsletter-strip-cta-border-color: ' . $cta_border,
    '--newsletter-strip-cta-hover-color: ' . $cta_hover_text,
    '--newsletter-strip-cta-hover-bg: ' . $cta_hover_bg,
);
if ( '' !== $inner_height ) {
    $style_vars[] = '--newsletter-strip-height: ' . $inner_height;
}

$default_newsletter_action = admin_url( 'admin-post.php' );
if ( '' === trim( $form_action ) || '#' === trim( $form_action ) ) {
    $form_action = $default_newsletter_action;
}

if ( function_exists( 'noyona_recaptcha_form_widget_html' ) ) {
    $captcha_markup = noyona_recaptcha_form_widget_html( 'newsletter', 'newsletter-strip__captcha' );
}

$newsletter_notice = function_exists( 'noyona_newsletter_notice_from_query' )
    ? noyona_newsletter_notice_from_query()
    : null;

$newsletter_redirect_back = function_exists( 'noyona_newsletter_redirect_back_url' )
    ? noyona_newsletter_redirect_back_url()
    : home_url( '/' );
?>

<section class="wp-block-noyona-newsletter-strip newsletter-strip alignfull" style="<?php echo esc_attr( implode( '; ', $style_vars ) ); ?>">
    <div class="newsletter-strip__inner">
        <h2 class="newsletter-strip__title">
            <?php if ( '' !== trim( $heading ) ) : ?>
                <?php echo esc_html( $heading ); ?>
            <?php else : ?>
                <?php if ( '' !== trim( $heading_before ) ) : ?>
                    <span><?php echo esc_html( $heading_before ); ?></span>
                <?php endif; ?>
                <?php if ( '' !== trim( $heading_accent ) ) : ?>
                    <em><?php echo esc_html( $heading_accent ); ?></em>
                <?php endif; ?>
                <?php if ( '' !== trim( $heading_after ) ) : ?>
                    <span><?php echo esc_html( $heading_after ); ?></span>
                <?php endif; ?>
            <?php endif; ?>
        </h2>

        <?php if ( '' !== trim( $subtitle ) ) : ?>
            <p class="newsletter-strip__subtitle"><?php echo esc_html( $subtitle ); ?></p>
        <?php endif; ?>

        <?php if ( is_array( $newsletter_notice ) && '' !== trim( (string) $newsletter_notice['message'] ) ) : ?>
            <?php
            $nl_notice_type = in_array( $newsletter_notice['type'], array( 'success', 'info', 'warning' ), true )
                ? (string) $newsletter_notice['type']
                : 'error';
            $nl_notice_role = 'error' === $nl_notice_type ? 'alert' : 'status';
            ?>
            <p
                class="noyona-notice is-<?php echo esc_attr( $nl_notice_type ); ?>"
                role="<?php echo esc_attr( $nl_notice_role ); ?>"
                data-noyona-notice-key="newsletter-strip"
                <?php if ( ! empty( $newsletter_notice['autohide'] ) ) : ?>
                    data-noyona-notice-autohide="<?php echo esc_attr( (string) (int) $newsletter_notice['autohide'] ); ?>"
                <?php endif; ?>
            ><?php echo esc_html( (string) $newsletter_notice['message'] ); ?></p>
        <?php endif; ?>

        <?php if ( '' !== trim( $button_text ) ) : ?>
            <?php if ( $show_email ) : ?>
                <form class="newsletter-strip__form" method="post" action="<?php echo esc_url( $form_action ); ?>" novalidate>
                    <input type="hidden" name="action" value="noyona_newsletter_subscribe" />
                    <input type="hidden" name="_wp_http_referer" value="<?php echo esc_url( $newsletter_redirect_back ); ?>" />
                    <?php wp_nonce_field( 'noyona_newsletter_subscribe', 'noyona_newsletter_nonce' ); ?>
                    <input
                        type="email"
                        class="newsletter-strip__input"
                        name="newsletter_email"
                        placeholder="<?php echo esc_attr( $placeholder ); ?>"
                        autocomplete="email"
                        inputmode="email"
                        aria-describedby="newsletter-strip-email-hint"
                    />
                    <span id="newsletter-strip-email-hint" class="screen-reader-text"><?php esc_html_e( 'Enter your email address to subscribe.', 'noyona-childtheme' ); ?></span>
                    <?php if ( '' !== $captcha_markup ) : ?>
                        <?php echo $captcha_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    <?php endif; ?>
                    <button type="submit" class="newsletter-strip__button"><?php echo esc_html( $button_text ); ?></button>
                </form>
            <?php else : ?>
                <a class="newsletter-strip__button" href="<?php echo esc_url( $form_action ); ?>"><?php echo esc_html( $button_text ); ?></a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
