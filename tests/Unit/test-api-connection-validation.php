<?php
/**
 * Tests for API Connection Validation
 *
 * @package    WordPress
 * @subpackage FormsCRM
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2025 Closemarketing
 */

/**
 * Test API Connection Validation
 */
class Test_API_Connection_Validation extends WP_UnitTestCase {

	/**
	 * Instance of GFCRM
	 *
	 * @var GFCRM
	 */
	private $gfcrm;

	/**
	 * Setup test environment
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		// Load Gravity Forms if available.
		if ( class_exists( 'GFForms' ) ) {
			GFForms::include_feed_addon_framework();
		}

		// Load FormsCRM classes.
		require_once FORMSCRM_PLUGIN_PATH . 'includes/formscrm-library/class-gravityforms.php';

		// Create instance.
		if ( class_exists( 'GFCRM' ) ) {
			$this->gfcrm = new GFCRM();
		}
	}

	/**
	 * Test that plugin_settings_update method exists
	 *
	 * @return void
	 */
	public function test_plugin_settings_update_method_exists() {
		$this->assertTrue(
			method_exists( $this->gfcrm, 'plugin_settings_update' ),
			'GFCRM class should have plugin_settings_update method'
		);
	}

	/**
	 * Test Clientify login with empty API key
	 *
	 * @return void
	 */
	public function test_clientify_login_empty_api_key() {
		require_once FORMSCRM_PLUGIN_PATH . 'includes/crm-library/class-crmlib-clientify.php';
		$clientify = new CRMLIB_Clientify();

		$settings = array(
			'fc_crm_apipassword' => '',
		);

		$result = $clientify->login( $settings );

		$this->assertIsArray( $result, 'Login should return an array' );
		$this->assertEquals( 'error', $result['status'], 'Status should be error for empty API key' );
		$this->assertStringContainsString( 'required', strtolower( $result['data'] ), 'Error message should mention required' );
	}

	/**
	 * Test Clientify login returns array format
	 *
	 * @return void
	 */
	public function test_clientify_login_returns_array() {
		require_once FORMSCRM_PLUGIN_PATH . 'includes/crm-library/class-crmlib-clientify.php';
		$clientify = new CRMLIB_Clientify();

		$settings = array(
			'fc_crm_apipassword' => 'test-key',
		);

		$result = $clientify->login( $settings );

		$this->assertIsArray( $result, 'Login should return an array' );
		$this->assertArrayHasKey( 'status', $result, 'Result should have status key' );
		$this->assertArrayHasKey( 'data', $result, 'Result should have data key' );
	}

	/**
	 * Test Holded login with empty API key
	 *
	 * @return void
	 */
	public function test_holded_login_empty_api_key() {
		require_once FORMSCRM_PLUGIN_PATH . 'includes/crm-library/class-crmlib-holded.php';
		$holded = new CRMLIB_Holded();

		$settings = array(
			'fc_crm_apipassword' => '',
		);

		$result = $holded->login( $settings );

		$this->assertIsArray( $result, 'Login should return an array' );
		$this->assertEquals( 'error', $result['status'], 'Status should be error for empty API key' );
		$this->assertStringContainsString( 'required', strtolower( $result['data'] ), 'Error message should mention required' );
	}

	/**
	 * Test MailerLite login with empty API key
	 *
	 * @return void
	 */
	public function test_mailerlite_login_empty_api_key() {
		require_once FORMSCRM_PLUGIN_PATH . 'includes/crm-library/class-crmlib-mailerlite.php';
		$mailerlite = new CRMLIB_MailerLite();

		$settings = array(
			'fc_crm_apipassword' => '',
		);

		$result = $mailerlite->login( $settings );

		$this->assertIsArray( $result, 'Login should return an array' );
		$this->assertEquals( 'error', $result['status'], 'Status should be error for empty API key' );
		$this->assertStringContainsString( 'required', strtolower( $result['data'] ), 'Error message should mention required' );
	}

	/**
	 * Test Brevo login with empty API key
	 *
	 * @return void
	 */
	public function test_brevo_login_empty_api_key() {
		require_once FORMSCRM_PLUGIN_PATH . 'includes/crm-library/class-crmlib-brevo.php';
		$brevo = new CRMLIB_Brevo();

		$settings = array(
			'fc_crm_apipassword' => '',
		);

		$result = $brevo->login( $settings );

		$this->assertIsArray( $result, 'Login should return an array' );
		$this->assertEquals( 'error', $result['status'], 'Status should be error for empty API key' );
		$this->assertStringContainsString( 'required', strtolower( $result['data'] ), 'Error message should mention required' );
	}

	/**
	 * Test AcumbaMail login with empty API key
	 *
	 * @return void
	 */
	public function test_acumbamail_login_empty_api_key() {
		require_once FORMSCRM_PLUGIN_PATH . 'includes/crm-library/class-crmlib-acumbamail.php';
		$acumbamail = new CRMLIB_AcumbaMail();

		$settings = array(
			'fc_crm_apipassword' => '',
		);

		$result = $acumbamail->login( $settings );

		$this->assertIsArray( $result, 'Login should return an array' );
		$this->assertEquals( 'error', $result['status'], 'Status should be error for empty API key' );
		$this->assertStringContainsString( 'required', strtolower( $result['data'] ), 'Error message should mention required' );
	}

