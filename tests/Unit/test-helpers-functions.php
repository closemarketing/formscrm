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

		add_filter(
			'pre_http_request',
			function( $pre, $r, $url ) {
				$body_query    = $r['body'];

				return array(
					'body' => '',
					'response' => array( 'code' => 200, 'message' => 'OK' ),
				);
			},
		10, 3 );
	}

	public function test_check_url_crm() {
		$url = 'https://example.com';
		$this->assertEquals( 'https://example.com/', formscrm_check_url_crm( $url ) );

		$url = 'https://example.com/';
		$this->assertEquals( 'https://example.com/', formscrm_check_url_crm( $url ) );
	}

	public function test_webhook_post() {
		$settings['fc_crm_webhook'] = 'https://webhook.com/test';
		$response_api = array(
			'status' => 'ok',
			'message' => 'success',
			'module' => 'contact',
			'id'     => '1234567890',
		);
		$response = formscrm_send_webhook( $settings, $response_api );

		$this->assertEquals( 200, $response['response']['response']['code'] );
		$this->assertEquals( 'OK', $response['response']['response']['message'] );
		$this->assertEquals( $response_api['id'], $response['request']['data']['id'] );
		$this->assertEquals( $response_api['module'] . '.saved', $response['request']['hook']['event'] );

		$deal_id = '32131321';
		$response_api = array(
			'status' => 'ok',
			'message' => 'success',
			'module' => 'deal',
			'id'     => '1234567890|' . $deal_id,
		);
		$response = formscrm_send_webhook( $settings, $response_api );

		$this->assertEquals( 200, $response['response']['response']['code'] );
		$this->assertEquals( 'OK', $response['response']['response']['message'] );
		$this->assertEquals( $deal_id, $response['request']['data']['id'] );
		$this->assertEquals( $response_api['module'] . '.saved', $response['request']['hook']['event'] );
	}

}