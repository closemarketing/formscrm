<?php
/**
 * FormsCRM integration for WPForms.
 *
 * @package   WordPress
 * @author    David Perez <david@closemarketing.es>
 * @copyright 2021 Closemarketing
 * @version   3.7.2
 * @since     1.0.0
 */

/**
 * FormsCRM integration.
 *
 * @since 1.0.0
 */
class FormsCRM_WPForms extends WPForms_Provider {
	/**
	 * CRM library instance.
	 *
	 * @var object
	 */
	private $crmlib;

	/**
	 * Connection fields.
	 *
	 * @var array
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
		$this->icon     = plugins_url( '../assets/addon-icon-wpforms.png', __FILE__ );
	}

	/**
	 * Process and submit entry to provider.
	 *
	 * @since 1.0.0
	 *
	 * @param array $fields    Form fields.
	 * @param array $entry     Entry data.
	 * @param array $form_data Form data.
	 * @param int   $entry_id  Entry ID.
	 */
	public function process_entry( $fields, $entry, $form_data, $entry_id = 0 ) {
		// Only run if this form has a connections for this provider.
		if ( empty( $form_data['providers'][ $this->slug ] ) ) {
			return;
		}

		// Fire for each connection.
		foreach ( $form_data['providers'][ $this->slug ] as $connection ) {
			$account_id                = $connection['account_id'];
			$settings                  = $this->api_connect( $account_id );
			$settings['fc_crm_module'] = $connection['list_id'];
			$merge_vars                = array();
			$entry_meta                = wpforms()->get( 'entry_meta' );
			$form_id                   = (int) $form_data['id'];
			$title                     = '<strong>FormsCRM Log</strong><br/>';

			// Check for credentials.
			if ( empty( $settings['fc_crm_type'] ) ) {
				$entry_meta->add(
					array(
						'entry_id' => $entry_id,
						'form_id'  => $form_id,
						'user_id'  => get_current_user_id(),
						'type'     => 'note',
						'data'     => $title . __( 'No connection details.', 'formscrm' ),
					),
					'entry_meta'
				);
				return;
			}
			$this->crmlib = formscrm_get_api_class( $settings['fc_crm_type'] );
			$login_result = false;
			if ( isset( $this->crmlib ) ) {
				$login_result = $this->crmlib->login( $settings );
			}

			if ( ! $login_result ) {
				$entry_meta->add(
					array(
						'entry_id' => $entry_id,
						'form_id'  => $form_id,
						'user_id'  => get_current_user_id(),
						'type'     => 'note',
						'data'     => $title . __( 'Could not connect to CRM.', 'formscrm' ),
					),
					'entry_meta'
				);
				return;
			}

			// Setup the custom fields.
			foreach ( $connection['fields'] as $conn_field_name => $conn_field ) {
				// If the custom field isn't map, skip it.
				if ( empty( $conn_field ) ) {
					continue;
				}

				$custom_field = explode( '.', $conn_field );
				$id           = $custom_field[0];
				$key          = ! empty( $custom_field[1] ) ? $custom_field[1] : 'value';
				$type         = ! empty( $custom_field[2] ) ? $custom_field[2] : 'text';

				// Check if mapped form field has a value.
				if ( empty( $fields[ $id ] [ $key ] ) ) {
					continue;
				}
				$type = 'address' === $fields[ $id ]['type'] ? 'Address' : $type;
				$type = 'date-time' === $fields[ $id ]['type'] || 'date' === $fields[ $id ]['type'] ? 'Date' : $type;

				// Special formatting for different types.
				switch ( $type ) {
					case 'Date':
						$merge_vars[] = array(
							'name'  => $conn_field_name,
							'value' => $this->format_date( $fields[ $id ], $conn_field_name, $form_data['fields'][ $id ], 'Y-m-d' ),
						);
						break;

					case 'Address':
						if ( str_contains( $conn_field_name, '|' ) ) {
							$address_key = explode( '|', $conn_field_name );
							$address_key = $address_key[1];
						} else {
							$address_key = $conn_field_name;
						}
						$equivalence  = array(
							'street'      => 'address1',
							'postal_code' => 'postal',
						);
						$key          = isset( $equivalence[ $address_key ] ) ? $equivalence[ $address_key ] : $address_key;
						$merge_vars[] = array(
							'name'  => $conn_field_name,
							'value' => $fields[ $id ][ $key ],
						);
						break;

					default:
						$merge_vars[] = array(
							'name'  => $conn_field_name,
							'value' => $this->fill_dynamic_value( $fields[ $id ][ $key ], $fields ),
						);
						break;
				}
			}
			// Submit to API.
			$message = '';
			try {
				$response_result = $this->crmlib->create_entry( $settings, $merge_vars );
				$api_status      = isset( $response_result['status'] ) ? $response_result['status'] : '';
				$api_message     = isset( $response_result['message'] ) ? $response_result['message'] : '';

				if ( 'error' === $api_status ) {
					$form_info = array(
						'form_type'       => 'wpforms',
						'form_type_title' => 'WPForms',
						'form_id'         => $form_id,
						'form_name'       => isset( $form_data['settings']['form_title'] ) ? $form_data['settings']['form_title'] : '',
						'entry_id'        => $entry_id,
					);
					formscrm_alert_error( $settings['fc_crm_type'], 'Error ' . $api_message, $merge_vars, '', '', $form_info );
					$message = __( 'Error', 'formscrm' );
				} else {
					$message = __( 'Success creating:', 'formscrm' ) . ' ' . $settings['fc_crm_type'] . ' ' . $settings['fc_crm_module'] . ' ' . $response_result['id'];
				}
				$message .= ' ' . $api_message;
			} catch ( Exception $e ) {
				$message = __( 'Error sending information to CRM.', 'formscrm' ) . ' ' . $e->getMessage();
			}

			// Add note final.
			$entry_meta->add(
				array(
					'entry_id' => $entry_id,
					'form_id'  => $form_id,
					'user_id'  => get_current_user_id(),
					'type'     => 'note',
					'data'     => $title . wpautop( $message ),
				),
				'entry_meta'
			);
		}
	}

