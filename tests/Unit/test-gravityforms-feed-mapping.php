<?php
/**
 * Class GravityFormsFeedMappingTest
 *
 * Tests for the field-mapping preservation and JSON export/import helpers used
 * when a Gravity Forms feed's CRM connection or module changes. Gravity Forms
 * stores a field_map setting as individual "{name}_{key}" meta entries, not as
 * a single nested array, so these helpers read/write that flat format via
 * formscrm_gf_get_prefixed_map() / formscrm_gf_set_prefixed_map().
 *
 * Command: composer test-debug --filter GravityFormsFeedMappingTest
 *
 * @package Formscrm
 */

/**
 * Unit tests for the Gravity Forms feed field-map helpers.
 */
class GravityFormsFeedMappingTest extends WP_UnitTestCase {

	// -------------------------------------------------------------------------
	// formscrm_gf_get_prefixed_map() / formscrm_gf_set_prefixed_map()
	// -------------------------------------------------------------------------

	/**
	 * Reconstructs a flat map from "{prefix}{key}" entries, ignoring unrelated keys.
	 */
	public function test_get_prefixed_map_reads_matching_keys() {
		$source = array(
			'listFields_email' => '3',
			'listFields_phone' => '4',
			'fc_crm_module'    => 'contacts',
			'listFields_empty' => '',
		);

		$this->assertSame(
			array(
				'email' => '3',
				'phone' => '4',
				'empty' => '',
			),
			formscrm_gf_get_prefixed_map( $source, 'listFields_' )
		);
	}

	/**
	 * Ignores non-scalar values so a stray nested array doesn't leak through.
	 */
	public function test_get_prefixed_map_ignores_non_scalar_values() {
		$source = array(
			'listFields_email' => '3',
			'listFields_broken' => array( 'key' => 'x', 'value' => 'y' ),
		);

		$this->assertSame( array( 'email' => '3' ), formscrm_gf_get_prefixed_map( $source, 'listFields_' ) );
	}

	/**
	 * Replaces every existing prefixed key with the given values, dropping ones not provided.
	 */
	public function test_set_prefixed_map_replaces_existing_entries() {
		$target = array(
			'listFields_email'  => '3',
			'listFields_old_key' => '9', // Belonged to a previous module; must be dropped.
			'fc_crm_module'     => 'contacts',
		);

		$result = formscrm_gf_set_prefixed_map( $target, 'listFields_', array( 'email' => '5', 'phone' => '4' ) );

		$this->assertSame( '5', $result['listFields_email'] );
		$this->assertSame( '4', $result['listFields_phone'] );
		$this->assertArrayNotHasKey( 'listFields_old_key', $result );
		$this->assertSame( 'contacts', $result['fc_crm_module'] );
	}

	/**
	 * Does not write an entry for an empty value.
	 */
	public function test_set_prefixed_map_skips_empty_values() {
		$result = formscrm_gf_set_prefixed_map( array(), 'listFields_', array( 'email' => '', 'phone' => '4' ) );

		$this->assertArrayNotHasKey( 'listFields_email', $result );
		$this->assertSame( '4', $result['listFields_phone'] );
	}

	// -------------------------------------------------------------------------
	// formscrm_gf_build_field_map_labels()
	// -------------------------------------------------------------------------

	/**
	 * Builds a key => label snapshot from a field map.
	 */
	public function test_build_field_map_labels() {
		$field_map = array(
			array( 'name' => 'email', 'label' => 'Email Main' ),
			array( 'name' => 'first_name', 'label' => 'Contact first name' ),
			array( 'name' => 'no_label' ),
		);

		$this->assertSame(
			array(
				'email'      => 'Email Main',
				'first_name' => 'Contact first name',
				'no_label'   => '',
			),
			formscrm_gf_build_field_map_labels( $field_map )
		);
	}

	/**
	 * Returns an empty array for an empty field map.
	 */
	public function test_build_field_map_labels_empty() {
		$this->assertSame( array(), formscrm_gf_build_field_map_labels( array() ) );
	}

	// -------------------------------------------------------------------------
	// formscrm_gf_merge_field_map()
	// -------------------------------------------------------------------------

