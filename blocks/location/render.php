<?php
/**
 * Render template for Noyona Store Locator Block
 */
$is_logged_in = is_user_logged_in();
$favorites = [];
if ($is_logged_in) {
    $favorites = get_user_meta(get_current_user_id(), 'noyona_store_favorites', true);
    if (!is_array($favorites)) {
        $favorites = [];
    }
}
$favorites = array_values(array_filter(array_map('absint', $favorites)));

$maybe_enqueue = true;
if (function_exists('wp_enqueue_style') && function_exists('wp_enqueue_script')) {
    wp_enqueue_style('leaflet-css');
    wp_enqueue_script('leaflet-js');
}

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => 'noyona-store-locator-wrapper',
]);
$data_attrs = sprintf(
    ' data-nsl-logged-in="%s" data-nsl-favorites="%s" data-nsl-ajax-url="%s" data-nsl-ajax-nonce="%s"',
    esc_attr($is_logged_in ? '1' : '0'),
    esc_attr($is_logged_in ? implode(',', $favorites) : ''),
    esc_attr(admin_url('admin-ajax.php')),
    esc_attr($is_logged_in ? wp_create_nonce('noyona_favorites') : '')
);

// Unique ids per block instance
$uid = wp_unique_id('nsl_');
$map_id = 'nsl-map-frame-' . $uid;
$json_id = 'nsl-stores-json-' . $uid;

if (!function_exists('nsl_time_to_minutes')) {
    function nsl_time_to_minutes($time_value)
    {
        if (!is_string($time_value) || $time_value === '') {
            return null;
        }

        $time_value = trim($time_value);

        if (preg_match('/^([01]?\d|2[0-3]):([0-5]\d)(?::[0-5]\d)?$/', $time_value, $matches)) {
            return ((int) $matches[1] * 60) + (int) $matches[2];
        }

        if (preg_match('/^([1-9]|1[0-2]):([0-5]\d)\s*([ap]m)$/i', $time_value, $matches)) {
            $hour = (int) $matches[1];
            $minute = (int) $matches[2];
            $period = strtolower($matches[3]);

            if ('pm' === $period && $hour < 12) {
                $hour += 12;
            } elseif ('am' === $period && 12 === $hour) {
                $hour = 0;
            }

            return ($hour * 60) + $minute;
        }

        return null;
    }
}

// Query Store CPT
$args = array(
    'post_type' => 'store',
    'posts_per_page' => -1,
    'post_status' => 'publish',
);
$store_query = new WP_Query($args);

$stores_data = [];

if ($store_query->have_posts()) {
    while ($store_query->have_posts()) {
        $store_query->the_post();
        $id = get_the_ID();

        // Location
        $lat = get_post_meta($id, '_store_lat', true);
        $lng = get_post_meta($id, '_store_lng', true);

        // Skip if no location
        if (!$lat || !$lng) {
            continue;
        }

        // Store Hours
        $open_time = get_post_meta($id, '_store_open_time', true);
        $close_time = get_post_meta($id, '_store_close_time', true);

        $open_minutes = nsl_time_to_minutes($open_time);
        $close_minutes = nsl_time_to_minutes($close_time);
        $now_minutes = ((int) current_time('H')) * 60 + ((int) current_time('i'));

        $is_open = false;
        if (null !== $open_minutes && null !== $close_minutes) {
            if ($open_minutes < $close_minutes) {
                $is_open = ($now_minutes >= $open_minutes && $now_minutes <= $close_minutes);
            } elseif ($open_minutes > $close_minutes) {
                $is_open = ($now_minutes >= $open_minutes || $now_minutes <= $close_minutes);
            }
        }

        $hours_display = '';
        if ($open_time && $close_time) {
            $open_display = date_i18n('g:i A', strtotime($open_time));
            $close_display = date_i18n('g:i A', strtotime($close_time));
            $hours_display = trim($open_display) . ' - ' . trim($close_display);
        }

        $status_label = $is_open ? 'Open Now' : 'Close Now';
        $status_class = $is_open ? 'is-open' : 'is-closed';

        // Products (Repeater)
        $products_meta = get_post_meta($id, '_store_products', true);
        $products_out = [];

        if (is_array($products_meta)) {
            foreach ($products_meta as $prod) {
                $products_out[] = [
                    'name' => isset($prod['name']) ? $prod['name'] : '',
                    'category' => isset($prod['category']) ? $prod['category'] : '',
                    'description' => isset($prod['description']) ? $prod['description'] : '',
                    'qty' => isset($prod['qty']) ? $prod['qty'] : 0,
                    'image' => isset($prod['image']) ? $prod['image'] : '', // Full URL
                ];
            }
        }

        // Store Image
        $store_img = get_the_post_thumbnail_url($id, 'large');

        $stores_data[] = [
            'id' => 'store-' . $id,
            'post_id' => $id,
            'title' => get_the_title(),
            'lat' => $lat,
            'lng' => $lng,
            'content' => wpautop(get_the_content()), // Details
            'image' => $store_img, // Can be used by JS
            'products' => $products_out,
            'hours' => $hours_display,
            'open_time' => $open_time,
            'close_time' => $close_time,
            'is_open' => $is_open,
            'status_label' => $status_label,
            'status_class' => $status_class,
            'updated_at' => get_the_modified_time('M j, Y g:i A'),
            'email' => '', // Not in requirements
            'phone' => '', // Not in requirements
            'address' => '', // Not in requirements but could be added
        ];
    }
    wp_reset_postdata();
} else {
    // Fallback or empty state?
}
?>

