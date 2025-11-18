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
				$response_file = 'clientify-' . strtolower( $r['method'] ) . '-';

				// Login.
				if ( false !== strpos( $url, 'settings/my-account/' ) ) {
					$response_file .= 'login.json';
				} elseif ( false !== strpos( $url, 'custom-fields/' ) ) {
					$response_file .= 'custom-fields.json';
				} elseif ( false !== strpos( $url, '/contacts' ) ) {
					$response_file .= 'contacts.json';
				} elseif ( 'https://webhook.com/test' === $url ) {
					$response_file .= 'webhook.json';
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
		$this->assertTrue( $login );
	}

	public function test_login_with_errors() {
		$this->settings['fc_crm_apipassword'] = '';
		$login = $this->crm_clientify->login( $this->settings );
		$this->assertFalse( $login );
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

	public function test_create_contact_without_errors() {
		$merge_vars = array(
			array( 'name' => 'first_name', 'value' => 'David' ),
			array( 'name' => 'last_name', 'value' => 'Close' ),
			array( 'name' => 'email', 'value' => 'david@close.marketing' ),
			array( 'name' => 'phone', 'value' => '1234567890' ),
			array( 'name' => 'custom_fields|interes_categoria', 'value' => '2.000' ),
			array( 'name' => 'custom_fields|interes2', 'value' => 'De 3 a 6 meses' ),
			array( 'name' => 'custom_fields|info_chat', 'value' => 'Autónomo' ),
		);
		$contact = $this->crm_clientify->create_entry( $this->settings, $merge_vars );
		$this->assertTrue( $contact['status'] === 'ok' );
		$this->assertIsInt( $contact['id'] );
		$this->assertEquals( 'contact', $contact['module'] );

		// Webhook.
		$this->settings['fc_crm_webhook'] = 'https://webhook.com/test';
		$res_webhook = formscrm_send_webhook( $this->settings, $contact );
		$this->assertTrue( $res_webhook['response']['response']['message'] === 'OK' );
		$request_hook = $res_webhook['request']['hook'];
		$this->assertEquals( 'contact.saved', $request_hook['event'] );
		$this->assertEquals( 'https://webhook.com/test', $request_hook['target'] );
		$request_data = $res_webhook['request']['data'];
		$this->assertEquals( $contact['id'], $request_data['id'] );
	}
}