	/**
	 * A value already present in the current submission is never overwritten.
	 */
	public function test_merge_keeps_current_value_when_present() {
		$field_map = array( array( 'name' => 'email', 'label' => 'Email Main' ) );

		$merged = formscrm_gf_merge_field_map( $field_map, array( 'email' => '5' ), array( 'email' => '9' ), array( 'email' => 'Email Main' ) );

		$this->assertSame( '5', $merged['email'] );
	}

	/**
	 * A key that exists in both the old and new field maps carries its old value
	 * over when the current submission has no value for it — the standard-fields
	 * case (e.g. two connections of the same CRM/module sharing the 'email' key).
	 */
	public function test_merge_preserves_value_by_matching_key() {
		$field_map = array(
			array( 'name' => 'email', 'label' => 'Email Main' ),
			array( 'name' => 'phone', 'label' => 'Phone Main' ),
		);

		$merged = formscrm_gf_merge_field_map(
			$field_map,
			array(), // Nothing submitted yet (module/connection just changed).
			array( 'email' => '3', 'phone' => '4' ),
			array( 'email' => 'Email Main', 'phone' => 'Phone Main' )
		);

		$this->assertSame( '3', $merged['email'] );
		$this->assertSame( '4', $merged['phone'] );
	}

	/**
	 * A key from the OLD map that no longer exists in the NEW map is dropped —
	 * it must not linger as a stale mapping that process_feed() would still send.
	 */
	public function test_merge_drops_keys_not_in_new_field_map() {
		$field_map = array( array( 'name' => 'email', 'label' => 'Email Main' ) );

		$merged = formscrm_gf_merge_field_map(
			$field_map,
			array( 'email' => '3', 'old_module_only_field' => '7' ),
			array( 'old_module_only_field' => '7' ),
			array( 'old_module_only_field' => 'Old Field' )
		);

		$this->assertSame( array( 'email' => '3' ), $merged );
	}

	/**
	 * When the key changes (different connection/module) but the label matches
	 * the previous snapshot, the old value is preserved under the new key.
	 */
	public function test_merge_preserves_value_by_matching_label() {
		$field_map = array(
			array( 'name' => 'contact_email', 'label' => 'Email Main' ),
		);

		$merged = formscrm_gf_merge_field_map(
			$field_map,
			array(),
			array( 'email' => '3' ),
			array( 'email' => 'Email Main' )
		);

		$this->assertSame( '3', $merged['contact_email'] );
	}

	/**
	 * Label matching is case-insensitive and trims surrounding whitespace.
	 */
	public function test_merge_label_match_is_case_insensitive() {
		$field_map = array(
			array( 'name' => 'contact_email', 'label' => '  email main  ' ),
		);

		$merged = formscrm_gf_merge_field_map(
			$field_map,
			array(),
			array( 'email' => '3' ),
			array( 'email' => 'Email Main' )
		);

		$this->assertSame( '3', $merged['contact_email'] );
	}

	/**
	 * A field with no old key match and no label match is left unset.
	 */
	public function test_merge_leaves_unmatched_field_empty() {
		$field_map = array(
			array( 'name' => 'custom_field', 'label' => 'Something new' ),
		);

		$merged = formscrm_gf_merge_field_map( $field_map, array(), array( 'email' => '3' ), array( 'email' => 'Email Main' ) );

		$this->assertArrayNotHasKey( 'custom_field', $merged );
	}

	/**
	 * Fields without a 'name' are skipped without notices.
	 */
	public function test_merge_skips_fields_without_name() {
		$field_map = array( array( 'label' => 'No name here' ) );

		$merged = formscrm_gf_merge_field_map( $field_map, array(), array(), array() );

		$this->assertSame( array(), $merged );
	}

	/**
	 * Non-array arguments are tolerated and treated as empty.
	 */
	public function test_merge_tolerates_non_array_arguments() {
		$field_map = array( array( 'name' => 'email', 'label' => 'Email Main' ) );

		$merged = formscrm_gf_merge_field_map( $field_map, null, null, null );

		$this->assertSame( array(), $merged );
	}

	// -------------------------------------------------------------------------
	// formscrm_gf_field_prop() / formscrm_gf_build_form_field_labels()
	// -------------------------------------------------------------------------

