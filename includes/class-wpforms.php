<?php
/**
 * Campaign Monitor integration.
 *
 * @since 1.0.0
 */
class WPForms_FormsCRM extends WPForms_Provider {
	private $crmlib;

	/**
	 * Connection fields
	 *
	 * @return array
	 */
	private $connection_fields = array(
		'fc_crm_url',
		'fc_crm_username',
		'fc_crm_password',
		'fc_crm_apipassword',
		'fc_crm_apisales',
		'fc_crm_odoodb',
	);

	/**
	 * Initialize.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		$this->version  = FORMSCRM_VERSION;
		$this->name     = 'FormsCRM';
		$this->slug     = 'formscrm';
		$this->priority = 14;
		$this->icon     = plugins_url( 'assets/addon-icon-wpforms.png', __FILE__ );
	}

	/**
	 * Process and submit entry to provider.
	 *
	 * @since 1.0.0
	 *
	 * @param array $fields
	 * @param array $entry
	 * @param array $form_data
	 * @param int $entry_id
	 */
	public function process_entry( $fields, $entry, $form_data, $entry_id = 0 ) {

		// Only run if this form has a connections for this provider.
		if ( empty( $form_data['providers'][ $this->slug ] ) ) {
			return;
		}

		// Fire for each connection.
		foreach ( $form_data['providers'][ $this->slug ] as $connection ) :

			// Setup basic data.
			$account_id = $connection['account_id'];
			$list_id    = $connection['list_id'];
			$name_data  = explode( '.', $connection['fields']['fullname'] );
			$email_data = explode( '.', $connection['fields']['email'] );
			$data       = array(
				'Name'         => $fields[ $name_data[0] ]['value'],
				'EmailAddress' => $fields[ $email_data[0] ]['value'],
				'CustomFields' => array(),
				'Resubscribe'  => true, // Set to false, won't subscribe even new email addresses to CM?
			);
			$api        = $this->api_connect( $account_id );

			// Bail if there is any sort of issues with the API connection.
			if ( is_wp_error( $api ) ) {
				continue;
			}

			// Email is required.
			if ( empty( $data['EmailAddress'] ) ) {
				continue;
			}

			// Check for conditionals.
			$pass = $this->process_conditionals( $fields, $entry, $form_data, $connection );
			if ( ! $pass ) {
				wpforms_log(
					'Campaign Monitor Subscription stopped by conditional logic',
					$fields,
					array(
						'type'    => array( 'provider', 'conditional_logic' ),
						'parent'  => $entry_id,
						'form_id' => $form_data['id'],
					)
				);
				continue;
			}

			// Setup the custom fields.
			foreach ( $connection['fields'] as $name => $custom_field ) {

				// Skip fullname and email fields, as these aren't custom fields.
				if ( 'fullname' === $name || 'email' === $name ) {
					continue;
				}

				// If the custom field isn't map, skip it.
				if ( empty( $custom_field ) ) {
					continue;
				}

				$custom_field = explode( '.', $custom_field );
				$id           = $custom_field[0];
				$key          = ! empty( $custom_field[1] ) ? $custom_field[1] : 'value';
				$type         = ! empty( $custom_field[2] ) ? $custom_field[2] : 'text';

				// Check if mapped form field has a value.
				if ( empty( $fields[ $id ] [ $key ] ) ) {
					continue;
				}

				// Special formatting for different types.
				switch ( $type ) {
					case 'MultiSelectMany':
						$data['CustomFields'] = array_merge(
							$data['CustomFields'],
							$this->format_multi_select_many( $fields[ $id ], $name )
						);
						break;

					case 'Date':
						$data['CustomFields'][] = $this->format_date( $fields[ $id ], $name, $form_data['fields'][ $id ], 'Y-m-d' );
						break;

					default:
						$data['CustomFields'][] = [
							'Key'   => '[' . $name . ']',
							'Value' => $fields[ $id ][ $key ],
						];
						break;
				}
			}

			// Submit to API.
			try {
				$this->api[ $account_id ]->subscribe( $list_id, $data );
			} catch ( Exception $e ) {
				wpforms_log(
					'Campaign Monitor Subscription error',
					$e->getMessage(),
					array(
						'type'    => array( 'provider', 'error' ),
						'parent'  => $entry_id,
						'form_id' => $form_data['id'],
					)
				);
			}

		endforeach;
	}