	/**
	 * Fills dynamic value.
	 *
	 * @param string $field_value Field value.
	 * @param array  $field_entries Field entries.
	 * @return string
	 */
	private function fill_dynamic_value( $field_value, $field_entries ) {
		if ( ! str_contains( $field_value, '{id:' ) ) {
			return $field_value;
		}

		// Generate dynamic value.
		$matches = array();
		preg_match_all( '/{([^}]*)}/', $field_value, $matches );
		if ( empty( $matches[1] ) ) {
			return $field_value;
		}

		foreach ( $matches[1] as $match ) {
			$field_options = explode( ':', $match );
			if ( ! isset( $field_options[1] ) ) {
				continue;
			}
			$field_id = (int) $field_options[1];
			if ( ! isset( $field_entries[ $field_id ]['value'] ) ) {
				continue;
			}
			$entry_value = is_array( $field_entries[ $field_id ]['value'] ) ? implode( ' ', $field_entries[ $field_id ]['value'] ) : $field_entries[ $field_id ]['value'];
			$field_value = str_replace( '{' . $match . '}', $entry_value, $field_value );
		}

		return $field_value;
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
		$result_date = $field_data;
		if (
			empty( $field_data['format'] ) ||
			! in_array( $field_data['format'], array( 'date', 'date-time' ), true )
		) {
			return $result_date;
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
			$result_date = $date_time->format( $expected_format );
		}

		return $result_date;
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
			return array(
				array(
					'Key'   => '[' . $name . ']',
					'Value' => '',
				),
			);
		}

		// "Multiple" field types, like `Checkbox`, use "\n" for delimiter.
		$values = explode( "\n", $field['value'] );

