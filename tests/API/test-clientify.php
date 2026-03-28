<?php
/**
 * Class ClientifyTests
 *
 * Tests for Clientify API v2 integration.
 * Command: composer test-debug --filter ClientifyTests
 *
 * @package Formscrm
 */

/**
 * Clientify API v2 test case.
 */
class ClientifyTests extends WP_UnitTestCase {

	/**
	 * Settings for testing
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * API connection for testing
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

		add_filter( 'pre_http_request', array( $this, 'mock_http_requests' ), 10, 3 );
	}

	/**
	 * Mock HTTP requests for Clientify API v2.
	 *
	 * @param false|array $pre     Pre-emptive return value.
	 * @param array       $r       Request arguments.
	 * @param string      $url     Request URL.
	 * @return array Mocked response.
	 */
	public function mock_http_requests( $pre, $r, $url ) {
		$response_file = '';

		// V2 login endpoint.
		if ( str_contains( $url, '/v2/me/' ) ) {
			$response_file = 'clientify-login.json';
		}

		// V2 custom fields endpoint.
		if ( str_contains( $url, '/v2/custom-fields/' ) ) {
			$response_file = 'clientify-custom-fields.json';
		}

		// V2 contact search by email: existing contact.
		if ( str_contains( $url, '/v2/contacts/' ) && 'GET' === $r['method'] && str_contains( $url, 'email=existing' ) ) {
			return array(
				'body'     => wp_json_encode(
					array(
						'count'   => 1,
						'next'    => null,
						'results' => array(
							array( 'id' => 99999 ),
						),
					)
				),
				'response' => array(
					'code'    => 200,
					'message' => 'OK',
				),
			);
		}

		// V2 contact/company search by tax_id: existing record.
		if ( str_contains( $url, '/v2/contacts/' ) && 'GET' === $r['method'] && str_contains( $url, 'taxpayer_identification_number' ) ) {
			return array(
				'body'     => wp_json_encode(
					array(
						'count'   => 1,
						'next'    => null,
						'results' => array(
							array( 'id' => 99999 ),
						),
					)
				),
				'response' => array(
					'code'    => 200,
					'message' => 'OK',
				),
			);
		}

		// V2 company search by email: existing company.
		if ( str_contains( $url, '/v2/companies/' ) && 'GET' === $r['method'] && str_contains( $url, 'email=existing' ) ) {
			return array(
				'body'     => wp_json_encode(
					array(
						'count'   => 1,
						'next'    => null,
						'results' => array(
							array( 'id' => 88888 ),
						),
					)
				),
				'response' => array(
					'code'    => 200,
					'message' => 'OK',
				),
			);
		}

		// V2 contact/company search: no match.
		if ( ( str_contains( $url, '/v2/contacts/' ) || str_contains( $url, '/v2/companies/' ) ) && 'GET' === $r['method'] ) {
			return array(
				'body'     => wp_json_encode(
					array(
						'count'   => 0,
						'next'    => null,
						'results' => array(),
					)
				),
				'response' => array(
					'code'    => 200,
					'message' => 'OK',
				),
			);
		}

		// V2 contact update via PATCH.
		if ( str_contains( $url, '/v2/contacts/' ) && 'PATCH' === $r['method'] ) {
			return array(
				'body'     => wp_json_encode(
					array(
						'id'         => 99999,
						'first_name' => 'Test',
						'last_name'  => 'User',
						'email'      => 'existing@example.com',
					)
				),
				'response' => array(
					'code'    => 200,
					'message' => 'OK',
				),
			);
		}

		// V2 company update via PATCH.
		if ( str_contains( $url, '/v2/companies/' ) && 'PATCH' === $r['method'] ) {
			return array(
				'body'     => wp_json_encode(
					array(
						'id'    => 88888,
						'name'  => 'Existing Company',
						'email' => 'existing@company.com',
					)
				),
				'response' => array(
					'code'    => 200,
					'message' => 'OK',
				),
			);
		}

		// V2 contact creation.
		if ( str_contains( $url, '/v2/contacts/' ) && 'POST' === $r['method'] ) {
			return array(
				'body'     => wp_json_encode(
					array(
						'id'         => 100597402,
						'first_name' => 'Test',
						'last_name'  => 'User',
						'email'      => 'test@example.com',
						'status'     => 'cold-lead',
					)
				),
				'response' => array(
					'code'    => 201,
					'message' => 'Created',
				),
			);
		}

		// V2 company creation.
		if ( str_contains( $url, '/v2/companies/' ) && 'POST' === $r['method'] ) {
			return array(
				'body'     => wp_json_encode(
					array(
						'id'   => 200001,
						'name' => 'New Company',
					)
				),
				'response' => array(
					'code'    => 201,
					'message' => 'Created',
				),
			);
		}

		// V2 deal creation.
		if ( str_contains( $url, '/v2/deals/' ) && 'POST' === $r['method'] ) {
			return array(
				'body'     => wp_json_encode(
					array(
						'id'     => 626,
						'name'   => 'Test Deal',
						'amount' => '1000.00',
					)
				),
				'response' => array(
					'code'    => 201,
					'message' => 'Created',
				),
			);
		}

		if ( $response_file ) {
			$response_file = UNIT_TESTS_DATA_PLUGIN_DIR . $response_file;
			if ( file_exists( $response_file ) ) {
				return array(
					'body'     => file_get_contents( $response_file ), // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
					'response' => array(
						'code'    => 200,
						'message' => 'OK',
					),
				);
			}
		}

		return array(
			'body'     => '',
			'response' => array(
				'code'    => 500,
				'message' => 'Error API',
			),
		);
	}

