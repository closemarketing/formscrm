<?php
/**
 * Class GravityFormsFeedMappingTest
 *
 * Tests for the field-mapping preservation and JSON export/import helpers used
 * when a Gravity Forms feed's CRM connection or module changes.
 *
 * Command: composer test-debug --filter GravityFormsFeedMappingTest
 *
 * @package Formscrm
 */

/**
 * Unit tests for formscrm_gf_build_field_map_labels(), formscrm_gf_merge_field_map(),
 * formscrm_gf_build_feed_export() and formscrm_gf_feed_meta_from_import().
 */
class GravityFormsFeedMappingTest extends WP_UnitTestCase {

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
	// formscrm_gf_merge_field_map() — same key across connections
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

	// -------------------------------------------------------------------------
	// formscrm_gf_merge_field_map() — label fallback across a connection change
	// -------------------------------------------------------------------------

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
	// formscrm_gf_build_feed_export()
	// -------------------------------------------------------------------------

	/**
	 * Builds the exportable structure from a feed and excludes credentials.
	 */
	public function test_build_feed_export_shape() {
		$feed = array(
			'meta' => array(
				'feedName'           => 'My Feed',
				'fc_crm_custom_type' => 'clientify',
				'fc_crm_module'      => 'contacts',
				'fc_crm_merge_entry' => 'email',
				'fc_crm_webhook'     => 'https://example.com/hook',
				'listFields'         => array( 'email' => '3' ),
				'listFields_labels'  => array( 'email' => 'Email Main' ),
				'optin'              => array( 'enabled' => false ),
				'fc_crm_custom_apipassword' => 'super-secret-key',
			),
		);

		$export = formscrm_gf_build_feed_export( $feed );

		$this->assertTrue( $export['formscrm_feed_export'] );
		$this->assertSame( 1, $export['schema_version'] );
		$this->assertSame( 'My Feed', $export['feed_name'] );
		$this->assertSame( 'clientify', $export['fc_crm_custom_type'] );
		$this->assertSame( 'contacts', $export['fc_crm_module'] );
		$this->assertSame( array( 'email' => '3' ), $export['listFields'] );
		$this->assertSame( array( 'email' => 'Email Main' ), $export['listFields_labels'] );

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
	 * Sanitizes a valid export back into feed meta.
	 */
	public function test_import_builds_sanitized_meta() {
		$import = array(
			'formscrm_feed_export' => true,
			'schema_version'       => 1,
			'feed_name'            => 'Imported Feed',
			'fc_crm_custom_type'   => 'clientify',
			'fc_crm_module'        => 'contacts',
			'fc_crm_merge_entry'   => 'email',
			'fc_crm_webhook'       => 'https://example.com/hook',
			'listFields'           => array( 'email' => '3' ),
			'listFields_labels'    => array( 'email' => 'Email Main' ),
			'optin'                => array( 'enabled' => false ),
		);

		$meta = formscrm_gf_feed_meta_from_import( $import );

		$this->assertSame( 'Imported Feed', $meta['feedName'] );
		$this->assertSame( 'clientify', $meta['fc_crm_custom_type'] );
		$this->assertSame( 'contacts', $meta['fc_crm_module'] );
		$this->assertSame( array( 'email' => '3' ), $meta['listFields'] );
		$this->assertSame( array( 'email' => 'Email Main' ), $meta['listFields_labels'] );
	}

	/**
	 * A round trip through export then import preserves the field mapping.
	 */
	public function test_export_then_import_round_trip() {
		$feed = array(
			'meta' => array(
				'feedName'          => 'Round Trip',
				'fc_crm_module'     => 'contacts',
				'listFields'        => array( 'email' => '3' ),
				'listFields_labels' => array( 'email' => 'Email Main' ),
			),
		);

		$export = formscrm_gf_build_feed_export( $feed );
		$meta   = formscrm_gf_feed_meta_from_import( $export );

		$this->assertSame( $feed['meta']['listFields'], $meta['listFields'] );
		$this->assertSame( $feed['meta']['listFields_labels'], $meta['listFields_labels'] );
	}
}
