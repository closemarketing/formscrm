<?php
/**
 * Class FormidableFormsTest
 *
 * Command: composer test-debug --filter FormidableFormsTest
 *
 * @package Formscrm
 */

/**
 * Formidable Forms field-mapping tests.
 *
 * Formidable Forms itself is not a test dependency, so these tests exercise
 * FORMSCRM_FormidableForms's pure data-shaping methods directly with
 * duck-typed field/entry objects instead of real FrmField/FrmEntry instances.
 *
 * @see FORMSCRM_FormidableForms::get_field_values()
 * @see FORMSCRM_FormidableForms::get_merge_vars()
 */
class FormidableFormsTest extends WP_UnitTestCase {

	/**
	 * Builds a duck-typed stand-in for a Formidable FrmField object.
	 *
	 * @param int    $id        Field ID.
	 * @param string $type      Field type.
	 * @param string $field_key Field key.
	 * @return object
	 */
	private function make_field( $id, $type, $field_key ) {
		$field            = new stdClass();
		$field->id        = $id;
		$field->type      = $type;
		$field->field_key = $field_key;
		return $field;
	}

	/**
	 * Builds a duck-typed stand-in for a Formidable FrmEntry object.
	 *
	 * @param array $metas Entry meta, keyed by field ID.
	 * @return object
	 */
	private function make_entry( array $metas ) {
		$entry        = new stdClass();
		$entry->metas = $metas;
		return $entry;
	}

	/**
	 * Non-data field types must not appear in the built field map.
	 */
	public function test_get_field_values_skips_non_data_field_types() {
		$fields = array(
			$this->make_field( 1, 'text', 'first_name' ),
			$this->make_field( 2, 'divider', 'divider_1' ),
			$this->make_field( 3, 'html', 'html_1' ),
			$this->make_field( 4, 'captcha', 'captcha_1' ),
		);
		$entry = $this->make_entry(
			array(
				1 => 'David',
				2 => '',
				3 => '',
				4 => '',
			)
		);

		$values = FORMSCRM_FormidableForms::get_field_values( $entry, $fields );

		$this->assertArrayHasKey( 'first_name', $values );
		$this->assertSame( 'David', $values['first_name'] );
		$this->assertArrayNotHasKey( 'divider_1', $values );
		$this->assertArrayNotHasKey( 'html_1', $values );
		$this->assertArrayNotHasKey( 'captcha_1', $values );
	}

	/**
	 * Each value is stored under both its field key and numeric ID.
	 */
	public function test_get_field_values_indexes_by_key_and_id() {
		$fields = array( $this->make_field( 10, 'text', 'email' ) );
		$entry  = $this->make_entry( array( 10 => 'david@close.marketing' ) );

		$values = FORMSCRM_FormidableForms::get_field_values( $entry, $fields );

		$this->assertSame( 'david@close.marketing', $values['email'] );
		$this->assertSame( 'david@close.marketing', $values[10] );
	}

	/**
	 * Multi-value fields (checkboxes, multi-select) are flattened to a comma-joined string.
	 */
	public function test_get_field_values_flattens_multi_value_fields() {
		$fields = array( $this->make_field( 5, 'checkbox', 'interests' ) );
		$entry  = $this->make_entry( array( 5 => array( 'CRM', 'Marketing', 'ERP' ) ) );

		$values = FORMSCRM_FormidableForms::get_field_values( $entry, $fields );

		$this->assertSame( 'CRM,Marketing,ERP', $values['interests'] );
	}

	/**
	 * File fields resolve their stored attachment ID to a public URL.
	 */
	public function test_get_field_values_resolves_file_field_to_attachment_url() {
		$attachment_id = self::factory()->attachment->create();
		$expected_url  = wp_get_attachment_url( $attachment_id );

		$fields = array( $this->make_field( 7, 'file', 'resume' ) );
		$entry  = $this->make_entry( array( 7 => $attachment_id ) );

		$values = FORMSCRM_FormidableForms::get_field_values( $entry, $fields );

		$this->assertSame( $expected_url, $values['resume'] );
	}

	/**
	 * An entry with no metas for a field yields an empty string, not a missing key.
	 */
	public function test_get_field_values_defaults_missing_meta_to_empty_string() {
		$fields = array( $this->make_field( 1, 'text', 'company' ) );
		$entry  = $this->make_entry( array() );

		$values = FORMSCRM_FormidableForms::get_field_values( $entry, $fields );

		$this->assertSame( '', $values['company'] );
	}

	/**
	 * Only settings keyed 'fc_crm_field-*' become merge vars, mapped from the built field-value map.
	 */
	public function test_get_merge_vars_maps_configured_fields_only() {
		$frm_crm = array(
			'fc_crm_type'         => 'clientify',
			'fc_crm_apipassword'  => 'api-password',
			'fc_crm_module'       => 'Contacts',
			'fc_crm_field-email'  => 'your-email',
			'fc_crm_field-phone'  => 'your-phone',
		);

		$field_values = array(
			'your-email' => 'david@close.marketing',
			'your-phone' => '666666666',
			'your-notes' => 'Not mapped to any CRM field.',
		);

		$merge_vars = FORMSCRM_FormidableForms::get_merge_vars( $frm_crm, $field_values );

		$this->assertEqualsCanonicalizing(
			array(
				array(
					'name'  => 'email',
					'value' => 'david@close.marketing',
				),
				array(
					'name'  => 'phone',
					'value' => '666666666',
				),
			),
			$merge_vars
		);
	}

	/**
	 * A field mapped to a Formidable field with no submitted value maps to an empty string.
	 */
	public function test_get_merge_vars_defaults_unmapped_value_to_empty_string() {
		$frm_crm = array(
			'fc_crm_type'        => 'clientify',
			'fc_crm_field-email' => 'your-email',
		);

		$merge_vars = FORMSCRM_FormidableForms::get_merge_vars( $frm_crm, array() );

		$this->assertSame(
			array(
				array(
					'name'  => 'email',
					'value' => '',
				),
			),
			$merge_vars
		);
	}

	/**
	 * Non-array settings return no merge vars instead of raising a notice.
	 */
	public function test_get_merge_vars_returns_empty_array_for_empty_settings() {
		$this->assertSame( array(), FORMSCRM_FormidableForms::get_merge_vars( array(), array() ) );
	}
}
