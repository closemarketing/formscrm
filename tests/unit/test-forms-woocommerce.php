<?php
/**
 * Class TestsFORMS
 *
 * @package Formscrm
 */

/**
 * Tests CRM Connections.
 */
class TestsWooCommerce extends WP_UnitTestCase {
	/**
	 * Tests for woocommerce
	 *
	 * @return void
	 */
	public function test_woocommerce() {
		require_once FORMSCRM_PLUGIN_PATH . 'includes/formscrm-library/class-woocommerce.php';

		$forms_woocommerce = new FormsCRM_WooCommerce();

		// Test Settings.
		$this->assertNotEmpty( $forms_woocommerce->get_settings() );
	}
}
