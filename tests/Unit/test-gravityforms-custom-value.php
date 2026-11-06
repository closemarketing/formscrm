<?php
/**
 * Class GravityFormsCustomValueTest
 *
 * Tests for the helpers that store and read fixed (custom) values mapped to
 * CRM fields in Gravity Forms feeds.
 *
 * Command: composer test-debug --filter GravityFormsCustomValueTest
 *
 * @package Formscrm
 */

/**
 * Unit tests for Gravity Forms custom value helpers.
 */
class GravityFormsCustomValueTest extends WP_UnitTestCase {

	/**
	 * Builds a feed with the given meta.
	 *
	 * @param array $meta Feed meta.
	 * @return array
	 */
	private function get_feed( $meta ) {
		return array(
			'id'   => 1,
			'meta' => $meta,
		);
	}

	/**
	 * Meta key uses a prefix that is not matched by get_field_map_fields().
	 */
	public function test_meta_key_is_not_a_field_map_key() {
		$meta_key = formscrm_gf_custom_value_meta_key( 'listFields', 'lead_source' );

		$this->assertSame( 'listFieldsCustom_lead_source', $meta_key );
		$this->assertNotSame( 0, strpos( $meta_key, 'listFields_' ) );
	}

	/**
	 * Returns the saved custom value.
	 */
	public function test_returns_saved_custom_value() {
		$feed = $this->get_feed(
			array(
				'listFields_lead_source'       => 'gf_custom',
				'listFieldsCustom_lead_source' => 'Web',
			)
		);

		$this->assertSame( 'Web', formscrm_gf_get_custom_value_template( $feed, 'lead_source' ) );
	}

	/**
	 * Keeps placeholders untouched so they are resolved on submission.
	 */
	public function test_keeps_placeholders() {
		$feed = $this->get_feed(
			array(
				'listFieldsCustom_description' => 'Web - {id:3} - {Name:1}',
			)
		);

		$this->assertSame( 'Web - {id:3} - {Name:1}', formscrm_gf_get_custom_value_template( $feed, 'description' ) );
	}

	/**
	 * Returns an empty string when no custom value is saved.
	 */
	public function test_returns_empty_when_missing() {
		$feed = $this->get_feed( array( 'listFields_lead_source' => 'gf_custom' ) );

		$this->assertSame( '', formscrm_gf_get_custom_value_template( $feed, 'lead_source' ) );
	}

	/**
	 * Returns an empty string for invalid feeds or non-scalar values.
	 */
	public function test_returns_empty_for_invalid_input() {
		$this->assertSame( '', formscrm_gf_get_custom_value_template( false, 'lead_source' ) );
		$this->assertSame( '', formscrm_gf_get_custom_value_template( array(), 'lead_source' ) );
		$this->assertSame(
			'',
			formscrm_gf_get_custom_value_template(
				$this->get_feed( array( 'listFieldsCustom_lead_source' => array( 'Web' ) ) ),
				'lead_source'
			)
		);
	}

	/**
	 * Supports a custom field map setting name.
	 */
	public function test_supports_custom_field_name() {
		$feed = $this->get_feed( array( 'otherMapCustom_stage' => 'New' ) );

		$this->assertSame( 'New', formscrm_gf_get_custom_value_template( $feed, 'stage', 'otherMap' ) );
	}

	/**
	 * Numeric values are returned as strings.
	 */
	public function test_casts_numeric_value() {
		$feed = $this->get_feed( array( 'listFieldsCustom_owner_id' => 42 ) );

		$this->assertSame( '42', formscrm_gf_get_custom_value_template( $feed, 'owner_id' ) );
	}
}
