<?php
/**
 * reCAPTCHA module loader.
 *
 * Loads the reCAPTCHA feature files in dependency order, so functions.php only
 * needs a single include line: 'recaptcha/loader.php'.
 *
 * Add any future reCAPTCHA-related partial to the $files array below (after its
 * dependencies) and it will be included automatically.
 *
 * @package Noyona
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$files = array(
    'recaptcha.php',       // Shared base helpers (keys, verify, widget markup). Must load first.
    'recaptcha-forms.php', // Per-form version switcher + wrappers. Depends on the base helpers.
);

foreach ( $files as $file ) {
    $path = __DIR__ . '/' . $file;
    if ( is_readable( $path ) ) {
        require_once $path;
    }
}

unset( $files, $file, $path );
