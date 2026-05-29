<?php
/**
 * SEO: robots.txt, sitemaps, robots meta, doc titles, meta descriptions, under-development gate.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ----- Disable core XML sitemaps when site is under development ----- */
/**
 * Disable WordPress core XML sitemaps.
 */
add_filter( 'wp_sitemaps_enabled', 'noyona_filter_wp_sitemaps_enabled' );
function noyona_filter_wp_sitemaps_enabled( $enabled ) {
    if ( noyona_is_site_under_development() ) {
        return false;
    }

    return $enabled;
}

/* ----- Force restrictive robots.txt when under development ----- */
/**
 * Force a restrictive robots.txt from WordPress.
 * Note: this discourages crawlers, but does not secure URLs from direct browser access.
 */
add_filter( 'robots_txt', 'noyona_custom_robots_txt', 999, 2 );
function noyona_custom_robots_txt( $output, $public ) {
    if ( ! noyona_is_site_under_development() ) {
        // In production, do not override plugin/core robots.txt output.
        return $output;
    }

    return implode( "\n", array(
        'User-agent: *',
        'Disallow: /',
        'Disallow: /wp-sitemap.xml',
        'Disallow: /sitemap.xml',
        'Disallow: /sitemap1.xml',
        'Disallow: /sitemap_index.xml',
        'Disallow: /product-sitemap.xml',
        'Disallow: /page-sitemap.xml',
        'Disallow: /post-sitemap.xml',
        'Disallow: /category-sitemap.xml',
        'Disallow: /product_cat-sitemap.xml',
        'Disallow: /product_tag-sitemap.xml',
    ) );
}

/* ----- X-Robots-Tag header output ----- */
/**
 * Send X-Robots-Tag header.
 */
add_action( 'send_headers', 'noyona_send_robots_headers', 1 );
function noyona_send_robots_headers() {
    // In production, avoid overriding robots headers set by SEO plugins/server.
    if ( ! noyona_is_site_under_development() ) {
        return;
    }

    header( 'X-Robots-Tag: noindex, nofollow, noarchive, nosnippet, noimageindex', true );
}

/* =================================================
 * SEO: Custom page values
 * ================================================= */
