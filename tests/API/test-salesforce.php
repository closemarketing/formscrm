<?php
/**
 * Class SalesforceTests
 *
 * Command: composer test-debug --filter SalesforceTests
 *
 * @package Formscrm
 */

/**
 * Salesforce API integration tests (OAuth token manager + CRM class).
 */
class SalesforceTests extends WP_UnitTestCase {

	/**
	 * Settings for testing.
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * API connection for testing.
	 *
	 * @var CRMLIB_Salesforce
	 */
	protected $crm_salesforce;

	/**
	 * Controls special-case mock behaviour: 'ok', 'exchange_fail', 'refresh_fail'.
	 *
	 * @var string
	 */
	protected $mock_mode = 'ok';

	/**
	 * Number of times the `sobjects/{module}/describe/` endpoint was hit.
	 *
	 * @var int
	 */
	protected $describe_calls = 0;

	/**
	 * Number of times the OAuth token endpoint was hit with grant_type=refresh_token.
	 *
	 * @var int
	 */
	protected $refresh_calls = 0;

	/**
	 * Last JSON body sent to a create/update (POST/PATCH) sobjects request.
	 *
	 * @var array|null
	 */
	protected $last_body;

	/**
	 * Set up test environment: a Salesforce account already connected (tokens
	 * obtained via a real, mocked authorization_code exchange).
	 */
	public function setUp(): void {
		parent::setUp();

		require_once FORMSCRM_PLUGIN_PATH . 'includes/crm-library/class-crmlib-salesforce-token.php';
		delete_option( CRMLIB_Salesforce_Token::OPTION_NAME );
		delete_transient( 'formscrm_sf_fields_lead' );
		delete_transient( 'formscrm_sf_fields_contact' );

		$this->mock_mode      = 'ok';
		$this->describe_calls = 0;
		$this->refresh_calls  = 0;
		$this->last_body      = null;

		add_filter( 'pre_http_request', array( $this, 'mock_http_request' ), 10, 3 );

		$token_manager = new CRMLIB_Salesforce_Token();
		$token_manager->save_client_credentials( 'test-client-id', 'test-client-secret', 'production' );
		$token_manager->exchange_code_for_token( 'test-auth-code', 'https://example.test/wp-admin/admin-post.php' );

		$this->settings = array(
			'fc_crm_type'   => 'salesforce',
			'fc_crm_module' => 'Lead',
		);
		$this->crm_salesforce = formscrm_get_api_class( 'salesforce' );
	}

	/**
	 * Tear down: remove the mock filter and stored tokens.
	 */
	public function tearDown(): void {
		remove_filter( 'pre_http_request', array( $this, 'mock_http_request' ), 10 );
		delete_option( CRMLIB_Salesforce_Token::OPTION_NAME );
		delete_transient( 'formscrm_sf_fields_lead' );
		delete_transient( 'formscrm_sf_fields_contact' );
		parent::tearDown();
	}

	/**
	 * Forces the stored access token to look expired, so the next call must refresh.
	 *
	 * @return void
	 */
	private function force_token_expired() {
		$settings                = get_option( CRMLIB_Salesforce_Token::OPTION_NAME, array() );
		$settings['expires_at']  = time() - 10;
		update_option( CRMLIB_Salesforce_Token::OPTION_NAME, $settings, false );
	}

