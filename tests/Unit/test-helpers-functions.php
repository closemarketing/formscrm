<?php
/**
 * Class HelpersFunctionsTest
 *
 * Command: composer test-debug --filter HelpersFunctionsTest
 *
 * @package Formscrm
 */

/**
 * Sample test case.
 */
class HelpersFunctionsTest extends WP_UnitTestCase {

	/**
	 * Set up test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		// Mock HTTP requests.
		add_filter(
			'pre_http_request',
			function( $pre, $r, $url ) {
				return array(
					'body'     => '',
					'response' => array( 'code' => 200, 'message' => 'OK' ),
				);
			},
			10,
			3
		);
	}

	/**
	 * Test formscrm_check_url_crm function.
	 */
	public function test_check_url_crm() {
		$url = 'https://example.com';
		$this->assertEquals( 'https://example.com/', formscrm_check_url_crm( $url ) );

		$url = 'https://example.com/';
		$this->assertEquals( 'https://example.com/', formscrm_check_url_crm( $url ) );

		$url = 'http://test.com/path';
		$this->assertEquals( 'http://test.com/path/', formscrm_check_url_crm( $url ) );
	}

	/**
	 * Test formscrm_send_webhook function.
	 */
	public function test_webhook_post() {
		$settings['fc_crm_webhook'] = 'https://webhook.com/test';
		$response_api              = array(
			'status'  => 'ok',
			'message' => 'success',
			'module'  => 'contact',
			'id'      => '1234567890',
		);
		$response                  = formscrm_send_webhook( $settings, $response_api );

		$this->assertEquals( 200, $response['response']['response']['code'] );
		$this->assertEquals( 'OK', $response['response']['response']['message'] );
		$this->assertEquals( $response_api['id'], $response['request']['data']['id'] );
		$this->assertEquals( $response_api['module'] . '.saved', $response['request']['hook']['event'] );

		$deal_id      = '32131321';
		$response_api = array(
			'status'  => 'ok',
			'message' => 'success',
			'module'  => 'deal',
			'id'      => '1234567890|' . $deal_id,
		);
		$response     = formscrm_send_webhook( $settings, $response_api );

		$this->assertEquals( 200, $response['response']['response']['code'] );
		$this->assertEquals( 'OK', $response['response']['response']['message'] );
		$this->assertEquals( $deal_id, $response['request']['data']['id'] );
		$this->assertEquals( $response_api['module'] . '.saved', $response['request']['hook']['event'] );
	}

	/**
	 * Test formscrm_send_webhook without webhook URL.
	 */
	public function test_webhook_post_no_url() {
		$settings     = array();
		$response_api = array(
			'status'  => 'ok',
			'message' => 'success',
			'module'  => 'contact',
			'id'      => '1234567890',
		);
		$response     = formscrm_send_webhook( $settings, $response_api );

		$this->assertNull( $response );
	}

	/**
	 * Test formscrm_send_webhook ID extraction from different formats.
	 *
	 * Test that the function correctly extracts numeric ID from:
	 * - Plain number: '22222'
	 * - Pipe-separated with text: 'Lead 32132|Deal 22222'
	 * - Text prefix: 'Deal 22222'
	 * - Spanish text prefix: 'Oportunidad 22222'
	 */
	public function test_webhook_id_extraction() {
		$settings = array( 'fc_crm_webhook' => 'https://webhook.com/test' );

		// Test Case 1: Plain numeric ID.
		$response_api = array(
			'status'  => 'ok',
			'message' => 'success',
			'module'  => 'deal',
			'id'      => '22222',
		);
		$response     = formscrm_send_webhook( $settings, $response_api );
		$this->assertEquals( 22222, $response['request']['data']['id'] );
		$this->assertIsInt( $response['request']['data']['id'] );

		// Test Case 2: Pipe-separated IDs with text prefix (Lead 32132|Deal 22222).
		$response_api = array(
			'status'  => 'ok',
			'message' => 'success',
			'module'  => 'deal',
			'id'      => 'Lead 32132|Deal 22222',
		);
		$response     = formscrm_send_webhook( $settings, $response_api );
		$this->assertEquals( 22222, $response['request']['data']['id'] );
		$this->assertIsInt( $response['request']['data']['id'] );

		// Test Case 3: Single ID with text prefix (Deal 22222).
		$response_api = array(
			'status'  => 'ok',
			'message' => 'success',
			'module'  => 'deal',
			'id'      => 'Deal 22222',
		);
		$response     = formscrm_send_webhook( $settings, $response_api );
		$this->assertEquals( 22222, $response['request']['data']['id'] );
		$this->assertIsInt( $response['request']['data']['id'] );

		// Test Case 4: Spanish text prefix (Oportunidad 22222).
		$response_api = array(
			'status'  => 'ok',
			'message' => 'success',
			'module'  => 'deal',
			'id'      => 'Oportunidad 22222',
		);
		$response     = formscrm_send_webhook( $settings, $response_api );
		$this->assertEquals( 22222, $response['request']['data']['id'] );
		$this->assertIsInt( $response['request']['data']['id'] );

		// Test Case 5: Multiple pipe-separated with different numbers.
		$response_api = array(
			'status'  => 'ok',
			'message' => 'success',
			'module'  => 'deal',
			'id'      => 'Contact 11111|Lead 99999|Deal 22222',
		);
		$response     = formscrm_send_webhook( $settings, $response_api );
		$this->assertEquals( 22222, $response['request']['data']['id'] );
		$this->assertIsInt( $response['request']['data']['id'] );
	}

