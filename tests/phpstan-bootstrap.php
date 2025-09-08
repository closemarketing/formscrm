<?php
/**
 * PHPStan Bootstrap File
 * 
 * This file defines constants and functions that PHPStan needs to understand
 * but are not available during static analysis.
 */

// Define plugin constants that are used throughout the codebase
if (!defined('FORMSCRM_PLUGIN_URL')) {
    define('FORMSCRM_PLUGIN_URL', 'http://localhost/wp-content/plugins/connect-ecommerce/');
}

if (!defined('FORMSCRM_VERSION')) {
    define('FORMSCRM_VERSION', '1.0.0');
}

if (!defined('FORMSCRM_FILE')) {
    define('FORMSCRM_FILE', __FILE__);
}

// Define WordPress constants that might be missing
if (!defined('DOING_AJAX')) {
    define('DOING_AJAX', false);
}

if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', false);
}

if (!defined('ABSPATH')) {
    define('ABSPATH', '/path/to/wordpress/');
}

if (!defined('FORMSCRM_PLUGIN_PATH')) {
    define('FORMSCRM_PLUGIN_PATH', plugin_dir_path(__FILE__));
}

// Mock WordPress functions that PHPStan can't find
if (!function_exists('wp_doing_ajax')) {
    function wp_doing_ajax() {
        return defined('DOING_AJAX') && DOING_AJAX;
    }
}

// Mock Action Scheduler function
if (!function_exists('as_schedule_recurring_action')) {
    function as_schedule_recurring_action($timestamp, $interval_in_seconds, $hook, $args = [], $group = '') {
        return true;
    }
}

// Mock WP_CLI class
if (!class_exists('WP_CLI')) {
	class WP_CLI {
			public static function line($message) {
					echo $message . "\n";
			}
			public static function add_command($command, $class) {
					return true;
			}
	}
}