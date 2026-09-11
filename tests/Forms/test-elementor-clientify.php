<?php
/**
 * Integration tests for the Elementor -> Clientify submission flow.
 *
 * Command: composer test-debug --filter ElementorClientifyTest
 *
 * @package Formscrm
 */

/**
 * Minimal stand-in for \ElementorPro\Modules\Forms\Classes\Form_Record.
 *
 * FormsCRM_Elementor_Action_After_Submit::run() only calls get( $key ) on the
 * record, so this duck-typed double is enough without depending on Elementor Pro.
 */
class FormsCRM_Test_Fake_Form_Record {

	/**
	 * Backing data.
	 *
	 * @var array
	 */
	private $data;

	/**
	 * Constructor.
	 *
	 * @param array $data Data keyed like 'form_settings' and 'fields'.
	 */
	public function __construct( array $data ) {
		$this->data = $data;
	}

	/**
	 * Get a value by key.
	 *
	 * @param string $key Key to retrieve.
	 * @return mixed
	 */
	public function get( $key ) {
		return isset( $this->data[ $key ] ) ? $this->data[ $key ] : null;
	}
}

/**
 * Minimal stand-in for \ElementorPro\Modules\Forms\Classes\Ajax_Handler.
 */
class FormsCRM_Test_Fake_Ajax_Handler {

	/**
	 * Messages collected by run().
	 *
	 * @var array
	 */
	public $messages = array();
}

/**
 * Elementor + Clientify submission tests.
 *
 * @see FormsCRM_Elementor_Action_After_Submit::run()
 * @see https://wordpress.org/support/topic/bug-formscrm-4-4-1-elementor-clientify-http-409-conflict/
 */
class ElementorClientifyTest extends WP_UnitTestCase {

	/**
	 * Requests captured from wp_remote_request() during a test, in order.
	 *
	 * @var array
	 */
	protected $requests = array();

	/**
	 * Set up: install the HTTP mock.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->requests = array();
		unset( $_POST['visitor_key'] );
		add_filter( 'pre_http_request', array( $this, 'mock_http_request' ), 10, 3 );
	}

	/**
	 * Tear down: remove the HTTP mock.
	 */
	public function tearDown(): void {
		remove_filter( 'pre_http_request', array( $this, 'mock_http_request' ), 10 );
		parent::tearDown();
	}

