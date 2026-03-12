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

if (!function_exists('nsl_v2_save_comment_rating')) {
    /**
     * Persist optional star rating from frontend store review form.
     *
     * @param int $comment_id Comment ID.
     * @return void
     */
    function nsl_v2_save_comment_rating($comment_id)
    {
        if (!isset($_POST['nsl_comment_rating'])) {
            return;
        }
        $rating = (int) wp_unslash($_POST['nsl_comment_rating']);
        $rating = max(1, min(5, $rating));
        update_comment_meta($comment_id, 'rating', $rating);
    }
}
add_action('comment_post', 'nsl_v2_save_comment_rating');

$wrapper_attributes = get_block_wrapper_attributes(array(
    'class' => 'noyona-store-locator-wrapper',
));
$default_store_image = trailingslashit(get_stylesheet_directory_uri()) . 'assets/images/logo_contact.webp';

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
        $address = strtolower((string) $address);
        if ($address === '') {
            return 'Luzon';
        }

        $visayas_tokens = array('cebu', 'iloilo', 'bacolod', 'bohol', 'leyte', 'samar', 'dumaguete', 'roxas', 'aklan', 'antique', 'siquijor');
        foreach ($visayas_tokens as $token) {
            if (strpos($address, $token) !== false) {
                return 'Visayas';
            }
        }

        $mindanao_tokens = array('davao', 'cagayan de oro', 'zamboanga', 'butuan', 'surigao', 'cotabato', 'general santos', 'iligan', 'dipolog');
        foreach ($mindanao_tokens as $token) {
            if (strpos($address, $token) !== false) {
                return 'Mindanao';
            }
        }

        return 'Luzon';
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

        foreach ($region_map as $region => $tokens) {
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
        return 'Luzon Other';
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

        $island_group = get_post_meta($post_id, '_nsl_island_group', true);
        if ($island_group === '') {
            $island_group = nsl_v2_guess_island_group($address);
        }
        $island_group = ucwords(strtolower((string) $island_group));

        $region = get_post_meta($post_id, '_nsl_region', true);
        if ($region === '') {
            $region = nsl_v2_guess_region($address, $island_group);
        }

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
                $manual_rating = isset($manual_review['rating']) ? (int) $manual_review['rating'] : 5;
                $manual_rating = max(1, min(5, $manual_rating));
                if ($manual_name === '' && $manual_text === '') {
                    continue;
                }
                $review_items[] = array(
                    'name' => $manual_name !== '' ? $manual_name : __('Anonymous', 'noyona'),
                    'text' => $manual_text,
                    'date' => $manual_date,
                    'rating' => $manual_rating,
                    'source' => 'manual',
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
            $review_items[] = array(
                'name' => (string) $comment->comment_author,
                'text' => wp_strip_all_tags((string) $comment->comment_content),
                'date' => get_comment_date('M j, Y', $comment),
                'rating' => $comment_rating,
                'source' => 'public',
            );
        }

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
            'island_group' => $island_group !== '' ? $island_group : 'Luzon',
            'region' => (string) $region,
            'allow_public_reviews' => (bool) $allow_public_reviews,
            'reviews' => $review_items,
        );
    }
    wp_reset_postdata();
}
?>

<div <?php echo $wrapper_attributes; ?>>
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
        <h2 class="nsl-v2-title">Find a <span class="nsl-v2-title__accent">Store</span></h2>
        <p class="nsl-v2-subtitle">Search by store name or address and explore branches by region.</p>
    </div>

    <section class="nsl-v2-top">
        <div class="nsl-v2-map-shell">
            <div class="nsl-v2-map" id="<?php echo esc_attr($map_id); ?>"></div>
            <aside class="nsl-v2-overlay-panel">
                <div class="nsl-v2-overlay-top">
                    <div class="nsl-v2-search-wrap">
                        <div class="nsl-v2-search-row">
                            <input type="text" class="nsl-v2-search-input" placeholder="Search location or store name">
                            <button type="button" class="nsl-v2-use-location">Use My Location</button>
                        </div>
                        <div class="nsl-v2-suggestions" hidden></div>
                    </div>

                    <div class="nsl-v2-route-mode">
                        <button type="button" class="nsl-v2-route-mode-btn is-active" data-route-mode="driving">Drive</button>
                        <button type="button" class="nsl-v2-route-mode-btn" data-route-mode="walking">Walk</button>
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

    <div class="nsl-v2-review-modal" id="nsl-v2-review-modal" hidden>
        <div class="nsl-v2-review-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="nsl-v2-review-modal-title">
            <button type="button" class="nsl-v2-review-modal__close" aria-label="<?php esc_attr_e('Close', 'noyona'); ?>">×</button>
            <h3 id="nsl-v2-review-modal-title"><?php esc_html_e('Add Your Review', 'noyona'); ?></h3>
            <form method="post" action="<?php echo esc_url(site_url('/wp-comments-post.php')); ?>" class="nsl-v2-review-form">
                <input type="hidden" name="comment_post_ID" id="nsl-v2-comment-post-id" value="">
                <input type="hidden" name="comment_parent" value="0">
                <input type="hidden" name="redirect_to" value="<?php echo esc_url(get_permalink()); ?>">
                <p>
                    <label for="nsl-v2-review-author"><?php esc_html_e('Name', 'noyona'); ?></label>
                    <input type="text" id="nsl-v2-review-author" name="author" required>
                </p>
                <p>
                    <label for="nsl-v2-review-email"><?php esc_html_e('Email', 'noyona'); ?></label>
                    <input type="email" id="nsl-v2-review-email" name="email" required>
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
                    <textarea id="nsl-v2-review-comment" name="comment" rows="4" required></textarea>
                </p>
                <p>
                    <button type="submit" class="nsl-v2-review-submit"><?php esc_html_e('Submit Review', 'noyona'); ?></button>
                </p>
            </form>
        </div>
    </div>
</div>

