<?php
/**
 * PHPStan Bootstrap File
 *
 * This file defines constants and functions that PHPStan needs to understand
 * but are not available during static analysis.
 */

// Define plugin constants that are used throughout the codebase.
if ( ! defined( 'FORMSCRM_PLUGIN_URL' ) ) {
	define( 'FORMSCRM_PLUGIN_URL', 'http://localhost/wp-content/plugins/formscrm/' );
}

if ( ! defined( 'FORMSCRM_VERSION' ) ) {
	define( 'FORMSCRM_VERSION', '3.15.7' );
}

if ( ! defined( 'FORMSCRM_PLUGIN' ) ) {
	define( 'FORMSCRM_PLUGIN', __FILE__ );
}

if ( ! defined( 'FORMSCRM_PLUGIN_PATH' ) ) {
	define( 'FORMSCRM_PLUGIN_PATH', dirname( __FILE__ ) . '/../' );
}

if ( ! defined( 'FORMSCRM_CRED_VARIABLES' ) ) {
	define( 'FORMSCRM_CRED_VARIABLES', array( 'url', 'username', 'password', 'apipassword', 'odoodb', 'apisales' ) );
}

// Define WordPress constants that might be missing.
if ( ! defined( 'DOING_AJAX' ) ) {
	define( 'DOING_AJAX', false );
}

if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/path/to/wordpress/' );
}

// Mock WordPress functions that PHPStan can't find.
if ( ! function_exists( 'wp_doing_ajax' ) ) {
	/**
	 * Mock wp_doing_ajax function.
	 *
	 * @return bool
	 */
	function wp_doing_ajax() {
		return defined( 'DOING_AJAX' ) && DOING_AJAX;
	}
}

if ( ! function_exists( 'formscrm_get_choices' ) ) {
	/**
	 * Mock formscrm_get_choices function.
	 *
	 * @return array
	 */
	function formscrm_get_choices() {
		return array();
	}
}

// Mock Action Scheduler function.
if ( ! function_exists( 'as_schedule_recurring_action' ) ) {
	/**
	 * Mock as_schedule_recurring_action function.
	 *
	 * @param int    $timestamp Timestamp.
	 * @param int    $interval_in_seconds Interval.
	 * @param string $hook Hook name.
	 * @param array  $args Arguments.
	 * @param string $group Group.
	 * @return bool
	 */
	function as_schedule_recurring_action( $timestamp, $interval_in_seconds, $hook, $args = array(), $group = '' ) {
		return true;
	}
}

// Mock WP_CLI class.
if ( ! class_exists( 'WP_CLI' ) ) {
	/**
	 * Mock WP_CLI class.
	 */
	class WP_CLI {
		/**
		 * Mock line method.
		 *
		 * @param string $message Message.
		 * @return void
		 */
		public static function line( $message ) {
			echo $message . "\n";
		}

		/**
		 * Mock add_command method.
		 *
		 * @param string $command Command.
		 * @param string $class Class.
		 * @return bool
		 */
		public static function add_command( $command, $class ) {
			return true;
		}
	}
}