function noyona_get_static_seo_pages() {
    $index = 'index, follow';

    return array(
        'home' => array(
            'aliases'     => array( '' ),
            'title'       => 'Noyona Essentials | Filipino Vegan Cosmetics Rooted in Nature',
            'description' => 'Discover Noyona Essentials, a Filipino cosmetics brand rooted in nature. Vegan, cruelty-free products crafted for lasting beauty and everyday confidence.',
            'robots'      => $index,
        ),
        'shop' => array(
            'aliases'     => array( 'shop' ),
            'title'       => 'Noyona Essentials | Shop Vegan Filipino Cosmetics - Keratin, Foundation & Lip Color',
            'description' => 'Explore Noyona Essentials cosmetics collection. Vegan, cruelty-free Filipino beauty rooted in nature. Shop keratin care, powder foundation, and dewy lip color.',
            'robots'      => $index,
        ),
        'face' => array(
            'aliases'     => array( 'face', 'face-makeup' ),
            'title'       => 'Face Makeup & Cosmetics: Foundation, Powder, & Concealer | Noyona Cosmetics and Skin Care Products OPC',
            'description' => 'Shop face makeup essentials including foundation, concealer, and powder to achieve your perfect finish. Explore shades designed to complement a wide range of skin tones.',
            'robots'      => $index,
        ),
        'lips' => array(
            'aliases'     => array( 'lips', 'lip-makeup' ),
            'title'       => 'Lip Makeup: Matte, Liquid & Creamy Lipsticks | Noyona Cosmetics and Skin Care Products OPC',
            'description' => 'Shop Noyona Essentials lip makeup for morena skin, including matte, liquid, and creamy lipsticks in nude, red, plum, and pink shades, perfect for long-lasting, tropical-ready wear.',
            'robots'      => $index,
        ),
        'eyes' => array(
            'aliases'     => array( 'eyes', 'eye-makeup' ),
            'title'       => 'Eye Makeup: Eyeliner, Brows & Brushes | Noyona Cosmetics and Skin Care Products OPC',
            'description' => 'Define your look with waterproof eyeliner, brow products, and precision brushes. Explore lashes to create styles from soft and natural to bold and dramatic.',
            'robots'      => $index,
        ),
        'hair' => array(
            'aliases'     => array( 'hair' ),
            'title'       => 'Hair Care & Styling Products | Noyona Cosmetics and Skin Care Products OPC',
            'description' => 'Nourish and style your hair with shampoos, conditioners, and treatments for healthy shine. Explore styling products from Noyona Essentials to achieve looks from sleek and polished to voluminous and textured.',
            'robots'      => $index,
        ),
        'body' => array(
            'aliases'     => array( 'body' ),
            'title'       => 'Body Care: Cleansers & Moisturizers | Noyona Cosmetics and Skin Care Products OPC',
            'description' => 'Pamper your skin with hydrating lotions and nourishing moisturizers. Explore gentle treatments from Noyona Essentials designed to deliver effective hydration and leave your skin feeling soft, smooth, and radiant.',
            'robots'      => $index,
        ),
        'partner-brands' => array(
            'aliases'     => array( 'partner-brands' ),
            'title'       => 'Noyona & Lovial | Partner Brands in Filipino Cosmetics & Malaysian Body Care',
            'description' => 'Explore Noyona and Lovial partner brands. Noyona offers vegan, cruelty-free Filipino cosmetics, while Lovial delivers trusted Malaysian body care products.',
            'robots'      => $index,
        ),
        'noyona' => array(
            'aliases'     => array( 'noyona', 'partner-brands/noyona' ),
            'title'       => 'Noyona | Vegan, Cruelty-Free Filipino Cosmetics',
            'description' => 'Discover Noyona, a Filipino cosmetics brand offering vegan, cruelty-free makeup and hair care. Shop trusted products for face, lips, eyes, and hair.',
            'robots'      => $index,
        ),
        'lovial' => array(
            'aliases'     => array( 'lovial', 'partner-brands/lovial' ),
            'title'       => 'Lovial | Trusted Malaysian Body Care Brand',
            'description' => 'Discover Lovial, a trusted Malaysian body care brand. Offering nourishing, hydrating, and brightening products designed to keep your skin soft, smooth, and radiant.',
            'robots'      => $index,
        ),
        'about' => array(
            'aliases'     => array( 'about-us', 'about' ),
            'title'       => 'About Noyona Essentials: Filipino Vegan & Cruelty-Free Cosmetics | Ethical Beauty PH',
            'description' => 'Learn about Noyona, a Filipino cosmetics brand offering vegan, cruelty-free beauty rooted in nature. Discover our story, mission, and values.',
            'robots'      => $index,
        ),
        'store-location' => array(
            'aliases'     => array( 'store-location', 'store-locations', 'location' ),
            'title'       => 'Noyona Essentials Store Locations | Filipino Vegan Cosmetics Near You',
            'description' => 'Find Noyona Essentials store locations. Explore vegan, cruelty-free Filipino cosmetics rooted in nature, available at select outlets for face, lips, eyes, and hair.',
            'robots'      => $index,
        ),
        'careers' => array(
            'aliases'     => array( 'careers' ),
            'title'       => 'Noyona Essentials Careers | Join Our Filipino Cosmetics Team',
            'description' => 'Explore career opportunities at Noyona Essentials. Join a Filipino cosmetics brand rooted in nature, offering roles in beauty, marketing, and product innovation.',
            'robots'      => $index,
        ),
        'blogs' => array(
            'aliases'     => array( 'blogs' ),
            'title'       => 'Noyona Essentials Blogs | Insights on Filipino Vegan Cosmetics',
            'description' => 'Read Noyona Essentials blogs for insights on vegan Filipino cosmetics, beauty trends, product tips, and updates from our cruelty-free brand.',
            'robots'      => $index,
        ),
        'contact' => array(
            'aliases'     => array( 'contact' ),
            'title'       => 'Noyona Essentials Contact Us | Customer Care & Support',
            'description' => 'Get in touch with Noyona Essentials. Contact our customer care team for inquiries, product support, shipping details, and trusted service in Filipino vegan cosmetics.',
            'robots'      => $index,
        ),
        'faq' => array(
            'aliases'     => array( 'faq' ),
            'title'       => 'Noyona Essentials FAQs | Product, Safety & Customer Support',
            'description' => 'Find answers in the Noyona Essentials FAQs. Learn about vegan Filipino cosmetics, product safety, usage, certifications, shipping, returns, and customer support.',
            'robots'      => $index,
        ),
        'shipping-policy' => array(
            'aliases'     => array( 'shipping-policy' ),
            'title'       => 'Noyona Essentials Shipping Policy | Delivery & Orders',
            'description' => 'Review the Noyona Essentials Shipping Policy. Learn about delivery options, order processing times, shipping fees, and trusted service for Filipino vegan cosmetics.',
            'robots'      => $index,
        ),
        'refund-policy' => array(
            'aliases'     => array( 'refund-policy' ),
            'title'       => 'Noyona Essentials Refund Policy | Returns & Customer Care',
            'description' => 'Review the Noyona Essentials Refund Policy. Learn about returns, refunds, and customer care support for vegan Filipino cosmetics rooted in nature.',
            'robots'      => $index,
        ),
        'products' => array(
            'aliases'     => array( 'products', 'product' ),
            'title'       => 'Noyona Essentials Products | Vegan Filipino Cosmetics for Face, Lips, Eyes & Hair',
            'description' => 'Shop Noyona Essentials products. Explore vegan, cruelty-free Filipino cosmetics rooted in nature, with trusted collections for face, lips, eyes, and hair care.',
            'robots'      => $index,
        ),
        'account' => array(
            'aliases'     => array( 'account', 'accounts', 'my-account' ),
            'title'       => 'Noyona Essentials Account | Manage Your Cosmetics Profile',
            'description' => 'Access your Noyona Essentials account. Manage orders, track shipping, update details, and enjoy personalized service for vegan Filipino cosmetics rooted in nature.',
            'robots'      => 'noindex, follow',
        ),
        'terms' => array(
            'aliases'     => array( 'terms-of-service', 'terms-and-policies', 'terms' ),
            'title'       => 'Noyona Essentials Terms of Service | Policies & Customer Rights',
            'description' => 'Review the Noyona Essentials Terms of Service. Learn about customer rights, policies, and trusted guidelines for shopping vegan Filipino cosmetics rooted in nature.',
            'robots'      => $index,
        ),
        'coming-soon' => array(
            'aliases'     => array( 'coming-soon' ),
            'title'       => 'Noyona Essentials | Coming Soon',
            'description' => 'New Noyona Essentials content is coming soon. Visit our shop to explore vegan, cruelty-free Filipino cosmetics rooted in nature.',
            'robots'      => 'noindex, follow',
        ),
    );
}

