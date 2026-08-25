<?php
/**
 * Class ReachTests
 *
 * Command: composer test-debug -- --filter ReachTests
 *
 * @package Formscrm
 */

/**
 * Hostinger Reach API integration tests.
 */
class ReachTests extends WP_UnitTestCase {

	/**
	 * Settings for testing.
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * API connection for testing.
	 *
	 * @var CRMLIB_Reach
	 */
	protected $crm_reach;

	/**
	 * Controls which scenario the mock simulates: 'ok', 'error', 'empty', 'paginated'.
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

		$this->settings          = array(
			'fc_crm_type'        => 'reach',
			'fc_crm_apipassword' => 'test-token-xxxxx',
			'fc_crm_module'      => '550e8400-e29b-41d4-a716-446655440000',
		);
		$this->crm_reach         = formscrm_get_api_class( 'reach' );
		$this->mock_mode         = 'ok';
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
			return $this->response( 401, file_get_contents( UNIT_TESTS_DATA_PLUGIN_DIR . 'reach-error-unauthenticated.json' ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Loads a local test fixture.
		}

		// profiles endpoint (login + list_modules).
		if ( false !== strpos( $url, '/profiles?' ) ) {
			if ( 'empty' === $this->mock_mode ) {
				return $this->response( 200, file_get_contents( UNIT_TESTS_DATA_PLUGIN_DIR . 'reach-get-profiles-empty.json' ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Loads a local test fixture.
			}
			if ( 'paginated' === $this->mock_mode ) {
				$fixture = false !== strpos( $url, 'page=2' ) ? 'reach-get-profiles-page-2.json' : 'reach-get-profiles-page-1.json';
				return $this->response( 200, file_get_contents( UNIT_TESTS_DATA_PLUGIN_DIR . $fixture ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Loads a local test fixture.
			}
			return $this->response( 200, file_get_contents( UNIT_TESTS_DATA_PLUGIN_DIR . 'reach-get-profiles.json' ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Loads a local test fixture.
		}

		// contacts POST endpoint (create_entry) — capture body for assertions.
		if ( 'POST' === $r['method'] && false !== strpos( $url, '/contacts' ) ) {
			$this->last_request_body = json_decode( $r['body'], true );
			return $this->response( 200, file_get_contents( UNIT_TESTS_DATA_PLUGIN_DIR . 'reach-create-contact-success.json' ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Loads a local test fixture.
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
	 * Valid token returns true.
	 */
	public function test_login_valid_credentials_returns_true() {
		$result = $this->crm_reach->login( $this->settings );
		$this->assertTrue( $result );
	}

	/**
	 * Invalid token returns error array.
	 */
	public function test_login_invalid_credentials_returns_error() {
		$this->mock_mode = 'error';
		$result          = $this->crm_reach->login( $this->settings );
		$this->assertIsArray( $result );
		$this->assertSame( 'error', $result['status'] );
	}

	/**
	 * Empty token returns null/falsy without making HTTP calls.
	 */
	public function test_login_empty_token_returns_falsy() {
		$this->settings['fc_crm_apipassword'] = '';
		$result                               = $this->crm_reach->login( $this->settings );
		$this->assertNotTrue( $result );
	}

	// -------------------------------------------------------------------------
	// list_modules tests.
	// -------------------------------------------------------------------------

	/**
	 * Returns an array with label/value pairs from the mock data.
	 */
	public function test_list_modules_returns_profiles() {
		$modules = $this->crm_reach->list_modules( $this->settings );
		$this->assertIsArray( $modules );
		$this->assertNotEmpty( $modules );
	}

	/**
	 * Each module entry has label and value (profile UUID).
	 */
	public function test_list_modules_entries_have_label_and_value() {
		$modules = $this->crm_reach->list_modules( $this->settings );
		foreach ( $modules as $module ) {
			$this->assertArrayHasKey( 'label', $module );
			$this->assertArrayHasKey( 'value', $module );
		}
	}

	/**
	 * Profile UUIDs and names from mock data are present in the returned modules.
	 */
	public function test_list_modules_contains_expected_profiles() {
		$modules = $this->crm_reach->list_modules( $this->settings );
		$values  = array_column( $modules, 'value' );
		$labels  = array_column( $modules, 'label' );
		$this->assertContains( '550e8400-e29b-41d4-a716-446655440000', $values );
		$this->assertContains( 'Newsletter Profile', $labels );
	}

	/**
	 * Bad credentials return an empty array.
	 */
	public function test_list_modules_on_auth_error_returns_empty_array() {
		$this->mock_mode = 'error';
		$modules         = $this->crm_reach->list_modules( $this->settings );
		$this->assertIsArray( $modules );
		$this->assertEmpty( $modules );
	}