<div <?php echo $wrapper_attributes; ?><?php echo $data_attrs; ?>>
    <div class="noyona-store-locator-content">
        <div class="nsl-header">
            <div class="nsl-breadcrumbs">Home / <span class="active">Find a Store</span></div>
            <h1 class="nsl-title">Store Locator</h1>
            <p class="nsl-description">Visit us to see our products in person.</p>
        </div>

        <div class="nsl-search-box">
            <div class="nsl-input-wrapper">
                <input type="text" placeholder="Search location..." class="nsl-input">
                <span class="nsl-search-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M15.5 14H14.71L14.43 13.73C15.41 12.59 16 11.11 16 9.5C16 5.91 13.09 3 9.5 3C5.91 3 3 5.91 3 9.5C3 13.09 5.91 16 9.5 16C11.11 16 12.59 15.41 13.73 14.43L14 14.71V15.5L19 20.49L20.49 19L15.5 14ZM9.5 14C7.01 14 5 11.99 5 9.5C5 7.01 7.01 5 9.5 5C11.99 5 14 7.01 14 9.5C14 11.99 11.99 14 9.5 14Z"
                            fill="#333" />
                    </svg>
                </span>
            </div>
            <button type="button" class="nsl-use-location" aria-label="Use my current location">
                <svg width="14" height="16" viewBox="0 0 14 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M7 15C7 15 12 10.5 12 6.5C12 3.18629 9.53771 0.5 7 0.5C4.46229 0.5 2 3.18629 2 6.5C2 10.5 7 15 7 15Z"
                        stroke="currentColor" stroke-width="1.3" />
                    <circle cx="7" cy="6.5" r="2.2" stroke="currentColor" stroke-width="1.3" />
                </svg>
                Use my current location
            </button>
        </div>

        <div class="nsl-tabs" role="tablist">
            <button class="nsl-tab active" type="button" role="tab" aria-selected="true">Near Me</button>
            <?php if ($is_logged_in): ?>
                <button class="nsl-tab" type="button" role="tab" aria-selected="false">Favorites</button>
            <?php endif; ?>
        </div>

        <div class="nsl-results-list">
            <?php if (!empty($stores_data)): ?>
                <?php foreach ($stores_data as $store): ?>
                    <?php $product_count = (!empty($store['products']) && is_array($store['products'])) ? count($store['products']) : 0; ?>
                    <div class="nsl-store-item" data-store-id="<?php echo esc_attr($store['id']); ?>"
                        data-store-post-id="<?php echo esc_attr($store['post_id']); ?>"
                        data-lat="<?php echo esc_attr($store['lat']); ?>" data-lng="<?php echo esc_attr($store['lng']); ?>">
                        <?php if (!empty($store['image'])): ?>
                            <div class="nsl-store-image">
                                <img src="<?php echo esc_url($store['image']); ?>"
                                    alt="<?php echo esc_attr($store['title']); ?>" loading="lazy">
                            </div>
                        <?php endif; ?>
                        <div class="nsl-store-content">
                            <div class="nsl-store-status">
                                <span class="nsl-status-badge <?php echo esc_attr($store['status_class']); ?>">
                                    <?php echo esc_html($store['status_label']); ?>
                                </span>
                                <span class="nsl-meta-sep">&bull;</span>
                                <span class="nsl-meta-text">0.5 km away</span>
                                <?php if ($is_logged_in): ?>
                                    <button type="button" class="nsl-card-bookmark" aria-label="Save store">
                                        <svg width="14" height="18" viewBox="0 0 14 18" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path d="M1 1H13V17L7 13L1 17V1Z" stroke="#333" stroke-width="1.5" fill="none" />
                                        </svg>
                                    </button>
                                <?php endif; ?>
                            </div>
                            <div class="nsl-store-header">
                                <h3 class="nsl-store-name"><?php echo esc_html($store['title']); ?></h3>
                            </div>

                            <?php if (!empty($store['content'])): ?>
                                <div class="nsl-store-address"><?php echo wp_kses_post($store['content']); ?></div>
                            <?php endif; ?>

                            <div class="nsl-store-meta">
                                <div class="nsl-store-meta-row nsl-store-meta-row--details">
                                    <span class="nsl-rating-badge"><i class="fa-solid fa-star"></i> 4.5</span>
                                    <?php if (!empty($store['hours'])): ?>
                                        <span class="nsl-meta-sep">&bull;</span>
                                        <span class="nsl-meta-text nsl-hours-text"><?php echo esc_html($store['hours']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if (!empty($store['lat']) && !empty($store['lng'])): ?>
                                <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo esc_attr($store['lat'] . ',' . $store['lng']); ?>"
                                    target="_blank" rel="noopener" class="nsl-get-directions">Get directions ></a>
                            <?php endif; ?>
                        </div>

                        <button type="button" class="nsl-store-cta"
                            aria-label="View catalog for <?php echo esc_attr($store['title']); ?>">
                            <span class="nsl-store-cta__text">
                                <span class="nsl-store-cta__label">View catalog</span>
                                <span class="nsl-store-cta__name"><?php echo esc_html($store['title']); ?></span>
                            </span>
                            <span class="nsl-store-cta__actions">
                                <span class="nsl-store-cta__count"><?php echo esc_html($product_count); ?></span>
                                <span class="nsl-store-cta__open" aria-hidden="true">
                                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path d="M2 6h6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                                        <path d="M6 2l4 4-4 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                    </svg>
                                </span>
                            </span>
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No store information available.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="nsl-map-container">
        <div id="<?php echo esc_attr($map_id); ?>" class="nsl-map-placeholder"
            data-json-id="<?php echo esc_attr($json_id); ?>"></div>

        <script type="application/json" id="<?php echo esc_attr($json_id); ?>">
            <?php echo wp_json_encode($stores_data); ?>
        </script>
    </div>
</div>
