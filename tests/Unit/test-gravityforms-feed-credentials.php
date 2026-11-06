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
}
