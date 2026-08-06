<?php
/**
 * Class HoldedTests
 *
 * Command: composer test-debug -- --filter HoldedTests
 *
 * @package Formscrm
 */

/**
 * Holded (API v1) integration tests.
 */
class HoldedTests extends WP_UnitTestCase {

	/**
	 * Settings for testing.
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * API connection for testing.
	 *
	 * @var CRMLIB_HOLDED
	 */
	protected $crm_holded;

	/**
	 * Controls which scenario the mock simulates: 'ok', 'error'.
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
	 * Last URL requested (for endpoint assertions).
	 *
	 * @var string|null
	 */
	protected $last_request_url = null;

	/**
	 * Set up test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->settings = array(
			'fc_crm_type'        => 'holded',
			'fc_crm_apipassword' => 'test-api-key-xxxxx',
			'fc_crm_module'      => 'contacts',
		);
		$this->crm_holded         = formscrm_get_api_class( 'holded' );
		$this->mock_mode          = 'ok';
		$this->last_request_body  = null;
		$this->last_request_url   = null;

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
		$this->last_request_url = $url;

		if ( 'error' === $this->mock_mode ) {
			return $this->response( 400, $this->fixture( 'holded-v1-error-invalid-key.json', 'body' ) );
		}

		// contacts POST endpoint (create_entry) — capture body for assertions.
		// The v1 class passes $bodypost as a raw PHP array (not JSON-encoded),
		// so pre_http_request intercepts it before WP serializes it.
		if ( 'POST' === $r['method'] && false !== strpos( $url, '/contacts' ) ) {
			$this->last_request_body = $r['body'];
			return $this->response( 201, file_get_contents( UNIT_TESTS_DATA_PLUGIN_DIR . 'holded-v1-contact-create-success.json' ) );
		}

		// contacts GET endpoint (login + search_by_email + list_fields context).
		if ( 'GET' === $r['method'] && false !== strpos( $url, '/contacts' ) ) {
			return $this->response( 200, file_get_contents( UNIT_TESTS_DATA_PLUGIN_DIR . 'holded-v1-contacts-list.json' ) );
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
				'message' => $code >= 200 && $code < 300 ? 'OK' : 'Error',
			),
		);
	}

	/**
	 * Loads a fixture file's raw JSON, optionally the "body" sub-key (for error fixtures).
	 *
	 * @param string      $filename Fixture filename inside tests/Data/.
	 * @param string|null $key      Optional top-level key to extract and re-encode.
	 * @return string Raw JSON string.
	 */
	private function fixture( $filename, $key = null ) {
		$contents = file_get_contents( UNIT_TESTS_DATA_PLUGIN_DIR . $filename );
		if ( null === $key ) {
			return $contents;
		}
		$decoded = json_decode( $contents, true );
		return wp_json_encode( $decoded[ $key ] );
	}

	// -------------------------------------------------------------------------
	// login tests.
	// -------------------------------------------------------------------------

	/**
	 * Valid API key returns an ok status array.
	 */
	public function test_login_valid_credentials_returns_ok() {
		$result = $this->crm_holded->login( $this->settings );
		$this->assertIsArray( $result );
		$this->assertSame( 'ok', $result['status'] );
	}

	/**
	 * login() reports the detected version via crm_name, used by the
	 * "API Connection Status" badge to show "Holded v1" instead of "Holded".
	 */
	public function test_login_reports_v1_in_crm_name() {
		$result = $this->crm_holded->login( $this->settings );
		$this->assertSame( 'Holded v1', $result['crm_name'] );
	}

	/**
	 * Invalid API key returns an error status array.
	 */
	public function test_login_invalid_credentials_returns_error() {
		$this->mock_mode = 'error';
		$result          = $this->crm_holded->login( $this->settings );
		$this->assertIsArray( $result );
		$this->assertSame( 'error', $result['status'] );
	}

	/**
	 * Empty API key returns an error status array without making HTTP calls.
	 */
	public function test_login_empty_apikey_returns_error() {
		$this->settings['fc_crm_apipassword'] = '';
		$result = $this->crm_holded->login( $this->settings );
		$this->assertIsArray( $result );
		$this->assertSame( 'error', $result['status'] );
		$this->assertNull( $this->last_request_url );
	}

	// -------------------------------------------------------------------------
	// list_modules tests.
	// -------------------------------------------------------------------------

	/**
	 * Returns a single "contacts" module — Holded v1 only supports contacts.
	 */
	public function test_list_modules_returns_contacts_only() {
		$modules = $this->crm_holded->list_modules( $this->settings );
		$this->assertIsArray( $modules );
		$this->assertCount( 1, $modules );
		$this->assertSame( 'contacts', $modules[0]['value'] );
	}

	// -------------------------------------------------------------------------
	// list_fields tests.
	// -------------------------------------------------------------------------

	/**
	 * Returns a non-empty array of fields for the contacts module.
	 */
	public function test_list_fields_contacts_returns_fields() {
		$fields = $this->crm_holded->list_fields( $this->settings, 'contacts' );
		$this->assertIsArray( $fields );
		$this->assertNotEmpty( $fields );
	}

