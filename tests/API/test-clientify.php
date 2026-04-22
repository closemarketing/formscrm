<?php
/**
 * Class ClientifyTests
 *
 * Command: composer test-debug --filter ClientifyTests
 *
 * @package Formscrm
 */

/**
 * Clientify API integration tests.
 */
class ClientifyTests extends WP_UnitTestCase {

	/**
	 * Settings for testing.
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * API connection for testing.
	 *
	 * @var CRMLIB_Clientify
	 */
	protected $crm_clientify;

	/**
	 * Controls which API version the mock simulates: 'v2', 'v1_via_account_status', 'v1_via_fallback', 'fail'.
	 *
	 * @var string
	 */
	protected $mock_api_mode = 'v2';

	/**
	 * Set up test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->settings = array(
			'fc_crm_type'        => 'clientify',
			'fc_crm_apipassword' => 'api-password',
			'fc_crm_module'      => 'Contacts',
		);
		$this->crm_clientify = formscrm_get_api_class( 'clientify' );
		$this->mock_api_mode = 'v2';

		add_filter( 'pre_http_request', array( $this, 'mock_http_request' ), 10, 3 );
	}

	/**
	 * Tear down: remove the mock filter.
	 */
	public function tearDown(): void {
		remove_filter( 'pre_http_request', array( $this, 'mock_http_request' ), 10 );
		parent::tearDown();
	}

	/**
	 * Central HTTP mock. Behaviour controlled by $this->mock_api_mode.
	 *
	 * @param mixed  $pre  Pre-empt value.
	 * @param array  $r    Request args.
	 * @param string $url  Request URL.
	 * @return array
	 */
	public function mock_http_request( $pre, $r, $url ) {
		$is_v2_base = str_contains( $url, 'api-plus.clientify.com' );

		// Login endpoint.
		if ( str_contains( $url, 'me/' ) ) {
			if ( 'fail' === $this->mock_api_mode ) {
				return $this->response( 500 );
			}
			if ( 'v1_via_fallback' === $this->mock_api_mode ) {
				// Simulate v2 me/ endpoint unreachable.
				return $this->response( 500 );
			}
			if ( 'v1_via_account_status' === $this->mock_api_mode ) {
				return $this->response( 200, '{"id":57672,"username":"test@example.com","account_status":"client_1_0"}' );
			}
			// v2 default.
			return $this->response( 200, file_get_contents( UNIT_TESTS_DATA_PLUGIN_DIR . 'clientify-v2-login.json' ) );
		}

		// v1 fallback login.
		if ( str_contains( $url, 'settings/my-account/' ) ) {
			if ( 'fail' === $this->mock_api_mode ) {
				return $this->response( 500 );
			}
			return $this->response( 200, file_get_contents( UNIT_TESTS_DATA_PLUGIN_DIR . 'clientify-login.json' ) );
		}

		// Custom fields endpoint.
		if ( str_contains( $url, 'custom-fields' ) ) {
			$file = $is_v2_base ? 'clientify-v2-custom-fields.json' : 'clientify-custom-fields.json';
			return $this->response( 200, file_get_contents( UNIT_TESTS_DATA_PLUGIN_DIR . $file ) );
		}

		// Search endpoint (GET contacts/companies with query).
		if ( str_contains( $url, 'contacts/' ) && 'GET' === $r['method'] && ! str_contains( $url, 'contacts/deals' ) ) {
			// Search found existing contact (v1 and v2 format).
			if ( str_contains( $url, 'query=test%40example.com' ) ) {
				return $this->response( 200, '{"id":"contact-123","first_name":"John","email":"test@example.com"}' );
			}
			// Search not found.
			return $this->response( 200, '[]' );
		}

		if ( str_contains( $url, 'companies/' ) && 'GET' === $r['method'] ) {
			// Search found existing company.
			if ( str_contains( $url, 'query=ACME' ) ) {
				return $this->response( 200, '{"id":"company-456","name":"ACME Corp"}' );
			}
			// Search not found.
			return $this->response( 200, '[]' );
		}

		// Create/update endpoints.
		if ( str_contains( $url, 'contacts/' ) && 'POST' === $r['method'] ) {
			return $this->response( 201, '{"id":"contact-new","first_name":"Jane","email":"new@example.com"}' );
		}

		if ( str_contains( $url, 'contacts/' ) && 'PATCH' === $r['method'] ) {
			return $this->response( 200, '{"id":"contact-123","first_name":"John","email":"test@example.com","updated":true}' );
		}

		if ( str_contains( $url, 'companies/' ) && 'POST' === $r['method'] ) {
			return $this->response( 201, '{"id":"company-new","name":"New Corp"}' );
		}

		if ( str_contains( $url, 'companies/' ) && 'PATCH' === $r['method'] ) {
			return $this->response( 200, '{"id":"company-456","name":"ACME Corp Updated"}' );
		}

		// Deals creation.
		if ( str_contains( $url, 'deals/' ) && 'POST' === $r['method'] ) {
			return $this->response( 201, '{"id":"deal-789","name":"New Deal","amount":5000}' );
		}

		return $this->response( 500 );
	}

