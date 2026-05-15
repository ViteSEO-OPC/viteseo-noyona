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

/* ----- Robots meta tag output ----- */
/**
 * Output robots meta tag.
 */
add_action( 'wp_head', 'noyona_output_robots_meta', 1 );
function noyona_output_robots_meta() {
    // In production, allow SEO plugins/core to manage robots directives.
    if ( ! noyona_is_site_under_development() ) {
        return;
    }

    echo '<meta name="robots" content="noindex, nofollow, noarchive, nosnippet, noimageindex">' . "\n";
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

/* ----- Static document titles ----- */
/* =================================================
 * SEO: DOCUMENT TITLES
 * ================================================= */
add_filter( 'pre_get_document_title', 'noyona_filter_static_document_titles' );
function noyona_filter_static_document_titles( $title ) {
    /* ---- STATIC PAGE TITLES ---- */
    if (is_front_page()) return 'Noyona Essentials | Filipino Vegan Cosmetics Rooted in Nature';
    if (is_page('shop')) return 'Noyona Essentials | Shop Vegan Filipino Cosmetics – Keratin, Foundation & Lip Color';
    if (is_page('face-makeup')) return 'Face Makeup & Cosmetics: Foundation, Powder, & Concealer | Noyona Cosmetics and Skin Care Products OPC';
    if (is_page('lip-makeup')) return 'Lip Makeup: Matte, Liquid & Creamy Lipsticks | Noyona Cosmetics and Skin Care Products OPC';
    if (is_page('eye-makeup')) return 'Eye Makeup: Eyeliner, Brows & Brushes | Noyona Cosmetics and Skin Care Products OPC';
    if (is_page('hair')) return 'Hair Care & Styling Products | Noyona Cosmetics and Skin Care Products OPC';
    if (is_page('body')) return 'Body Care: Cleansers & Moisturizers | Noyona Cosmetics and Skin Care Products OPC';
    if (is_page('partner-brands')) return 'Noyona & Lovial | Partner Brands in Filipino Cosmetics & Malaysian Body Care';
    if (is_page('partner-brands/noyona')) return 'Noyona | Vegan, Cruelty-Free Filipino Cosmetics';
    if (is_page('partner-brands/lovial')) return 'Lovial | Trusted Malaysian Body Care Brand';
    if (is_page('about-noyona-essentials')) return 'About Noyona Essentials: Filipino Vegan & Cruelty-Free Cosmetics | Ethical Beauty PH';
    if (is_page('store-locations')) return 'Noyona Essentials Store Locations | Filipino Vegan Cosmetics Near You';
    if (is_page('careers')) return 'Noyona Essentials Careers | Join Our Filipino Cosmetics Team';
    if (is_page('blogs')) return 'Noyona Essentials Blogs | Insights on Filipino Vegan Cosmetics';
    if (is_page('contact')) return 'Noyona Essentials Contact Us | Customer Care & Support';
    if (is_page('faq')) return 'Noyona Essentials FAQs | Product, Safety & Customer Support';
    if (is_page('shipping-policy')) return 'Noyona Essentials Shipping Policy | Delivery & Orders';
    if (is_page('refund-policy')) return 'Noyona Essentials Refund Policy | Returns & Customer Care';
    if (is_page('products')) return 'Noyona Essentials Products | Vegan Filipino Cosmetics for Face, Lips, Eyes & Hair';
    if (is_page(array('accounts', 'account'))) return 'Noyona Essentials Account | Manage Your Cosmetics Profile';
    if (is_page(array('terms-of-service', 'terms-of-services'))) return 'Noyona Essentials Terms of Service | Policies & Customer Rights';
    return $title;
}

/* ----- Static meta description tags ----- */
/* =================================================
 * SEO: META DESCRIPTIONS
 * ================================================= */
add_action( 'wp_head', 'noyona_render_static_meta_descriptions' );
function noyona_render_static_meta_descriptions() {

    if (is_front_page()) {
        echo '<meta name="description" content="Discover Noyona Essentials, a Filipino cosmetics brand rooted in nature. Vegan, cruelty-free products crafted for lasting beauty and everyday confidence.">';
        return;
    }

    if (is_page('shop')) {
        echo '<meta name="description" content="Explore Noyona Essentials cosmetics collection. Vegan, cruelty-free Filipino beauty rooted in nature. Shop keratin care, powder foundation, and dewy lip color.">';
        return;    
    }
    if (is_page('face-makeup')) {
        echo '<meta name="description" content="Shop face makeup essentials including foundation, concealer, and powder to achieve your perfect finish. Explore shades designed to complement a wide range of skin tones.">';
        return; 
    }
    if (is_page('lip-makeup')) {
        echo '<meta name="description" content="Shop Noyona Essentials lip makeup for morena skin, including matte, liquid, and creamy lipsticks in nude, red, plum, and pink shades, perfect for long-lasting, tropical-ready wear.">';
        return; 
    }
    if (is_page('eye-makeup')) {
        echo '<meta name="description" content="Define your look with waterproof eyeliner, brow products, and precision brushes. Explore lashes to create styles from soft and natural to bold and dramatic.">';
        return; 
    }
    if (is_page('hair')) {
        echo '<meta name="description" content="Nourish and style your hair with shampoos, conditioners, and treatments for healthy shine. Explore styling products from Noyona Essentials to achieve looks from sleek and polished to voluminous and textured.">';
        return; 
    }
    if (is_page('body')) {
        echo '<meta name="description" content="Pamper your skin with hydrating lotions and nourishing moisturizers. Explore gentle treatments from Noyona Essentials designed to deliver effective hydration and leave your skin feeling soft, smooth, and radiant.">';
        return; 
    }
    if (is_page('partner-brands')) {
        echo '<meta name="description" content="Explore Noyona and Lovial partner brands. Noyona offers vegan, cruelty-free Filipino cosmetics, while Lovial delivers trusted Malaysian body care products.">';
        return; 
    }
    if (is_page('partner-brands/noyona')) {
        echo '<meta name="description" content="Discover Noyona, a Filipino cosmetics brand offering vegan, cruelty-free makeup and hair care. Shop trusted products for face, lips, eyes, and hair.">';
        return; 
    }
    if (is_page('partner-brands/lovial')) {
        echo '<meta name="description" content="Discover Lovial, a trusted Malaysian body care brand. Offering nourishing, hydrating, and brightening products designed to keep your skin soft, smooth, and radiant.">';
        return; 
    }
    if (is_page('about-noyona-essentials')) {
        echo '<meta name="description" content="Learn about Noyona, a Filipino cosmetics brand offering vegan, cruelty-free beauty rooted in nature. Discover our story, mission, and values.">';
        return; 
    }
    if (is_page('store-locations')) {
        echo '<meta name="description" content="Find Noyona Essentials store locations. Explore vegan, cruelty-free Filipino cosmetics rooted in nature, available at select outlets for face, lips, eyes, and hair.">';
        return; 
    }
    if (is_page('careers')) {
        echo '<meta name="description" content="Explore career opportunities at Noyona Essentials. Join a Filipino cosmetics brand rooted in nature, offering roles in beauty, marketing, and product innovation.">';
        return; 
    }
    if (is_page('blogs')) {
        echo '<meta name="description" content="Read Noyona Essentials blogs for insights on vegan Filipino cosmetics, beauty trends, product tips, and updates from our cruelty-free brand.">';
        return; 
    }
    if (is_page('contact')) {
        echo '<meta name="description" content="Get in touch with Noyona Essentials. Contact our customer care team for inquiries, product support, shipping details, and trusted service in Filipino vegan cosmetics.">';
        return; 
    }
    if (is_page('faq')) {
        echo '<meta name="description" content="Find answers in the Noyona Essentials FAQs. Learn about vegan Filipino cosmetics, product safety, usage, certifications, shipping, returns, and customer support.">';
        return; 
    }
    if (is_page('shipping-policy')) {
        echo '<meta name="description" content="Review the Noyona Essentials Shipping Policy. Learn about delivery options, order processing times, shipping fees, and trusted service for Filipino vegan cosmetics.">';
        return; 
    }
    if (is_page('refund-policy')) {
        echo '<meta name="description" content="Review the Noyona Essentials Refund Policy. Learn about returns, refunds, and customer care support for vegan Filipino cosmetics rooted in nature.">';
        return; 
    }
    if (is_page('products')) {
        echo '<meta name="description" content="Shop Noyona Essentials products. Explore vegan, cruelty-free Filipino cosmetics rooted in nature, with trusted collections for face, lips, eyes, and hair care.">';
        return; 
    }
    if (is_page(array('accounts', 'account'))) {
        echo '<meta name="description" content="Access your Noyona Essentials account. Manage orders, track shipping, update details, and enjoy personalized service for vegan Filipino cosmetics rooted in nature.">';
        return; 
    }
    if (is_page(array('terms-of-service', 'terms-of-services'))) {
        echo '<meta name="description" content="Review the Noyona Essentials Terms of Service. Learn about customer rights, policies, and trusted guidelines for shopping vegan Filipino cosmetics rooted in nature.">';
        return;
    }
}

