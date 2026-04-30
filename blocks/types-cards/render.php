<?php
/**
 * Types Cards block render.
 *
 * @param array $attributes Block attributes.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$defaults = array(
    'title' => 'Types of Face Makeup',
    'cards' => array(),
);

$atts  = wp_parse_args( is_array( $attributes ) ? $attributes : array(), $defaults );
$title = isset( $atts['title'] ) ? (string) $atts['title'] : '';
$cards = isset( $atts['cards'] ) && is_array( $atts['cards'] ) ? $atts['cards'] : array();

$align = '';
$align_value = '';
if ( isset( $atts['align'] ) && is_string( $atts['align'] ) ) {
    $align_value = $atts['align'];
} elseif ( isset( $block ) && is_array( $block ) && ! empty( $block['align'] ) ) {
    $align_value = (string) $block['align'];
}

if ( 'full' === $align_value ) {
    $align = ' alignfull';
} elseif ( 'wide' === $align_value ) {
    $align = ' alignwide';
}
?>
<section class="wp-block-noyona-types-cards noyona-types-cards<?php echo esc_attr( $align ); ?>">
    <div class="noyona-types-cards__inner">
        <?php if ( '' !== trim( $title ) ) : ?>
            <h2 class="noyona-types-cards__title"><?php echo esc_html( $title ); ?></h2>
        <?php endif; ?>

        <?php if ( ! empty( $cards ) ) : ?>
            <div class="noyona-types-cards__grid">
                <?php foreach ( $cards as $card ) : ?>
                    <?php
                    $card = is_array( $card ) ? $card : array();

                    $image_id = isset( $card['imageId'] ) ? absint( $card['imageId'] ) : 0;
                    $img      = isset( $card['image'] ) ? (string) $card['image'] : '';
                    if ( $image_id ) {
                        $resolved_image = wp_get_attachment_image_url( $image_id, 'large' );
                        if ( $resolved_image ) {
                            $img = (string) $resolved_image;
                        }
                    }

                    $card_title = isset( $card['title'] ) ? (string) $card['title'] : '';
                    $card_text  = isset( $card['text'] ) ? (string) $card['text'] : '';
                    ?>
                    <article class="noyona-types-cards__card">
                        <div class="noyona-types-cards__media">
                            <?php if ( '' !== trim( $img ) ) : ?>
                                <img class="noyona-types-cards__image" src="<?php echo esc_url( $img ); ?>" alt="" width="600" height="600" loading="lazy" decoding="async" sizes="(max-width: 768px) 92vw, (max-width: 1280px) 33vw, 360px" />
                            <?php else : ?>
                                <span class="noyona-types-cards__image-placeholder" aria-hidden="true"></span>
                            <?php endif; ?>
                        </div>
                        <div class="noyona-types-cards__body">
                            <?php if ( '' !== trim( $card_title ) ) : ?>
                                <h3 class="noyona-types-cards__card-title"><?php echo esc_html( $card_title ); ?></h3>
                            <?php endif; ?>
                            <?php if ( '' !== trim( $card_text ) ) : ?>
                                <p class="noyona-types-cards__card-text"><?php echo esc_html( $card_text ); ?></p>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