	/**
	 * Reads a property from a plain-array field (e.g. after a JSON round trip).
	 */
	public function test_field_prop_reads_array_field() {
		$field = array( 'id' => '3', 'label' => 'Email' );

		$this->assertSame( '3', formscrm_gf_field_prop( $field, 'id' ) );
		$this->assertSame( '', formscrm_gf_field_prop( $field, 'missing' ) );
	}

	/**
	 * Reads a property from an object field (a real GF_Field instance).
	 */
	public function test_field_prop_reads_object_field() {
		$field = (object) array( 'id' => 3, 'label' => 'Email' );

		$this->assertSame( 3, formscrm_gf_field_prop( $field, 'id' ) );
	}

	/**
	 * Builds a field id => label map, including sub-inputs of a composite field.
	 */
	public function test_build_form_field_labels() {
		$form = array(
			'fields' => array(
				array( 'id' => '1', 'label' => 'Email' ),
				array(
					'id'     => '2',
					'label'  => 'Name',
					'inputs' => array(
						array( 'id' => '2.3', 'label' => 'First' ),
						array( 'id' => '2.6', 'label' => 'Last' ),
					),
				),
			),
		);

		$labels = formscrm_gf_build_form_field_labels( $form );

		$this->assertSame( 'Email', $labels['1'] );
		$this->assertSame( 'Name First', $labels['2.3'] );
		$this->assertSame( 'Name Last', $labels['2.6'] );
	}

	/**
	 * Returns an empty array when the form has no fields.
	 */
	public function test_build_form_field_labels_empty_form() {
		$this->assertSame( array(), formscrm_gf_build_form_field_labels( array() ) );
	}

	// -------------------------------------------------------------------------
	// formscrm_gf_build_feed_export()
	// -------------------------------------------------------------------------

	/**
	 * Builds the exportable structure from a feed, reading the field map from its
	 * flat "listFields_{key}" meta entries, and excludes credentials.
	 */
	public function test_build_feed_export_shape() {
		$feed = array(
			'form_id' => 7,
			'meta'    => array(
				'feedName'                  => 'My Feed',
				'fc_crm_custom_type'        => 'clientify',
				'fc_crm_module'             => 'contacts',
				'fc_crm_merge_entry'        => 'email',
				'fc_crm_webhook'            => 'https://example.com/hook',
				'listFields_email'          => '3',
				'listFieldsCustom_source'   => 'Website',
				'listFields_source'         => 'gf_custom',
				'listFields_labels'         => array( 'email' => 'Email Main' ),
				'optin'                     => array( 'enabled' => false ),
				'fc_crm_custom_apipassword' => 'super-secret-key',
			),
		);
		$form = array( 'id' => 7, 'fields' => array( array( 'id' => '3', 'label' => 'Email' ) ) );

		$export = formscrm_gf_build_feed_export( $feed, $form, 'clientify' );

		$this->assertTrue( $export['formscrm_feed_export'] );
		$this->assertSame( 'My Feed', $export['feed_name'] );
		$this->assertSame( 'clientify', $export['fc_crm_custom_type'] );
		$this->assertSame( 'clientify', $export['fc_crm_type_resolved'] );
		$this->assertSame( 'contacts', $export['fc_crm_module'] );
		$this->assertSame( array( 'email' => '3', 'source' => 'gf_custom' ), $export['listFields'] );
		$this->assertSame( array( 'source' => 'Website' ), $export['listFieldsCustom'] );
		$this->assertSame( array( 'email' => 'Email Main' ), $export['listFields_labels'] );
		$this->assertSame( 7, $export['source_form_id'] );
		$this->assertSame( array( '3' => 'Email' ), $export['source_fields'] );

		// Credentials must never be part of the export.
		$this->assertArrayNotHasKey( 'fc_crm_custom_apipassword', $export );
	}

	/**
	 * Handles a feed with no meta gracefully.
	 */
	public function test_build_feed_export_handles_missing_meta() {
		$export = formscrm_gf_build_feed_export( array() );

		$this->assertTrue( $export['formscrm_feed_export'] );
		$this->assertSame( '', $export['feed_name'] );
		$this->assertSame( array(), $export['listFields'] );
		$this->assertSame( 0, $export['source_form_id'] );
	}

