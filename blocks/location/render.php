<?php
/**
 * Render template for Noyona Store Locator Block (v2 UI).
 */

if (!defined('ABSPATH')) {
    exit;
}

if (function_exists('wp_enqueue_style') && function_exists('wp_enqueue_script')) {
    wp_enqueue_style('leaflet-css');
    wp_enqueue_script('leaflet-js');
}

// Rating persistence moved to inc/store-locator-reviews.php so the hook is
// registered on every request (this render template does not run during the
// /wp-comments-post.php submission).

$wrapper_attributes = get_block_wrapper_attributes(array(
    'class' => 'noyona-store-locator-wrapper',
));
$default_store_image = trailingslashit(get_stylesheet_directory_uri()) . 'assets/images/logo_contact.webp';

// Map mouse-wheel zoom is opt-in (default false) so the page scrolls normally
// while the cursor hovers the map. The block attribute is the single source of
// truth; the frontend script reads the rendered data attribute during Leaflet
// init. Touch/pinch gestures and the +/- zoom controls are unaffected.
$nsl_enable_scroll_wheel_zoom = !empty($attributes['enableScrollWheelZoom']);

if (!function_exists('nsl_v2_time_to_minutes')) {
    function nsl_v2_time_to_minutes($time_value)
    {
        if (!is_string($time_value) || $time_value === '') {
            return null;
        }
        if (!preg_match('/^([01]?\d|2[0-3]):([0-5]\d)$/', trim($time_value), $m)) {
            return null;
        }
        return ((int) $m[1] * 60) + (int) $m[2];
    }
}

if (!function_exists('nsl_v2_guess_island_group')) {
    function nsl_v2_guess_island_group($address)
    {
        return nsl_v2_island_label(nsl_v2_address_to_island_key($address));
    }
}

if (!function_exists('nsl_v2_normalize_location_text')) {
    function nsl_v2_normalize_location_text($value)
    {
        $value = strtolower(trim((string) $value));
        $value = str_replace(array('&', '-', '_', '/', ',', '.', '(', ')'), ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);
        return trim((string) $value);
    }
}

if (!function_exists('nsl_v2_normalize_island_key')) {
    function nsl_v2_normalize_island_key($value)
    {
        $value = nsl_v2_normalize_location_text($value);
        if ($value === '') {
            return '';
        }
        if (strpos($value, 'luzon') !== false) {
            return 'luzon';
        }
        if (strpos($value, 'visayas') !== false) {
            return 'visayas';
        }
        if (strpos($value, 'mindanao') !== false) {
            return 'mindanao';
        }
        return '';
    }
}

if (!function_exists('nsl_v2_island_label')) {
    function nsl_v2_island_label($island_key)
    {
        if ($island_key === 'luzon') {
            return 'Luzon';
        }
        if ($island_key === 'visayas') {
            return 'Visayas';
        }
        if ($island_key === 'mindanao') {
            return 'Mindanao';
        }
        return '';
    }
}

if (!function_exists('nsl_v2_coordinates_in_bounds')) {
    function nsl_v2_coordinates_in_bounds($lat, $lng, $bounds)
    {
        return $lat >= $bounds[0] && $lat <= $bounds[1] && $lng >= $bounds[2] && $lng <= $bounds[3];
    }
}