	/**
	 * Builds a mock HTTP response array.
	 *
	 * @param int    $code HTTP status code.
	 * @param string $body Response body.
	 * @return array
	 */
	private function response( $code, $body = '' ) {
		return array(
			'body'     => $body,
			'response' => array(
				'code'    => $code,
				'message' => 200 === $code ? 'OK' : 'Error',
			),
		);
	}

	// -------------------------------------------------------------------------
	// Login tests.
	// -------------------------------------------------------------------------

	/**
	 * Valid v2 credentials return ok and message contains 'v2'.
	 */
	public function test_login_v2_detects_version() {
		$login = $this->crm_clientify->login( $this->settings );
		$this->assertIsArray( $login );
		$this->assertSame( 'ok', $login['status'] );
		$this->assertStringContainsString( 'v2', $login['message'] );
	}

	/**
	 * account_status = client_1_0 in v2 response sets version to v1.
	 */
	public function test_login_v1_account_status_detects_v1() {
		$this->mock_api_mode = 'v1_via_account_status';

		$login = $this->crm_clientify->login( $this->settings );
		$this->assertIsArray( $login );
		$this->assertSame( 'ok', $login['status'] );
		$this->assertStringContainsString( 'v1', $login['message'] );
	}

	/**
	 * When v2 me/ fails, login falls back to v1 settings/my-account/.
	 */
	public function test_login_fallback_to_v1_when_v2_fails() {
		$this->mock_api_mode = 'v1_via_fallback';

		$login = $this->crm_clientify->login( $this->settings );
		$this->assertIsArray( $login );
		$this->assertSame( 'ok', $login['status'] );
		$this->assertStringContainsString( 'v1', $login['message'] );
	}

	/**
	 * Both v2 and v1 endpoints failing returns error.
	 */
	public function test_login_both_endpoints_fail_returns_error() {
		$this->mock_api_mode = 'fail';

		$login = $this->crm_clientify->login( $this->settings );
		$this->assertIsArray( $login );
		$this->assertSame( 'error', $login['status'] );
	}

	/**
	 * Empty API key returns error immediately without making HTTP calls.
	 */
	public function test_login_empty_apikey_returns_error() {
		$this->settings['fc_crm_apipassword'] = '';
		$login                                = $this->crm_clientify->login( $this->settings );
		$this->assertIsArray( $login );
		$this->assertSame( 'error', $login['status'] );
	}

	// -------------------------------------------------------------------------
	// Module tests.
	// -------------------------------------------------------------------------

	/**
	 * list_modules returns all four expected modules.
	 */
	public function test_list_modules_returns_all_modules() {
		$modules = $this->crm_clientify->list_modules( $this->settings );
		$this->assertIsArray( $modules );
		$values = array_column( $modules, 'value' );
		$this->assertContains( 'Contacts', $values );
		$this->assertContains( 'Contacts-Deals', $values );
		$this->assertContains( 'Companies', $values );
		$this->assertContains( 'Companies-Deals', $values );
	}

	// -------------------------------------------------------------------------
	// list_fields tests — v2 (default mock).
	// -------------------------------------------------------------------------

	/**
	 * Contacts module: standard fields, websites, v2-only marketing_status, v2 contact custom fields.
	 * Deal fields and deal custom fields must NOT appear.
	 */
	public function test_list_fields_contacts_v2() {
		$this->crm_clientify->login( $this->settings );

		$fields      = $this->crm_clientify->list_fields( $this->settings, 'Contacts' );
		$field_names = array_column( $fields, 'name' );

		$this->assertIsArray( $fields );
		$this->assertContains( 'first_name', $field_names );
		$this->assertContains( 'last_name', $field_names );
		$this->assertContains( 'email', $field_names );
		$this->assertContains( 'phone', $field_names );
		$this->assertContains( 'company', $field_names );
		$this->assertContains( 'websites|corporate', $field_names );
		$this->assertContains( 'websites|personal', $field_names );
		// v2-only field.
		$this->assertContains( 'marketing_status', $field_names );
		// v2 contact custom fields.
		$this->assertContains( 'custom_fields|verified', $field_names );
		$this->assertContains( 'custom_fields|social_lead_1', $field_names );
		$this->assertContains( 'custom_fields|interes2', $field_names );
		// Deal fields must NOT appear.
		$this->assertNotContains( 'deal|name', $field_names );
		$this->assertNotContains( 'deal|custom_fields|campo_oportunidades', $field_names );
	}

