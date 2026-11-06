<?php
/**
 * Class CustomValueHelpersTest
 *
 * Tests for the shared helpers behind the "Custom value" field mapping option.
 *
 * Command: composer test-debug --filter CustomValueHelpersTest
 *
 * @package Formscrm
 */

/**
 * Unit tests for custom value helpers.
 */
class CustomValueHelpersTest extends WP_UnitTestCase {

	/**
	 * Option is marked selected only for the custom value.
	 */
	public function test_option_html_selected_state() {
		$this->assertStringContainsString( 'value="formscrm_custom" selected="selected"', formscrm_custom_value_option_html( 'formscrm_custom' ) );
		$this->assertStringNotContainsString( 'selected', formscrm_custom_value_option_html( '3' ) );
	}

	/**
	 * Input is hidden unless the custom value is selected, and escapes its value.
	 */
	public function test_input_html_visibility_and_escaping() {
		$visible = formscrm_custom_value_input_html( 'map[x]', 'A "quoted" <b>value</b>', 'formscrm_custom' );
		$hidden  = formscrm_custom_value_input_html( 'map[x]', '', '' );

		$this->assertStringNotContainsString( 'display:none', $visible );
		$this->assertStringContainsString( 'name="map[x]"', $visible );
		$this->assertStringContainsString( 'value="A &quot;quoted&quot; &lt;b&gt;value&lt;/b&gt;"', $visible );
		$this->assertStringContainsString( 'display:none', $hidden );
	}

	/**
	 * Replaces known placeholders, joins arrays and keeps unknown ones.
	 */
	public function test_replace_id_placeholders() {
		$data = array(
			'name'   => 'Ana',
			'3'      => 'Madrid',
			'topics' => array( 'SEO', 'Ads' ),
		);

		$this->assertSame( 'Web', formscrm_replace_id_placeholders( 'Web', $data ) );
		$this->assertSame( 'Ana - Madrid', formscrm_replace_id_placeholders( '{id:name} - {id:3}', $data ) );
		$this->assertSame( 'SEO, Ads', formscrm_replace_id_placeholders( '{id:topics}', $data ) );
		$this->assertSame( '{id:missing}', formscrm_replace_id_placeholders( '{id:missing}', $data ) );
	}
}
