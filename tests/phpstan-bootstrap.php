<?php
/**
 * PHPStan Bootstrap File
 * 
 * This file defines constants and functions that PHPStan needs to understand
 * but are not available during static analysis.
 */

// Define plugin constants that are used throughout the codebase.
if ( ! defined( 'FORMSCRM_VERSION' ) ) {
	define( 'FORMSCRM_VERSION', '4.0.7' );
}

if ( ! defined( 'FORMSCRM_PLUGIN' ) ) {
	define( 'FORMSCRM_PLUGIN', __FILE__ );
}

if ( ! defined( 'FORMSCRM_PLUGIN_URL' ) ) {
	define( 'FORMSCRM_PLUGIN_URL', 'http://localhost/wp-content/plugins/formscrm/' );
}

if ( ! defined( 'FORMSCRM_PLUGIN_PATH' ) ) {
	define( 'FORMSCRM_PLUGIN_PATH', '/path/to/formscrm/' );
}

if ( ! defined( 'FORMSCRM_CRED_VARIABLES' ) ) {
	define( 'FORMSCRM_CRED_VARIABLES', array( 'url', 'username', 'password', 'apipassword', 'odoodb', 'apisales' ) );
}

// Define WordPress constants that might be missing.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/path/to/wordpress/' );
}

if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

if ( ! defined( 'DOING_AJAX' ) ) {
	define( 'DOING_AJAX', false );
}

// Mock WordPress functions that PHPStan can't find.
if ( ! function_exists( 'wp_doing_ajax' ) ) {
	function wp_doing_ajax() {
		return defined( 'DOING_AJAX' ) && DOING_AJAX;
	}
}

// Mock common WordPress functions for PHPStan.
if ( ! function_exists( 'plugin_dir_url' ) ) {
	function plugin_dir_url( $file ) {
		return 'http://localhost/wp-content/plugins/formscrm/';
	}
}

if ( ! function_exists( 'plugin_dir_path' ) ) {
	function plugin_dir_path( $file ) {
		return '/path/to/formscrm/';
	}
}

if ( ! function_exists( 'load_plugin_textdomain' ) ) {
	function load_plugin_textdomain( $domain, $deprecated = false, $plugin_rel_path = false ) {
		return true;
	}
}

