<?php
/**
 * Tests for the Forms WooCommerce integration.
 *
 * @package FormsCRM
 */

namespace FormsCRM\Tests\Unit;

use WP_UnitTestCase;

class Test_Forms_WooCommerce extends WP_UnitTestCase {

	/**
	 * Test example method.
	 */
	public function test_example() {
		$this->assertTrue( true );
	}

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
