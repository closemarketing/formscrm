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
		 * @return void
		 */
		public function enqueue_admin_scripts() {
			wp_enqueue_style(
				'formscrm-admin',
				FORMSCRM_PLUGIN_URL . 'includes/assets/admin.css',
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
			$lang_url = 'es' === substr( get_locale(), 0, 2 ) ? '' : 'en.';
			?>
			<div class="header-wrap">
				<div class="wrapper">
					<h2 style="display: none;"></h2>
					<div id="nag-container"></div>
					<div class="header formscrm-header">
						<div class="logo">
							<h2><?php esc_html_e( 'FormsCRM Settings', 'formscrm' ); ?></h2>
						</div>
					</div>
				</div>
			</div>
			<div class="wrap">
				<?php
				settings_errors();
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
					)
				);

				// Ensure tabs is an array.
				if ( ! is_array( $formscrm_tabs ) ) {
					$formscrm_tabs = array();
				}

				echo '<h2 class="nav-tab-wrapper">';
				foreach ( $formscrm_tabs as $tab ) {
					if ( ! is_array( $tab ) || ! isset( $tab['tab'] ) ) {
						continue;
					}
					echo '<a href="?page=formscrm&tab=' . esc_attr( $tab['tab'] ) . '" class="nav-tab ';
					echo $tab['tab'] === $active_tab ? 'nav-tab-active' : '';
					echo '">' . esc_html( $tab['label'] ?? '' ) . '</a>';
				}
				// Allow addons to add their own tabs via separate action.
				do_action( 'formscrm_settings_tabs_html', $active_tab );
				echo '</h2>';

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
			</div>
			<?php
		}

		/**
		 * Renders the settings page.
		 *
		 * Displays the FormsCRM settings form with Slack integration options.
		 *
		 * @return void
		 */
		public function settings_page() {
			$source_shop_url          = 'es' === strtok( get_locale(), '_' ) ? 'https://close.technology/' : 'https://close.technology/en/';
			$utm_source               = '?utm_source=WordPress+Settings&utm_medium=plugin&utm_campaign=link';
			$slack_webhook_url        = get_option( 'formscrm_slack_webhook_url', '' );
			$error_notification_email = get_option( 'formscrm_error_notification_email', '' );
			?>

			<form method="post" action="options.php">
				<?php settings_fields( 'formscrm_settings' ); ?>

				<h3><?php esc_html_e( 'Notification Settings', 'formscrm' ); ?></h3>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="formscrm_error_notification_email"><?php esc_html_e( 'Error Notification Email', 'formscrm' ); ?></label>
						</th>
						<td>
							<input type="text" id="formscrm_error_notification_email" name="formscrm_error_notification_email" value="<?php echo esc_attr( $error_notification_email ); ?>" class="regular-text" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" />
							<p class="description">
								<?php
								printf(
									/* translators: %s: default admin email */
									esc_html__( 'Custom email address for error notifications. Leave empty to use the default admin email (%s). You can add multiple emails separated by commas.', 'formscrm' ),
									esc_html( get_option( 'admin_email' ) )
								);
								?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="formscrm_slack_webhook_url"><?php esc_html_e( 'Slack Webhook URL', 'formscrm' ); ?></label>
						</th>
						<td>
							<input type="url" id="formscrm_slack_webhook_url" name="formscrm_slack_webhook_url" value="<?php echo esc_attr( $slack_webhook_url ); ?>" class="regular-text" placeholder="https://hooks.slack.com/services/YOUR/WEBHOOK/URL" />
							<p class="description">
								<?php
								esc_html_e( 'Enter your Slack Incoming Webhook URL to receive error notifications in Slack. Leave empty to disable Slack notifications.', 'formscrm' );
								echo ' <a href="https://api.slack.com/messaging/webhooks" target="_blank">' . esc_html__( 'Learn how to create a Slack Webhook', 'formscrm' ) . ' →</a>';
								?>
							</p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>

			<hr style="margin: 30px 0;">

			<h3><strong><?php esc_html_e( 'Forms supported:', 'formscrm' ); ?></strong></h3>
			<ul class="formscrm-list-forms">
				<?php
				$forms_supported = array(
					array( 'label' => 'Gravity' ),
					array( 'label' => 'Elementor' ),
					array( 'label' => 'ContactForm7' ),
					array( 'label' => 'WooCommerce' ),
					array( 'label' => 'WPForms' ),
				);

				foreach ( $forms_supported as $form ) {
					echo '<li>';
					$slug = strtolower( $form['label'] );
					echo '<img src="' . esc_url( FORMSCRM_PLUGIN_URL . 'includes/assets/forms-' . $slug . '.svg' ) . '" width="80" alt="' . esc_html( $form['label'] ) . '"/><br/>';
					echo '</li>';
				}
				?>
			</ul>
			<h3><strong><?php esc_html_e( 'CRM/ERP/Email Marketing supported:', 'formscrm' ); ?></strong></h3>
			<ul class="formscrm-list-crm">
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
						'label' => 'Odoo',
						'url'   => true,
					),
					array(
						'label' => 'Brevo',
						'url'   => false,
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
					echo '<li class="item">';
					$slug = strtolower( $crm['label'] );
					if ( ! empty( $crm['url'] ) ) {
						$url = esc_url( $source_shop_url ) . 'wordpress-plugins/formscrm-' . $slug . '/' . esc_attr( $utm_source );
						echo ' <a href="' . esc_url( $url ) . '" target="_blank">';
					}
					echo '<img src="' . esc_url( FORMSCRM_PLUGIN_URL . 'includes/assets/formscrm-' . $slug . '.svg' ) . '" width="250" alt="' . esc_html( $crm['label'] ) . '"/><br/>';

					if ( ! empty( $crm['url'] ) ) {
						echo '</a>';
					}
					echo '</li>';
				}
					?>
			</ul>
			<br/>
			<a class="button button-primary" href="<?php echo esc_url( $source_shop_url ); ?>formscrm/<?php echo esc_attr( $utm_source ); ?>" target="_blank"><?php esc_html_e( 'View all addons', 'formscrm' ); ?></a>
			<a class="button button-secondary" href="https://wordpress.org/support/plugin/formscrm/" target="_blank"><?php esc_html_e( 'Get Support', 'formscrm' ); ?></a>
				<?php
		}
	}
}
if ( is_admin() ) {
	$formscrm_admin = new FORMSCRM_Admin();
}
