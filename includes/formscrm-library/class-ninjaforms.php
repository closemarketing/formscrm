<?php
/**
 * Ninja Forms integration.
 *
 * @package FormsCRM
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'NF_Abstracts_Action' ) && ! class_exists( 'FormsCRM_NinjaForms_Action' ) ) {

	/**
	 * Registers a custom action within Ninja Forms to push entries to the CRM.
	 */
	class FormsCRM_NinjaForms_Action extends NF_Abstracts_Action {

		/**
		 * Action system name.
		 *
		 * @var string
		 */
		protected $_name = 'formscrm';

		/**
		 * Action label in the builder drawer.
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
		 * Drawer group.
		 *
		 * @var string
		 */
		protected $_group = 'crm';

		/**
		 * Default timing.
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
		 * CRM library instance.
		 *
		 * @var object|null
		 */
		private $crmlib = null;

		/**
		 * Constructor.
		 */
		public function __construct() {
			parent::__construct();

			add_action( 'init', array( $this, 'init_settings' ) );
		}

		/**
		 * Register custom settings displayed inside the action drawer.
		 *
		 * @return void
		 */
		public function init_settings() {
			$this->_nicename = __( 'FormsCRM', 'formscrm' );

			$custom_settings = array(
				'fc_crm_type'       => array(
					'name'        => 'fc_crm_type',
					'type'        => 'select',
					'group'       => 'primary',
					'label'       => __( 'CRM Provider', 'formscrm' ),
					'width'       => 'full',
					'options'     => $this->get_crm_options(),
					'placeholder' => __( 'Choose the CRM to connect', 'formscrm' ),
				),
				'fc_crm_module'     => array(
					'name'        => 'fc_crm_module',
					'type'        => 'textbox',
					'group'       => 'primary',
					'label'       => __( 'Module', 'formscrm' ),
					'width'       => 'full',
					'use_merge_tags' => false,
					'help'        => __( 'Exact module or list name inside your CRM.', 'formscrm' ),
				),
				'fc_crm_url'        => array(
					'name'        => 'fc_crm_url',
					'type'        => 'textbox',
					'group'       => 'advanced',
					'label'       => __( 'CRM URL', 'formscrm' ),
					'width'       => 'full',
					'placeholder' => 'https://example.com/',
				),
				'fc_crm_username'   => array(
					'name'  => 'fc_crm_username',
					'type'  => 'textbox',
					'group' => 'advanced',
					'label' => __( 'Username', 'formscrm' ),
					'width' => 'one-half',
				),
				'fc_crm_password'   => array(
					'name'  => 'fc_crm_password',
					'type'  => 'textbox',
					'group' => 'advanced',
					'label' => __( 'Password', 'formscrm' ),
					'width' => 'one-half',
				),
				'fc_crm_apipassword'=> array(
					'name'  => 'fc_crm_apipassword',
					'type'  => 'textbox',
					'group' => 'advanced',
					'label' => __( 'API Password / Token', 'formscrm' ),
					'width' => 'one-half',
				),
				'fc_crm_apisales'   => array(
					'name'  => 'fc_crm_apisales',
					'type'  => 'textbox',
					'group' => 'advanced',
					'label' => __( 'API Sales Key', 'formscrm' ),
					'width' => 'one-half',
				),
				'fc_crm_odoodb'     => array(
					'name'  => 'fc_crm_odoodb',
					'type'  => 'textbox',
					'group' => 'advanced',
					'label' => __( 'Odoo Database', 'formscrm' ),
					'width' => 'one-half',
				),
				'fc_crm_mode_expert'=> array(
					'name'  => 'fc_crm_mode_expert',
					'type'  => 'toggle',
					'group' => 'advanced',
					'label' => __( 'Expert Mode', 'formscrm' ),
					'width' => 'one-half',
				),
				'fc_crm_webhook'    => array(
					'name'        => 'fc_crm_webhook',
					'type'        => 'textbox',
					'group'       => 'advanced',
					'label'       => __( 'Webhook URL', 'formscrm' ),
					'width'       => 'full',
					'placeholder' => 'https://example.com/webhook',
					'help'        => __( 'Optional URL to forward CRM confirmation payloads.', 'formscrm' ),
				),
				'fc_crm_field_map'  => array(
					'name'          => 'fc_crm_field_map',
					'type'          => 'textarea',
					'group'         => 'advanced',
					'label'         => __( 'Field Mapping', 'formscrm' ),
					'width'         => 'full',
					'use_merge_tags'=> true,
					'help'          => __( 'One mapping per line. Example: email = {field:email}', 'formscrm' ),
					'placeholder'   => "email = {field:email}\nfirst_name = {field:first_name}",
				),
			);

			$this->_settings = array_merge( $this->_settings, $custom_settings );
		}

		/**
		 * Send submission data to the configured CRM.
		 *
		 * @param array $action_settings Action configuration.
		 * @param int   $form_id         Form identifier.
		 * @param array $data            Submission data.
		 *
		 * @return array
		 */
		public function process( $action_settings, $form_id, $data ) {
			if ( empty( $action_settings['active'] ) ) {
				return $data;
			}

			$settings = $this->build_settings_array( $action_settings );

			if ( empty( $settings['fc_crm_type'] ) || empty( $settings['fc_crm_module'] ) ) {
				return $this->add_status_to_data( $data, 'skipped', __( 'CRM type or module missing.', 'formscrm' ) );
			}

			$this->crmlib = formscrm_get_api_class( $settings['fc_crm_type'] );

			if ( empty( $this->crmlib ) ) {
				return $this->add_status_to_data( $data, 'error', __( 'CRM library not available.', 'formscrm' ) );
			}

			$login_result = $this->crmlib->login( $settings );
			if ( is_array( $login_result ) && isset( $login_result['status'] ) && 'error' === $login_result['status'] ) {
				formscrm_debug_message( 'Ninja Forms login error: ' . $login_result['message'] );
				return $this->add_status_to_data( $data, 'error', $login_result['message'] );
			}

			if ( false === $login_result ) {
				return $this->add_status_to_data( $data, 'error', __( 'Could not connect to CRM.', 'formscrm' ) );
			}

			$merge_vars = $this->build_merge_vars( $action_settings, $data, $form_id );

			if ( empty( $merge_vars ) ) {
				return $this->add_status_to_data( $data, 'skipped', __( 'No mapped fields available.', 'formscrm' ) );
			}

			$response_result = $this->crmlib->create_entry( $settings, $merge_vars );
			$status          = isset( $response_result['status'] ) ? $response_result['status'] : 'success';
			$message         = isset( $response_result['message'] ) ? $response_result['message'] : '';

			if ( 'error' === $status ) {
				$url   = isset( $response_result['url'] ) ? $response_result['url'] : '';
				$query = isset( $response_result['query'] ) ? $response_result['query'] : '';
				formscrm_debug_email_lead( $settings['fc_crm_type'], 'Error ' . $message, $merge_vars, $url, $query );

				return $this->add_status_to_data( $data, 'error', $message );
			}

			formscrm_send_webhook( $settings, $response_result );

			$success_message = $message ? $message : __( 'Lead created successfully.', 'formscrm' );

			return $this->add_status_to_data(
				$data,
				'success',
				$success_message,
				array(
					'entry_id' => isset( $response_result['id'] ) ? $response_result['id'] : '',
				)
			);
		}

		/**
		 * Build sanitized settings array expected by CRM libraries.
		 *
		 * @param array $action_settings Action settings.
		 * @return array
		 */
		private function build_settings_array( $action_settings ) {
			$settings = array(
				'fc_crm_type'        => isset( $action_settings['fc_crm_type'] ) ? sanitize_text_field( $action_settings['fc_crm_type'] ) : '',
				'fc_crm_module'      => isset( $action_settings['fc_crm_module'] ) ? sanitize_text_field( $action_settings['fc_crm_module'] ) : '',
				'fc_crm_url'         => isset( $action_settings['fc_crm_url'] ) ? esc_url_raw( $action_settings['fc_crm_url'] ) : '',
				'fc_crm_username'    => isset( $action_settings['fc_crm_username'] ) ? sanitize_text_field( $action_settings['fc_crm_username'] ) : '',
				'fc_crm_password'    => isset( $action_settings['fc_crm_password'] ) ? sanitize_text_field( $action_settings['fc_crm_password'] ) : '',
				'fc_crm_apipassword' => isset( $action_settings['fc_crm_apipassword'] ) ? sanitize_text_field( $action_settings['fc_crm_apipassword'] ) : '',
				'fc_crm_apisales'    => isset( $action_settings['fc_crm_apisales'] ) ? sanitize_text_field( $action_settings['fc_crm_apisales'] ) : '',
				'fc_crm_odoodb'      => isset( $action_settings['fc_crm_odoodb'] ) ? sanitize_text_field( $action_settings['fc_crm_odoodb'] ) : '',
				'fc_crm_mode_expert' => isset( $action_settings['fc_crm_mode_expert'] ) ? sanitize_text_field( $action_settings['fc_crm_mode_expert'] ) : '',
				'fc_crm_webhook'     => isset( $action_settings['fc_crm_webhook'] ) ? esc_url_raw( $action_settings['fc_crm_webhook'] ) : '',
			);

			if ( ! empty( $settings['fc_crm_url'] ) ) {
				$settings['fc_crm_url'] = formscrm_check_url_crm( $settings['fc_crm_url'] );
			}

			return array_filter( $settings, 'strlen' );
		}

		/**
		 * Compose merge vars array based on mapping definition or field keys.
		 *
		 * @param array $action_settings Action settings.
		 * @param array $data            Submission data.
		 * @param int   $form_id         Form identifier.
		 * @return array
		 */
		private function build_merge_vars( $action_settings, $data, $form_id ) {
			$merge_vars    = array();
			$raw_mapping   = isset( $action_settings['fc_crm_field_map'] ) ? $action_settings['fc_crm_field_map'] : '';
			$field_mapping = formscrm_parse_field_mapping( $raw_mapping );

			if ( ! empty( $field_mapping ) ) {
				$this->prime_merge_tags( $form_id, $data );
				foreach ( $field_mapping as $crm_field => $template ) {
					$merge_vars[] = array(
						'name'  => $crm_field,
						'value' => $this->resolve_field_value( $template, $data ),
					);
				}
			} elseif ( ! empty( $data['fields'] ) ) {
				foreach ( $data['fields'] as $field ) {
					$field_data = $this->normalize_field( $field );
					$field_name = '';

					if ( isset( $field_data['key'] ) && $field_data['key'] ) {
						$field_name = $field_data['key'];
					} elseif ( isset( $field_data['id'] ) ) {
						$field_name = (string) $field_data['id'];
					}

					if ( '' === $field_name ) {
						continue;
					}

					$merge_vars[] = array(
						'name'  => $field_name,
						'value' => $this->normalize_field_value( isset( $field_data['value'] ) ? $field_data['value'] : '' ),
					);
				}
			}

			if ( ! empty( $_POST['visitor_key'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$merge_vars[] = array(
					'name'  => 'visitor_key',
					'value' => sanitize_text_field( wp_unslash( $_POST['visitor_key'] ) ),
				);
			}

			return $merge_vars;
		}

		/**
		 * Resolve mapped value by replacing merge tags or by referencing field keys.
		 *
		 * @param string $template Template string.
		 * @param array  $data     Submission data.
		 * @return string
		 */
		private function resolve_field_value( $template, $data ) {
			if ( false !== strpos( $template, '{' ) ) {
				return apply_filters( 'ninja_forms_merge_tags', $template );
			}

			if ( empty( $data['fields'] ) ) {
				return $template;
			}

			foreach ( $data['fields'] as $field ) {
				$field_data = $this->normalize_field( $field );
				if ( ( isset( $field_data['key'] ) && $template === $field_data['key'] )
					|| ( isset( $field_data['id'] ) && (string) $field_data['id'] === $template )
				) {
					return $this->normalize_field_value( isset( $field_data['value'] ) ? $field_data['value'] : '' );
				}
			}

			return $template;
		}

		/**
		 * Ensure the merge tag engine knows about the current submission fields.
		 *
		 * @param int   $form_id Form identifier.
		 * @param array $data    Submission data.
		 * @return void
		 */
		private function prime_merge_tags( $form_id, $data ) {
			if ( ! function_exists( 'Ninja_Forms' ) ) {
				return;
			}

			$merge_tags = Ninja_Forms()->merge_tags;
			if ( empty( $merge_tags['fields'] ) || ! is_object( $merge_tags['fields'] ) ) {
				return;
			}

			if ( method_exists( $merge_tags['fields'], 'set_form_id' ) ) {
				$merge_tags['fields']->set_form_id( $form_id );
			}

			if ( empty( $data['fields'] ) ) {
				return;
			}

			foreach ( $data['fields'] as $field ) {
				$field_data = $this->normalize_field( $field );
				if ( empty( $field_data ) ) {
					continue;
				}
				$merge_tags['fields']->add_field( $field_data );
			}
		}

		/**
		 * Normalize field data that can arrive either as an array or as an object.
		 *
		 * @param mixed $field Field data.
		 * @return array
		 */
		private function normalize_field( $field ) {
			if ( is_array( $field ) ) {
				return $field;
			}

			if ( is_object( $field ) ) {
				$normalized = array();

				if ( method_exists( $field, 'get_settings' ) ) {
					$normalized = $field->get_settings();
				}

				if ( method_exists( $field, 'get_id' ) ) {
					$normalized['id'] = $field->get_id();
				}

				if ( method_exists( $field, 'get_value' ) ) {
					$normalized['value'] = $field->get_value();
				}

				if ( method_exists( $field, 'get_setting' ) ) {
					if ( ! isset( $normalized['key'] ) ) {
						$normalized['key'] = $field->get_setting( 'key' );
					}
					if ( ! isset( $normalized['label'] ) ) {
						$normalized['label'] = $field->get_setting( 'label' );
					}
				}

				return $normalized;
			}

			return array();
		}

		/**
		 * Cast any type of field value to a scalar string.
		 *
		 * @param mixed $value Field value.
		 * @return string
		 */
		private function normalize_field_value( $value ) {
			if ( is_array( $value ) ) {
				$sanitized = array_map(
					static function( $item ) {
						return is_scalar( $item ) ? (string) $item : '';
					},
					$value
				);
				return implode( ',', array_filter( $sanitized, 'strlen' ) );
			}

			if ( is_scalar( $value ) ) {
				return (string) $value;
			}

			return '';
		}

		/**
		 * Append CRM status to Ninja Forms debug data.
		 *
		 * @param array  $data    Submission data.
		 * @param string $status  Status slug.
		 * @param string $message Message to record.
		 * @param array  $extra   Extra metadata.
		 * @return array
		 */
		private function add_status_to_data( $data, $status, $message, $extra = array() ) {
			if ( ! isset( $data['actions'] ) ) {
				$data['actions'] = array();
			}

			$data['actions']['formscrm'] = array_merge(
				array(
					'status'  => $status,
					'message' => $message,
				),
				$extra
			);

			return $data;
		}

		/**
		 * Convert CRM choices to Ninja Forms select options.
		 *
		 * @return array
		 */
		private function get_crm_options() {
			$options = array(
				array(
					'label' => __( 'Select a CRM', 'formscrm' ),
					'value' => '',
				),
			);

			foreach ( formscrm_get_choices() as $choice ) {
				$options[] = array(
					'label' => esc_html( $choice['label'] ),
					'value' => esc_html( $choice['value'] ),
				);
			}

			return $options;
		}
	}
}

if ( ! function_exists( 'formscrm_register_ninja_forms_action' ) ) {
	/**
	 * Register the FormsCRM action within Ninja Forms.
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
