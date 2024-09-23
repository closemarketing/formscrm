<?php
/**
 * Class for Elementor
 *
 * @package    WordPress
 * @author     David Perez <david@close.technology>
 * @copyright  2023 Closemarketing
 * @version    3.10
 *
 * DOC: https://developers.elementor.com/docs/form-actions/
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Action Class
 */
class FormsCRM_Elementor_Action_After_Submit extends \ElementorPro\Modules\Forms\Classes\Action_Base {

	private $crmlib;

	/**
	 * Get Name
	 *
	 * Return the action name
	 *
	 * @access public
	 * @return string
	 */
	public function get_name() {
		return 'formscrm';
	}

	/**
	 * Get Label
	 *
	 * Returns the action label
	 *
	 * @access public
	 * @return string
	 */
	public function get_label() {
		return __( 'FormsCRM', 'formscrm' );
	}

	/**
	 * Include library connector
	 *
	 * @param string $crmtype Type of CRM.
	 * @return void
	 */
	private function include_library( $crmtype ) {
		if ( isset( $_POST['fc_crm_type'] ) ) {
			$crmtype = sanitize_text_field( $_POST['fc_crm_type'] );
		}

		if ( isset( $crmtype ) ) {
			$crmname      = strtolower( $crmtype );
			$crmclassname = str_replace( ' ', '', $crmname );
			$crmclassname = 'CRMLIB_' . strtoupper( $crmclassname );
			$crmname      = str_replace( ' ', '_', $crmname );

			$array_path = formscrm_get_crmlib_path();
			if ( isset( $array_path[ $crmname ] ) ) {
				include_once $array_path[ $crmname ];
			}

			formscrm_debug_message( $array_path[ $crmname ] );

			if ( class_exists( $crmclassname ) ) {
				$this->crmlib = new $crmclassname();
			}
		}
	}