if (!function_exists('nsl_v2_coordinates_to_island_key')) {
    function nsl_v2_coordinates_to_island_key($lat, $lng)
    {
        if (!is_numeric($lat) || !is_numeric($lng)) {
            return '';
        }

        $lat = (float) $lat;
        $lng = (float) $lng;
        if ($lat < 4.0 || $lat > 22.5 || $lng < 116.0 || $lng > 128.5) {
            return '';
        }

        $samar_bounds = array(
            array(10.7, 12.8, 124.0, 126.4), // Samar, Eastern Samar, Northern Samar, and nearby Eastern Visayas.
        );
        foreach ($samar_bounds as $bounds) {
            if (nsl_v2_coordinates_in_bounds($lat, $lng, $bounds)) {
                return 'visayas';
            }
        }

        $luzon_bounds = array(
            array(12.0, 21.8, 119.0, 126.8), // Mainland Luzon and Bicol.
            array(7.4, 13.3, 116.5, 121.8), // MIMAROPA, including Palawan and Mindoro.
        );
        foreach ($luzon_bounds as $bounds) {
            if (nsl_v2_coordinates_in_bounds($lat, $lng, $bounds)) {
                return 'luzon';
            }
        }

        $visayas_bounds = array(
            array(9.0, 12.4, 121.8, 125.35), // Panay, Negros, Cebu, Bohol, Siquijor, Leyte.
            array(10.0, 12.8, 125.35, 126.35), // Samar and eastern Leyte.
        );
        foreach ($visayas_bounds as $bounds) {
            if (nsl_v2_coordinates_in_bounds($lat, $lng, $bounds)) {
                return 'visayas';
            }
        }

        $mindanao_bounds = array(
            array(4.4, 9.99, 121.5, 127.6), // Mindanao mainland and nearby islands.
            array(9.9, 10.6, 125.2, 126.7), // Dinagat/Surigao island area.
        );
        foreach ($mindanao_bounds as $bounds) {
            if (nsl_v2_coordinates_in_bounds($lat, $lng, $bounds)) {
                return 'mindanao';
            }
        }

        return '';
    }
}

if (!function_exists('nsl_v2_region_to_island_key')) {
    function nsl_v2_region_to_island_key($region)
    {
        $region = nsl_v2_normalize_location_text($region);
        if ($region === '') {
            return '';
        }

        $region_aliases = array(
            'luzon' => array(
                'ncr',
                'national capital region',
                'metro manila',
                'car',
                'cordillera administrative region',
                'region i',
                'region 1',
                'ilocos',
                'ilocos region',
                'region ii',
                'region 2',
                'cagayan valley',
                'region iii',
                'region 3',
                'central luzon',
                'region iv a',
                'region iva',
                'region 4 a',
                'calabarzon',
                'region iv b',
                'region ivb',
                'region 4 b',
                'mimaropa',
                'region v',
                'region 5',
                'bicol',
                'bicol region',
                'luzon other',
            ),
            'visayas' => array(
                'region vi',
                'region 6',
                'western visayas',
                'region vii',
                'region 7',
                'central visayas',
                'region viii',
                'region 8',
                'eastern visayas',
                'visayas other',
            ),
            'mindanao' => array(
                'region ix',
                'region 9',
                'zamboanga peninsula',
                'region x',
                'region 10',
                'northern mindanao',
                'region xi',
                'region 11',
                'davao region',
                'region xii',
                'region 12',
                'soccsksargen',
                'region xiii',
                'region 13',
                'caraga',
                'barmm',
                'bangsamoro autonomous region in muslim mindanao',
                'mindanao other',
            ),
        );

        $haystack = ' ' . $region . ' ';
        foreach ($region_aliases as $island_key => $aliases) {
            foreach ($aliases as $alias) {
                $alias = nsl_v2_normalize_location_text($alias);
                if ($region === $alias || strpos($haystack, ' ' . $alias . ' ') !== false) {
                    return $island_key;
                }
            }
        }

        return '';
    }
}

if (!function_exists('nsl_v2_address_to_island_key')) {
    function nsl_v2_address_to_island_key($address)
    {
        $address = nsl_v2_normalize_location_text($address);
        if ($address === '') {
            return '';
        }

        $token_map = array(
            'visayas' => array('cebu', 'iloilo', 'bacolod', 'bohol', 'leyte', 'samar', 'eastern samar', 'northern samar', 'western samar', 'calbayog', 'catarman', 'dolores eastern samar', 'dumaguete', 'roxas', 'aklan', 'antique', 'capiz', 'guimaras', 'negros occidental', 'negros oriental', 'siquijor', 'tacloban', 'ormoc'),
            'mindanao' => array('davao', 'cagayan de oro', 'zamboanga', 'butuan', 'surigao', 'cotabato', 'general santos', 'iligan', 'dipolog', 'pagadian', 'misamis', 'bukidnon', 'camiguin', 'lanao', 'agusan', 'sarangani', 'south cotabato', 'sultan kudarat'),
            'luzon' => array('manila', 'quezon city', 'makati', 'pasig', 'taguig', 'pasay', 'mandaluyong', 'marikina', 'caloocan', 'muntinlupa', 'paranaque', 'las pinas', 'san juan', 'malabon', 'navotas', 'valenzuela', 'pateros', 'bulacan', 'pampanga', 'tarlac', 'zambales', 'nueva ecija', 'aurora', 'bataan', 'laguna', 'cavite', 'batangas', 'rizal', 'quezon province', 'ilocos', 'la union', 'pangasinan', 'albay', 'camarines', 'catanduanes', 'masbate', 'sorsogon', 'palawan', 'mindoro', 'marinduque', 'romblon'),
        );

        foreach ($token_map as $island_key => $tokens) {
            foreach ($tokens as $token) {
                if (strpos($address, nsl_v2_normalize_location_text($token)) !== false) {
                    return $island_key;
                }
            }
        }

        return '';
    }
}