		return array_map(
			static function ( $option ) use ( $name ) {
				return array(
					'Key'   => '[' . $name . ']',
					'Value' => $option,
				);
			},
			$values
		);
	}

	/************************************************************************
	 * API methods - these methods interact directly with the provider API. *
	 ************************************************************************/

	/**
	 * Authenticate with the API.
	 *
	 * @param array  $data    Connection data with credentials.
	 * @param string $form_id Form ID for authentication.
	 *
	 * @return mixed id or WP_Error object.
	 */
	public function api_auth( $data = array(), $form_id = '' ) {
		$this->crmlib = formscrm_get_api_class( $data['fc_crm_type'] );
		$login_result = false;
		if ( isset( $this->crmlib ) ) {
			$login_result = $this->crmlib->login( $data );
		}
		if ( is_array( $login_result ) && isset( $login_result['status'] ) && 'error' === $login_result['status'] ) {
			return $this->error( esc_html__( 'We could not login to the CRM', 'formscrm' ) . ' ' . esc_html( $login_result['message'] ) );
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
	 * @param string $account_id Account ID for API connection.
	 *
	 * @return mixed array or WP_Error object.
	 */
	public function api_connect( $account_id ) {
		$providers = get_option( 'wpforms_providers' );
		if ( ! empty( $providers[ $this->slug ][ $account_id ] ) ) {
			return $providers[ $this->slug ][ $account_id ];
		} else {
			return $this->error( 'API error' );
		}
	}

	/**
	 * Retrieve provider account lists.
	 *
	 * @since 1.0.0
	 *
	 * @param string $connection_id Connection ID.
	 * @param string $account_id Account ID.
	 *
	 * @return mixed array or WP_Error object.
	 */
	public function api_lists( $connection_id = '', $account_id = '' ) {
		$settings = $this->api_connect( $account_id );
		try {
			if ( empty( $settings['fc_crm_type'] ) ) {
				$this->error( __( 'No connection details.', 'formscrm' ) );
			}
			$this->crmlib = formscrm_get_api_class( $settings['fc_crm_type'] );
			$lists        = $this->crmlib->list_modules( $settings );

			$lists_wpforms = array();
			foreach ( $lists as $list ) {
				$lists_wpforms[] = array(
					'id'   => $list['value'],
					'name' => $list['label'],
				);
			}
			return $lists_wpforms;
		} catch ( Exception $e ) {
			wpforms_log(
				'FormsCRM API error',
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
	 * @param string $connection_id Connection identifier.
	 * @param string $account_id    Account identifier.
	 * @param string $list_id       List identifier.
	 *
	 * @return mixed array or error object.
	 */
	public function api_groups( $connection_id = '', $account_id = '', $list_id = '' ) {
		// Need to return an error otherwise all hell breaks loose.
		// CM doesn't have a concept of 'groups'.
		return new WP_Error( esc_html__( 'Groups do not exist.', 'formscrm' ) );
	}

	/**
	 * Retrieve provider account list fields.
	 *
	 * @since 1.0.0
	 *
	 * @param string $connection_id Connection ID.
	 * @param string $account_id Account ID.
	 * @param string $module Module from CRM.
	 *
	 * @return mixed array or WP_Error object.
	 */
	public function api_fields( $connection_id = '', $account_id = '', $module = '' ) {
		$settings = $this->api_connect( $account_id );
		if ( empty( $settings['fc_crm_type'] ) ) {
			$this->error( __( 'No connection details.', 'formscrm' ) );
		}
		$this->crmlib = formscrm_get_api_class( $settings['fc_crm_type'] );
		$login_result = '';
		if ( isset( $this->crmlib ) ) {
			$login_result = $this->crmlib->login( $settings );
		}

		if ( ! $login_result ) {
			$this->error( __( 'Could not connect to CRM.', 'formscrm' ) );
		}

		try {
			// Get Custom Fields for the List from the API.
			$fields = $this->crmlib->list_fields( $settings, $module );

			$fields_wpforms = array();
			foreach ( $fields as $field ) {
				$fields_wpforms[] = array(
					'name'       => $field['label'],
					'req'        => $field['required'],
					'tag'        => $field['name'],
					'field_type' => 'text',
				);
			}
			return $fields_wpforms;
		} catch ( Exception $e ) {
			wpforms_log(
				'FormsCRM API error',
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
		return '';
	}

	/**
	 * Provider account list options HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param string $connection_id Connection ID.
	 * @param array  $connection Connection data.
	 *
	 * @return string
	 */
	public function output_options( $connection_id = '', $connection = array() ) {
		$account_id = ! empty( $connection['account_id'] ) ? $connection['account_id'] : '';
		$html       = '';

		if ( ! empty( $account_id ) ) {
			$settings = $this->api_connect( $account_id );
			if ( is_array( $settings ) && ! empty( $settings['fc_crm_type'] ) ) {
				// Get connection status and display it prominently.
				$status_html = formscrm_get_connection_status_html( $settings, 'badge' );

				// Wrap in a visible container with proper styling.
				$html  = '<div class="wpforms-provider-connection-status" style="margin: 15px 0; padding: 12px; background: #f9f9f9; border-radius: 4px; border-left: 4px solid #0073aa;">';
				$html .= '<div style="display: flex; align-items: center; justify-content: space-between;">';
				$html .= '<div>';
				$html .= '<strong style="display: block; margin-bottom: 5px; color: #23282d;">' . esc_html__( 'CRM Connection:', 'formscrm' ) . '</strong>';
				$html .= $status_html;
				$html .= '</div>';

				// Add CRM type info.
				if ( ! empty( $settings['fc_crm_type'] ) ) {
					$html .= '<div style="text-align: right;">';
					$html .= '<span style="display: block; font-size: 11px; color: #666; text-transform: uppercase; letter-spacing: 0.5px;">' . esc_html__( 'CRM Type', 'formscrm' ) . '</span>';
					$html .= '<strong style="font-size: 14px; color: #0073aa;">' . esc_html( ucfirst( $settings['fc_crm_type'] ) ) . '</strong>';
					$html .= '</div>';
				}

				$html .= '</div>';
				$html .= '</div>';
			}
		}

		return $html;
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
			$select_page .= '<option value="' . esc_attr( $option_crm['value'] ) . '"';
			if ( $option_saved === $option_crm['value'] ) {
				$select_page .= ' selected';
			}
			$select_page .= '>' . esc_html( $option_crm['label'] ) . '</option>';
		}

		printf(
			'<select id="fc_crm_type" name="fc_crm_type">%s</select>',
			$select_page // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);

		foreach ( formscrm_get_crm_field_definitions() as $def ) {
			$input_name = 'fc_crm_' . $def['name'];
			$css_class  = 'fc_crm_' . $def['name'];
			if ( 'select' === $def['type'] && ! empty( $def['choices'] ) ) {
				echo '<select name="' . esc_attr( $input_name ) . '" class="' . esc_attr( $css_class ) . '" placeholder="' . esc_attr( $def['label'] ) . '">';
				foreach ( $def['choices'] as $choice ) {
					echo '<option value="' . esc_attr( $choice['value'] ) . '">' . esc_html( $choice['label'] ) . '</option>';
				}
				echo '</select>';
			} else {
				$input_type = 'api_key' === $def['type'] ? 'password' : 'text';
				printf(
					'<input type="%s" name="%s" class="%s" placeholder="%s">',
					esc_attr( $input_type ),
					esc_attr( $input_name ),
					esc_attr( $css_class ),
					esc_attr( $def['label'] )
				);
			}
		}

		printf(
			'<input type="checkbox" name="fc_crm_mode_expert" class="fc_crm_mode_expert" value="on" /><label for="fc_crm_mode_expert">%s</label>',
			esc_html__( 'Enable Expert Mode', 'formscrm' )
		);

		$js_dependency = '';
		foreach ( formscrm_get_choices() as $crm ) {
			$js_dependency .= "if ($('#fc_crm_type option:selected').val() == '" . esc_js( $crm['value'] ) . "') {";
			foreach ( formscrm_get_crm_field_definitions() as $def ) {
				$css_class   = 'fc_crm_' . $def['name'];
				$dependency = call_user_func( $def['dependency'] );
				if ( in_array( $crm['value'], $dependency, true ) ) {
					$js_dependency .= '$(".' . esc_js( $css_class ) . '").show();';
				} else {
					$js_dependency .= '$(".' . esc_js( $css_class ) . '").hide();';
				}
			}
			$js_dependency .= '}';
		}

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- JavaScript code generated from sanitized values.
		printf(
			'<script>
				jQuery( function($) {
					' . $js_dependency . "
					$('#fc_crm_type').change(function () { " . $js_dependency . ' });
				});
			</script>'
		);
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

new FormsCRM_WPForms();
