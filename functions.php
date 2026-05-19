<?php
/**
 * Noyona Child Theme — main loader file.
 *
 * All theme logic lives in organized partials under inc/.
 * Each partial is loaded with a guarded require_once so a missing file
 * never produces a fatal during deploys or development.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$theme_inc_path = get_stylesheet_directory() . '/inc';

$theme_inc_files = array(
    'theme-setup.php',
    'enqueue.php',
    'helpers.php',
    'rewrites.php',
    'shortcodes.php',
    'ajax.php',
    'admin.php',
    'contact-form.php',
    'seo.php',
    'security.php',
    'performance.php',
    'woocommerce-general.php',
    'woocommerce-cart.php',
    'woocommerce-checkout.php',
    'woocommerce-pdp.php',
    'woocommerce-shipping.php',
);

foreach ( $theme_inc_files as $theme_inc_file ) {
    $theme_inc_file_path = $theme_inc_path . '/' . $theme_inc_file;
    if ( is_readable( $theme_inc_file_path ) ) {
        require_once $theme_inc_file_path;
    }
}