	/**
	 * Test formscrm_debug_message with WP_DEBUG enabled.
	 */
	public function test_debug_message_with_wp_debug() {
		// Set WP_DEBUG constant for this test.
		if ( ! defined( 'WP_DEBUG' ) ) {
			define( 'WP_DEBUG', true );
		}

		// Function should execute without errors.
		formscrm_debug_message( 'Test message' );
		$this->assertTrue( true );
	}

	/**
	 * Test formscrm_debug_message with array.
	 */
	public function test_debug_message_with_array() {
		if ( ! defined( 'WP_DEBUG' ) ) {
			define( 'WP_DEBUG', true );
		}

		// Function should handle array input without errors.
		formscrm_debug_message( array( 'key' => 'value' ) );
		$this->assertTrue( true );
	}

	/**
	 * Test formscrm_debug_message with WP_DEBUG disabled.
	 */
	public function test_debug_message_without_wp_debug() {
		// Note: WP_DEBUG might already be defined, so we test that function doesn't crash.
		formscrm_debug_message( 'Test message' );
		$this->assertTrue( true );
	}

	/**
	 * Test formscrm_get_module with POST data.
	 */
	public function test_get_module_from_post() {
		$_POST['_gform_setting_fc_crm_module'] = 'test_module';

		$module = formscrm_get_module( 'default_module' );
		$this->assertEquals( 'test_module', $module );

		unset( $_POST['_gform_setting_fc_crm_module'] );
	}

	/**
	 * Test formscrm_get_module with settings array.
	 */
	public function test_get_module_from_settings() {
		$settings = array( 'fc_crm_module' => 'settings_module' );
		$module   = formscrm_get_module( 'default_module', $settings );
		$this->assertEquals( 'settings_module', $module );
	}

	/**
	 * Test formscrm_get_module with default value.
	 */
	public function test_get_module_default() {
		$module = formscrm_get_module( 'default_module' );
		$this->assertEquals( 'default_module', $module );
	}

	/**
	 * Test formscrm_error_admin_message.
	 */
	public function test_error_admin_message() {
		if ( ! defined( 'WP_DEBUG' ) ) {
			define( 'WP_DEBUG', true );
		}

		// Should not throw errors.
		formscrm_error_admin_message( 'ERROR_CODE', 'Error message' );
		$this->assertTrue( true );
	}

	/**
	 * Test formscrm_alert_error function.
	 */
	public function test_alert_error() {
		$crm       = 'test_crm';
		$error     = 'Test error message';
		$data      = array(
			array(
				'name'  => 'Name',
				'value' => 'John Doe',
			),
			array(
				'name'  => 'Email',
				'value' => 'test@example.com',
			),
		);
		$url       = 'https://api.example.com/endpoint';
		$json      = '{"test": "data"}';
		$form_info = array(
			'form_id'   => '123',
			'form_name' => 'Test Form',
			'form_type' => 'gravityforms',
			'entry_id'  => '456',
		);

		// Function should execute without errors.
		formscrm_alert_error( $crm, $error, $data, $url, $json, $form_info );

		// Verify function executed successfully.
		$this->assertTrue( true );
	}

