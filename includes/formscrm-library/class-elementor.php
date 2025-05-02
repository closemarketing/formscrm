<?php
/**
 * Class for Elementor
 *
 * @package    WordPress
 * @author     David Perez <david@close.technology>
 * @copyright  2025 CLOSE
 * @version    4.0.0
 *
 * DOC: https://developers.elementor.com/docs/form-actions/
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Action Class
 */
class FormsCRM_Elementor_Action_After_Submit extends \ElementorPro\Modules\Forms\Classes\Action_Base {
	/**
	 * CRM Library Object
	 *
	 * @var object
	 */
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
	 * @param \Elementor\Widget_Base $widget Widget object.
	 */
	public function register_settings_section( $widget ) {
		$widget->start_controls_section(
			'section_formscrm',
			array(
				'label'     => __( 'FormsCRM', 'formscrm' ),
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
				'description' => __( 'Username for authentication.', 'formscrm' ),
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
				'description' => __( 'Password for authentication.', 'formscrm' ),
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
				'description' => __( 'API Password for authentication.', 'formscrm' ),
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
			'connect_crm',
			[
				'label'       => esc_html__( 'Connect CRM', 'formscrm' ),
				'type'        => \Elementor\Controls_Manager::BUTTON,
				'separator'   => 'before',
				'button_type' => 'info',
				'text'        => esc_html__( 'Connect', 'formscrm' ),
				'event'       => 'formscrm:editor:connectCRM',
				'condition' => array(
					'fc_crm_type' => formscrm_get_dependency_apipassword(),
				),
			]
		);

		$widget->add_control(
			'formscrm_html',
			array(
				'type'      => \Elementor\Controls_Manager::RAW_HTML,
				'raw'       => '<div id="formscrm-popup"></div>',
				'condition' => array(
					'fc_crm_type' => formscrm_get_dependency_apipassword(),
				),
			)
		);

		// Add hidden text field.
		$widget->add_control(
			'formscrm_settings_hidden',
			array(
				'type'  => \Elementor\Controls_Manager::HIDDEN,
				'label' => esc_html__( 'CRM Settings', 'formscrm' ),
			)
		);

		$widget->end_controls_section();
	}

	/**
	 * Run
	 *
	 * Runs the action after submit
	 *
	 * @access public
	 * @param \ElementorPro\Modules\Forms\Classes\Form_Record  $record Record object.
	 * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $ajax_handler Ajax handler object.
	 */
	public function run( $record, $ajax_handler ) {
		$settings = $record->get( 'form_settings' );

		// Get submitted Form data.
		$raw_fields = $record->get( 'fields' );

		// Normalize the Form Data.
		$merge_vars = [];
		foreach ( $raw_fields as $id => $field ) {
			$merge_vars[ $id ] = [
				'name'  => $id,
				'value' => $field['value'],
			];
		}

		// Unpack hidden settings for the form.
		if ( isset( $settings['formscrm_settings_hidden'] ) ) {
			$hidden_settings = json_decode( $settings['formscrm_settings_hidden'], true );
			$settings        = array_merge( $settings, $hidden_settings );

			if ( isset( $settings['fc_crm_type'] ) && ! empty( $hidden_settings[ $settings['fc_crm_type'] ] ) ) {
				$settings['fc_crm_module'] = $hidden_settings[ $settings['fc_crm_type'] ];
			}
		}

		if ( !empty($_POST['visitor_key']) ) {
			$merge_vars['visitor_key'] = [
				'name' => 'visitor_key',
				'value' => sanitize_text_field( $_POST['visitor_key'] )
			];
		}
		// Create contact in CRM.
		$this->include_library( $settings['fc_crm_type'] );
		$response_result = $this->crmlib->create_entry( $settings, $merge_vars );

		if ( 'error' === $response_result['status'] ) {
			$url   = isset( $response_result['url'] ) ? $response_result['url'] : '';
			$query = isset( $response_result['query'] ) ? $response_result['query'] : '';

			formscrm_debug_email_lead( $settings['fc_crm_type'], 'Error ' . $response_result['message'], $merge_vars, $url, $query );
		}
	}

	/**
	 * On Export
	 *
	 * This method is called when the form is exported.
	 *
	 * @access public
	 * @param \ElementorPro\Modules\Forms\Classes\Form_Record $element Form element.
	 */
	public function on_export( $element ) {}
}
