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
}
