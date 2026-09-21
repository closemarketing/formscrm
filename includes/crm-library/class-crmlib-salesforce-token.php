<?php
/**
 * Salesforce OAuth token manager.
 *
 * Stores the Salesforce Connected App credentials and the OAuth tokens
 * obtained from it, encrypted at rest via formscrm_encrypt()/formscrm_decrypt().
 * Transparently refreshes the access token when it has expired.
 *
 * Never throws into the form-submission path: every failure is reported via
 * get_last_error() instead, so a broken/expired connection degrades to a
 * clear admin-facing error rather than a fatal error or lost submission.
 *
 * @package FormsCRM
 */

defined( 'ABSPATH' ) || exit;

/**
 * Manages Salesforce OAuth 2.0 credentials and tokens.
 */
class CRMLIB_Salesforce_Token {
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- Follows the existing CRMLIB_ naming convention.

	/**
	 * Option name used to store the (encrypted) OAuth settings.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'formscrm_salesforce_oauth';

	/**
	 * Returns the raw stored settings array (values still encrypted).
	 *
	 * @return array
	 */
	private function get_raw_settings(): array {
		$settings = get_option( self::OPTION_NAME, array() );
		return is_array( $settings ) ? $settings : array();
	}

	/**
	 * Persists the raw settings array.
	 *
	 * @param array $settings Settings to store.
	 * @return void
	 */
	private function save_raw_settings( array $settings ): void {
		update_option( self::OPTION_NAME, $settings, false );
	}

	/**
	 * Records the last error so the admin settings screen can display it.
	 *
	 * @param string $message Human-readable error message.
	 * @return void
	 */
	private function set_last_error( string $message ): void {
		$settings               = $this->get_raw_settings();
		$settings['last_error'] = $message;
		$this->save_raw_settings( $settings );
	}

	/**
	 * Returns the last recorded connection/refresh error, if any.
	 *
	 * @return string
	 */
	public function get_last_error(): string {
		$settings = $this->get_raw_settings();
		return isset( $settings['last_error'] ) ? (string) $settings['last_error'] : '';
	}

	/**
	 * Whether a Salesforce account is currently connected (has a refresh token).
	 *
	 * @return bool
	 */
	public function is_connected(): bool {
		$settings = $this->get_raw_settings();
		return ! empty( $settings['refresh_token'] );
	}

	/**
	 * Returns the configured environment ('production' or 'sandbox').
	 *
	 * @return string
	 */
	public function get_environment(): string {
		$settings = $this->get_raw_settings();
		return isset( $settings['environment'] ) && 'sandbox' === $settings['environment'] ? 'sandbox' : 'production';
	}

	/**
	 * Returns the decrypted Client ID (Consumer Key), or empty string when not set.
	 *
	 * @return string
	 */
	public function get_client_id(): string {
		$settings = $this->get_raw_settings();
		return isset( $settings['client_id'] ) ? formscrm_decrypt( $settings['client_id'] ) : '';
	}

	/**
	 * Returns the instance URL of the connected org, or empty string when not connected.
	 *
	 * @return string
	 */
	public function get_instance_url(): string {
		$settings = $this->get_raw_settings();
		return isset( $settings['instance_url'] ) ? (string) $settings['instance_url'] : '';
	}

	/**
	 * Returns a snapshot of the connection state for the admin settings screen.
	 *
	 * @return array{connected: bool, environment: string, instance_url: string, client_id: string, last_error: string}
	 */
	public function get_connection_status(): array {
		return array(
			'connected'    => $this->is_connected(),
			'environment'  => $this->get_environment(),
			'instance_url' => $this->get_instance_url(),
			'client_id'    => $this->get_client_id(),
			'last_error'   => $this->get_last_error(),
		);
	}

	/**
	 * Saves the Connected App credentials configured by the admin.
	 *
	 * @param string $client_id     Consumer Key.
	 * @param string $client_secret Consumer Secret. Pass an empty string to keep the previously stored secret.
	 * @param string $environment   'production' or 'sandbox'.
	 * @return void
	 */
	public function save_client_credentials( string $client_id, string $client_secret, string $environment ): void {
		$settings = $this->get_raw_settings();

		$settings['client_id']   = formscrm_encrypt( sanitize_text_field( $client_id ) );
		$settings['environment'] = 'sandbox' === $environment ? 'sandbox' : 'production';

		// Only overwrite the stored secret when a new one was actually submitted,
		// so re-saving the environment doesn't require re-entering the secret.
		if ( '' !== $client_secret ) {
			$settings['client_secret'] = formscrm_encrypt( $client_secret );
		}

		$this->save_raw_settings( $settings );
	}

