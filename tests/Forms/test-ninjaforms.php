<?php
/**
 * Test Ninja Forms Integration
 *
 * @package FormsCRM
 * @subpackage Tests
 */

/**
 * Class Test_NinjaForms
 *
 * @package FormsCRM
 */
class Test_NinjaForms extends WP_UnitTestCase {

	/**
	 * Test if Ninja Forms class exists
	 */
	public function test_ninjaforms_class_exists() {
		$this->assertTrue( class_exists( 'FORMSCRM_NinjaForms_Settings' ) );
	}

	/**
	 * Test if Ninja Forms Action class exists
	 */
	public function test_ninjaforms_action_class_exists() {
		$this->assertTrue( class_exists( 'FORMSCRM_NinjaForms_Action' ) );
	}

	/**
	 * Test Ninja Forms action registration
	 */
	public function test_ninjaforms_action_properties() {
		if ( ! class_exists( 'NF_Abstracts_Action' ) ) {
			$this->markTestSkipped( 'Ninja Forms is not installed' );
		}

		$action = new FORMSCRM_NinjaForms_Action();
		
		$this->assertEquals( 'formscrm', $action->get_name() );
		$this->assertEquals( 'FormsCRM', $action->get_nicename() );
	}

	/**
	 * Test get settings method
	 */
	public function test_get_settings() {
		if ( ! class_exists( 'NF_Abstracts_Action' ) ) {
			$this->markTestSkipped( 'Ninja Forms is not installed' );
		}

		$action   = new FORMSCRM_NinjaForms_Action();
		$settings = $action->get_settings();

		// Check if settings is an array.
		$this->assertIsArray( $settings );

		// Check for required settings.
		$this->assertArrayHasKey( 'fc_crm_type', $settings );
		$this->assertArrayHasKey( 'fc_crm_url', $settings );
		$this->assertArrayHasKey( 'fc_crm_username', $settings );
		$this->assertArrayHasKey( 'fc_crm_password', $settings );
		$this->assertArrayHasKey( 'fc_crm_apipassword', $settings );
		$this->assertArrayHasKey( 'fc_crm_module', $settings );
		$this->assertArrayHasKey( 'fc_crm_mode_expert', $settings );
	}

	/**
	 * Test CRM type setting structure
	 */
	public function test_crm_type_setting_structure() {
		if ( ! class_exists( 'NF_Abstracts_Action' ) ) {
			$this->markTestSkipped( 'Ninja Forms is not installed' );
		}

		$action   = new FORMSCRM_NinjaForms_Action();
		$settings = $action->get_settings();

		$crm_type = $settings['fc_crm_type'];

		$this->assertEquals( 'fc_crm_type', $crm_type['name'] );
		$this->assertEquals( 'select', $crm_type['type'] );
		$this->assertEquals( 'CRM Type', $crm_type['label'] );
		$this->assertArrayHasKey( 'options', $crm_type );
		$this->assertIsArray( $crm_type['options'] );
	}

	/**
	 * Test merge vars extraction
	 */
	public function test_merge_vars_extraction() {
		$action_settings = array(
			'fc_crm_type'         => 'clientify',
			'fc_crm_field-email'  => '1',
			'fc_crm_field-name'   => '2',
			'fc_crm_field-phone'  => '3',
		);

		$form_fields = array(
			array(
				'id'    => '1',
				'value' => 'test@example.com',
			),
			array(
				'id'    => '2',
				'value' => 'John Doe',
			),
			array(
				'id'    => '3',
				'value' => '123456789',
			),
		);

		if ( ! class_exists( 'NF_Abstracts_Action' ) ) {
			$this->markTestSkipped( 'Ninja Forms is not installed' );
		}

		$action     = new FORMSCRM_NinjaForms_Action();
		$reflection = new ReflectionClass( $action );
		$method     = $reflection->getMethod( 'get_merge_vars' );
		$method->setAccessible( true );

		$merge_vars = $method->invoke( $action, $action_settings, $form_fields );

		$this->assertIsArray( $merge_vars );
		$this->assertCount( 3, $merge_vars );

		// Check first merge var.
		$this->assertEquals( 'email', $merge_vars[0]['name'] );
		$this->assertEquals( 'test@example.com', $merge_vars[0]['value'] );

		// Check second merge var.
		$this->assertEquals( 'name', $merge_vars[1]['name'] );
		$this->assertEquals( 'John Doe', $merge_vars[1]['value'] );

		// Check third merge var.
		$this->assertEquals( 'phone', $merge_vars[2]['name'] );
		$this->assertEquals( '123456789', $merge_vars[2]['value'] );
	}

