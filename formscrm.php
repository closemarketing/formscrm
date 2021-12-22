<?php
/**
 * Plugin Name: FormsCRM
 * Plugin URI:  https://closemarketing.net/formscrm
 * Description: Connects Forms with CRM.
 * Version:     3.4
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
define( 'FORMSCRM_VERSION', '3.4' );

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

if ( ! function_exists( 'formscrm_fs' ) ) {
	// Create a helper function for easy SDK access.
	function formscrm_fs() {
		global $formscrm_fs;

		if ( ! isset( $formscrm_fs ) ) {
			// Include Freemius SDK.
			require_once dirname( __FILE__ ) . '/vendor/freemius/wordpress-sdk/start.php';

			$formscrm_fs = fs_dynamic_init(
				array(
					'id'             => '8504',
					'slug'           => 'formscrm',
					'type'           => 'plugin',
					'public_key'     => 'pk_fa93ef3eb788d04ac4803d15c1511',
					'is_premium'     => false,
					'has_addons'     => true,
					'has_paid_plans' => false,
					'navigation'     => 'tabs',
					'menu'           => array(
						'slug'       => 'formscrm',
						'first-path' => 'admin.php?page=formscrm',
					),
				)
			);
		}

		return $formscrm_fs;
	}

	// Init Freemius.
	formscrm_fs();
	// Signal that SDK was initiated.
	do_action( 'formscrm_fs_loaded' );
}