	/**
	 * Clears the stored tokens (but keeps the Connected App credentials so the
	 * admin can reconnect without re-entering the Client ID/Secret).
	 *
	 * @return void
	 */
	public function disconnect(): void {
		$settings = $this->get_raw_settings();

		unset( $settings['refresh_token'], $settings['access_token'], $settings['instance_url'], $settings['expires_at'] );
		$settings['last_error'] = '';

		$this->save_raw_settings( $settings );
	}

	/**
	 * Builds the Salesforce authorization URL to redirect the admin to.
	 *
	 * @param string $redirect_uri Callback URL registered in the Connected App.
	 * @param string $state        Opaque CSRF-protection value, verified on callback.
	 * @return string|WP_Error Authorization URL, or WP_Error when no Client ID is configured.
	 */
	public function get_authorize_url( string $redirect_uri, string $state ) {
		$client_id = $this->get_client_id();

		if ( '' === $client_id ) {
			return new WP_Error( 'formscrm_salesforce_missing_client_id', __( 'Configure the Salesforce Client ID and Client Secret before connecting.', 'formscrm' ) );
		}

		$query = array(
			'response_type' => 'code',
			'client_id'     => $client_id,
			'redirect_uri'  => $redirect_uri,
			'state'         => $state,
			'scope'         => apply_filters( 'formscrm_salesforce_oauth_scope', 'api refresh_token' ),
		);

		return $this->get_login_base_url() . '/services/oauth2/authorize?' . http_build_query( $query );
	}

	/**
	 * Exchanges an authorization code for access/refresh tokens and stores them.
	 *
	 * @param string $code         Authorization code returned by Salesforce.
	 * @param string $redirect_uri The same redirect URI used to request the code.
	 * @return bool True on success, false on failure (see get_last_error()).
	 */
	public function exchange_code_for_token( string $code, string $redirect_uri ): bool {
		$settings      = $this->get_raw_settings();
		$client_id     = $this->get_client_id();
		$client_secret = isset( $settings['client_secret'] ) ? formscrm_decrypt( $settings['client_secret'] ) : '';

		if ( '' === $client_id || '' === $client_secret ) {
			$this->set_last_error( __( 'Configure the Salesforce Client ID and Client Secret before connecting.', 'formscrm' ) );
			return false;
		}

		$response = $this->token_request(
			array(
				'grant_type'    => 'authorization_code',
				'code'          => $code,
				'client_id'     => $client_id,
				'client_secret' => $client_secret,
				'redirect_uri'  => $redirect_uri,
			)
		);

		if ( is_wp_error( $response ) ) {
			$this->set_last_error( $response->get_error_message() );
			return false;
		}

		if ( empty( $response['access_token'] ) ) {
			$this->set_last_error( __( 'Salesforce did not return an access token.', 'formscrm' ) );
			return false;
		}

		$settings                 = $this->get_raw_settings();
		$settings['access_token'] = formscrm_encrypt( $response['access_token'] );
		$settings['instance_url'] = ! empty( $response['instance_url'] ) ? esc_url_raw( $response['instance_url'] ) : '';
		$settings['expires_at']   = time() + $this->get_token_ttl();
		$settings['last_error']   = '';

		// Salesforce only returns a refresh_token when the "refresh_token" scope
		// was granted (normally on the very first authorization); keep the
		// existing one when a later re-authorization doesn't return a new one.
		if ( ! empty( $response['refresh_token'] ) ) {
			$settings['refresh_token'] = formscrm_encrypt( $response['refresh_token'] );
		}

		if ( empty( $settings['refresh_token'] ) ) {
			$this->set_last_error( __( 'Salesforce did not return a refresh token. Make sure the Connected App allows the "refresh_token" (or "offline_access") scope.', 'formscrm' ) );
			return false;
		}

		$this->save_raw_settings( $settings );
		return true;
	}

