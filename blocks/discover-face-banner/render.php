<?php
/**
 * Discover Face Banner block render.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$defaults = array(
    'heading'        => 'Discover Face',
    'subheading'     => 'Makeup Essentials',
    'paragraphOne'   => 'Choosing the right face makeup for morena skin and humid weather ensures your look lasts all day.',
    'paragraphTwo'   => 'Browse our full collection online and enjoy fast, reliable delivery via TikTok, Shopee and Lazada.',
    'buttonText'     => 'View all products',
    'buttonUrl'      => '/coming-soon/',
    'backgroundImage'=> '',
    'cards'          => array(),
);

$atts = wp_parse_args( $attributes, $defaults );

$heading         = isset( $atts['heading'] ) ? trim( (string) $atts['heading'] ) : '';
$subheading      = isset( $atts['subheading'] ) ? trim( (string) $atts['subheading'] ) : '';
$paragraph_one   = isset( $atts['paragraphOne'] ) ? trim( (string) $atts['paragraphOne'] ) : '';
$paragraph_two   = isset( $atts['paragraphTwo'] ) ? trim( (string) $atts['paragraphTwo'] ) : '';
$button_text     = isset( $atts['buttonText'] ) ? trim( (string) $atts['buttonText'] ) : '';
$button_url      = isset( $atts['buttonUrl'] ) ? trim( (string) $atts['buttonUrl'] ) : '';
$background_image= isset( $atts['backgroundImage'] ) ? trim( (string) $atts['backgroundImage'] ) : '';
$cards           = is_array( $atts['cards'] ?? null ) ? $atts['cards'] : array();

$style = '';
if ( '' !== $background_image ) {
    $style = '--discover-face-bg-image: url(' . esc_url_raw( $background_image ) . ');';
}
?>

<section <?php echo get_block_wrapper_attributes( array( 'class' => 'noyona-discover-face-banner', 'style' => $style ) ); ?>>
    <div class="noyona-discover-face-banner__inner">
        <div class="noyona-discover-face-banner__content">
            <?php if ( '' !== $heading ) : ?>
                <h2 class="noyona-discover-face-banner__heading"><?php echo esc_html( $heading ); ?></h2>
            <?php endif; ?>

            <?php if ( '' !== $subheading ) : ?>
                <p class="noyona-discover-face-banner__subheading"><?php echo esc_html( $subheading ); ?></p>
            <?php endif; ?>

            <?php if ( '' !== $paragraph_one ) : ?>
                <p class="noyona-discover-face-banner__paragraph"><?php echo esc_html( $paragraph_one ); ?></p>
            <?php endif; ?>

            <?php if ( '' !== $paragraph_two ) : ?>
                <p class="noyona-discover-face-banner__paragraph"><?php echo esc_html( $paragraph_two ); ?></p>
            <?php endif; ?>

            <?php if ( '' !== $button_text ) : ?>
                <a class="noyona-discover-face-banner__cta" href="<?php echo esc_url( '' !== $button_url ? $button_url : '#' ); ?>">
                    <span><?php echo esc_html( $button_text ); ?></span>
                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </a>
            <?php endif; ?>
        </div>

        <?php if ( ! empty( $cards ) ) : ?>
            <div class="noyona-discover-face-banner__cards">
                <?php foreach ( $cards as $card ) : ?>
                    <?php
                    $card_title       = isset( $card['title'] ) ? trim( (string) $card['title'] ) : '';
                    $card_description = isset( $card['description'] ) ? trim( (string) $card['description'] ) : '';
                    $card_icon_class  = isset( $card['iconClass'] ) ? trim( (string) $card['iconClass'] ) : 'fa-solid fa-bag-shopping';
                    $chips            = isset( $card['chips'] ) && is_array( $card['chips'] ) ? $card['chips'] : array();
                    if ( '' === $card_icon_class || false === strpos( $card_icon_class, 'fa-' ) ) {
                        $card_icon_class = 'fa-solid fa-bag-shopping';
                    }
                    if ( false !== strpos( $card_icon_class, 'fa-regular fa-bag-shopping' ) ) {
                        // Free Font Awesome sets bag-shopping in solid style.
                        $card_icon_class = 'fa-solid fa-bag-shopping';
                    }
                    ?>
                    <article class="noyona-discover-face-banner__card">
                        <div class="noyona-discover-face-banner__card-header">
                            <?php if ( ! empty( $chips ) ) : ?>
                                <div class="noyona-discover-face-banner__chips" aria-hidden="true">
                                    <?php foreach ( $chips as $chip ) : ?>
                                        <span class="noyona-discover-face-banner__chip"><?php echo esc_html( (string) $chip ); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php else : ?>
                                <span class="noyona-discover-face-banner__icon" aria-hidden="true">
                                    <i class="<?php echo esc_attr( $card_icon_class ); ?>"></i>
                                </span>
                            <?php endif; ?>
                            <?php if ( '' !== $card_title ) : ?>
                                <h3 class="noyona-discover-face-banner__card-title"><?php echo esc_html( $card_title ); ?></h3>
                            <?php endif; ?>
                        </div>

                        <?php if ( '' !== $card_description ) : ?>
                            <p class="noyona-discover-face-banner__card-description"><?php echo esc_html( $card_description ); ?></p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