	/**
	 * Test merge vars with array values
	 */
	public function test_merge_vars_with_array_values() {
		$action_settings = array(
			'fc_crm_type'           => 'clientify',
			'fc_crm_field-services' => '1',
		);

		$form_fields = array(
			array(
				'id'    => '1',
				'value' => array( 'Service 1', 'Service 2', 'Service 3' ),
			),
		);

		if ( ! class_exists( 'NF_Abstracts_Action' ) ) {
			$this->markTestSkipped( 'Ninja Forms is not installed' );
		}

		$action     = new FORMSCRM_NinjaForms_Action();
		$reflection = new ReflectionClass( $action );
		$method     = $reflection->getMethod( 'get_merge_vars' );
		$method->setAccessible( true );

		$merge_vars = $method->invoke( $action, $action_settings, $form_fields );

		$this->assertIsArray( $merge_vars );
		$this->assertCount( 1, $merge_vars );
		$this->assertEquals( 'services', $merge_vars[0]['name'] );
		$this->assertEquals( 'Service 1,Service 2,Service 3', $merge_vars[0]['value'] );
	}

	/**
	 * Test merge vars with empty values
	 */
	public function test_merge_vars_with_empty_values() {
		$action_settings = array(
			'fc_crm_type'        => 'clientify',
			'fc_crm_field-email' => '1',
			'fc_crm_field-name'  => '2',
		);

		$form_fields = array(
			array(
				'id'    => '1',
				'value' => 'test@example.com',
			),
			array(
				'id'    => '2',
				'value' => '', // Empty value.
			),
		);

		if ( ! class_exists( 'NF_Abstracts_Action' ) ) {
			$this->markTestSkipped( 'Ninja Forms is not installed' );
		}

		$action     = new FORMSCRM_NinjaForms_Action();
		$reflection = new ReflectionClass( $action );
		$method     = $reflection->getMethod( 'get_merge_vars' );
		$method->setAccessible( true );

		$merge_vars = $method->invoke( $action, $action_settings, $form_fields );

		// Should only return merge vars with non-empty values.
		$this->assertIsArray( $merge_vars );
		$this->assertCount( 1, $merge_vars );
		$this->assertEquals( 'email', $merge_vars[0]['name'] );
	}

	/**
	 * Test settings field types
	 */
	public function test_settings_field_types() {
		if ( ! class_exists( 'NF_Abstracts_Action' ) ) {
			$this->markTestSkipped( 'Ninja Forms is not installed' );
		}

		$action   = new FORMSCRM_NinjaForms_Action();
		$settings = $action->get_settings();

		// CRM Type should be a select.
		$this->assertEquals( 'select', $settings['fc_crm_type']['type'] );

		// Text fields.
		$this->assertEquals( 'textbox', $settings['fc_crm_url']['type'] );
		$this->assertEquals( 'textbox', $settings['fc_crm_username']['type'] );
		$this->assertEquals( 'textbox', $settings['fc_crm_password']['type'] );

		// Expert mode should be toggle.
		$this->assertEquals( 'toggle', $settings['fc_crm_mode_expert']['type'] );
	}

	/**
	 * Test action hook is registered
	 */
	public function test_action_hook_registered() {
		$this->assertTrue( has_filter( 'ninja_forms_register_actions' ) );
	}

	/**
	 * Test process action with missing CRM type
	 */
	public function test_process_without_crm_type() {
		if ( ! class_exists( 'NF_Abstracts_Action' ) ) {
			$this->markTestSkipped( 'Ninja Forms is not installed' );
		}

		$action = new FORMSCRM_NinjaForms_Action();

		// Action settings without CRM type.
		$action_settings = array();
		$form_id         = 1;
		$data            = array(
			'fields' => array(),
		);

		// This should exit early without error.
		$result = $action->process( $action_settings, $form_id, $data );

		// If no exception is thrown, the test passes.
		$this->assertTrue( true );
	}
}
