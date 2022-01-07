<?php
/**
 * Plugin Name: FormsCRM
 * Plugin URI:  https://closemarketing.net/formscrm
 * Description: Connects Forms with CRM.
 * Version:     3.5.1
 * Author:      Closemarketing
 * Author URI:  https://close.marketing
 * Text Domain: formscrm
 * Domain Path: /languages
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *
 * @package     WordPress
 * @author      Closemarketing
 * @copyright   2021 Closemarketing
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 *
 * Prefix:      fcrm
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

define( 'FORMSCRM_VERSION', '3.5.1' );

add_action( 'plugins_loaded', 'fcrm_plugin_init' );
/**
 * Load localization files
 *
 * @return void
 */
function fcrm_plugin_init() {
	load_plugin_textdomain( 'formscrm', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

require_once 'includes/debug.php';
require_once 'includes/class-library-crm.php';
require_once 'includes/class-admin-options.php';

// Prevents fatal error is_plugin_active.
if ( ! function_exists( 'is_plugin_active' ) ) {
	include_once ABSPATH . 'wp-admin/includes/plugin.php';
}

if ( is_plugin_active( 'gravityforms/gravityforms.php' ) || is_plugin_active( 'gravity-forms/gravityforms.php' ) ) {
	add_action( 'gform_loaded', array( 'FC_CRM_Bootstrap', 'load' ), 5 );
	class FC_CRM_Bootstrap {

		public static function load() {

			if ( ! method_exists( 'GFForms', 'include_feed_addon_framework' ) ) {
				return;
			}

			require_once 'includes/class-gravityforms.php';

			GFAddOn::register( 'GFCRM' );
		}
	}

	function gf_crm() {
		return FCCRM::get_instance();
	}
}

// ContactForms7.
if ( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) {
	require_once 'includes/class-contactform7.php';
}

// WooCommerce.
if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
	require_once 'includes/class-woocommerce.php';
}