	/**
	 * Test login succeeds with valid credentials.
	 */
	public function test_login_without_errors() {
		$login = $this->crm_clientify->login( $this->settings );
		$this->assertTrue( $login );
	}

	/**
	 * Test login fails with empty API key.
	 */
	public function test_login_with_errors() {
		$this->settings['fc_crm_apipassword'] = '';
		$login = $this->crm_clientify->login( $this->settings );
		$this->assertFalse( $login );
	}

	/**
	 * Test modules list returns expected structure.
	 */
	public function test_list_modules_without_errors() {
		$modules = $this->crm_clientify->list_modules( $this->settings );
		$this->assertIsArray( $modules );
		$module_names = array_column( $modules, 'name' );
		$this->assertContains( 'contacts', $module_names );
		$this->assertContains( 'contacts-deals', $module_names );
		$this->assertContains( 'companies', $module_names );
		$this->assertContains( 'companies-deals', $module_names );
	}

	/**
	 * Test fields list returns standard and custom fields.
	 */
	public function test_list_fields_without_errors() {
		$fields = $this->crm_clientify->list_fields( $this->settings, 'Contacts' );
		$this->assertIsArray( $fields );
		$field_names = array_column( $fields, 'name' );

		// Standard fields.
		$this->assertContains( 'first_name', $field_names );
		$this->assertContains( 'last_name', $field_names );
		$this->assertContains( 'email', $field_names );
		$this->assertContains( 'phone', $field_names );
		$this->assertContains( 'company', $field_names );

		// Website fields.
		$this->assertContains( 'websites|corporate', $field_names );
		$this->assertContains( 'websites|personal', $field_names );

		// Custom fields from v2 mock data.
		$this->assertContains( 'custom_fields|interes2', $field_names );
		$this->assertContains( 'custom_fields|verified', $field_names );
		$this->assertContains( 'custom_fields|social_lead_1', $field_names );
	}

	/**
	 * Test v2 email type 4 (Main) and phone type 1 (Main) are present.
	 */
	public function test_list_fields_includes_v2_types() {
		$fields      = $this->crm_clientify->list_fields( $this->settings, 'Contacts' );
		$field_names = array_column( $fields, 'name' );

		$this->assertContains( 'emails|4', $field_names );
		$this->assertContains( 'phones|1', $field_names );
	}

	/**
	 * Test deal fields are present for contacts-deals module.
	 */
	public function test_list_fields_deals_module() {
		$fields      = $this->crm_clientify->list_fields( $this->settings, 'contacts-deals' );
		$field_names = array_column( $fields, 'name' );

		$this->assertContains( 'deal|name', $field_names );
		$this->assertContains( 'deal|amount', $field_names );
		$this->assertContains( 'deal|pipeline_desc', $field_names );
		$this->assertContains( 'deal|pipeline_id', $field_names );
		$this->assertContains( 'deal|pipeline_stage_desc', $field_names );
		$this->assertContains( 'deal|product_skus', $field_names );
		$this->assertContains( 'deal|tags', $field_names );
	}

	/**
	 * Test creating a contact entry.
	 */
	public function test_create_entry_contact() {
		$merge_vars = array(
			array(
				'name'  => 'first_name',
				'value' => 'Test',
			),
			array(
				'name'  => 'last_name',
				'value' => 'User',
			),
			array(
				'name'  => 'email',
				'value' => 'test@example.com',
			),
			array(
				'name'  => 'status',
				'value' => 'cold-lead',
			),
		);

		$result = $this->crm_clientify->create_entry( $this->settings, $merge_vars );
		$this->assertEquals( 'ok', $result['status'] );
		$this->assertEquals( 100597402, $result['id'] );
	}

