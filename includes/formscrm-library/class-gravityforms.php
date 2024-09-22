<?php
/**
 * Functions for CRM in Gravity Forms
 *
 * All helpers functions for Gravity Forms
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.net>
 * @copyright  2019 Closemarketing
 * @version    1.0
 */

GFForms::include_feed_addon_framework();
global $formscrm_api;

/**
 * Class for Addon GravityForms
 */
class GFCRM extends GFFeedAddOn {

	protected $_version                  = FORMSCRM_VERSION;
	protected $_min_gravityforms_version = '1.9.0';
	protected $_slug                     = 'formscrm';
	protected $_path                     = 'formscrm/crm.php';
	protected $_full_path                = __FILE__;
	protected $_url                      = 'https://www.formscrm.com';
	protected $_title                    = 'CRM Add-On';
	protected $_short_title              = 'FormsCRM';
	public    $_async_feed_processing    = true;

	// Members plugin integration.
	protected $_capabilities = array(
		'formscrm',
		'formscrm_uninstall',
	);

	// Permissions.
	protected $_capabilities_settings_page = 'formscrm';
	protected $_capabilities_form_settings = 'formscrm';
	protected $_capabilities_uninstall     = 'formscrm_uninstall';
	protected $_enable_rg_autoupgrade      = true;

	private static $_instance = null;

