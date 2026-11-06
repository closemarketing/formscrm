<?php
/**
 * Plugin Name: FormsCRM
 * Plugin URI : https://close.technology/wordpress-plugins/formscrm/
 * Description: Connects Forms with CRM, ERP and Email Marketing.
 * Version: 4.4.5
 * Author: CloseTechnology
 * Author URI: https://close.technology
 * Text Domain: formscrm
 * Domain Path: /languages
 * License: GPL-2.0+
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

define( 'FORMSCRM_VERSION', '4.4.5' );
define( 'FORMSCRM_PLUGIN', __FILE__ );
define( 'FORMSCRM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'FORMSCRM_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

// Values which can be overridden per Gravity Forms feed. Keep gateway-specific
// credentials here as well as the generic CRM credentials so a custom feed
// does not silently fall back to the site-wide settings.
define( 'FORMSCRM_CRED_VARIABLES', array( 'url', 'username', 'password', 'apipassword', 'odoodb', 'apisales', 'fuc', 'terminal', 'sha_secret', 'redsys_mode' ) );

add_filter(
	'formscrm_choices',
	function ( $choices ) {
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
			'label' => 'Brevo',
			'value' => 'brevo',
		);

		$choices[] = array(
			'label' => 'MailerLite Classic',
			'value' => 'mailerlite',
		);

		return $choices;
	}
);

add_filter(
	'formscrm_dependency_apipassword',
	function ( $choices ) {

		$choices[] = 'clientify';
		$choices[] = 'acumbamail';
		$choices[] = 'holded';
		$choices[] = 'mailerlite';
		$choices[] = 'brevo';

		return $choices;
	}
);

// Page size of the Holded API, kept global for add-ons such as Holded PRO.
if ( ! defined( 'FORMSCRM_MAX_LIMIT_HOLDED_API' ) ) {
	define( 'FORMSCRM_MAX_LIMIT_HOLDED_API', 500 );
}

if ( ! file_exists( FORMSCRM_PLUGIN_PATH . 'vendor/autoload.php' ) ) {
	add_action(
		'admin_notices',
		function () {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'FormsCRM is missing its Composer autoloader. Run "composer install" in the plugin folder or reinstall the plugin.', 'formscrm' ) . '</p></div>';
		}
	);
	return;
}

require_once FORMSCRM_PLUGIN_PATH . 'vendor/autoload.php';

\CLOSE\FormsCRM\Plugin::boot();
