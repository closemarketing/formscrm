<?php
/**
 * Ninja Forms integration.
 *
 * Registers a native "FormsCRM" action (via `NF_Abstracts_Action`, Ninja
 * Forms' own action framework) that sends submissions to the CRM connection
 * configured in FormsCRM > Ninja Forms (see class-ninjaforms-settings-tab.php).
 *
 * The "CRM Module" select and "Field Mapping" fieldset follow the same
 * select+fieldset pattern Ninja Forms core uses for its own newsletter/CRM
 * style actions: each module option carries its own field list, and Ninja
 * Forms renders the matching Field Mapping inputs for whichever module is
 * currently selected. Each mapping input supports Ninja Forms' native merge
 * tag picker, so the admin selects real form fields instead of typing them.
 *
 * @package FormsCRM
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'NF_Abstracts_Action' ) && ! class_exists( 'FormsCRM_NinjaForms_Action' ) ) {

	/**
	 * FormsCRM action for Ninja Forms.
	 */
	class FormsCRM_NinjaForms_Action extends NF_Abstracts_Action {

		/**
		 * Action system name.
		 *
		 * @var string
		 */
		protected $_name = 'formscrm';

		/**
		 * Action label shown in the actions drawer.
		 *
		 * @var string
		 */
		protected $_nicename = 'FormsCRM';

		/**
		 * Drawer section.
		 *
		 * @var string
		 */
		protected $_section = 'installed';

		/**
		 * Drawer tags.
		 *
		 * @var array
		 */
		protected $_tags = array( 'crm' );

		/**
		 * Execution timing.
		 *
		 * @var string
		 */
		protected $_timing = 'normal';

		/**
		 * Execution priority.
		 *
		 * @var int
		 */
		protected $_priority = 10;

		/**
		 * Constructor.
		 */
		public function __construct() {
			parent::__construct();

			$this->_nicename = __( 'FormsCRM', 'formscrm' );

			add_action( 'init', array( $this, 'init_settings' ), 20 );
		}

		/**
		 * Builds the "CRM Module" and "Field Mapping" settings shown in the action drawer.
		 *
		 * @return void
		 */
		public function init_settings() {
			$options = array(
				array(
					'value'  => '',
					'label'  => __( 'Select a module', 'formscrm' ),
					'fields' => array(),
				),
			);

			foreach ( $this->get_modules_with_fields() as $module ) {
				$options[] = $module;
			}

			$this->_settings['fc_crm_module']    = array(
				'name'    => 'fc_crm_module',
				'type'    => 'select',
				'group'   => 'primary',
				'label'   => __( 'CRM Module', 'formscrm' ),
				'width'   => 'full',
				'value'   => '',
				'options' => $options,
				'help'    => __( 'Configure the CRM connection in FormsCRM > Ninja Forms.', 'formscrm' ),
			);
			$this->_settings['fc_crm_field_map'] = array(
				'name'     => 'fc_crm_field_map',
				'type'     => 'fieldset',
				'group'    => 'primary',
				'label'    => __( 'Field Mapping', 'formscrm' ),
				'settings' => array(),
			);
		}

		/**
		 * Fetches (with caching) the CRM modules and their fields for the
		 * globally configured connection.
		 *
		 * Every module's fields are fetched up front because Ninja Forms
		 * core switches between them entirely on the client side once the
		 * drawer has loaded - there is no per-selection AJAX round trip.
		 *
		 * @return array
		 */
		private function get_modules_with_fields() {
			$connection = FormsCRM_NinjaForms_Settings::get_connection_settings();

			if ( empty( $connection['fc_crm_type'] ) ) {
				return array();
			}

			$cache_key = 'formscrm_nf_modules_fields';
			$cached    = get_transient( $cache_key );

			if ( false !== $cached ) {
				return $cached;
			}

			$modules = array();
			$crmlib  = formscrm_get_api_class( $connection['fc_crm_type'] );

			if ( $crmlib && $crmlib->login( $connection ) ) {
				foreach ( (array) $crmlib->list_modules( $connection ) as $module ) {
					if ( empty( $module['value'] ) ) {
						continue;
					}

					$fields = array();

					foreach ( (array) $crmlib->list_fields( $connection, $module['value'] ) as $crm_field ) {
						if ( empty( $crm_field['name'] ) ) {
							continue;
						}

						$fields[] = array(
							'value' => $crm_field['name'],
							'label' => ! empty( $crm_field['label'] ) ? $crm_field['label'] : $crm_field['name'],
						);
					}

					$modules[] = array(
						'value'  => $module['value'],
						'label'  => ! empty( $module['label'] ) ? $module['label'] : $module['value'],
						'fields' => $fields,
					);
				}
			}

			set_transient( $cache_key, $modules, 5 * MINUTE_IN_SECONDS );

			return $modules;
		}

		/**
		 * Sends the submission to the configured CRM.
		 *
		 * @param array $action_settings Resolved action settings (Ninja Forms has already substituted merge tags).
		 * @param int   $form_id         Form identifier.
		 * @param array $data            Submission data.
		 * @return array
		 */
		public function process( $action_settings, $form_id, $data ) {
			if ( isset( $action_settings['active'] ) && empty( $action_settings['active'] ) ) {
				return $data;
			}

			$connection = FormsCRM_NinjaForms_Settings::get_connection_settings();

			if ( empty( $connection['fc_crm_type'] ) || empty( $action_settings['fc_crm_module'] ) ) {
				return $data;
			}

			$form_info = array(
				'form_type'       => 'ninjaforms',
				'form_type_title' => 'Ninja Forms',
				'form_id'         => $form_id,
				'form_name'       => $this->get_form_title( $form_id ),
			);

			$crmlib = formscrm_get_api_class( $connection['fc_crm_type'] );

			if ( empty( $crmlib ) ) {
				formscrm_alert_error( $connection['fc_crm_type'], __( 'CRM library not available.', 'formscrm' ), array(), '', '', $form_info );
				return $data;
			}

			try {
				$login_result = $crmlib->login( $connection );

				if ( ! $login_result || ( is_array( $login_result ) && isset( $login_result['status'] ) && 'error' === $login_result['status'] ) ) {
					$message = is_array( $login_result ) && ! empty( $login_result['message'] ) ? $login_result['message'] : __( 'Could not connect to CRM.', 'formscrm' );
					formscrm_alert_error( $connection['fc_crm_type'], $message, array(), '', '', $form_info );
					return $data;
				}

				$settings                  = $connection;
				$settings['fc_crm_module'] = sanitize_text_field( $action_settings['fc_crm_module'] );

				$crm_fields = (array) $crmlib->list_fields( $settings, $settings['fc_crm_module'] );
				$merge_vars = formscrm_ninjaforms_build_merge_vars( $crm_fields, $action_settings );

				if ( empty( $merge_vars ) ) {
					return $data;
				}

				$merge_vars      = apply_filters( 'formscrm_merge_vars_before_send', $merge_vars, $settings );
				$response_result = $crmlib->create_entry( $settings, $merge_vars );
				$status          = isset( $response_result['status'] ) ? $response_result['status'] : 'success';

				if ( 'error' === $status ) {
					$message = isset( $response_result['message'] ) ? $response_result['message'] : '';
					formscrm_alert_error(
						$settings['fc_crm_type'],
						'Error ' . $message,
						$merge_vars,
						isset( $response_result['url'] ) ? $response_result['url'] : '',
						isset( $response_result['query'] ) ? $response_result['query'] : '',
						$form_info
					);
				} else {
					formscrm_debug_message( 'Success creating ' . $settings['fc_crm_type'] . ' entry ID: ' . ( isset( $response_result['id'] ) ? $response_result['id'] : '' ) );
				}
			} catch ( Exception $e ) {
				formscrm_alert_error( $connection['fc_crm_type'], $e->getMessage(), array(), '', '', $form_info );
			}

			return $data;
		}

		/**
		 * Returns the form title for use in error log entries.
		 *
		 * @param int $form_id Form identifier.
		 * @return string
		 */
		private function get_form_title( $form_id ) {
			if ( ! function_exists( 'Ninja_Forms' ) ) {
				return '';
			}

			$form = Ninja_Forms()->form( $form_id )->get();

			return $form ? (string) $form->get_setting( 'title' ) : '';
		}
	}
}

// phpcs:disable Universal.Files.SeparateFunctionsFromOO.Mixed
if ( ! function_exists( 'formscrm_register_ninja_forms_action' ) ) {
	/**
	 * Registers the FormsCRM action within Ninja Forms.
	 *
	 * @param array $actions Existing actions.
	 * @return array
	 */
	function formscrm_register_ninja_forms_action( $actions ) {
		if ( ! class_exists( 'FormsCRM_NinjaForms_Action' ) ) {
			return $actions;
		}

		$actions['formscrm'] = new FormsCRM_NinjaForms_Action();

		return $actions;
	}

	add_filter( 'ninja_forms_register_actions', 'formscrm_register_ninja_forms_action' );
}
