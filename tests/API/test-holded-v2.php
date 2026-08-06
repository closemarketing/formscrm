<?php
/**
 * Class HoldedV2Tests
 *
 * Command: composer test-debug -- --filter HoldedV2Tests
 *
 * @package Formscrm
 */

/**
 * Holded (API v2) integration tests for the base CRMLIB_HOLDED class.
 *
 * CRMLIB_HOLDED supports both v1 and v2 through a single class, detecting the
 * API version from the key's shape: keys prefixed with `pat_` use v2 (Bearer
 * auth, snake_case fields, cursor pagination); anything else uses v1 (key
 * header, camelCase fields, page-based pagination). See test-holded.php for
 * v1 coverage.
 */
class HoldedV2Tests extends WP_UnitTestCase {

	/**
	 * Settings for testing (v2 key format).
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
	 * Controls which scenario the mock simulates.
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
			'fc_crm_apipassword' => 'pat_test_v2_sandbox_key',
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

		if ( 'invalid_key' === $this->mock_mode ) {
			return $this->response( 403, $this->fixture( 'holded-v2-base-error-invalid-key.json', 'body' ) );
		}

		// Create contact (POST contacts).
		if ( 'POST' === $r['method'] && false !== strpos( $url, '/contacts' ) ) {
			$this->last_request_body = json_decode( $r['body'], true );
			return $this->response( 201, $this->fixture( 'holded-v2-base-contact-create-success.json' ) );
		}

		// Login check (GET contacts?limit=1).
		if ( 'GET' === $r['method'] && false !== strpos( $url, 'limit=1' ) && false === strpos( $url, 'cursor=' ) ) {
			return $this->response( 200, $this->fixture( 'holded-v2-base-contacts-search-found.json' ) );
		}

		// search_by_email pagination (GET contacts?limit=100[&cursor=...]).
		if ( 'GET' === $r['method'] && false !== strpos( $url, '/contacts' ) ) {
			if ( 'search_not_found' === $this->mock_mode ) {
				return $this->response( 200, $this->fixture( 'holded-v2-base-contacts-empty-page.json' ) );
			}
			return $this->response( 200, $this->fixture( 'holded-v2-base-contacts-list.json' ) );
		}

		return $this->response( 500 );
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

	// -------------------------------------------------------------------------
	// API version detection.
	// -------------------------------------------------------------------------

	/**
	 * A "pat_"-prefixed key is treated as v2: login hits contacts?limit=1
	 * with Bearer auth, not the v1 key-header endpoint.
	 */
	public function test_pat_prefixed_key_uses_v2_endpoint() {
		$this->crm_holded->login( $this->settings );
		$this->assertStringContainsString( '/api/v2/contacts', $this->last_request_url );
		$this->assertStringContainsString( 'limit=1', $this->last_request_url );
	}

	// -------------------------------------------------------------------------
	// login tests.
	// -------------------------------------------------------------------------

	/**
	 * Valid v2 API key returns an ok status array with the version in the message.
	 */
	public function test_login_valid_credentials_returns_ok() {
		$result = $this->crm_holded->login( $this->settings );
		$this->assertIsArray( $result );
		$this->assertSame( 'ok', $result['status'] );
		$this->assertStringContainsString( 'v2', $result['message'] );
	}

	/**
	 * Invalid v2 API key returns an error status array.
	 */
	public function test_login_invalid_credentials_returns_error() {
		$this->mock_mode = 'invalid_key';
		$result          = $this->crm_holded->login( $this->settings );
		$this->assertIsArray( $result );
		$this->assertSame( 'error', $result['status'] );
	}

	// -------------------------------------------------------------------------
	// search_by_email tests (cursor-based pagination).
	// -------------------------------------------------------------------------

	/**
	 * Existing email returns the contact ID from the fixture.
	 */
	public function test_search_by_email_found_returns_id() {
		$id = $this->crm_holded->search_by_email( 'contacts', 'fixturecontactqa@gmail.com', $this->settings['fc_crm_apipassword'] );
		$this->assertSame( '6a6c7230bc72eb5167065075', $id );
	}

	/**
	 * Unknown email exhausts the (single, non-paginated) result set and returns false.
	 */
	public function test_search_by_email_not_found_returns_false() {
		$this->mock_mode = 'search_not_found';
		$id              = $this->crm_holded->search_by_email( 'contacts', 'doesnotexist@example.com', $this->settings['fc_crm_apipassword'] );
		$this->assertFalse( $id );
	}

