<?php
/**
 * Tests for Elementor merge vars field mapping logic.
 *
 * Command: vendor/bin/phpunit --filter ElementorFormsTest
 *
 * @package Formscrm
 */

/**
 * Class ElementorFormsTest
 *
 * @see FormsCRM_Elementor_Action_After_Submit::get_merge_vars()
 */
class ElementorFormsTest extends WP_UnitTestCase {

	/**
	 * Basic field mapping: each CRM field maps to a distinct form field.
	 */
	public function test_get_merge_vars_basic() {
		$formscrm_settings = array(
			'fc_crm_field-first_name' => 'form-name',
			'fc_crm_field-email'      => 'form-email',
		);

		$raw_fields = array(
			'form-name'  => array( 'value' => 'David' ),
			'form-email' => array( 'value' => 'david@example.com' ),
		);

		$merge_vars = FormsCRM_Elementor_Action_After_Submit::get_merge_vars( $formscrm_settings, $raw_fields );

		$this->assertEquals(
			array(
				array(
					'name'  => 'first_name',
					'value' => 'David',
				),
				array(
					'name'  => 'email',
					'value' => 'david@example.com',
				),
			),
			$merge_vars
		);
	}

	/**
	 * Duplicate form field: two CRM fields mapped to the same form field.
	 *
	 * Before the fix, iterating $raw_fields with array_search() would stop at
	 * the first matching key and skip subsequent mappings for the same form field.
	 *
	 * @see https://github.com/closemarketing/formscrm/issues/179
	 */
	public function test_get_merge_vars_duplicate_form_field_mapping() {
		$formscrm_settings = array(
			'fc_crm_field-first_name'                    => 'form-name',
			'fc_crm_field-email'                         => 'form-email',
			'fc_crm_field-custom_fields|category'        => 'form-category',
			'fc_crm_field-custom_fields|category_backup' => 'form-category', // Same form field, different CRM field.
		);

		$raw_fields = array(
			'form-name'     => array( 'value' => 'David' ),
			'form-email'    => array( 'value' => 'david@example.com' ),
			'form-category' => array( 'value' => 'Technology' ),
		);

		$merge_vars = FormsCRM_Elementor_Action_After_Submit::get_merge_vars( $formscrm_settings, $raw_fields );

		$this->assertCount( 4, $merge_vars );
		$this->assertEquals(
			array(
				'name'  => 'first_name',
				'value' => 'David',
			),
			$merge_vars[0]
		);
		$this->assertEquals(
			array(
				'name'  => 'email',
				'value' => 'david@example.com',
			),
			$merge_vars[1]
		);
		$this->assertEquals(
			array(
				'name'  => 'custom_fields|category',
				'value' => 'Technology',
			),
			$merge_vars[2]
		);
		$this->assertEquals(
			array(
				'name'  => 'custom_fields|category_backup',
				'value' => 'Technology',
			),
			$merge_vars[3]
		);
	}

	/**
	 * Array field value (e.g. multi-select or checkbox group) is joined with ', '.
	 */
	public function test_get_merge_vars_array_value_joined() {
		$formscrm_settings = array(
			'fc_crm_field-interests' => 'form-interests',
		);

		$raw_fields = array(
			'form-interests' => array( 'value' => array( 'Sports', 'Music', 'Tech' ) ),
		);

		$merge_vars = FormsCRM_Elementor_Action_After_Submit::get_merge_vars( $formscrm_settings, $raw_fields );

		$this->assertEquals(
			array(
				array(
					'name'  => 'interests',
					'value' => 'Sports, Music, Tech',
				),
			),
			$merge_vars
		);
	}

	/**
	 * Missing form field in raw_fields falls back to empty string.
	 */
	public function test_get_merge_vars_missing_field_returns_empty_string() {
		$formscrm_settings = array(
			'fc_crm_field-phone' => 'form-phone',
		);

		$raw_fields = array(); // Field not present in submission.

		$merge_vars = FormsCRM_Elementor_Action_After_Submit::get_merge_vars( $formscrm_settings, $raw_fields );

		$this->assertEquals(
			array(
				array(
					'name'  => 'phone',
					'value' => '',
				),
			),
			$merge_vars
		);
	}
}
