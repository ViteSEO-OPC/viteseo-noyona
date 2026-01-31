<?php
/**
 * Plugin Name: Store Manager Plugin
 * Plugin URI:  https://example.com/store-manager-plugin
 * Description: A plugin to manage Store Details and Products, integrated with the Contact Block.
 * Version:     1.0.0
 * Author:      Noyona Team
 * Author URI:  https://example.com
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: noyona
 * Domain Path: /languages
 *
 * @package NOYONA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Store_Manager_Plugin
{

    /**
     * Instance of this class.
     *
     * @var object
     */
    protected static $instance = null;

    /**
     * Return an instance of this class.
     *
     * @return object A single instance of this class.
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        add_action('init', array($this, 'register_store_cpt'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_box_data'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Register Custom Post Type 'store'
     */
    public function register_store_cpt()
    {
        $labels = array(
            'name' => _x('Stores', 'Post Type General Name', 'noyona'),
            'singular_name' => _x('Store', 'Post Type Singular Name', 'noyona'),
            'menu_name' => __('Stores', 'noyona'),
            'name_admin_bar' => __('Store', 'noyona'),
            'archives' => __('Store Archives', 'noyona'),
            'attributes' => __('Store Attributes', 'noyona'),
            'all_items' => __('All Stores', 'noyona'),
            'add_new_item' => __('Add New Store', 'noyona'),
            'add_new' => __('Add New', 'noyona'),
            'new_item' => __('New Store', 'noyona'),
            'edit_item' => __('Edit Store', 'noyona'),
            'update_item' => __('Update Store', 'noyona'),
            'view_item' => __('View Store', 'noyona'),
            'view_items' => __('View Stores', 'noyona'),
            'search_items' => __('Search Store', 'noyona'),
            'not_found' => __('Not found', 'noyona'),
            'not_found_in_trash' => __('Not found in Trash', 'noyona'),
            'featured_image' => __('Store Image', 'noyona'),
            'set_featured_image' => __('Set store image', 'noyona'),
            'remove_featured_image' => __('Remove store image', 'noyona'),
            'use_featured_image' => __('Use as store image', 'noyona'),
        );
        $args = array(
            'label' => __('Store', 'noyona'),
            'description' => __('Store Locations', 'noyona'),
            'labels' => $labels,
            'supports' => array('title', 'editor', 'thumbnail'), // Title, Details(Editor), Store Image(Thumb)
            'hierarchical' => false,
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_position' => 5,
            'show_in_admin_bar' => true,
            'show_in_nav_menus' => true,
            'can_export' => true,
            'has_archive' => false,
            'exclude_from_search' => false,
            'publicly_queryable' => true,
            'capability_type' => 'post',
            'menu_icon' => 'dashicons-store',
        );
        register_post_type('store', $args);
    }

    /**
     * Add Meta Boxes
     */
    public function add_meta_boxes()
    {
        add_meta_box(
            'store_location_meta',
            __('Store Location', 'noyona'),
            array($this, 'render_location_meta_box'),
            'store',
            'normal',
            'high'
        );

        add_meta_box(
            'store_products_meta',
            __('Store Products', 'noyona'),
            array($this, 'render_products_meta_box'),
            'store',
            'normal',
            'high'
        );
    }

    /**
     * Render Location Meta Box (Map)
     */
    public function render_location_meta_box($post)
    {
        wp_nonce_field('store_save_data', 'store_nonce');
        $lat = get_post_meta($post->ID, '_store_lat', true);
        $lng = get_post_meta($post->ID, '_store_lng', true);
        $open_time = get_post_meta($post->ID, '_store_open_time', true);
        $close_time = get_post_meta($post->ID, '_store_close_time', true);

        // Default to Makati if empty
        if (!$lat)
            $lat = '14.5547';
        if (!$lng)
            $lng = '121.0244';
        ?>
        <div id="store-map-picker" style="height: 400px; width: 100%; margin-bottom: 10px;"></div>
        <p class="description"><?php _e('Click on the map to set the store location.', 'noyona'); ?></p>

        <div style="display: flex; gap: 10px;">
            <div>
                <label for="store_lat"><?php _e('Latitude', 'noyona'); ?></label> <br>
                <input type="text" id="store_lat" name="store_lat" value="<?php echo esc_attr($lat); ?>" readonly
                    style="background: #f0f0f1; border: 1px solid #ccc;">
            </div>
            <div>
                <label for="store_lng"><?php _e('Longitude', 'noyona'); ?></label> <br>
                <input type="text" id="store_lng" name="store_lng" value="<?php echo esc_attr($lng); ?>" readonly
                    style="background: #f0f0f1; border: 1px solid #ccc;">
            </div>
        </div>

        <div style="display: flex; gap: 10px; margin-top: 12px;">
            <div>
                <label for="store_open_time"><?php _e('Opening Time', 'noyona'); ?></label> <br>
                <input type="time" id="store_open_time" name="store_open_time"
                    value="<?php echo esc_attr($open_time); ?>">
            </div>
            <div>
                <label for="store_close_time"><?php _e('Closing Time', 'noyona'); ?></label> <br>
                <input type="time" id="store_close_time" name="store_close_time"
                    value="<?php echo esc_attr($close_time); ?>">
            </div>
        </div>
        <p class="description"><?php _e('Set store hours in 24-hour format.', 'noyona'); ?></p>
        <?php
    }

    /**
     * Render Products Meta Box (Repeater)
     */
    public function render_products_meta_box($post)
    {
        $products = get_post_meta($post->ID, '_store_products', true);
        if (!is_array($products)) {
            $products = array();
        }
        ?>
        <div id="store-products-wrapper">
            <table class="widefat" id="store-products-table">
                <thead>
                    <tr>
                        <th style="width: 20px;"></th> <!-- Drag handle logic if needed, or index -->
                        <th><?php _e('Product Name', 'noyona'); ?></th>
                        <th style="width: 140px;"><?php _e('Category', 'noyona'); ?></th>
                        <th style="width: 220px;"><?php _e('Description', 'noyona'); ?></th>
                        <th style="width: 100px;"><?php _e('Quantity', 'noyona'); ?></th>
                        <th style="width: 150px;"><?php _e('Image', 'noyona'); ?></th>
                        <th style="width: 50px;"></th> <!-- Remove button -->
                    </tr>
                </thead>
                <tbody id="store-products-list">
                    <?php
                    if (!empty($products)) {
                        foreach ($products as $index => $product) {
                            $this->render_product_row($index, $product);
                        }
                    }
                    ?>
                </tbody>
            </table>
            <p>
                <button type="button" class="button button-secondary"
                    id="add-store-product"><?php _e('Add Product', 'noyona'); ?></button>
            </p>
        </div>

        <!-- Template for new row -->
        <script type="text/template" id="store-product-row-template">
                            <?php $this->render_product_row('{{INDEX}}', array('name' => '', 'category' => '', 'description' => '', 'qty' => '', 'image' => '', 'image_id' => '')); ?>
                        </script>
        <?php
    }

    /**
     * Render a single product row
     */
    private function render_product_row($index, $data)
    {
        $name = isset($data['name']) ? $data['name'] : '';
        $category = isset($data['category']) ? $data['category'] : '';
        $description = isset($data['description']) ? $data['description'] : '';
        $qty = isset($data['qty']) ? $data['qty'] : '';
        $img = isset($data['image']) ? $data['image'] : '';
        $img_id = isset($data['image_id']) ? $data['image_id'] : '';

        // Ensure index is safe for JS template replacement
        // If it's the template call, we leave {{INDEX}} alone. If it's a real call, $index is int.
        ?>
        <tr class="store-product-row">
            <td><span class="dashicons dashicons-menu" style="cursor: move; color: #ccc;"></span></td>
            <td>
                <input type="text" name="store_products[<?php echo $index; ?>][name]" value="<?php echo esc_attr($name); ?>"
                    class="widefat" placeholder="Product Name">
            </td>
            <td>
                <input type="text" name="store_products[<?php echo $index; ?>][category]"
                    value="<?php echo esc_attr($category); ?>" class="widefat" placeholder="Category">
            </td>
            <td>
                <textarea name="store_products[<?php echo $index; ?>][description]" class="widefat" rows="2"
                    placeholder="Description"><?php echo esc_textarea($description); ?></textarea>
            </td>
            <td>
                <input type="number" name="store_products[<?php echo $index; ?>][qty]" value="<?php echo esc_attr($qty); ?>"
                    class="widefat" min="0" placeholder="0">
            </td>
            <td>
                <div class="store-product-image-container">
                    <?php if ($img): ?>
                        <img src="<?php echo esc_url($img); ?>"
                            style="max-width: 100px; height: auto; display: block; margin-bottom: 5px;">
                    <?php endif; ?>
                    <input type="hidden" name="store_products[<?php echo $index; ?>][image]"
                        value="<?php echo esc_attr($img); ?>" class="store-product-image-url">
                    <input type="hidden" name="store_products[<?php echo $index; ?>][image_id]"
                        value="<?php echo esc_attr($img_id); ?>" class="store-product-image-id">
                    <button type="button"
                        class="button button-small upload-product-image"><?php _e('Select Image', 'noyona'); ?></button>
                    <?php if ($img): ?>
                        <button type="button" class="button button-small remove-product-image"
                            style="color: #a00;"><?php _e('Remove', 'noyona'); ?></button>
                    <?php endif; ?>
                </div>
            </td>
            <td>
                <button type="button" class="button button-link-delete remove-store-product"><span
                        class="dashicons dashicons-trash"></span></button>
            </td>
        </tr>
        <?php
    }

    /**
     * Save Meta Box Data
     */
    public function save_meta_box_data($post_id)
    {
        if (!isset($_POST['store_nonce'])) {
            return;
        }
        if (!wp_verify_nonce($_POST['store_nonce'], 'store_save_data')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save Location
        if (isset($_POST['store_lat']))
            update_post_meta($post_id, '_store_lat', sanitize_text_field($_POST['store_lat']));
        if (isset($_POST['store_lng']))
            update_post_meta($post_id, '_store_lng', sanitize_text_field($_POST['store_lng']));

        // Save Hours
        if (isset($_POST['store_open_time']))
            update_post_meta($post_id, '_store_open_time', sanitize_text_field($_POST['store_open_time']));
        if (isset($_POST['store_close_time']))
            update_post_meta($post_id, '_store_close_time', sanitize_text_field($_POST['store_close_time']));

        // Save Products
        if (isset($_POST['store_products']) && is_array($_POST['store_products'])) {
            $products = array();
            foreach ($_POST['store_products'] as $item) {
                if (
                    empty($item['name']) &&
                    empty($item['category']) &&
                    empty($item['description']) &&
                    empty($item['qty']) &&
                    empty($item['image'])
                ) {
                    continue;
                }
                $products[] = array(
                    'name' => sanitize_text_field($item['name']),
                    'category' => sanitize_text_field(isset($item['category']) ? $item['category'] : ''),
                    'description' => sanitize_textarea_field(isset($item['description']) ? $item['description'] : ''),
                    'qty' => intval($item['qty']),
                    'image' => esc_url_raw($item['image']),
                    'image_id' => intval($item['image_id']),
                );
            }
            update_post_meta($post_id, '_store_products', $products);
        } else {
            delete_post_meta($post_id, '_store_products');
        }
    }

    /**
     * Enqueue Admin Scripts
     */
    public function enqueue_admin_scripts($hook)
    {
        global $post_type;
        if ('store' !== $post_type) {
            return;
        }

        // Leaflet CSS
        wp_enqueue_style('leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4');

        // Custom Admin CSS
        wp_enqueue_style('store-manager-admin-css', plugins_url('assets/css/admin.css', __FILE__), array(), '1.0.0');

        // Leaflet JS
        wp_enqueue_script('leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true);

        // WP Media
        wp_enqueue_media();

        // Custom Admin JS
        wp_enqueue_script('store-manager-admin-js', plugins_url('assets/js/admin.js', __FILE__), array('jquery', 'leaflet-js'), '1.0.0', true);
    }
}

// Initialize the plugin
Store_Manager_Plugin::get_instance();