function noyona_get_current_seo_path() {
    if ( is_front_page() ) {
        return '';
    }

    global $wp;

    if ( isset( $wp->request ) ) {
        return trim( (string) $wp->request, '/' );
    }

    $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
    $path        = wp_parse_url( $request_uri, PHP_URL_PATH );

    return trim( (string) $path, '/' );
}

function noyona_get_current_static_seo() {
    $path = noyona_get_current_seo_path();

    foreach ( noyona_get_static_seo_pages() as $seo ) {
        $aliases = isset( $seo['aliases'] ) ? (array) $seo['aliases'] : array();

        if ( in_array( $path, $aliases, true ) ) {
            return $seo;
        }

        if ( '' !== $path && is_page( $aliases ) ) {
            return $seo;
        }
    }

    return array();
}

function noyona_get_current_static_seo_value( $key ) {
    $seo = noyona_get_current_static_seo();

    if ( isset( $seo[ $key ] ) && '' !== trim( (string) $seo[ $key ] ) ) {
        return (string) $seo[ $key ];
    }

    return '';
}

function noyona_get_custom_robots_content() {
    if ( noyona_is_site_under_development() ) {
        return 'noindex, nofollow, noarchive, nosnippet, noimageindex';
    }

    if ( is_404() || is_search() ) {
        return 'noindex, follow';
    }

    return noyona_get_current_static_seo_value( 'robots' );
}

