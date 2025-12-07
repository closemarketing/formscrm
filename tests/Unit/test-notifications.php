<?php
/**
 * Class SlackNotificationsTest
 * 
 * Tests for Slack error notifications
 * 
 * Command: composer test --filter SlackNotificationsTest
 * Debug: composer test-debug --filter SlackNotificationsTest
 *
 * @package Formscrm
 */

/**
 * Test case for Slack notification functions.
 */
class SlackNotificationsTest extends WP_UnitTestCase {

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
		delete_option( 'formscrm_slack_webhook_url' );
	}

	/**
	 * Tear down test environment.
	 */
	public function tearDown(): void {
		parent::tearDown();

		// Clean up options.
		delete_option( 'formscrm_slack_webhook_url' );
	}

	/**
	 * Test that formscrm_alert_error function exists.
	 */
	public function test_debug_email_lead_function_exists() {
		$this->assertTrue( function_exists( 'formscrm_alert_error' ) );
	}

	/**
	 * Test that formscrm_send_slack_notification function exists.
	 */
	public function test_send_slack_notification_function_exists() {
		$this->assertTrue( function_exists( 'formscrm_send_slack_notification' ) );
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
}
