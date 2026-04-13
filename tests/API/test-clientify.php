<?php
/**
 * Class ClientifyTests
 * 
 * Command: composer test-debug --filter ClientifyTests
 *
 * @package Formscrm
 */

/**
 * Sample test case.
 */
class ClientifyTests extends WP_UnitTestCase {

	/**
	 * Settings for testing
	 */
	protected $settings;
	
	/**
	 * API connection for testing
	 */
	protected $crm_clientify;
	
	/**
	 * Set up test environment.
	 */
	public function setUp(): void {
		parent::setUp();
		
		$this->settings = [
			'fc_crm_type' => 'clientify',
			'fc_crm_apipassword' => 'api-password',
			'fc_crm_module' => 'Contacts',
		];
		$this->crm_clientify = formscrm_get_api_class( 'clientify' );
		
		// Mock the HTTP request to return a successful response.
		add_filter(
			'pre_http_request',
			function( $pre, $r, $url ) {
				$body_query    = ! empty( $r['body'] ) ? json_decode( $r['body'], true ) : array();
				$response_file = 'clientify-';

				// Login v2.
				if ( str_contains( $url, 'api-plus.clientify.com' ) && str_contains( $url, 'me/' ) ) {
					$response_file .= 'v2-login.json';
				}
				// Login v1 fallback.
				if ( str_contains( $url, 'settings/my-account/' ) ) {
					$response_file .= 'login.json';
				}
				if ( str_contains( $url, 'custom-fields/' ) ) {
					$response_file .= str_contains( $url, 'api-plus.clientify.com' ) ? 'v2-custom-fields.json' : 'custom-fields.json';
				}

				$response_file = UNIT_TESTS_DATA_PLUGIN_DIR . $response_file;
				if ( file_exists( $response_file ) ) {
					return array(
						'body' => file_get_contents( $response_file ),
						'response' => array( 'code' => 200, 'message' => 'OK' ),
					);
				}

				return array(
					'body' => '',
					'response' => array( 'code' => 500, 'message' => 'Error API' ),
				);
			},
		10, 3 );
	}

	public function test_login_without_errors() {
		$login = $this->crm_clientify->login( $this->settings );
		$this->assertIsArray( $login );
		$this->assertSame( 'ok', $login['status'] );
	}

	public function test_login_with_errors() {
		$this->settings['fc_crm_apipassword'] = '';
		$login = $this->crm_clientify->login( $this->settings );
		$this->assertIsArray( $login );
		$this->assertSame( 'error', $login['status'] );
	}

	public function test_list_modules_without_errors() {
		$modules = $this->crm_clientify->list_modules( $this->settings );
		$this->assertTrue( is_array( $modules ) );
	}

	public function test_list_fields_without_errors() {
		$fields = $this->crm_clientify->list_fields( $this->settings, 'Contacts' );

		$this->assertTrue( is_array( $fields ) );
		$fields = array_column( $fields, 'name' );
		$this->assertTrue( in_array( 'first_name', $fields ) );
		$this->assertTrue( in_array( 'last_name', $fields ) );
		$this->assertTrue( in_array( 'email', $fields ) );
		$this->assertTrue( in_array( 'phone', $fields ) );
		$this->assertTrue( in_array( 'company', $fields ) );
		$this->assertTrue( in_array( 'custom_fields|interes2', $fields ) );
		$this->assertTrue( in_array( 'websites|corporate', $fields ) );
		$this->assertTrue( in_array( 'websites|personal', $fields ) );
		$this->assertTrue( in_array( 'custom_fields|verified', $fields ) );
		$this->assertTrue( in_array( 'custom_fields|social_lead_1', $fields ) );
	}
}
