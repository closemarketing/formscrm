<?php
/**
 * Class SureFormsTest
 *
 * Command: composer test-debug --filter SureFormsTest
 *
 * @package Formscrm
 */

class SureFormsTest extends WP_UnitTestCase {

	public function test_get_merge_vars() {
		$settings = array(
			'fc_crm_type'              => 'clientify',
			'fc_crm_apipassword'       => 'api-password',
			'fc_crm_module'            => 'Contacts',
			'fc_crm_field-first_name'  => 'Your Name',
			'fc_crm_field-email'       => 'Your Email',
			'fc_crm_field-phone'       => 'Your Phone',
		);

		$submitted_data = array(
			'Your Name'    => 'david',
			'Your Email'   => 'david@close.marketing',
			'Your Phone'   => '66666666',
			'Your Message' => 'Hello there.',
		);

		$merge_vars = FORMSCRM_SureForms::get_merge_vars( $settings, $submitted_data );

		$this->assertEquals(
			array(
				array( 'name' => 'first_name', 'value' => 'david' ),
				array( 'name' => 'email', 'value' => 'david@close.marketing' ),
				array( 'name' => 'phone', 'value' => '66666666' ),
			),
			$merge_vars
		);
	}

	/**
	 * A field mapped to a label missing from the submission is sent as a literal value.
	 */
	public function test_get_merge_vars_literal_fallback() {
		$settings = array(
			'fc_crm_type'             => 'clientify',
			'fc_crm_module'           => 'Contacts',
			'fc_crm_field-source'     => 'Website',
		);

		$merge_vars = FORMSCRM_SureForms::get_merge_vars( $settings, array() );

		$this->assertEquals(
			array( array( 'name' => 'source', 'value' => 'Website' ) ),
			$merge_vars
		);
	}

	/**
	 * Array values (e.g. checkbox groups) are imploded into a single string.
	 */
	public function test_get_merge_vars_array_value() {
		$settings = array(
			'fc_crm_type'          => 'clientify',
			'fc_crm_module'        => 'Contacts',
			'fc_crm_field-topics'  => 'Topics of Interest',
		);

		$submitted_data = array(
			'Topics of Interest' => array( 'Sales', 'Support' ),
		);

		$merge_vars = FORMSCRM_SureForms::get_merge_vars( $settings, $submitted_data );

		$this->assertEquals(
			array( array( 'name' => 'topics', 'value' => 'Sales,Support' ) ),
			$merge_vars
		);
	}

	public function test_get_merge_vars_empty_settings() {
		$this->assertEquals( array(), FORMSCRM_SureForms::get_merge_vars( array(), array() ) );
	}
}
