<?php
/**
 * Plugin Name: FormsCRM
 * Plugin URI:  https://close.technology/wordpress-plugins/formscrm/
 * Description: Connects Forms with CRM, ERP and Email Marketing.
 * Version:     3.15.1-rc.4
 * Author:      CloseTechnology
 * Author URI:  https://close.technology
 * Text Domain: formscrm
 * Domain Path: /languages
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *
 * @package     WordPress
 * @author      CloseTechnology
 * @copyright   2024 CloseTechnology
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 *
 * Prefix:      fcrm
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

define( 'FORMSCRM_VERSION', '3.15.1-rc.4' );
define( 'FORMSCRM_PLUGIN', __FILE__ );
define( 'FORMSCRM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'FORMSCRM_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'FORMSCRM_CRED_VARIABLES', array( 'url', 'username', 'password', 'apipassword', 'odoodb', 'apisales' ) );

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

		$choices[] = array(
			'label' => 'MailerLite Classic',
			'value' => 'mailerlite',
		);

		$choices[] = array(
			'label' => 'MailerLite',
			'value' => 'mailerlite-new',
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
		$choices[] = 'mailerlite';
		$choices[] = 'mailerlite-new';

		return $choices;
	}
);

add_filter(
	'formscrm_crmlib_path',
	function( $choices ) {

		$choices['holded']     = FORMSCRM_PLUGIN_PATH . 'includes/crm-library/class-crmlib-holded.php';
		$choices['clientify']  = FORMSCRM_PLUGIN_PATH . 'includes/crm-library/class-crmlib-clientify.php';
		$choices['acumbamail'] = FORMSCRM_PLUGIN_PATH . 'includes/crm-library/class-crmlib-acumbamail.php';
		$choices['mailerlite'] = FORMSCRM_PLUGIN_PATH . 'includes/crm-library/class-crmlib-mailerlite.php';
		$choices['mailerlite-new'] = FORMSCRM_PLUGIN_PATH . 'includes/crm-library/class-crmlib-mailerlite-new.php';

		return $choices;
	}
);

// Include files.
require_once FORMSCRM_PLUGIN_PATH . '/includes/admin/class-admin-options.php';
require_once FORMSCRM_PLUGIN_PATH . '/includes/admin/class-admin-updater.php';
require_once FORMSCRM_PLUGIN_PATH . '/includes/formscrm-library/loader.php';