if (!function_exists('nsl_v2_guess_region')) {
    function nsl_v2_guess_region($address, $island_group)
    {
        $address = strtolower((string) $address);
        if ($address === '') {
            return 'Uncategorized';
        }

        $region_map = array(
            'NCR' => array('manila', 'quezon city', 'makati', 'pasig', 'taguig', 'pasay', 'mandaluyong', 'marikina', 'caloocan', 'muntinlupa', 'paranaque', 'las pinas', 'san juan', 'malabon', 'navotas', 'valenzuela', 'pateros'),
            'Central Luzon' => array('bulacan', 'pampanga', 'tarlac', 'zambales', 'nueva ecija', 'aurora', 'bataan'),
            'CALABARZON' => array('laguna', 'cavite', 'batangas', 'rizal', 'quezon province'),
            'Ilocos' => array('ilocos', 'la union', 'pangasinan'),
            'Bicol' => array('albay', 'camarines', 'catanduanes', 'masbate', 'sorsogon'),
            'Western Visayas' => array('iloilo', 'bacolod', 'negros occidental', 'aklan', 'antique', 'capiz', 'guimaras'),
            'Central Visayas' => array('cebu', 'bohol', 'negros oriental', 'siquijor'),
            'Eastern Visayas' => array('leyte', 'samar', 'biliran', 'ormoc', 'tacloban'),
            'Davao Region' => array('davao'),
            'Northern Mindanao' => array('cagayan de oro', 'misamis', 'bukidnon', 'camiguin', 'lanao'),
            'SOCCSKSARGEN' => array('general santos', 'south cotabato', 'sultan kudarat', 'sarangani', 'cotabato'),
            'Zamboanga Peninsula' => array('zamboanga', 'dipolog', 'pagadian'),
            'Caraga' => array('butuan', 'surigao', 'agusan'),
        );
        $region_islands = array(
            'NCR' => 'Luzon',
            'Central Luzon' => 'Luzon',
            'CALABARZON' => 'Luzon',
            'Ilocos' => 'Luzon',
            'Bicol' => 'Luzon',
            'Western Visayas' => 'Visayas',
            'Central Visayas' => 'Visayas',
            'Eastern Visayas' => 'Visayas',
            'Davao Region' => 'Mindanao',
            'Northern Mindanao' => 'Mindanao',
            'SOCCSKSARGEN' => 'Mindanao',
            'Zamboanga Peninsula' => 'Mindanao',
            'Caraga' => 'Mindanao',
        );

        foreach ($region_map as $region => $tokens) {
            if ($island_group !== '' && isset($region_islands[$region]) && $region_islands[$region] !== $island_group) {
                continue;
            }
            foreach ($tokens as $token) {
                if (strpos($address, $token) !== false) {
                    return $region;
                }
            }
        }

        if ($island_group === 'Visayas') {
            return 'Visayas Other';
        }
        if ($island_group === 'Mindanao') {
            return 'Mindanao Other';
        }
        if ($island_group === 'Luzon') {
            return 'Luzon Other';
        }
        return 'Uncategorized';
    }
}

$store_query = new WP_Query(array(
    'post_type' => 'store',
    'posts_per_page' => -1,
    'post_status' => 'publish',
));

$stores_data = array();
$uid = wp_unique_id('nsl_v2_');
$map_id = 'nsl-map-' . $uid;
$json_id = 'nsl-data-' . $uid;

