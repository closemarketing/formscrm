<?php
/**
 * Class GravityFormsFieldMapTest
 *
 * Regression tests for the fatal error raised by Gravity Forms when a CRM
 * library returns an error payload from list_fields():
 *
 *   PHP Fatal error: Uncaught TypeError: Cannot access offset of type string
 *   on string in gravityforms/includes/settings/fields/class-field-map.php:50
 *
 * Command: composer test --filter GravityFormsFieldMapTest
 *
 * @package Formscrm
 */

/**
 * Tests for formscrm_sanitize_field_map().
 */
class GravityFormsFieldMapTest extends WP_UnitTestCase {

	/**
	 * Error payload returned by CRMLIB_Odoo::list_fields() in production.
	 *
	 * @return array
	 */
	private function odoo_error_payload() {
		return array(
			'status'  => 'error',
			'data'    => array(),
			'message' => 'Some credentials are missing',
		);
	}

	/**
	 * Replicates the choices loop of
	 * Gravity_Forms\Gravity_Forms\Settings\Fields\Field_Map::__construct()
	 * (Gravity Forms is not loaded in the test environment).
	 *
	 * @param array $props Field props, with 'field_map' as passed by FormsCRM.
	 * @return array Prepared key field choices.
	 */
	private function gf_field_map_prepare_choices( $props ) {
		$rgar = static function ( $data, $key, $default_value = '' ) {
			return isset( $data[ $key ] ) ? $data[ $key ] : $default_value;
		};

		$key_field            = array();
		$key_field['choices'] = $rgar( $props, 'field_map' ) ? array_values( $props['field_map'] ) : array();
		foreach ( $key_field['choices'] as $i => $choice ) {
			$field_types = isset( $choice['field_types'] ) ? $choice['field_types'] : $rgar( $choice, 'field_type', false );

			$key_field['choices'][ $i ]['required_types'] = $field_types ? $field_types : $rgar( $choice, 'required_types', array() );
			$key_field['choices'][ $i ]['excluded_types'] = $rgar( $choice, 'exclude_field_types', false ) ? $choice['exclude_field_types'] : $rgar( $choice, 'excluded_types', array() );

			unset( $key_field['choices'][ $i ]['field_type'], $key_field['choices'][ $i ]['exclude_field_types'] );
		}

		return $key_field['choices'];
	}

	/**
	 * Reproduces the production fatal: the raw error payload breaks Field_Map.
	 *
	 * Guards the replica above: if this stops throwing, the replica no longer
	 * matches Gravity Forms and the remaining tests prove nothing.
	 */
	public function test_raw_error_payload_reproduces_gravity_forms_fatal() {
		$this->expectException( TypeError::class );
		$this->expectExceptionMessage( 'Cannot access offset of type string on string' );

		$this->gf_field_map_prepare_choices( array( 'field_map' => $this->odoo_error_payload() ) );
	}

	/**
	 * Sanitized error payload is an empty field map and does not break Field_Map.
	 */
	public function test_error_payload_is_sanitized_to_empty_field_map() {
		$field_map = formscrm_sanitize_field_map( $this->odoo_error_payload() );

		$this->assertSame( array(), $field_map );
		$this->assertSame( array(), $this->gf_field_map_prepare_choices( array( 'field_map' => $field_map ) ) );
	}

	/**
	 * Error payload is written to the PHP error log even without WP_DEBUG,
	 * so production sites keep a record of the CRM response.
	 */
	public function test_error_payload_is_logged_outside_debug_mode() {
		$log_file     = wp_tempnam( 'formscrm-field-map' );
		$previous_log = ini_set( 'error_log', $log_file ); // phpcs:ignore WordPress.PHP.IniSet.Risky

		try {
			formscrm_sanitize_field_map( $this->odoo_error_payload() );
			$log = file_get_contents( $log_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		} finally {
			ini_set( 'error_log', $previous_log ); // phpcs:ignore WordPress.PHP.IniSet.Risky
			wp_delete_file( $log_file );
		}

		$this->assertStringContainsString( 'FORMSCRM: CRM list_fields error:', $log );
		$this->assertStringContainsString( 'Some credentials are missing', $log );
	}

	/**
	 * Valid fields are kept untouched and prepared by Field_Map.
	 */
	public function test_valid_fields_are_kept() {
		$fields = array(
			array(
				'name'     => 'email_from',
				'label'    => 'Email (email_from)',
				'required' => true,
			),
			array(
				'name'  => 'attachment',
				'label' => 'Attachment',
			),
		);

		$field_map = formscrm_sanitize_field_map( $fields );
		$this->assertSame( $fields, $field_map );

		$choices = $this->gf_field_map_prepare_choices( array( 'field_map' => $field_map ) );
		$this->assertCount( 2, $choices );
		$this->assertSame( 'email_from', $choices[0]['name'] );
	}

	/**
	 * Non-array entries mixed with fields are dropped and keys are reindexed.
	 */
	public function test_non_array_entries_are_dropped() {
		$field_map = formscrm_sanitize_field_map(
			array(
				'junk',
				array( 'name' => 'name', 'label' => 'Name' ),
				42,
				null,
			)
		);

		$this->assertSame( array( array( 'name' => 'name', 'label' => 'Name' ) ), $field_map );
		$this->gf_field_map_prepare_choices( array( 'field_map' => $field_map ) );
	}

	/**
	 * Non-array return values (false, null, strings) become an empty field map.
	 *
	 * @dataProvider non_array_provider
	 *
	 * @param mixed $value Value returned by list_fields().
	 */
	public function test_non_array_values_become_empty( $value ) {
		$this->assertSame( array(), formscrm_sanitize_field_map( $value ) );
	}

	/**
	 * Non-array values.
	 *
	 * @return array
	 */
	public function non_array_provider() {
		return array(
			'false'  => array( false ),
			'null'   => array( null ),
			'string' => array( 'error' ),
			'int'    => array( 0 ),
		);
	}

	/**
	 * GFCRM must pass list_fields() through the sanitizer before building the
	 * listFields setting, so the fix cannot be reverted silently.
	 */
	public function test_gravityforms_feed_uses_sanitizer() {
		$source = file_get_contents( TESTS_PLUGIN_DIR . '/includes/formscrm-library/class-gravityforms.php' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		$this->assertMatchesRegularExpression(
			"/'field_map'\s*=>\s*formscrm_sanitize_field_map\(\s*\\\$this->crmlib->list_fields\(/",
			$source
		);
	}
}
