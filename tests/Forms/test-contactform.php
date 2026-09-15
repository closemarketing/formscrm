<?php
/**
 * Class ContactFormsTest
 *
 * Command: composer test-debug --filter ContactFormsTest
 *
 * @package Formscrm
 */

if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
	/**
	 * Minimal stand-in for WPCF7_ContactForm, since Contact Form 7 isn't
	 * installed in the test environment. Only get_current()/id() are used by
	 * FORMSCRM_CF7_Settings::register_analytics_plus_selector().
	 */
	class WPCF7_ContactForm {

		/**
		 * Instance returned by get_current(), set per-test.
		 *
		 * @var WPCF7_ContactForm|null
		 */
		public static $current = null;

		/**
		 * Form ID this instance represents.
		 *
		 * @var int
		 */
		private $id;

		/**
		 * Constructor.
		 *
		 * @param int $id Form ID.
		 */
		public function __construct( $id ) {
			$this->id = $id;
		}

		/**
		 * Returns the form currently being rendered/processed, per test setup.
		 *
		 * @return WPCF7_ContactForm|null
		 */
		public static function get_current() {
			return self::$current;
		}

		/**
		 * Returns this form's ID.
		 *
		 * @return int
		 */
		public function id() {
			return $this->id;
		}
	}
}

class ContactFormsTest extends WP_UnitTestCase {

	/**
	 * Tear down: reset the CF7 double and any registered selectors.
	 */
	public function tearDown(): void {
		WPCF7_ContactForm::$current = null;
		remove_all_filters( 'formscrm_analytics_plus_selectors' );
		parent::tearDown();
	}

	/**
	 * A form with visitor_key2 mapped to a Clientify feed must register a
	 * selector for the tracking script to fill, scoped to that form field.
	 */
	public function test_register_analytics_plus_selector_registers_mapped_field() {
		WPCF7_ContactForm::$current = new WPCF7_ContactForm( 123 );
		update_option(
			'cf7_crm_123',
			array(
				'fc_crm_type'                => 'clientify',
				'fc_crm_field-visitor_key2'  => 'visitor-key2-field',
			)
		);

		$settings = new FORMSCRM_CF7_Settings();
		$settings->register_analytics_plus_selector( '<form></form>' );

		$selectors = apply_filters( 'formscrm_analytics_plus_selectors', array() );
		$this->assertSame( array( '.wpcf7-form [name="visitor-key2-field"]' ), $selectors );

		delete_option( 'cf7_crm_123' );
	}

	/**
	 * No selector should be registered when visitor_key2 isn't mapped, or the
	 * form's CRM isn't Clientify.
	 */
	public function test_register_analytics_plus_selector_skips_when_not_mapped() {
		WPCF7_ContactForm::$current = new WPCF7_ContactForm( 124 );
		update_option(
			'cf7_crm_124',
			array(
				'fc_crm_type'              => 'clientify',
				'fc_crm_field-email'       => 'your-email',
			)
		);

		$settings = new FORMSCRM_CF7_Settings();
		$settings->register_analytics_plus_selector( '<form></form>' );

		$this->assertSame( array(), apply_filters( 'formscrm_analytics_plus_selectors', array() ) );

		delete_option( 'cf7_crm_124' );
	}

	public function test_get_merge_vars() {
		$cf7_crm = array(
			'fc_crm_type'                                   => 'clientify',
			'fc_crm_apipassword'                            => 'api-password',
			'fc_crm_module'                                 => 'Contacts',
			'fc_crm_field-first_name'                       => 'your-name',
			'fc_crm_field-email'                            => 'your-email',
			'fc_crm_field-custom_fields|interes_categoria'  => 'menu-991',
			'fc_crm_field-custom_fields|interes2'           => 'menu-992',
			'fc_crm_field-custom_fields|info_chat'          => 'menu-993',
		);

		$submitted_data = array(
			'your-name'        => 'david',
			'your-email'       => 'david@close.marketing',
			'your-phone'       => '66666666',
			'menu-991'         => array( '2.000' ),
			'menu-992'         => array( 'De 3 a 6 meses' ),
			'menu-993'         => array( 'Autónomo' ),
			'your-subject'     => 'test',
			'your-message'     => 'En un lugar de la Mancha, de cuyo nombre no quiero acordarme, no ha mucho tiempo que vivía un hidalgo de los de lanza en astillero, adarga antigua, rocín flaco y galgo corredor. Una olla de algo más vaca que carnero, salpicón las más noches, duelos y quebrantos los sábados, lantejas los viernes, algún palomino de añadidura los domingos, consumían las tres cuartas partes de su hacienda.',
			'clientify_cookie' => '',
		);

		$merge_vars = FORMSCRM_CF7_Settings::get_merge_vars( $cf7_crm, $submitted_data );
		$this->assertEquals( $merge_vars, array(
			array( 'name' => 'first_name', 'value' => 'david' ),
			array( 'name' => 'email', 'value' => 'david@close.marketing' ),
			array( 'name' => 'custom_fields|interes_categoria', 'value' => '2.000' ),
			array( 'name' => 'custom_fields|interes2', 'value' => 'De 3 a 6 meses' ),
			array( 'name' => 'custom_fields|info_chat', 'value' => 'Autónomo' ),
		) );
	}

