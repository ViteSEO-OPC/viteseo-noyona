<?php
/**
 * Script dependencies for CTA Blogpost editor script.
 *
 * @package viteseo-noyona
 */

return array(
	'dependencies' => array(
		'wp-blocks',
		'wp-block-editor',
		'wp-components',
		'wp-element',
		'wp-i18n',
		'wp-dom-ready',
	),
	'version'      => file_exists( __DIR__ . '/editor.js' ) ? (string) filemtime( __DIR__ . '/editor.js' ) : '1.0.0',
);
