<?php
/**
 * Class GravityFormsDynamicFieldsTest
 *
 * Tests for the formscrm_gf_get_label_by_value() helper that resolves
 * {label:X} merge tags in GravityForms field mapping.
 *
 * Command: composer test-debug --filter GravityFormsDynamicFieldsTest
 *
 * @package Formscrm
 */

/**
 * Unit tests for formscrm_gf_get_label_by_value().
 */
class GravityFormsDynamicFieldsTest extends WP_UnitTestCase {

	/**
	 * Choices array where every value is unique.
	 *
	 * @var array
	 */
	private $unique_choices = array();

	/**
	 * Choices array with two entries sharing the same value.
	 *
	 * @var array
	 */
	private $duplicate_value_choices = array();

	/**
	 * Set up shared fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->unique_choices = array(
			array( 'text' => 'Option A', 'value' => 'val_a' ),
			array( 'text' => 'Option B', 'value' => 'val_b' ),
			array( 'text' => 'Option C', 'value' => 'val_c' ),
		);

		// Two choices share value 'x'; the third has a unique value.
		$this->duplicate_value_choices = array(
			array( 'text' => 'Option A', 'value' => 'x' ),
			array( 'text' => 'Option B', 'value' => 'x' ),
			array( 'text' => 'Option C', 'value' => 'y' ),
		);
	}

	// -------------------------------------------------------------------------
	// Basic lookup (unique values)
	// -------------------------------------------------------------------------

	/**
	 * Returns the correct label when values are all unique.
	 */
	public function test_returns_label_for_unique_value() {
		$this->assertEquals( 'Option A', formscrm_gf_get_label_by_value( $this->unique_choices, 'val_a' ) );
		$this->assertEquals( 'Option B', formscrm_gf_get_label_by_value( $this->unique_choices, 'val_b' ) );
		$this->assertEquals( 'Option C', formscrm_gf_get_label_by_value( $this->unique_choices, 'val_c' ) );
	}

	/**
	 * Returns empty string when the value does not exist in choices.
	 */
	public function test_returns_empty_string_when_value_not_found() {
		$result = formscrm_gf_get_label_by_value( $this->unique_choices, 'does_not_exist' );
		$this->assertSame( '', $result );
	}

	/**
	 * Returns empty string for an empty choices array.
	 */
	public function test_returns_empty_string_for_empty_choices() {
		$result = formscrm_gf_get_label_by_value( array(), 'val_a' );
		$this->assertSame( '', $result );
	}

	// -------------------------------------------------------------------------
	// Duplicate values – the core bug scenario
	// -------------------------------------------------------------------------

	/**
	 * When two choices share the same value the first one is returned, which is
	 * the best possible behaviour given that only the value is stored in the entry.
	 *
	 * Previously, array_search + array_column could return an incorrect label
	 * because array_column keyed by 'text' then array_search returned the first
	 * key for the given value — but crucially, if the user selected the SECOND
	 * choice (same value), array_search still returned the first label.
	 *
	 * The fix ensures we iterate in order and break on the first match, which is
	 * deterministic and consistent with GravityForms behaviour (it stores the
	 * value, so the first matching label is the canonical one).
	 */
	public function test_duplicate_value_returns_first_matching_label() {
		// Both 'Option A' and 'Option B' map to 'x'. First match must win.
		$result = formscrm_gf_get_label_by_value( $this->duplicate_value_choices, 'x' );
		$this->assertEquals( 'Option A', $result );
	}

	/**
	 * Unique value still works correctly when the choices array contains duplicates.
	 */
	public function test_unique_value_still_works_alongside_duplicates() {
		$result = formscrm_gf_get_label_by_value( $this->duplicate_value_choices, 'y' );
		$this->assertEquals( 'Option C', $result );
	}

	/**
	 * The old array_search + array_column approach was wrong when the 'text' keys
	 * (used as the index) collided.  This test verifies our fix does NOT reproduce
	 * that failure mode.
	 *
	 * Scenario: two choices with the SAME label but different values. The old code
	 * would overwrite one entry in the array_column result, losing a value.
	 */
	public function test_duplicate_label_different_values_both_found() {
		$choices = array(
			array( 'text' => 'Same Label', 'value' => 'val_1' ),
			array( 'text' => 'Same Label', 'value' => 'val_2' ),
		);

		$this->assertEquals( 'Same Label', formscrm_gf_get_label_by_value( $choices, 'val_1' ) );
		$this->assertEquals( 'Same Label', formscrm_gf_get_label_by_value( $choices, 'val_2' ) );
	}

	// -------------------------------------------------------------------------
	// Edge cases
	// -------------------------------------------------------------------------

	/**
	 * Strict type comparison: integer entry value must not match string choice value.
	 */
	public function test_strict_type_comparison() {
		$choices = array(
			array( 'text' => 'Zero', 'value' => '0' ),
			array( 'text' => 'One',  'value' => '1' ),
		);

		// Integer 0 must not match string '0' (=== comparison).
		$result = formscrm_gf_get_label_by_value( $choices, 0 );
		$this->assertSame( '', $result );

		// String '1' must match string '1'.
		$result = formscrm_gf_get_label_by_value( $choices, '1' );
		$this->assertEquals( 'One', $result );
	}

	/**
	 * Choice with empty string value is found correctly.
	 */
	public function test_empty_string_value_is_found() {
		$choices = array(
			array( 'text' => 'Please select', 'value' => '' ),
			array( 'text' => 'Option A',       'value' => 'a' ),
		);

		$result = formscrm_gf_get_label_by_value( $choices, '' );
		$this->assertEquals( 'Please select', $result );
	}

	/**
	 * A choice missing the 'value' key is skipped without a PHP notice.
	 */
	public function test_choice_missing_value_key_is_skipped() {
		$choices = array(
			array( 'text' => 'No value key' ),
			array( 'text' => 'Has value', 'value' => 'found' ),
		);

		$result = formscrm_gf_get_label_by_value( $choices, 'found' );
		$this->assertEquals( 'Has value', $result );
	}

	/**
	 * When GravityForms stores the label as the entry value (no separate "Use Value"
	 * configured), choices have an empty string value and the entry stores the text.
	 * The function must fall back to matching by text.
	 */
	public function test_empty_choice_value_falls_back_to_text_match() {
		$choices = array(
			array( 'text' => 'Option A', 'value' => '' ),
			array( 'text' => 'Option B', 'value' => '' ),
			array( 'text' => 'Option C', 'value' => '' ),
		);

		$this->assertEquals( 'Option B', formscrm_gf_get_label_by_value( $choices, 'Option B' ) );
		$this->assertEquals( 'Option A', formscrm_gf_get_label_by_value( $choices, 'Option A' ) );
	}

	/**
	 * A single choice in the array is resolved correctly.
	 */
	public function test_single_choice_array() {
		$choices = array(
			array( 'text' => 'Only Option', 'value' => 'only' ),
		);

		$this->assertEquals( 'Only Option', formscrm_gf_get_label_by_value( $choices, 'only' ) );
		$this->assertSame( '', formscrm_gf_get_label_by_value( $choices, 'other' ) );
	}
}