	/**
	 * Contacts-Deals module: deal static fields + v2-only pipeline fields + deal custom fields.
	 */
	public function test_list_fields_contacts_deals_v2() {
		$this->crm_clientify->login( $this->settings );

		$fields      = $this->crm_clientify->list_fields( $this->settings, 'Contacts-Deals' );
		$field_names = array_column( $fields, 'name' );

		$this->assertContains( 'first_name', $field_names );
		$this->assertContains( 'custom_fields|verified', $field_names );
		$this->assertContains( 'deal|name', $field_names );
		$this->assertContains( 'deal|amount', $field_names );
		$this->assertContains( 'deal|pipeline_desc', $field_names );
		$this->assertContains( 'deal|product_skus', $field_names );
		$this->assertContains( 'deal|tags', $field_names );
		$this->assertContains( 'deal|expected_closed_date_days', $field_names );
		// v2-only deal fields.
		$this->assertContains( 'deal|pipeline_id', $field_names );
		$this->assertContains( 'deal|pipeline_stage_desc', $field_names );
		// Deal custom field with deal| prefix.
		$this->assertContains( 'deal|custom_fields|campo_oportunidades', $field_names );
		// Company custom field must NOT appear.
		$this->assertNotContains( 'custom_fields|campo_empresas', $field_names );
	}

	/**
	 * Companies module: company fields + company custom fields, no deal fields.
	 */
	public function test_list_fields_companies_v2() {
		$this->crm_clientify->login( $this->settings );

		$fields      = $this->crm_clientify->list_fields( $this->settings, 'Companies' );
		$field_names = array_column( $fields, 'name' );

		$this->assertContains( 'sector', $field_names );
		$this->assertContains( 'custom_fields|campo_empresas', $field_names );
		$this->assertNotContains( 'deal|name', $field_names );
		$this->assertNotContains( 'deal|custom_fields|campo_oportunidades', $field_names );
	}

	/**
	 * Companies-Deals module: deal static fields + deal custom fields with deal| prefix.
	 */
	public function test_list_fields_companies_deals_v2() {
		$this->crm_clientify->login( $this->settings );

		$fields      = $this->crm_clientify->list_fields( $this->settings, 'Companies-Deals' );
		$field_names = array_column( $fields, 'name' );

		$this->assertContains( 'sector', $field_names );
		$this->assertContains( 'custom_fields|campo_empresas', $field_names );
		$this->assertContains( 'deal|name', $field_names );
		$this->assertContains( 'deal|amount', $field_names );
		$this->assertContains( 'deal|custom_fields|campo_oportunidades', $field_names );
	}

	// -------------------------------------------------------------------------
	// list_fields tests — v1.
	// -------------------------------------------------------------------------

	/**
	 * Contacts module with v1: no marketing_status, no deal fields, v1 custom fields present.
	 */
	public function test_list_fields_contacts_v1() {
		$this->mock_api_mode = 'v1_via_account_status';
		$this->crm_clientify->login( $this->settings );

		$fields      = $this->crm_clientify->list_fields( $this->settings, 'Contacts' );
		$field_names = array_column( $fields, 'name' );

		$this->assertContains( 'first_name', $field_names );
		$this->assertContains( 'custom_fields|verified', $field_names );
		$this->assertContains( 'custom_fields|social_lead_1', $field_names );
		// v2-only field must NOT appear.
		$this->assertNotContains( 'marketing_status', $field_names );
		// Deal fields must NOT appear.
		$this->assertNotContains( 'deal|name', $field_names );
	}

	/**
	 * Contacts-Deals with v1: no v2-only pipeline fields, v1 deal custom fields with deal| prefix.
	 */
	public function test_list_fields_contacts_deals_v1() {
		$this->mock_api_mode = 'v1_via_account_status';
		$this->crm_clientify->login( $this->settings );

		$fields      = $this->crm_clientify->list_fields( $this->settings, 'Contacts-Deals' );
		$field_names = array_column( $fields, 'name' );

		$this->assertContains( 'deal|name', $field_names );
		$this->assertContains( 'deal|amount', $field_names );
		$this->assertContains( 'deal|pipeline_desc', $field_names );
		// v2-only deal fields must NOT appear.
		$this->assertNotContains( 'deal|pipeline_id', $field_names );
		$this->assertNotContains( 'deal|pipeline_stage_desc', $field_names );
		// v1 deal custom field (content_type: "deals | deal").
		$this->assertContains( 'deal|custom_fields|campo_deal_v1', $field_names );
	}

	// -------------------------------------------------------------------------
	// create_or_update_entry tests — v2 (no merge, direct POST).
	// -------------------------------------------------------------------------