	private $crmlib;

	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new GFCRM();
		}

		return self::$_instance;
	}
	/**
	 * Init function of library
	 *
	 * @return void
	 */
	public function init() {
		parent::init();
	}

	public function init_admin() {
		parent::init_admin();

		$this->ensure_upgrade();
	}

	private function get_crm_fields( $select_crm_type = true, $settings = array(), $page = 'feed' ) {
		$custom_crm = isset( $settings['fc_crm_custom_type'] ) ? $settings['fc_crm_custom_type'] : 'no';
		$field_name = 'no' !== $custom_crm ? 'fc_crm_custom_type' : 'fc_crm_type';
		$prefix     = 'no' !== $custom_crm ? 'fc_crm_custom_' : 'fc_crm_';
		if ( 'feed' === $page && ( 'no' === $custom_crm || empty( $custom_crm ) ) ) {
			return array();
		}

		$crm_fields = array(
			array(
				'name'          => $prefix . 'url',
				'label'         => __( 'CRM URL', 'formscrm' ),
				'type'          => 'text',
				'class'         => 'medium',
				'tooltip'       => __( 'Use the URL with http and the ending slash /.', 'formscrm' ),
				'tooltip_class' => 'tooltipclass',
				'dependency'    => array(
					'field'  => $field_name,
					'values' => formscrm_get_dependency_url(),
				),
			),
			array(
				'name'              => $prefix . 'username',
				'label'             => __( 'Username', 'formscrm' ),
				'type'              => 'text',
				'class'             => 'medium',
				'dependency'        => array(
					'field' => $field_name,
					'values' => formscrm_get_dependency_username(),
				),
			),
			array(
				'name'          => $prefix . 'password',
				'label'         => __('Password', 'formscrm' ),
				'type'          => 'api_key',
				'class'         => 'medium',
				'tooltip'       => __( 'Use the password of the actual user.', 'formscrm' ),
				'tooltip_class' => 'tooltipclass',
				'dependency'    => array(
					'field' => $field_name,
					'values' => formscrm_get_dependency_password(),
				),
			),
			array(
				'name'          => $prefix . 'apipassword',
				'label'         => __( 'API Password for User', 'formscrm' ),
				'type'          => 'api_key',
				'class'         => 'medium',
				'tooltip'       => __( 'Find the API Password in the profile of the user in CRM.', 'formscrm' ),
				'tooltip_class' => 'tooltipclass',
				'dependency'    => array(
					'field' => $field_name,
					'values' => formscrm_get_dependency_apipassword(),
				),
			),
			array(
				'name'          => $prefix . 'apisales',
				'label'         => __('Password and Security Key', 'formscrm'),
				'type'          => 'api_key',
				'class'         => 'medium',
				'tooltip'       => __( '"Password""SecurityKey" Go to My Settings / Reset my Security Key.', 'formscrm'),
				'tooltip_class' => 'tooltipclass',
				'dependency'    => array(
					'field'  => $field_name,
					'values' => formscrm_get_dependency_apisales(),
				),
			),
			array(
				'name'       => $prefix . 'odoodb',
				'label'      => __( 'Odoo DB Name', 'formscrm' ),
				'type'       => 'text',
				'class'      => 'medium',
				'dependency' => array(
					'field'  => $field_name,
					'values' => formscrm_get_dependency_odoodb(),
				),
			),
		);
		if ( $select_crm_type ) {
			$crm_fields = array_merge(
				array(
					array(
						'name'     => $prefix . 'type',
						'label'    => __( 'CRM Type', 'formscrm' ),
						'type'     => 'select',
						'class'    => 'medium',
						'onchange' => 'jQuery(this).parents("form").submit();',
						'choices'  => formscrm_get_choices(),
					),
				),
				$crm_fields,
			);
		}
		return $crm_fields;
	}

	/**
	 * Plugin settings
	 *
	 * @return array
	 */
	public function plugin_settings_fields() {
		return array(
			array(
				'title'       => __( 'CRM Account Information', 'formscrm' ),
				'description' => __( 'Use this connector with CRM software. Use Gravity Forms to collect customer information and automatically add them to your CRM Leads.', 'formscrm' ),
				'fields'      => $this->get_crm_fields( true, array(), 'settings'),
			),
		);
	}

	public function settings_api_key( $field, $echo = true ) {

		$field['type'] = 'text';

		$api_key_field = $this->settings_text( $field, false );

		//switch type="text" to type="password" so the key is not visible
		$api_key_field = str_replace('type="text"', 'type="password"', $api_key_field);

		$caption = '<small>' . sprintf( esc_html__( 'Find a Password or API key depending of CRM.', 'formscrm' ) ) . '</small>';

		if ( $echo ) {
			echo esc_html( $api_key_field ) . '</br>' . esc_html( $caption );
		}

		return $api_key_field . '</br>' . $caption;
	}

	/**
	 * Forms Settings
	 *
	 * @param array  $form Form.
	 * @param string $feed_id Feed id.
	 * @return void
	 */
	public function feed_edit_page( $form, $feed_id ) {

		echo '<script type="text/javascript">var form = ' . esc_html( GFCommon::json_encode( $form ) ) . ';</script><style type="text/css">#gform_setting_fc_login_result {display: block !important; } #gform_setting_fc_login_result label { font-size:18px; color:red;} #gform_setting_fc_select_module {display:block !important}</style>';

		parent::feed_edit_page( $form, $feed_id );
	}

	/**
	 * Include library connector
	 *
	 * @param string $crmtype Type of CRM.
	 * @return void
	 */
	private function include_library( $crm_type ) {
		if ( isset( $crm_type ) ) {
			$crmname      = strtolower( $crm_type );
			$crmclassname = str_replace( ' ', '', $crmname );
			$crmclassname = 'CRMLIB_' . strtoupper( $crmclassname );
			$crmname      = str_replace( ' ', '_', $crmname );

			$array_path = formscrm_get_crmlib_path();

			if ( isset( $array_path[ $crmname ] ) ) {
				include_once $array_path[ $crmname ];
				formscrm_debug_message( $array_path[ $crmname ] );
			}

			if ( class_exists( $crmclassname ) ) {
				$this->crmlib = new $crmclassname();
			}
		}
	}

	/**
	 * Get Settings fields
	 *
	 * @return array
	 */
	public function feed_settings_fields() {
		$settings   = $this->get_api_settings_custom();
		$custom_crm = $this->get_custom_crm();
		$settings_crm = isset( $settings['fc_crm_type'] ) ? $settings['fc_crm_type'] : '';

		if ( empty( $settings['fc_crm_type'] ) ) {
			return array();
		} elseif ( 'no' !== $custom_crm ) {
			$settings['fc_crm_type'] = $custom_crm;
		}

		$this->include_library( $settings['fc_crm_type'] );

		$settings['fc_crm_module']      = isset( $_POST['_gform_setting_fc_crm_module'] ) ? sanitize_text_field( $_POST['_gform_setting_fc_crm_module'] ) : '';
		$settings['fc_crm_custom_type'] = $custom_crm;

		return apply_filters(
			'formscrm_gf_feed',
			array(
				array(
					'title'       => __( 'CRM Feed', 'formscrm' ),
					'description' => '',
					'fields'      => array_merge(
						array(
							array(
								'name'     => 'feedName',
								'label'    => __( 'Name', 'formscrm' ),
								'type'     => 'text',
								'required' => true,
								'class'    => 'medium',
								'tooltip'  => '<h6>' . __( 'Name', 'formscrm' ) . '</h6>' . __( 'Enter a feed name to uniquely identify this setup.', 'formscrm' ),
							),
							array(
								'name'     => 'fc_crm_custom_type',
								'label'    => __( 'Custom CRM Settings', 'formscrm' ),
								'type'     => 'select',
								'class'    => 'medium',
								'onchange' => 'jQuery(this).parents("form").submit();',
								'choices'  => 
								array_merge(
									array(
										array(
											'label' => sprintf(
												__( 'Use default CRM defined in Settings: %s', 'formscrm' ),
												ucfirst( $settings_crm )
											),
											'value' => 'no',
										),	
									),
									formscrm_get_choices()
								),
							),
						),
						$this->get_crm_fields( false, $settings ),
						$this->get_crm_feed_fields( $settings ),
					),
				),
			),
		);
	}

	/**
	 * Get CRM fields
	 *
	 * @param [type] $settings
	 * @return array
	 */
	private function get_crm_feed_fields( $settings ) {
		$crm_feed_fields = array();
		$feed_settings   = $this->get_current_feed();

		if ( false === $this->login_api_crm() ) {
			$crm_feed_fields[] = array(
				'name'  => 'fc_login_result',
				'label' => __( 'We could not login to the CRM', 'formscrm' ),
				'type'  => 'hidden',
			);
		} else {
			$module = $this->get_actual_feed_value( 'fc_crm_module', $feed_settings );

			$crm_feed_fields[] = array(
					'name'     => 'fc_crm_module',
					'label'    => __( 'CRM Module', 'formscrm' ),
					'type'     => 'select',
					'class'    => 'medium',
					'onchange' => 'jQuery(this).parents("form").submit();',
					'choices'  => $this->crmlib->list_modules( $settings ),
			);
			if ( empty( $module ) ) {
				$crm_feed_fields[] = array(
					'name'  => 'fc_select_module',
					'label' => esc_html( 'Select Module and save to select merge values', 'formscrm' ),
					'type'  => 'hidden',
				);
			}
			
			$crm_feed_fields[] = array(
				'name'       => 'listFields',
				'label'      => __( 'Map Fields', 'formscrm' ),
				'type'       => 'field_map',
				'dependency' => 'fc_crm_module',
				'field_map'  => $this->crmlib->list_fields( $settings, $module ),
				'tooltip'    => '<h6>' . __( 'Map Fields', 'formscrm' ) . '</h6>' . __('Associate your CRM custom fields to the appropriate Gravity Form fields by selecting the appropriate form field from the list.', 'formscrm' ),
			);

			$crm_feed_fields[] = array(
				'name'       => 'optin',
				'label'      => esc_html__( 'Conditional Logic', 'formscrm' ),
				'type'       => 'feed_condition',
				'dependency' => 'fc_crm_module',
				'tooltip'    => sprintf(
					'<h6>%s</h6>%s',
					esc_html__( 'Conditional Logic', 'formscrm' ),
					esc_html__( 'When conditional logic is enabled, form submissions will only be exported to MailerLite when the condition is met. When disabled all form submissions will be exported.', 'formscrm' )
				),
			);
		}
		
		return $crm_feed_fields;
	}

	/**
	 * Get Settings with custom CRM in feed
	 *
	 * @param array $settings
	 * @return array
	 */
	private function get_api_settings_custom( $feed = array() ) {
		if ( empty( $feed ) ) {
			$feed = $this->get_current_feed();
		}
		$custom_crm = $this->get_custom_crm( $feed );
		$settings   = $this->get_plugin_settings();
		if ( 'no' === $custom_crm ) {
			return $settings;
		}
		$settings['fc_crm_type'] = $custom_crm;
		foreach ( FORMSCRM_CRED_VARIABLES as $variable ) {
			if ( isset( $_POST['_gform_setting_fc_crm_custom_' . $variable ] ) ) {
				$settings[ 'fc_crm_' . $variable ] = sanitize_text_field( $_POST['_gform_setting_fc_crm_custom_' . $variable ] );
			} elseif ( isset( $feed['meta'][ 'fc_crm_custom_' . $variable ] ) ) {
				$settings[ 'fc_crm_' . $variable ] = $feed['meta'][ 'fc_crm_custom_' . $variable ];
			} elseif ( isset( $settings[ 'fc_crm_custom_' . $variable ] ) ) {
				$settings[ 'fc_crm_' . $variable ] = $settings[ 'fc_crm_custom_' . $variable ];
				unset( $settings[ 'fc_crm_custom_' . $variable ] );
			}
		}
		return $settings;
	}

	/**
	 * Get actual feed value
	 *
	 * @param [type] $value
	 * @param array $feed_settings
	 * @return void
	 */
	private function get_actual_feed_value( $value, $feed_settings ) {
		if ( isset( $_POST['_gform_setting_' . $value] ) ) {
			$feed_value = sanitize_text_field( $_POST['_gform_setting_' . $value] );
		} elseif ( isset( $feed_settings['meta'][ $value ] ) ) {
			$feed_value = $feed_settings['meta'][ $value ];
		}
		return $feed_value;
	}

	/**
	 * Get custom crm from feed
	 *
	 * @return void
	 */
	private function get_custom_crm( $feed_settings = array() ) {
		if ( empty( $feed_settings ) ) {
			$feed_settings = $this->get_current_feed();
		}
		if ( isset( $_POST['_gform_setting_fc_crm_custom_type'] ) ) {
			$custom_crm = sanitize_text_field( $_POST['_gform_setting_fc_crm_custom_type'] );
		} elseif ( ! empty( $feed_settings['meta']['fc_crm_custom_type'] ) ) {
			$custom_crm = $feed_settings['meta']['fc_crm_custom_type'];
		} else {
			$custom_crm = 'no';
		}
		return $custom_crm;
	}

	/**
	 * Return the plugin's icon for the plugin/form settings menu.
	 *
	 * @since 1.8
	 *
	 * @return string
	 */
	public function get_menu_icon() {

		return file_get_contents( FORMSCRM_PLUGIN_PATH . 'includes/assets/icon.svg' );

	}

	public function ensure_upgrade() {

		if ( get_option( 'fc_crm_upgrade' ) ) {
			return false;
		}

		$feeds = $this->get_feeds();
		if ( empty( $feeds ) ) {

			// Force Add-On framework upgrade.
			$this->upgrade( '2.0' );
		}

		update_option( 'fc_crm_upgrade', 1 );
	}

	public function feed_list_columns() {
		return array(
			'feedName' => __( 'Name', 'formscrm' ),
		);
	}

	/**
	 * Sends data to API
	 *
	 * @param array  $entry Entry data.
	 * @param object $form Form data.
	 * @param array  $feed Feed data.
	 * @return void
	 */
	public function process_feed( $feed, $entry, $form ) {
		$settings  = $this->get_api_settings_custom( $feed );
		$feed_type = ! empty( $settings['fc_crm_type'] ) ? $settings['fc_crm_type'] : '';
		$this->include_library( $feed_type );

		$merge_vars         = array();
		$field_maps         = $this->get_field_map_fields( $feed, 'listFields' );
		$field_clientify_id = 0;

		if ( ! empty( $field_maps ) ) {
			// Normal WAY.
			foreach ( $field_maps as $var_key => $field_id ) {
				if ( ! empty( $field_id ) ) {
					$merge_vars[] = $this->get_value_from_field( $var_key, $field_id, $entry, $form );
				}
			}
		}

		// Dynamic Fields.
		foreach ( $form['fields'] as $field ) {
			if ( empty( $field_maps ) ) {
				if ( ! empty( $field->adminLabel ) && ! empty( $entry[ $field->id ] ) ) {
					$merge_vars[] = array(
						'name'  => $field->adminLabel,
						'value' => $entry[ $field->id ],
					);
				} elseif ( $field && RGFormsModel::get_input_type( $field ) == 'checkbox' ) {
					$value = array();
					foreach ( $field['inputs'] as $input ) {
						$index   = (string) $input['id'];
						$value[] = ! empty( $entry[ $index ] ) ? $entry[ $index ] : '';
					}
					$merge_vars[] = array(
						'name'  => $field->adminLabel,
						'value' => $value,
					);
				}
			}
			if ( 'clientify' === $feed_type && isset( $field->adminLabel ) && 'clientify_visitor_key' === $field->adminLabel ) {
				$field_clientify_id = $field->id;
			}
		}

		// Adds Clientify visitor key.
		if ( ! empty( $field_clientify_id ) && ! empty( $entry[ $field_clientify_id ] ) ) {
			$merge_vars[] = array(
				'name'  => 'visitor_key',
				'value' => $entry[ $field_clientify_id ],
			);
		}

		$override_custom_fields = apply_filters( 'formscrm_override_blank_custom_fields', false, $entry, $form, $feed );
		if ( ! $override_custom_fields ) {
			$merge_vars = $this->remove_blank_custom_fields( $merge_vars );
		}

		formscrm_debug_message( $settings );
		formscrm_debug_message( $merge_vars );

		// Fill meta settings.
		if ( ! empty( $feed['meta'] ) ) {
			foreach ( $feed['meta'] as $key => $value ) {
				if ( ! empty( $value ) ) {
					$settings[ $key ] = $value;
				}
			}
		}

		if ( isset( $feed['meta']['fc_crm_module'] ) ) {
			$settings['fc_crm_module'] = $feed['meta']['fc_crm_module'];
		}
		// Send info from entry and form filled.
		$settings['entry'] = $entry;

		// Sends the entry to CRM.
		$response_result = $this->crmlib->create_entry( $settings, $merge_vars );
		$api_status      = isset( $response_result['status'] ) ? $response_result['status'] : '';

		if ( 'error' === $api_status ) {
			$url     = isset( $response_result['url'] ) ? $response_result['url'] : '';
			$query   = isset( $response_result['query'] ) ? $response_result['query'] : '';
			$message = isset( $response_result['message'] ) ? $response_result['message'] : '';

			formscrm_debug_email_lead( $settings['fc_crm_type'], 'Error ' . $message, $merge_vars, $url, $query );

			$response_message = sprintf(
				// translators: %1$s CRM name %2$s Error message %3$s URL %4$s Query.
				__( 'Error creating %1$s Error: %2$s URL: %3$s QUERY: %4$s', 'formscrm' ),
				esc_html( $settings['fc_crm_type'] ),
				$message,
				$url,
				$query
			);
			$this->add_note( $entry['id'], $response_message, 'error' );
		} else {
			$response_message = sprintf(
				// translators: %1$s CRM name %2$s ID number of entry created.
				__( 'Success creating %1$s Entry ID: %2$s', 'formscrm' ),
				esc_html( $settings['fc_crm_type'] ),
				$response_result['id']
			);
			$this->add_note( $entry['id'], $response_message, 'success' );
			formscrm_debug_message( $response_result['id'] );
			gform_add_meta( $entry['id'], $settings['fc_crm_type'], $response_result['id'], $form['id'] );
		}
	}

	/**
	 * Returns the value of GF Field depending of type.
	 *
	 * @param array $field
	 * @return array
	 */
	public function get_value_from_field( $var_key, $field_id, $entry, $form ) {
		$field = RGFormsModel::get_field( $form, $field_id );
		if ( isset( $field['type'] ) && GFCommon::is_product_field( $field['type'] ) && rgar( $field, 'enablePrice' ) ) {
			$ary          = explode( '|', $entry[ $field_id ] );
			$product_name = count( $ary ) > 0 ? $ary[0] : '';
			return array(
				'name' => $var_key,
				'value' => $product_name,
			);
		} elseif ( $field && RGFormsModel::get_input_type( $field ) == 'checkbox' ) {
			$value = '';
			foreach ( $field['inputs'] as $input ) {
				$index   = (string) $input['id'];
				$value_n = apply_filters( 'formscrm_field_value_default', rgar( $entry, $index ), $form['id'], $field_id, $entry );
				$value .= $value_n;
				if ( $value_n ) {
					$value .= '|';
				}
			}
			$value        = substr( $value, 0, -1 );
			return array(
				'name'  => $var_key,
				'value' => $value,
			);
		} elseif ( $field && RGFormsModel::get_input_type( $field ) == 'multiselect' ) {
			$value = apply_filters( 'formscrm_field_value_multiselect', rgar( $entry, $field_id ), $form['id'], $field_id, $entry );
			$value = str_replace( ',', '|', $value );

			return array(
				'name'  => $var_key,
				'value' => $value,
			);
		} elseif ( $field && RGFormsModel::get_input_type( $field ) == 'textarea' ) {
			$value = apply_filters( 'formscrm_field_value_textarea', rgar( $entry, $field_id ), $form['id'], $field_id, $entry );
			return array(
				'name'  => $var_key,
				'value' => $this->fill_dynamic_value( $value, $entry, $form ),
			);
		} elseif ( $field && RGFormsModel::get_input_type( $field ) == 'name' && false === strpos( $field_id, '.' ) ) {
			$value = rgar( $entry, $field_id . '.3' ) . ' ' . rgar( $entry, $field_id . '.6' );
			return array(
				'name'  => $var_key,
				'value' => $value,
			);
		} else {
			$value = apply_filters( 'formscrm_field_value', rgar( $entry, $field_id ), $form['id'], $field_id, $entry );
			return array(
				'name'  => $var_key,
				'value' => $this->fill_dynamic_value( $value, $entry, $form ),
			);
		}
	}

	/**
	 * Fill field values dinamic with value
	 *
	 * @param string $field_value
	 * @param array $entry
	 * @return string
	 */
	private function fill_dynamic_value( $field_value, $entry, $form ) {
		if ( str_contains( $field_value, '{id:' ) || str_contains( $field_value, '{label:' ) ) { 
			$dynamic_value = $field_value;
			preg_match_all( '#\{(.*?)\}#', $field_value, $matches );
			if ( ! empty( $matches[1] ) && is_array( $matches[1] ) ) {
				foreach ( $matches[1] as $field ) {
					$mode = str_contains( $field, 'id:' ) ? 'id' : 'label';
					if ( 'id' === $mode ) {
						$field_id = (int) str_replace( 'id:', '', $field );
						$value    = isset( $entry[ $field_id ] ) ? $entry[ $field_id ] : '';
						if ( str_contains( $value, '[' ) ) {
							// is array.
							$clean_note_file = str_replace( '[', '', $value );
							$clean_note_file = str_replace( ']', '', $clean_note_file );
							$clean_note_file = str_replace( '"', '', $clean_note_file );
							$clean_note_file = str_replace( '\/', '/', $clean_note_file );

							$files     = explode( ',', $clean_note_file );
							$file_note = '';
							foreach ( $files as $file ) {
								$file_note .= $file . "\n";
							}
							$value = $file_note;
						} else {
							$value = isset( $entry[ $field_id ] ) ? $entry[ $field_id ] : '';
						}
					} else {
						$field_id   = str_replace( 'label:', '', $field );
						$field_obj  = RGFormsModel::get_field( $form, $field_id );
						$field_type = RGFormsModel::get_input_type( $field_obj );
						if ( 'radio' === $field_type || 'select' === $field_type ) {
							$value = array_search( $entry[ $field_id ], array_column( $field_obj['choices'], 'value', 'text' ) );
						} elseif ( 'checkbox' === $field_type ) {
							$search_values = array();
							$count_choices = count( $field_obj['choices'] );
							for ( $i = 1; $i <= $count_choices; $i++ ) {
								if ( ! empty( $entry[ $field_id . '.' . $i ] ) ) {
									$search_values[] = array_search( $field_id . '.' . $i, array_column( $field_obj['inputs'], 'id', 'label' ) );
								}
							}
							$value = implode( ', ', $search_values );
						} else {
							$value = isset( $entry[ $field_id ] ) ? $entry[ $field_id ] : '';
						}
					}
					$dynamic_value = str_replace( '{' . $field . '}', $value, $dynamic_value );
				}
			}
			return $dynamic_value;
		}
		return $field_value;
	}

	/**
	 * Remove blank custom fields
	 *
	 * @param  array $merge_vars Vars to send to API.
	 * @return array
	 */
	private static function remove_blank_custom_fields( $merge_vars ) {
		$i = 0;

		$count = count( $merge_vars );

		for ( $i = 0; $i < $count; $i++ ) {
			if ( rgblank( $merge_vars[ $i ]['value'] ) ) {
				unset( $merge_vars[ $i ] );
			}
		}
		// resort the array because items could have been removed, this will give an error from CRM if the keys are not in numeric sequence.
		sort( $merge_vars );
		return $merge_vars;
	}

	private function get_name( $entry, $field_id ) {

		// If field is simple (one input), simply return full content.
		$name = rgar( $entry, $field_id );
		if ( ! empty( $name ) ) {
			return $name;
		}

		// Complex field (multiple inputs). Join all pieces and create name.
		$prefix = trim( rgar( $entry, $field_id . '.2' ) );
		$first  = trim( rgar( $entry, $field_id . '.3' ) );
		$last   = trim( rgar( $entry, $field_id . '.6' ) );
		$suffix = trim( rgar( $entry, $field_id . '.8' ) );

		$name = $prefix;
		$name .= ! empty( $name ) && ! empty( $first ) ? " $first" : $first;
		$name .= ! empty( $name ) && ! empty( $last ) ? " $last" : $last;
		$name .= ! empty( $name ) && ! empty( $suffix ) ? " $suffix" : $suffix;

		return $name;
	}

	/**
	 * Logins to the CRM.
	 *
	 * @return boolean
	 */
	private function login_api_crm() {
		$login_result = false;

		// Logins to CRM.
		$settings = $this->get_api_settings_custom();

		if ( isset( $settings['fc_crm_type'] ) ) {
			$this->include_library( $settings['fc_crm_type'] );
		}

		if ( isset( $this->crmlib ) ) {
			$login_result = $this->crmlib->login( $settings );
			formscrm_debug_message( $login_result );
		}
		formscrm_testserver();

		return $login_result;
	}

} //from main class