	/**
	 * Empty profiles response returns an empty array.
	 */
	public function test_list_modules_on_empty_profiles_returns_empty_array() {
		$this->mock_mode = 'empty';
		$modules         = $this->crm_reach->list_modules( $this->settings );
		$this->assertIsArray( $modules );
		$this->assertEmpty( $modules );
	}

	/**
	 * Profiles from each API page are returned as modules.
	 */
	public function test_list_modules_returns_all_paginated_profiles() {
		$this->mock_mode = 'paginated';
		$modules         = $this->crm_reach->list_modules( $this->settings );
		$labels          = array_column( $modules, 'label' );

		$this->assertCount( 2, $modules );
		$this->assertContains( 'Newsletter ES', $labels );
		$this->assertContains( 'Newsletter EN', $labels );
	}

	// -------------------------------------------------------------------------
	// list_fields tests.
	// -------------------------------------------------------------------------

	/**
	 * Returns the fixed field map.
	 */
	public function test_list_fields_returns_fixed_fields() {
		$fields = $this->crm_reach->list_fields( $this->settings, '' );
		$names  = array_column( $fields, 'name' );
		$this->assertSame( array( 'email', 'name', 'surname', 'phone', 'note' ), $names );
	}

	/**
	 * Email is marked as required.
	 */
	public function test_list_fields_email_is_required() {
		$fields = $this->crm_reach->list_fields( $this->settings, '' );
		foreach ( $fields as $field ) {
			if ( 'email' === $field['name'] ) {
				$this->assertTrue( $field['required'] );
			}
		}
	}

	// -------------------------------------------------------------------------
	// create_entry tests.
	// -------------------------------------------------------------------------

	/**
	 * Successful create_entry returns status ok and an empty ID when the API returns none.
	 */
	public function test_create_entry_returns_ok() {
		$merge_vars = array(
			array(
				'name'  => 'email',
				'value' => 'contact@example.com',
			),
			array(
				'name'  => 'name',
				'value' => 'Jane',
			),
		);
		$result     = $this->crm_reach->create_entry( $this->settings, $merge_vars );
		$this->assertIsArray( $result );
		$this->assertSame( 'ok', $result['status'] );
		$this->assertSame( '', $result['id'] );
	}

	/**
	 * Request body only contains recognized Reach contact fields.
	 */
	public function test_create_entry_body_contains_only_known_fields() {
		$merge_vars = array(
			array(
				'name'  => 'email',
				'value' => 'contact@example.com',
			),
			array(
				'name'  => 'surname',
				'value' => 'Doe',
			),
			array(
				'name'  => 'phone',
				'value' => '+14155552671',
			),
			array(
				'name'  => 'note',
				'value' => 'VIP customer',
			),
			array(
				'name'  => 'unknown_field',
				'value' => 'should be dropped',
			),
		);
		$this->crm_reach->create_entry( $this->settings, $merge_vars );

		$body = $this->last_request_body;
		$this->assertNotNull( $body );
		$this->assertSame( 'contact@example.com', $body['email'] );
		$this->assertSame( 'Doe', $body['surname'] );
		$this->assertSame( '+14155552671', $body['phone'] );
		$this->assertSame( 'VIP customer', $body['note'] );
		$this->assertArrayNotHasKey( 'unknown_field', $body );
	}

	/**
	 * Missing email returns an error without calling the API.
	 */
	public function test_create_entry_without_email_returns_error() {
		$merge_vars = array(
			array(
				'name'  => 'name',
				'value' => 'Jane',
			),
		);
		$result     = $this->crm_reach->create_entry( $this->settings, $merge_vars );
		$this->assertSame( 'error', $result['status'] );
		$this->assertNull( $this->last_request_body );
	}

	/**
	 * Missing profile (module) returns an error without calling the API.
	 */
	public function test_create_entry_without_profile_returns_error() {
		$this->settings['fc_crm_module'] = '';
		$merge_vars                      = array(
			array(
				'name'  => 'email',
				'value' => 'contact@example.com',
			),
		);
		$result                          = $this->crm_reach->create_entry( $this->settings, $merge_vars );
		$this->assertSame( 'error', $result['status'] );
		$this->assertNull( $this->last_request_body );
	}

	/**
	 * API error on create_entry returns error array.
	 */
	public function test_create_entry_on_api_error_returns_error() {
		$this->mock_mode = 'error';
		$merge_vars      = array(
			array(
				'name'  => 'email',
				'value' => 'contact@example.com',
			),
		);
		$result          = $this->crm_reach->create_entry( $this->settings, $merge_vars );
		$this->assertIsArray( $result );
		$this->assertSame( 'error', $result['status'] );
	}
}