	/**
	 * v2: No merge field configured. Contact created via POST directly.
	 */
	public function test_create_entry_v2_no_merge_post_directly() {
		$this->crm_clientify->login( $this->settings );

		$entry_data = array(
			'first_name' => 'Jane',
			'email'      => 'new@example.com',
		);

		$result = $this->crm_clientify->create_entry( $this->settings, 'Contacts', $entry_data );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'id', $result );
		$this->assertSame( 'contact-new', $result['id'] );
	}

	// -------------------------------------------------------------------------
	// create_or_update_entry tests — v2 (with merge field, search then POST/PATCH).
	// -------------------------------------------------------------------------

	/**
	 * v2: Merge by email. Contact found — updated via PATCH.
	 */
	public function test_create_entry_v2_merge_email_found_patched() {
		$this->settings['fc_crm_merge_field'] = 'email';
		$this->crm_clientify->login( $this->settings );

		$entry_data = array(
			'first_name' => 'John',
			'email'      => 'test@example.com',
		);

		$result = $this->crm_clientify->create_entry( $this->settings, 'Contacts', $entry_data );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'id', $result );
		$this->assertSame( 'contact-123', $result['id'] );
		$this->assertArrayHasKey( 'updated', $result );
		$this->assertTrue( $result['updated'] );
	}

	/**
	 * v2: Merge by email. Contact not found — created via POST.
	 */
	public function test_create_entry_v2_merge_email_not_found_posted() {
		$this->settings['fc_crm_merge_field'] = 'email';
		$this->crm_clientify->login( $this->settings );

		$entry_data = array(
			'first_name' => 'Alice',
			'email'      => 'alice@example.com',
		);

		$result = $this->crm_clientify->create_entry( $this->settings, 'Contacts', $entry_data );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'id', $result );
		$this->assertSame( 'contact-new', $result['id'] );
	}

	// -------------------------------------------------------------------------
	// create_or_update_entry tests — v1 (with merge field, search then POST/PATCH).
	// -------------------------------------------------------------------------

	/**
	 * v1: Merge by email. Contact found — updated via PATCH.
	 */
	public function test_create_entry_v1_merge_email_found_patched() {
		$this->mock_api_mode               = 'v1_via_account_status';
		$this->settings['fc_crm_merge_field'] = 'email';
		$this->crm_clientify->login( $this->settings );

		$entry_data = array(
			'first_name' => 'John',
			'email'      => 'test@example.com',
		);

		$result = $this->crm_clientify->create_entry( $this->settings, 'Contacts', $entry_data );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'id', $result );
		$this->assertSame( 'contact-123', $result['id'] );
		$this->assertArrayHasKey( 'updated', $result );
		$this->assertTrue( $result['updated'] );
	}

	/**
	 * v1: Merge by email. Contact not found — created via POST.
	 */
	public function test_create_entry_v1_merge_email_not_found_posted() {
		$this->mock_api_mode               = 'v1_via_account_status';
		$this->settings['fc_crm_merge_field'] = 'email';
		$this->crm_clientify->login( $this->settings );

		$entry_data = array(
			'first_name' => 'Bob',
			'email'      => 'bob@example.com',
		);

		$result = $this->crm_clientify->create_entry( $this->settings, 'Contacts', $entry_data );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'id', $result );
		$this->assertSame( 'contact-new', $result['id'] );
	}

	/**
	 * v1: Merge by business_name (companies). Company found — updated via PATCH.
	 */
	public function test_create_entry_v1_merge_business_name_found_patched() {
		$this->mock_api_mode                = 'v1_via_account_status';
		$this->settings['fc_crm_module']     = 'Companies';
		$this->settings['fc_crm_merge_field'] = 'business_name';
		$this->crm_clientify->login( $this->settings );

		$entry_data = array(
			'name'         => 'ACME Corp',
			'business_name' => 'ACME',
		);

		$result = $this->crm_clientify->create_entry( $this->settings, 'Companies', $entry_data );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'id', $result );
		$this->assertSame( 'company-456', $result['id'] );
	}

	// -------------------------------------------------------------------------
	// API version compatibility tests.
	// -------------------------------------------------------------------------

	/**
	 * v2: Verify $this->api_version is set to 'v2' after login.
	 */
	public function test_api_version_set_to_v2_after_login() {
		$this->crm_clientify->login( $this->settings );
		$this->assertSame( 'v2', $this->crm_clientify->api_version );
	}

	/**
	 * v1: Verify $this->api_version is set to 'v1' after login.
	 */
	public function test_api_version_set_to_v1_after_login() {
		$this->mock_api_mode = 'v1_via_account_status';
		$this->crm_clientify->login( $this->settings );
		$this->assertSame( 'v1', $this->crm_clientify->api_version );
	}
}
