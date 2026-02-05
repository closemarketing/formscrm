<?php
/**
 * Plugin Name: FormsCRM
 * Plugin URI : https://close.technology/wordpress-plugins/formscrm/
 * Description: Connects Forms with CRM, ERP and Email Marketing.
 * Version: 4.3.0-rc.1
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

define( 'FORMSCRM_VERSION', '4.3.0-rc.1' );
define( 'FORMSCRM_PLUGIN', __FILE__ );
define( 'FORMSCRM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'FORMSCRM_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'FORMSCRM_CRED_VARIABLES', array( 'url', 'username', 'password', 'apipassword', 'odoodb', 'apisales' ) );

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

add_filter(
	'formscrm_crmlib_path',
	function ( $choices ) {

		$choices['holded']     = FORMSCRM_PLUGIN_PATH . 'includes/crm-library/class-crmlib-holded.php';
		$choices['clientify']  = FORMSCRM_PLUGIN_PATH . 'includes/crm-library/class-crmlib-clientify.php';
		$choices['acumbamail'] = FORMSCRM_PLUGIN_PATH . 'includes/crm-library/class-crmlib-acumbamail.php';
		$choices['mailerlite'] = FORMSCRM_PLUGIN_PATH . 'includes/crm-library/class-crmlib-mailerlite.php';
		$choices['brevo']      = FORMSCRM_PLUGIN_PATH . 'includes/crm-library/class-crmlib-brevo.php';

		return $choices;
	}
);

// Include files.
require_once FORMSCRM_PLUGIN_PATH . '/includes/admin/class-admin-options.php';
require_once FORMSCRM_PLUGIN_PATH . '/includes/admin/class-error-log.php';
require_once FORMSCRM_PLUGIN_PATH . '/includes/admin/class-error-log-page.php';
require_once FORMSCRM_PLUGIN_PATH . '/includes/admin/class-markdown-export.php';
require_once FORMSCRM_PLUGIN_PATH . '/includes/formscrm-library/loader.php';

// Add Markdown Export tab to FormsCRM settings.
add_filter(
	'formscrm_settings_tabs',
	function ( $tabs ) {
		$tabs[] = array(
			'tab'    => 'markdown-export',
			'label'  => esc_html__( 'Export Entries', 'formscrm' ),
			'action' => 'formscrm_markdown_export_content',
		);
		return $tabs;
	}
);

// AJAX handler to get form entries for selection.
add_action( 'wp_ajax_formscrm_get_form_entries', 'formscrm_ajax_get_form_entries' );
/**
 * AJAX handler to get form entries
 *
 * @return void
 */
function formscrm_ajax_get_form_entries() {
	// Check nonce.
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'formscrm_markdown_export_nonce' ) ) {
		wp_send_json_error( array( 'message' => __( 'Security check failed', 'formscrm' ) ) );
	}

	// Check capabilities.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied', 'formscrm' ) ) );
	}

	$form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;

	if ( empty( $form_id ) || ! class_exists( 'GFAPI' ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid form ID', 'formscrm' ) ) );
	}

	// Get entries.
	$search_criteria = array(
		'status' => 'active',
	);

	$sorting = array(
		array(
			'key'        => 'date_created',
			'direction'  => 'DESC',
			'is_numeric' => false,
		),
	);

	$paging = array(
		'offset'    => 0,
		'page_size' => 100, // Limit to recent 100 entries for selection.
	);

	$entries = GFAPI::get_entries( $form_id, $search_criteria, $sorting, $paging );

	if ( is_wp_error( $entries ) ) {
		wp_send_json_error( array( 'message' => $entries->get_error_message() ) );
	}

	// Format entries for display.
	$formatted_entries = array();
	$form              = GFAPI::get_form( $form_id );

	foreach ( $entries as $entry ) {
		// Try to find a meaningful preview field.
		$preview = '';
		foreach ( $form['fields'] as $field ) {
			if ( in_array( $field->type, array( 'name', 'email', 'text' ), true ) && ! empty( $entry[ $field->id ] ) ) {
				$preview = wp_trim_words( $entry[ $field->id ], 10 );
				break;
			}
		}

		$formatted_entries[] = array(
			'id'      => $entry['id'],
			'date'    => date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $entry['date_created'] ) ),
			'preview' => $preview,
		);
	}

	wp_send_json_success( array( 'entries' => $formatted_entries ) );
}
