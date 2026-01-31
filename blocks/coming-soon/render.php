<?php
/**
 * Coming Soon block render.
 *
 * @param array $attributes Block attributes.
 */

$defaults = array(
    'titlePrefix'      => 'Something',
    'titleHighlight'   => 'beautiful',
    'titleSuffix'      => 'is blooming',
    'message'          => "We're perfecting our natural, high-quality formulas to bring you the glow you deserve. Noyona Essentials is leveling up - stay tuned!",
    'inputPlaceholder' => 'Your Email Address',
    'buttonText'       => 'Notify Me',
    'formAction'       => '',
    'backgroundImage'  => 'http://noyona.local/wp-content/uploads/2026/01/Rectangle-1-scaled.png',
);

$atts = wp_parse_args( $attributes, $defaults );

$background_image = $atts['backgroundImage'];
if ( empty( $background_image ) ) {
    $background_image = 'http://noyona.local/wp-content/uploads/2026/01/Rectangle-1-scaled.png';
}

$form_action = ! empty( $atts['formAction'] ) ? $atts['formAction'] : home_url( '/' );
$input_id = wp_unique_id( 'coming-soon-email-' );
?>
<section
    class="wp-block-noyona-coming-soon noyona-coming-soon alignfull"
    style="--noyona-coming-soon-watermark: url('<?php echo esc_url( $background_image ); ?>');"
>
    <div class="noyona-coming-soon__inner">
        <h1 class="noyona-coming-soon__title">
            <span class="noyona-coming-soon__title-prefix"><?php echo esc_html( $atts['titlePrefix'] ); ?></span>
            <span class="noyona-coming-soon__title-highlight"><?php echo esc_html( $atts['titleHighlight'] ); ?></span>
            <span class="noyona-coming-soon__title-suffix"><?php echo esc_html( $atts['titleSuffix'] ); ?></span>
        </h1>
        <p class="noyona-coming-soon__copy"><?php echo esc_html( $atts['message'] ); ?></p>
        <form class="noyona-coming-soon__form" action="<?php echo esc_url( $form_action ); ?>" method="post">
            <label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>">
                <?php esc_html_e( 'Email Address', 'noyona-childtheme' ); ?>
            </label>
            <input
                id="<?php echo esc_attr( $input_id ); ?>"
                class="noyona-coming-soon__input"
                type="email"
                name="noyona_coming_soon_email"
                placeholder="<?php echo esc_attr( $atts['inputPlaceholder'] ); ?>"
                autocomplete="email"
            />
            <button class="noyona-coming-soon__button" type="submit">
                <?php echo esc_html( $atts['buttonText'] ); ?>
            </button>
        </form>
    </div>
</section>
