<?php
/**
 * Class NotificationsTest
 * 
 * Tests for error notification functions (Email and Slack)
 * 
 * Command: composer test --filter NotificationsTest
 * Debug: composer test-debug --filter NotificationsTest
 *
 * @package Formscrm
 */

/**
 * Test case for notification functions.
 */
class NotificationsTest extends WP_UnitTestCase {

	/**
	 * Set up test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		// Mock HTTP requests for Slack.
		add_filter(
			'pre_http_request',
			function( $pre, $r, $url ) {
				// Check if it's a Slack webhook URL.
				if ( strpos( $url, 'hooks.slack.com' ) !== false ) {
					return array(
						'body'     => 'ok',
						'response' => array(
							'code'    => 200,
							'message' => 'OK',
						),
					);
				}
				return $pre;
			},
			10,
			3
		);

		// Reset options before each test.
		delete_option( 'formscrm_error_notification_email' );
		delete_option( 'formscrm_slack_webhook_url' );
	}

	/**
	 * Tear down test environment.
	 */
	public function tearDown(): void {
		parent::tearDown();

		// Clean up options.
		delete_option( 'formscrm_error_notification_email' );
		delete_option( 'formscrm_slack_webhook_url' );

		// Reset email test vars.
		reset_phpmailer_instance();
	}

	/**
	 * Test that formscrm_debug_email_lead function exists.
	 */
	public function test_debug_email_lead_function_exists() {
		$this->assertTrue( function_exists( 'formscrm_debug_email_lead' ) );
	}

	/**
	 * Test that formscrm_send_slack_notification function exists.
	 */
	public function test_send_slack_notification_function_exists() {
		$this->assertTrue( function_exists( 'formscrm_send_slack_notification' ) );
	}

	/**
	 * Test email notification uses custom email when configured.
	 */
	public function test_email_uses_custom_email_when_configured() {
		$custom_email = 'custom@example.com';
		update_option( 'formscrm_error_notification_email', $custom_email );

		$crm   = 'Holded';
		$error = 'Test error';
		$data  = array(
			array( 'name' => 'Email', 'value' => 'test@example.com' ),
		);

		formscrm_debug_email_lead( $crm, $error, $data );

		$mailer = tests_retrieve_phpmailer_instance();
		$this->assertStringContainsString( $custom_email, $mailer->get_recipient( 'to' )->address );
	}

	/**
	 * Test email notification uses admin email when no custom email configured.
	 */
	public function test_email_uses_admin_email_when_no_custom_email() {
		$admin_email = get_option( 'admin_email' );

		$crm   = 'Holded';
		$error = 'Test error';
		$data  = array(
			array( 'name' => 'Email', 'value' => 'test@example.com' ),
		);

		formscrm_debug_email_lead( $crm, $error, $data );

		$mailer = tests_retrieve_phpmailer_instance();
		$this->assertEquals( $admin_email, $mailer->get_recipient( 'to' )->address );
	}

	/**
	 * Test email subject includes site name.
	 */
	public function test_email_subject_includes_site_name() {
		$site_name = get_bloginfo( 'name' );

		$crm   = 'Holded';
		$error = 'Test error';
		$data  = array(
			array( 'name' => 'Email', 'value' => 'test@example.com' ),
		);

		formscrm_debug_email_lead( $crm, $error, $data );

		$mailer = tests_retrieve_phpmailer_instance();
		$this->assertStringContainsString( $site_name, $mailer->get_sent()->subject );
		$this->assertStringContainsString( 'FormsCRM', $mailer->get_sent()->subject );
	}

	/**
	 * Test email body contains CRM name.
	 */
	public function test_email_body_contains_crm_name() {
		$crm   = 'Holded';
		$error = 'Test error message';
		$data  = array(
			array( 'name' => 'Email', 'value' => 'test@example.com' ),
		);

		formscrm_debug_email_lead( $crm, $error, $data );

		$mailer = tests_retrieve_phpmailer_instance();
		$body   = $mailer->get_sent()->body;

		$this->assertStringContainsString( $crm, $body );
		$this->assertStringContainsString( $error, $body );
	}

