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

		// Expert Mode.
		$widget->add_control(
			'fc_crm_mode_expert',
			array(
				'label' => __( 'Expert Mode', 'formscrm' ),
				'type'  => \Elementor\Controls_Manager::SWITCHER,
			)
		);

		// API Connection Status indicator.
		$widget->add_control(
			'fc_connection_status_info',
			array(
				'type'            => \Elementor\Controls_Manager::RAW_HTML,
				'raw'             => '<div class="formscrm-elementor-status-container" id="formscrm-connection-status">' .
					'<div style="padding: 12px; background: #f9f9f9; border-left: 4px solid #0073aa; border-radius: 4px; margin: 10px 0;">' .
					'<div style="display: flex; align-items: center; gap: 8px;">' .
					'<strong style="color: #23282d;">' . esc_html__( 'API Connection Status:', 'formscrm' ) . '</strong> ' .
					'<span style="display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 3px; background: #999; color: white; font-size: 12px; font-weight: bold;">' .
					'<span style="margin-right: 5px;">○</span>' . esc_html__( 'Not verified', 'formscrm' ) .
					'</span>' .
					'</div>' .
					'<p style="margin: 8px 0 0 0; color: #666; font-size: 12px;">' . esc_html__( 'Click "Connect" button below to verify your CRM credentials', 'formscrm' ) . '</p>' .
					'</div></div>',
				'content_classes' => 'formscrm-connection-status-wrapper',
				'separator'       => 'before',
			)
		);

		$widget->add_control(
			'connect_crm',
			array(
				'label'       => esc_html__( 'Connect CRM', 'formscrm' ),
				'type'        => \Elementor\Controls_Manager::BUTTON,
				'button_type' => 'info',
				'text'        => esc_html__( 'Connect', 'formscrm' ),
				'event'       => 'formscrm:editor:connectCRM',
				'condition'   => array(
					'fc_crm_type' => formscrm_get_dependency_apipassword(),
				),
			)
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
	 * Get Merge Vars
	 *
	 * Builds the merge vars array from CRM field settings and submitted raw fields.
	 *
	 * @access public
	 * @param array $formscrm_settings Decoded hidden settings (field_crm => field_form_id).
	 * @param array $raw_fields        Submitted form fields keyed by field id.
	 * @return array
	 */
	public static function get_merge_vars( $formscrm_settings, $raw_fields ) {
		$merge_vars = array();
		foreach ( $formscrm_settings as $field_crm => $field_form ) {
			$field_crm    = str_replace( 'fc_crm_field-', '', $field_crm );
			$field_value  = $raw_fields[ $field_form ]['value'] ?? '';
			$merge_vars[] = array(
				'name'  => $field_crm,
				'value' => is_array( $field_value ) ? implode( ', ', $field_value ) : $field_value,
			);
		}
		return $merge_vars;
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
		$crm_type = $settings['fc_crm_type'] ?? '';
		$module   = '';

		if ( empty( $crm_type ) ) {
			formscrm_alert_error( $crm_type, __( 'Invalid CRM type.', 'formscrm' ), array() );
			return;
		}

		// Get submitted Form data.
		$raw_fields = $record->get( 'fields' );

		// Unpack hidden settings for the form.
		$formscrm_settings = array();
		if ( isset( $settings['formscrm_settings_hidden'] ) ) {
			$formscrm_settings = json_decode( $settings['formscrm_settings_hidden'], true );
			$module            = $formscrm_settings[ $crm_type ] ?? '';
			unset( $formscrm_settings[ $crm_type ] );
		}

		if ( empty( $module ) ) {
			formscrm_alert_error( $module, __( 'Module not found.', 'formscrm' ), array() );
			return;
		}

		// Normalize the Form data.
		$merge_vars = self::get_merge_vars( $formscrm_settings, $raw_fields );

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verification handled by Elementor forms.
		if ( ! empty( $_POST['visitor_key'] ) ) {
			$merge_vars['visitor_key'] = array(
				'name'  => 'visitor_key',
				'value' => sanitize_text_field( wp_unslash( $_POST['visitor_key'] ) ),
			);
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		// Create contact in CRM.
		$settings        = formscrm_elementor_process_settings( $settings );
		$this->crmlib    = formscrm_get_api_class( $settings['fc_crm_type'] );
		$response_result = $this->crmlib->create_entry( $settings, $merge_vars );

		$response_message = '';
		if ( 'error' === $response_result['status'] ) {
			$url     = isset( $response_result['url'] ) ? $response_result['url'] : '';
			$query   = isset( $response_result['query'] ) ? $response_result['query'] : '';
			$message = isset( $response_result['message'] ) ? $response_result['message'] : '';

			$form_info = array(
				'form_type'       => 'elementor',
				'form_type_title' => 'Elementor',
				'form_id'         => isset( $settings['form_id'] ) ? $settings['form_id'] : ( isset( $settings['id'] ) ? $settings['id'] : '' ),
				'form_name'       => isset( $settings['form_name'] ) ? $settings['form_name'] : '',
			);

			formscrm_alert_error( $settings['fc_crm_type'], 'Error ' . $message, $merge_vars, $url, $query, $form_info );

			$response_message = sprintf(
				// translators: %1$s CRM name %2$s Error message %3$s URL %4$s Query.
				__( 'Error creating %1$s Error: %2$s URL: %3$s QUERY: %4$s', 'formscrm' ),
				esc_html( $settings['fc_crm_type'] ),
				$message,
				$url,
				$query
			);
			$ajax_handler->messages['admin_error'][] = $response_message;
		} else {
			$response_message = sprintf(
				// translators: %1$s CRM name %2$s ID number of entry created.
				__( 'Success creating %1$s Entry ID: %2$s', 'formscrm' ),
				esc_html( $settings['fc_crm_type'] ),
				$response_result['id']
			);
			$ajax_handler->messages['success'][] = $response_message;
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
