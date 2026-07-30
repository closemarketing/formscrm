<?php
/**
 * Class PhoneNormalizationTest
 *
 * Tests for formscrm_normalize_phone_number(), used to normalize GravityForms
 * Phone field values (including the 3.0 international format) before sending
 * them to the CRM.
 *
 * Command: composer test-debug --filter PhoneNormalizationTest
 *
 * @package Formscrm
 */

/**
 * Unit tests for formscrm_normalize_phone_number().
 */
class PhoneNormalizationTest extends WP_UnitTestCase {

	/**
	 * International values (with a leading "+") keep the "+" and strip everything else.
	 */
	public function test_strips_formatting_from_international_number() {
		$this->assertSame( '+34612345678', formscrm_normalize_phone_number( '+34 612 34 56 78' ) );
		$this->assertSame( '+34612345678', formscrm_normalize_phone_number( '+34-612-345-678' ) );
		$this->assertSame( '+1234567890', formscrm_normalize_phone_number( '+1 (234) 567-890' ) );
	}

	/**
	 * Classic (standard/US) formatted values without a leading "+" keep only digits.
	 */
	public function test_strips_formatting_from_standard_number() {
		$this->assertSame( '2345678900', formscrm_normalize_phone_number( '(234) 567-8900' ) );
		$this->assertSame( '2345678900', formscrm_normalize_phone_number( '234.567.8900' ) );
	}

	/**
	 * A "+" only counts as a prefix when it is the very first character.
	 */
	public function test_plus_sign_not_at_start_is_dropped() {
		$this->assertSame( '234567', formscrm_normalize_phone_number( '234+567' ) );
	}

	/**
	 * Already-normalized values pass through unchanged.
	 */
	public function test_already_normalized_value_is_unchanged() {
		$this->assertSame( '+34612345678', formscrm_normalize_phone_number( '+34612345678' ) );
		$this->assertSame( '612345678', formscrm_normalize_phone_number( '612345678' ) );
	}

	/**
	 * Empty and whitespace-only values are returned untouched.
	 */
	public function test_empty_value_is_untouched() {
		$this->assertSame( '', formscrm_normalize_phone_number( '' ) );
		$this->assertSame( '', formscrm_normalize_phone_number( '   ' ) );
	}

	/**
	 * A value with no digits (only formatting characters) is returned untouched,
	 * rather than collapsing to an empty string.
	 */
	public function test_value_with_no_digits_is_returned_as_is() {
		$this->assertSame( '--', formscrm_normalize_phone_number( '--' ) );
	}

	/**
	 * Leading/trailing whitespace around an otherwise valid number is trimmed.
	 */
	public function test_trims_surrounding_whitespace() {
		$this->assertSame( '+34612345678', formscrm_normalize_phone_number( '  +34 612 345 678  ' ) );
	}
}
