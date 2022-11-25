<?php
/**
 * Plugin Name: FormsCRM
 * Plugin URI:  https://closemarketing.net/formscrm
 * Description: Connects Forms with CRM.
 * Version:     3.8.2-beta.1
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

define( 'FORMSCRM_VERSION', '3.8.2-beta.1' );
define( 'FORMSCRM_PLUGIN', __FILE__ );
define( 'FORMSCRM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'FORMSCRM_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

add_action( 'plugins_loaded', 'fcrm_plugin_init' );
/**
 * Load localization files
 *
 * @return void
 */
function fcrm_plugin_init() {
	load_plugin_textdomain( 'formscrm', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

add_filter(
	'formscrm_choices',
	function( $choices ) {
		$choices[] = array(
			'label' => 'Holded',
			'value' => 'holded',
		);

		$choices[] = array(
			'label' => 'Clientify',
			'value' => 'clientify',
		);

		$choices[] = array(
			'label' => 'AcumbaMail',
			'value' => 'acumbamail',
		);

		return $choices;
	}
);

add_filter(
	'formscrm_dependency_apipassword',
	function( $choices ) {

		$choices[] = 'clientify';
		$choices[] = 'acumbamail';
		$choices[] = 'holded';

		return $choices;
	}
);

add_filter(
	'formscrm_crmlib_path',
	function( $choices ) {

		$choices['holded']     = FORMSCRM_PLUGIN_PATH . 'includes/crm-library/class-crmlib-holded.php';
		$choices['clientify']  = FORMSCRM_PLUGIN_PATH . 'includes/crm-library/class-crmlib-clientify.php';
		$choices['acumbamail'] = FORMSCRM_PLUGIN_PATH . 'includes/crm-library/class-crmlib-acumbamail.php';

		return $choices;
	}
);

// Include files.
require_once FORMSCRM_PLUGIN_PATH . '/includes/formscrm-library/loader.php';