	/**
	 * Format a value in expected format for `Date` field type.
	 *
	 * @since {VERSION}
	 *
	 * @param array  $field           Field attributes.
	 * @param string $name            Custom Field name.
	 * @param array  $field_data      Field data.
	 * @param string $expected_format Date format.
	 *
	 * @return array
	 */
	private function format_date( $field, $name, $field_data, $expected_format ) {

		$result = [
			'Key'   => '[' . $name . ']',
			'Value' => '',
		];

		if (
			empty( $field_data['format'] ) ||
			! in_array( $field_data['format'], [ 'date', 'date-time' ], true )
		) {
			return $result;
		}

		// Parse a value with date string according to a specified format.
		$date_time = false;
		if ( ! empty( $field_data['date_format'] ) ) {
			$date_time = date_create_from_format( $field_data['date_format'], $field['value'] );
		}

		// Fallback with using timestamp value.
		if ( ! $date_time && ! empty( $field['unix'] ) ) {
			$date_time = date_create( '@' . $field['unix'] );
		}

		if ( $date_time ) {
			$result['Value'] = $date_time->format( $expected_format );
		}

		return $result;
	}

	/**
	 * Format a value(s) for `MultiSelectMany` field type.
	 *
	 * @since {VERSION}
	 *
	 * @param array $field Field attributes.
	 * @param array $name  Custom Field name.
	 *
	 * @return array
	 */
	private function format_multi_select_many( $field, $name ) {

		// Firstly, check if submitted field value is empty.
		if ( empty( $field['value'] ) ) {
			return [
				[
					'Key'   => '[' . $name . ']',
					'Value' => '',
				],
			];
		}

		// "Multiple" field types, like `Checkbox`, use "\n" for delimiter.
		$values = explode( "\n", $field['value'] );

		return array_map(
			static function( $option ) use ( $name ) {
				return [
					'Key'   => '[' . $name . ']',
					'Value' => $option,
				];
			},
			$values
		);
	}

	/************************************************************************
	 * API methods - these methods interact directly with the provider API. *
	 ************************************************************************/


	/**
	 * Include library connector
	 *
	 * @param string $crmtype Type of CRM.
	 * @return void
	 */
	private function include_library( $crmtype ) {
		if ( isset( $_POST['_gform_setting_fc_crm_type'] ) ) {
			$crmtype = sanitize_text_field( $_POST['_gform_setting_fc_crm_type'] );
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

			if ( class_exists( $crmclassname ) ) {
				$this->crmlib = new $crmclassname();
			}
		}
	}

	/**
	 * Authenticate with the API.
	 *
	 * @param array $data
	 * @param string $form_id
	 *
	 * @return mixed id or WP_Error object.
	 */
	public function api_auth( $data = array(), $form_id = '' ) {
		error_log( 'api_auth run' );
		$this->include_library( $data['fc_crm_type'] );
		$login_result = '';
		if ( isset( $this->crmlib ) ) {
			$login_result = $this->crmlib->login( $data );
		}

		if ( isset( $login_result ) && false === $login_result ) {
			return $this->error( 'API authorization error: ' . $data['fc_crm_type'] );
		}

		$id                              = uniqid();
		$providers                       = get_option( 'wpforms_providers', array() );
		$providers[ $this->slug ][ $id ] = array(
			'fc_crm_type' => sanitize_text_field( $data['fc_crm_type'] ),
			'date'        => time(),
			'label'       => sanitize_text_field( $data['fc_crm_type'] ),
		);

		foreach ( $this->connection_fields as $connection_field ) {
			if ( isset( $data[ $connection_field ] ) && $data[ $connection_field ] ) {
				$providers[ $this->slug ][ $id ][ $connection_field ] = $data[ $connection_field ];
			}
		}

		update_option( 'wpforms_providers', $providers );
		return $id;
	}

	/**
	 * Establish connection object to API.
	 *
	 * @since 1.0.0
	 *
	 * @param string $account_id
	 *
	 * @return mixed array or WP_Error object.
	 */
	public function api_connect( $account_id ) {

		if ( ! empty( $this->api[ $account_id ] ) ) {
			return $this->api[ $account_id ];
		} else {
			$providers = get_option( 'wpforms_providers' );
			if ( ! empty( $providers[ $this->slug ][ $account_id ]['api'] ) ) {
				return $this->api[ $account_id ];
			} else {
				return $this->error( 'API error' );
			}
		}
	}

