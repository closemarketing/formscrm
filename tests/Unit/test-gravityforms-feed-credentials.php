<?php
/**
 * Class GravityFormsFeedCredentialsTest
 *
 * Tests for resolving per-feed custom CRM credentials and reading field maps
 * that were stored in the nested format.
 *
 * Command: composer test-debug --filter GravityFormsFeedCredentialsTest
 *
 * @package Formscrm
 */

/**
 * Unit tests for formscrm_merge_custom_crm_credentials() and
 * formscrm_gf_get_nested_field_map().
 */
class GravityFormsFeedCredentialsTest extends WP_UnitTestCase {

	/**
	 * Plugin settings with a global API key.
	 *
	 * @var array
	 */
	private $settings = array(
		'fc_crm_type'        => 'holded',
		'fc_crm_apipassword' => 'global-key',
	);

	/**
	 * An empty posted password keeps the inherited credential.
	 */
	public function test_empty_posted_value_keeps_inherited_credential() {
		$result = formscrm_merge_custom_crm_credentials(
			$this->settings,
			array( '_gform_setting_fc_crm_custom_apipassword' => '' ),
			array()
		);

		$this->assertSame( 'global-key', $result['fc_crm_apipassword'] );
	}

	/**
	 * A whitespace-only posted value is treated as empty.
	 */
	public function test_whitespace_posted_value_is_ignored() {
		$result = formscrm_merge_custom_crm_credentials(
			$this->settings,
			array( '_gform_setting_fc_crm_custom_apipassword' => '   ' ),
			array( 'fc_crm_custom_apipassword' => 'feed-key' )
		);

		$this->assertSame( 'feed-key', $result['fc_crm_apipassword'] );
	}

	/**
	 * A posted value wins over feed meta.
	 */
	public function test_posted_value_wins() {
		$result = formscrm_merge_custom_crm_credentials(
			$this->settings,
			array( '_gform_setting_fc_crm_custom_apipassword' => ' posted-key ' ),
			array( 'fc_crm_custom_apipassword' => 'feed-key' )
		);

		$this->assertSame( 'posted-key', $result['fc_crm_apipassword'] );
	}

	/**
	 * Saved feed meta is used when nothing is posted.
	 */
	public function test_feed_meta_used_when_not_posted() {
		$result = formscrm_merge_custom_crm_credentials(
			$this->settings,
			array(),
			array( 'fc_crm_custom_apipassword' => 'feed-key' )
		);

		$this->assertSame( 'feed-key', $result['fc_crm_apipassword'] );
	}

	/**
	 * Empty feed meta keeps the inherited credential.
	 */
	public function test_empty_feed_meta_keeps_inherited_credential() {
		$result = formscrm_merge_custom_crm_credentials(
			$this->settings,
			array(),
			array( 'fc_crm_custom_apipassword' => '' )
		);

		$this->assertSame( 'global-key', $result['fc_crm_apipassword'] );
	}

	/**
	 * Whitespace-only or non-scalar feed meta keeps the inherited credential.
	 */
	public function test_whitespace_feed_meta_keeps_inherited_credential() {
		$whitespace = formscrm_merge_custom_crm_credentials( $this->settings, array(), array( 'fc_crm_custom_apipassword' => '  ' ) );
		$non_scalar = formscrm_merge_custom_crm_credentials( $this->settings, array(), array( 'fc_crm_custom_apipassword' => array( 'x' ) ) );

		$this->assertSame( 'global-key', $whitespace['fc_crm_apipassword'] );
		$this->assertSame( 'global-key', $non_scalar['fc_crm_apipassword'] );
	}

	/**
	 * Falls back to the "fc_crm_custom_*" plugin setting and removes it.
	 */
	public function test_falls_back_to_custom_plugin_setting() {
		$settings = array_merge( $this->settings, array( 'fc_crm_custom_url' => 'https://crm.example' ) );
		$result   = formscrm_merge_custom_crm_credentials( $settings, array(), array() );

		$this->assertSame( 'https://crm.example', $result['fc_crm_url'] );
		$this->assertArrayNotHasKey( 'fc_crm_custom_url', $result );
	}

	/**
	 * Non-scalar posted values are ignored.
	 */
	public function test_non_scalar_posted_value_is_ignored() {
		$result = formscrm_merge_custom_crm_credentials(
			$this->settings,
			array( '_gform_setting_fc_crm_custom_apipassword' => array( 'x' ) ),
			array()
		);

		$this->assertSame( 'global-key', $result['fc_crm_apipassword'] );
	}

	/**
	 * A different CRM type never inherits the global credential.
	 */
	public function test_other_crm_type_does_not_inherit_global_credential() {
		$result = formscrm_merge_custom_crm_credentials(
			$this->settings,
			array( '_gform_setting_fc_crm_custom_apipassword' => '' ),
			array(),
			false
		);

		$this->assertArrayNotHasKey( 'fc_crm_apipassword', $result );
	}

	/**
	 * A different CRM type still uses its own feed credential.
	 */
	public function test_other_crm_type_uses_feed_credential() {
		$result = formscrm_merge_custom_crm_credentials(
			$this->settings,
			array(),
			array( 'fc_crm_custom_apipassword' => 'feed-key' ),
			false
		);

		$this->assertSame( 'feed-key', $result['fc_crm_apipassword'] );
	}

