<?php
/**
 * Hero Banner block render.
 *
 * @param array $attributes Block attributes.
 */

$defaults = array(
    'eyebrow'         => '',
    'titleLine'       => 'Radiate Natural,',
    'body'            => 'Noyona celebrates beauty that feels like you elevated by nature, crafted for everyday wear, and made to last. Discover effortless formulas that care for skin while enhancing your glow.',
    'searchEnabled'   => false,
    'searchPlaceholder' => 'Search articles',
    'searchPostType'  => '',
    'buttonText'      => 'Discover Your Glow',
    'buttonUrl'       => '/shop/',
    'buttonAlign'     => '',

    'backgroundImageId' => 0,
    'backgroundImage' => '',
    'backgroundSize'  => 'cover',
    // ✅ default focal point to the right so the subject on the right won’t get chopped
    'backgroundPosition' => 'right center',

    // ✅ mobile-specific (optional)
    'backgroundImageMobileId' => 0,
    'backgroundImageMobile' => '',
    // Default mobile behavior: fill the hero (no pink bars). Override per-block if you need "contain".
    'backgroundSizeMobile' => 'cover',
    'backgroundPositionMobile' => 'right center',
    'desktopHeight'     => '950px',

    // ✅ prevents white gaps when using "contain"
    'backgroundColor' => '#f7d0d8',
);

$atts = wp_parse_args( $attributes, $defaults );

$bg_image_id = absint( $atts['backgroundImageId'] );
$bg_image_mobile_id = absint( $atts['backgroundImageMobileId'] );

if ( ! $bg_image_id && ! empty( $atts['backgroundImage'] ) ) {
    $resolved_id = attachment_url_to_postid( $atts['backgroundImage'] );
    if ( $resolved_id ) {
        $bg_image_id = (int) $resolved_id;
    }
}

if ( ! $bg_image_mobile_id && ! empty( $atts['backgroundImageMobile'] ) ) {
    $resolved_mobile_id = attachment_url_to_postid( $atts['backgroundImageMobile'] );
    if ( $resolved_mobile_id ) {
        $bg_image_mobile_id = (int) $resolved_mobile_id;
    }
}

$fallback_image = get_stylesheet_directory_uri() . '/assets/images/makeup.jpg';
$bg_image = $bg_image_id
    ? wp_get_attachment_image_url( $bg_image_id, 'full' )
    : ( ! empty( $atts['backgroundImage'] ) ? $atts['backgroundImage'] : $fallback_image );

$bg_image_mobile = $bg_image_mobile_id
    ? wp_get_attachment_image_url( $bg_image_mobile_id, 'full' )
    : ( ! empty( $atts['backgroundImageMobile'] ) ? $atts['backgroundImageMobile'] : $bg_image );

if ( empty( $bg_image ) ) {
    $bg_image = $fallback_image;
}

if ( empty( $bg_image_mobile ) ) {
    $bg_image_mobile = $bg_image;
}

$bg_size = $atts['backgroundSize'] ?: 'cover';
$bg_position = $atts['backgroundPosition'] ?: 'right center';

$bg_size_mobile = $atts['backgroundSizeMobile'] ?: 'cover';
$bg_position_mobile = $atts['backgroundPositionMobile'] ?: 'right center';
$desktop_height_raw = isset( $atts['desktopHeight'] ) ? trim( (string) $atts['desktopHeight'] ) : '';
$desktop_height = $desktop_height_raw !== '' ? $desktop_height_raw : '900px';

$bg_color = $atts['backgroundColor'] ?: '#f7d0d8';
$is_front_page = is_front_page();
$mobile_srcset = $bg_image_mobile_id ? wp_get_attachment_image_srcset( $bg_image_mobile_id, 'full' ) : '';
$desktop_srcset = $bg_image_id ? wp_get_attachment_image_srcset( $bg_image_id, 'full' ) : '';