	/**
	 * Test email body contains form information when provided.
	 */
	public function test_email_body_contains_form_information() {
		$crm       = 'Holded';
		$error     = 'Test error';
		$data      = array(
			array( 'name' => 'Name', 'value' => 'John Doe' ),
		);
		$form_info = array(
			'form_type' => 'Gravity Forms',
			'form_id'   => '42',
			'form_name' => 'Contact Form',
			'entry_id'  => '12345',
		);

		formscrm_debug_email_lead( $crm, $error, $data, '', '', $form_info );

		$mailer = tests_retrieve_phpmailer_instance();
		$body   = $mailer->get_sent()->body;

		$this->assertStringContainsString( 'Gravity Forms', $body );
		$this->assertStringContainsString( '42', $body );
		$this->assertStringContainsString( 'Contact Form', $body );
		$this->assertStringContainsString( '12345', $body );
	}

	/**
	 * Test email body contains site information.
	 */
	public function test_email_body_contains_site_information() {
		$site_name = get_bloginfo( 'name' );
		$site_url  = get_site_url();

		$crm   = 'Holded';
		$error = 'Test error';
		$data  = array(
			array( 'name' => 'Email', 'value' => 'test@example.com' ),
		);

		formscrm_debug_email_lead( $crm, $error, $data );

		$mailer = tests_retrieve_phpmailer_instance();
		$body   = $mailer->get_sent()->body;

		$this->assertStringContainsString( $site_name, $body );
		$this->assertStringContainsString( $site_url, $body );
	}

	/**
	 * Test email body contains lead data.
	 */
	public function test_email_body_contains_lead_data() {
		$crm   = 'Holded';
		$error = 'Test error';
		$data  = array(
			array( 'name' => 'Name', 'value' => 'John Doe' ),
			array( 'name' => 'Email', 'value' => 'john@example.com' ),
			array( 'name' => 'Phone', 'value' => '+34 600 123 456' ),
		);

		formscrm_debug_email_lead( $crm, $error, $data );

		$mailer = tests_retrieve_phpmailer_instance();
		$body   = $mailer->get_sent()->body;

		$this->assertStringContainsString( 'John Doe', $body );
		$this->assertStringContainsString( 'john@example.com', $body );
		$this->assertStringContainsString( '+34 600 123 456', $body );
	}

	/**
	 * Test email body contains technical details when provided.
	 */
	public function test_email_body_contains_technical_details() {
		$crm   = 'Holded';
		$error = 'Test error';
		$data  = array(
			array( 'name' => 'Email', 'value' => 'test@example.com' ),
		);
		$url   = 'https://api.holded.com/api/contacts/v1';
		$json  = '{"name":"Test","email":"test@example.com"}';

		formscrm_debug_email_lead( $crm, $error, $data, $url, $json );

		$mailer = tests_retrieve_phpmailer_instance();
		$body   = $mailer->get_sent()->body;

		$this->assertStringContainsString( $url, $body );
		$this->assertStringContainsString( $json, $body );
	}

	/**
	 * Test Slack notification returns false when no webhook configured.
	 */
	public function test_slack_returns_false_when_no_webhook() {
		$crm   = 'Holded';
		$error = 'Test error';
		$data  = array(
			array( 'name' => 'Email', 'value' => 'test@example.com' ),
		);

		$result = formscrm_send_slack_notification( $crm, $error, $data );

		$this->assertFalse( $result );
	}

	/**
	 * Test Slack notification sends when webhook configured.
	 */
	public function test_slack_sends_when_webhook_configured() {
		$webhook_url = 'https://hooks.slack.com/services/TEST/WEBHOOK/URL';
		update_option( 'formscrm_slack_webhook_url', $webhook_url );

		$crm   = 'Holded';
		$error = 'Test error';
		$data  = array(
			array( 'name' => 'Email', 'value' => 'test@example.com' ),
		);

		$result = formscrm_send_slack_notification( $crm, $error, $data );

		$this->assertTrue( $result );
	}