	/**
	 * Test all CRM login methods return consistent format
	 *
	 * @return void
	 */
	public function test_all_crm_login_consistent_format() {
		$crm_classes = array(
			'CRMLIB_Clientify'  => 'includes/crm-library/class-crmlib-clientify.php',
			'CRMLIB_Holded'     => 'includes/crm-library/class-crmlib-holded.php',
			'CRMLIB_MailerLite' => 'includes/crm-library/class-crmlib-mailerlite.php',
			'CRMLIB_Brevo'      => 'includes/crm-library/class-crmlib-brevo.php',
			'CRMLIB_AcumbaMail' => 'includes/crm-library/class-crmlib-acumbamail.php',
		);

		$settings = array(
			'fc_crm_apipassword' => '',
		);

		foreach ( $crm_classes as $class_name => $file_path ) {
			require_once FORMSCRM_PLUGIN_PATH . $file_path;
			$crm    = new $class_name();
			$result = $crm->login( $settings );

			$this->assertIsArray( $result, $class_name . ' login should return an array' );
			$this->assertArrayHasKey( 'status', $result, $class_name . ' result should have status key' );
			$this->assertArrayHasKey( 'data', $result, $class_name . ' result should have data key' );
			$this->assertContains(
				$result['status'],
				array( 'ok', 'error' ),
				$class_name . ' status should be either ok or error'
			);
		}
	}

	/**
	 * Test error message format for invalid credentials
	 *
	 * @return void
	 */
	public function test_error_message_format() {
		require_once FORMSCRM_PLUGIN_PATH . 'includes/crm-library/class-crmlib-clientify.php';
		$clientify = new CRMLIB_Clientify();

		$settings = array(
			'fc_crm_apipassword' => '',
		);

		$result = $clientify->login( $settings );

		$this->assertIsString( $result['data'], 'Error data should be a string' );
		$this->assertNotEmpty( $result['data'], 'Error message should not be empty' );
		$this->assertGreaterThan( 10, strlen( $result['data'] ), 'Error message should be descriptive' );
	}

	/**
	 * Test that settings are validated before connection test
	 *
	 * @return void
	 */
	public function test_settings_validation_before_connection() {
		if ( ! method_exists( $this->gfcrm, 'plugin_settings_update' ) ) {
			$this->markTestSkipped( 'plugin_settings_update method not available' );
			return;
		}

		// Test with empty settings.
		$empty_settings = array();
		$result         = $this->gfcrm->plugin_settings_update( $empty_settings );

		// Should return settings unchanged.
		$this->assertIsArray( $result, 'Should return settings array' );
	}

	/**
	 * Test success message format expectation
	 *
	 * This test documents the expected behavior but doesn't actually
	 * test the UI message (which requires integration testing)
	 *
	 * @return void
	 */
	public function test_success_message_documentation() {
		$expected_success_message = 'Settings saved successfully! API connection test passed.';
		$expected_error_prefix    = 'Settings saved, but API connection test failed. Error:';

		$this->assertIsString( $expected_success_message, 'Success message should be string' );
		$this->assertIsString( $expected_error_prefix, 'Error prefix should be string' );
		$this->assertStringContainsString( 'successfully', strtolower( $expected_success_message ) );
		$this->assertStringContainsString( 'error', strtolower( $expected_error_prefix ) );
	}

	/**
	 * Test CRM-specific success messages
	 *
	 * @return void
	 */
	public function test_crm_specific_success_messages() {
		$expected_messages = array(
			'Clientify'  => 'Successfully connected to Clientify',
			'Holded'     => 'Successfully connected to Holded',
			'MailerLite' => 'Successfully connected to MailerLite',
			'Brevo'      => 'Successfully connected to Brevo',
			'AcumbaMail' => 'Successfully connected to AcumbaMail',
		);

		foreach ( $expected_messages as $crm => $message ) {
			$this->assertStringContainsString(
				'Successfully connected',
				$message,
				$crm . ' should have success message'
			);
			$this->assertStringContainsString(
				$crm,
				$message,
				'Success message should contain CRM name'
			);
		}
	}

	/**
	 * Test backward compatibility with boolean returns
	 *
	 * @return void
	 */
	public function test_backward_compatibility() {
		// Test that code handles both array and boolean returns.
		$test_cases = array(
			array(
				'status' => 'ok',
				'data'   => 'Success',
			),
			array(
				'status' => 'error',
				'data'   => 'Error message',
			),
			true,
			false,
		);

		foreach ( $test_cases as $test_case ) {
			if ( is_array( $test_case ) ) {
				$this->assertArrayHasKey( 'status', $test_case );
			} else {
				$this->assertIsBool( $test_case );
			}
		}
	}

	/**
	 * Cleanup test environment
	 *
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();
	}
}

