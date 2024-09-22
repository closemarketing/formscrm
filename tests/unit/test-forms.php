<?php
/**
 * Class TestsFORMS
 *
 * @package Formscrm
 */

/**
 * Tests CRM Connections.
 */
class TestsFORMS extends WP_UnitTestCase {

	private function generateRandomString( $length = 10 ) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}
	/**
	 * Tests for woocommerce
	 *
	 * @return void
	 */
	public function test_woocommerce() {
		require_once dirname( dirname( __FILE__ ) ) . '/includes/formscrm-library/class-woocommerce.php';

		echo 'Test woocommerce';

		$forms_woocommerce = new FormsCRM_WooCommerce();

		// Test Settings.
		$this->assertNotEmpty( $forms_woocommerce->get_settings() );



		ob_flush();
	}

}
