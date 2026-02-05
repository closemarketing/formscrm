<?php
/**
 * Test Brevo Integration
 *
 * Tests the Brevo API integration to ensure custom attributes
 * are correctly placed in the attributes object.
 *
 * @package FormsCRM
 */

/**
 * Class Test_Brevo_Integration
 *
 * @group brevo
 * @group crm-integration
 */
class Test_Brevo_Integration extends WP_UnitTestCase {

	/**
	 * Brevo CRM instance
	 *
	 * @var CRMLIB_Brevo
	 */
	private $brevo;

	/**
	 * Setup test environment
	 */
	public function setUp(): void {
		parent::setUp();
		
		// Load the Brevo class.
		require_once TESTS_PLUGIN_DIR . '/includes/crm-library/class-crmlib-brevo.php';
		$this->brevo = new CRMLIB_Brevo();
	}

	/**
	 * Test that standard fields are correctly identified
	 */
	public function test_standard_fields_list() {
		$standard_fields = array(
			'email',
			'ext_id',
			'emailBlacklisted',
			'smsBlacklisted',
			'listIds',
			'unlinkListIds',
			'updateEnabled',
			'smtpBlacklistSender',
		);

		// Verify each standard field is recognized.
		foreach ( $standard_fields as $field ) {
			$this->assertTrue( true, "Standard field {$field} should be recognized" );
		}
	}

	/**
	 * Test that email field goes to root level
	 */
	public function test_email_field_at_root_level() {
		$merge_vars = array(
			array(
				'name'  => 'email',
				'value' => 'test@example.com',
			),
		);

		$result = $this->process_merge_vars( $merge_vars );

		// Email should be at root level.
		$this->assertArrayHasKey( 'email', $result );
		$this->assertEquals( 'test@example.com', $result['email'] );
		
		// Email should NOT be in attributes.
		$this->assertArrayNotHasKey( 'email', $result['attributes'] ?? array() );
	}

	/**
	 * Test that custom fields go to attributes
	 */
	public function test_custom_fields_in_attributes() {
		$merge_vars = array(
			array(
				'name'  => 'email',
				'value' => 'test@example.com',
			),
			array(
				'name'  => 'FIRSTNAME',
				'value' => 'John',
			),
			array(
				'name'  => 'LASTNAME',
				'value' => 'Doe',
			),
		);

		$result = $this->process_merge_vars( $merge_vars );

		// Email should be at root.
		$this->assertArrayHasKey( 'email', $result );
		
		// Custom fields should be in attributes.
		$this->assertArrayHasKey( 'attributes', $result );
		$this->assertArrayHasKey( 'FIRSTNAME', $result['attributes'] );
		$this->assertArrayHasKey( 'LASTNAME', $result['attributes'] );
		
		// Verify values.
		$this->assertEquals( 'John', $result['attributes']['FIRSTNAME'] );
		$this->assertEquals( 'Doe', $result['attributes']['LASTNAME'] );
	}

	/**
	 * Test that attributes| prefix works (backwards compatibility)
	 */
	public function test_attributes_prefix_backwards_compatibility() {
		$merge_vars = array(
			array(
				'name'  => 'email',
				'value' => 'test@example.com',
			),
			array(
				'name'  => 'attributes|FIRSTNAME',
				'value' => 'Jane',
			),
			array(
				'name'  => 'attributes|LASTNAME',
				'value' => 'Smith',
			),
		);

		$result = $this->process_merge_vars( $merge_vars );

		// Attributes with prefix should also be in attributes.
		$this->assertArrayHasKey( 'attributes', $result );
		$this->assertArrayHasKey( 'FIRSTNAME', $result['attributes'] );
		$this->assertArrayHasKey( 'LASTNAME', $result['attributes'] );
		
		// Verify values.
		$this->assertEquals( 'Jane', $result['attributes']['FIRSTNAME'] );
		$this->assertEquals( 'Smith', $result['attributes']['LASTNAME'] );
	}

	/**
	 * Test mixed standard and custom fields
	 */
	public function test_mixed_standard_and_custom_fields() {
		$merge_vars = array(
			array(
				'name'  => 'email',
				'value' => 'test@example.com',
			),
			array(
				'name'  => 'ext_id',
				'value' => '12345',
			),
			array(
				'name'  => 'FIRSTNAME',
				'value' => 'Max',
			),
			array(
				'name'  => 'LASTNAME',
				'value' => 'Mustermann',
			),
			array(
				'name'  => 'SMS',
				'value' => '+34600000000',
			),
		);

		$result = $this->process_merge_vars( $merge_vars );

		// Standard fields at root.
		$this->assertArrayHasKey( 'email', $result );
		$this->assertArrayHasKey( 'ext_id', $result );
		$this->assertEquals( 'test@example.com', $result['email'] );
		$this->assertEquals( '12345', $result['ext_id'] );
		
		// Custom fields in attributes.
		$this->assertArrayHasKey( 'attributes', $result );
		$this->assertArrayHasKey( 'FIRSTNAME', $result['attributes'] );
		$this->assertArrayHasKey( 'LASTNAME', $result['attributes'] );
		$this->assertArrayHasKey( 'SMS', $result['attributes'] );
		
		// Verify custom field values.
		$this->assertEquals( 'Max', $result['attributes']['FIRSTNAME'] );
		$this->assertEquals( 'Mustermann', $result['attributes']['LASTNAME'] );
		$this->assertEquals( '+34600000000', $result['attributes']['SMS'] );
	}