	/**
	 * Retrieve provider account lists.
	 *
	 * @since 1.0.0
	 *
	 * @param string $connection_id
	 * @param string $account_id
	 *
	 * @return mixed array or WP_Error object.
	 */
	public function api_lists( $connection_id = '', $account_id = '' ) {
		
		$settings_data = $this->api_connect( $account_id );
		error_log( 'data' . print_r( $settings_data, true ) );
		try {
			$lists = $this->crmlib->get_modules( $settings_data );


			return $lists;
		} catch ( Exception $e ) {
			wpforms_log(
				'Campaign Monitor API error',
				$e->getMessage(),
				array(
					'type' => array( 'provider', 'error' ),
				)
			);

			return $this->error( 'API list error: ' . $e->getMessage() );
		}
	}

	/**
	 * Retrieve provider account list groups.
	 *
	 * @since 1.0.0
	 *
	 * @param string $connection_id
	 * @param string $account_id
	 * @param string $list_id
	 *
	 * @return mixed array or error object.
	 */
	public function api_groups( $connection_id = '', $account_id = '', $list_id = '' ) {
		echo 'hola';
		error_log( 'api_groups run' );

		// Need to return an error otherwise all hell breaks loose.
		// CM doesn't have a concept of 'groups'.
		return new WP_Error( esc_html__( 'Groups do not exist.', 'formscrm' ) );
	}

	/**
	 * Retrieve provider account list fields.
	 *
	 * @since 1.0.0
	 *
	 * @param string $connection_id
	 * @param string $account_id
	 * @param string $list_id
	 *
	 * @return mixed array or WP_Error object.
	 */
	public function api_fields( $connection_id = '', $account_id = '', $list_id = '' ) {
		error_log( 'api_fields run' );
		$this->include_library( $data['fc_crm_type'] );
		$login_result = '';
		if ( isset( $this->crmlib ) ) {
			$login_result = $this->crmlib->login( $data );
		}

		$this->api_connect( $account_id );

		try {
			// Get Custom Fields for the List from the API.
			$fields = $this->api[ $account_id ]->get_list_custom_fields( $list_id );

			// Prepend the Name and Email Fields to the list, as these aren't included in the custom fields API call.
			$default_fields = array(
				array(
					'name'       => 'Full Name',
					'req'        => false,
					'tag'        => 'fullname',
					'field_type' => 'text',
				),
				array(
					'name'       => 'Email',
					'req'        => true,
					'tag'        => 'email',
					'field_type' => 'email',
				),
			);

			return array_merge( $default_fields, $fields );
		} catch ( Exception $e ) {
			wpforms_log(
				'Campaign Monitor API error',
				$e->getMessage(),
				array(
					'type' => array( 'provider', 'error' ),
				)
			);

			return $this->error(
				sprintf(
					/* translators: %s - API error message. */
					esc_html__( 'API fields error: %s', 'formscrm' ),
					$e->getMessage()
				)
			);
		}
	}

	/*************************************************************************
	 * Output methods - these methods generally return HTML for the builder. *
	 *************************************************************************/