	// -------------------------------------------------------------------------
	// formscrm_gf_feed_meta_from_import()
	// -------------------------------------------------------------------------

	/**
	 * Rejects data that isn't a recognised feed export.
	 */
	public function test_import_rejects_invalid_data() {
		$this->assertFalse( formscrm_gf_feed_meta_from_import( null ) );
		$this->assertFalse( formscrm_gf_feed_meta_from_import( 'not-an-array' ) );
		$this->assertFalse( formscrm_gf_feed_meta_from_import( array( 'feed_name' => 'Missing marker' ) ) );
	}

	/**
	 * Rejects an import naming a CRM connector that isn't available on this site
	 * rather than saving a feed that would fatal on every submission.
	 */
	public function test_import_rejects_unresolvable_crm_type() {
		$import = array(
			'formscrm_feed_export' => true,
			'fc_crm_custom_type'   => 'this_crm_does_not_exist',
		);

		$this->assertFalse( formscrm_gf_feed_meta_from_import( $import ) );
	}

	/**
	 * Accepts "no" (use the site's shared/default CRM) without resolving a connector.
	 */
	public function test_import_accepts_shared_connection_sentinel() {
		$import = array(
			'formscrm_feed_export' => true,
			'fc_crm_custom_type'   => 'no',
		);

		$meta = formscrm_gf_feed_meta_from_import( $import );

		$this->assertIsArray( $meta );
		$this->assertSame( 'no', $meta['fc_crm_custom_type'] );
	}

	/**
	 * Accepts a feed-level connection naming a CRM that IS available (built-in connector).
	 */
	public function test_import_accepts_resolvable_crm_type() {
		$import = array(
			'formscrm_feed_export' => true,
			'fc_crm_custom_type'   => 'clientify',
		);

		$meta = formscrm_gf_feed_meta_from_import( $import );

		$this->assertIsArray( $meta );
		$this->assertSame( 'clientify', $meta['fc_crm_custom_type'] );
	}

	/**
	 * Sanitizes a valid export back into the feed's simple scalar meta. The field
	 * map itself is handled separately by formscrm_gf_reconcile_imported_field_map().
	 */
	public function test_import_builds_sanitized_scalar_meta() {
		$import = array(
			'formscrm_feed_export' => true,
			'feed_name'            => 'Imported Feed',
			'fc_crm_custom_type'   => 'no',
			'fc_crm_module'        => 'contacts',
			'fc_crm_merge_entry'   => 'email',
			'fc_crm_webhook'       => 'https://example.com/hook',
			'optin'                => array( 'enabled' => false ),
		);

		$meta = formscrm_gf_feed_meta_from_import( $import );

		$this->assertSame( 'Imported Feed', $meta['feedName'] );
		$this->assertSame( 'contacts', $meta['fc_crm_module'] );
		$this->assertSame( 'email', $meta['fc_crm_merge_entry'] );
		$this->assertSame( 'https://example.com/hook', $meta['fc_crm_webhook'] );
		$this->assertArrayNotHasKey( 'listFields', $meta );
	}

	// -------------------------------------------------------------------------
	// formscrm_gf_reconcile_imported_field_map()
	// -------------------------------------------------------------------------

	/**
	 * Importing onto the SAME form keeps the original Gravity Forms field ids unchanged.
	 */
	public function test_reconcile_same_form_keeps_field_ids() {
		$import_data = array(
			'listFields'      => array( 'email' => '3' ),
			'source_form_id'  => 7,
			'source_fields'   => array( '3' => 'Email' ),
		);
		$target_form      = array( 'id' => 7, 'fields' => array( array( 'id' => '3', 'label' => 'Email' ) ) );
		$target_field_map = array( array( 'name' => 'email', 'label' => 'Email Main' ) );

		$result = formscrm_gf_reconcile_imported_field_map( $import_data, $target_form, $target_field_map, array(), array(), array() );

		$this->assertSame( '3', $result['values']['email'] );
	}

