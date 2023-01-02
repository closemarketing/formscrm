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

require_once 'helpers-functions.php';
require_once 'helpers-library-crm.php';

$load_admin_options = apply_filters( 'formscrm_load_options', true );
if ( $load_admin_options ) {
	require_once 'class-admin-options.php';
}
require_once 'class-forms-clientify.php';

// Prevents fatal error is_plugin_active.
if ( ! function_exists( 'is_plugin_active' ) ) {
	include_once ABSPATH . 'wp-admin/includes/plugin.php';
}

if ( ( is_plugin_active( 'gravityforms/gravityforms.php' ) || is_plugin_active( 'gravity-forms/gravityforms.php' ) ) && ! class_exists( 'FC_CRM_Bootstrap' ) ) {
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
if ( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) && ! class_exists( 'FORMSCRM_CF7_Settings' ) ) {
	require_once 'class-contactform7.php';
}

// WooCommerce.
if ( is_plugin_active( 'woocommerce/woocommerce.php' ) && ! class_exists( 'FormsCRM_WooCommerce' ) ) {
	require_once 'class-woocommerce.php';
}

// WPForms.
if ( is_plugin_active( 'wpforms/wpforms.php' ) && ! class_exists( 'WPForms_FormsCRM' ) ) {
	add_action( 'wpforms_loaded', 'wpforms_formscrm' );
	/**
	 * Load the provider class.
	 *
	 * @since 3.7.2
	 */
	function wpforms_formscrm() {

		// WPForms Pro is required.
		if ( ! wpforms()->pro ) {
			return;
		}
		require_once 'class-wpforms.php';
	}
}

// Elementor.
if ( is_plugin_active( 'elementor/elementor.php' ) ) {
	require_once 'class-elementor.php';
}