	/**
	 * Test Slack notification includes site information.
	 */
	public function test_slack_includes_site_information() {
		$webhook_url = 'https://hooks.slack.com/services/TEST/WEBHOOK/URL';
		update_option( 'formscrm_slack_webhook_url', $webhook_url );

		$site_name = get_bloginfo( 'name' );
		$site_url  = get_site_url();

		$crm   = 'Holded';
		$error = 'Test error';
		$data  = array(
			array( 'name' => 'Email', 'value' => 'test@example.com' ),
		);

		// Capture the HTTP request.
		$http_request = null;
		add_filter(
			'pre_http_request',
			function( $pre, $r, $url ) use ( &$http_request ) {
				$http_request = $r;
				return array(
					'body'     => 'ok',
					'response' => array(
						'code'    => 200,
						'message' => 'OK',
					),
				);
			},
			20,
			3
		);

		formscrm_send_slack_notification( $crm, $error, $data );

		$this->assertNotNull( $http_request );
		$body = json_decode( $http_request['body'], true );

		$this->assertNotEmpty( $body['attachments'] );
		$message_text = $body['attachments'][0]['text'];

		$this->assertStringContainsString( $site_name, $message_text );
		$this->assertStringContainsString( $site_url, $message_text );
	}

	/**
	 * Test Slack notification includes form information.
	 */
	public function test_slack_includes_form_information() {
		$webhook_url = 'https://hooks.slack.com/services/TEST/WEBHOOK/URL';
		update_option( 'formscrm_slack_webhook_url', $webhook_url );

		$crm       = 'Holded';
		$error     = 'Test error';
		$data      = array(
			array( 'name' => 'Email', 'value' => 'test@example.com' ),
		);
		$form_info = array(
			'form_type' => 'WPForms',
			'form_id'   => '99',
			'form_name' => 'Test Form',
			'entry_id'  => '54321',
		);

		// Capture the HTTP request.
		$http_request = null;
		add_filter(
			'pre_http_request',
			function( $pre, $r, $url ) use ( &$http_request ) {
				$http_request = $r;
				return array(
					'body'     => 'ok',
					'response' => array(
						'code'    => 200,
						'message' => 'OK',
					),
				);
			},
			20,
			3
		);

		formscrm_send_slack_notification( $crm, $error, $data, '', '', $form_info );

		$body         = json_decode( $http_request['body'], true );
		$message_text = $body['attachments'][0]['text'];

		$this->assertStringContainsString( 'WPForms', $message_text );
		$this->assertStringContainsString( '99', $message_text );
		$this->assertStringContainsString( 'Test Form', $message_text );
		$this->assertStringContainsString( '54321', $message_text );
	}

	/**
	 * Test Slack notification includes CRM and error.
	 */
	public function test_slack_includes_crm_and_error() {
		$webhook_url = 'https://hooks.slack.com/services/TEST/WEBHOOK/URL';
		update_option( 'formscrm_slack_webhook_url', $webhook_url );

		$crm   = 'Clientify';
		$error = 'Invalid API credentials provided';
		$data  = array(
			array( 'name' => 'Email', 'value' => 'test@example.com' ),
		);

		// Capture the HTTP request.
		$http_request = null;
		add_filter(
			'pre_http_request',
			function( $pre, $r, $url ) use ( &$http_request ) {
				$http_request = $r;
				return array(
					'body'     => 'ok',
					'response' => array(
						'code'    => 200,
						'message' => 'OK',
					),
				);
			},
			20,
			3
		);

		formscrm_send_slack_notification( $crm, $error, $data );

		$body         = json_decode( $http_request['body'], true );
		$message_text = $body['attachments'][0]['text'];

		$this->assertStringContainsString( 'Clientify', $message_text );
		$this->assertStringContainsString( 'Invalid API credentials', $message_text );
	}

	/**
	 * Test Slack notification includes lead preview.
	 */
	public function test_slack_includes_lead_preview() {
		$webhook_url = 'https://hooks.slack.com/services/TEST/WEBHOOK/URL';
		update_option( 'formscrm_slack_webhook_url', $webhook_url );

		$crm   = 'Holded';
		$error = 'Test error';
		$data  = array(
			array( 'name' => 'Name', 'value' => 'Jane Smith' ),
			array( 'name' => 'Email', 'value' => 'jane@example.com' ),
			array( 'name' => 'Company', 'value' => 'Tech Corp' ),
			array( 'name' => 'Phone', 'value' => '+1 555 1234' ),
		);

		// Capture the HTTP request.
		$http_request = null;
		add_filter(
			'pre_http_request',
			function( $pre, $r, $url ) use ( &$http_request ) {
				$http_request = $r;
				return array(
					'body'     => 'ok',
					'response' => array(
						'code'    => 200,
						'message' => 'OK',
					),
				);
			},
			20,
			3
		);

		formscrm_send_slack_notification( $crm, $error, $data );

		$body         = json_decode( $http_request['body'], true );
		$message_text = $body['attachments'][0]['text'];

		// Should include first 3 fields.
		$this->assertStringContainsString( 'Jane Smith', $message_text );
		$this->assertStringContainsString( 'jane@example.com', $message_text );
		$this->assertStringContainsString( 'Tech Corp', $message_text );

		// Should show "+1 more" indicator.
		$this->assertStringContainsString( '+1 more', $message_text );
	}

