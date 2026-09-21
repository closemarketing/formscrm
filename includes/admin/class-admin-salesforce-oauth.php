<?php
/**
 * Salesforce OAuth connect/disconnect admin flow.
 *
 * Adds a "Salesforce" tab to the FormsCRM settings page where the admin sets
 * the Connected App's Client ID/Secret and environment, then connects via
 * Salesforce's OAuth 2.0 authorization-code flow. Kept generic enough (hook
 * names, `formscrm_dependency_oauth`) that a future OAuth-based CRM could
 * reuse the same settings-tab plumbing.
 *
 * @package FormsCRM
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'FORMSCRM_Salesforce_OAuth' ) ) {
	/**
	 * Class FORMSCRM_Salesforce_OAuth
	 *
	 * Handles the Salesforce OAuth connect/disconnect admin-post actions and
	 * renders the connection settings tab.
	 */
	class FORMSCRM_Salesforce_OAuth {

		/**
		 * Query var used to carry a one-time status message back to the settings page.
		 *
		 * @var string
		 */
		const NOTICE_QUERY_VAR = 'fcrm_sf_notice';

		/**
		 * Transient name used to hold the CSRF state value between the redirect
		 * to Salesforce and the callback.
		 *
		 * @var string
		 */
		const STATE_TRANSIENT = 'formscrm_salesforce_oauth_state';

		/**
		 * Token manager instance.
		 *
		 * @var CRMLIB_Salesforce_Token
		 */
		private $token_manager;

		/**
		 * Constructor.
		 */
		public function __construct() {
			require_once FORMSCRM_PLUGIN_PATH . 'includes/crm-library/class-crmlib-salesforce-token.php';
			$this->token_manager = new CRMLIB_Salesforce_Token();

			add_filter( 'formscrm_settings_tabs', array( $this, 'add_settings_tab' ) );
			add_action( 'formscrm_salesforce_settings', array( $this, 'render_settings_page' ) );

			add_action( 'admin_post_formscrm_salesforce_save_credentials', array( $this, 'handle_save_credentials' ) );
			add_action( 'admin_post_formscrm_salesforce_connect', array( $this, 'handle_connect' ) );
			add_action( 'admin_post_formscrm_salesforce_callback', array( $this, 'handle_callback' ) );
			add_action( 'admin_post_formscrm_salesforce_disconnect', array( $this, 'handle_disconnect' ) );
		}

		/**
		 * Adds the "Salesforce" tab to the FormsCRM settings page.
		 *
		 * @param array $tabs Existing tabs.
		 * @return array
		 */
		public function add_settings_tab( $tabs ) {
			if ( ! is_array( $tabs ) ) {
				$tabs = array();
			}

			$tabs[] = array(
				'tab'    => 'salesforce',
				'label'  => __( 'Salesforce', 'formscrm' ),
				'action' => 'formscrm_salesforce_settings',
			);

			return $tabs;
		}

		/**
		 * The Salesforce Connected App's OAuth callback URL for this site.
		 *
		 * @return string
		 */
		private function get_redirect_uri() {
			return admin_url( 'admin-post.php?action=formscrm_salesforce_callback' );
		}

		/**
		 * Renders the Salesforce settings tab: credentials form plus connect/disconnect UI.
		 *
		 * @return void
		 */
		public function render_settings_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$status = $this->token_manager->get_connection_status();
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only notice display, not a state-changing action.
			$notice = isset( $_GET[ self::NOTICE_QUERY_VAR ] ) ? sanitize_key( wp_unslash( $_GET[ self::NOTICE_QUERY_VAR ] ) ) : '';
			?>
			<div class="fcrm-section">
				<div class="fcrm-section-header">
					<h2 class="fcrm-section-title"><?php esc_html_e( 'Salesforce Connection', 'formscrm' ); ?></h2>
					<p class="fcrm-section-description">
						<?php esc_html_e( 'Salesforce uses OAuth 2.0: create a Connected App in Salesforce, paste its Client ID and Client Secret below, then connect your account.', 'formscrm' ); ?>
					</p>
				</div>
				<div class="fcrm-section-content">
					<?php $this->render_notice( $notice, $status ); ?>

					<p>
						<strong><?php esc_html_e( 'OAuth Callback URL:', 'formscrm' ); ?></strong>
						<code><?php echo esc_html( $this->get_redirect_uri() ); ?></code>
						<br />
						<span class="fcrm-form-hint"><?php esc_html_e( 'Use this exact URL as the "Callback URL" of your Salesforce Connected App.', 'formscrm' ); ?></span>
					</p>

					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="formscrm_salesforce_save_credentials" />
						<?php wp_nonce_field( 'formscrm_salesforce_save_credentials' ); ?>

						<div class="fcrm-form-group">
							<label class="fcrm-form-label" for="fcrm_sf_client_id"><?php esc_html_e( 'Client ID (Consumer Key)', 'formscrm' ); ?></label>
							<input type="text" id="fcrm_sf_client_id" name="fcrm_sf_client_id" class="fcrm-form-input" value="<?php echo esc_attr( $status['client_id'] ); ?>" autocomplete="off" />
						</div>

						<div class="fcrm-form-group">
							<label class="fcrm-form-label" for="fcrm_sf_client_secret"><?php esc_html_e( 'Client Secret (Consumer Secret)', 'formscrm' ); ?></label>
							<input type="password" id="fcrm_sf_client_secret" name="fcrm_sf_client_secret" class="fcrm-form-input" value="" autocomplete="off" placeholder="<?php echo $status['connected'] ? esc_attr__( 'Leave blank to keep the saved secret', 'formscrm' ) : ''; ?>" />
						</div>

						<div class="fcrm-form-group">
							<label class="fcrm-form-label" for="fcrm_sf_environment"><?php esc_html_e( 'Environment', 'formscrm' ); ?></label>
							<select id="fcrm_sf_environment" name="fcrm_sf_environment" class="fcrm-form-input">
								<option value="production" <?php selected( $status['environment'], 'production' ); ?>><?php esc_html_e( 'Production', 'formscrm' ); ?></option>
								<option value="sandbox" <?php selected( $status['environment'], 'sandbox' ); ?>><?php esc_html_e( 'Sandbox', 'formscrm' ); ?></option>
							</select>
						</div>

						<button type="submit" class="fcrm-button fcrm-button-primary"><?php esc_html_e( 'Save Credentials', 'formscrm' ); ?></button>
					</form>

					<hr />

					<?php if ( $status['connected'] ) : ?>
						<p>
							<strong><?php esc_html_e( 'Status:', 'formscrm' ); ?></strong>
							<span style="color:#46b450;"><?php esc_html_e( 'Connected', 'formscrm' ); ?></span>
							<?php if ( ! empty( $status['instance_url'] ) ) : ?>
								(<?php echo esc_html( $status['instance_url'] ); ?>)
							<?php endif; ?>
						</p>
						<a class="fcrm-button fcrm-button-secondary" href="<?php echo esc_url( $this->get_action_url( 'formscrm_salesforce_disconnect' ) ); ?>">
							<?php esc_html_e( 'Disconnect', 'formscrm' ); ?>
						</a>
					<?php else : ?>
						<p>
							<strong><?php esc_html_e( 'Status:', 'formscrm' ); ?></strong>
							<span style="color:#dc3232;"><?php esc_html_e( 'Not connected', 'formscrm' ); ?></span>
						</p>
						<a class="fcrm-button fcrm-button-primary" href="<?php echo esc_url( $this->get_action_url( 'formscrm_salesforce_connect' ) ); ?>">
							<?php esc_html_e( 'Connect to Salesforce', 'formscrm' ); ?>
						</a>
					<?php endif; ?>

					<?php if ( ! empty( $status['last_error'] ) ) : ?>
						<p style="color:#dc3232;"><strong><?php esc_html_e( 'Last error:', 'formscrm' ); ?></strong> <?php echo esc_html( $status['last_error'] ); ?></p>
					<?php endif; ?>
				</div>
			</div>
			<?php
		}

		/**
		 * Renders a dismissible-style notice for the last connect/save/disconnect action.
		 *
		 * @param string $notice Notice key from the query string.
		 * @param array  $status Current connection status.
		 * @return void
		 */
		private function render_notice( $notice, $status ) {
			$messages = array(
				'saved'        => array( 'success', __( 'Salesforce credentials saved.', 'formscrm' ) ),
				'connected'    => array( 'success', __( 'Salesforce connected successfully.', 'formscrm' ) ),
				'disconnected' => array( 'success', __( 'Salesforce disconnected.', 'formscrm' ) ),
				'error'        => array( 'error', ! empty( $status['last_error'] ) ? $status['last_error'] : __( 'Something went wrong connecting to Salesforce.', 'formscrm' ) ),
			);

			if ( ! isset( $messages[ $notice ] ) ) {
				return;
			}

			list( $type, $text ) = $messages[ $notice ];
			$class               = 'success' === $type ? 'fcrm-notice-success' : 'fcrm-notice-error';
			?>
			<div class="fcrm-notice <?php echo esc_attr( $class ); ?>">
				<p class="fcrm-notice-text"><?php echo esc_html( $text ); ?></p>
			</div>
			<?php
		}

		/**
		 * Builds a nonce-protected admin-post URL for a given action.
		 *
		 * @param string $action Registered admin-post action name.
		 * @return string
		 */
		private function get_action_url( $action ) {
			return wp_nonce_url(
				admin_url( 'admin-post.php?action=' . $action ),
				$action
			);
		}

		/**
		 * URL of the FormsCRM settings page, Salesforce tab, with an optional notice.
		 *
		 * @param string $notice Notice key, or empty for none.
		 * @return string
		 */
		private function get_settings_url( $notice = '' ) {
			$url = add_query_arg(
				array(
					'page' => 'formscrm',
					'tab'  => 'salesforce',
				),
				admin_url( 'options-general.php' )
			);

			if ( '' !== $notice ) {
				$url = add_query_arg( self::NOTICE_QUERY_VAR, $notice, $url );
			}

			return $url;
		}

		/**
		 * Saves the Client ID/Secret/Environment submitted from the settings form.
		 *
		 * @return void
		 */
		public function handle_save_credentials() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to do this.', 'formscrm' ) );
			}
			check_admin_referer( 'formscrm_salesforce_save_credentials' );

			$client_id     = isset( $_POST['fcrm_sf_client_id'] ) ? sanitize_text_field( wp_unslash( $_POST['fcrm_sf_client_id'] ) ) : '';
			$client_secret = isset( $_POST['fcrm_sf_client_secret'] ) ? sanitize_text_field( wp_unslash( $_POST['fcrm_sf_client_secret'] ) ) : '';
			$environment   = isset( $_POST['fcrm_sf_environment'] ) ? sanitize_text_field( wp_unslash( $_POST['fcrm_sf_environment'] ) ) : 'production';

			$this->token_manager->save_client_credentials( $client_id, $client_secret, $environment );

			wp_safe_redirect( $this->get_settings_url( 'saved' ) );
			exit;
		}

		/**
		 * Redirects the admin to Salesforce's authorization page.
		 *
		 * @return void
		 */
		public function handle_connect() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to do this.', 'formscrm' ) );
			}
			check_admin_referer( 'formscrm_salesforce_connect' );

			$state = wp_generate_password( 32, false );
			set_transient( self::STATE_TRANSIENT, $state, 10 * MINUTE_IN_SECONDS );

			$authorize_url = $this->token_manager->get_authorize_url( $this->get_redirect_uri(), $state );

			if ( is_wp_error( $authorize_url ) ) {
				wp_safe_redirect( $this->get_settings_url( 'error' ) );
				exit;
			}

			wp_redirect( $authorize_url ); // phpcs:ignore WordPress.Security.SafeRedirect -- Redirecting off-site to Salesforce's own OAuth endpoint is expected here.
			exit;
		}

		/**
		 * Handles Salesforce's OAuth redirect back: verifies state, exchanges the
		 * authorization code for tokens, and stores them.
		 *
		 * @return void
		 */
		public function handle_callback() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to do this.', 'formscrm' ) );
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- CSRF protection here is Salesforce's own `state` param, checked against the stored transient below.
			$state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$code = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';

			$expected_state = get_transient( self::STATE_TRANSIENT );
			delete_transient( self::STATE_TRANSIENT );

			if ( empty( $code ) || empty( $state ) || ! is_string( $expected_state ) || ! hash_equals( $expected_state, $state ) ) {
				wp_safe_redirect( $this->get_settings_url( 'error' ) );
				exit;
			}

			$success = $this->token_manager->exchange_code_for_token( $code, $this->get_redirect_uri() );

			wp_safe_redirect( $this->get_settings_url( $success ? 'connected' : 'error' ) );
			exit;
		}

		/**
		 * Clears the stored Salesforce tokens.
		 *
		 * @return void
		 */
		public function handle_disconnect() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to do this.', 'formscrm' ) );
			}
			check_admin_referer( 'formscrm_salesforce_disconnect' );

			$this->token_manager->disconnect();

			wp_safe_redirect( $this->get_settings_url( 'disconnected' ) );
			exit;
		}
	}
}

if ( is_admin() ) {
	new FORMSCRM_Salesforce_OAuth();
}