	/**
	 * Reads a nested field map.
	 */
	public function test_reads_nested_field_map() {
		$meta = array(
			'listFields' => array(
				array( 'key' => 'name', 'custom_key' => '', 'value' => '1', 'custom_value' => '' ),
				array( 'key' => 'email', 'custom_key' => '', 'value' => '2', 'custom_value' => '' ),
				array( 'key' => 'phone', 'custom_key' => '', 'value' => '', 'custom_value' => '' ),
			),
		);

		$this->assertSame(
			array(
				'name'  => '1',
				'email' => '2',
			),
			formscrm_gf_get_nested_field_map( $meta, 'listFields' )
		);
	}

	/**
	 * A map with only unmapped rows is empty, so the adminLabel fallback runs.
	 */
	public function test_nested_field_map_with_only_blank_rows_is_empty() {
		$meta = array(
			'listFields' => array(
				array( 'key' => 'name', 'value' => '' ),
				array( 'key' => 'email', 'value' => '' ),
			),
		);

		$this->assertSame( array(), formscrm_gf_get_nested_field_map( $meta, 'listFields' ) );
	}

	/**
	 * Skips invalid mappings and non-array meta.
	 */
	public function test_nested_field_map_ignores_invalid_data() {
		$meta = array(
			'listFields' => array(
				'not-an-array',
				array( 'value' => '1' ),
				array( 'key' => 'name', 'value' => array( '1' ) ),
				array( 'key' => 'email', 'value' => 2 ),
			),
		);

		$this->assertSame( array( 'email' => '2' ), formscrm_gf_get_nested_field_map( $meta, 'listFields' ) );
		$this->assertSame( array(), formscrm_gf_get_nested_field_map( array( 'listFields' => '1' ), 'listFields' ) );
		$this->assertSame( array(), formscrm_gf_get_nested_field_map( false, 'listFields' ) );
	}

	/**
	 * A nested field map is rewritten as "{name}_{key}" settings.
	 */
	public function test_flatten_nested_field_map() {
		$settings = array(
			'feedName'   => 'Feed',
			'listFields' => array(
				array( 'key' => 'name', 'custom_key' => '', 'value' => '1', 'custom_value' => '' ),
				array( 'key' => 'email', 'custom_key' => '', 'value' => '2', 'custom_value' => '' ),
				array( 'key' => 'phone', 'custom_key' => '', 'value' => '', 'custom_value' => '' ),
			),
		);

		$this->assertSame(
			array(
				'feedName'         => 'Feed',
				'listFields_name'  => '1',
				'listFields_email' => '2',
				'listFields_phone' => '',
			),
			formscrm_gf_flatten_nested_field_map( $settings, 'listFields' )
		);
	}

	/**
	 * The text of an "Add Custom Value" mapping is kept in its own setting.
	 */
	public function test_flatten_nested_field_map_keeps_custom_value() {
		$settings = array(
			'listFields' => array(
				array( 'key' => 'source', 'custom_key' => '', 'value' => 'gf_custom', 'custom_value' => 'Web <b>{id:1}</b>' ),
				array( 'key' => 'name', 'custom_key' => '', 'value' => '1', 'custom_value' => 'ignored' ),
			),
		);

		$this->assertSame(
			array(
				'listFields_source'       => 'gf_custom',
				'listFieldsCustom_source' => 'Web {id:1}',
				'listFields_name'         => '1',
			),
			formscrm_gf_flatten_nested_field_map( $settings, 'listFields' )
		);
	}

	/**
	 * Settings already saved in the "{name}_{key}" format are left untouched.
	 */
	public function test_flatten_nested_field_map_keeps_registered_values() {
		$settings = array( 'listFields_name' => '1' );
		$this->assertSame( $settings, formscrm_gf_flatten_nested_field_map( $settings, 'listFields' ) );

		$settings = array(
			'listFields_name' => '5',
			'listFields'      => array( array( 'key' => 'name', 'value' => '1' ) ),
		);
		$this->assertSame( array( 'listFields_name' => '5' ), formscrm_gf_flatten_nested_field_map( $settings, 'listFields' ) );
	}

	/**
	 * Invalid mappings are skipped and non-array settings are returned as they are.
	 */
	public function test_flatten_nested_field_map_ignores_invalid_data() {
		$settings = array(
			'listFields' => array(
				'not-an-array',
				array( 'value' => '1' ),
				array( 'key' => array( 'x' ), 'value' => '1' ),
				array( 'key' => 'name', 'value' => array( '1' ) ),
				array( 'key' => 'email', 'value' => 2 ),
			),
		);

		$this->assertSame(
			array(
				'listFields_name'  => '',
				'listFields_email' => '2',
			),
			formscrm_gf_flatten_nested_field_map( $settings, 'listFields' )
		);
		$this->assertSame( false, formscrm_gf_flatten_nested_field_map( false, 'listFields' ) );
		$this->assertSame( array( 'listFields' => '1' ), formscrm_gf_flatten_nested_field_map( array( 'listFields' => '1' ), 'listFields' ) );
	}
}