// Store IDs the current logged-in user has already reviewed (approved or
// pending only; spam/trash excluded so a removed review frees the slot).
// One query for all of the user's store reviews, mapped by store ID.
// Administrators are exempt from the one-review-per-store rule, so the map
// stays empty for them and the Add Review button remains visible everywhere.
$nsl_user_reviewed_store_ids = array();
if (is_user_logged_in() && !current_user_can('manage_options')) {
    $nsl_user_review_comments = get_comments(array(
        'user_id' => get_current_user_id(),
        'type'    => 'comment',
        'status'  => 'all',
        'fields'  => 'all',
    ));
    foreach ($nsl_user_review_comments as $nsl_user_review_comment) {
        $nsl_comment_approved = (string) $nsl_user_review_comment->comment_approved;
        // '1' = approved, '0' = pending. Skip 'spam' and 'trash'.
        if ($nsl_comment_approved !== '1' && $nsl_comment_approved !== '0') {
            continue;
        }
        $nsl_user_reviewed_store_ids[(int) $nsl_user_review_comment->comment_post_ID] = true;
    }
}

/**
 * Review sort mode.
 *
 * Supported:
 *  - rating_desc : Rating DESC, then Date DESC.
 *  - rating_asc  : Rating ASC, then Date DESC.
 *  - newest      : Date DESC only.
 *  - default     : Original order (manual reviews in admin order, then customer
 *                  reviews newest-first); no combined sort applied.
 */
$nsl_review_sort_mode = 'rating_desc';