	/**
	 * Provider account authorize fields HTML.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function output_auth() {
		global $choices_crm;

		$providers = get_option( 'wpforms_providers' );
		$class     = ! empty( $providers[ $this->slug ] ) ? 'hidden' : '';

		$output = '<div class="wpforms-provider-account-add ' . $class . ' wpforms-connection-block">';

		$output .= '<h4>' . esc_html__( 'Add New Account', 'formscrm' ) . '</h4>';

		$output .= sprintf(
			'<select type="text" data-name="fc_crm_type" placeholder="%s" class="wpforms-required">',
			sprintf(
				/* translators: %s - current provider name. */
				esc_html__( '%s API Key', 'formscrm' ),
				$this->name
			)
		);

		$output .= sprintf(
			'<input type="text" data-name="apikey" placeholder="%s" class="wpforms-required">',
			sprintf(
				/* translators: %s - current provider name. */
				esc_html__( '%s API Key', 'formscrm' ),
				$this->name
			)
		);

		$output .= sprintf(
			'<input type="text" data-name="client_id" placeholder="%s" class="wpforms-required">',
			sprintf(
				/* translators: %s - current provider name. */
				esc_html__( '%s Client ID', 'formscrm' ),
				$this->name
			)
		);

		$output .= sprintf(
			'<input type="text" data-name="label" placeholder="%s" class="wpforms-required">',
			sprintf(
				/* translators: %s - current provider name. */
				esc_html__( '%s Account Nickname', 'formscrm' ),
				$this->name
			)
		);

		$output .= sprintf( '<button data-provider="%s">%s</button>', esc_attr( $this->slug ), esc_html__( 'Connect', 'formscrm' ) );

		$output .= '</div>';

		return $output;
	}

	/**
	 * Provider account list options HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param string $connection_id
	 * @param array $connection
	 *
	 * @return string
	 */
	public function output_options( $connection_id = '', $connection = array() ) {

		// Double opt in and a welcome email are defined in the List options on Campaign Monitor.
		// They can't be controlled via the API.
		return '';
	}

	/*************************************************************************
	 * Integrations tab methods - these methods relate to the settings page. *
	 *************************************************************************/

	/**
	 * Form fields to add a new provider account.
	 *
	 * @since 1.0.0
	 */
	public function integrations_tab_new_form() {

		$select_page  = '';
		$options_crm  = formscrm_get_choices();
		$option_saved = '';
		foreach ( $options_crm as $option_crm ) {
			$select_page .= '<option value="' . $option_crm['value'] . '"';
			if ( $option_saved == $option_crm['value'] ) {
				$select_page .= ' selected';
			}
			$select_page .= '>' . $option_crm['label'] . '</option>';
		}

		printf(
			'<select id="fc_crm_type" name="fc_crm_type">%s</select>',
			$select_page
		);

		// CRM URL.
		printf(
			'<input type="text" name="fc_crm_url" class="fc_crm_url" placeholder="%s">',
			esc_html__( 'CRM URL', 'formscrm' )
		);

		// CRM Username.
		printf(
			'<input type="text" name="fc_crm_username" class="fc_crm_username" placeholder="%s">',
			esc_html__( 'CRM Username', 'formscrm' )
		);

		// CRM Password.
		printf(
			'<input type="text" name="fc_crm_password" class="fc_crm_password" placeholder="%s">',
			esc_html__( 'CRM Password', 'formscrm' )
		);

		// CRM API Password.
		printf(
			'<input type="text" name="fc_crm_apipassword" class="fc_crm_apipassword" placeholder="%s">',
			esc_html__( 'CRM API Password', 'formscrm' )
		);

		// CRM API Sales.
		printf(
			'<input type="text" name="fc_crm_apisales" class="fc_crm_apisales" placeholder="%s">',
			esc_html__( 'CRM API Sales', 'formscrm' )
		);

		// CRM Odoo DB.
		printf(
			'<input type="text" name="fc_crm_odoodb" class="fc_crm_odoodb" placeholder="%s">',
			esc_html__( 'CRM Odoo DB', 'formscrm' )
		);

		$js_dependency = '';
		foreach ( formscrm_get_choices() as $crm ) {
			$js_dependency .= "if ($('#fc_crm_type option:selected').val() == '" . esc_html( $crm['value'] ) . "') {";

			// URL dependency.
			if ( in_array( $crm['value'], formscrm_get_dependency_url() ) ) {
				$js_dependency .= '$(".fc_crm_url").show();';
			} else {
				$js_dependency .= '$(".fc_crm_url").hide();';
			}

			// Username dependency.
			if ( in_array( $crm['value'], formscrm_get_dependency_username() ) ) {
				$js_dependency .= '$(".fc_crm_username").show();';
			} else {
				$js_dependency .= '$(".fc_crm_username").hide();';
			}

			// Password dependency.
			if ( in_array( $crm['value'], formscrm_get_dependency_password() ) ) {
				$js_dependency .= '$(".fc_crm_password").show();';
			} else {
				$js_dependency .= '$(".fc_crm_password").hide();';
			}

			// API Password dependency.
			if ( in_array( $crm['value'], formscrm_get_dependency_apipassword() ) ) {
				$js_dependency .= '$(".fc_crm_apipassword").show();';
			} else {
				$js_dependency .= '$(".fc_crm_apipassword").hide();';
			}

			// API Sales dependency.
			if ( in_array( $crm['value'], formscrm_get_dependency_apisales() ) ) {
				$js_dependency .= '$(".fc_crm_apisales").show();';
			} else {
				$js_dependency .= '$(".fc_crm_apisales").hide();';
			}

			// API Sales dependency.
			if ( in_array( $crm['value'], formscrm_get_dependency_odoodb() ) ) {
				$js_dependency .= '$(".fc_crm_odoodb").show();';
			} else {
				$js_dependency .= '$(".fc_crm_odoodb").hide();';
			}

			$js_dependency .= '}';
		}

		printf(
			"<script>
				jQuery( function($) {
					" . $js_dependency . "
					$('#fc_crm_type').change(function () { " . $js_dependency . " });
				});
			</script>"
		);
	}
}

new WPForms_FormsCRM;