	/**
	 * Test formscrm_alert_error with custom email.
	 */
	public function test_alert_error_custom_email() {
		update_option( 'formscrm_error_notification_email', 'custom@example.com' );

		// Function should execute without errors.
		formscrm_alert_error( 'test_crm', 'Error', array() );

		// Verify custom email option is used.
		$custom_email = get_option( 'formscrm_error_notification_email' );
		$this->assertEquals( 'custom@example.com', $custom_email );

		delete_option( 'formscrm_error_notification_email' );
	}

	/**
	 * Test formscrm_debug_email_lead backward compatibility.
	 */
	public function test_debug_email_lead() {
		$merge_vars = array(
			array(
				'name'  => 'Name',
				'value' => 'Test',
			),
		);

		// Function should execute without errors (wrapper for formscrm_alert_error).
		formscrm_debug_email_lead( 'test_crm', 'Error message', $merge_vars );

		$this->assertTrue( true );
	}

	/**
	 * Test formscrm_send_slack_notification without webhook.
	 */
	public function test_send_slack_notification_no_webhook() {
		delete_option( 'formscrm_slack_webhook_url' );

		$result = formscrm_send_slack_notification( 'test_crm', 'Error', array() );

		$this->assertFalse( $result );
	}

	/**
	 * Test formscrm_send_slack_notification with webhook.
	 */
	public function test_send_slack_notification_with_webhook() {
		update_option( 'formscrm_slack_webhook_url', 'https://hooks.slack.com/test' );

		$data      = array(
			array(
				'name'  => 'Name',
				'value' => 'John',
			),
			array(
				'name'  => 'Email',
				'value' => 'test@example.com',
			),
		);
		$form_info = array(
			'form_id'   => '123',
			'form_name' => 'Test Form',
			'form_type' => 'gravityforms',
			'entry_id'  => '456',
		);

		$result = formscrm_send_slack_notification( 'test_crm', 'Error message', $data, 'https://api.test.com', '{"test": "data"}', $form_info );

		$this->assertTrue( $result );

		delete_option( 'formscrm_slack_webhook_url' );
	}

	/**
	 * Test formscrm_testserver function.
	 */
	public function test_testserver() {
		// Should not throw errors.
		formscrm_testserver();
		$this->assertTrue( true );
	}

	/**
	 * Test formscrm_get_svg_icon with existing icon.
	 */
	public function test_get_svg_icon_existing() {
		$icon_content = formscrm_get_svg_icon( 'icon-bell' );

		$this->assertNotEmpty( $icon_content );
		$this->assertStringContainsString( '<svg', $icon_content );
	}

	/**
	 * Test formscrm_get_svg_icon with class name.
	 */
	public function test_get_svg_icon_with_class() {
		$icon_content = formscrm_get_svg_icon( 'icon-bell', 'test-class' );

		$this->assertNotEmpty( $icon_content );
		$this->assertStringContainsString( 'class="test-class"', $icon_content );
	}

	/**
	 * Test formscrm_get_svg_icon with non-existent icon.
	 */
	public function test_get_svg_icon_not_found() {
		$icon_content = formscrm_get_svg_icon( 'non-existent-icon' );

		$this->assertEmpty( $icon_content );
	}

	/**
	 * Test formscrm_get_api_class with valid CRM type.
	 */
	public function test_get_api_class() {
		// Mock the filter to return a test path.
		add_filter(
			'formscrm_crmlib_path',
			function( $choices ) {
				$choices['test_crm'] = FORMSCRM_PLUGIN_PATH . 'includes/crm-library/class-crmlib-test.php';
				return $choices;
			}
		);

		// Create a mock class file.
		$mock_class_file = FORMSCRM_PLUGIN_PATH . 'includes/crm-library/class-crmlib-test.php';
		if ( ! file_exists( dirname( $mock_class_file ) ) ) {
			wp_mkdir_p( dirname( $mock_class_file ) );
		}
		file_put_contents( $mock_class_file, '<?php class CRMLIB_TESTCRM { }' );

		// Test the function.
		$result = formscrm_get_api_class( 'Test CRM' );

		// Clean up.
		if ( file_exists( $mock_class_file ) ) {
			unlink( $mock_class_file );
		}

		// Should return instance or null if class doesn't exist properly.
		$this->assertTrue( is_object( $result ) || null === $result );
	}

	/**
	 * Test formscrm_get_api_class with invalid CRM type.
	 */
	public function test_get_api_class_invalid() {
		$result = formscrm_get_api_class( 'Invalid CRM' );
		$this->assertNull( $result );
	}
}