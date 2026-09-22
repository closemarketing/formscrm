<?php
/**
 * Class ContactFormsTest
 *
 * Command: composer test-debug --filter ContactFormsTest
 *
 * @package Formscrm
 */

class ContactFormsTest extends WP_UnitTestCase {

	/**
	 * Creates a real wpcf7_contact_form post and makes it CF7's "current"
	 * form via WPCF7_ContactForm::get_instance(), the same way CF7 itself
	 * sets it while rendering/processing a form. WPCF7_ContactForm's
	 * constructor is private and $current isn't settable directly, so this
	 * is the only way to get FORMSCRM_CF7_Settings::inject_analytics_plus_fields()
	 * — which reads WPCF7_ContactForm::get_current() — into a known state.
	 *
	 * @return int The created post's ID.
	 */
	private function make_current_contact_form() {
		$post_id = $this->factory()->post->create( array( 'post_type' => 'wpcf7_contact_form' ) );
		WPCF7_ContactForm::get_instance( $post_id );
		return $post_id;
	}

	/**
	 * Tear down: reset the shared tracking-needed filter.
	 */
	public function tearDown(): void {
		remove_all_filters( 'formscrm_needs_analytics_plus_tracking' );
		parent::tearDown();
	}

	/**
	 * A Clientify-connected form must get the fixed-name hidden field
	 * injected before its submit button, and report needing the tracking script.
	 */
	public function test_inject_analytics_plus_fields_adds_hidden_field() {
		$post_id = $this->make_current_contact_form();
		update_option( 'cf7_crm_' . $post_id, array( 'fc_crm_type' => 'clientify' ) );

		$settings  = new FORMSCRM_CF7_Settings();
		$form_html = $settings->inject_analytics_plus_fields( '<form><input type="submit" value="Send" /></form>' );

		$this->assertStringContainsString( '<input type="hidden" name="formscrm_vk" class="formscrm-vk" />', $form_html );
		$this->assertTrue( apply_filters( 'formscrm_needs_analytics_plus_tracking', false ) );
	}

	/**
	 * Fields must not be injected, nor the tracking script requested, for a
	 * form whose CRM isn't Clientify.
	 */
	public function test_inject_analytics_plus_fields_skips_non_clientify_forms() {
		$post_id = $this->make_current_contact_form();
		update_option( 'cf7_crm_' . $post_id, array( 'fc_crm_type' => 'holded' ) );

		$settings  = new FORMSCRM_CF7_Settings();
		$original  = '<form><input type="submit" value="Send" /></form>';
		$form_html = $settings->inject_analytics_plus_fields( $original );

		$this->assertSame( $original, $form_html );
		$this->assertFalse( apply_filters( 'formscrm_needs_analytics_plus_tracking', false ) );
	}

	/**
	 * Re-rendering the same form (e.g. AJAX re-validation) must not duplicate
	 * the hidden field.
	 */
	public function test_inject_analytics_plus_fields_does_not_duplicate_field() {
		$post_id = $this->make_current_contact_form();
		update_option( 'cf7_crm_' . $post_id, array( 'fc_crm_type' => 'clientify' ) );

		$settings  = new FORMSCRM_CF7_Settings();
		$form_html = '<form><input type="hidden" name="formscrm_vk" class="formscrm-vk" /><input type="submit" value="Send" /></form>';
		$form_html = $settings->inject_analytics_plus_fields( $form_html );

		$this->assertSame( 1, substr_count( $form_html, 'name="formscrm_vk"' ) );
	}

