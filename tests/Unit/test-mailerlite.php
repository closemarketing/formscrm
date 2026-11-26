<?php
/**
 * Class MailerLiteTest
 *
 * Tests for the MailerLite Classic connector: subscriber status options are
 * mapped as virtual fields and only sent when mapped.
 *
 * Command: composer test --filter MailerLiteTest
 *
 * @package Formscrm
 */

/**
 * Test case for CRMLIB_Mailerlite.
 */
class MailerLiteTest extends WP_UnitTestCase {

	/**
	 * Decoded body of the last POST sent to the API.
	 *
	 * @var array
	 */
	private $last_body = array();

	/**
	 * Set up: mock the API.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->last_body = array();
		add_filter( 'pre_http_request', array( $this, 'mock_api' ), 10, 3 );
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		remove_filter( 'pre_http_request', array( $this, 'mock_api' ), 10 );
		remove_all_filters( 'formscrm_mailerlite_subscriber' );
		parent::tearDown();
	}

	/**
	 * Mocks MailerLite Classic API.
	 *
	 * @param false|array $preempt Preempt value.
	 * @param array       $args    Request args.
	 * @param string      $url     Request URL.
	 * @return array
	 */
	public function mock_api( $preempt, $args, $url ) {
		if ( false !== strpos( $url, '/fields' ) ) {
			$body = array(
				array(
					'key'   => 'company',
					'title' => 'Company',
				),
			);
		} else {
			$this->last_body = json_decode( $args['body'], true );
			$body            = array( 'id' => 123 );
		}
		return array(
			'headers'  => array(),
			'body'     => wp_json_encode( $body ),
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'cookies'  => array(),
		);
	}

	/**
	 * Sends an entry and returns the payload received by the API.
	 *
	 * @param array $merge_vars Name/value pairs.
	 * @return array
	 */
	private function send( $merge_vars ) {
		$crm = formscrm_get_api_class( 'mailerlite' );
		$crm->create_entry(
			array(
				'fc_crm_apipassword' => 'KEY',
				'fc_crm_module'      => '1',
			),
			$merge_vars
		);
		return $this->last_body;
	}

	/**
	 * Without mapped options the payload is the same as before.
	 */
	public function test_payload_unchanged_without_options() {
		$body = $this->send(
			array(
				array( 'name' => 'email', 'value' => 'a@example.com' ),
				array( 'name' => 'company', 'value' => 'Acme' ),
			)
		);

		$this->assertSame(
			array(
				'email'  => 'a@example.com',
				'fields' => array( 'company' => 'Acme' ),
			),
			$body
		);
	}

	/**
	 * Status and resubscribe go to the top level, not to custom fields.
	 */
	public function test_status_and_resubscribe_are_top_level() {
		$body = $this->send(
			array(
				array( 'name' => 'email', 'value' => 'a@example.com' ),
				array( 'name' => CRMLIB_Mailerlite::OPTION_TYPE, 'value' => ' Active ' ),
				array( 'name' => CRMLIB_Mailerlite::OPTION_RESUBSCRIBE, 'value' => 'true' ),
			)
		);

		$this->assertSame( 'active', $body['type'] );
		$this->assertTrue( $body['resubscribe'] );
		$this->assertArrayNotHasKey( 'fields', $body );
	}

	/**
	 * Invalid values are ignored so the API decides.
	 */
	public function test_invalid_values_are_ignored() {
		$body = $this->send(
			array(
				array( 'name' => 'email', 'value' => 'a@example.com' ),
				array( 'name' => CRMLIB_Mailerlite::OPTION_TYPE, 'value' => 'bounced' ),
				array( 'name' => CRMLIB_Mailerlite::OPTION_RESUBSCRIBE, 'value' => 'maybe' ),
			)
		);

		$this->assertArrayNotHasKey( 'type', $body );
		$this->assertArrayNotHasKey( 'resubscribe', $body );
	}

	/**
	 * The filter can change the payload.
	 */
	public function test_filter_changes_payload() {
		add_filter(
			'formscrm_mailerlite_subscriber',
			function ( $subscriber ) {
				$subscriber['type'] = 'unconfirmed';
				return $subscriber;
			}
		);

		$body = $this->send( array( array( 'name' => 'email', 'value' => 'a@example.com' ) ) );

		$this->assertSame( 'unconfirmed', $body['type'] );
	}

	/**
	 * Virtual fields are listed in the field map.
	 */
	public function test_list_fields_includes_options() {
		$crm    = formscrm_get_api_class( 'mailerlite' );
		$fields = $crm->list_fields( array( 'fc_crm_apipassword' => 'KEY' ), '1' );
		$names  = wp_list_pluck( $fields, 'name' );

		$this->assertContains( 'company', $names );
		$this->assertContains( CRMLIB_Mailerlite::OPTION_TYPE, $names );
		$this->assertContains( CRMLIB_Mailerlite::OPTION_RESUBSCRIBE, $names );
	}
}