	/**
	 * search_by_email uses cursor query params, not v1 page params.
	 */
	public function test_search_by_email_uses_cursor_pagination() {
		$this->crm_holded->search_by_email( 'contacts', 'fixturecontactqa@gmail.com', $this->settings['fc_crm_apipassword'] );
		$this->assertStringContainsString( 'limit=100', $this->last_request_url );
		$this->assertStringNotContainsString( 'page=', $this->last_request_url );
	}

	// -------------------------------------------------------------------------
	// create_entry tests — v2 field name translation.
	// -------------------------------------------------------------------------

	/**
	 * Successful creation returns ok with the id from the v2 response.
	 */
	public function test_create_entry_returns_ok_with_id() {
		$merge_vars = array(
			array(
				'name'  => 'name',
				'value' => 'Fixture V2 Contact',
			),
			array(
				'name'  => 'email',
				'value' => 'fixture.v2.contact@example.com',
			),
		);
		$result = $this->crm_holded->create_entry( $this->settings, $merge_vars );

		$this->assertSame( 'ok', $result['status'] );
		$this->assertSame( '6a6dc76eba9cd299e0069aeb', $result['id'] );
	}

	/**
	 * create_entry() reports the detected API version via fc_crm_name, so the
	 * entry note shows "Holded v2" instead of a bare "holded".
	 */
	public function test_create_entry_reports_v2_in_fc_crm_name() {
		$merge_vars = array(
			array(
				'name'  => 'name',
				'value' => 'Fixture V2 Contact',
			),
		);
		$result = $this->crm_holded->create_entry( $this->settings, $merge_vars );

		$this->assertSame( 'Holded v2', $result['fc_crm_name'] );
	}

	/**
	 * v1 camelCase field IDs from list_fields() (tradename, code, isperson,
	 * sepaRef, billAddress|city) are translated to v2 snake_case before
	 * being sent, since existing feeds map to the v1 names.
	 */
	public function test_create_entry_translates_v1_field_ids_to_v2_snake_case() {
		$merge_vars = array(
			array(
				'name'  => 'name',
				'value' => 'Legacy Fields Contact',
			),
			array(
				'name'  => 'tradename',
				'value' => 'Legacy Trade SL',
			),
			array(
				'name'  => 'code',
				'value' => 'B12345678',
			),
			array(
				'name'  => 'isperson',
				'value' => '1',
			),
			array(
				'name'  => 'sepaRef',
				'value' => 'REF-LEGACY',
			),
			array(
				'name'  => 'billAddress|city',
				'value' => 'Barcelona',
			),
			array(
				'name'  => 'billAddress|postalCode',
				'value' => '08001',
			),
		);
		$this->crm_holded->create_entry( $this->settings, $merge_vars );

		$body = $this->last_request_body;
		$this->assertSame( 'Legacy Trade SL', $body['trade_name'] );
		$this->assertSame( 'B12345678', $body['vat_number'] );
		$this->assertSame( '1', $body['is_person'] );
		$this->assertSame( 'REF-LEGACY', $body['sepa_ref'] );
		$this->assertSame( 'Barcelona', $body['bill_address']['city'] );
		$this->assertSame( '08001', $body['bill_address']['postal_code'] );
		// The old v1 field IDs must never be sent to the v2 API as-is.
		$this->assertArrayNotHasKey( 'tradename', $body );
		$this->assertArrayNotHasKey( 'code', $body );
		$this->assertArrayNotHasKey( 'isperson', $body );
		$this->assertArrayNotHasKey( 'sepaRef', $body );
		$this->assertArrayNotHasKey( 'billAddress', $body );
	}

	/**
	 * socialNetworks|website is flattened to a top-level "website" field in v2.
	 */
	public function test_create_entry_website_is_top_level_in_v2() {
		$merge_vars = array(
			array(
				'name'  => 'name',
				'value' => 'Website Contact',
			),
			array(
				'name'  => 'socialNetworks|website',
				'value' => 'https://example.com',
			),
		);
		$this->crm_holded->create_entry( $this->settings, $merge_vars );

		$this->assertSame( 'https://example.com', $this->last_request_body['website'] );
		$this->assertArrayNotHasKey( 'socialNetworks', $this->last_request_body );
	}
}