if ($store_query->have_posts()) {
    while ($store_query->have_posts()) {
        $store_query->the_post();
        $post_id = get_the_ID();

        $lat = get_post_meta($post_id, '_nsl_lat', true);
        $lng = get_post_meta($post_id, '_nsl_lng', true);
        if ($lat === '') {
            $lat = get_post_meta($post_id, '_store_lat', true);
        }
        if ($lng === '') {
            $lng = get_post_meta($post_id, '_store_lng', true);
        }

        if ($lat === '' || $lng === '') {
            continue;
        }

        $address = get_post_meta($post_id, '_nsl_store_address', true);
        $description = get_post_meta($post_id, '_nsl_store_description', true);
        if ($description === '') {
            $description = get_the_content(null, false, $post_id);
        }

        $tel = get_post_meta($post_id, '_nsl_tel_phone', true);
        $phone = get_post_meta($post_id, '_nsl_phone_number', true);
        $email = get_post_meta($post_id, '_nsl_email', true);

        $open_time = get_post_meta($post_id, '_nsl_store_open_time', true);
        $close_time = get_post_meta($post_id, '_nsl_store_close_time', true);
        if ($open_time === '') {
            $open_time = get_post_meta($post_id, '_store_open_time', true);
        }
        if ($close_time === '') {
            $close_time = get_post_meta($post_id, '_store_close_time', true);
        }

        $open_minutes = nsl_v2_time_to_minutes($open_time);
        $close_minutes = nsl_v2_time_to_minutes($close_time);
        $now_minutes = ((int) current_time('H')) * 60 + ((int) current_time('i'));

        $is_open = false;
        if ($open_minutes !== null && $close_minutes !== null) {
            if ($open_minutes <= $close_minutes) {
                $is_open = ($now_minutes >= $open_minutes && $now_minutes <= $close_minutes);
            } else {
                $is_open = ($now_minutes >= $open_minutes || $now_minutes <= $close_minutes);
            }
        }

        $hours = '';
        if ($open_time !== '' && $close_time !== '') {
            $hours = date_i18n('g:i A', strtotime($open_time)) . ' - ' . date_i18n('g:i A', strtotime($close_time));
        }

        $image = get_the_post_thumbnail_url($post_id, 'large');
        $gallery_urls = array();
        if ($image) {
            $gallery_urls[] = (string) $image;
        }

        $gallery_ids = get_post_meta($post_id, '_nsl_gallery_ids', true);
        if (is_array($gallery_ids)) {
            foreach ($gallery_ids as $gallery_id) {
                $gallery_url = wp_get_attachment_image_url((int) $gallery_id, 'large');
                if ($gallery_url) {
                    $gallery_urls[] = (string) $gallery_url;
                }
            }
        }

        if (!$image) {
            if (!empty($gallery_urls[0])) {
                $image = $gallery_urls[0];
            }
        }
        if (!$image) {
            $image = $default_store_image;
        }
        if (empty($gallery_urls)) {
            $gallery_urls[] = (string) $image;
        }
        $gallery_urls = array_values(array_unique(array_filter($gallery_urls)));

        $island_key = nsl_v2_coordinates_to_island_key($lat, $lng);
        if ($island_key === '') {
            $island_key = nsl_v2_normalize_island_key(get_post_meta($post_id, '_nsl_island_group', true));
        }

        $region = trim((string) get_post_meta($post_id, '_nsl_region', true));
        if ($region === '') {
            $region = trim((string) get_post_meta($post_id, '_nsl_region_name', true));
        }
        if ($region === '') {
            $region = nsl_v2_guess_region($address, nsl_v2_island_label($island_key));
        }
        if ($island_key === '') {
            $island_key = nsl_v2_region_to_island_key($region);
        }
        if ($island_key === '') {
            $island_key = nsl_v2_address_to_island_key($address);
        }
        $island_group = nsl_v2_island_label($island_key);

        $allow_public_reviews = ('open' === get_post_field('comment_status', $post_id));
        $review_items = array();

        $manual_reviews = get_post_meta($post_id, '_nsl_manual_reviews', true);
        if (is_array($manual_reviews)) {
            foreach ($manual_reviews as $manual_review) {
                if (!is_array($manual_review)) {
                    continue;
                }
                $manual_name = isset($manual_review['name']) ? sanitize_text_field((string) $manual_review['name']) : '';
                $manual_text = isset($manual_review['text']) ? sanitize_textarea_field((string) $manual_review['text']) : '';
                $manual_date = isset($manual_review['date']) ? sanitize_text_field((string) $manual_review['date']) : '';
                $manual_rating = isset($manual_review['rating']) ? (int) $manual_review['rating'] : 3;
                $manual_rating = max(1, min(5, $manual_rating));
                if ($manual_name === '' && $manual_text === '') {
                    continue;
                }
                $manual_ts = $manual_date !== '' ? strtotime($manual_date) : false;
                $review_items[] = array(
                    'name' => $manual_name !== '' ? $manual_name : __('Anonymous', 'noyona'),
                    'text' => $manual_text,
                    'date' => $manual_date,
                    'rating' => $manual_rating,
                    'source' => 'manual',
                    '_ts' => $manual_ts ? (int) $manual_ts : 0,
                );
            }
        }

        $public_comments = get_comments(array(
            'post_id' => $post_id,
            'status' => 'approve',
            'type' => 'comment',
            'orderby' => 'comment_date_gmt',
            'order' => 'DESC',
        ));

        foreach ($public_comments as $comment) {
            $comment_rating = (int) get_comment_meta($comment->comment_ID, 'rating', true);
            if ($comment_rating < 1 || $comment_rating > 5) {
                $comment_rating = 5;
            }
            $comment_ts = strtotime((string) $comment->comment_date_gmt);
            $review_items[] = array(
                'name' => (string) $comment->comment_author,
                'text' => wp_strip_all_tags((string) $comment->comment_content),
                'date' => get_comment_date('M j, Y', $comment),
                'rating' => $comment_rating,
                'source' => 'public',
                '_ts' => $comment_ts ? (int) $comment_ts : 0,
            );
        }

        // Order reviews based on $nsl_review_sort_mode (see config above). A
        // stable original-index tiebreaker keeps manual reviews with unparseable
        // dates in their existing admin order rather than reordering randomly.
        foreach ($review_items as $review_index => $review_item) {
            $review_items[$review_index]['_idx'] = $review_index;
        }
        if ('default' !== $nsl_review_sort_mode) {
            usort($review_items, function ($a, $b) use ($nsl_review_sort_mode) {
                if ('newest' !== $nsl_review_sort_mode && $a['rating'] !== $b['rating']) {
                    return 'rating_asc' === $nsl_review_sort_mode
                        ? ($a['rating'] <=> $b['rating'])
                        : ($b['rating'] <=> $a['rating']);
                }
                if ($a['_ts'] !== $b['_ts']) {
                    return $b['_ts'] <=> $a['_ts'];
                }
                return $a['_idx'] <=> $b['_idx'];
            });
        }
        foreach ($review_items as $review_index => $review_item) {
            unset($review_items[$review_index]['_ts'], $review_items[$review_index]['_idx']);
        }
        $review_items = array_values($review_items);

        $stores_data[] = array(
            'id' => (string) $post_id,
            'title' => html_entity_decode((string) get_the_title($post_id), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'address' => (string) $address,
            'description' => wp_kses_post(wpautop((string) $description)),
            'lat' => (float) $lat,
            'lng' => (float) $lng,
            'image' => (string) $image,
            'gallery' => $gallery_urls,
            'tel' => (string) $tel,
            'phone' => (string) $phone,
            'email' => (string) $email,
            'open_time' => (string) $open_time,
            'close_time' => (string) $close_time,
            'hours' => (string) $hours,
            'is_open' => (bool) $is_open,
            'status_label' => $is_open ? 'Open Now' : 'Closed',
            'rating' => (float) get_post_meta($post_id, '_nsl_rating', true) ?: 4.5,
            'island' => $island_key,
            'island_group' => $island_group,
            'region' => (string) $region,
            'allow_public_reviews' => (bool) $allow_public_reviews,
            'user_has_reviewed' => isset($nsl_user_reviewed_store_ids[$post_id]),
            'reviews' => $review_items,
        );
    }
    wp_reset_postdata();
}
?>

<div <?php echo $wrapper_attributes; ?> data-nsl-logged-in="<?php echo is_user_logged_in() ? '1' : '0'; ?>">
    <nav class="nsl-v2-breadcrumbs" aria-label="<?php esc_attr_e('Breadcrumb', 'noyona'); ?>">
        <a class="nsl-v2-breadcrumbs__home" href="<?php echo esc_url(home_url('/')); ?>">
            <?php esc_html_e('Home', 'noyona'); ?>
        </a>
        <span class="nsl-v2-breadcrumbs__sep" aria-hidden="true">/</span>
        <span class="nsl-v2-breadcrumbs__current">
            <?php esc_html_e('Find a Store', 'noyona'); ?>
        </span>
    </nav>

    <div class="nsl-v2-header">
        <h1 class="nsl-v2-title">Find a <span class="nsl-v2-title__accent">Store</span></h1>
        <p class="nsl-v2-subtitle">Search by store name or address and explore branches by region.</p>
    </div>

    <section class="nsl-v2-top">
        <div class="nsl-v2-map-shell">
            <div class="nsl-v2-map" id="<?php echo esc_attr($map_id); ?>" data-scroll-wheel-zoom="<?php echo $nsl_enable_scroll_wheel_zoom ? 'true' : 'false'; ?>"></div>
            <aside class="nsl-v2-overlay-panel">
                <div class="nsl-v2-overlay-top">
                    <div class="nsl-v2-search-wrap">
                        <div class="nsl-v2-search-row">
                            <input type="text" class="nsl-v2-search-input" placeholder="Search location or store name">
                        </div>
                        <div class="nsl-v2-suggestions" hidden></div>
                    </div>
                </div>

                <div class="nsl-v2-selected-panel">
                    <div class="nsl-v2-selected-empty">Select a store from suggestions or the list to view full details.</div>
                </div>
            </aside>
        </div>
    </section>

    <section class="nsl-v2-bottom">
        <div class="nsl-v2-bottom-top">
            <div class="nsl-v2-parent-filter-list"></div>
        </div>
        <div class="nsl-v2-bottom-row">
            <aside class="nsl-v2-filter-panel">
                <div class="nsl-v2-compact-filters" aria-label="<?php esc_attr_e('Store filters', 'noyona'); ?>">
                    <label class="nsl-v2-compact-filter">
                        <span>Island Group</span>
                        <select class="nsl-v2-island-select"></select>
                    </label>
                    <label class="nsl-v2-compact-filter">
                        <span>Region</span>
                        <select class="nsl-v2-region-select"></select>
                    </label>
                    <label class="nsl-v2-compact-filter">
                        <span>Quick Filter</span>
                        <select class="nsl-v2-quick-filter-select"></select>
                    </label>
                </div>
                <h3 class="nsl-v2-bottom-title">Region Filter</h3>
                <div class="nsl-v2-child-filter-list"></div>
                <h3 class="nsl-v2-bottom-title nsl-v2-bottom-title--sub">Quick Filters</h3>
                <div class="nsl-v2-extra-filter-list"></div>
            </aside>
            <div class="nsl-v2-store-panel">
                <div class="nsl-v2-store-count"></div>
                <div class="nsl-v2-store-grid"></div>
                <div class="nsl-v2-store-pagination"></div>
            </div>
        </div>
    </section>

    <script type="application/json" id="<?php echo esc_attr($json_id); ?>"><?php echo wp_json_encode($stores_data); ?></script>

    <?php if (is_user_logged_in()) : ?>
    <div class="nsl-v2-review-modal" id="nsl-v2-review-modal" hidden>
        <div class="nsl-v2-review-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="nsl-v2-review-modal-title">
            <button type="button" class="nsl-v2-review-modal__close" aria-label="<?php esc_attr_e('Close', 'noyona'); ?>">×</button>
            <h3 id="nsl-v2-review-modal-title"><?php esc_html_e('Add Your Review', 'noyona'); ?></h3>
            <form method="post" action="<?php echo esc_url(site_url('/wp-comments-post.php')); ?>" class="nsl-v2-review-form">
                <input type="hidden" name="comment_post_ID" id="nsl-v2-comment-post-id" value="">
                <input type="hidden" name="comment_parent" value="0">
                <input type="hidden" name="redirect_to" value="<?php echo esc_url(get_permalink()); ?>">
                <input type="hidden" name="nsl_v2_store_review" value="1">
                <?php wp_nonce_field('noyona_location_review_submit', 'noyona_location_review_nonce'); ?>
                <p>
                    <label for="nsl-v2-review-author"><?php esc_html_e('Name', 'noyona'); ?></label>
                    <input type="text" id="nsl-v2-review-author" name="author" required>
                </p>
                <p>
                    <label for="nsl-v2-review-email"><?php esc_html_e('Email', 'noyona'); ?></label>
                    <input type="email" id="nsl-v2-review-email" name="email">
                </p>
                <p>
                    <label for="nsl-v2-review-rating"><?php esc_html_e('Rating', 'noyona'); ?></label>
                    <select id="nsl-v2-review-rating" name="nsl_comment_rating">
                        <option value="5">5</option>
                        <option value="4">4</option>
                        <option value="3">3</option>
                        <option value="2">2</option>
                        <option value="1">1</option>
                    </select>
                </p>
                <p>
                    <label for="nsl-v2-review-comment"><?php esc_html_e('Review', 'noyona'); ?></label>
                    <textarea id="nsl-v2-review-comment" name="comment" rows="4"></textarea>
                </p>
                <p>
                    <button type="submit" class="nsl-v2-review-submit"><?php esc_html_e('Submit Review', 'noyona'); ?></button>
                </p>
            </form>
        </div>
    </div>
    <script>
        (function () {
            var reviewForm = document.querySelector('.nsl-v2-review-form');
            if (!reviewForm || typeof window.fetch !== 'function') return;

            var notFoundUrl = <?php echo wp_json_encode(esc_url(home_url('/404'))); ?>;

            function redirectTo404() {
                window.location.href = notFoundUrl;
            }

            reviewForm.addEventListener('submit', function (event) {
                event.preventDefault();

                var formData = new FormData(reviewForm);
                fetch(reviewForm.action, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin',
                    redirect: 'follow'
                })
                    .then(function (response) {
                        if (!response.ok) {
                            redirectTo404();
                            return null;
                        }

                        var finalUrl = String(response.url || '');
                        if (finalUrl.indexOf('/wp-comments-post.php') === -1) {
                            window.location.href = finalUrl || reviewForm.action;
                            return null;
                        }

                        return response.text().then(function (html) {
                            if (/comment submission failure|<strong>\s*error:/i.test(html)) {
                                redirectTo404();
                                return;
                            }
                            window.location.href = finalUrl;
                        });
                    })
                    .catch(function () {
                        redirectTo404();
                    });
            });
        })();
    </script>
    <?php endif; ?>
</div>

