<?php
/**
 * Library for admin settings
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2019 Closemarketing
 * @version    1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Library for WooCommerce Settings
 *
 * Settings in order to sync products
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2019 Closemarketing
 * @version    0.1
 */

if ( ! class_exists( 'FORMSCRM_Admin' ) ) {
	/**
	 * Class FORMSCRM_Admin
	 *
	 * Handles admin settings page for FormsCRM plugin.
	 */
	class FORMSCRM_Admin {
		/**
		 * Construct of class
		 */
		public function __construct() {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

			add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
			add_action( 'formscrm_settings', array( $this, 'settings_page' ) );
			add_action( 'formscrm_notifications', array( $this, 'notifications_page' ) );
			add_action( 'admin_init', array( $this, 'register_settings' ) );
		}

		/**
		 * Register settings
		 *
		 * @return void
		 */
		public function register_settings() {
			register_setting( 'formscrm_settings', 'formscrm_slack_webhook_url' );
			register_setting( 'formscrm_settings', 'formscrm_error_notification_email' );
		}

		/**
		 * Enqueue Scripts and styles
		 *
		 * @param string $hook Current admin page hook.
		 * @return void
		 */
		public function enqueue_admin_scripts( $hook ) {
			// Only load on our settings page.
			if ( 'toplevel_page_formscrm' !== $hook ) {
				return;
			}

			wp_enqueue_style(
				'formscrm-admin',
				FORMSCRM_PLUGIN_URL . 'includes/assets/formscrm-admin.css',
				array(),
				FORMSCRM_VERSION
			);
		}

		/**
		 * Adds plugin page.
		 *
		 * @return void
		 */
		public function add_plugin_page() {
			// SVG icon encoded as data URI.
			$icon_svg = 'data:image/svg+xml;base64,' . base64_encode(
				file_get_contents( FORMSCRM_PLUGIN_URL . 'includes/assets/icon-menu.svg' ) // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			);

			add_menu_page(
				__( 'FormsCRM', 'formscrm' ),
				__( 'FormsCRM', 'formscrm' ),
				'manage_options',
				'formscrm',
				array( $this, 'create_admin_page' ),
				$icon_svg,
				80
			);
		}

		/**
		 * Create admin page.
		 *
		 * @return void
		 */
		public function create_admin_page() {
			?>
		<div class="fcrm-settings-wrapper">
			<!-- Header Section -->
			<div class="fcrm-header">
				<div class="fcrm-header-content">
					<div class="fcrm-header-text">
						<h1><?php esc_html_e( 'FormsCRM Settings', 'formscrm' ); ?></h1>
						<p><?php esc_html_e( 'Connect your forms with CRM, ERP, and Email Marketing platforms', 'formscrm' ); ?></p>
					</div>
					<span class="fcrm-version-badge">
						<?php echo esc_html__( 'Version', 'formscrm' ) . ' ' . esc_html( FORMSCRM_VERSION ); ?>
					</span>
				</div>
			</div>

			<!-- Main Container -->
			<div class="fcrm-container">
				<?php
				// Show success message after settings are saved.
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( isset( $_GET['settings-updated'] ) && 'true' === sanitize_text_field( wp_unslash( $_GET['settings-updated'] ) ) ) :
					?>
					<div class="fcrm-notice fcrm-notice-success">
						<?php
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG content from file.
						echo formscrm_get_svg_icon( 'icon-checkmark', 'fcrm-notice-icon' );
						?>
						<p class="fcrm-notice-text"><?php esc_html_e( 'Changes saved successfully', 'formscrm' ); ?></p>
					</div>
					<?php
					endif;

				// Check if tabs exist via filter.
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'settings';

			$formscrm_tabs = apply_filters(
				'formscrm_settings_tabs',
				array(
					array(
						'tab'    => 'settings',
						'label'  => esc_html__( 'Settings', 'formscrm' ),
						'action' => 'formscrm_settings',
					),
					array(
						'tab'    => 'notifications',
						'label'  => esc_html__( 'Notifications', 'formscrm' ),
						'action' => 'formscrm_notifications',
					),
					array(
						'tab'    => 'error-log',
						'label'  => esc_html__( 'Error Log', 'formscrm' ),
						'action' => 'formscrm_error_log_content',
					),
				)
			);

				// Ensure tabs is an array.
			if ( ! is_array( $formscrm_tabs ) ) {
				$formscrm_tabs = array();
			}

				// Display tabs if there's more than one tab.
			if ( count( $formscrm_tabs ) > 1 ) :
				?>
					<div class="fcrm-tabs-wrapper">
						<nav class="fcrm-tabs">
						<?php
						foreach ( $formscrm_tabs as $tab ) {
							if ( ! is_array( $tab ) || ! isset( $tab['tab'] ) ) {
								continue;
							}
							$is_active = $tab['tab'] === $active_tab;
							$class     = $is_active ? 'fcrm-tab fcrm-tab-active' : 'fcrm-tab';
							?>
								<a href="?page=formscrm&tab=<?php echo esc_attr( $tab['tab'] ); ?>" class="<?php echo esc_attr( $class ); ?>">
								<?php echo esc_html( $tab['label'] ?? '' ); ?>
								</a>
								<?php
						}
						// Allow addons to add their own tabs via separate action.
						do_action( 'formscrm_settings_tabs_html', $active_tab );
						?>
						</nav>
					</div>
					<?php
					endif;

				// Handle standard tabs with actions.
				$tab_handled = false;
			foreach ( $formscrm_tabs as $tab ) {
				if ( ! is_array( $tab ) || ! isset( $tab['tab'] ) ) {
					continue;
				}
				if ( $tab['tab'] === $active_tab && isset( $tab['action'] ) ) {
					do_action( $tab['action'] ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- Dynamic action name from tab configuration.
					$tab_handled = true;
				}
			}

				// If not handled by standard tabs, check for addon content (like license content).
			if ( ! $tab_handled ) {
				do_action( 'formscrm_settings_content', $active_tab );
			}
			?>

				<!-- Footer -->
				<div class="fcrm-footer">
					<?php
					printf(
						/* translators: %s: Close·technology link */
						esc_html__( 'Made with ❤️ by %s', 'formscrm' ),
						'<a href="https://close.technology/?utm_source=formscrm&utm_medium=plugin&utm_campaign=settings" target="_blank" rel="noopener noreferrer">Close·Technology</a>'
					);
					?>
				</div>
			</div>
		</div>
			<?php
		}

		/**
		 * Renders the notifications page.
		 *
		 * Displays error notification settings including Slack and Email options.
		 *
		 * @return void
		 */
		public function notifications_page() {
			$slack_webhook_url        = get_option( 'formscrm_slack_webhook_url', '' );
			$error_notification_email = get_option( 'formscrm_error_notification_email', '' );
			?>

		<!-- Error Notifications Section -->
		<div class="fcrm-section">
			<div class="fcrm-section-header">
				<h2 class="fcrm-section-title">
					<?php
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG content from file.
					echo formscrm_get_svg_icon( 'icon-bell' );
					?>
					<?php esc_html_e( 'Error Notifications', 'formscrm' ); ?>
				</h2>
				<p class="fcrm-section-description">
					<?php esc_html_e( 'Configure how you want to receive error notifications when form submissions fail', 'formscrm' ); ?>
				</p>
			</div>
			<div class="fcrm-section-content">
				<form method="post" action="options.php">
					<?php settings_fields( 'formscrm_settings' ); ?>
					
					<div class="fcrm-form-group">
						<label class="fcrm-form-label" for="formscrm_slack_webhook_url">
							<span style="width: 1rem; height: 1rem; display: inline-block; vertical-align: middle; margin-right: 0.25rem;">
								<?php
								// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG content from file.
								echo formscrm_get_svg_icon( 'icon-slack' );
								?>
							</span>
							<?php esc_html_e( 'Slack Webhook URL', 'formscrm' ); ?>
						</label>
						<input 
							type="url" 
							id="formscrm_slack_webhook_url" 
							name="formscrm_slack_webhook_url" 
							value="<?php echo esc_attr( $slack_webhook_url ); ?>" 
							class="fcrm-form-input" 
							placeholder="https://hooks.slack.com/services/YOUR/WEBHOOK/URL"
						/>
						<span class="fcrm-form-hint">
							<?php
							esc_html_e( 'Enter your Slack Incoming Webhook URL to receive error notifications in Slack. Leave empty to disable Slack notifications.', 'formscrm' );
							echo ' <a href="https://api.slack.com/messaging/webhooks" target="_blank">' . esc_html__( 'Learn how to create a Slack Webhook', 'formscrm' ) . ' →</a>';
							?>
						</span>
					</div>

					<div class="fcrm-form-group">
						<label class="fcrm-form-label" for="formscrm_error_notification_email">
							<span style="width: 1rem; height: 1rem; display: inline-block; vertical-align: middle; margin-right: 0.25rem;">
								<?php
								// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG content from file.
								echo formscrm_get_svg_icon( 'icon-email' );
								?>
							</span>
							<?php esc_html_e( 'Error Notification Email', 'formscrm' ); ?>
						</label>
						<input 
							type="text" 
							id="formscrm_error_notification_email" 
							name="formscrm_error_notification_email" 
							value="<?php echo esc_attr( $error_notification_email ); ?>" 
							class="fcrm-form-input" 
							placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>"
						/>
						<span class="fcrm-form-hint">
							<?php
							printf(
								/* translators: %s: default admin email */
								esc_html__( 'Custom email address for error notifications. Leave empty to use the default admin email (%s). You can add multiple emails separated by commas.', 'formscrm' ),
								esc_html( get_option( 'admin_email' ) )
							);
							?>
						</span>
					</div>

					<button type="submit" class="fcrm-button fcrm-button-primary">
						<?php
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG content from file.
						echo formscrm_get_svg_icon( 'icon-check-simple' );
						?>
						<?php esc_html_e( 'Save Settings', 'formscrm' ); ?>
					</button>
				</form>
			</div>
		</div>
			<?php
		}

		/**
		 * Renders the settings page.
		 *
		 * Displays supported forms and CRM integrations.
		 *
		 * @return void
		 */
		public function settings_page() {
			$source_shop_url = 'es' === strtok( get_locale(), '_' ) ? 'https://close.technology/' : 'https://close.technology/en/';
			$utm_source      = '?utm_source=WordPress+Settings&utm_medium=plugin&utm_campaign=link';
			?>

		<!-- Forms Supported Section -->
		<div class="fcrm-section">
			<div class="fcrm-section-header">
				<h2 class="fcrm-section-title">
					<?php
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG content from file.
					echo formscrm_get_svg_icon( 'icon-document' );
					?>
					<?php esc_html_e( 'Forms Supported', 'formscrm' ); ?>
				</h2>
				<p class="fcrm-section-description">
						<?php esc_html_e( 'FormsCRM works seamlessly with these popular form plugins', 'formscrm' ); ?>
				</p>
			</div>
			<div class="fcrm-section-content">
				<div class="fcrm-grid fcrm-grid-forms">
						<?php
						$forms_supported = array(
							array( 'label' => 'Gravity' ),
							array( 'label' => 'Elementor' ),
							array( 'label' => 'ContactForm7' ),
							array( 'label' => 'WooCommerce' ),
							array( 'label' => 'WPForms' ),
						);

						foreach ( $forms_supported as $form ) {
							$slug = strtolower( $form['label'] );
							?>
						<div class="fcrm-card">
							<img 
								src="<?php echo esc_url( FORMSCRM_PLUGIN_URL . 'includes/assets/forms-' . $slug . '.svg' ); ?>" 
								alt="<?php echo esc_attr( $form['label'] ); ?>"
								class="fcrm-card-icon"
							/>
							<h3 class="fcrm-card-title"><?php echo esc_html( $form['label'] ); ?></h3>
						</div>
							<?php
						}
						?>
				</div>
			</div>
		</div>

		<!-- CRM Supported Section -->
		<div class="fcrm-section">
			<div class="fcrm-section-header">
				<h2 class="fcrm-section-title">
					<?php
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG content from file.
					echo formscrm_get_svg_icon( 'icon-users' );
					?>
					<?php esc_html_e( 'CRM / ERP / Email Marketing', 'formscrm' ); ?>
				</h2>
				<p class="fcrm-section-description">
					<?php esc_html_e( 'Connect your forms with these powerful business tools. Free and premium integrations available.', 'formscrm' ); ?>
				</p>
			</div>
			<div class="fcrm-section-content">
				<div class="fcrm-grid">
					<?php
					$crms_supported = array(
						array(
							'label' => 'Holded',
							'url'   => false,
						),
						array(
							'label' => 'Clientify',
							'url'   => false,
						),
						array(
							'label' => 'AcumbaMail',
							'url'   => false,
						),
						array(
							'label' => 'Brevo',
							'url'   => false,
						),
						array(
							'label' => 'Odoo',
							'url'   => true,
						),
						array(
							'label' => 'WHMCS',
							'url'   => true,
						),
						array(
							'label' => 'vTiger',
							'url'   => true,
						),
						array(
							'label' => 'Inmovilla',
							'url'   => true,
						),
						array(
							'label' => 'Pipedrive',
							'url'   => true,
						),
						array(
							'label' => 'SuiteCRM',
							'url'   => true,
						),
						array(
							'label' => 'FacturaDirecta',
							'url'   => true,
						),
					);

					foreach ( $crms_supported as $crm ) {
						$slug       = strtolower( $crm['label'] );
						$has_link   = isset( $crm['url'] ) && $crm['url'];
						$card_class = $has_link ? 'fcrm-card fcrm-card-pro' : 'fcrm-card';
						?>
						<div class="<?php echo esc_attr( $card_class ); ?>">
							<?php if ( $has_link ) : ?>
								<span class="fcrm-card-pro-badge">PRO</span>
							<?php endif; ?>
							
							<?php if ( $has_link ) : ?>
								<a href="<?php echo esc_url( $source_shop_url . 'wordpress-plugins/formscrm-' . $slug . '/' . $utm_source ); ?>" target="_blank" class="fcrm-card-link">
							<?php endif; ?>
							
							<img 
								src="<?php echo esc_url( FORMSCRM_PLUGIN_URL . 'includes/assets/formscrm-' . $slug . '.svg' ); ?>" 
								alt="<?php echo esc_attr( $crm['label'] ); ?>"
								class="fcrm-card-icon"
							/>
							<h3 class="fcrm-card-title"><?php echo esc_html( $crm['label'] ); ?></h3>
							
							<?php if ( $has_link ) : ?>
								</a>
							<?php endif; ?>
						</div>
						<?php
					}
					?>
				</div>

				<!-- Action Buttons -->
				<div style="display: flex; gap: 1rem; margin-top: 2rem; flex-wrap: wrap;">
					<a class="fcrm-button fcrm-button-primary" href="<?php echo esc_url( $source_shop_url . 'functionalities/wordpress-forms/' . $utm_source ); ?>" target="_blank">
						<?php
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG content from file.
						echo formscrm_get_svg_icon( 'icon-plus' );
						?>
						<?php esc_html_e( 'View All Addons', 'formscrm' ); ?>
					</a>
					<a class="fcrm-button fcrm-button-secondary" href="https://wordpress.org/support/plugin/formscrm/" target="_blank">
						<?php
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG content from file.
						echo formscrm_get_svg_icon( 'icon-support' );
						?>
						<?php esc_html_e( 'Get Support', 'formscrm' ); ?>
					</a>
				</div>
			</div>
		</div>
			<?php
		}
	}
}
if ( is_admin() ) {
	$formscrm_admin = new FORMSCRM_Admin();
}