$allowed_button_align = array( 'left', 'center', 'right' );
$button_align_raw = isset( $atts['buttonAlign'] ) ? strtolower( trim( (string) $atts['buttonAlign'] ) ) : '';
$button_align = in_array( $button_align_raw, $allowed_button_align, true ) ? $button_align_raw : '';
$hero_banner_class = 'wp-block-noyona-hero-banner hero-banner alignfull';
if ( '' !== $button_align ) {
    $hero_banner_class .= ' hero-banner--button-' . $button_align;
}
?>
<section
    class="<?php echo esc_attr( $hero_banner_class ); ?>"
    style="
        --hero-banner-bg: none;
        --hero-banner-bg-mobile: none;

        --hero-banner-bg-size: <?php echo esc_attr( $bg_size ); ?>;
        --hero-banner-bg-position: <?php echo esc_attr( $bg_position ); ?>;

        --hero-banner-bg-size-mobile: <?php echo esc_attr( $bg_size_mobile ); ?>;
        --hero-banner-bg-position-mobile: <?php echo esc_attr( $bg_position_mobile ); ?>;

        --hero-banner-desktop-height: <?php echo esc_attr( $desktop_height ); ?>;
        --hero-banner-bg-color: <?php echo esc_attr( $bg_color ); ?>;
    "
>
    <picture class="hero-banner__bg-media" aria-hidden="true">
        <?php if ( ! empty( $bg_image_mobile ) ) : ?>
            <source
                media="(max-width: 900px)"
                <?php if ( ! empty( $mobile_srcset ) ) : ?>
                    srcset="<?php echo esc_attr( $mobile_srcset ); ?>"
                    sizes="100vw"
                <?php else : ?>
                    srcset="<?php echo esc_url( $bg_image_mobile ); ?>"
                <?php endif; ?>
            />
        <?php endif; ?>
        <img
            class="hero-banner__bg"
            src="<?php echo esc_url( $bg_image ); ?>"
            alt=""
            decoding="async"
            <?php if ( ! empty( $desktop_srcset ) ) : ?>
                srcset="<?php echo esc_attr( $desktop_srcset ); ?>"
                sizes="100vw"
            <?php endif; ?>
            <?php if ( $is_front_page ) : ?>
                fetchpriority="high"
                loading="eager"
            <?php else : ?>
                loading="lazy"
            <?php endif; ?>
        />
    </picture>
    <div class="hero-banner__inner">
        <div class="hero-banner__content">
            <?php if ( ! empty( $atts['eyebrow'] ) ) : ?>
                <p class="hero-banner__eyebrow"><?php echo esc_html( $atts['eyebrow'] ); ?></p>
            <?php endif; ?>

            <h1 class="hero-banner__title">
                <span class="hero-banner__title-line">
                    <?php
                    echo wp_kses(
                        $atts['titleLine'],
                        array(
                            'span' => array( 'class' => array() ),
                            'em' => array(),
                            'strong' => array(),
                        )
                    );
                    ?>
                </span>
            </h1>

            <p class="hero-banner__body"><?php echo esc_html( $atts['body'] ); ?></p>

            <?php if ( ! empty( $atts['searchEnabled'] ) ) : ?>
                <form class="hero-banner__search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" role="search">
                    <input
                        class="hero-banner__search-input"
                        type="search"
                        name="s"
                        value="<?php echo esc_attr( get_search_query() ); ?>"
                        placeholder="<?php echo esc_attr( ! empty( $atts['searchPlaceholder'] ) ? $atts['searchPlaceholder'] : __( 'Search articles', 'noyona-childtheme' ) ); ?>"
                        aria-label="<?php esc_attr_e( 'Search', 'noyona-childtheme' ); ?>"
                    />
                    <?php if ( ! empty( $atts['searchPostType'] ) ) : ?>
                        <input type="hidden" name="post_type" value="<?php echo esc_attr( $atts['searchPostType'] ); ?>" />
                    <?php endif; ?>
                    <button class="hero-banner__search-btn" type="submit" aria-label="<?php esc_attr_e( 'Submit search', 'noyona-childtheme' ); ?>">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    </button>
                </form>
            <?php endif; ?>

            <?php if ( ! empty( $atts['buttonText'] ) && ! empty( $atts['buttonUrl'] ) ) : ?>
                <a class="hero-banner__cta" href="<?php echo esc_url( $atts['buttonUrl'] ); ?>">
                    <?php echo esc_html( $atts['buttonText'] ); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>
