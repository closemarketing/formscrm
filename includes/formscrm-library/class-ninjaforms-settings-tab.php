<?php
/**
 * Ninja Forms global CRM connection settings.
 *
 * Ninja Forms actions are registered once on `init`, before any specific
 * form or action instance is known, so the CRM connection (URL, credentials)
 * can't be entered inline per-action the way CF7 or WPForms do it. Instead,
 * a single site-wide connection is stored inside FormsCRM's own settings
 * page (Settings > FormsCRM > Ninja Forms tab), the same place other
 * cross-cutting FormsCRM settings (Notifications, Error Log) already live.
 *
 * @package FormsCRM
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'FormsCRM_NinjaForms_Settings' ) ) {

	/**
	 * Registers and renders the "Ninja Forms" tab on the FormsCRM settings page.
	 */
	class FormsCRM_NinjaForms_Settings {

		const OPTION_NAME = 'formscrm_ninjaforms_connection';

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_filter( 'formscrm_settings_tabs', array( $this, 'register_tab' ) );
			add_action( 'formscrm_ninjaforms_settings', array( $this, 'render' ) );
			add_action( 'admin_init', array( $this, 'register_setting' ) );
		}

		/**
		 * Adds the "Ninja Forms" tab to the FormsCRM settings page.
		 *
		 * @param array $tabs Existing tabs.
		 * @return array
		 */
		public function register_tab( $tabs ) {
			$tabs[] = array(
				'tab'    => 'ninja-forms',
				'label'  => __( 'Ninja Forms', 'formscrm' ),
				'action' => 'formscrm_ninjaforms_settings',
			);

			return $tabs;
		}

		/**
		 * Registers the connection option with the WordPress Settings API.
		 *
		 * @return void
		 */
		public function register_setting() {
			register_setting(
				'formscrm_ninjaforms_settings',
				self::OPTION_NAME,
				array( $this, 'sanitize' )
			);
		}

		/**
		 * Sanitizes the submitted connection settings and clears the cached
		 * module/field list so the Ninja Forms action drawer picks up the change.
		 *
		 * @param mixed $value Submitted values.
		 * @return array
		 */
		public function sanitize( $value ) {
			$value = (array) $value;

			$sanitized = array(
				'fc_crm_type'        => isset( $value['fc_crm_type'] ) ? sanitize_text_field( $value['fc_crm_type'] ) : '',
				'fc_crm_url'         => isset( $value['fc_crm_url'] ) ? esc_url_raw( $value['fc_crm_url'] ) : '',
				'fc_crm_username'    => isset( $value['fc_crm_username'] ) ? sanitize_text_field( $value['fc_crm_username'] ) : '',
				'fc_crm_password'    => isset( $value['fc_crm_password'] ) ? sanitize_text_field( $value['fc_crm_password'] ) : '',
				'fc_crm_apipassword' => isset( $value['fc_crm_apipassword'] ) ? sanitize_text_field( $value['fc_crm_apipassword'] ) : '',
				'fc_crm_apisales'    => isset( $value['fc_crm_apisales'] ) ? sanitize_text_field( $value['fc_crm_apisales'] ) : '',
				'fc_crm_odoodb'      => isset( $value['fc_crm_odoodb'] ) ? sanitize_text_field( $value['fc_crm_odoodb'] ) : '',
			);

			if ( ! empty( $sanitized['fc_crm_url'] ) ) {
				$sanitized['fc_crm_url'] = formscrm_check_url_crm( $sanitized['fc_crm_url'] );
			}

			delete_transient( 'formscrm_nf_modules_fields' );

			return $sanitized;
		}

		/**
		 * Returns the stored connection settings, merged with defaults.
		 *
		 * @return array
		 */
		public static function get_connection_settings() {
			$defaults = array(
				'fc_crm_type'        => '',
				'fc_crm_url'         => '',
				'fc_crm_username'    => '',
				'fc_crm_password'    => '',
				'fc_crm_apipassword' => '',
				'fc_crm_apisales'    => '',
				'fc_crm_odoodb'      => '',
			);

			return wp_parse_args( get_option( self::OPTION_NAME, array() ), $defaults );
		}

		/**
		 * Renders the "Ninja Forms" settings tab.
		 *
		 * @return void
		 */
		public function render() {
			$settings = self::get_connection_settings();
			?>
			<div class="fcrm-section">
				<div class="fcrm-section-header">
					<h2 class="fcrm-section-title"><?php esc_html_e( 'Ninja Forms Connection', 'formscrm' ); ?></h2>
					<p class="fcrm-section-description">
						<?php esc_html_e( 'The CRM connection used by the "FormsCRM" Ninja Forms action is configured once here, then each form only needs to pick a CRM module and map its fields.', 'formscrm' ); ?>
					</p>
				</div>
				<div class="fcrm-section-content">
					<form method="post" action="options.php">
						<?php settings_fields( 'formscrm_ninjaforms_settings' ); ?>

						<div class="fcrm-form-group">
							<label class="fcrm-form-label" for="formscrm_nf_crm_type"><?php esc_html_e( 'CRM', 'formscrm' ); ?></label>
							<select id="formscrm_nf_crm_type" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[fc_crm_type]" class="fcrm-form-input">
								<option value=""><?php esc_html_e( 'Select a CRM', 'formscrm' ); ?></option>
								<?php foreach ( formscrm_get_choices() as $choice ) : ?>
									<option value="<?php echo esc_attr( $choice['value'] ); ?>" <?php selected( $settings['fc_crm_type'], $choice['value'] ); ?>>
										<?php echo esc_html( $choice['label'] ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<?php if ( in_array( $settings['fc_crm_type'], formscrm_get_dependency_url(), true ) ) : ?>
							<div class="fcrm-form-group">
								<label class="fcrm-form-label" for="formscrm_nf_crm_url"><?php esc_html_e( 'CRM URL', 'formscrm' ); ?></label>
								<input type="text" id="formscrm_nf_crm_url" class="fcrm-form-input" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[fc_crm_url]" value="<?php echo esc_attr( $settings['fc_crm_url'] ); ?>" />
							</div>
						<?php endif; ?>

						<?php if ( in_array( $settings['fc_crm_type'], formscrm_get_dependency_username(), true ) ) : ?>
							<div class="fcrm-form-group">
								<label class="fcrm-form-label" for="formscrm_nf_crm_username"><?php esc_html_e( 'Username', 'formscrm' ); ?></label>
								<input type="text" id="formscrm_nf_crm_username" class="fcrm-form-input" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[fc_crm_username]" value="<?php echo esc_attr( $settings['fc_crm_username'] ); ?>" />
							</div>
						<?php endif; ?>

						<?php if ( in_array( $settings['fc_crm_type'], formscrm_get_dependency_password(), true ) ) : ?>
							<div class="fcrm-form-group">
								<label class="fcrm-form-label" for="formscrm_nf_crm_password"><?php esc_html_e( 'Password', 'formscrm' ); ?></label>
								<input type="password" id="formscrm_nf_crm_password" class="fcrm-form-input" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[fc_crm_password]" value="<?php echo esc_attr( $settings['fc_crm_password'] ); ?>" />
							</div>
						<?php endif; ?>

						<?php if ( in_array( $settings['fc_crm_type'], formscrm_get_dependency_apipassword(), true ) ) : ?>
							<div class="fcrm-form-group">
								<label class="fcrm-form-label" for="formscrm_nf_crm_apipassword"><?php esc_html_e( 'API Password / Token', 'formscrm' ); ?></label>
								<input type="password" id="formscrm_nf_crm_apipassword" class="fcrm-form-input" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[fc_crm_apipassword]" value="<?php echo esc_attr( $settings['fc_crm_apipassword'] ); ?>" />
							</div>
						<?php endif; ?>

						<?php if ( in_array( $settings['fc_crm_type'], formscrm_get_dependency_apisales(), true ) ) : ?>
							<div class="fcrm-form-group">
								<label class="fcrm-form-label" for="formscrm_nf_crm_apisales"><?php esc_html_e( 'API Sales Key', 'formscrm' ); ?></label>
								<input type="text" id="formscrm_nf_crm_apisales" class="fcrm-form-input" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[fc_crm_apisales]" value="<?php echo esc_attr( $settings['fc_crm_apisales'] ); ?>" />
							</div>
						<?php endif; ?>

						<?php if ( in_array( $settings['fc_crm_type'], formscrm_get_dependency_odoodb(), true ) ) : ?>
							<div class="fcrm-form-group">
								<label class="fcrm-form-label" for="formscrm_nf_crm_odoodb"><?php esc_html_e( 'Odoo Database', 'formscrm' ); ?></label>
								<input type="text" id="formscrm_nf_crm_odoodb" class="fcrm-form-input" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[fc_crm_odoodb]" value="<?php echo esc_attr( $settings['fc_crm_odoodb'] ); ?>" />
							</div>
						<?php endif; ?>

						<?php if ( ! empty( $settings['fc_crm_type'] ) ) : ?>
							<div class="fcrm-form-group">
								<?php
								echo wp_kses_post( formscrm_get_connection_status_html( $settings, 'badge' ) );
								?>
							</div>
						<?php endif; ?>

						<button type="submit" class="fcrm-button fcrm-button-primary"><?php esc_html_e( 'Save Connection', 'formscrm' ); ?></button>
					</form>
				</div>
			</div>
			<?php
		}
	}
}

if ( is_admin() ) {
	new FormsCRM_NinjaForms_Settings();
}