	/**
	 * Register Settings Section
	 *
	 * Registers the Action controls
	 *
	 * @access public
	 * @param \Elementor\Widget_Base $widget
	 */
	public function register_settings_section( $widget ) {
		$widget->start_controls_section(
			'section_formscrm',
			array(
				'label' => __( 'FormsCRM', 'formscrm' ),
				'condition' => array(
					'submit_actions' => $this->get_name(),
				),
			)
		);

		$crm_types = array();
		foreach ( formscrm_get_choices() as $choice ) {
			$crm_types[ esc_html( $choice['value'] ) ] = esc_html( $choice['label'] );
		}

		// CRM Type.
		$widget->add_control(
			'fc_crm_type',
			array(
				'label'       => __( 'CRM Type', 'formscrm' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'label_block' => true,
				'separator'   => 'before',
				'description' => __( 'Choose the CRM or Email Marketing to connect', 'formscrm' ),
				'options'     => $crm_types,
			)
		);

		// URL field.
		$widget->add_control(
			'fc_crm_url',
			array(
				'label'       => __( 'URL:', 'formscrm' ),
				'type'        => \Elementor\Controls_Manager::URL,
				'placeholder' => 'https://domain.com',
				'label_block' => true,
				'description' => __( 'CRM URL', 'formscrm' ),
				'condition'   => array(
					'fc_crm_type' => formscrm_get_dependency_url(),
				),
			)
		);

		// Username field.
		$widget->add_control(
			'fc_crm_username',
			array(
				'label'       => __( 'Username', 'formscrm' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'label_block' => true,
				'description' => __( '', 'formscrm' ),
				'condition'   => array(
					'fc_crm_type' => formscrm_get_dependency_username(),
				),
			)
		);

		// Password field.
		$widget->add_control(
			'fc_crm_password',
			array(
				'label'       => __( 'Password', 'formscrm' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'label_block' => true,
				'description' => __( '', 'formscrm' ),
				'condition'   => array(
					'fc_crm_type' => formscrm_get_dependency_password(),
				),
			)
		);

		// API Password field.
		$widget->add_control(
			'fc_crm_apipassword',
			array(
				'label'       => __( 'API Password', 'formscrm' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'label_block' => true,
				'description' => __( '', 'formscrm' ),
				'condition'   => array(
					'fc_crm_type' => formscrm_get_dependency_apipassword(),
				),
			)
		);

		// API Sales field.
		$widget->add_control(
			'fc_crm_apisales',
			array(
				'label'       => __( 'API Sales', 'formscrm' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'label_block' => true,
				'description' => __( '', 'formscrm' ),
				'condition'   => array(
					'fc_crm_type' => formscrm_get_dependency_apisales(),
				),
			)
		);

		// Odoo DB field.
		$widget->add_control(
			'fc_crm_odoodb',
			array(
				'label'       => __( 'Odoo DB', 'formscrm' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'label_block' => true,
				'description' => __( 'Odoo DB to connect this form.', 'formscrm' ),
				'condition'   => array(
					'fc_crm_type' => formscrm_get_dependency_odoodb(),
				),
			)
		);

		$widget->add_control(
			'delete_content',
			[
				'label'       => esc_html__( 'Connect CRM', 'textdomain' ),
				'type'        => \Elementor\Controls_Manager::BUTTON,
				'separator'   => 'before',
				'button_type' => 'info',
				'text'        => esc_html__( 'Connect', 'textdomain' ),
				'event'       => 'formscrm:editor:connectCRM',
			]
		);

		// CRM Type.
		$widget->add_control(
			'fc_crm_module',
			array(
				'label'       => __( 'CRM Module', 'formscrm' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'label_block' => true,
				'separator'   => 'before',
				'description' => __( 'Choose the CRM or Email Marketing to connect', 'formscrm' ),
				'options'     => $crm_types,
			)
		);

		$settings_form = $widget->get_settings();
		if ( ! empty( $settings_form['fc_crm_type'] ) ) {
			$this->include_library( $settings_form['fc_crm_type'] );
			/**
			 * ## Module
			 * --------------------------- */

			$modules = isset( $this->crmlib ) ? $this->crmlib->list_modules( $settings_form['fc_crm_type'] ) : array();
			if ( ! empty( $modules ) ) {
				foreach ( $modules as $module ) {

				}
			}
			/**
			 * ## Fields
			 * --------------------------- */
			/*
			$widget->add_control(
				'formscrm_fields',
				array(
					'label' => esc_html__( 'Repeater List', 'textdomain' ),
					'type' => \Elementor\Controls_Manager::REPEATER,
					'fields' => array(
						array(
							'label'       => __( 'Field from CRM', 'formscrm' ),
							'type'        => \Elementor\Controls_Manager::SELECT,
							'label_block' => true,
							'separator'   => 'before',
							'description' => __( 'Choose the CRM or Email Marketing to connect', 'formscrm' ),
							'options'     => $crm_types,
						),
						array(
							'label'       => __( 'CRM Type', 'formscrm' ),
							'type'        => \Elementor\Controls_Manager::SELECT,
							'label_block' => true,
							'separator'   => 'before',
							'description' => __( 'Choose the CRM or Email Marketing to connect', 'formscrm' ),
							'options'     => $crm_types,
						),
					),
					'title_field' => '{{{ list_title }}}',
				)
			);
			*/
		}
		$widget->end_controls_section();
	}

	/**
	 * On Export
	 *
	 * Clears form settings on export
	 * @access Public
	 * @param array $element
	 */
	public function on_export( $element ) {
		unset(
			$element['formscrm_api'],
			$element['formscrm_double_optin'],
			$element['formscrm_double_optin_template'],
			$element['formscrm_double_optin_redirect_url'],
			$element['formscrm_double_optin_check_if_email_exists'],
			$element['formscrm_gdpr_checkbox'],
			$element['formscrm_gdpr_checkbox_field'],
			$element['formscrm_list'],
			$element['formscrm_email_field'],
			$element['formscrm_name_attribute_field'],
			$element['formscrm_name_field'],
			$element['formscrm_last_name_attribute_field'],
			$element['formscrm_last_name_field']
		);

		return $element;
	}

	/**
	 * Run
	 *
	 * Runs the action after submit
	 *
	 * @access public
	 * @param \ElementorPro\Modules\Forms\Classes\Form_Record $record
	 * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $ajax_handler
	 */
	public function run( $record, $ajax_handler ) {
		$settings = $record->get( 'form_settings' );

		//Global key
		$useglobalkey = $settings['formscrm_use_global_api_key'];
		if ($useglobalkey == "yes") {
			$webtica_formscrm_options = get_option( 'webtica_formscrm_option_name' );
			$globalapikey = $webtica_formscrm_options['global_api_key_webtica_formscrm'];
			if ( empty( $globalapikey ) ) {
				if( WP_DEBUG === true ) { error_log('Elementor forms FormsCRM integration - FormsCRM Global API Key not set.'); }
				return;
			}
			else {
				$settings['formscrm_api'] = $globalapikey;
			}
		}
		else {
			//  Make sure that there is an FormsCRM API key set
			if ( empty( $settings['formscrm_api'] ) ) {
				if( WP_DEBUG === true ) { error_log('Elementor forms FormsCRM integration - FormsCRM API Key not set.'); }
				return;
			}
		}

		//  Make sure that there is a FormsCRM list ID
		if ( empty( $settings['formscrm_list'] ) ) {
			if( WP_DEBUG === true ) { error_log('Elementor forms FormsCRM integration - FormsCRM list ID not set.'); }
			return;
		}

		//Doubleoptin
		$doubleoptin = $settings['formscrm_double_optin'];
		if ($doubleoptin == "yes") {
			//  Make sure that there is a FormsCRM double optin ID if switch is set
			if ( empty( $settings['formscrm_double_optin_template'] ) ) {
				if( WP_DEBUG === true ) { error_log('Elementor forms FormsCRM integration - FormsCRM double optin template ID not set.'); }
				return;
			}
			//  Make sure that there is a FormsCRM double optin redirect URL else set default url
			if ( empty( $settings['formscrm_double_optin_redirect_url'] ) ) {
				$doubleoptinurl = get_site_url();
			}
			else {
				$doubleoptinurl = $settings['formscrm_double_optin_redirect_url'];
			}
		}

		// Make sure that there is a FormsCRM Email field ID
		if ( empty( $settings['formscrm_email_field'] ) ) {
			if( WP_DEBUG === true ) { error_log('Elementor forms FormsCRM integration - FormsCRM e-mail field ID not set.'); }
			return;
		}

		// Get submitted Form data
		$raw_fields = $record->get( 'fields' );

		// Normalize the Form Data
		$fields = [];
		foreach ( $raw_fields as $id => $field ) {
			$fields[ $id ] = $field['value'];
		}

		//Check if email field contains the elementor form attribute shortcodes
		if (strpos($settings['formscrm_email_field'], '[field id=') !== false) {
			$settings['formscrm_email_field'] = substr($settings['formscrm_email_field'], strpos($settings['formscrm_email_field'], '"') + 1);
			$settings['formscrm_email_field'] = trim($settings['formscrm_email_field'], '"]');
		}
		//Check if first name field contains the elementor form attribute shortcodes
		if (strpos($settings['formscrm_name_field'], '[field id=') !== false) {
			$settings['formscrm_name_field'] = substr($settings['formscrm_name_field'], strpos($settings['formscrm_name_field'], '"') + 1);
			$settings['formscrm_name_field'] = trim($settings['formscrm_name_field'], '"]');
		}
		//Check if last name field contains the elementor form attribute shortcodes
		if (strpos($settings['formscrm_last_name_field'], '[field id=') !== false) {
			$settings['formscrm_last_name_field'] = substr($settings['formscrm_last_name_field'], strpos($settings['formscrm_last_name_field'], '"') + 1);
			$settings['formscrm_last_name_field'] = trim($settings['formscrm_last_name_field'], '"]');
		}

		// Make sure that the user has an email
		if ( empty( $fields[ $settings['formscrm_email_field'] ] ) ) {
			if( WP_DEBUG === true ) { error_log('Elementor forms FormsCRM integration - Client did not enter an e-mail.'); }
			return;
		}

		//GDPR Checkbox
		$gdprcheckbox = $settings['formscrm_gdpr_checkbox'];
		if ($gdprcheckbox == "yes") {
			//  Make sure that there is a acceptence field if switch is set
			if ( empty( $settings['formscrm_gdpr_checkbox_field'] ) ) {
				if( WP_DEBUG === true ) { error_log('Elementor forms FormsCRM integration - Acceptence field ID is not set.'); }
				return;
			}
			// Make sure that checkbox is on
			$gdprcheckboxchecked = $fields[$settings['formscrm_gdpr_checkbox_field']];
			if ($gdprcheckboxchecked != "on") {
				if( WP_DEBUG === true ) { error_log('Elementor forms FormsCRM integration - GDPR Checkbox was not thicked.'); }
				return;
			}
		}

		// FormsCRM attribute names - Firstname
		if (empty($settings['formscrm_name_attribute_field'])) {
			$formscrmattributename = "FIRSTNAME";
		}
		else {
			$formscrmattributename = $settings['formscrm_name_attribute_field'];
		}

		// FormsCRM attribute names - Lastname
		if (empty($settings['formscrm_last_name_attribute_field'])) {
			$formscrmattributelastname = "LASTNAME";
		}
		else {
			$formscrmattributelastname = $settings['formscrm_last_name_attribute_field'];
		}

		//Check if user already exists
		$emailexistsswitch = $settings['formscrm_double_optin_check_if_email_exists'];
		if ($emailexistsswitch == "yes") {
			$requesturl = 'https://api.formscrm.com/v3/contacts/'.urlencode($fields[$settings['formscrm_email_field']]);
			//Send data to FormsCRM
			$request = wp_remote_request( $requesturl, array(
					'method'      => 'GET',
					'timeout'     => 45,
					'httpversion' => '1.0',
					'blocking'    => true,
					'headers'     => [
						'accept' => 'application/json',
						'api-key' => $settings['formscrm_api'],
						'content-Type' => 'application/json',
					],
					'body'        => ''
				)
			);
			$response_code = wp_remote_retrieve_response_code( $request );	
			if ($response_code == 200){
				$emailexists = "yes";
			} else {
				$emailexists = "no";
			}
		} else {
			$emailexists = "no";
		}

		if ($doubleoptin == "yes" && $emailexists == "no") {
			//Send data to FormsCRM Double optin
			wp_remote_post( 'https://api.formscrm.com/v3/contacts/doubleOptinConfirmation', array(
				'method'      => 'POST',
			    'timeout'     => 45,
			    'httpversion' => '1.0',
			    'blocking'    => false,
			    'headers'     => [
		            'accept' => 'application/json',
		            'api-key' => $settings['formscrm_api'],
			    	'content-Type' => 'application/json',
			    ],
			    'body'        => json_encode(["attributes" => [ $formscrmattributename => $fields[$settings['formscrm_name_field']], $formscrmattributelastname => $fields[$settings['formscrm_last_name_field']] ], "includeListIds" => [(int)$settings['formscrm_list']], "templateId" => (int)$settings['formscrm_double_optin_template'], "redirectionUrl" => $doubleoptinurl, "email" => $fields[$settings['formscrm_email_field']]])
				)
			);
		}
		else {
			//Send data to FormsCRM
			wp_remote_post( 'https://api.formscrm.com/v3/contacts', array(
				'method'      => 'POST',
		    	'timeout'     => 45,
		    	'httpversion' => '1.0',
		    	'blocking'    => false,
		    	'headers'     => [
	            	'accept' => 'application/json',
	            	'api-key' => $settings['formscrm_api'],
		    		'content-Type' => 'application/json',
		    	],
		    	'body'        => json_encode(["attributes" => [ $formscrmattributename => $fields[$settings['formscrm_name_field']], $formscrmattributelastname => $fields[$settings['formscrm_last_name_field']] ], "updateEnabled" => true, "listIds" => [(int)$settings['formscrm_list']], "email" => $fields[$settings['formscrm_email_field']]])
				)
			);	
		}
	}
}