	/**
	 * Central HTTP mock. Behaviour controlled by $this->mock_mode.
	 *
	 * @param mixed  $pre Pre-empt value.
	 * @param array  $r   Request args.
	 * @param string $url Request URL.
	 * @return array
	 */
	public function mock_http_request( $pre, $r, $url ) {
		// OAuth token endpoint (authorization_code and refresh_token grants).
		if ( false !== strpos( $url, '/services/oauth2/token' ) ) {
			$grant_type = isset( $r['body']['grant_type'] ) ? $r['body']['grant_type'] : '';

			if ( 'authorization_code' === $grant_type ) {
				if ( 'exchange_fail' === $this->mock_mode ) {
					return $this->response( 400, file_get_contents( UNIT_TESTS_DATA_PLUGIN_DIR . 'salesforce-refresh-token-error.json' ) );
				}
				return $this->response( 200, file_get_contents( UNIT_TESTS_DATA_PLUGIN_DIR . 'salesforce-oauth-token.json' ) );
			}

			if ( 'refresh_token' === $grant_type ) {
				if ( 'refresh_fail' === $this->mock_mode ) {
					return $this->response( 400, file_get_contents( UNIT_TESTS_DATA_PLUGIN_DIR . 'salesforce-refresh-token-error.json' ) );
				}
				++$this->refresh_calls;
				return $this->response( 200, file_get_contents( UNIT_TESTS_DATA_PLUGIN_DIR . 'salesforce-refresh-token.json' ) );
			}

			return $this->response( 400, '{"error":"invalid_request"}' );
		}

		// describe/ endpoint — must be checked before the generic sobjects/ checks below.
		if ( false !== strpos( $url, '/describe/' ) ) {
			++$this->describe_calls;

			if ( false !== strpos( $url, '/sobjects/Contact/describe/' ) ) {
				return $this->response( 200, file_get_contents( UNIT_TESTS_DATA_PLUGIN_DIR . 'salesforce-describe-contact.json' ) );
			}
			if ( false !== strpos( $url, '/sobjects/Lead/describe/' ) ) {
				return $this->response( 200, file_get_contents( UNIT_TESTS_DATA_PLUGIN_DIR . 'salesforce-describe-lead.json' ) );
			}
			return $this->response( 404, '[{"message":"not found","errorCode":"NOT_FOUND"}]' );
		}

		// SOQL query (dedupe lookup). Matches on the quoted literal (`'found@example.com'`,
		// URL-encoded) rather than the bare address, so "notfound@example.com" — which
		// contains "found@example.com" as a plain substring — isn't mistaken for a match.
		if ( false !== strpos( $url, '/query/?q=' ) ) {
			if ( false !== strpos( $url, rawurlencode( "'found@example.com'" ) ) ) {
				return $this->response( 200, file_get_contents( UNIT_TESTS_DATA_PLUGIN_DIR . 'salesforce-query-found.json' ) );
			}
			return $this->response( 200, file_get_contents( UNIT_TESTS_DATA_PLUGIN_DIR . 'salesforce-query-not-found.json' ) );
		}

		// Create (POST) / Update (PATCH) sobject records, for any module (Lead, Account, ...).
		if ( false !== strpos( $url, '/sobjects/' ) && 'POST' === $r['method'] ) {
			$this->last_body = json_decode( $r['body'], true );
			return $this->response( 201, file_get_contents( UNIT_TESTS_DATA_PLUGIN_DIR . 'salesforce-create-success.json' ) );
		}

		if ( false !== strpos( $url, '/sobjects/' ) && 'PATCH' === $r['method'] ) {
			$this->last_body = json_decode( $r['body'], true );
			return $this->response( 204, '' );
		}

		return $this->response( 500, '[{"message":"Unhandled mock URL: ' . $url . '","errorCode":"UNKNOWN"}]' );
	}

	/**
	 * Builds a mock HTTP response array.
	 *
	 * @param int    $code HTTP status code.
	 * @param string $body Response body.
	 * @return array
	 */
	public function response( $code, $body = '' ) {
		return array(
			'body'     => $body,
			'response' => array(
				'code'    => $code,
				'message' => 200 === $code || 201 === $code || 204 === $code ? 'OK' : 'Error',
			),
		);
	}

	// -------------------------------------------------------------------------
	// Login / token validity.
	// -------------------------------------------------------------------------

	/**
	 * A freshly connected account (valid, non-expired access token) logs in successfully.
	 */
	public function test_login_success_when_connected() {
		$login = $this->crm_salesforce->login( $this->settings );

		$this->assertIsArray( $login );
		$this->assertSame( 'ok', $login['status'] );
	}

	/**
	 * With no stored refresh token at all, login() fails with a clear message
	 * instead of throwing.
	 */
	public function test_login_error_when_not_connected() {
		delete_option( CRMLIB_Salesforce_Token::OPTION_NAME );

		$login = $this->crm_salesforce->login( $this->settings );

		$this->assertIsArray( $login );
		$this->assertSame( 'error', $login['status'] );
		$this->assertStringContainsString( 'not connected', strtolower( $login['message'] ) );
	}

	/**
	 * Exchanging the authorization code stores a usable access + refresh token.
	 */
	public function test_exchange_code_for_token_success() {
		delete_option( CRMLIB_Salesforce_Token::OPTION_NAME );

		$token_manager = new CRMLIB_Salesforce_Token();
		$token_manager->save_client_credentials( 'client-id', 'client-secret', 'production' );

		$result = $token_manager->exchange_code_for_token( 'auth-code', 'https://example.test/callback' );

		$this->assertTrue( $result );
		$this->assertTrue( $token_manager->is_connected() );
		$this->assertSame( 'https://mycompany.my.salesforce.com', $token_manager->get_instance_url() );
	}