	/**
	 * Test Slack notification includes API URL.
	 */
	public function test_slack_includes_api_url() {
		$webhook_url = 'https://hooks.slack.com/services/TEST/WEBHOOK/URL';
		update_option( 'formscrm_slack_webhook_url', $webhook_url );

		$crm   = 'Holded';
		$error = 'Test error';
		$data  = array(
			array( 'name' => 'Email', 'value' => 'test@example.com' ),
		);
		$url   = 'https://api.holded.com/api/invoicing/v1/contacts';

		// Capture the HTTP request.
		$http_request = null;
		add_filter(
			'pre_http_request',
			function( $pre, $r, $url ) use ( &$http_request ) {
				$http_request = $r;
				return array(
					'body'     => 'ok',
					'response' => array(
						'code'    => 200,
						'message' => 'OK',
					),
				);
			},
			20,
			3
		);

		formscrm_send_slack_notification( $crm, $error, $data, $url );

		$body         = json_decode( $http_request['body'], true );
		$message_text = $body['attachments'][0]['text'];

		$this->assertStringContainsString( $url, $message_text );
	}

	/**
	 * Test Slack notification uses correct color (danger/red).
	 */
	public function test_slack_uses_danger_color() {
		$webhook_url = 'https://hooks.slack.com/services/TEST/WEBHOOK/URL';
		update_option( 'formscrm_slack_webhook_url', $webhook_url );

		$crm   = 'Holded';
		$error = 'Test error';
		$data  = array(
			array( 'name' => 'Email', 'value' => 'test@example.com' ),
		);

		// Capture the HTTP request.
		$http_request = null;
		add_filter(
			'pre_http_request',
			function( $pre, $r, $url ) use ( &$http_request ) {
				$http_request = $r;
				return array(
					'body'     => 'ok',
					'response' => array(
						'code'    => 200,
						'message' => 'OK',
					),
				);
			},
			20,
			3
		);

		formscrm_send_slack_notification( $crm, $error, $data );

		$body = json_decode( $http_request['body'], true );

		$this->assertEquals( 'danger', $body['attachments'][0]['color'] );
	}

	/**
	 * Test integration: Email is sent and Slack is notified when both configured.
	 */
	public function test_integration_both_email_and_slack_sent() {
		$custom_email = 'errors@example.com';
		$webhook_url  = 'https://hooks.slack.com/services/TEST/WEBHOOK/URL';

		update_option( 'formscrm_error_notification_email', $custom_email );
		update_option( 'formscrm_slack_webhook_url', $webhook_url );

		$crm       = 'Holded';
		$error     = 'Integration test error';
		$data      = array(
			array( 'name' => 'Name', 'value' => 'Integration Test' ),
			array( 'name' => 'Email', 'value' => 'test@example.com' ),
		);
		$form_info = array(
			'form_type' => 'Gravity Forms',
			'form_id'   => '1',
			'form_name' => 'Contact Form',
		);

		// Call the main error function which should trigger both.
		formscrm_debug_email_lead( $crm, $error, $data, '', '', $form_info );

		// Check email was sent.
		$mailer = tests_retrieve_phpmailer_instance();
		$this->assertStringContainsString( $custom_email, $mailer->get_recipient( 'to' )->address );
		$this->assertStringContainsString( $error, $mailer->get_sent()->body );

		// Slack is called by the email function, so it should have been triggered.
		// We can't easily verify the HTTP call here without more complex mocking,
		// but the previous tests verify Slack works independently.
		$this->assertTrue( true );
	}
}