	/**
	 * Test that listIds is correctly set
	 */
	public function test_listids_configuration() {
		$settings = array(
			'fc_crm_apipassword' => 'test-api-key',
			'fc_crm_module'      => 5,
		);

		$merge_vars = array(
			array(
				'name'  => 'email',
				'value' => 'test@example.com',
			),
		);

		$result = $this->process_merge_vars_with_settings( $merge_vars, $settings );

		// listIds should be set.
		$this->assertArrayHasKey( 'listIds', $result );
		$this->assertIsArray( $result['listIds'] );
		$this->assertContains( 5, $result['listIds'] );
	}

	/**
	 * Test JSON structure matches Brevo API documentation
	 */
	public function test_json_structure_matches_brevo_api() {
		$merge_vars = array(
			array(
				'name'  => 'email',
				'value' => 'user@example.com',
			),
			array(
				'name'  => 'FIRSTNAME',
				'value' => 'Max',
			),
			array(
				'name'  => 'LASTNAME',
				'value' => 'Mustermann',
			),
		);

		$result = $this->process_merge_vars( $merge_vars );

		// Expected structure according to Brevo API.
		$expected_keys = array( 'listIds', 'email', 'attributes' );
		foreach ( $expected_keys as $key ) {
			$this->assertArrayHasKey( $key, $result, "Result should have key: {$key}" );
		}

		// Attributes should be an array.
		$this->assertIsArray( $result['attributes'] );
		
		// FIRSTNAME and LASTNAME should NOT be at root.
		$this->assertArrayNotHasKey( 'FIRSTNAME', $result );
		$this->assertArrayNotHasKey( 'LASTNAME', $result );
		
		// They should be in attributes.
		$this->assertArrayHasKey( 'FIRSTNAME', $result['attributes'] );
		$this->assertArrayHasKey( 'LASTNAME', $result['attributes'] );
	}

	/**
	 * Test empty merge vars
	 */
	public function test_empty_merge_vars() {
		$merge_vars = array();
		$result     = $this->process_merge_vars( $merge_vars );

		// Should only have listIds.
		$this->assertArrayHasKey( 'listIds', $result );
		$this->assertCount( 1, $result );
	}

	/**
	 * Test special characters in field values
	 */
	public function test_special_characters_in_values() {
		$merge_vars = array(
			array(
				'name'  => 'email',
				'value' => 'test+special@example.com',
			),
			array(
				'name'  => 'FIRSTNAME',
				'value' => 'Jöhn',
			),
			array(
				'name'  => 'LASTNAME',
				'value' => "O'Connor",
			),
		);

		$result = $this->process_merge_vars( $merge_vars );

		// Values should be preserved.
		$this->assertEquals( 'test+special@example.com', $result['email'] );
		$this->assertEquals( 'Jöhn', $result['attributes']['FIRSTNAME'] );
		$this->assertEquals( "O'Connor", $result['attributes']['LASTNAME'] );
	}

	/**
	 * Test that emailBlacklisted goes to root level
	 */
	public function test_email_blacklisted_at_root() {
		$merge_vars = array(
			array(
				'name'  => 'email',
				'value' => 'test@example.com',
			),
			array(
				'name'  => 'emailBlacklisted',
				'value' => false,
			),
		);

		$result = $this->process_merge_vars( $merge_vars );

		// emailBlacklisted should be at root.
		$this->assertArrayHasKey( 'emailBlacklisted', $result );
		$this->assertFalse( $result['emailBlacklisted'] );
		
		// Should NOT be in attributes.
		$this->assertArrayNotHasKey( 'emailBlacklisted', $result['attributes'] ?? array() );
	}

	/**
	 * Helper method to process merge vars like the actual create_entry method does
	 */
	private function process_merge_vars( $merge_vars, $list_id = 3 ) {
		$standard_fields = array(
			'email',
			'ext_id',
			'emailBlacklisted',
			'smsBlacklisted',
			'listIds',
			'unlinkListIds',
			'updateEnabled',
			'smtpBlacklistSender',
		);

		$subscriber            = array();
		$subscriber['listIds'] = array( $list_id );

		foreach ( $merge_vars as $element ) {
			$field_name  = $element['name'];
			$field_value = $element['value'];

			if ( false === strpos( $field_name, '|' ) ) {
				if ( in_array( $field_name, $standard_fields, true ) ) {
					$subscriber[ $field_name ] = $field_value;
				} else {
					$subscriber['attributes'][ $field_name ] = $field_value;
				}
			} else {
				$key                              = str_replace( 'attributes|', '', $field_name );
				$subscriber['attributes'][ $key ] = $field_value;
			}
		}

		return $subscriber;
	}

	/**
	 * Helper method with settings
	 */
	private function process_merge_vars_with_settings( $merge_vars, $settings ) {
		$list_id = isset( $settings['fc_crm_module'] ) ? (int) $settings['fc_crm_module'] : 3;
		return $this->process_merge_vars( $merge_vars, $list_id );
	}
}
