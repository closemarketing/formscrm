<?php
/**
 * Class BrevoTests
 *
 * Command: composer test-debug -- --filter BrevoTests
 *
 * @package Formscrm
 */

/**
 * Brevo API integration tests.
 */
class BrevoTests extends WP_UnitTestCase {

	/**
	 * Settings for testing.
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * API connection for testing.
	 *
	 * @var CRMLIB_Brevo
	 */
	protected $crm_brevo;

	/**
	 * Controls which scenario the mock simulates: 'ok', 'error', 'empty'.
	 *
	 * @var string
	 */
	protected $mock_mode = 'ok';

	/**
	 * Last body sent to a POST endpoint.
	 *
	 * @var array|null
	 */
	protected $last_request_body = null;

	/**
	 * Set up test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->settings = array(
			'fc_crm_type'        => 'brevo',
			'fc_crm_apipassword' => 'test-api-key-xxxxx',
			'fc_crm_module'      => 10,
		);
		$this->crm_brevo   = formscrm_get_api_class( 'brevo' );
		$this->mock_mode   = 'ok';
		$this->last_request_body = null;

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
	 * Central HTTP mock. Behaviour controlled by $this->mock_mode.
	 *
	 * @param mixed  $pre Pre-empt value.
	 * @param array  $r   Request args.
	 * @param string $url Request URL.
	 * @return array
	 */
	public function mock_http_request( $pre, $r, $url ) {
		if ( 'error' === $this->mock_mode ) {
			return $this->response( 401, '{"code":"unauthorized","message":"Key not found"}' );
		}

		// contacts/lists endpoint (login + list_modules).
		if ( str_contains( $url, 'contacts/lists' ) ) {
			if ( 'empty' === $this->mock_mode ) {
				return $this->response( 200, '{"lists":[],"count":0}' );
			}
			return $this->response( 200, file_get_contents( UNIT_TESTS_DATA_PLUGIN_DIR . 'brevo-get-contacts-lists.json' ) );
		}

		// contacts/attributes endpoint (list_fields).
		if ( str_contains( $url, 'contacts/attributes' ) ) {
			return $this->response( 200, file_get_contents( UNIT_TESTS_DATA_PLUGIN_DIR . 'brevo-get-contacts-attributes.json' ) );
		}

		// contacts POST endpoint (create_entry) — capture body for assertions.
		if ( 'POST' === $r['method'] && str_contains( $url, '/contacts' ) ) {
			$this->last_request_body = json_decode( $r['body'], true );
			return $this->response( 201, '{"id":42}' );
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
				'message' => 200 === $code || 201 === $code ? 'OK' : 'Error',
			),
		);
	}

	// -------------------------------------------------------------------------
	// Login tests.
	// -------------------------------------------------------------------------

	/**
	 * Valid API key returns true.
	 */
	public function test_login_valid_credentials_returns_true() {
		$result = $this->crm_brevo->login( $this->settings );
		$this->assertTrue( $result );
	}

	/**
	 * Invalid API key returns error array.
	 */
	public function test_login_invalid_credentials_returns_error() {
		$this->mock_mode = 'error';
		$result          = $this->crm_brevo->login( $this->settings );
		$this->assertIsArray( $result );
		$this->assertSame( 'error', $result['status'] );
	}

	/**
	 * Empty API key returns null/falsy without making HTTP calls.
	 */
	public function test_login_empty_apikey_returns_falsy() {
		$this->settings['fc_crm_apipassword'] = '';
		$result = $this->crm_brevo->login( $this->settings );
		$this->assertNotTrue( $result );
	}

	// -------------------------------------------------------------------------
	// list_modules tests.
	// -------------------------------------------------------------------------

	/**
	 * Returns an array with label/value pairs from the mock data.
	 */
	public function test_list_modules_returns_lists() {
		$modules = $this->crm_brevo->list_modules( $this->settings );
		$this->assertIsArray( $modules );
		$this->assertNotEmpty( $modules );
	}

	/**
	 * Each module entry has label (string) and value (int).
	 */
	public function test_list_modules_entries_have_label_and_int_value() {
		$modules = $this->crm_brevo->list_modules( $this->settings );
		foreach ( $modules as $module ) {
			$this->assertArrayHasKey( 'label', $module );
			$this->assertArrayHasKey( 'value', $module );
			$this->assertIsString( $module['label'] );
			$this->assertIsInt( $module['value'] );
		}
	}

	/**
	 * List IDs from mock data are present in the returned modules.
	 */
	public function test_list_modules_contains_expected_ids() {
		$modules = $this->crm_brevo->list_modules( $this->settings );
		$values  = array_column( $modules, 'value' );
		$this->assertContains( 10, $values );
		$this->assertContains( 15, $values );
		$this->assertContains( 19, $values );
	}

	/**
	 * List names from mock data are present in returned modules.
	 */
	public function test_list_modules_contains_expected_names() {
		$modules = $this->crm_brevo->list_modules( $this->settings );
		$labels  = array_column( $modules, 'label' );
		$this->assertContains( 'Test List A', $labels );
		$this->assertContains( 'Test List J', $labels );
	}

	/**
	 * Bad credentials return an empty array.
	 */
	public function test_list_modules_on_auth_error_returns_empty_array() {
		$this->mock_mode = 'error';
		$modules         = $this->crm_brevo->list_modules( $this->settings );
		$this->assertIsArray( $modules );
		$this->assertEmpty( $modules );
	}

	/**
	 * Empty lists response returns an empty array.
	 */
	public function test_list_modules_on_empty_lists_returns_empty_array() {
		$this->mock_mode = 'empty';
		$modules         = $this->crm_brevo->list_modules( $this->settings );
		$this->assertIsArray( $modules );
		$this->assertEmpty( $modules );
	}

	// -------------------------------------------------------------------------
	// list_fields tests.
	// -------------------------------------------------------------------------

	/**
	 * Returns a non-empty array of fields.
	 */
	public function test_list_fields_returns_fields() {
		$fields = $this->crm_brevo->list_fields( $this->settings, '' );
		$this->assertIsArray( $fields );
		$this->assertNotEmpty( $fields );
	}

	/**
	 * email and ext_id are always present as the first two fields.
	 */
	public function test_list_fields_always_includes_email_and_ext_id() {
		$fields = $this->crm_brevo->list_fields( $this->settings, '' );
		$names  = array_column( $fields, 'name' );
		$this->assertContains( 'email', $names );
		$this->assertContains( 'ext_id', $names );
	}

	/**
	 * Global category attributes are excluded from the field map.
	 */
	public function test_list_fields_excludes_global_category() {
		$fields = $this->crm_brevo->list_fields( $this->settings, '' );
		$names  = array_column( $fields, 'name' );
		// These are in the mock data with category=global.
		$this->assertNotContains( 'BLACKLIST', $names );
		$this->assertNotContains( 'READERS', $names );
		$this->assertNotContains( 'CLICKERS', $names );
	}

	/**
	 * EXT_ID attribute from attributes list is not duplicated (already added manually).
	 */
	public function test_list_fields_ext_id_not_duplicated() {
		$fields = $this->crm_brevo->list_fields( $this->settings, '' );
		$names  = array_column( $fields, 'name' );
		$count  = array_count_values( $names );
		$this->assertSame( 1, $count['ext_id'] );
	}

	/**
	 * Normal and transactional attributes are included.
	 */
	public function test_list_fields_includes_normal_and_transactional_attributes() {
		$fields = $this->crm_brevo->list_fields( $this->settings, '' );
		$names  = array_column( $fields, 'name' );
		// Normal attributes.
		$this->assertContains( 'NOMBRE', $names );
		$this->assertContains( 'PHONE', $names );
		// Transactional attributes.
		$this->assertContains( 'ORDER_ID', $names );
		$this->assertContains( 'ORDER_DATE', $names );
	}

	/**
	 * Each field entry has a label and a name key.
	 */
	public function test_list_fields_entries_have_label_and_name() {
		$fields = $this->crm_brevo->list_fields( $this->settings, '' );
		foreach ( $fields as $field ) {
			$this->assertArrayHasKey( 'label', $field );
			$this->assertArrayHasKey( 'name', $field );
		}
	}

	// -------------------------------------------------------------------------
	// create_entry tests.
	// -------------------------------------------------------------------------

	/**
	 * Successful create_entry returns status ok with an id.
	 */
	public function test_create_entry_returns_ok_with_id() {
		$merge_vars = array(
			array( 'name' => 'email', 'value' => 'contact@example.com' ),
			array( 'name' => 'NOMBRE', 'value' => 'Jane' ),
		);
		$result = $this->crm_brevo->create_entry( $this->settings, $merge_vars );
		$this->assertIsArray( $result );
		$this->assertSame( 'ok', $result['status'] );
		$this->assertArrayHasKey( 'id', $result );
		$this->assertSame( 42, $result['id'] );
	}

	/**
	 * email is placed at root level of the POST body, not inside attributes.
	 */
	public function test_create_entry_email_at_root_level() {
		$merge_vars = array(
			array( 'name' => 'email', 'value' => 'contact@example.com' ),
		);
		$this->crm_brevo->create_entry( $this->settings, $merge_vars );

		$body = $this->last_request_body;
		$this->assertNotNull( $body );
		$this->assertArrayHasKey( 'email', $body );
		$this->assertSame( 'contact@example.com', $body['email'] );
		$this->assertArrayNotHasKey( 'email', $body['attributes'] ?? array() );
	}

	/**
	 * ext_id is placed at root level of the POST body.
	 */
	public function test_create_entry_ext_id_at_root_level() {
		$merge_vars = array(
			array( 'name' => 'email',  'value' => 'contact@example.com' ),
			array( 'name' => 'ext_id', 'value' => 'user-123' ),
		);
		$this->crm_brevo->create_entry( $this->settings, $merge_vars );

		$body = $this->last_request_body;
		$this->assertArrayHasKey( 'ext_id', $body );
		$this->assertSame( 'user-123', $body['ext_id'] );
	}

	/**
	 * Custom attribute fields are placed inside the attributes object.
	 */
	public function test_create_entry_custom_field_placed_in_attributes() {
		$merge_vars = array(
			array( 'name' => 'email',  'value' => 'contact@example.com' ),
			array( 'name' => 'NOMBRE', 'value' => 'Jane' ),
			array( 'name' => 'PHONE',  'value' => '600000000' ),
		);
		$this->crm_brevo->create_entry( $this->settings, $merge_vars );

		$body = $this->last_request_body;
		$this->assertArrayHasKey( 'attributes', $body );
		$this->assertArrayHasKey( 'NOMBRE', $body['attributes'] );
		$this->assertSame( 'Jane', $body['attributes']['NOMBRE'] );
		$this->assertArrayHasKey( 'PHONE', $body['attributes'] );
	}

	/**
	 * Fields using legacy attributes|FIELDNAME prefix are correctly mapped.
	 */
	public function test_create_entry_legacy_pipe_prefix_mapped_to_attributes() {
		$merge_vars = array(
			array( 'name' => 'email',             'value' => 'contact@example.com' ),
			array( 'name' => 'attributes|CIUDAD',  'value' => 'Madrid' ),
		);
		$this->crm_brevo->create_entry( $this->settings, $merge_vars );

		$body = $this->last_request_body;
		$this->assertArrayHasKey( 'attributes', $body );
		$this->assertArrayHasKey( 'CIUDAD', $body['attributes'] );
		$this->assertSame( 'Madrid', $body['attributes']['CIUDAD'] );
	}

	/**
	 * listIds always contains the module id from settings.
	 */
	public function test_create_entry_list_id_from_settings() {
		$merge_vars = array(
			array( 'name' => 'email', 'value' => 'contact@example.com' ),
		);
		$this->crm_brevo->create_entry( $this->settings, $merge_vars );

		$body = $this->last_request_body;
		$this->assertArrayHasKey( 'listIds', $body );
		$this->assertContains( 10, $body['listIds'] );
	}

	/**
	 * updateEnabled is always sent as true.
	 */
	public function test_create_entry_update_enabled_is_true() {
		$merge_vars = array(
			array( 'name' => 'email', 'value' => 'contact@example.com' ),
		);
		$this->crm_brevo->create_entry( $this->settings, $merge_vars );

		$body = $this->last_request_body;
		$this->assertArrayHasKey( 'updateEnabled', $body );
		$this->assertTrue( $body['updateEnabled'] );
	}

	/**
	 * API error on create_entry returns error array.
	 */
	public function test_create_entry_on_api_error_returns_error() {
		$this->mock_mode = 'error';
		$merge_vars      = array(
			array( 'name' => 'email', 'value' => 'contact@example.com' ),
		);
		$result = $this->crm_brevo->create_entry( $this->settings, $merge_vars );
		$this->assertIsArray( $result );
		$this->assertSame( 'error', $result['status'] );
	}

	/**
	 * Data provider: standard fields that must go to root level.
	 *
	 * @return array
	 */
	public function standard_fields_provider() {
		return array(
			'emailBlacklisted'   => array( 'emailBlacklisted',   false ),
			'smsBlacklisted'     => array( 'smsBlacklisted',     false ),
			'updateEnabled'      => array( 'updateEnabled',      false ),
		);
	}

	/**
	 * Standard boolean fields are placed at root level, never inside attributes.
	 *
	 * @dataProvider standard_fields_provider
	 * @param string $field_name  Field name.
	 * @param mixed  $field_value Field value to send.
	 */
	public function test_create_entry_standard_field_stays_at_root( $field_name, $field_value ) {
		$merge_vars = array(
			array( 'name' => 'email',     'value' => 'contact@example.com' ),
			array( 'name' => $field_name, 'value' => $field_value ),
		);
		$this->crm_brevo->create_entry( $this->settings, $merge_vars );

		$body = $this->last_request_body;
		$this->assertArrayHasKey( $field_name, $body );
		$this->assertArrayNotHasKey( $field_name, $body['attributes'] ?? array() );
	}
}
