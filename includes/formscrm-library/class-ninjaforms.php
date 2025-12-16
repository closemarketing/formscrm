<?php
/**
 * Ninja Forms Wrapper
 *
 * @package   WordPress
 * @author    Closemarketing
 * @copyright 2024 Closemarketing
 * @version   4.1
 */

defined( 'ABSPATH' ) || exit;

// Check if Ninja Forms abstract class exists before defining our classes.
if ( ! class_exists( 'NF_Abstracts_Action' ) ) {
	return;
}

/**
 * Library for Ninja Forms Settings
 *
 * @package    WordPress
 * @author     Closemarketing
 * @copyright  2024 Closemarketing
 * @version    1.0
 */
class FORMSCRM_NinjaForms_Settings {

	/**
	 * CRM LIB external
	 *
	 * @var obj
	 */
	private $crmlib;

	/**
	 * Construct of class
	 */
	public function __construct() {
		add_filter( 'ninja_forms_register_actions', array( $this, 'register_actions' ) );
	}

	/**
	 * Register FormsCRM actions with Ninja Forms
	 *
	 * @param array $actions Array of registered actions.
	 * @return array
	 */
	public function register_actions( $actions ) {
		$actions['formscrm'] = new FORMSCRM_NinjaForms_Action();
		return $actions;
	}
}

/**
 * Ninja Forms Action Class
 */
class FORMSCRM_NinjaForms_Action extends NF_Abstracts_Action {

	/**
	 * CRM Library instance
	 *
	 * @var object
	 */
	private $crmlib;

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();

		$this->_name = 'formscrm';
		$this->_nicename = __( 'FormsCRM', 'formscrm' );
		$this->_timing = 'normal';

