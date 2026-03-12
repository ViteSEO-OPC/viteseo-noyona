<?php
/**
 * Term Overview Block Template.
 */

$defaults = array(
    'heading'      => 'Terms & Policy',
    'subheading'   => 'At Noyona Cosmetics and Skin Care Products OPC, we prioritize transparency and trust by providing clear guidelines on how we handle your orders, protect your rights, and ensure a seamless shopping experience.',
    'sectionTitle' => 'Our Commitment to Transparency & Trust',
    'sectionIntro' => 'Welcome to Noyona Essentials and Skin Care Products OPC. We value transparency and customer trust, so we have organized our policies into three clear sections: Terms of Service, Shipping Policy and Refund Policy.',
    'cards'        => array(),
);

$atts  = wp_parse_args( $attributes, $defaults );
$cards = is_array( $atts['cards'] ) ? $atts['cards'] : array();
?>

<section class="wp-block-noyona-term term-overview alignfull">
    <div class="term-overview__hero">
        <div class="term-overview__hero-inner">
            <?php if ( ! empty( $atts['heading'] ) ) : ?>
                <h1 class="term-overview__hero-title"><?php echo esc_html( $atts['heading'] ); ?></h1>
            <?php endif; ?>
            <?php if ( ! empty( $atts['subheading'] ) ) : ?>
                <p class="term-overview__hero-subtitle"><?php echo esc_html( $atts['subheading'] ); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="term-overview__body">
        <?php if ( ! empty( $atts['sectionTitle'] ) ) : ?>
            <h2 class="term-overview__section-title">
                <?php
                echo wp_kses(
                    $atts['sectionTitle'],
                    array(
                        'span' => array(
                            'class' => array(),
                        ),
                        'em' => array(),
                    )
                );
                ?>
            </h2>
        <?php endif; ?>

        <?php if ( ! empty( $atts['sectionIntro'] ) ) : ?>
            <p class="term-overview__section-intro"><?php echo esc_html( $atts['sectionIntro'] ); ?></p>
        <?php endif; ?>

        <?php if ( ! empty( $cards ) ) : ?>
            <div class="term-overview__grid">
                <?php foreach ( $cards as $card ) : ?>
                    <?php
                    $title       = isset( $card['title'] ) ? $card['title'] : '';
                    $description = isset( $card['description'] ) ? $card['description'] : '';
                    $url         = isset( $card['url'] ) ? $card['url'] : '#';
                    $button      = isset( $card['buttonLabel'] ) ? $card['buttonLabel'] : 'Read More';
                    $image       = isset( $card['imageUrl'] ) ? $card['imageUrl'] : '';
                    $image_id    = isset( $card['imageId'] ) ? absint( $card['imageId'] ) : 0;

                    if ( $image_id ) {
                        $resolved_image = wp_get_attachment_image_url( $image_id, 'large' );
                        if ( $resolved_image ) {
                            $image = (string) $resolved_image;
                        }
                    } elseif ( ! empty( $image ) ) {
                        $resolved_id = attachment_url_to_postid( $image );
                        if ( $resolved_id ) {
                            $resolved_image = wp_get_attachment_image_url( (int) $resolved_id, 'large' );
                            if ( $resolved_image ) {
                                $image = (string) $resolved_image;
                            }
                        }
                    }

                    if ( '' === $title ) {
                        continue;
                    }
                    ?>
                    <article class="term-overview__card">
                        <a class="term-overview__card-link" href="<?php echo esc_url( $url ); ?>">
                            <div class="term-overview__card-media"<?php echo $image ? ' style="background-image: url(\'' . esc_url( $image ) . '\');"' : ''; ?>>
                                <h3 class="term-overview__card-title"><?php echo esc_html( $title ); ?></h3>
                            </div>
                            <div class="term-overview__card-content">
                                <?php if ( $description ) : ?>
                                    <p class="term-overview__card-description"><?php echo esc_html( $description ); ?></p>
                                <?php endif; ?>
                                <span class="term-overview__card-button">
                                    <?php echo esc_html( $button ); ?>
                                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                </span>
                            </div>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