	/**
	 * Importing into a DIFFERENT form remaps the Gravity Forms field id by label,
	 * since a bare field id from another form would otherwise point at an unrelated field.
	 */
	public function test_reconcile_cross_form_remaps_by_label() {
		$import_data = array(
			'listFields'     => array( 'email' => '3' ),
			'source_form_id' => 7,
			'source_fields'  => array( '3' => 'Email Address' ),
		);
		// Target form: same label, but a different field id (9).
		$target_form      = array( 'id' => 42, 'fields' => array( array( 'id' => '9', 'label' => 'Email Address' ) ) );
		$target_field_map = array( array( 'name' => 'email', 'label' => 'Email Main' ) );

		$result = formscrm_gf_reconcile_imported_field_map( $import_data, $target_form, $target_field_map, array(), array(), array() );

		$this->assertSame( '9', $result['values']['email'] );
	}

	/**
	 * A cross-form import leaves a CRM field unmapped, rather than pointing at an
	 * unrelated field, when no field in the target form has a matching label.
	 */
	public function test_reconcile_cross_form_drops_unmatched_field() {
		$import_data = array(
			'listFields'     => array( 'email' => '3' ),
			'source_form_id' => 7,
			'source_fields'  => array( '3' => 'Email Address' ),
		);
		$target_form      = array( 'id' => 42, 'fields' => array( array( 'id' => '9', 'label' => 'Completely Different Field' ) ) );
		$target_field_map = array( array( 'name' => 'email', 'label' => 'Email Main' ) );

		$result = formscrm_gf_reconcile_imported_field_map( $import_data, $target_form, $target_field_map, array(), array(), array() );

		$this->assertArrayNotHasKey( 'email', $result['values'] );
	}

	/**
	 * A fixed ("Add Custom Value") mapping has no form field to remap and is
	 * carried over unchanged (and its text with it) even across a cross-form import.
	 */
	public function test_reconcile_cross_form_keeps_custom_values() {
		$import_data = array(
			'listFields'       => array( 'source' => 'gf_custom' ),
			'listFieldsCustom' => array( 'source' => 'Website' ),
			'source_form_id'   => 7,
			'source_fields'    => array(),
		);
		$target_form      = array( 'id' => 42, 'fields' => array() );
		$target_field_map = array( array( 'name' => 'source', 'label' => 'Source' ) );

		$result = formscrm_gf_reconcile_imported_field_map( $import_data, $target_form, $target_field_map, array(), array(), array() );

		$this->assertSame( 'gf_custom', $result['values']['source'] );
		$this->assertSame( 'Website', $result['custom']['source'] );
	}

	/**
	 * A CRM field the import doesn't define keeps the target feed's existing mapping
	 * instead of being cleared.
	 */
	public function test_reconcile_keeps_existing_value_not_covered_by_import() {
		$import_data      = array( 'listFields' => array( 'email' => '3' ) );
		$target_form      = array( 'id' => 7, 'fields' => array() );
		$target_field_map = array(
			array( 'name' => 'email', 'label' => 'Email Main' ),
			array( 'name' => 'phone', 'label' => 'Phone Main' ),
		);
		$existing_values = array( 'phone' => '4' );

		$result = formscrm_gf_reconcile_imported_field_map( $import_data, $target_form, $target_field_map, $existing_values, array(), array() );

		$this->assertSame( '3', $result['values']['email'] );
		$this->assertSame( '4', $result['values']['phone'] );
	}

	// -------------------------------------------------------------------------
	// Export -> import round trip
	// -------------------------------------------------------------------------

	/**
	 * A round trip through export then reconcile-import (same form) preserves the field mapping.
	 */
	public function test_export_then_import_round_trip_same_form() {
		$feed = array(
			'form_id' => 7,
			'meta'    => array(
				'feedName'          => 'Round Trip',
				'fc_crm_module'     => 'contacts',
				'listFields_email'  => '3',
				'listFields_labels' => array( 'email' => 'Email Main' ),
			),
		);
		$form = array( 'id' => 7, 'fields' => array( array( 'id' => '3', 'label' => 'Email' ) ) );

		$export = formscrm_gf_build_feed_export( $feed, $form );
		$meta   = formscrm_gf_feed_meta_from_import( $export );

		$this->assertIsArray( $meta );

		$field_map = array( array( 'name' => 'email', 'label' => 'Email Main' ) );
		$result    = formscrm_gf_reconcile_imported_field_map( $export, $form, $field_map, array(), array(), array() );

		$this->assertSame( '3', $result['values']['email'] );
	}
}