	/**
	 * HTTP mock for Clientify v2 endpoints. Records every request made.
	 *
	 * @param mixed  $pre Pre-empt value.
	 * @param array  $r   Request args.
	 * @param string $url Request URL.
	 * @return array
	 */
	public function mock_http_request( $pre, $r, $url ) {
		$this->requests[] = array(
			'url'    => $url,
			'method' => $r['method'],
		);

		if ( false !== strpos( $url, 'me/' ) ) {
			return $this->response( 200, '{"id":57672,"username":"test@example.com","account_status":"client"}' );
		}

		// Search endpoint (GET contacts/ with query).
		if ( false !== strpos( $url, 'contacts/' ) && 'GET' === $r['method'] ) {
			if ( false !== strpos( $url, 'query=existing%40example.com' ) ) {
				return $this->response( 200, '{"count":1,"results":[{"id":"contact-123","email":"existing@example.com"}]}' );
			}
			return $this->response( 200, '{"count":0,"results":[]}' );
		}

		if ( false !== strpos( $url, 'contacts/' ) && 'POST' === $r['method'] ) {
			return $this->response( 201, '{"id":"contact-new","email":"new@example.com"}' );
		}

		if ( false !== strpos( $url, 'contacts/' ) && 'PATCH' === $r['method'] ) {
			return $this->response( 200, '{"id":"contact-123","email":"existing@example.com","updated":true}' );
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
	protected function response( $code, $body = '' ) {
		return array(
			'body'     => $body,
			'response' => array(
				'code'    => $code,
				'message' => 200 === $code ? 'OK' : 'Error',
			),
		);
	}

	/**
	 * Builds a Form_Record double for a given hidden-settings payload.
	 *
	 * @param array $hidden_settings Decoded 'formscrm_settings_hidden' contents.
	 * @param array $raw_fields      Submitted Elementor fields, keyed by field id.
	 * @return FormsCRM_Test_Fake_Form_Record
	 */
	protected function make_record( array $hidden_settings, array $raw_fields ) {
		return new FormsCRM_Test_Fake_Form_Record(
			array(
				'form_settings' => array(
					'fc_crm_type'              => 'clientify',
					'fc_crm_apipassword'       => 'api-password',
					'formscrm_settings_hidden' => wp_json_encode( $hidden_settings ),
				),
				'fields'        => $raw_fields,
			)
		);
	}

	/**
	 * Requests hitting contacts/ that are not a search (i.e. not a GET with a query).
	 *
	 * @return array
	 */
	protected function get_create_or_update_requests() {
		return array_values(
			array_filter(
				$this->requests,
				function ( $request ) {
					return false !== strpos( $request['url'], 'contacts/' ) && 'GET' !== $request['method'];
				}
			)
		);
	}

	/**
	 * Without a merge strategy configured, the contact is always force-created.
	 *
	 * This was the only option Elementor forms had before this fix, which is what
	 * caused a 409 Conflict on every resubmission from an existing contact.
	 */
	public function test_run_without_merge_strategy_forces_insert() {
		$record = $this->make_record(
			array(
				'clientify'          => 'Contacts',
				'fc_crm_field-email' => 'form-email',
			),
			array(
				'form-email' => array( 'value' => 'existing@example.com' ),
			)
		);
		$ajax_handler = new FormsCRM_Test_Fake_Ajax_Handler();

		( new FormsCRM_Elementor_Action_After_Submit() )->run( $record, $ajax_handler );

		$create_requests = $this->get_create_or_update_requests();

		$this->assertCount( 1, $create_requests );
		$this->assertSame( 'POST', $create_requests[0]['method'] );
		$this->assertStringContainsString( 'force_insert=true', $create_requests[0]['url'] );
	}

	/**
	 * With a merge strategy configured (email), a resubmission from an existing
	 * contact is searched and updated via PATCH instead of forcing an insert.
	 */
	public function test_run_with_merge_strategy_updates_existing_contact() {
		$record = $this->make_record(
			array(
				'clientify'          => 'Contacts',
				'fc_crm_field-email' => 'form-email',
				'fc_crm_merge_entry' => 'email',
			),
			array(
				'form-email' => array( 'value' => 'existing@example.com' ),
			)
		);
		$ajax_handler = new FormsCRM_Test_Fake_Ajax_Handler();

		( new FormsCRM_Elementor_Action_After_Submit() )->run( $record, $ajax_handler );

		$create_requests = $this->get_create_or_update_requests();

		$this->assertCount( 1, $create_requests );
		$this->assertSame( 'PATCH', $create_requests[0]['method'] );
		$this->assertStringContainsString( 'contact-123', $create_requests[0]['url'] );
		$this->assertNotEmpty( $ajax_handler->messages['success'] );
	}

	/**
	 * With a merge strategy configured but no existing contact found, a new
	 * contact is still created — just via the search-then-create path.
	 */
	public function test_run_with_merge_strategy_creates_new_contact_when_not_found() {
		$record = $this->make_record(
			array(
				'clientify'          => 'Contacts',
				'fc_crm_field-email' => 'form-email',
				'fc_crm_merge_entry' => 'email',
			),
			array(
				'form-email' => array( 'value' => 'new@example.com' ),
			)
		);
		$ajax_handler = new FormsCRM_Test_Fake_Ajax_Handler();

		( new FormsCRM_Elementor_Action_After_Submit() )->run( $record, $ajax_handler );

		$create_requests = $this->get_create_or_update_requests();

		$this->assertCount( 1, $create_requests );
		$this->assertSame( 'POST', $create_requests[0]['method'] );
		$this->assertStringNotContainsString( 'force_insert=true', $create_requests[0]['url'] );
	}
}
