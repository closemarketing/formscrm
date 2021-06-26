<?php
/**
 * Plugin Name: FormsCRM
 * Plugin URI:  https://closemarketing.net/formscrm
 * Description: Connects Forms with CRM.
 * Version:     3.0
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

add_action( 'plugins_loaded', 'fcrm_plugin_init' );
/**
 * Load localization files
 *
 * @return void
 */
function fcrm_plugin_init() {
	load_plugin_textdomain( 'formscrm', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

define( 'FORMSCRM_VERSION', '3.0' );

require_once 'includes/debug.php';
require_once 'includes/class-library-crm.php';
require_once 'includes/class-admin-options.php';

// GravityForms.
if ( is_plugin_active( 'gravityforms/gravityforms.php' ) || is_plugin_active( 'gravity-forms/gravityforms.php' ) ) {
	add_action( 'gform_loaded', array( 'GF_CRM_Bootstrap', 'load' ), 5 );
	class GF_CRM_Bootstrap {

		public static function load() {

			if ( ! method_exists( 'GFForms', 'include_feed_addon_framework' ) ) {
				return;
			}

			require_once 'includes/class-gravityforms.php';

			GFAddOn::register( 'GFCRM' );
		}
	}

	function gf_crm() {
		return GFCRM::get_instance();
	}
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