	/**
	 * Test creating a contact with deal entry.
	 */
	public function test_create_entry_contact_deal() {
		$this->settings['fc_crm_module'] = 'contacts-deals';

		$merge_vars = array(
			array(
				'name'  => 'first_name',
				'value' => 'Test',
			),
			array(
				'name'  => 'email',
				'value' => 'test@example.com',
			),
			array(
				'name'  => 'deal|name',
				'value' => 'Test Deal',
			),
			array(
				'name'  => 'deal|amount',
				'value' => '1000',
			),
		);

		$result = $this->crm_clientify->create_entry( $this->settings, $merge_vars );
		$this->assertEquals( 'ok', $result['status'] );
		$this->assertEquals( 'deal', $result['module'] );
	}

	/**
	 * Test update_by = 'none': always creates new contact via POST.
	 */
	public function test_create_entry_update_by_none_always_creates() {
		$this->settings['fc_crm_update_by'] = 'none';

		$merge_vars = array(
			array(
				'name'  => 'first_name',
				'value' => 'Test',
			),
			array(
				'name'  => 'email',
				'value' => 'test@example.com',
			),
		);

		$result = $this->crm_clientify->create_entry( $this->settings, $merge_vars );
		$this->assertEquals( 'ok', $result['status'] );
		$this->assertEquals( 100597402, $result['id'] );
	}

	/**
	 * Test update_by = 'email': updates existing contact via PATCH when match found.
	 */
	public function test_create_entry_update_by_email_found() {
		$this->settings['fc_crm_update_by'] = 'email';

		$merge_vars = array(
			array(
				'name'  => 'first_name',
				'value' => 'Test',
			),
			array(
				'name'  => 'email',
				'value' => 'existing@example.com',
			),
		);

		$result = $this->crm_clientify->create_entry( $this->settings, $merge_vars );
		$this->assertEquals( 'ok', $result['status'] );
		$this->assertEquals( 99999, $result['id'] );
	}

	/**
	 * Test update_by = 'email': creates new contact via POST when no match found.
	 */
	public function test_create_entry_update_by_email_not_found() {
		$this->settings['fc_crm_update_by'] = 'email';

		$merge_vars = array(
			array(
				'name'  => 'first_name',
				'value' => 'New',
			),
			array(
				'name'  => 'email',
				'value' => 'new@example.com',
			),
		);

		$result = $this->crm_clientify->create_entry( $this->settings, $merge_vars );
		$this->assertEquals( 'ok', $result['status'] );
		$this->assertEquals( 100597402, $result['id'] );
	}

	/**
	 * Test update_by = 'email': creates new contact when email field is missing.
	 */
	public function test_create_entry_update_by_email_empty_value() {
		$this->settings['fc_crm_update_by'] = 'email';

		$merge_vars = array(
			array(
				'name'  => 'first_name',
				'value' => 'Test',
			),
		);

		$result = $this->crm_clientify->create_entry( $this->settings, $merge_vars );
		$this->assertEquals( 'ok', $result['status'] );
		$this->assertEquals( 100597402, $result['id'] );
	}

	/**
	 * Test update_by = 'tax_id': updates existing contact via PATCH when match found.
	 */
	public function test_create_entry_update_by_tax_id_found() {
		$this->settings['fc_crm_update_by'] = 'tax_id';

		$merge_vars = array(
			array(
				'name'  => 'first_name',
				'value' => 'Test',
			),
			array(
				'name'  => 'taxpayer_identification_number',
				'value' => '12345678A',
			),
		);

		$result = $this->crm_clientify->create_entry( $this->settings, $merge_vars );
		$this->assertEquals( 'ok', $result['status'] );
		$this->assertEquals( 99999, $result['id'] );
	}

	/**
	 * Test update_by = 'email' on companies module: updates existing company via PATCH.
	 */
	public function test_create_entry_update_by_email_companies_found() {
		$this->settings['fc_crm_module']     = 'Companies';
		$this->settings['fc_crm_update_by']  = 'email';

		$merge_vars = array(
			array(
				'name'  => 'name',
				'value' => 'Existing Company',
			),
			array(
				'name'  => 'email',
				'value' => 'existing@company.com',
			),
		);

		$result = $this->crm_clientify->create_entry( $this->settings, $merge_vars );
		$this->assertEquals( 'ok', $result['status'] );
		$this->assertEquals( 88888, $result['id'] );
	}
}