	/**
	 * GDPR checkbox unchecked: CF7 submits empty array, value must be false.
	 */
	public function test_get_merge_vars_gdpr_unchecked() {
		$cf7_crm = array(
			'fc_crm_type'              => 'clientify',
			'fc_crm_apipassword'       => 'api-password',
			'fc_crm_module'            => 'Contacts',
			'fc_crm_field-gdpr_accept' => 'extra-info',
		);

		$submitted_data = array(
			'extra-info' => array(), // Unchecked checkbox returns empty array in CF7.
		);

		$merge_vars = FORMSCRM_CF7_Settings::get_merge_vars( $cf7_crm, $submitted_data );
		$this->assertEquals(
			array( array( 'name' => 'gdpr_accept', 'value' => false ) ),
			$merge_vars
		);
	}

	/**
	 * GDPR checkbox checked: CF7 submits the label string, value must be true.
	 */
	public function test_get_merge_vars_gdpr_checked() {
		$cf7_crm = array(
			'fc_crm_type'              => 'clientify',
			'fc_crm_apipassword'       => 'api-password',
			'fc_crm_module'            => 'Contacts',
			'fc_crm_field-gdpr_accept' => 'extra-info',
		);

		$submitted_data = array(
			'extra-info' => array( 'Me gustaría estar al tanto de las novedades de Ipace' ),
		);

		$merge_vars = FORMSCRM_CF7_Settings::get_merge_vars( $cf7_crm, $submitted_data );
		$this->assertEquals(
			array( array( 'name' => 'gdpr_accept', 'value' => true ) ),
			$merge_vars
		);
	}

	/**
	 * Data provider for gdpr_accept bool normalization.
	 *
	 * @return array
	 */
	public function gdpr_falsy_values_provider() {
		return array(
			// PHP empty() → false (not accepted).
			'empty string' => array( '',      false ),
			'zero string'  => array( '0',     false ),  // empty('0') === true in PHP.
			'empty array'  => array( array(), false ),
			// Non-empty → true (accepted), regardless of label language.
			'label string' => array( 'Acepto términos',       true ),
			'false string' => array( 'false',                 true ),  // non-empty string.
			'array label'  => array( array( 'Me gustaría' ),  true ),
		);
	}

	/**
	 * gdpr_accept must be normalized to bool, never left as the field name.
	 *
	 * @dataProvider gdpr_falsy_values_provider
	 * @param mixed $submitted_value Raw value as CF7 would submit it.
	 * @param bool  $expected_value  Expected bool value in merge vars.
	 */
	public function test_get_merge_vars_gdpr_falsy_values( $submitted_value, $expected_value ) {
		$cf7_crm = array(
			'fc_crm_type'              => 'clientify',
			'fc_crm_apipassword'       => 'api-password',
			'fc_crm_module'            => 'Contacts',
			'fc_crm_field-gdpr_accept' => 'extra-info',
		);

		$submitted_data = array(
			'extra-info' => $submitted_value,
		);

		$merge_vars = FORMSCRM_CF7_Settings::get_merge_vars( $cf7_crm, $submitted_data );

		$this->assertCount( 1, $merge_vars );
		$this->assertSame( 'gdpr_accept', $merge_vars[0]['name'] );
		$this->assertIsBool( $merge_vars[0]['value'] );
		$this->assertSame( $expected_value, $merge_vars[0]['value'] );
	}
}
