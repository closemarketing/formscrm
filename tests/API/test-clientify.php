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

		// Default mock: v2 login + v2 custom fields.
		add_filter(
			'pre_http_request',
			function ( $pre, $r, $url ) {
				$response_file = 'clientify-';

				if ( str_contains( $url, 'api-plus.clientify.com' ) && str_contains( $url, 'me/' ) ) {
					$response_file .= 'v2-login.json';
				} elseif ( str_contains( $url, 'settings/my-account/' ) ) {
					$response_file .= 'login.json';
				} elseif ( str_contains( $url, 'custom-fields' ) ) {
					$response_file .= str_contains( $url, 'api-plus.clientify.com' ) ? 'v2-custom-fields.json' : 'custom-fields.json';
				}

				$response_file = UNIT_TESTS_DATA_PLUGIN_DIR . $response_file;
				if ( file_exists( $response_file ) ) {
					return array(
						'body'     => file_get_contents( $response_file ),
						'response' => array(
							'code'    => 200,
							'message' => 'OK',
						),
					);
				}

				return array(
					'body'     => '',
					'response' => array(
						'code'    => 500,
						'message' => 'Error API',
					),
				);
			},
			10,
			3
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
		add_filter(
			'pre_http_request',
			function ( $pre, $r, $url ) {
				if ( str_contains( $url, 'api-plus.clientify.com' ) && str_contains( $url, 'me/' ) ) {
					return array(
						'body'     => '{"id":57672,"username":"test@example.com","account_status":"client_1_0"}',
						'response' => array(
							'code'    => 200,
							'message' => 'OK',
						),
					);
				}
				return false;
			},
			5,
			3
		);

		$login = $this->crm_clientify->login( $this->settings );
		$this->assertIsArray( $login );
		$this->assertSame( 'ok', $login['status'] );
		$this->assertStringContainsString( 'v1', $login['message'] );
	}

	/**
	 * When v2 me/ fails, login falls back to v1 settings/my-account/.
	 */
	public function test_login_fallback_to_v1_when_v2_fails() {
		add_filter(
			'pre_http_request',
			function ( $pre, $r, $url ) {
				if ( str_contains( $url, 'api-plus.clientify.com' ) && str_contains( $url, 'me/' ) ) {
					return array(
						'body'     => '',
						'response' => array(
							'code'    => 500,
							'message' => 'Server Error',
						),
					);
				}
				return false;
			},
			5,
			3
		);

		$login = $this->crm_clientify->login( $this->settings );
		$this->assertIsArray( $login );
		$this->assertSame( 'ok', $login['status'] );
		$this->assertStringContainsString( 'v1', $login['message'] );
	}

	/**
	 * Both v2 and v1 endpoints failing returns error.
	 */
	public function test_login_both_endpoints_fail_returns_error() {
		add_filter(
			'pre_http_request',
			function ( $pre, $r, $url ) {
				return array(
					'body'     => '',
					'response' => array(
						'code'    => 500,
						'message' => 'Server Error',
					),
				);
			},
			5,
			3
		);

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
		$fields      = $this->crm_clientify->list_fields( $this->settings, 'Contacts' );
		$field_names = array_column( $fields, 'name' );

		$this->assertIsArray( $fields );

		// Standard contact fields.
		$this->assertContains( 'first_name', $field_names );
		$this->assertContains( 'last_name', $field_names );
		$this->assertContains( 'email', $field_names );
		$this->assertContains( 'phone', $field_names );
		$this->assertContains( 'company', $field_names );

		// Website subfields.
		$this->assertContains( 'websites|corporate', $field_names );
		$this->assertContains( 'websites|personal', $field_names );

		// v2-only field.
		$this->assertContains( 'marketing_status', $field_names );

		// v2 contact custom fields (content_type: "contacts | contacto").
		$this->assertContains( 'custom_fields|verified', $field_names );
		$this->assertContains( 'custom_fields|social_lead_1', $field_names );
		$this->assertContains( 'custom_fields|interes2', $field_names );

		// Deal fields must NOT appear in plain Contacts module.
		$this->assertNotContains( 'deal|name', $field_names );
		$this->assertNotContains( 'deal|custom_fields|campo_oportunidades', $field_names );
	}

	/**
	 * Contacts-Deals module: includes deal static fields (with v2-only pipeline_id/stage),
	 * contact custom fields and deal custom fields with deal| prefix.
	 */
	public function test_list_fields_contacts_deals_v2() {
		$fields      = $this->crm_clientify->list_fields( $this->settings, 'Contacts-Deals' );
		$field_names = array_column( $fields, 'name' );

		// Contact fields present.
		$this->assertContains( 'first_name', $field_names );
		$this->assertContains( 'custom_fields|verified', $field_names );

		// Deal static fields.
		$this->assertContains( 'deal|name', $field_names );
		$this->assertContains( 'deal|amount', $field_names );
		$this->assertContains( 'deal|pipeline_desc', $field_names );
		$this->assertContains( 'deal|product_skus', $field_names );
		$this->assertContains( 'deal|tags', $field_names );
		$this->assertContains( 'deal|expected_closed_date_days', $field_names );

		// v2-only deal fields.
		$this->assertContains( 'deal|pipeline_id', $field_names );
		$this->assertContains( 'deal|pipeline_stage_desc', $field_names );

		// Deal custom field with deal| prefix (content_type: "deals | oportunidad").
		$this->assertContains( 'deal|custom_fields|campo_oportunidades', $field_names );

		// Company custom field must NOT appear.
		$this->assertNotContains( 'custom_fields|campo_empresas', $field_names );
	}

	/**
	 * Companies module: company fields, company custom fields, no deal fields.
	 */
	public function test_list_fields_companies_v2() {
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
		$fields      = $this->crm_clientify->list_fields( $this->settings, 'Companies-Deals' );
		$field_names = array_column( $fields, 'name' );

		// Company fields present.
		$this->assertContains( 'sector', $field_names );
		$this->assertContains( 'custom_fields|campo_empresas', $field_names );

		// Deal static fields must be present.
		$this->assertContains( 'deal|name', $field_names );
		$this->assertContains( 'deal|amount', $field_names );

		// Deal custom fields with deal| prefix.
		$this->assertContains( 'deal|custom_fields|campo_oportunidades', $field_names );
	}

	// -------------------------------------------------------------------------
	// list_fields tests — v1.
	// -------------------------------------------------------------------------

	/**
	 * Force v1 login then check Contacts module: no marketing_status, no v2-only deal fields,
	 * v1 contact custom fields present.
	 */
	public function test_list_fields_contacts_v1() {
		add_filter(
			'pre_http_request',
			function ( $pre, $r, $url ) {
				if ( str_contains( $url, 'api-plus.clientify.com' ) && str_contains( $url, 'me/' ) ) {
					return array(
						'body'     => '{"id":57672,"username":"test@example.com","account_status":"client_1_0"}',
						'response' => array( 'code' => 200, 'message' => 'OK' ),
					);
				}
				return false;
			},
			5,
			3
		);

		// Login to set api_version = v1.
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
	 * Contacts-Deals module with v1: deal custom fields appear with deal| prefix,
	 * no v2-only pipeline_id or pipeline_stage_desc.
	 */
	public function test_list_fields_contacts_deals_v1() {
		add_filter(
			'pre_http_request',
			function ( $pre, $r, $url ) {
				if ( str_contains( $url, 'api-plus.clientify.com' ) && str_contains( $url, 'me/' ) ) {
					return array(
						'body'     => '{"id":57672,"username":"test@example.com","account_status":"client_1_0"}',
						'response' => array( 'code' => 200, 'message' => 'OK' ),
					);
				}
				return false;
			},
			5,
			3
		);

		$this->crm_clientify->login( $this->settings );

		$fields      = $this->crm_clientify->list_fields( $this->settings, 'Contacts-Deals' );
		$field_names = array_column( $fields, 'name' );

		// Deal static fields.
		$this->assertContains( 'deal|name', $field_names );
		$this->assertContains( 'deal|amount', $field_names );
		$this->assertContains( 'deal|pipeline_desc', $field_names );

		// v2-only deal fields must NOT appear in v1.
		$this->assertNotContains( 'deal|pipeline_id', $field_names );
		$this->assertNotContains( 'deal|pipeline_stage_desc', $field_names );

		// v1 deal custom field (content_type: "deals | deal").
		$this->assertContains( 'deal|custom_fields|campo_deal_v1', $field_names );
	}
}
