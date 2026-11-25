<?php
/**
 * Class AcumbaMailTest
 *
 * Tests for the AcumbaMail connector: the lists are requested once and a
 * throttled request (HTTP 429, the API allows 5 per second) is retried, so the
 * CRM Module dropdown is not left empty.
 *
 * Command: composer test --filter AcumbaMailTest
 *
 * @package Formscrm
 */

/**
 * Test case for CRMLIB_AcumbaMail.
 */
class AcumbaMailTest extends WP_UnitTestCase {

	/**
	 * Requests made to getLists.
	 *
	 * @var int
	 */
	private $lists_calls = 0;

	/**
	 * Number of getLists requests to answer with HTTP 429 before succeeding.
	 *
	 * @var int
	 */
	private $throttled = 0;

	/**
	 * Set up: mock the API and clear caches.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->lists_calls = 0;
		$this->throttled   = 0;
		delete_transient( 'formscrm_acumba_lists_' . md5( 'KEY' ) );

		// The connector keeps the lists per request in a static property: reset it between tests.
		formscrm_get_api_class( 'acumbamail' );
		$cache = new ReflectionProperty( 'CRMLIB_AcumbaMail', 'lists_cache' );
		$cache->setAccessible( true );
		$cache->setValue( null, array() );
		add_filter( 'pre_http_request', array( $this, 'mock_api' ), 10, 3 );
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		remove_filter( 'pre_http_request', array( $this, 'mock_api' ), 10 );
		delete_transient( 'formscrm_acumba_lists_' . md5( 'KEY' ) );
		parent::tearDown();
	}

	/**
	 * Mocks https://acumbamail.com/api/1/.
	 *
	 * @param mixed  $pre  Preempt value.
	 * @param array  $args Request args.
	 * @param string $url  URL.
	 * @return mixed
	 */
	public function mock_api( $pre, $args, $url ) {
		if ( false === strpos( $url, 'acumbamail.com/api/1/' ) ) {
			return $pre;
		}

		$function = basename( rtrim( $url, '/' ) );

		if ( 'getLists' === $function ) {
			++$this->lists_calls;
			if ( $this->throttled > 0 ) {
				--$this->throttled;
				return array(
					'headers'  => array(),
					'body'     => 'Too many requests',
					'response' => array(
						'code'    => 429,
						'message' => 'Too Many Requests',
					),
					'cookies'  => array(),
					'filename' => null,
				);
			}
			$body = wp_json_encode(
				array(
					'123' => array( 'name' => 'Newsletter' ),
					'456' => array( 'name' => 'Clientes' ),
				)
			);
		} else {
			$body = wp_json_encode(
				array(
					'email' => 'email',
					'name'  => 'str',
				)
			);
		}

		return array(
			'headers'  => array(),
			'body'     => $body,
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'cookies'  => array(),
			'filename' => null,
		);
	}

	/**
	 * The lists become the CRM Module choices, after the dynamic option.
	 */
	public function test_list_modules_returns_account_lists() {
		$crm     = formscrm_get_api_class( 'acumbamail' );
		$modules = $crm->list_modules( array( 'fc_crm_apipassword' => 'KEY' ) );

		$this->assertSame( array( 'Newsletter', 'Clientes' ), array_slice( wp_list_pluck( $modules, 'label' ), 1 ) );
		$this->assertSame( 'dinamic', $modules[0]['name'] );
	}

	/**
	 * Login, modules and fields share one getLists request.
	 */
	public function test_lists_are_requested_once() {
		$crm      = formscrm_get_api_class( 'acumbamail' );
		$settings = array(
			'fc_crm_apipassword' => 'KEY',
			'fc_crm_module'      => 'Newsletter',
		);

		$this->assertTrue( $crm->login( $settings ) );
		$crm->list_modules( $settings );
		$crm->list_fields( $settings );

		$this->assertSame( 1, $this->lists_calls );
	}

	/**
	 * A throttled request is retried and the lists still come back.
	 */
	public function test_throttled_request_is_retried() {
		$this->throttled = 1;

		$crm     = formscrm_get_api_class( 'acumbamail' );
		$modules = $crm->list_modules( array( 'fc_crm_apipassword' => 'KEY' ) );

		$this->assertCount( 3, $modules );
		$this->assertSame( 2, $this->lists_calls );
	}

	/**
	 * Without an API key there is nothing to list.
	 */
	public function test_no_key_no_modules() {
		$crm = formscrm_get_api_class( 'acumbamail' );
		$this->assertSame( array(), $crm->list_modules( array() ) );
	}
}
