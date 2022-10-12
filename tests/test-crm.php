<?php
/**
 * Class TestsCRM
 *
 * @package Formscrm
 */

/**
 * Tests CRM Connections.
 */
class TestsCRM extends WP_UnitTestCase {

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
	 * Tests for Clientify
	 *
	 * @return void
	 */
	public function test_clientify() {
		require_once dirname( dirname( __FILE__ ) ) . '/includes/crm-library/class-crmlib-clientify.php';
		require_once 'credentials.php';

		$settings['fc_crm_type'] = "Clientify";
		$settings['fc_crm_apipassword'] = FC_CRM_CLIENTIFY_APIKEY; // const FC_CRM_CLIENTIFY_APIKEY;
		$settings['fc_crm_module'] = "contacts";

		echo 'Test clientify';

		$crm_clientify = new CRMLIB_Clientify();

		// Test Login.
		$this->assertTrue( $crm_clientify->login( $settings ) );

		// Test Modules.
		$list_modules = $crm_clientify->list_modules( $settings );
		$this->assertNotEmpty( $list_modules );

		foreach ( $list_modules as $module ) {
			$this->assertNotEmpty( $crm_clientify->list_fields( $settings ), $module['name'] );
		}
		$settings['fc_crm_module'] = "contacts";

		$test_mergevars = array(
			array( 'name' => 'first_name', 'value' => 'David Prueba'),
			array( 'name' => 'last_name', 'value' => 'User test'),
			array( 'name' => 'status', 'value' => 'cold-lead '),
			array( 'name' => 'email', 'value' => 'david+' . $this->generateRandomString( 4 ) . '@close.marketing' ),
			array( 'name' => 'phone', 'value' => '669904426'),
		);
		$create_entry = $crm_clientify->create_entry( $settings, $test_mergevars );
		$this->assertNotEmpty( $create_entry );
		$this->assertArrayHasKey( 'id', $create_entry );

		ob_flush();
	}
	/**
	 * Tests for holded
	 *
	 * @return void
	 */
	public function test_holded() {
		require_once dirname( dirname( __FILE__ ) ) . '/includes/crm-library/class-crmlib-holded.php';

		$settings['fc_crm_type'] = "Holded";
		$settings['fc_crm_apipassword'] = FC_CRM_HOLDED_APIKEY; // const FC_CRM_HOLDED_APIKEY;
		$settings['fc_crm_module'] = "contacts";

		echo 'Test Holded';

		$crm_holded = new CRMLIB_Holded();

		// Test Login.
		$this->assertTrue( $crm_holded->login( $settings ) );

		// Test Modules.
		$list_modules = $crm_holded->list_modules( $settings );
		$this->assertNotEmpty( $list_modules );

		foreach ( $list_modules as $module ) {
			$this->assertNotEmpty( $crm_holded->list_fields( $settings, $module['name'] ) );
		}
		$settings['fc_crm_module'] = "contacts";

		$test_mergevars = array(
			array( 'name' => 'name', 'value' => 'User test'),
			array( 'name' => 'tradename', 'value' => 'User test'),
			array( 'name' => 'code', 'value' => 'B1999999'),
			array( 'name' => 'phone', 'value' => '823322323'),
			array( 'name' => 'mobile', 'value' => '23212323'),
			array( 'name' => 'address', 'value' => 'Street'),
			array( 'name' => 'email', 'value' => 'unit_test+' . $this->generateRandomString( 4 ) . '@close.marketing' ),
			array( 'name' => 'phone', 'value' => '9999999999'),
		);

		$create_entry = $crm_holded->create_entry( $settings, $test_mergevars );
		$this->assertNotEmpty( $create_entry );
		$this->assertArrayHasKey( 'id', $create_entry );

		ob_flush();
	}
}
