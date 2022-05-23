<?php
/**
 * Loader
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2020 Closemarketing
 * @version    1.0
 */

defined( 'ABSPATH' ) || exit;

require_once 'debug.php';
require_once 'helpers-library-crm.php';
require_once 'class-admin-options.php';
require_once 'class-forms-clientify.php';

// Prevents fatal error is_plugin_active.
if ( ! function_exists( 'is_plugin_active' ) ) {
	include_once ABSPATH . 'wp-admin/includes/plugin.php';
}

<<<<<<< HEAD
if ( is_plugin_active( 'gravityforms/gravityforms.php' ) || is_plugin_active( 'gravity-forms/gravityforms.php' ) ) {
=======
if ( ( is_plugin_active( 'gravityforms/gravityforms.php' ) || is_plugin_active( 'gravity-forms/gravityforms.php' ) ) && ! class_exists( 'FC_CRM_Bootstrap' ) ) {
>>>>>>> 96534909d3e4e54c720aabe6e54e60e434746524
	add_action( 'gform_loaded', array( 'FC_CRM_Bootstrap', 'load' ), 5 );
	class FC_CRM_Bootstrap {

		public static function load() {

			if ( ! method_exists( 'GFForms', 'include_feed_addon_framework' ) ) {
				return;
			}

			require_once 'class-gravityforms.php';

			GFAddOn::register( 'GFCRM' );
		}
	}

	function gf_crm() {
		return FCCRM::get_instance();
	}
}

// ContactForms7.
<<<<<<< HEAD
if ( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) {
=======
if ( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) && ! class_exists( 'FORMSCRM_CF7_Settings' ) ) {
>>>>>>> 96534909d3e4e54c720aabe6e54e60e434746524
	require_once 'class-contactform7.php';
}

// WooCommerce.
<<<<<<< HEAD
if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
	require_once 'class-woocommerce.php';
}

// Visitor Key.
add_action( 'init', 'formscrm_visitorkey_session', 1 );
/**
 * Adds visitor key to the session.
 *
 * @return void
 */
function formscrm_visitorkey_session() {
	global $wp_session;

	$visitor_key = isset( $_COOKIE['vk'] ) ? sanitize_text_field( $_COOKIE['vk'] ) : '';
	if ( $visitor_key && ! isset( $wp_session['clientify_visitor_key'] ) ) {
		$wp_session['clientify_visitor_key'] = $visitor_key;
	}
}
=======
if ( is_plugin_active( 'woocommerce/woocommerce.php' ) && ! class_exists( 'FormsCRM_WooCommerce' ) ) {
	require_once 'class-woocommerce.php';
}
>>>>>>> 96534909d3e4e54c720aabe6e54e60e434746524
