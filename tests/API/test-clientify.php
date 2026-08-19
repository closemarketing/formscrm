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
	 * Last body sent to a contacts/ POST endpoint.
	 *
	 * @var array|null
	 */
	protected $last_contact_body = null;

	/**
	 * Central HTTP mock. Behaviour controlled by $this->mock_api_mode.
	 *
	 * @param mixed  $pre  Pre-empt value.
	 * @param array  $r    Request args.
	 * @param string $url  Request URL.
	 * @return array
	 */
	public function mock_http_request( $pre, $r, $url ) {
		$is_v2_base = false !== strpos( $url, 'api-plus.clientify.com' );

		// Login endpoint.
		if ( false !== strpos( $url, 'me/' ) ) {
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
		if ( false !== strpos( $url, 'settings/my-account/' ) ) {
			if ( 'fail' === $this->mock_api_mode ) {
				return $this->response( 500 );
			}
			return $this->response( 200, file_get_contents( UNIT_TESTS_DATA_PLUGIN_DIR . 'clientify-login.json' ) );
		}

		// Custom fields endpoint.
		if ( false !== strpos( $url, 'custom-fields' ) ) {
			$file = $is_v2_base ? 'clientify-v2-custom-fields.json' : 'clientify-custom-fields.json';
			return $this->response( 200, file_get_contents( UNIT_TESTS_DATA_PLUGIN_DIR . $file ) );
		}

		// Search endpoint (GET contacts/companies with query).
		if ( false !== strpos( $url, 'contacts/' ) && 'GET' === $r['method'] && false === strpos( $url, 'contacts/deals' ) ) {
			// The v2 API rejects any GET request without a `fields` param. Real
			// Clientify response: ["You must specify the fields param: fields = id, ..."].
			if ( $is_v2_base && false === strpos( $url, 'fields=' ) ) {
				return $this->response( 400, '["You must specify the fields param: fields = id, ..."]' );
			}
			// Search found existing contact.
			if ( false !== strpos( $url, 'query=test%40example.com' ) ) {
				return $this->response( 200, '{"count":1,"results":[{"id":"contact-123","first_name":"John","email":"test@example.com"}]}' );
			}
			// Search found existing contact by NIF/DNI. Confirmed against the real
			// Clientify API: it does NOT support filtering by
			// taxpayer_identification_number as a literal query param — only the
			// generic `query` param matches it.
			if ( false !== strpos( $url, 'query=50997453J' ) ) {
				return $this->response( 200, '{"count":1,"results":[{"id":"contact-789","first_name":"Antonio","taxpayer_identification_number":"50997453J"}]}' );
			}
			// Substring false-positive: `query` is a substring match on Clientify's
			// side, so searching for an exact email must not silently adopt an
			// unrelated contact whose email merely contains it as a substring.
			if ( false !== strpos( $url, 'query=molina%40gmail.com' ) ) {
				return $this->response( 200, '{"count":1,"results":[{"id":"contact-wrong","email":"aestepamolina@gmail.com"}]}' );
			}
			// Ambiguous: two contacts already share this exact email. Must not
			// silently pick one of them to update.
			if ( false !== strpos( $url, 'query=duplicate%40example.com' ) ) {
				return $this->response( 200, '{"count":2,"results":[{"id":"contact-dup-1","email":"duplicate@example.com"},{"id":"contact-dup-2","email":"duplicate@example.com"}]}' );
			}
			// Search not found.
			return $this->response( 200, '{"count":0,"results":[]}' );
		}

		if ( false !== strpos( $url, 'companies/' ) && 'GET' === $r['method'] ) {
			if ( $is_v2_base && false === strpos( $url, 'fields=' ) ) {
				return $this->response( 400, '["You must specify the fields param: fields = id, ..."]' );
			}
			// Search found existing company.
			if ( false !== strpos( $url, 'query=ACME' ) ) {
				return $this->response( 200, '{"count":1,"results":[{"id":"company-456","name":"ACME Corp"}]}' );
			}
			// Search not found.
			return $this->response( 200, '{"count":0,"results":[]}' );
		}

		// Create/update endpoints — capture body for assertions.
		if ( false !== strpos( $url, 'contacts/' ) && 'POST' === $r['method'] ) {
			$this->last_contact_body = json_decode( $r['body'], true );
			// Real Clientify behaviour: POSTing a contact whose NIF already exists
			// server-side returns 409. Guards against a regression in
			// determine_search_by() that would stop mapping
			// taxpayer_identification_number to the `query` search param.
			if ( ! empty( $this->last_contact_body['taxpayer_identification_number'] )
				&& '50997453J' === $this->last_contact_body['taxpayer_identification_number'] ) {
				return $this->response( 409, '{"detail":"Contact with this taxpayer_identification_number already exists."}' );
			}
			return $this->response( 201, '{"id":"contact-new","first_name":"Jane","email":"new@example.com"}' );
		}

		if ( false !== strpos( $url, 'contacts/' ) && 'PATCH' === $r['method'] ) {
			$this->last_contact_body = json_decode( $r['body'], true );
			if ( false !== strpos( $url, 'contacts/contact-789/' ) ) {
				return $this->response( 200, '{"id":"contact-789","first_name":"Antonio","taxpayer_identification_number":"50997453J","updated":true}' );
			}
			return $this->response( 200, '{"id":"contact-123","first_name":"John","email":"test@example.com","updated":true}' );
		}

		if ( false !== strpos( $url, 'companies/' ) && 'POST' === $r['method'] ) {
			$this->last_contact_body = json_decode( $r['body'], true );
			return $this->response( 201, '{"id":"company-new","name":"New Corp"}' );
		}

		if ( false !== strpos( $url, 'companies/' ) && 'PATCH' === $r['method'] ) {
			$this->last_contact_body = json_decode( $r['body'], true );
			return $this->response( 200, '{"id":"company-456","name":"ACME Corp Updated"}' );
		}

		// Deals creation.
		if ( false !== strpos( $url, 'deals/' ) && 'POST' === $r['method'] ) {
			$this->last_contact_body = json_decode( $r['body'], true );
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
	public function response( $code, $body = '' ) {
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
		$this->settings['fc_crm_module'] = 'Contacts';
		$this->crm_clientify->login( $this->settings );

		$entry_data = array(
			array(
				'name'  => 'first_name',
				'value' => 'Jane',
			),
			array(
				'name'  => 'email',
				'value' => 'new@example.com',
			),
		);

		$result = $this->crm_clientify->create_entry( $this->settings, $entry_data );

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
		$this->settings['fc_crm_module']      = 'Contacts';
		$this->settings['fc_crm_merge_entry'] = 'email';
		$this->crm_clientify->login( $this->settings );

		$entry_data = array(
			array(
				'name'  => 'first_name',
				'value' => 'John',
			),
			array(
				'name'  => 'email',
				'value' => 'test@example.com',
			),
		);

		$result = $this->crm_clientify->create_entry( $this->settings, $entry_data );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'id', $result );
		$this->assertSame( 'contact-123', $result['id'] );
	}

	/**
	 * v2: Merge by email. Contact not found — created via POST.
	 */
	public function test_create_entry_v2_merge_email_not_found_posted() {
		$this->settings['fc_crm_module']      = 'Contacts';
		$this->settings['fc_crm_merge_entry'] = 'email';
		$this->crm_clientify->login( $this->settings );

		$entry_data = array(
			array(
				'name'  => 'first_name',
				'value' => 'Alice',
			),
			array(
				'name'  => 'email',
				'value' => 'alice@example.com',
			),
		);

		$result = $this->crm_clientify->create_entry( $this->settings, $entry_data );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'id', $result );
		$this->assertSame( 'contact-new', $result['id'] );
	}

	/**
	 * v2: Merge by taxpayer_identification_number. Confirmed against the real
	 * Clientify API (developer.clientify.com) that GET /contacts/ only matches
	 * NIF/DNI via the generic `query` param, not a literal
	 * taxpayer_identification_number param. determine_search_by() maps this
	 * field to `query` — contact found and updated via PATCH, no 409.
	 *
	 * Reproduces the real-world case: Antonio Luque Oliveros (DNI 50997453J)
	 * already exists in the account with merge_strategy set to
	 * taxpayer_identification_number — this must resolve to an update, not a 409.
	 */
	public function test_create_entry_v2_merge_nif_found_patched() {
		$this->settings['fc_crm_module']      = 'Contacts';
		$this->settings['fc_crm_merge_entry'] = 'taxpayer_identification_number';
		$this->crm_clientify->login( $this->settings );

		$entry_data = array(
			array(
				'name'  => 'first_name',
				'value' => 'Antonio',
			),
			array(
				'name'  => 'taxpayer_identification_number',
				'value' => '50997453J',
			),
		);

		$result = $this->crm_clientify->create_entry( $this->settings, $entry_data );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'id', $result );
		$this->assertSame( 'contact-789', $result['id'] );
	}

	/**
	 * v2: Merge by taxpayer_identification_number. Contact not found (NIF not in
	 * Clientify) — created via POST, no error.
	 */
	public function test_create_entry_v2_merge_nif_not_found_posted() {
		$this->settings['fc_crm_module']      = 'Contacts';
		$this->settings['fc_crm_merge_entry'] = 'taxpayer_identification_number';
		$this->crm_clientify->login( $this->settings );

		$entry_data = array(
			array(
				'name'  => 'first_name',
				'value' => 'Alice',
			),
			array(
				'name'  => 'taxpayer_identification_number',
				'value' => '00000000T',
			),
		);

		$result = $this->crm_clientify->create_entry( $this->settings, $entry_data );

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
		$this->mock_api_mode                  = 'v1_via_account_status';
		$this->settings['fc_crm_module']      = 'Contacts';
		$this->settings['fc_crm_merge_entry'] = 'email';
		$this->crm_clientify->login( $this->settings );

		$entry_data = array(
			array(
				'name'  => 'first_name',
				'value' => 'John',
			),
			array(
				'name'  => 'email',
				'value' => 'test@example.com',
			),
		);

		$result = $this->crm_clientify->create_entry( $this->settings, $entry_data );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'id', $result );
		$this->assertSame( 'contact-123', $result['id'] );
	}

	/**
	 * v1: Merge by email. Contact not found — created via POST.
	 */
	public function test_create_entry_v1_merge_email_not_found_posted() {
		$this->mock_api_mode                  = 'v1_via_account_status';
		$this->settings['fc_crm_module']      = 'Contacts';
		$this->settings['fc_crm_merge_entry'] = 'email';
		$this->crm_clientify->login( $this->settings );

		$entry_data = array(
			array(
				'name'  => 'first_name',
				'value' => 'Bob',
			),
			array(
				'name'  => 'email',
				'value' => 'bob@example.com',
			),
		);

		$result = $this->crm_clientify->create_entry( $this->settings, $entry_data );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'id', $result );
		$this->assertSame( 'contact-new', $result['id'] );
	}

	/**
	 * v1: Merge by business_name (companies), exact match. Company found — updated via PATCH.
	 */
	public function test_create_entry_v1_merge_business_name_found_patched() {
		$this->mock_api_mode                  = 'v1_via_account_status';
		$this->settings['fc_crm_module']      = 'Companies';
		$this->settings['fc_crm_merge_entry'] = 'business_name';
		$this->crm_clientify->login( $this->settings );

		$entry_data = array(
			array(
				'name'  => 'name',
				'value' => 'ACME Corp',
			),
			array(
				'name'  => 'business_name',
				'value' => 'ACME Corp',
			),
		);

		$result = $this->crm_clientify->create_entry( $this->settings, $entry_data );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'id', $result );
		$this->assertSame( 'company-456', $result['id'] );
	}

	// -------------------------------------------------------------------------
	// Merge strategy exact-match safety (issue #286).
	//
	// Clientify's `query` param performs a SUBSTRING search, not an exact one.
	// These tests guard against create_or_update_entry() picking results[0] and
	// PATCHing an unrelated contact/company just because its value happens to
	// contain the searched-for text.
	// -------------------------------------------------------------------------

	/**
	 * v2: Merge by email. Search value is only a substring of an existing
	 * contact's email ("molina@gmail.com" is contained in
	 * "aestepamolina@gmail.com") — must NOT be treated as a match. A new
	 * contact is created instead of overwriting the unrelated one.
	 */
	public function test_create_entry_v2_merge_email_substring_false_positive_creates_new() {
		$this->settings['fc_crm_module']      = 'Contacts';
		$this->settings['fc_crm_merge_entry'] = 'email';
		$this->crm_clientify->login( $this->settings );

		$entry_data = array(
			array(
				'name'  => 'first_name',
				'value' => 'Molina',
			),
			array(
				'name'  => 'email',
				'value' => 'molina@gmail.com',
			),
		);

		$result = $this->crm_clientify->create_entry( $this->settings, $entry_data );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'id', $result );
		// Must be the POST-created id, never the unrelated substring-matched contact.
		$this->assertSame( 'contact-new', $result['id'] );
		$this->assertNotSame( 'contact-wrong', $result['id'] );
	}

	/**
	 * v1: Merge by business_name. Search value ("ACME") is only a substring of
	 * an existing company's name ("ACME Corp") — must NOT be treated as a
	 * match. A new company is created instead of overwriting the unrelated one.
	 */
	public function test_create_entry_v1_merge_business_name_substring_false_positive_creates_new() {
		$this->mock_api_mode                  = 'v1_via_account_status';
		$this->settings['fc_crm_module']      = 'Companies';
		$this->settings['fc_crm_merge_entry'] = 'business_name';
		$this->crm_clientify->login( $this->settings );

		$entry_data = array(
			array(
				'name'  => 'name',
				'value' => 'ACME',
			),
			array(
				'name'  => 'business_name',
				'value' => 'ACME',
			),
		);

		$result = $this->crm_clientify->create_entry( $this->settings, $entry_data );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'id', $result );
		$this->assertSame( 'company-new', $result['id'] );
	}

	/**
	 * v2: Merge by email. Two existing contacts already share the exact same
	 * email (ambiguous). Must not silently pick one to update — creates a new
	 * entry instead.
	 */
	public function test_create_entry_v2_merge_email_ambiguous_exact_matches_creates_new() {
		$this->settings['fc_crm_module']      = 'Contacts';
		$this->settings['fc_crm_merge_entry'] = 'email';
		$this->crm_clientify->login( $this->settings );

		$entry_data = array(
			array(
				'name'  => 'first_name',
				'value' => 'Dup',
			),
			array(
				'name'  => 'email',
				'value' => 'duplicate@example.com',
			),
		);

		$result = $this->crm_clientify->create_entry( $this->settings, $entry_data );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'id', $result );
		$this->assertSame( 'contact-new', $result['id'] );
	}

	// -------------------------------------------------------------------------
	// marketing_status create-only default (issue #286).
	// -------------------------------------------------------------------------

	/**
	 * v2: No merge field configured — creating a contact defaults
	 * marketing_status to 2 (Marketing Contact) when not explicitly mapped.
	 */
	public function test_create_entry_v2_no_merge_defaults_marketing_status_on_create() {
		$this->settings['fc_crm_module'] = 'Contacts';
		$this->crm_clientify->login( $this->settings );

		$entry_data = array(
			array(
				'name'  => 'email',
				'value' => 'new@example.com',
			),
		);

		$this->crm_clientify->create_entry( $this->settings, $entry_data );

		$this->assertSame( 2, $this->last_contact_body['marketing_status'] );
	}

	/**
	 * v2: Merge by email, contact found — the PATCH sent to update the existing
	 * contact must NOT inject a marketing_status default. Doing so would
	 * silently flip a contact intentionally set as Sales Contact (1) back to
	 * Marketing Contact (2) on every resubmission.
	 */
	public function test_create_entry_v2_merge_email_found_does_not_default_marketing_status_on_update() {
		$this->settings['fc_crm_module']      = 'Contacts';
		$this->settings['fc_crm_merge_entry'] = 'email';
		$this->crm_clientify->login( $this->settings );

		$entry_data = array(
			array(
				'name'  => 'first_name',
				'value' => 'John',
			),
			array(
				'name'  => 'email',
				'value' => 'test@example.com',
			),
		);

		$this->crm_clientify->create_entry( $this->settings, $entry_data );

		$this->assertArrayNotHasKey( 'marketing_status', $this->last_contact_body );
	}

	// -------------------------------------------------------------------------
	// Field-mapping label collision (issue #286).
	// -------------------------------------------------------------------------

	/**
	 * The native 'email' field and the 'emails|4' (Email Main, from the typed
	 * emails list) field must not render with the same label — mapping the
	 * wrong one silently defeats the merge strategy lookup, since it only
	 * reads $data_array['email'].
	 */
	public function test_list_fields_email_and_emails_main_have_distinct_labels() {
		$this->crm_clientify->login( $this->settings );

		$fields = $this->crm_clientify->list_fields( $this->settings, 'Contacts' );
		$labels = array();
		foreach ( $fields as $field ) {
			if ( 'email' === $field['name'] || 'emails|4' === $field['name'] ) {
				$labels[ $field['name'] ] = $field['label'];
			}
		}

		$this->assertArrayHasKey( 'email', $labels );
		$this->assertArrayHasKey( 'emails|4', $labels );
		$this->assertNotSame( $labels['email'], $labels['emails|4'] );
	}

	// gdpr_accept bool normalization tests.
	// -------------------------------------------------------------------------

	/**
	 * Data provider: merge_vars value => expected bool sent to Clientify API.
	 *
	 * @return array
	 */
	public function gdpr_accept_provider() {
		return array(
			// Falsy values — must send false.
			'empty string'     => array( '',        false ),
			'zero string'      => array( '0',       false ),
			'null'             => array( null,       false ),
			// Truthy values — must send true.
			'label string'     => array( 'Acepto',  true ),
			'one string'       => array( '1',       true ),
			'on string'        => array( 'on',      true ),
		);
	}

	/**
	 * gdpr_accept must arrive at the Clientify API as a PHP bool, not a string.
	 *
	 * @dataProvider gdpr_accept_provider
	 * @param mixed $merge_value  Value from get_merge_vars (already a string after CF7 processing).
	 * @param bool  $expected     Expected bool in the JSON body sent to the API.
	 */
	public function test_create_entry_gdpr_accept_sent_as_bool( $merge_value, $expected ) {
		$this->last_contact_body = null;

		$merge_vars = array(
			array( 'name' => 'email',       'value' => 'test@example.com' ),
			array( 'name' => 'gdpr_accept', 'value' => $merge_value ),
		);

		$this->crm_clientify->create_entry( $this->settings, $merge_vars );

		/** @var array $body */
		$body = $this->last_contact_body ?? array();
		$this->assertNotEmpty( $body, 'No POST was made to the contacts endpoint.' );
		$this->assertArrayHasKey( 'gdpr_accept', $body );
		$this->assertIsBool( $body['gdpr_accept'] );
		$this->assertSame( $expected, $body['gdpr_accept'] );
	}

	/**
	 * Contact custom_fields with an empty string value must NOT be sent to the API.
	 */
	public function test_create_entry_contact_custom_field_empty_string_is_skipped() {
		$this->last_contact_body = null;

		$merge_vars = array(
			array( 'name' => 'email',                    'value' => 'test@example.com' ),
			array( 'name' => 'custom_fields|my_field',   'value' => '' ),
			array( 'name' => 'custom_fields|filled',     'value' => 'hello' ),
		);

		$this->crm_clientify->create_entry( $this->settings, $merge_vars );

		$body          = $this->last_contact_body ?? array();
		$custom_fields = $body['custom_fields'] ?? array();
		$field_keys    = array_column( $custom_fields, 'field' );

		$this->assertNotContains( 'my_field', $field_keys, 'Empty contact custom_field must not be sent.' );
		$this->assertContains( 'filled', $field_keys, 'Non-empty contact custom_field must be sent.' );
	}

	/**
	 * Contact custom_fields with a non-empty value must be sent to the API.
	 */
	public function test_create_entry_contact_custom_field_non_empty_is_sent() {
		$this->last_contact_body = null;

		$merge_vars = array(
			array( 'name' => 'email',                  'value' => 'test@example.com' ),
			array( 'name' => 'custom_fields|interest',  'value' => 'sports' ),
		);

		$this->crm_clientify->create_entry( $this->settings, $merge_vars );

		$body          = $this->last_contact_body ?? array();
		$custom_fields = $body['custom_fields'] ?? array();
		$field_keys    = array_column( $custom_fields, 'field' );

		$this->assertContains( 'interest', $field_keys );
		$found = array_filter( $custom_fields, fn( $f ) => 'interest' === $f['field'] );
		$this->assertSame( 'sports', array_values( $found )[0]['value'] );
	}

	/**
	 * Deal custom_fields with an empty string value must NOT be sent to the API.
	 */
	public function test_create_entry_deal_custom_field_empty_string_is_skipped() {
		$this->last_contact_body = null;
		$this->settings['fc_crm_module'] = 'Contacts-Deals';

		// Capture the deals/ POST body as well.
		$last_deal_body = null;
		add_filter(
			'pre_http_request',
			function ( $pre, $r, $url ) use ( &$last_deal_body ) {
				if ( 'POST' === $r['method'] && false !== strpos( $url, '/deals/' ) ) {
					$last_deal_body = json_decode( $r['body'], true );
					return array(
						'body'     => '{"id":888}',
						'response' => array( 'code' => 201, 'message' => 'Created' ),
					);
				}
				return false;
			},
			5,
			3
		);

		$merge_vars = array(
			array( 'name' => 'email',                             'value' => 'test@example.com' ),
			array( 'name' => 'deal|name',                         'value' => 'Test deal' ),
			array( 'name' => 'deal|custom_fields|empty_field',    'value' => '' ),
			array( 'name' => 'deal|custom_fields|filled_field',   'value' => 'value123' ),
		);

		$this->crm_clientify->create_entry( $this->settings, $merge_vars );

		$deal_custom = $last_deal_body['custom_fields'] ?? array();
		$field_keys  = array_column( $deal_custom, 'field' );

		$this->assertNotContains( 'empty_field', $field_keys, 'Empty deal custom_field must not be sent.' );
		$this->assertContains( 'filled_field', $field_keys, 'Non-empty deal custom_field must be sent.' );
	}

	// -------------------------------------------------------------------------
	// api.clientify.net 504 fallback tests (v1 only).
	// -------------------------------------------------------------------------

	/**
	 * Registers a filter that returns 504 for every request to api.clientify.net
	 * (optionally restricted to $only_method) and, past $fail_count hits, a
	 * success response from the api.clientify.com fallback host. Runs at
	 * priority 20 — after mock_http_request() (10) — so it wins: WordPress's
	 * pre_http_request filter chain lets the LAST-run callback's return value
	 * win over earlier ones, not the first.
	 *
	 * @param array       $calls       Reference: every intercepted call is appended as ['method' => ..., 'url' => ...].
	 * @param int         $fail_count  How many api.clientify.net hits return 504 before this filter stops intercepting.
	 * @param array       $success_body Decoded JSON body for the api.clientify.com fallback success response.
	 * @param int         $success_code HTTP code for the fallback success response.
	 * @param string|null $only_method  If set, only intercept requests using this HTTP method.
	 * @return void
	 */
	private function register_504_filter( array &$calls, $fail_count, array $success_body, $success_code = 200, $only_method = null ) {
		add_filter(
			'pre_http_request',
			function ( $pre, $r, $url ) use ( &$calls, $fail_count, $success_body, $success_code, $only_method ) {
				if ( null !== $only_method && $only_method !== $r['method'] ) {
					return $pre;
				}
				if ( false !== strpos( $url, 'api.clientify.net' ) ) {
					$calls[] = array( 'method' => $r['method'], 'url' => $url );
					if ( count( $calls ) <= $fail_count ) {
						return $this->response( 504, '{"detail":"Gateway Timeout"}' );
					}
				}
				if ( false !== strpos( $url, 'api.clientify.com' ) ) {
					$calls[] = array( 'method' => $r['method'], 'url' => $url );
					return $this->response( $success_code, wp_json_encode( $success_body ) );
				}
				return $pre;
			},
			20,
			3
		);
	}

	/**
	 * v1 GET (contact search) 504 on api.clientify.net retries once against
	 * api.clientify.com and succeeds.
	 */
	public function test_v1_get_504_falls_back_to_clientify_com() {
		$this->mock_api_mode                  = 'v1_via_account_status';
		$this->settings['fc_crm_module']      = 'Contacts';
		$this->settings['fc_crm_merge_entry'] = 'email';
		$this->crm_clientify->login( $this->settings );

		$calls = array();
		$this->register_504_filter( $calls, 1, array( 'count' => 0, 'results' => array() ) );

		$entry_data = array(
			array( 'name' => 'first_name', 'value' => 'Fallback' ),
			array( 'name' => 'email', 'value' => 'fallback-get@example.com' ),
		);

		$result = $this->crm_clientify->create_entry( $this->settings, $entry_data );

		// 3 calls total: GET search on .net (504) -> GET search retried on .com (success,
		// "not found") -> POST create (falls through to .net again, this mock leaves it
		// alone so it succeeds via the central mock_http_request()).
		$this->assertCount( 3, $calls );
		$this->assertStringContainsString( 'api.clientify.net', $calls[0]['url'] );
		$this->assertSame( 'GET', $calls[0]['method'] );
		$this->assertStringContainsString( 'api.clientify.com', $calls[1]['url'] );
		$this->assertSame( 'GET', $calls[1]['method'] );
		$this->assertIsArray( $result );
		$this->assertSame( 'ok', $result['status'], 'Submission must not be lost when the fallback succeeds.' );
	}

	/**
	 * v1 POST (contact create, force_insert=true) 504 on api.clientify.net
	 * retries once against api.clientify.com and succeeds.
	 */
	public function test_v1_post_create_504_falls_back_to_clientify_com() {
		$this->mock_api_mode             = 'v1_via_account_status';
		$this->settings['fc_crm_module'] = 'Contacts';
		$this->crm_clientify->login( $this->settings );

		$calls = array();
		$this->register_504_filter( $calls, 1, array( 'id' => 'contact-fallback', 'email' => 'fallback-post@example.com' ), 201 );

		$entry_data = array(
			array( 'name' => 'first_name', 'value' => 'Fallback' ),
			array( 'name' => 'email', 'value' => 'fallback-post@example.com' ),
		);

		$result = $this->crm_clientify->create_entry( $this->settings, $entry_data );

		$this->assertCount( 2, $calls );
		$this->assertStringContainsString( 'api.clientify.net', $calls[0]['url'] );
		$this->assertStringContainsString( 'api.clientify.com', $calls[1]['url'] );
		$this->assertSame( 'POST', $calls[0]['method'] );
		$this->assertStringContainsString( 'force_insert=true', $calls[0]['url'] );
		$this->assertIsArray( $result );
		$this->assertSame( 'ok', $result['status'] );
		$this->assertSame( 'contact-fallback', $result['id'] );
	}

	/**
	 * v1 PATCH (contact update via merge strategy) 504 on api.clientify.net
	 * retries once against api.clientify.com and succeeds.
	 */
	public function test_v1_patch_update_504_falls_back_to_clientify_com() {
		$this->mock_api_mode                  = 'v1_via_account_status';
		$this->settings['fc_crm_module']      = 'Contacts';
		$this->settings['fc_crm_merge_entry'] = 'email';
		$this->crm_clientify->login( $this->settings );

		// The merge search itself must find an existing contact so create_or_update_entry()
		// takes the PATCH branch; the 504/fallback filter only intercepts the PATCH call.
		add_filter(
			'pre_http_request',
			function ( $pre, $r, $url ) {
				if ( 'GET' === $r['method'] && false !== strpos( $url, 'query=test%40example.com' ) ) {
					return $this->response( 200, '{"count":1,"results":[{"id":"contact-123","email":"test@example.com"}]}' );
				}
				return $pre;
			},
			20,
			3
		);

		$calls = array();
		$this->register_504_filter( $calls, 1, array( 'id' => 'contact-123', 'updated' => true ), 200, 'PATCH' );

		$entry_data = array(
			array( 'name' => 'first_name', 'value' => 'Updated' ),
			array( 'name' => 'email', 'value' => 'test@example.com' ),
		);

		$result = $this->crm_clientify->create_entry( $this->settings, $entry_data );

		$this->assertCount( 2, $calls );
		$this->assertStringContainsString( 'api.clientify.net', $calls[0]['url'] );
		$this->assertStringContainsString( 'api.clientify.com', $calls[1]['url'] );
		$this->assertSame( 'PATCH', $calls[0]['method'] );
		$this->assertIsArray( $result );
		$this->assertSame( 'ok', $result['status'] );
	}

	/**
	 * A repeated 504 (fallback also fails) must surface as an error, not retry forever.
	 */
	public function test_v1_504_persists_on_both_hosts_returns_error() {
		$this->mock_api_mode             = 'v1_via_account_status';
		$this->settings['fc_crm_module'] = 'Contacts';
		$this->crm_clientify->login( $this->settings );

		add_filter(
			'pre_http_request',
			function ( $pre, $r, $url ) {
				if ( false !== strpos( $url, 'api.clientify.net' ) || false !== strpos( $url, 'api.clientify.com' ) ) {
					return $this->response( 504, '{"detail":"Gateway Timeout"}' );
				}
				return $pre;
			},
			20,
			3
		);

		$entry_data = array(
			array( 'name' => 'first_name', 'value' => 'Persistent' ),
			array( 'name' => 'email', 'value' => 'persistent-504@example.com' ),
		);

		$result = $this->crm_clientify->create_entry( $this->settings, $entry_data );

		$this->assertIsArray( $result );
		$this->assertSame( 'error', $result['status'] );
	}

	/**
	 * v2 requests must NOT trigger the fallback — it's scoped to v1 only.
	 */
	public function test_v2_504_does_not_fall_back() {
		$this->settings['fc_crm_module'] = 'Contacts';
		$this->crm_clientify->login( $this->settings ); // Default mock_api_mode is 'v2'.

		$calls = array();
		add_filter(
			'pre_http_request',
			function ( $pre, $r, $url ) use ( &$calls ) {
				if ( false !== strpos( $url, 'contacts/' ) && 'POST' === $r['method'] ) {
					$calls[] = array( 'method' => $r['method'], 'url' => $url );
					return $this->response( 504, '{"detail":"Gateway Timeout"}' );
				}
				return $pre;
			},
			20,
			3
		);

		$entry_data = array(
			array( 'name' => 'first_name', 'value' => 'V2NoFallback' ),
			array( 'name' => 'email', 'value' => 'v2-no-fallback@example.com' ),
		);

		$result = $this->crm_clientify->create_entry( $this->settings, $entry_data );

		$this->assertCount( 1, $calls, 'v2 must fail immediately on 504, no api.clientify.com retry.' );
		$this->assertIsArray( $result );
		$this->assertSame( 'error', $result['status'] );
	}
}
