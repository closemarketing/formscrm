<?php
/**
 * Class ContactFormsTest
 * 
 * Command: composer test-debug --filter ContactFormsTest
 *
 * @package Formscrm
 */

class ContactFormsTest extends WP_UnitTestCase {

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
	 * GDPR checkbox unchecked: CF7 submits empty array, value must be '' not the field name.
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
			array( array( 'name' => 'gdpr_accept', 'value' => '' ) ),
			$merge_vars
		);
	}

	/**
	 * GDPR checkbox checked: CF7 submits the label string, value must be non-empty.
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
			array( array( 'name' => 'gdpr_accept', 'value' => '1' ) ),
			$merge_vars
		);
	}

	/**
	 * Data provider with falsy submitted values for gdpr_accept.
	 *
	 * @return array
	 */
	public function gdpr_falsy_values_provider() {
		return array(
			// Scalar strings — CF7 hidden/text fields.
			'empty string'     => array( '',        '' ),
			'zero string'      => array( '0',       '0' ),
			'false string'     => array( 'false',   'false' ),
			// Arrays — CF7 checkbox fields (unchecked = empty array, checked = array of labels).
			'empty array'      => array( array(),           '' ),
			'array with 0'     => array( array( '0' ),      '0' ),
			'array with false' => array( array( 'false' ),  'false' ),
		);
	}

	/**
	 * GDPR field with various falsy submitted values: none should produce the field name as value.
	 *
	 * @dataProvider gdpr_falsy_values_provider
	 * @param mixed  $submitted_value Raw value as CF7 would submit it.
	 * @param string $expected_value  Expected string value in merge vars.
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
		$this->assertEquals( 'gdpr_accept', $merge_vars[0]['name'] );
		$this->assertEquals( $expected_value, $merge_vars[0]['value'] );
		// Value must never equal the form field name ('extra-info').
		$this->assertNotEquals( 'extra-info', $merge_vars[0]['value'] );
	}
}
