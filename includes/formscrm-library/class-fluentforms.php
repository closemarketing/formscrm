<?php
/**
 * FluentForms Wrapper
 *
 * @package   WordPress
 * @author    David Perez <david@closemarketing.es>
 * @copyright 2025 Closemarketing
 * @version   4.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Library for FluentForms Settings
 */
class FORMSCRM_FluentForms_Settings {

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
		add_action( 'fluentform_loaded', array( $this, 'init' ) );
	}

	/**
	 * Initialize hooks after FluentForms is loaded
	 *
	 * @return void
	 */
	public function init() {
		// Global settings - register in FluentForms global addons (this creates the menu item).
		add_filter( 'fluentform/global_addons', array( $this, 'register_global_addon' ), 12, 1 );
		add_filter( 'fluentform/global_settings_components', array( $this, 'add_global_settings_component' ), 12, 1 );
		add_filter( 'fluentform/global_integration_settings_formscrm', array( $this, 'get_global_settings' ), 12, 1 );
		add_filter( 'fluentform/global_integration_fields_formscrm', array( $this, 'get_global_fields' ), 12, 1 );
		add_action( 'fluentform/save_global_integration_settings_formscrm', array( $this, 'save_global_settings_action' ), 12, 1 );
		add_action( 'fluentform/global_settings_component_general-formscrm-settings', array( $this, 'render_global_settings_vue_component' ) );
		
		// AJAX endpoint to get fields based on CRM type.
		// add_action( 'wp_ajax_formscrm_get_fields_by_crm_type', array( $this, 'ajax_get_fields_by_crm_type' ) );
		
		// Enqueue JavaScript for global settings.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_global_settings_scripts' ) );

		// Form-specific settings.
		add_filter( 'fluentform_form_settings_menu', array( $this, 'add_form_settings_menu' ), 10, 1 );
		add_action( 'wp_ajax_formscrm_fluentforms_save_settings', array( $this, 'save_settings' ) );
		add_action( 'fluentform_submission_inserted', array( $this, 'process_entry' ), 20, 3 );
		add_action( 'fluentform_render_form_settings_formscrm', array( $this, 'render_settings' ), 10, 1 );
	}

	/**
	 * Register FormsCRM in global addons (creates menu item)
	 *
	 * @param array $addons Existing addons.
	 * @return array
	 */
	public function register_global_addon( $addons ) {
		$global_settings = get_option( 'formscrm_fluentforms_global_settings', array() );
		$is_configured   = ! empty( $global_settings['fc_crm_type'] );

		$addons['formscrm'] = array(
			'title'                   => __( 'FormsCRM', 'formscrm' ),
			'category'                => 'crm',
			'disable_global_settings' => 'no',
			'description'             => __( 'Connect your forms with CRM, ERP and Email Marketing platforms.', 'formscrm' ),
			'config_url'              => admin_url( 'admin.php?page=fluent_forms_settings#general-formscrm-settings' ),
			'logo'                    => FORMSCRM_PLUGIN_URL . 'includes/assets/images/addon-icon-wpforms.png',
			'enabled'                 => $is_configured ? 'yes' : 'no',
		);
		return $addons;
	}

	/**
	 * Add global settings component
	 *
	 * @param array $components Existing components.
	 * @return array
	 */
	public function add_global_settings_component( $components ) {
		$components['formscrm'] = array(
			'hash'         => 'general-formscrm-settings',
			'component'    => 'general-integration-settings',
			'settings_key' => 'formscrm',
			'title'        => __( 'FormsCRM', 'formscrm' ),
		);
		return $components;
	}

	/**
	 * Render global settings Vue component placeholder
	 * FluentForms handles rendering via Vue.js, but we provide this for compatibility
	 *
	 * @return void
	 */
	public function render_global_settings_vue_component() {
		// FluentForms uses Vue.js components for rendering, so this is mostly handled automatically.
		// But we can add any custom HTML if needed.
	}

	/**
	 * Enqueue scripts for global settings page
	 *
	 * @return void
	 */
	public function enqueue_global_settings_scripts() {
		// Only enqueue on FluentForms settings page.
		if ( ! isset( $_GET['page'] ) || 'fluent_forms_settings' !== $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		// Only enqueue when viewing FormsCRM settings or on the settings page.
		$hash = '';
		if ( isset( $_GET['hash'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$hash = sanitize_text_field( wp_unslash( $_GET['hash'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		} elseif ( isset( $_SERVER['REQUEST_URI'] ) && strpos( $_SERVER['REQUEST_URI'], '#general-formscrm-settings' ) !== false ) {
			$hash = 'general-formscrm-settings';
		}

		// Enqueue on settings page (will check hash in JS if needed).
		wp_enqueue_script(
			'formscrm-fluentforms-admin',
			FORMSCRM_PLUGIN_URL . 'includes/assets/scripts/formscrm-fluentforms-admin.js',
			array(),
			FORMSCRM_VERSION,
			true
		);

		// Localize script with AJAX URL, nonce, and dependencies.
		wp_localize_script(
			'formscrm-fluentforms-admin',
			'formscrmAjax',
			array(
				'ajaxurl'     => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( 'formscrm_ajax_nonce' ),
				'dependencies' => array(
					'url'         => formscrm_get_dependency_url(),
					'username'    => formscrm_get_dependency_username(),
					'password'    => formscrm_get_dependency_password(),
					'apipassword' => formscrm_get_dependency_apipassword(),
					'apisales'    => formscrm_get_dependency_apisales(),
					'odoodb'      => formscrm_get_dependency_odoodb(),
				),
			)
		);
	}

	/**
	 * Get global settings for FluentForms
	 *
	 * @param array $settings Existing settings.
	 * @return array
	 */
	public function get_global_settings( $settings ) {
		$global_settings = get_option( 'formscrm_fluentforms_global_settings', array() );
		return wp_parse_args( $global_settings, array() );
	}

	/**
	 * Get global fields configuration
	 * Returns contextual fields based on selected CRM type
	 *
	 * @param array $fields Existing fields.
	 * @return array
	 */
	public function get_global_fields( $fields ) {
		$global_settings = get_option( 'formscrm_fluentforms_global_settings', array() );
		$crm_type        = isset( $global_settings['fc_crm_type'] ) ? $global_settings['fc_crm_type'] : '';
		
		$crm_choices = formscrm_get_choices();
		$crm_options = array();
		foreach ( $crm_choices as $choice ) {
			$crm_options[ $choice['value'] ] = $choice['label'];
		}

		// Base fields array - always include CRM type.
		$fields_config = array(
			'fc_crm_type' => array(
				'type'        => 'select',
				'label'       => __( 'CRM Type', 'formscrm' ),
				'options'     => $crm_options,
				'label_tips'  => __( 'Select your CRM type to see relevant credential fields.', 'formscrm' ),
			),
		);

		// Add all credential fields with dependencies - FluentForms will show/hide them automatically.
		$fields_config['fc_crm_url'] = array(
			'type'        => 'text',
			'label'       => __( 'CRM URL', 'formscrm' ),
			'label_tips'  => __( 'Enter your CRM URL', 'formscrm' ),
			'placeholder' => __( 'https://your-crm.com', 'formscrm' ),
			'dependency'  => array(
				array(
					'depends_on' => 'fc_crm_type',
					'operator'   => 'in_array',
					'value'      => formscrm_get_dependency_url(),
				),
			),
		);

		$fields_config['fc_crm_username'] = array(
			'type'        => 'text',
			'label'       => __( 'Username', 'formscrm' ),
			'label_tips'  => __( 'Enter your CRM username', 'formscrm' ),
			'placeholder' => __( 'Username', 'formscrm' ),
			'dependency'  => array(
				array(
					'depends_on' => 'fc_crm_type',
					'operator'   => 'in_array',
					'value'      => formscrm_get_dependency_username(),
				),
			),
		);

		$fields_config['fc_crm_password'] = array(
			'type'        => 'password',
			'label'       => __( 'Password', 'formscrm' ),
			'label_tips'  => __( 'Enter your CRM password', 'formscrm' ),
			'placeholder' => __( 'Password', 'formscrm' ),
			'dependency'  => array(
				array(
					'depends_on' => 'fc_crm_type',
					'operator'   => 'in_array',
					'value'      => formscrm_get_dependency_password(),
				),
			),
		);

		$fields_config['fc_crm_apipassword'] = array(
			'type'        => 'password',
			'label'       => __( 'API Password', 'formscrm' ),
			'label_tips'  => __( 'Enter your CRM API password or token', 'formscrm' ),
			'placeholder' => __( 'API Password', 'formscrm' ),
			'dependency'  => array(
				array(
					'depends_on' => 'fc_crm_type',
					'operator'   => 'in_array',
					'value'      => formscrm_get_dependency_apipassword(),
				),
			),
		);

		$fields_config['fc_crm_apisales'] = array(
			'type'        => 'text',
			'label'       => __( 'API Sales Key', 'formscrm' ),
			'label_tips'  => __( 'Enter your CRM API sales key', 'formscrm' ),
			'placeholder' => __( 'API Sales Key', 'formscrm' ),
			'dependency'  => array(
				array(
					'depends_on' => 'fc_crm_type',
					'operator'   => 'in_array',
					'value'      => formscrm_get_dependency_apisales(),
				),
			),
		);

		$fields_config['fc_crm_odoodb'] = array(
			'type'        => 'text',
			'label'       => __( 'Odoo Database Name', 'formscrm' ),
			'label_tips'  => __( 'Enter your Odoo database name', 'formscrm' ),
			'placeholder' => __( 'Database Name', 'formscrm' ),
			'dependency'  => array(
				array(
					'depends_on' => 'fc_crm_type',
					'operator'   => 'in_array',
					'value'      => formscrm_get_dependency_odoodb(),
				),
			),
		);

		return array(
			'logo'             => FORMSCRM_PLUGIN_URL . 'includes/assets/images/addon-icon-wpforms.png',
			'menu_title'       => __( 'FormsCRM Settings', 'formscrm' ),
			'menu_description' => __( 'FormsCRM connects your forms with CRM, ERP and Email Marketing platforms. Configure your default CRM credentials here. These settings can be used globally or overridden per form.', 'formscrm' ),
			'valid_message'    => __( 'FormsCRM is configured', 'formscrm' ),
			'invalid_message'  => __( 'FormsCRM is not configured', 'formscrm' ),
			'save_button_text' => __( 'Save Settings', 'formscrm' ),
			'fields'           => $fields_config,
		);
	}

	/**
	 * Save global settings action (called by FluentForms)
	 *
	 * @param array $settings Settings to save.
	 * @return void
	 */
	public function save_global_settings_action( $settings ) {
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$saved_settings = array();
		
		if ( isset( $settings['fc_crm_type'] ) ) {
			$saved_settings['fc_crm_type'] = sanitize_text_field( wp_unslash( $settings['fc_crm_type'] ) );
		}
		if ( isset( $settings['fc_crm_url'] ) ) {
			$saved_settings['fc_crm_url'] = sanitize_text_field( wp_unslash( $settings['fc_crm_url'] ) );
		}
		if ( isset( $settings['fc_crm_username'] ) ) {
			$saved_settings['fc_crm_username'] = sanitize_text_field( wp_unslash( $settings['fc_crm_username'] ) );
		}
		if ( isset( $settings['fc_crm_password'] ) ) {
			$saved_settings['fc_crm_password'] = sanitize_text_field( wp_unslash( $settings['fc_crm_password'] ) );
		}
		if ( isset( $settings['fc_crm_apipassword'] ) ) {
			$saved_settings['fc_crm_apipassword'] = sanitize_text_field( wp_unslash( $settings['fc_crm_apipassword'] ) );
		}
		if ( isset( $settings['fc_crm_apisales'] ) ) {
			$saved_settings['fc_crm_apisales'] = sanitize_text_field( wp_unslash( $settings['fc_crm_apisales'] ) );
		}
		if ( isset( $settings['fc_crm_odoodb'] ) ) {
			$saved_settings['fc_crm_odoodb'] = sanitize_text_field( wp_unslash( $settings['fc_crm_odoodb'] ) );
		}

		update_option( 'formscrm_fluentforms_global_settings', array_filter( $saved_settings ) );
		
		// Return success response to FluentForms.
		wp_send_json_success(
			array(
				'message' => __( 'Settings saved successfully. Fields will update automatically.', 'formscrm' ),
				'settings' => $saved_settings,
			)
		);
	}


	/**
	 * Render global settings page
	 *
	 * @return void
	 */
	public function render_global_settings() {
		$global_settings = get_option( 'formscrm_fluentforms_global_settings', array() );
		wp_enqueue_script( 'jquery' );
		?>
		<div class="formscrm-global-settings-wrapper" style="padding: 20px;">
			<h2><?php esc_html_e( 'FormsCRM Global Settings', 'formscrm' ); ?></h2>
			<p><?php esc_html_e( 'Configure your default FormsCRM settings. These can be overridden in individual forms.', 'formscrm' ); ?></p>
			<form id="formscrm-fluentforms-global-settings" method="post">
				<?php wp_nonce_field( 'formscrm_fluentforms_save_global', 'formscrm_fluentforms_global_nonce' ); ?>
				<input type="hidden" name="action" value="formscrm_fluentforms_save_global_settings" />

				<p>
					<label for="fc_crm_type"><strong><?php esc_html_e( 'CRM Type:', 'formscrm' ); ?></strong></label><br />
					<select name="fc_crm_type" id="fc_crm_type" class="medium" onchange="jQuery(this).parents('form').submit();" style="min-width: 300px;">
						<option value=""><?php esc_html_e( 'Select CRM', 'formscrm' ); ?></option>
						<?php
						foreach ( formscrm_get_choices() as $choice ) {
							echo '<option value="' . esc_attr( $choice['value'] ) . '" ';
							if ( isset( $global_settings['fc_crm_type'] ) ) {
								selected( $global_settings['fc_crm_type'], $choice['value'] );
							}
							echo '>' . esc_html( $choice['label'] ) . '</option>';
						}
						?>
					</select>
				</p>

				<?php if ( isset( $global_settings['fc_crm_type'] ) && ! empty( $global_settings['fc_crm_type'] ) ) { ?>

					<?php if ( false !== array_search( $global_settings['fc_crm_type'], formscrm_get_dependency_url(), true ) ) { ?>
					<p>
						<label for="fc_crm_url"><?php esc_html_e( 'URL:', 'formscrm' ); ?></label><br />
						<input type="text" id="fc_crm_url" name="fc_crm_url" class="wide" size="70" placeholder="<?php esc_html_e( 'CRM URL', 'formscrm' ); ?>" value="<?php echo isset( $global_settings['fc_crm_url'] ) ? esc_attr( $global_settings['fc_crm_url'] ) : ''; ?>" />
					</p>
					<?php } ?>

					<?php if ( false !== array_search( $global_settings['fc_crm_type'], formscrm_get_dependency_username(), true ) ) { ?>
					<p>
						<label for="fc_crm_username"><?php esc_html_e( 'Username:', 'formscrm' ); ?></label><br />
						<input type="text" id="fc_crm_username" name="fc_crm_username" class="wide" size="70" placeholder="<?php esc_html_e( 'Username', 'formscrm' ); ?>" value="<?php echo isset( $global_settings['fc_crm_username'] ) ? esc_attr( $global_settings['fc_crm_username'] ) : ''; ?>" />
					</p>
					<?php } ?>

					<?php if ( false !== array_search( $global_settings['fc_crm_type'], formscrm_get_dependency_password(), true ) ) { ?>
					<p>
						<label for="fc_crm_password"><?php esc_html_e( 'Password:', 'formscrm' ); ?></label><br />
						<input type="password" id="fc_crm_password" name="fc_crm_password" class="wide" size="70" placeholder="<?php esc_html_e( 'CRM Password', 'formscrm' ); ?>" value="<?php echo isset( $global_settings['fc_crm_password'] ) ? esc_attr( $global_settings['fc_crm_password'] ) : ''; ?>" />
					</p>
					<?php } ?>

					<?php if ( false !== array_search( $global_settings['fc_crm_type'], formscrm_get_dependency_apipassword(), true ) ) { ?>
					<p>
						<label for="fc_crm_apipassword"><?php esc_html_e( 'API Password:', 'formscrm' ); ?></label><br />
						<input type="password" id="fc_crm_apipassword" name="fc_crm_apipassword" class="wide" size="70" placeholder="<?php esc_html_e( 'CRM API Password', 'formscrm' ); ?>" value="<?php echo isset( $global_settings['fc_crm_apipassword'] ) ? esc_attr( $global_settings['fc_crm_apipassword'] ) : ''; ?>" />
					</p>
					<?php } ?>

					<?php if ( false !== array_search( $global_settings['fc_crm_type'], formscrm_get_dependency_apisales(), true ) ) { ?>
					<p>
						<label for="fc_crm_apisales"><?php esc_html_e( 'API Sales:', 'formscrm' ); ?></label><br />
						<input type="text" id="fc_crm_apisales" name="fc_crm_apisales" class="wide" size="70" placeholder="<?php esc_html_e( 'CRM API Sales', 'formscrm' ); ?>" value="<?php echo isset( $global_settings['fc_crm_apisales'] ) ? esc_attr( $global_settings['fc_crm_apisales'] ) : ''; ?>" />
					</p>
					<?php } ?>

					<?php if ( false !== array_search( $global_settings['fc_crm_type'], formscrm_get_dependency_odoodb(), true ) ) { ?>
					<p>
						<label for="fc_crm_odoodb"><?php esc_html_e( 'Odoo DB:', 'formscrm' ); ?></label><br />
						<input type="text" id="fc_crm_odoodb" name="fc_crm_odoodb" class="wide" size="70" placeholder="<?php esc_html_e( 'Odoo DB', 'formscrm' ); ?>" value="<?php echo isset( $global_settings['fc_crm_odoodb'] ) ? esc_attr( $global_settings['fc_crm_odoodb'] ) : ''; ?>" />
					</p>
					<?php } ?>

					<?php
					$this->include_library( $global_settings['fc_crm_type'] );
					if ( ! empty( $this->crmlib ) ) {
						$login_result = $this->crmlib->login( $global_settings );
						if ( ! empty( $login_result ) && ! ( is_array( $login_result ) && isset( $login_result['status'] ) && 'error' === $login_result['status'] ) ) {
							?>
							<p style="color: green;">
								<?php esc_html_e( '✓ Connected successfully to CRM.', 'formscrm' ); ?>
							</p>
							<?php
						} else {
							?>
							<p style="color: red;">
								<?php esc_html_e( '✗ Could not connect to CRM. Please check credentials.', 'formscrm' ); ?>
							</p>
							<?php
						}
					}
					?>

				<?php } ?>

				<p>
					<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Global Settings', 'formscrm' ); ?>" />
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Save global settings via AJAX
	 *
	 * @return void
	 */
	public function save_global_settings() {
		check_ajax_referer( 'formscrm_fluentforms_save_global', 'formscrm_fluentforms_global_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to save settings.', 'formscrm' ) ) );
		}

		$settings = array();
		if ( isset( $_POST['fc_crm_type'] ) ) {
			$settings['fc_crm_type'] = sanitize_text_field( $_POST['fc_crm_type'] );
		}
		if ( isset( $_POST['fc_crm_url'] ) ) {
			$settings['fc_crm_url'] = sanitize_text_field( $_POST['fc_crm_url'] );
		}
		if ( isset( $_POST['fc_crm_username'] ) ) {
			$settings['fc_crm_username'] = sanitize_text_field( $_POST['fc_crm_username'] );
		}
		if ( isset( $_POST['fc_crm_password'] ) ) {
			$settings['fc_crm_password'] = sanitize_text_field( $_POST['fc_crm_password'] );
		}
		if ( isset( $_POST['fc_crm_apipassword'] ) ) {
			$settings['fc_crm_apipassword'] = sanitize_text_field( $_POST['fc_crm_apipassword'] );
		}
		if ( isset( $_POST['fc_crm_apisales'] ) ) {
			$settings['fc_crm_apisales'] = sanitize_text_field( $_POST['fc_crm_apisales'] );
		}
		if ( isset( $_POST['fc_crm_odoodb'] ) ) {
			$settings['fc_crm_odoodb'] = sanitize_text_field( $_POST['fc_crm_odoodb'] );
		}

		update_option( 'formscrm_fluentforms_global_settings', array_filter( $settings ) );

		wp_send_json_success( array( 'message' => __( 'Global settings saved successfully.', 'formscrm' ) ) );
	}

	/**
	 * Add FormsCRM menu to FluentForms settings
	 *
	 * @param array $menus Existing menus.
	 * @return array
	 */
	public function add_form_settings_menu( $menus ) {
		$menus['formscrm'] = array(
			'title' => __( 'FormsCRM', 'formscrm' ),
			'slug'  => 'formscrm',
		);
		return $menus;
	}

	/**
	 * Render settings page
	 *
	 * @param int $form_id Form ID.
	 * @return void
	 */
	public function render_settings( $form_id ) {
		$form_id         = absint( $form_id );
		$ff_crm          = get_post_meta( $form_id, '_formscrm_settings', true );
		$ff_crm          = ! empty( $ff_crm ) ? $ff_crm : array();
		$global_settings = get_option( 'formscrm_fluentforms_global_settings', array() );
		$use_global      = isset( $ff_crm['use_global'] ) ? $ff_crm['use_global'] : 'yes';
		$form_crm_merged = wp_parse_args( $ff_crm, $global_settings );

		wp_enqueue_script( 'jquery' );
		?>
		<div class="formscrm-settings-wrapper" style="padding: 20px;">
			<h3><?php esc_html_e( 'FormsCRM Settings', 'formscrm' ); ?></h3>
			<?php if ( ! empty( $global_settings['fc_crm_type'] ) ) { ?>
				<p>
					<label>
						<input type="checkbox" name="use_global" id="use_global" value="yes" <?php checked( $use_global, 'yes' ); ?> onchange="jQuery('#form-specific-settings').toggle(!this.checked);" />
						<strong><?php esc_html_e( 'Use Global Settings', 'formscrm' ); ?></strong>
					</label>
					<br />
					<small><?php esc_html_e( 'Use the global FormsCRM settings configured in FluentForms Settings. Uncheck to override with form-specific settings.', 'formscrm' ); ?></small>
				</p>
			<?php } ?>
			<form id="formscrm-fluentforms-settings" method="post">
				<?php wp_nonce_field( 'formscrm_fluentforms_save', 'formscrm_fluentforms_nonce' ); ?>
				<input type="hidden" name="form_id" value="<?php echo esc_attr( $form_id ); ?>" />
				<input type="hidden" name="action" value="formscrm_fluentforms_save_settings" />
				<input type="hidden" name="use_global" value="<?php echo esc_attr( $use_global ); ?>" />

				<div id="form-specific-settings" style="<?php echo 'yes' === $use_global ? 'display: none;' : ''; ?>">
					<p>
						<label for="fc_crm_type"><strong><?php esc_html_e( 'CRM Type:', 'formscrm' ); ?></strong></label><br />
						<select name="fc_crm_type" id="fc_crm_type" class="medium" onchange="jQuery(this).parents('form').submit();">
							<option value=""><?php esc_html_e( 'Select CRM', 'formscrm' ); ?></option>
							<?php
							foreach ( formscrm_get_choices() as $choice ) {
								echo '<option value="' . esc_attr( $choice['value'] ) . '" ';
								if ( isset( $ff_crm['fc_crm_type'] ) ) {
									selected( $ff_crm['fc_crm_type'], $choice['value'] );
								} elseif ( isset( $global_settings['fc_crm_type'] ) ) {
									selected( $global_settings['fc_crm_type'], $choice['value'] );
								}
								echo '>' . esc_html( $choice['label'] ) . '</option>';
							}
							?>
						</select>
					</p>
				</div>

				<?php
				// Use form-specific settings if set, otherwise use global.
				$display_crm_type = ! empty( $ff_crm['fc_crm_type'] ) ? $ff_crm['fc_crm_type'] : ( ! empty( $global_settings['fc_crm_type'] ) ? $global_settings['fc_crm_type'] : '' );
				$crm_to_display   = 'yes' === $use_global ? $global_settings : $ff_crm;
				?>

				<?php if ( ! empty( $display_crm_type ) ) { ?>
					<?php if ( false !== array_search( $display_crm_type, formscrm_get_dependency_url(), true ) ) { ?>
					<p>
						<label for="fc_crm_url"><?php esc_html_e( 'URL:', 'formscrm' ); ?></label><br />
						<input type="text" id="fc_crm_url" name="fc_crm_url" class="wide" size="70" placeholder="<?php esc_html_e( 'CRM URL', 'formscrm' ); ?>" value="<?php echo isset( $crm_to_display['fc_crm_url'] ) ? esc_attr( $crm_to_display['fc_crm_url'] ) : ''; ?>" />
					</p>
					<?php } ?>

					<?php if ( false !== array_search( $display_crm_type, formscrm_get_dependency_username(), true ) ) { ?>
					<p>
						<label for="fc_crm_username"><?php esc_html_e( 'Username:', 'formscrm' ); ?></label><br />
						<input type="text" id="fc_crm_username" name="fc_crm_username" class="wide" size="70" placeholder="<?php esc_html_e( 'Username', 'formscrm' ); ?>" value="<?php echo isset( $crm_to_display['fc_crm_username'] ) ? esc_attr( $crm_to_display['fc_crm_username'] ) : ''; ?>" />
					</p>
					<?php } ?>

					<?php if ( false !== array_search( $display_crm_type, formscrm_get_dependency_password(), true ) ) { ?>
					<p>
						<label for="fc_crm_password"><?php esc_html_e( 'Password:', 'formscrm' ); ?></label><br />
						<input type="password" id="fc_crm_password" name="fc_crm_password" class="wide" size="70" placeholder="<?php esc_html_e( 'CRM Password', 'formscrm' ); ?>" value="<?php echo isset( $crm_to_display['fc_crm_password'] ) ? esc_attr( $crm_to_display['fc_crm_password'] ) : ''; ?>" />
					</p>
					<?php } ?>

					<?php if ( false !== array_search( $display_crm_type, formscrm_get_dependency_apipassword(), true ) ) { ?>
					<p>
						<label for="fc_crm_apipassword"><?php esc_html_e( 'API Password:', 'formscrm' ); ?></label><br />
						<input type="password" id="fc_crm_apipassword" name="fc_crm_apipassword" class="wide" size="70" placeholder="<?php esc_html_e( 'CRM API Password', 'formscrm' ); ?>" value="<?php echo isset( $crm_to_display['fc_crm_apipassword'] ) ? esc_attr( $crm_to_display['fc_crm_apipassword'] ) : ''; ?>" />
					</p>
					<?php } ?>

					<?php if ( false !== array_search( $display_crm_type, formscrm_get_dependency_apisales(), true ) ) { ?>
					<p>
						<label for="fc_crm_apisales"><?php esc_html_e( 'API Sales:', 'formscrm' ); ?></label><br />
						<input type="text" id="fc_crm_apisales" name="fc_crm_apisales" class="wide" size="70" placeholder="<?php esc_html_e( 'CRM API Sales', 'formscrm' ); ?>" value="<?php echo isset( $crm_to_display['fc_crm_apisales'] ) ? esc_attr( $crm_to_display['fc_crm_apisales'] ) : ''; ?>" />
					</p>
					<?php } ?>

					<?php if ( false !== array_search( $display_crm_type, formscrm_get_dependency_odoodb(), true ) ) { ?>
					<p>
						<label for="fc_crm_odoodb"><?php esc_html_e( 'Odoo DB:', 'formscrm' ); ?></label><br />
						<input type="text" id="fc_crm_odoodb" name="fc_crm_odoodb" class="wide" size="70" placeholder="<?php esc_html_e( 'Odoo DB', 'formscrm' ); ?>" value="<?php echo isset( $crm_to_display['fc_crm_odoodb'] ) ? esc_attr( $crm_to_display['fc_crm_odoodb'] ) : ''; ?>" />
					</p>
					<?php } ?>

					<p>
						<?php $this->include_library( $display_crm_type ); ?>
						<label for="fc_crm_module"><?php esc_html_e( 'Module:', 'formscrm' ); ?></label><br />
						<select name="fc_crm_module" id="fc_crm_module" class="medium" onchange="jQuery(this).parents('form').submit();">
							<option value=""><?php esc_html_e( 'Select Module', 'formscrm' ); ?></option>
							<?php
							if ( ! empty( $this->crmlib ) ) {
								$login_result = $this->crmlib->login( $crm_to_display );
								if ( ! empty( $login_result ) && ! ( is_array( $login_result ) && isset( $login_result['status'] ) && 'error' === $login_result['status'] ) ) {
									$settings_module = isset( $ff_crm['fc_crm_module'] ) ? $ff_crm['fc_crm_module'] : ( isset( $global_settings['fc_crm_module'] ) ? $global_settings['fc_crm_module'] : '' );
									foreach ( $this->crmlib->list_modules( $crm_to_display ) as $module ) {
										$value = '';
										if ( ! empty( $module['value'] ) ) {
											$value = $module['value'];
										} elseif ( ! empty( $module['name'] ) ) {
											$value = $module['name'];
										}
										if ( empty( $value ) || ! isset( $module['label'] ) ) {
											continue;
										}
										echo '<option value="' . esc_attr( $value ) . '" ';
										selected( $settings_module, $value );
										echo '>' . esc_html( $module['label'] ) . '</option>';
									}
								} else {
									echo '<option value="">' . esc_html__( 'Could not connect to CRM. Please check credentials.', 'formscrm' ) . '</option>';
								}
							}
							?>
						</select>
					</p>

					<p>
						<label for="fc_crm_mode_expert">
							<input type="checkbox" id="fc_crm_mode_expert" name="fc_crm_mode_expert" class="medium" value="on" <?php checked( isset( $ff_crm['fc_crm_mode_expert'] ) ? $ff_crm['fc_crm_mode_expert'] : '', 'on' ); ?> />
							<?php esc_html_e( 'Expert Mode - Enable this option to show all fields of the CRM.', 'formscrm' ); ?>
						</label>
					</p>

					<?php
					$selected_module = isset( $ff_crm['fc_crm_module'] ) ? $ff_crm['fc_crm_module'] : ( isset( $global_settings['fc_crm_module'] ) ? $global_settings['fc_crm_module'] : '' );
					if ( ! empty( $selected_module ) && ! empty( $this->crmlib ) ) {
						?>
						<?php
						$login_result = $this->crmlib->login( $crm_to_display );
						if ( ! empty( $login_result ) && ! ( is_array( $login_result ) && isset( $login_result['status'] ) && 'error' === $login_result['status'] ) ) {
							$crm_fields = $this->crmlib->list_fields( $crm_to_display, $selected_module );
							// Get form fields from FluentForms.
							$form_fields = array();
							if ( class_exists( '\FluentForm\App\Modules\Form\Form' ) ) {
								$form = \FluentForm\App\Modules\Form\Form::find( $form_id );
								if ( $form && isset( $form->fields ) ) {
									$form_fields = json_decode( $form->fields, true );
									if ( ! is_array( $form_fields ) ) {
										$form_fields = array();
									}
								}
							}
							?>
							<h4><?php esc_html_e( 'Field Mapping', 'formscrm' ); ?></h4>
							<table class="formscrm-map-table" cellspacing="0" cellpadding="0" style="width: 100%;">
								<tbody>
									<tr>
										<th style="text-align: left; padding: 10px;"><?php esc_html_e( 'CRM Field', 'formscrm' ); ?></th>
										<th style="text-align: left; padding: 10px;"><?php esc_html_e( 'FluentForms Field', 'formscrm' ); ?></th>
									</tr>
									<?php
									if ( ! empty( $crm_fields ) && is_array( $crm_fields ) ) {
										foreach ( $crm_fields as $crm_field ) {
											if ( empty( $crm_field['name'] ) ) {
												continue;
											}
											$crm_field_name  = sanitize_text_field( $crm_field['name'] );
											$crm_field_label = isset( $crm_field['label'] ) ? sanitize_text_field( $crm_field['label'] ) : '';
											$crm_field_req   = isset( $crm_field['req'] ) ? (bool) $crm_field['req'] : false;
											?>
											<tr>
												<td style="padding: 10px;">
													<label for="formscrm-field-<?php echo esc_attr( $crm_field_name ); ?>">
														<?php
														echo esc_html( $crm_field_label );
														if ( $crm_field_req ) {
															echo ' <span style="color: red;">*</span>';
														}
														?>
													</label>
												</td>
												<td style="padding: 10px;">
													<select class="wide" name="fc_crm_field-<?php echo esc_attr( $crm_field_name ); ?>" style="min-width:300px;">
														<option value=""><?php esc_html_e( 'Select a field', 'formscrm' ); ?></option>
														<?php
														if ( ! empty( $form_fields ) ) {
															foreach ( $form_fields as $form_field ) {
																// FluentForms field structure.
																$field_name  = isset( $form_field['attributes']['name'] ) ? $form_field['attributes']['name'] : ( isset( $form_field['name'] ) ? $form_field['name'] : '' );
																$field_label = isset( $form_field['settings']['label'] ) ? $form_field['settings']['label'] : ( isset( $form_field['attributes']['label'] ) ? $form_field['attributes']['label'] : $field_name );
																if ( ! empty( $field_name ) ) {
																	echo '<option value="' . esc_attr( $field_name ) . '" ';
																	if ( isset( $ff_crm[ 'fc_crm_field-' . $crm_field_name ] ) ) {
																		selected( $ff_crm[ 'fc_crm_field-' . $crm_field_name ], $field_name );
																	}
																	echo '>' . esc_html( $field_label ) . '</option>';
																}
															}
														}
														?>
													</select>
												</td>
											</tr>
											<?php
										}
									} else {
										echo '<tr><td colspan="2">' . esc_html__( 'No fields found, or the connection has not got the right permissions.', 'formscrm' ) . '</td></tr>';
									}
									?>
								</tbody>
							</table>
							<?php
						} else {
							echo '<p>' . esc_html__( 'Could not connect to CRM. Please check credentials.', 'formscrm' ) . '</p>';
						}
						?>
					<?php } ?>
				<?php } ?>

				<p>
					<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Settings', 'formscrm' ); ?>" />
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Save settings via AJAX
	 *
	 * @return void
	 */
	public function save_settings() {
		check_ajax_referer( 'formscrm_fluentforms_save', 'formscrm_fluentforms_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to save settings.', 'formscrm' ) ) );
		}

		$form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
		if ( empty( $form_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid form ID.', 'formscrm' ) ) );
		}

		$settings = array();
		if ( isset( $_POST['fc_crm_type'] ) ) {
			$settings['fc_crm_type'] = sanitize_text_field( $_POST['fc_crm_type'] );
		}
		if ( isset( $_POST['fc_crm_url'] ) ) {
			$settings['fc_crm_url'] = sanitize_text_field( $_POST['fc_crm_url'] );
		}
		if ( isset( $_POST['fc_crm_username'] ) ) {
			$settings['fc_crm_username'] = sanitize_text_field( $_POST['fc_crm_username'] );
		}
		if ( isset( $_POST['fc_crm_password'] ) ) {
			$settings['fc_crm_password'] = sanitize_text_field( $_POST['fc_crm_password'] );
		}
		if ( isset( $_POST['fc_crm_apipassword'] ) ) {
			$settings['fc_crm_apipassword'] = sanitize_text_field( $_POST['fc_crm_apipassword'] );
		}
		if ( isset( $_POST['fc_crm_apisales'] ) ) {
			$settings['fc_crm_apisales'] = sanitize_text_field( $_POST['fc_crm_apisales'] );
		}
		if ( isset( $_POST['fc_crm_odoodb'] ) ) {
			$settings['fc_crm_odoodb'] = sanitize_text_field( $_POST['fc_crm_odoodb'] );
		}
		if ( isset( $_POST['fc_crm_module'] ) ) {
			$settings['fc_crm_module'] = sanitize_text_field( $_POST['fc_crm_module'] );
		}
		if ( isset( $_POST['fc_crm_mode_expert'] ) ) {
			$settings['fc_crm_mode_expert'] = sanitize_text_field( $_POST['fc_crm_mode_expert'] );
		}
		if ( isset( $_POST['use_global'] ) ) {
			$settings['use_global'] = sanitize_text_field( $_POST['use_global'] );
		}

		// Save field mappings.
		foreach ( $_POST as $key => $value ) {
			if ( false !== strpos( $key, 'fc_crm_field-' ) ) {
				$settings[ $key ] = sanitize_text_field( $value );
			}
		}

		update_post_meta( $form_id, '_formscrm_settings', array_filter( $settings ) );

		wp_send_json_success( array( 'message' => __( 'Settings saved successfully.', 'formscrm' ) ) );
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

			if ( class_exists( $crmclassname ) ) {
				$this->crmlib = new $crmclassname();
			}
		}
	}

	/**
	 * Process the entry.
	 *
	 * @param int   $entry_id Entry ID.
	 * @param array $form_data Form data.
	 * @param object $form Form object.
	 * @return void
	 */
	public function process_entry( $entry_id, $form_data, $form ) {
		$form_id         = is_object( $form ) && isset( $form->id ) ? $form->id : ( is_numeric( $form ) ? $form : 0 );
		$ff_crm          = get_post_meta( $form_id, '_formscrm_settings', true );
		$ff_crm          = ! empty( $ff_crm ) ? $ff_crm : array();
		$global_settings = get_option( 'formscrm_fluentforms_global_settings', array() );
		$use_global      = isset( $ff_crm['use_global'] ) ? $ff_crm['use_global'] : 'yes';

		// Merge global and form-specific settings (form-specific takes precedence).
		$crm_settings = 'yes' === $use_global ? wp_parse_args( $ff_crm, $global_settings ) : $ff_crm;
		$crm_type     = ! empty( $crm_settings['fc_crm_type'] ) ? sanitize_text_field( $crm_settings['fc_crm_type'] ) : '';

		if ( empty( $crm_type ) || empty( $form_id ) ) {
            return;
        }

		// Form data from FluentForms hook already contains submitted data.
		$submitted_data = is_array( $form_data ) ? $form_data : array();

		// If form_data doesn't have the data, try to get it from entry.
		if ( empty( $submitted_data ) && class_exists( '\FluentForm\App\Modules\Entries\Entries' ) ) {
			$entry = \FluentForm\App\Modules\Entries\Entries::find( $entry_id );
			if ( $entry && ! empty( $entry->response ) ) {
				$submitted_data = json_decode( $entry->response, true );
				if ( ! is_array( $submitted_data ) ) {
					$submitted_data = array();
				}
			}
		}

		if ( empty( $submitted_data ) ) {
                return;
            }

		// Create contact in CRM.
		$this->include_library( $crm_type );
		if ( empty( $this->crmlib ) ) {
			return;
		}

		$merge_vars      = $this->get_merge_vars( $ff_crm, $submitted_data );
		$response_result = $this->crmlib->create_entry( $crm_settings, $merge_vars );

		if ( 'error' === $response_result['status'] ) {
			$url   = isset( $response_result['url'] ) ? $response_result['url'] : '';
			$query = isset( $response_result['query'] ) ? $response_result['query'] : '';

			formscrm_debug_email_lead( $crm_settings['fc_crm_type'], 'Error ' . $response_result['message'], $merge_vars, $url, $query );
		}
	}

	/**
	 * Extract merge variables
	 *
	 * @param array $ff_crm Array settings from CRM.
	 * @param array $submitted_data Submitted data.
	 * @return array
	 */
	public function get_merge_vars( $ff_crm, $submitted_data ) {
		if ( empty( $ff_crm ) || ! is_array( $ff_crm ) ) {
			return array();
		}
		$merge_vars = array();
		foreach ( $ff_crm as $key => $value ) {
			if ( false === strpos( $key, 'fc_crm_field' ) ) {
				continue;
			}
			$crm_key = str_replace( 'fc_crm_field-', '', $key );

			$field_name = $value;
			$field_value = '';

			if ( isset( $submitted_data[ $field_name ] ) ) {
				$field_value = $submitted_data[ $field_name ];
			}

			if ( is_array( $field_value ) ) {
				$field_value = implode( ', ', $field_value );
			}

			$merge_vars[] = array(
				'name'  => $crm_key,
				'value' => $field_value,
			);
		}

		return $merge_vars;
	}
}

new FORMSCRM_FluentForms_Settings();