		add_action( 'ninja_forms_after_submission', array( $this, 'process_action' ) );
	}

	/**
	 * Get settings fields
	 *
	 * @return array
	 */
	public function get_settings() {
		$settings = array();

		// CRM Type selector.
		$crm_choices = array();
		foreach ( formscrm_get_choices() as $choice ) {
			$crm_choices[] = array(
				'label' => $choice['label'],
				'value' => $choice['value'],
			);
		}

		$settings['fc_crm_type'] = array(
			'name'    => 'fc_crm_type',
			'type'    => 'select',
			'label'   => __( 'CRM Type', 'formscrm' ),
			'width'   => 'full',
			'group'   => 'primary',
			'value'   => '',
			'options' => $crm_choices,
		);

		// URL Field.
		$settings['fc_crm_url'] = array(
			'name'  => 'fc_crm_url',
			'type'  => 'textbox',
			'label' => __( 'CRM URL', 'formscrm' ),
			'width' => 'full',
			'group' => 'primary',
			'value' => '',
		);

		// Username Field.
		$settings['fc_crm_username'] = array(
			'name'  => 'fc_crm_username',
			'type'  => 'textbox',
			'label' => __( 'Username', 'formscrm' ),
			'width' => 'full',
			'group' => 'primary',
			'value' => '',
		);

		// Password Field.
		$settings['fc_crm_password'] = array(
			'name'  => 'fc_crm_password',
			'type'  => 'textbox',
			'label' => __( 'Password', 'formscrm' ),
			'width' => 'full',
			'group' => 'primary',
			'value' => '',
		);

		// API Password Field.
		$settings['fc_crm_apipassword'] = array(
			'name'  => 'fc_crm_apipassword',
			'type'  => 'textbox',
			'label' => __( 'API Password', 'formscrm' ),
			'width' => 'full',
			'group' => 'primary',
			'value' => '',
		);

		// API Sales Field.
		$settings['fc_crm_apisales'] = array(
			'name'  => 'fc_crm_apisales',
			'type'  => 'textbox',
			'label' => __( 'API Sales', 'formscrm' ),
			'width' => 'full',
			'group' => 'primary',
			'value' => '',
		);

		// Odoo DB Field.
		$settings['fc_crm_odoodb'] = array(
			'name'  => 'fc_crm_odoodb',
			'type'  => 'textbox',
			'label' => __( 'Odoo DB', 'formscrm' ),
			'width' => 'full',
			'group' => 'primary',
			'value' => '',
		);

		// Module selector.
		$settings['fc_crm_module'] = array(
			'name'  => 'fc_crm_module',
			'type'  => 'textbox',
			'label' => __( 'CRM Module', 'formscrm' ),
			'width' => 'full',
			'group' => 'primary',
			'value' => '',
			'help'  => __( 'Enter the CRM module name (e.g., Leads, Contacts)', 'formscrm' ),
		);

		// Expert Mode.
		$settings['fc_crm_mode_expert'] = array(
			'name'  => 'fc_crm_mode_expert',
			'type'  => 'toggle',
			'label' => __( 'Expert Mode', 'formscrm' ),
			'width' => 'full',
			'group' => 'primary',
			'value' => 0,
			'help'  => __( 'Enable this option to show all fields of the CRM.', 'formscrm' ),
		);

		return $settings;
	}

	/**
	 * Include library connector
	 *
	 * @param string $crmtype Type of CRM.
	 * @return void
	 */
	private function include_library( $crmtype ) {
		if ( empty( $crmtype ) ) {
			return;
		}

		$crmname      = strtolower( $crmtype );
		$crmclassname = str_replace( ' ', '', $crmname );
		$crmclassname = 'CRMLIB_' . strtoupper( $crmclassname );
		$crmname      = str_replace( ' ', '_', $crmname );

		$array_path = formscrm_get_crmlib_path();
		if ( isset( $array_path[ $crmname ] ) ) {
			include_once $array_path[ $crmname ];
		}

		formscrm_debug_message( 'Loading CRM library: ' . $crmname );

		if ( class_exists( $crmclassname ) ) {
			$this->crmlib = new $crmclassname();
		}
	}

	/**
	 * Process the form submission
	 *
	 * @param array $form_data Ninja Forms form data.
	 * @return void
	 */
	public function process( $action_settings, $form_id, $data ) {
		// Get CRM settings from action.
		$crm_settings = array(
			'fc_crm_type'        => isset( $action_settings['fc_crm_type'] ) ? $action_settings['fc_crm_type'] : '',
			'fc_crm_url'         => isset( $action_settings['fc_crm_url'] ) ? $action_settings['fc_crm_url'] : '',
			'fc_crm_username'    => isset( $action_settings['fc_crm_username'] ) ? $action_settings['fc_crm_username'] : '',
			'fc_crm_password'    => isset( $action_settings['fc_crm_password'] ) ? $action_settings['fc_crm_password'] : '',
			'fc_crm_apipassword' => isset( $action_settings['fc_crm_apipassword'] ) ? $action_settings['fc_crm_apipassword'] : '',
			'fc_crm_apisales'    => isset( $action_settings['fc_crm_apisales'] ) ? $action_settings['fc_crm_apisales'] : '',
			'fc_crm_odoodb'      => isset( $action_settings['fc_crm_odoodb'] ) ? $action_settings['fc_crm_odoodb'] : '',
			'fc_crm_module'      => isset( $action_settings['fc_crm_module'] ) ? $action_settings['fc_crm_module'] : '',
		);

		// Check if CRM type is set.
		if ( empty( $crm_settings['fc_crm_type'] ) ) {
			formscrm_debug_message( 'FormsCRM: No CRM type selected' );
			return;
		}

		// Load CRM library.
		$this->include_library( $crm_settings['fc_crm_type'] );
		if ( empty( $this->crmlib ) ) {
			formscrm_debug_message( 'FormsCRM: Could not load CRM library' );
			return;
		}

		// Login to CRM.
		$login_result = $this->crmlib->login( $crm_settings );
		if ( is_array( $login_result ) && isset( $login_result['status'] ) && 'error' === $login_result['status'] ) {
			formscrm_debug_message( 'FormsCRM: Login error - ' . $login_result['message'] );
			return;
		}

		if ( false === $login_result ) {
			formscrm_debug_message( 'FormsCRM: Could not login to CRM' );
			return;
		}

		// Get form fields and prepare merge vars.
		$merge_vars = $this->get_merge_vars( $action_settings, $data['fields'] );

		// Create entry in CRM.
		$response_result = $this->crmlib->create_entry( $crm_settings, $merge_vars );

		if ( 'error' === $response_result['status'] ) {
			$url   = isset( $response_result['url'] ) ? $response_result['url'] : '';
			$query = isset( $response_result['query'] ) ? $response_result['query'] : '';

			formscrm_debug_email_lead( $crm_settings['fc_crm_type'], 'Error ' . $response_result['message'], $merge_vars, $url, $query );
		} else {
			formscrm_debug_message( 'FormsCRM: Entry created successfully - ID: ' . ( isset( $response_result['id'] ) ? $response_result['id'] : 'unknown' ) );
		}
	}

	/**
	 * Extract merge variables from Ninja Forms submission
	 *
	 * @param array $action_settings Action settings containing field mappings.
	 * @param array $form_fields     Submitted form fields.
	 * @return array
	 */
	private function get_merge_vars( $action_settings, $form_fields ) {
		$merge_vars = array();

		// Loop through all action settings to find field mappings.
		foreach ( $action_settings as $key => $value ) {
			// Check if this is a field mapping (starts with fc_crm_field-).
			if ( false === strpos( $key, 'fc_crm_field-' ) ) {
				continue;
			}

			// Extract CRM field name.
			$crm_field_name = str_replace( 'fc_crm_field-', '', $key );

			// Get the Ninja Forms field ID.
			$nf_field_id = $value;

			// Find the field value in submitted data.
			$field_value = '';
			foreach ( $form_fields as $field ) {
				if ( isset( $field['id'] ) && $field['id'] == $nf_field_id ) {
					$field_value = isset( $field['value'] ) ? $field['value'] : '';
					break;
				}
			}

			// Convert arrays to comma-separated strings.
			if ( is_array( $field_value ) ) {
				$field_value = implode( ',', $field_value );
			}

			// Add to merge vars if value is not empty.
			if ( ! empty( $field_value ) ) {
				$merge_vars[] = array(
					'name'  => $crm_field_name,
					'value' => $field_value,
				);
			}
		}

		return $merge_vars;
	}
}

new FORMSCRM_NinjaForms_Settings();