	/**
	 * A failed authorization_code exchange (e.g. invalid code) is reported via
	 * get_last_error(), never thrown, and leaves the account disconnected.
	 */
	public function test_exchange_code_for_token_failure_sets_last_error() {
		delete_option( CRMLIB_Salesforce_Token::OPTION_NAME );
		$this->mock_mode = 'exchange_fail';

		$token_manager = new CRMLIB_Salesforce_Token();
		$token_manager->save_client_credentials( 'client-id', 'client-secret', 'production' );

		$result = $token_manager->exchange_code_for_token( 'bad-code', 'https://example.test/callback' );

		$this->assertFalse( $result );
		$this->assertFalse( $token_manager->is_connected() );
		$this->assertNotEmpty( $token_manager->get_last_error() );
	}

	/**
	 * An expired access token is transparently refreshed via grant_type=refresh_token.
	 */
	public function test_token_refresh_on_expiry() {
		$this->force_token_expired();

		$token_manager = new CRMLIB_Salesforce_Token();
		$token          = $token_manager->get_valid_access_token();

		$this->assertIsArray( $token );
		$this->assertSame( 1, $this->refresh_calls );
		$this->assertStringContainsString( 'AfterRefresh', $token['access_token'] );
	}

	/**
	 * A refresh call that isn't needed yet (token still valid) must not hit the network.
	 */
	public function test_token_not_refreshed_when_still_valid() {
		$token_manager = new CRMLIB_Salesforce_Token();
		$token_manager->get_valid_access_token();

		$this->assertSame( 0, $this->refresh_calls );
	}

	/**
	 * A revoked/expired refresh token (invalid_grant) returns null and records a
	 * clear admin-facing error instead of throwing into the form-submission path.
	 */
	public function test_refresh_failure_returns_null_with_clear_error() {
		$this->force_token_expired();
		$this->mock_mode = 'refresh_fail';

		$token_manager = new CRMLIB_Salesforce_Token();
		$token          = $token_manager->get_valid_access_token();

		$this->assertNull( $token );
		$this->assertStringContainsString( 'expired', strtolower( $token_manager->get_last_error() ) );
	}

	/**
	 * create_entry() must degrade gracefully (no exception, clear error) when the
	 * Salesforce connection has failed (e.g. expired refresh token).
	 */
	public function test_create_entry_failure_returns_error_without_throwing() {
		$this->force_token_expired();
		$this->mock_mode = 'refresh_fail';

		$entry_data = array(
			array( 'name' => 'Email', 'value' => 'notfound@example.com' ),
			array( 'name' => 'LastName', 'value' => 'Doe' ),
			array( 'name' => 'Company', 'value' => 'Acme' ),
		);

		$result = $this->crm_salesforce->create_entry( $this->settings, $entry_data );

		$this->assertIsArray( $result );
		$this->assertSame( 'error', $result['status'] );
		$this->assertNotEmpty( $result['message'] );
	}

	// -------------------------------------------------------------------------
	// list_modules().
	// -------------------------------------------------------------------------

	/**
	 * list_modules returns the four expected Salesforce object types.
	 */
	public function test_list_modules_returns_all_modules() {
		$modules = $this->crm_salesforce->list_modules( $this->settings );
		$values  = array_column( $modules, 'value' );

		$this->assertContains( 'Lead', $values );
		$this->assertContains( 'Contact', $values );
		$this->assertContains( 'Account', $values );
		$this->assertContains( 'Opportunity', $values );
	}

	// -------------------------------------------------------------------------
	// list_fields(): filtering + caching.
	// -------------------------------------------------------------------------

