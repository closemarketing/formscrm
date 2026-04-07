<?php
/**
 * Class ContactFormsTest
 * 
 * Command: composer test-debug --filter ContactFormsTest
 *
 * @package Formscrm
 */

class ContactFormsTest extends WP_UnitTestCase {
	public function setUp() {
		parent::setUp();
		$this->contact_form = WPCF7_ContactForm::create();
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

		$merge_vars = $this->contact_form->get_merge_vars( $cf7_crm, $submitted_data );
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

		$merge_vars = $this->contact_form->get_merge_vars( $cf7_crm, $submitted_data );
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

		$merge_vars = $this->contact_form->get_merge_vars( $cf7_crm, $submitted_data );
		$this->assertEquals(
			array( array( 'name' => 'gdpr_accept', 'value' => 'Me gustaría estar al tanto de las novedades de Ipace' ) ),
			$merge_vars
		);
	}
}
