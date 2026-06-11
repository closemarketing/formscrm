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
	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	protected $_version = FORMSCRM_VERSION;
	/**
	 * Minimum Gravity Forms version.
	 *
	 * @var string
	 */
	protected $_min_gravityforms_version = '1.9.0';
	/**
	 * Plugin slug.
	 *
	 * @var string
	 */
	protected $_slug = 'formscrm';
	/**
	 * Plugin path.
	 *
	 * @var string
	 */
	protected $_path = 'formscrm/crm.php';
	/**
	 * Full path to main plugin file.
	 *
	 * @var string
	 */
	protected $_full_path = __FILE__;
	/**
	 * Plugin URL.
	 *
	 * @var string
	 */
	protected $_url = 'https://www.formscrm.com';
	/**
	 * Plugin title.
	 *
	 * @var string
	 */
	protected $_title = 'CRM Add-On';
	/**
	 * Short plugin title.
	 *
	 * @var string
	 */
	protected $_short_title = 'FormsCRM';
	/**
	 * Enable async feed processing.
	 *
	 * @var bool
	 */
	public $_async_feed_processing = true;

	/**
	 * Members plugin integration capabilities.
	 *
	 * @var array
	 */
	protected $_capabilities = array(
		'formscrm',
		'formscrm_uninstall',
	);

	/**
	 * Permissions for settings page.
	 *
	 * @var string
	 */
	protected $_capabilities_settings_page = 'formscrm';
	/**
	 * Permissions for form settings.
	 *
	 * @var string
	 */
	protected $_capabilities_form_settings = 'formscrm';
	/**
	 * Permissions for uninstall.
	 *
	 * @var string
	 */
	protected $_capabilities_uninstall = 'formscrm_uninstall';
	/**
	 * Enable Rocketgenius autoupgrade.
	 *
	 * @var bool
	 */
	protected $_enable_rg_autoupgrade = true;
	// phpcs:enable PSR2.Classes.PropertyDeclaration.Underscore, Squiz.Commenting.VariableComment

	/**
	 * Singleton instance.
	 *
	 * @var GFCRM
	 */
	private static $_instance = null; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

	/**
	 * CRM library instance.
	 *
	 * @var object
	 */
	private $crmlib;

	/**
	 * Get singleton instance.
	 *
	 * @return GFCRM
	 */
	public static function get_instance() {
		if ( null === self::$_instance ) {
			self::$_instance = new GFCRM();
		}

		return self::$_instance;
	}

	/**
	 * Init admin functions.
	 *
	 * @return void
	 */
	public function init_admin() {
		parent::init_admin();

		$this->ensure_upgrade();

		// Add custom columns to forms list.
		if ( is_admin() ) {
			add_filter( 'gform_form_list_columns', array( $this, 'add_feeds_column' ), 10 );
			add_action( 'gform_form_list_column_formscrm_feeds', array( $this, 'display_feeds_column' ), 10, 1 );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_forms_list_styles' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_module_search_scripts' ) );
			add_filter( 'gform_field_map_choices', array( $this, 'add_gravitypdf_field_map_choices' ), 10, 4 );
		}
	}

	/**
	 * Adds GravityPDF generated PDFs as selectable choices in the field map.
	 *
	 * @param array $field_groups       Current field groups.
	 * @param array $form               Current form object.
	 * @param array $field_filter       Field filters being applied.
	 * @param array $exclude_field_types Field types to exclude.
	 * @return array Modified field groups.
	 */
	public function add_gravitypdf_field_map_choices( $field_groups, $form, $field_filter, $exclude_field_types ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Required by WordPress filter hook.
		if ( ! class_exists( 'GPDFAPI' ) || empty( $form['id'] ) ) {
			return $field_groups;
		}

		$pdfs = GPDFAPI::get_form_pdfs( $form['id'] );

		if ( is_wp_error( $pdfs ) || empty( $pdfs ) ) {
			return $field_groups;
		}

		$pdf_choices = array();
		foreach ( $pdfs as $pdf_id => $pdf ) {
			if ( empty( $pdf['active'] ) ) {
				continue;
			}
			$pdf_choices[] = array(
				// Value uses GravityPDF's own merge tag format: {Name:pdf:ID}.
				'value' => '{' . $pdf['name'] . ':pdf:' . $pdf_id . '}',
				'label' => esc_html( $pdf['name'] ),
			);
		}

		if ( ! empty( $pdf_choices ) ) {
			$field_groups[] = array(
				'name'   => 'gravitypdf',
				'label'  => __( 'GravityPDF', 'formscrm' ),
				'fields' => $pdf_choices,
			);
		}

		return $field_groups;
	}

	/**
	 * Enqueue styles for forms list page
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_forms_list_styles( $hook ) {
		// Only load on Gravity Forms pages.
		if ( 'toplevel_page_gf_edit_forms' === $hook || strpos( $hook, 'gf_' ) !== false ) {
			wp_enqueue_style(
				'formscrm-forms-list',
				FORMSCRM_PLUGIN_URL . 'includes/assets/formscrm-admin.css',
				array(),
				FORMSCRM_VERSION
			);
		}
	}

	/**
	 * Enqueues searchable module select script for GF feed settings pages.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_module_search_scripts( $hook ) {
		if ( strpos( $hook, 'gf_' ) === false ) {
			return;
		}
		wp_enqueue_script(
			'formscrm-module-search',
			FORMSCRM_PLUGIN_URL . 'includes/formscrm-library/js/module-search.js',
			array(),
			FORMSCRM_VERSION,
			true
		);
	}

	/**
	 * Get CRM fields configuration.
	 *
	 * @param bool   $select_crm_type Whether to select CRM type.
	 * @param array  $settings        Feed settings.
	 * @param string $page           Current page context.
	 * @return array
	 */
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
				'name'       => $prefix . 'username',
				'label'      => __( 'Username', 'formscrm' ),
				'type'       => 'text',
				'class'      => 'medium',
				'dependency' => array(
					'field'  => $field_name,
					'values' => formscrm_get_dependency_username(),
				),
			),
			array(
				'name'          => $prefix . 'password',
				'label'         => __( 'Password', 'formscrm' ),
				'type'          => 'api_key',
				'class'         => 'medium',
				'tooltip'       => __( 'Use the password of the actual user.', 'formscrm' ),
				'tooltip_class' => 'tooltipclass',
				'dependency'    => array(
					'field'  => $field_name,
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
					'field'  => $field_name,
					'values' => formscrm_get_dependency_apipassword(),
				),
			),
			array(
				'name'          => $prefix . 'apisales',
				'label'         => __( 'Password and Security Key', 'formscrm' ),
				'type'          => 'api_key',
				'class'         => 'medium',
				'tooltip'       => __( '"Password""SecurityKey" Go to My Settings / Reset my Security Key.', 'formscrm' ),
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
		$fields = array();
		$fields = $this->get_crm_fields( true, array(), 'settings' );

		// API Connection Status.
		$fields = array_merge(
			$fields,
			array(
				array(
					'label' => __( 'API Connection Status', 'formscrm' ),
					'type'  => 'connection_status',
					'name'  => 'fc_crm_connection_status',
				),
			),
		);

		// Expert Mode.
		$fields = array_merge(
			$fields,
			array(
				array(
					'label'   => __( 'Mode', 'formscrm' ),
					'type'    => 'checkbox',
					'name'    => 'fc_crm_mode_expert',
					'tooltip' => __( 'Enable this option to show all fields of the CRM.', 'formscrm' ),
					'choices' => array(
						array(
							'label' => __( 'Enable Expert Mode', 'formscrm' ),
							'name'  => 'fc_crm_mode_expert',
						),
					),
				),
			),
		);

		return array(
			array(
				'title'       => __( 'CRM Account Information', 'formscrm' ),
				'description' => __( 'Use this connector with CRM software. Use Gravity Forms to collect customer information and automatically add them to your CRM Leads.', 'formscrm' ),
				'fields'      => $fields,
			),
		);
	}

	/**
	 * Settings API Key
	 *
	 * @param array $field   Field.
	 * @param bool  $display Display.
	 * @return string
	 */
	public function settings_api_key( $field, $display = true ) {
		$field['type'] = 'text';
		$api_key_field = $this->settings_text( $field, false );

		// Switch type="text" to type="password" so the key is not visible.
		$api_key_field = str_replace( 'type="text"', 'type="password"', $api_key_field );

		$caption = '<small>' . sprintf( esc_html__( 'Find a Password or API key depending of CRM.', 'formscrm' ) ) . '</small>';

		if ( $display ) {
			echo esc_html( $api_key_field ) . '</br>' . esc_html( $caption );
		}

		return $api_key_field . '</br>' . $caption;
	}

	/**
	 * Settings Connection Status field.
	 *
	 * Renders the API connection status indicator for plugin settings.
	 *
	 * @param array $field   Field configuration.
	 * @param bool  $display Whether to display or return the HTML.
	 * @return string HTML output.
	 */
	public function settings_connection_status( $field, $display = true ) {
		$settings  = $this->get_plugin_settings();
		$help_text = __( 'Save settings and reload the page to test the connection.', 'formscrm' );
		$html      = formscrm_get_connection_status_html( $settings, 'badge', $help_text );

		if ( $display ) {
			formscrm_render_connection_status( $settings, 'badge', $help_text );
		}

		return $html;
	}

	/**
	 * Settings Feed Connection Status field.
	 *
	 * Renders the API connection status indicator for feed settings.
	 *
	 * @param array $field   Field configuration.
	 * @param bool  $display Whether to display or return the HTML.
	 * @return string HTML output.
	 */
	public function settings_feed_connection_status( $field, $display = true ) {
		$settings    = $this->get_api_settings_custom();
		$status_data = formscrm_check_connection_status( $settings );
		$help_text   = '';

		// Show help text only for error states.
		if ( 'disconnected' === $status_data['status'] || 'error' === $status_data['status'] ) {
			$help_text = __( 'Please check your CRM credentials in the FormsCRM settings.', 'formscrm' );
		}

		$html = formscrm_build_status_html( $status_data, 'badge', $help_text );

		if ( $display ) {
			formscrm_render_connection_status( $settings, 'badge', $help_text );
		}

		return $html;
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
	 * Get Settings fields
	 *
	 * @return array
	 */
	public function feed_settings_fields() {
		$settings     = $this->get_api_settings_custom();
		$custom_crm   = $this->get_custom_crm();
		$settings_crm = isset( $settings['fc_crm_type'] ) ? $settings['fc_crm_type'] : '';

		if ( empty( $settings['fc_crm_type'] ) ) {
			return array();
		} elseif ( 'no' !== $custom_crm ) {
			$settings['fc_crm_type'] = $custom_crm;
		}

		$this->crmlib = formscrm_get_api_class( $settings['fc_crm_type'] );

		$settings['fc_crm_module']      = isset( $_POST['_gform_setting_fc_crm_module'] ) ? sanitize_text_field( wp_unslash( $_POST['_gform_setting_fc_crm_module'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification handled by Gravity Forms.
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
								'choices'  => array_merge(
									array(
										// translators: %s is the name of the CRM as defined in settings.
										array(
											'label' => sprintf(
												// translators: %s is the name of the CRM as defined in settings.
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
						array(
							array(
								'name'        => 'fc_crm_webhook',
								'label'       => __( 'FormsCRM webhook', 'formscrm' ),
								'type'        => 'text',
								'class'       => 'medium',
								'input_type'  => 'url',
								'placeholder' => __( 'https://your-webhook-url.com', 'formscrm' ),
								'tooltip'     => '<h6>' . __( 'FormsCRM webhook', 'formscrm' ) . '</h6>' . __( 'Enter a URL to send a webhook form data received from CRM.', 'formscrm' ),
							),
						)
					),
				),
			),
		);
	}

	/**
	 * Get CRM fields configuration for feed.
	 *
	 * @param array $settings Feed settings array.
	 * @return array CRM field configuration.
	 */
	private function get_crm_feed_fields( $settings ) {
		$crm_feed_fields = array();
		$feed_settings   = $this->get_current_feed();
		$login_crm       = $this->login_api_crm();

		// Add connection status field.
		$crm_feed_fields[] = array(
			'name'  => 'fc_feed_connection_status',
			'label' => __( 'API Connection Status', 'formscrm' ),
			'type'  => 'feed_connection_status',
		);

		if ( ! $login_crm || ( is_array( $login_crm ) && isset( $login_crm['status'] ) && 'error' === $login_crm['status'] ) ) {
			return $crm_feed_fields;
		}

		$module          = $this->get_actual_feed_value( 'fc_crm_module', $feed_settings );
		$modules_choices = $this->crmlib->list_modules( $settings );

		if ( empty( $modules_choices ) ) {
			$modules_choices = array(
				array(
					'label' => esc_html__( 'No modules found. Check your API credentials.', 'formscrm' ),
					'value' => '',
				),
			);
		}

		$crm_feed_fields[] = array(
			'name'     => 'fc_crm_module',
			'label'    => __( 'CRM Module', 'formscrm' ),
			'type'     => 'select',
			'class'    => 'medium',
			'onchange' => 'jQuery(this).parents("form").submit();',
			'choices'  => $modules_choices,
		);
		if ( empty( $module ) ) {
			$crm_feed_fields[] = array(
				'name'  => 'fc_select_module',
				'label' => esc_html__( 'Select Module and save to select merge values', 'formscrm' ),
				'type'  => 'hidden',
			);
		}

		$crm_feed_fields[] = array(
			'name'       => 'listFields',
			'label'      => __( 'Map Fields', 'formscrm' ),
			'type'       => 'field_map',
			'dependency' => 'fc_crm_module',
			'field_map'  => $this->crmlib->list_fields( $settings, $module ),
			'tooltip'    => '<h6>' . __( 'Map Fields', 'formscrm' ) . '</h6>' . __( 'Associate your CRM custom fields to the appropriate Gravity Form fields by selecting the appropriate form field from the list.', 'formscrm' ),
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

		return $crm_feed_fields;
	}

	/**
	 * Get Settings with custom CRM in feed
	 *
	 * @param array $feed Feed settings array.
	 * @return array Settings array with custom CRM configuration.
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
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verification handled by Gravity Forms.
		foreach ( FORMSCRM_CRED_VARIABLES as $variable ) {
			if ( isset( $_POST[ '_gform_setting_fc_crm_custom_' . $variable ] ) ) {
				$settings[ 'fc_crm_' . $variable ] = sanitize_text_field( wp_unslash( $_POST[ '_gform_setting_fc_crm_custom_' . $variable ] ) );
			} elseif ( isset( $feed['meta'][ 'fc_crm_custom_' . $variable ] ) ) {
				$settings[ 'fc_crm_' . $variable ] = $feed['meta'][ 'fc_crm_custom_' . $variable ];
			} elseif ( isset( $settings[ 'fc_crm_custom_' . $variable ] ) ) {
				$settings[ 'fc_crm_' . $variable ] = $settings[ 'fc_crm_custom_' . $variable ];
				unset( $settings[ 'fc_crm_custom_' . $variable ] );
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		return $settings;
	}

	/**
	 * Get actual feed value
	 *
	 * @param string $value         Value key to retrieve.
	 * @param array  $feed_settings Feed settings array.
	 * @return string Feed value.
	 */
	private function get_actual_feed_value( $value, $feed_settings ) {
		$feed_value = '';
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verification handled by Gravity Forms.
		if ( isset( $_POST[ '_gform_setting_' . $value ] ) ) {
			$feed_value = sanitize_text_field( wp_unslash( $_POST[ '_gform_setting_' . $value ] ) );
		} elseif ( isset( $feed_settings['meta'][ $value ] ) ) {
			$feed_value = $feed_settings['meta'][ $value ];
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		return $feed_value;
	}

	/**
	 * Get custom crm from feed
	 *
	 * @param array $feed_settings Feed settings array.
	 * @return string Custom CRM type.
	 */
	private function get_custom_crm( $feed_settings = array() ) {
		if ( empty( $feed_settings ) ) {
			$feed_settings = $this->get_current_feed();
		}
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verification handled by Gravity Forms.
		if ( isset( $_POST['_gform_setting_fc_crm_custom_type'] ) ) {
			$custom_crm = sanitize_text_field( wp_unslash( $_POST['_gform_setting_fc_crm_custom_type'] ) );
		} elseif ( ! empty( $feed_settings['meta']['fc_crm_custom_type'] ) ) {
			$custom_crm = $feed_settings['meta']['fc_crm_custom_type'];
		} else {
			$custom_crm = 'no';
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing
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
		return file_get_contents( FORMSCRM_PLUGIN_PATH . 'includes/assets/icon.svg' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	}

	/**
	 * Ensure database upgrade
	 *
	 * @return bool False if already upgraded, true if upgrade performed.
	 */
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
		return true;
	}

	/**
	 * Add feeds column to forms list
	 *
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function add_feeds_column( $columns ) {
		$columns['formscrm_feeds'] = esc_html__( 'Connected Feeds', 'formscrm' );
		return $columns;
	}

	/**
	 * Display feeds column content
	 *
	 * @param array $form Form object.
	 * @return void
	 */
	public function display_feeds_column( $form ) {
		// Get form ID from array or object.
		$form_id = 0;

		if ( is_array( $form ) ) {
			$form_id = isset( $form['id'] ) ? absint( $form['id'] ) : 0;
		} elseif ( is_object( $form ) ) {
			$form_id = isset( $form->id ) ? absint( $form->id ) : 0;
		}

		// If no form ID, show disconnected.
		if ( ! $form_id ) {
			echo '<span class="gform-status-indicator gform-status--inactive">● ' . esc_html__( 'Disconnected', 'formscrm' ) . '</span>';
			return;
		}

		try {
			// Get feeds for this form.
			$feeds = $this->get_feeds( $form_id );

			// No feeds - show Disconnected.
			if ( empty( $feeds ) || ! is_array( $feeds ) ) {
				return;
			}

			// Has feeds - show Connected.
			$feed_count = count( $feeds );

			echo '<div class="formscrm-feeds-wrapper">';
			echo '<span class="gform-status-indicator gform-status--active">' . esc_html__( 'Connected', 'formscrm' ) . '</span>';

			// Show feed details.
			echo '<div class="formscrm-feeds-list">';

			foreach ( $feeds as $feed ) {
				if ( ! is_array( $feed ) || empty( $feed['meta'] ) ) {
					continue;
				}

				$feed_name = isset( $feed['meta']['feedName'] ) ? $feed['meta']['feedName'] : __( 'Unnamed Feed', 'formscrm' );
				$crm_type  = '';

				// Get CRM type.
				if ( ! empty( $feed['meta']['fc_crm_custom_type'] ) && 'no' !== $feed['meta']['fc_crm_custom_type'] ) {
					$crm_type = $feed['meta']['fc_crm_custom_type'];
				} else {
					$settings = $this->get_plugin_settings();
					if ( ! empty( $settings['fc_crm_type'] ) ) {
						$crm_type = $settings['fc_crm_type'];
					}
				}

				$is_active = ! empty( $feed['is_active'] );
				$status    = $is_active ? '✓' : '✗';
				$color     = $is_active ? '#46b450' : '#dc3232';
				$title     = $is_active ? __( 'Active', 'formscrm' ) : __( 'Inactive', 'formscrm' );

				echo '<div class="formscrm-feed-item">';
				printf(
					'<span style="color: %s; font-weight: bold;" title="%s">%s</span> ',
					esc_attr( $color ),
					esc_attr( $title ),
					esc_html( $status )
				);
					echo '<span class="formscrm-feed-name">' . esc_html( $feed_name ) . '</span>';

				if ( ! empty( $crm_type ) ) {
					echo ' <span class="formscrm-feed-crm">(' . esc_html( ucfirst( $crm_type ) ) . ')</span>';
				}
				echo '</div>';
			}

			// Show total if more than 1.
			if ( $feed_count > 1 ) {
				echo '<div class="formscrm-feed-total">';
				printf(
					/* translators: %d: number of feeds */
					esc_html__( 'Total: %d feeds', 'formscrm' ),
					absint( $feed_count )
				);
				echo '</div>';
			}

			echo '</div>'; // .formscrm-feeds-list
			echo '</div>'; // .formscrm-feeds-wrapper
		} catch ( Exception $e ) {
			echo '<span class="gform-status-indicator gform-status--inactive">● ' . esc_html__( 'Error', 'formscrm' ) . '</span>';
		}
	}

	/**
	 * Get feed list columns
	 *
	 * @return array Column configuration.
	 */
	public function feed_list_columns() {
		return array(
			'feedName' => __( 'Name', 'formscrm' ),
		);
	}

	/**
	 * Sends data to API
	 *
	 * @param array  $feed  Feed data.
	 * @param array  $entry Entry data.
	 * @param object $form  Form data.
	 * @return void
	 */
	public function process_feed( $feed, $entry, $form ) {
		$settings     = $this->get_api_settings_custom( $feed );
		$feed_type    = ! empty( $settings['fc_crm_type'] ) ? $settings['fc_crm_type'] : '';
		$this->crmlib = formscrm_get_api_class( $feed_type );

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
				} elseif ( $field && 'checkbox' === RGFormsModel::get_input_type( $field ) ) {
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

		// Filter before send to CRM.
		$merge_vars = apply_filters( 'formscrm_merge_vars_before_send', $merge_vars, $settings, $entry );

		// Sends the entry to CRM.
		$response_result = $this->crmlib->create_entry( $settings, $merge_vars );
		$api_status      = isset( $response_result['status'] ) ? $response_result['status'] : '';

		if ( 'error' === $api_status ) {
			$url     = isset( $response_result['url'] ) ? $response_result['url'] : '';
			$query   = isset( $response_result['query'] ) ? $response_result['query'] : '';
			$message = isset( $response_result['message'] ) ? $response_result['message'] : '';

			$form_info = array(
				'form_type'       => 'gravityforms',
				'form_type_title' => 'Gravity Forms',
				'form_id'         => isset( $form['id'] ) ? $form['id'] : '',
				'form_name'       => isset( $form['title'] ) ? $form['title'] : '',
				'entry_id'        => isset( $entry['id'] ) ? $entry['id'] : '',
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
			$this->add_note( $entry['id'], $response_message, 'error' );
		} else {
			$response_message = sprintf(
				// translators: %1$s CRM name %2$s CRM type %3$s ID number of entry created.
				__( 'Success creating %1$s (%2$s) Entry ID: %3$s', 'formscrm' ),
				isset( $settings['fc_crm_name'] ) ? esc_html( $settings['fc_crm_name'] ) : '',
				esc_html( $settings['fc_crm_type'] ),
				$response_result['id'],
				$response_result['message'] ?? ''
			);
			$this->add_note( $entry['id'], $response_message, 'success' );
			formscrm_debug_message( $response_result['id'] );
			formscrm_send_webhook( $settings, $response_result );
			gform_add_meta( $entry['id'], $settings['fc_crm_type'], $response_result['id'], $form['id'] );
		}
	}

	/**
	 * Returns the value of GF Field depending of type.
	 *
	 * @param string $var_key  Variable key.
	 * @param int    $field_id Field ID.
	 * @param array  $entry    Entry data.
	 * @param array  $form     Form configuration.
	 * @return array Field value array with name and value.
	 */
	public function get_value_from_field( $var_key, $field_id, $entry, $form ) {
		// GravityPDF merge tag format: {PDF_Name:pdf:PDF_ID} — handle before RGFormsModel lookup.
		if ( is_string( $field_id ) && false !== strpos( $field_id, ':pdf:' ) ) {
			$value  = '';
			$pdf_id = '';

			// Extract PDF ID from merge tag {Name:pdf:ID}.
			if ( preg_match( '/\{[^:]+:pdf:([^}]+)\}/', $field_id, $matches ) ) {
				$pdf_id = $matches[1];
			}

			if ( class_exists( 'GPDFAPI' ) && ! empty( $pdf_id ) && ! empty( $entry['id'] ) ) {
				// get_pdf_url is an instance method on Model_PDF, not a static GPDFAPI method.
				$model_pdf = GPDFAPI::get_pdf_class( 'model' );

				if ( ! is_wp_error( $model_pdf ) && method_exists( $model_pdf, 'get_pdf_url' ) ) {
					$pdf_url = $model_pdf->get_pdf_url( $pdf_id, $entry['id'] );

					if ( ! empty( $pdf_url ) ) {
						$value = $pdf_url;
					}
				}
			}

			return array(
				'name'  => $var_key,
				'value' => apply_filters( 'formscrm_field_value_gravitypdf', $value, $form['id'], $pdf_id, $entry ),
			);
		}

		$field = RGFormsModel::get_field( $form, $field_id );
		if ( isset( $field['type'] ) && GFCommon::is_product_field( $field['type'] ) && rgar( $field, 'enablePrice' ) ) {
			$ary          = explode( '|', $entry[ $field_id ] );
			$product_name = count( $ary ) > 0 ? $ary[0] : '';
			return array(
				'name'  => $var_key,
				'value' => $product_name,
			);
		} elseif ( $field && 'checkbox' === RGFormsModel::get_input_type( $field ) ) {
			$value = '';
			foreach ( $field['inputs'] as $input ) {
				$index   = (string) $input['id'];
				$value_n = apply_filters( 'formscrm_field_value_default', rgar( $entry, $index ), $form['id'], $field_id, $entry );
				$value  .= $value_n;
				if ( $value_n ) {
					$value .= '|';
				}
			}
			$value = substr( $value, 0, -1 );
			return array(
				'name'  => $var_key,
				'value' => $value,
			);
		} elseif ( $field && 'multiselect' === RGFormsModel::get_input_type( $field ) ) {
			$value = apply_filters( 'formscrm_field_value_multiselect', rgar( $entry, $field_id ), $form['id'], $field_id, $entry );
			$value = str_replace( ',', '|', $value );

			return array(
				'name'  => $var_key,
				'value' => $value,
			);
		} elseif ( $field && 'fileupload' === RGFormsModel::get_input_type( $field ) ) {
			$file_value = rgar( $entry, $field_id );
			$value      = '';
			if ( ! empty( $file_value ) ) {
				// Multiple files are stored as a JSON array, single file as plain URL.
				$files = json_decode( $file_value, true );
				if ( is_array( $files ) ) {
					$value = implode( ', ', array_filter( $files ) );
				} else {
					$value = $file_value;
				}
			}
			return array(
				'name'  => $var_key,
				'value' => apply_filters( 'formscrm_field_value_fileupload', $value, $form['id'], $field_id, $entry ),
			);
		} elseif ( $field && 'textarea' === RGFormsModel::get_input_type( $field ) ) {
			$value = apply_filters( 'formscrm_field_value_textarea', rgar( $entry, $field_id ), $form['id'], $field_id, $entry );
			return array(
				'name'  => $var_key,
				'value' => $this->fill_dynamic_value( $value, $entry, $form ),
			);
		} elseif ( $field && 'name' === RGFormsModel::get_input_type( $field ) && false === strpos( $field_id, '.' ) ) {
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
	 * @param string $field_value Field value to process.
	 * @param array  $entry       Entry data.
	 * @param array  $form        Form configuration.
	 * @return string Processed field value.
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
							$value = array_search( $entry[ $field_id ], array_column( $field_obj['choices'], 'value', 'text' ), true );
						} elseif ( 'checkbox' === $field_type ) {
							$search_values = array();
							$count_choices = count( $field_obj['choices'] );
							for ( $i = 1; $i <= $count_choices; $i++ ) {
								if ( ! empty( $entry[ $field_id . '.' . $i ] ) ) {
									$search_values[] = array_search( $field_id . '.' . $i, array_column( $field_obj['inputs'], 'id', 'label' ), true );
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

	/**
	 * Get name from entry field
	 *
	 * @param array $entry    Entry data.
	 * @param int   $field_id Field ID.
	 * @return string Name value.
	 */
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

		$name  = $prefix;
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
			$this->crmlib = formscrm_get_api_class( $settings['fc_crm_type'] );
		}

		if ( isset( $this->crmlib ) ) {
			$login_result = $this->crmlib->login( $settings );
			formscrm_debug_message( $login_result );
		}
		formscrm_testserver();

		return $login_result;
	}
} //from main class