	/**
	 * Core static contact fields are present.
	 */
	public function test_list_fields_contains_expected_names() {
		$fields = $this->crm_holded->list_fields( $this->settings, 'contacts' );
		$names  = array_column( $fields, 'name' );
		$this->assertContains( 'name', $names );
		$this->assertContains( 'email', $names );
		$this->assertContains( 'tradename', $names );
		$this->assertContains( 'billAddress|city', $names );
	}

	/**
	 * An unknown module returns an empty array.
	 */
	public function test_list_fields_unknown_module_returns_empty_array() {
		$fields = $this->crm_holded->list_fields( $this->settings, 'unknown-module' );
		$this->assertIsArray( $fields );
		$this->assertEmpty( $fields );
	}

	// -------------------------------------------------------------------------
	// search_by_email tests.
	// -------------------------------------------------------------------------

	/**
	 * Existing email returns the matching contact ID from the fixture.
	 */
	public function test_search_by_email_found_returns_id() {
		$id = $this->crm_holded->search_by_email( 'contacts', 'fixturecontactqa@gmail.com', $this->settings['fc_crm_apipassword'] );
		$this->assertSame( '6a6c7230bc72eb5167065075', $id );
	}

	/**
	 * Unknown email returns false — no contact in the fixture matches, and the
	 * fixture has fewer than FORMSCRM_MAX_LIMIT_HOLDED_API items so it stops
	 * after a single page.
	 */
	public function test_search_by_email_not_found_returns_false() {
		$id = $this->crm_holded->search_by_email( 'contacts', 'doesnotexist@example.com', $this->settings['fc_crm_apipassword'] );
		$this->assertFalse( $id );
	}

	/**
	 * An API error while searching returns false.
	 */
	public function test_search_by_email_api_error_returns_false() {
		$this->mock_mode = 'error';
		$id              = $this->crm_holded->search_by_email( 'contacts', 'fixturecontactqa@gmail.com', $this->settings['fc_crm_apipassword'] );
		$this->assertFalse( $id );
	}

	// -------------------------------------------------------------------------
	// create_entry tests.
	// -------------------------------------------------------------------------

	/**
	 * Successful creation returns ok with the id from the v1 response.
	 */
	public function test_create_entry_returns_ok_with_id() {
		$merge_vars = array(
			array(
				'name'  => 'name',
				'value' => 'Fixture V1 Contact',
			),
			array(
				'name'  => 'email',
				'value' => 'fixture.v1.contact@example.com',
			),
		);
		$result = $this->crm_holded->create_entry( $this->settings, $merge_vars );

		$this->assertIsArray( $result );
		$this->assertSame( 'ok', $result['status'] );
		$this->assertSame( '6a6dbf9f2c0eaed0fb0df1d7', $result['id'] );
	}

	/**
	 * create_entry() reports the detected API version via fc_crm_name, so the
	 * entry note shows "Holded v1" instead of a bare "holded".
	 */
	public function test_create_entry_reports_v1_in_fc_crm_name() {
		$merge_vars = array(
			array(
				'name'  => 'name',
				'value' => 'Fixture V1 Contact',
			),
		);
		$result = $this->crm_holded->create_entry( $this->settings, $merge_vars );

		$this->assertSame( 'Holded v1', $result['fc_crm_name'] );
	}

	/**
	 * A failed creation returns an error status with the API's message.
	 */
	public function test_create_entry_on_error_returns_error_status() {
		$this->mock_mode = 'error';
		$merge_vars      = array(
			array(
				'name'  => 'name',
				'value' => 'Fixture V1 Contact',
			),
		);
		$result = $this->crm_holded->create_entry( $this->settings, $merge_vars );

		$this->assertSame( 'error', $result['status'] );
		$this->assertArrayNotHasKey( 'id', $result );
	}

	/**
	 * Pipe-separated field names (e.g. billAddress|city) are nested in the POST body.
	 */
	public function test_create_entry_nested_bill_address_fields() {
		$merge_vars = array(
			array(
				'name'  => 'name',
				'value' => 'Nested Fields Contact',
			),
			array(
				'name'  => 'billAddress|city',
				'value' => 'Madrid',
			),
			array(
				'name'  => 'billAddress|postalCode',
				'value' => '28001',
			),
		);
		$this->crm_holded->create_entry( $this->settings, $merge_vars );

		$this->assertArrayHasKey( 'billAddress', $this->last_request_body );
		$this->assertSame( 'Madrid', $this->last_request_body['billAddress']['city'] );
		$this->assertSame( '28001', $this->last_request_body['billAddress']['postalCode'] );
	}

	/**
	 * The tags field is split into an array from a comma-separated string.
	 */
	public function test_create_entry_tags_split_into_array() {
		$merge_vars = array(
			array(
				'name'  => 'name',
				'value' => 'Tags Contact',
			),
			array(
				'name'  => 'tags',
				'value' => 'lead,webinar,2026',
			),
		);
		$this->crm_holded->create_entry( $this->settings, $merge_vars );

		$this->assertSame( array( 'lead', 'webinar', '2026' ), $this->last_request_body['tags'] );
	}
}