	public function test_get_merge_vars() {
		$cf7_crm = array(
			'fc_crm_type'                                   => 'clientify',
			'fc_crm_apipassword'                            => 'api-password',
			'fc_crm_module'                                 => 'Contacts',
			'fc_crm_field-first_name'                       => 'your-name',
			'fc_crm_field-email'                            => 'your-email',
			'fc_crm_field-custom_fields|interes_categoria'  => 'menu-991',
			'fc_crm_field-custom_fields|interes2'           => 'menu-992',
			'fc_crm_field-custom_fields|info_chat'          => 'menu-993',
		);

		$submitted_data = array(
			'your-name'        => 'david',
			'your-email'       => 'david@close.marketing',
			'your-phone'       => '66666666',
			'menu-991'         => array( '2.000' ),
			'menu-992'         => array( 'De 3 a 6 meses' ),
			'menu-993'         => array( 'Autónomo' ),
			'your-subject'     => 'test',
			'your-message'     => 'En un lugar de la Mancha, de cuyo nombre no quiero acordarme, no ha mucho tiempo que vivía un hidalgo de los de lanza en astillero, adarga antigua, rocín flaco y galgo corredor. Una olla de algo más vaca que carnero, salpicón las más noches, duelos y quebrantos los sábados, lantejas los viernes, algún palomino de añadidura los domingos, consumían las tres cuartas partes de su hacienda.',
			'clientify_cookie' => '',
		);

		$merge_vars = FORMSCRM_CF7_Settings::get_merge_vars( $cf7_crm, $submitted_data );
		$this->assertEquals( $merge_vars, array(
			array( 'name' => 'first_name', 'value' => 'david' ),
			array( 'name' => 'email', 'value' => 'david@close.marketing' ),
			array( 'name' => 'custom_fields|interes_categoria', 'value' => '2.000' ),
			array( 'name' => 'custom_fields|interes2', 'value' => 'De 3 a 6 meses' ),
			array( 'name' => 'custom_fields|info_chat', 'value' => 'Autónomo' ),
		) );
	}

	/**
	 * GDPR checkbox unchecked: CF7 submits empty array, value must be false.
	 */
	public function test_get_merge_vars_gdpr_unchecked() {
		$cf7_crm = array(
			'fc_crm_type'              => 'clientify',
			'fc_crm_apipassword'       => 'api-password',
			'fc_crm_module'            => 'Contacts',
			'fc_crm_field-gdpr_accept' => 'extra-info',
		);

		$submitted_data = array(
			'extra-info' => array(), // Unchecked checkbox returns empty array in CF7.
		);

		$merge_vars = FORMSCRM_CF7_Settings::get_merge_vars( $cf7_crm, $submitted_data );
		$this->assertEquals(
			array( array( 'name' => 'gdpr_accept', 'value' => false ) ),
			$merge_vars
		);
	}

	/**
	 * GDPR checkbox checked: CF7 submits the label string, value must be true.
	 */
	public function test_get_merge_vars_gdpr_checked() {
		$cf7_crm = array(
			'fc_crm_type'              => 'clientify',
			'fc_crm_apipassword'       => 'api-password',
			'fc_crm_module'            => 'Contacts',
			'fc_crm_field-gdpr_accept' => 'extra-info',
		);

		$submitted_data = array(
			'extra-info' => array( 'Me gustaría estar al tanto de las novedades de Ipace' ),
		);

		$merge_vars = FORMSCRM_CF7_Settings::get_merge_vars( $cf7_crm, $submitted_data );
		$this->assertEquals(
			array( array( 'name' => 'gdpr_accept', 'value' => true ) ),
			$merge_vars
		);
	}

	/**
	 * Data provider for gdpr_accept bool normalization.
	 *
	 * @return array
	 */
	public function gdpr_falsy_values_provider() {
		return array(
			// PHP empty() → false (not accepted).
			'empty string' => array( '',      false ),
			'zero string'  => array( '0',     false ),  // empty('0') === true in PHP.
			'empty array'  => array( array(), false ),
			// Non-empty → true (accepted), regardless of label language.
			'label string' => array( 'Acepto términos',       true ),
			'false string' => array( 'false',                 true ),  // non-empty string.
			'array label'  => array( array( 'Me gustaría' ),  true ),
		);
	}

	/**
	 * gdpr_accept must be normalized to bool, never left as the field name.
	 *
	 * @dataProvider gdpr_falsy_values_provider
	 * @param mixed $submitted_value Raw value as CF7 would submit it.
	 * @param bool  $expected_value  Expected bool value in merge vars.
	 */
	public function test_get_merge_vars_gdpr_falsy_values( $submitted_value, $expected_value ) {
		$cf7_crm = array(
			'fc_crm_type'              => 'clientify',
			'fc_crm_apipassword'       => 'api-password',
			'fc_crm_module'            => 'Contacts',
			'fc_crm_field-gdpr_accept' => 'extra-info',
		);

		$submitted_data = array(
			'extra-info' => $submitted_value,
		);

		$merge_vars = FORMSCRM_CF7_Settings::get_merge_vars( $cf7_crm, $submitted_data );

		$this->assertCount( 1, $merge_vars );
		$this->assertSame( 'gdpr_accept', $merge_vars[0]['name'] );
		$this->assertIsBool( $merge_vars[0]['value'] );
		$this->assertSame( $expected_value, $merge_vars[0]['value'] );
	}
}