function noyona_is_rank_math_active() {
    return defined( 'RANK_MATH_VERSION' ) || defined( 'RANK_MATH_FILE' ) || class_exists( 'RankMath' );
}

function noyona_robots_content_to_rank_math_array( $content ) {
    $robots = array();
    $items  = array_filter( array_map( 'trim', explode( ',', (string) $content ) ) );

    foreach ( $items as $item ) {
        $key            = sanitize_key( strtok( $item, ':' ) );
        $robots[ $key ] = $item;
    }

    return $robots;
}

/* ----- Avoid duplicate SEO tags when custom values or Rank Math are active. ----- */
add_action( 'wp', 'noyona_prepare_seo_head_output', 20 );
function noyona_prepare_seo_head_output() {
    if ( noyona_get_custom_robots_content() ) {
        remove_action( 'wp_head', 'wp_robots', 1 );
    }

    if ( noyona_is_rank_math_active() ) {
        remove_action( 'wp_head', '_wp_render_title_tag', 1 );
        remove_action( 'wp_head', '_block_template_render_title_tag', 1 );
    }
}

/* ----- Document titles: core fallback plus Rank Math override. ----- */
add_filter( 'pre_get_document_title', 'noyona_filter_static_document_title', 1000 );
function noyona_filter_static_document_title( $title ) {
    $custom_title = noyona_get_current_static_seo_value( 'title' );

    return $custom_title ? $custom_title : $title;
}

add_filter( 'rank_math/frontend/title', 'noyona_filter_rank_math_title', 1000 );
function noyona_filter_rank_math_title( $title ) {
    return noyona_filter_static_document_title( $title );
}

/* ----- Meta descriptions: use Rank Math when active, direct output otherwise. ----- */
add_filter( 'rank_math/frontend/description', 'noyona_filter_rank_math_description', 1000 );
function noyona_filter_rank_math_description( $description ) {
    $custom_description = noyona_get_current_static_seo_value( 'description' );

    return $custom_description ? $custom_description : $description;
}

add_action( 'wp_head', 'noyona_render_static_meta_description', 1 );
function noyona_render_static_meta_description() {
    if ( noyona_is_rank_math_active() ) {
        return;
    }

    $description = noyona_get_current_static_seo_value( 'description' );

    if ( $description ) {
        echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
    }
}

/* ----- Robots meta: use Rank Math when active, direct output otherwise. ----- */
add_filter( 'rank_math/frontend/robots', 'noyona_filter_rank_math_robots', 1000 );
function noyona_filter_rank_math_robots( $robots ) {
    $custom_robots = noyona_get_custom_robots_content();

    if ( ! $custom_robots ) {
        return $robots;
    }

    return noyona_robots_content_to_rank_math_array( $custom_robots );
}

add_action( 'wp_head', 'noyona_output_robots_meta', 1 );
function noyona_output_robots_meta() {
    if ( noyona_is_rank_math_active() ) {
        return;
    }

    $robots = noyona_get_custom_robots_content();

    if ( $robots ) {
        echo '<meta name="robots" content="' . esc_attr( $robots ) . '">' . "\n";
    }
}