	/**
	 * list_fields() for Contact excludes system/calculated/compound fields and
	 * keeps createable/updateable ones, and caches the describe() result.
	 */
	public function test_list_fields_contact_filters_and_caches() {
		$fields      = $this->crm_salesforce->list_fields( $this->settings, 'Contact' );
		$field_names = array_column( $fields, 'name' );

		$this->assertContains( 'FirstName', $field_names );
		$this->assertContains( 'LastName', $field_names );
		$this->assertContains( 'Email', $field_names );
		$this->assertContains( 'Phone', $field_names );
		$this->assertContains( 'MailingStreet', $field_names );

		// System fields excluded.
		$this->assertNotContains( 'Id', $field_names );
		$this->assertNotContains( 'CreatedDate', $field_names );
		$this->assertNotContains( 'LastModifiedDate', $field_names );
		$this->assertNotContains( 'SystemModstamp', $field_names );
		// Calculated field excluded.
		$this->assertNotContains( 'FullName__c', $field_names );
		// Compound (address) field excluded.
		$this->assertNotContains( 'MailingAddress', $field_names );

		// Required flag: non-nillable, not defaulted, createable field must be required.
		$by_name = array_column( $fields, null, 'name' );
		$this->assertTrue( $by_name['LastName']['required'] );
		$this->assertFalse( $by_name['FirstName']['required'] );

		// The transient must now hold the cached field list.
		$this->assertNotFalse( get_transient( 'formscrm_sf_fields_contact' ) );

		// A second call must be served from cache — no extra describe/ call.
		$this->assertSame( 1, $this->describe_calls );
		$this->crm_salesforce->list_fields( $this->settings, 'Contact' );
		$this->assertSame( 1, $this->describe_calls, 'list_fields() must use the cached transient on a second call.' );
	}

	/**
	 * list_fields() for Lead returns Lead-specific fields and is cached independently per module.
	 */
	public function test_list_fields_lead_uses_separate_cache() {
		$this->crm_salesforce->list_fields( $this->settings, 'Contact' );
		$this->assertSame( 1, $this->describe_calls );

		$fields      = $this->crm_salesforce->list_fields( $this->settings, 'Lead' );
		$field_names = array_column( $fields, 'name' );

		$this->assertContains( 'Company', $field_names );
		$this->assertContains( 'Email', $field_names );
		$this->assertNotContains( 'Address', $field_names );
		// A different module must trigger its own describe/ request, not reuse Contact's cache.
		$this->assertSame( 2, $this->describe_calls );
	}

	// -------------------------------------------------------------------------
	// create_entry(): dedupe via SOQL, then create (POST) or update (PATCH).
	// -------------------------------------------------------------------------

	/**
	 * No existing record matches the Email — a new Lead is created via POST.
	 */
	public function test_create_entry_creates_new_when_not_found() {
		$entry_data = array(
			array( 'name' => 'Email', 'value' => 'notfound@example.com' ),
			array( 'name' => 'LastName', 'value' => 'Doe' ),
			array( 'name' => 'Company', 'value' => 'Acme' ),
		);

		$result = $this->crm_salesforce->create_entry( $this->settings, $entry_data );

		$this->assertIsArray( $result );
		$this->assertSame( 'ok', $result['status'] );
		$this->assertSame( '00Q1a0000098765AAA', $result['id'] );
		$this->assertSame( 'Doe', $this->last_body['LastName'] );
	}

	/**
	 * An existing record matches the Email — the Lead is updated via PATCH,
	 * returning the existing record's Id rather than creating a duplicate.
	 */
	public function test_create_entry_updates_when_found() {
		$entry_data = array(
			array( 'name' => 'Email', 'value' => 'found@example.com' ),
			array( 'name' => 'LastName', 'value' => 'Smith' ),
			array( 'name' => 'Company', 'value' => 'Acme' ),
		);

		$result = $this->crm_salesforce->create_entry( $this->settings, $entry_data );

		$this->assertIsArray( $result );
		$this->assertSame( 'ok', $result['status'] );
		$this->assertSame( '00Q1a0000012345AAA', $result['id'] );
		$this->assertSame( 'Smith', $this->last_body['LastName'] );
	}

	/**
	 * Account/Opportunity dedupe by Name, not Email.
	 */
	public function test_create_entry_account_dedupes_by_name() {
		$this->settings['fc_crm_module'] = 'Account';

		$entry_data = array(
			array( 'name' => 'Name', 'value' => 'notfound@example.com' ),
		);

		$result = $this->crm_salesforce->create_entry( $this->settings, $entry_data );

		$this->assertIsArray( $result );
		$this->assertSame( 'ok', $result['status'] );
	}

	/**
	 * Empty/null values from the form must not be sent to Salesforce.
	 */
	public function test_create_entry_skips_empty_values() {
		$entry_data = array(
			array( 'name' => 'Email', 'value' => 'notfound@example.com' ),
			array( 'name' => 'LastName', 'value' => 'Doe' ),
			array( 'name' => 'Company', 'value' => 'Acme' ),
			array( 'name' => 'Description', 'value' => '' ),
		);

		$this->crm_salesforce->create_entry( $this->settings, $entry_data );

		$this->assertArrayNotHasKey( 'Description', $this->last_body );
	}
}