	/**
	 * Returns a valid access token, transparently refreshing it first when expired.
	 *
	 * Never throws: any failure (not connected, refresh token revoked/expired,
	 * network error) is reported through get_last_error() and null is returned,
	 * so callers can fail the CRM call gracefully without breaking form submission.
	 *
	 * @return array{access_token: string, instance_url: string}|null
	 */
	public function get_valid_access_token(): ?array {
		$settings = $this->get_raw_settings();

		if ( empty( $settings['refresh_token'] ) ) {
			$this->set_last_error( __( 'Salesforce is not connected. Go to FormsCRM Settings to connect your account.', 'formscrm' ) );
			return null;
		}

		$has_fresh_token = ! empty( $settings['access_token'] )
			&& ! empty( $settings['instance_url'] )
			&& ! empty( $settings['expires_at'] )
			&& time() < (int) $settings['expires_at'];

		if ( $has_fresh_token ) {
			return array(
				'access_token' => formscrm_decrypt( $settings['access_token'] ),
				'instance_url' => (string) $settings['instance_url'],
			);
		}

		return $this->refresh_access_token();
	}

	/**
	 * Requests a new access token using the stored refresh token.
	 *
	 * @return array{access_token: string, instance_url: string}|null
	 */
	private function refresh_access_token(): ?array {
		$settings      = $this->get_raw_settings();
		$refresh_token = isset( $settings['refresh_token'] ) ? formscrm_decrypt( $settings['refresh_token'] ) : '';
		$client_id     = isset( $settings['client_id'] ) ? formscrm_decrypt( $settings['client_id'] ) : '';
		$client_secret = isset( $settings['client_secret'] ) ? formscrm_decrypt( $settings['client_secret'] ) : '';

		if ( '' === $refresh_token || '' === $client_id ) {
			$this->set_last_error( __( 'Salesforce is not connected. Go to FormsCRM Settings to connect your account.', 'formscrm' ) );
			return null;
		}

		$response = $this->token_request(
			array(
				'grant_type'    => 'refresh_token',
				'refresh_token' => $refresh_token,
				'client_id'     => $client_id,
				'client_secret' => $client_secret,
			)
		);

		if ( is_wp_error( $response ) ) {
			// A revoked/expired refresh token (invalid_grant) must not throw and must
			// not break the form submission — just surface a clear admin message.
			$this->set_last_error( $response->get_error_message() );
			return null;
		}

		if ( empty( $response['access_token'] ) ) {
			$this->set_last_error( __( 'Salesforce did not return an access token while refreshing.', 'formscrm' ) );
			return null;
		}

		$settings                 = $this->get_raw_settings();
		$settings['access_token'] = formscrm_encrypt( $response['access_token'] );
		if ( ! empty( $response['instance_url'] ) ) {
			$settings['instance_url'] = esc_url_raw( $response['instance_url'] );
		}
		$settings['expires_at'] = time() + $this->get_token_ttl();
		$settings['last_error'] = '';
		$this->save_raw_settings( $settings );

		return array(
			'access_token' => $response['access_token'],
			'instance_url' => (string) $settings['instance_url'],
		);
	}

	/**
	 * Number of seconds an access token is trusted before a refresh is attempted.
	 *
	 * Salesforce OAuth token responses don't include an expiry, so a
	 * conservative default is used and refreshed transparently on 401s would
	 * add another round trip; this keeps behaviour simple and filterable.
	 *
	 * @return int
	 */
	private function get_token_ttl(): int {
		return (int) apply_filters( 'formscrm_salesforce_token_ttl', 15 * MINUTE_IN_SECONDS );
	}

	/**
	 * Salesforce login base URL for the configured environment.
	 *
	 * @return string
	 */
	private function get_login_base_url(): string {
		$environment = $this->get_environment();
		$default_url = 'sandbox' === $environment ? 'https://test.salesforce.com' : 'https://login.salesforce.com';

		return apply_filters( 'formscrm_salesforce_login_url', $default_url, $environment );
	}

	/**
	 * Posts to the Salesforce OAuth token endpoint.
	 *
	 * @param array $params Form-encoded request parameters.
	 * @return array|WP_Error Decoded JSON response, or WP_Error with a human-readable message.
	 */
	private function token_request( array $params ) {
		$url      = $this->get_login_base_url() . '/services/oauth2/token';
		$response = wp_remote_post(
			$url,
			array(
				'body'    => $params,
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code < 200 || $code >= 300 ) {
			$message = is_array( $body ) && ! empty( $body['error_description'] )
				? $body['error_description']
				: sprintf(
					/* translators: %d: HTTP status code returned by Salesforce. */
					__( 'Salesforce OAuth request failed with HTTP %d.', 'formscrm' ),
					$code
				);

			return new WP_Error( 'formscrm_salesforce_oauth_error', $message, $body );
		}

		return is_array( $body ) ? $body : array();
	}
}
