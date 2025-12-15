<?php
/**
 * Library for admin settings
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2019 Closemarketing
 * @version    1.0
 *
 * phpcs:disable WordPress.Files.FileName.InvalidClassFileName
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
 // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- File name follows plugin convention.

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
			'<svg width="512" height="512" viewBox="0 0 512 512" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path d="M53.28 95.68V382.24C53.3364 403.355 61.7493 423.589 76.68 438.52C91.6107 453.451 111.845 461.864 132.96 461.92H240.8C242.335 450.843 245.517 440.057 250.24 429.92C263.161 401.422 286.528 378.971 315.52 367.2C305.073 359.971 296.54 350.31 290.659 339.048C284.777 327.787 281.723 315.264 281.76 302.56C281.781 281.561 290.132 261.429 304.98 246.58C319.829 231.732 339.961 223.381 360.96 223.36C368.607 223.331 376.214 224.464 383.52 226.72C395.513 230.268 406.485 236.632 415.52 245.28V95.68C415.518 74.5481 407.123 54.2823 392.18 39.3398C377.238 24.3974 356.972 16.0019 335.84 16H132.96C111.837 16.0291 91.5865 24.4333 76.6499 39.3699C61.7133 54.3064 53.3091 74.5565 53.28 95.68ZM149.92 95.36H318.88C323.123 95.36 327.193 97.0457 330.194 100.046C333.194 103.047 334.88 107.117 334.88 111.36C334.88 115.603 333.194 119.673 330.194 122.674C327.193 125.674 323.123 127.36 318.88 127.36H149.92C145.677 127.36 141.607 125.674 138.606 122.674C135.606 119.673 133.92 115.603 133.92 111.36C133.92 107.117 135.606 103.047 138.606 100.046C141.607 97.0457 145.677 95.36 149.92 95.36ZM149.92 180.48H318.88C323.123 180.48 327.193 182.166 330.194 185.166C333.194 188.167 334.88 192.237 334.88 196.48C334.88 200.723 333.194 204.793 330.194 207.794C327.193 210.794 323.123 212.48 318.88 212.48H149.92C145.677 212.48 141.607 210.794 138.606 207.794C135.606 204.793 133.92 200.723 133.92 196.48C133.92 192.237 135.606 188.167 138.606 185.166C141.607 182.166 145.677 180.48 149.92 180.48ZM149.92 265.44H226.72C230.963 265.44 235.033 267.126 238.034 270.126C241.034 273.127 242.72 277.197 242.72 281.44C242.72 285.683 241.034 289.753 238.034 292.754C235.033 295.754 230.963 297.44 226.72 297.44H149.92C145.677 297.44 141.607 295.754 138.606 292.754C135.606 289.753 133.92 285.683 133.92 281.44C133.92 277.197 135.606 273.127 138.606 270.126C141.607 267.126 145.677 265.44 149.92 265.44Z" fill="black"/>
			<path d="M360.96 357.76C391.446 357.76 416.16 333.046 416.16 302.56C416.16 272.074 391.446 247.36 360.96 247.36C330.474 247.36 305.76 272.074 305.76 302.56C305.76 333.046 330.474 357.76 360.96 357.76Z" fill="black"/>
			<path d="M279.36 496H442.72C446.959 495.987 451.022 494.298 454.02 491.3C457.017 488.302 458.707 484.24 458.72 480C458.72 454.094 448.429 429.248 430.11 410.93C411.792 392.611 386.946 382.32 361.04 382.32C335.134 382.32 310.288 392.611 291.97 410.93C273.651 429.248 263.36 454.094 263.36 480C263.372 484.24 265.062 488.302 268.06 491.3C271.058 494.298 275.12 495.988 279.36 496Z" fill="black"/>
			</svg>'
		);

		add_menu_page(
			__( 'FormsCRM', 'formscrm' ),
			__( 'FormsCRM', 'formscrm' ),
			'manage_options',
			'formscrm',
			array( $this, 'create_admin_page' ),
			$icon_svg,
			30
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
			$source_shop_url   = 'es' === strtok( get_locale(), '_' ) ? 'https://close.technology/' : 'https://close.technology/en/';
			$utm_source        = '?utm_source=WordPress+Settings&utm_medium=plugin&utm_campaign=link';
			$slack_webhook_url = get_option( 'formscrm_slack_webhook_url', '' );
			?>
	
	<form method="post" action="options.php">
			<?php settings_fields( 'formscrm_settings' ); ?>
		<h3><?php esc_html_e( 'Slack Notifications', 'formscrm' ); ?></h3>
		<table class="form-table">
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
			<?php
			$source_shop_url          = 'es' === strtok( get_locale(), '_' ) ? 'https://close.technology/' : 'https://close.technology/en/';
			$utm_source               = '?utm_source=WordPress+Settings&utm_medium=plugin&utm_campaign=link';
			$error_notification_email = get_option( 'formscrm_error_notification_email', '' );
			?>
			<form method="post" action="options.php">
				<?php settings_fields( 'formscrm_settings' ); ?>
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
						if ( isset( $crm['url'] ) && $crm['url'] ) {
							$url = esc_url( $source_shop_url ) . 'wordpress-plugins/formscrm-' . $slug . '/' . esc_attr( $utm_source );
							echo ' <a href="' . esc_url( $url ) . '" target="_blank">';
						}
						echo '<img src="' . esc_url( FORMSCRM_PLUGIN_URL . 'includes/assets/formscrm-' . $slug . '.svg' ) . '" width="250" alt="' . esc_html( $crm['label'] ) . '"/><br/>';

						if ( isset( $crm['url'] ) && $crm['url'] ) {
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
