<?php
/**
 * Tests for the pre-namespace class aliases.
 *
 * @package FormsCRM
 */

use CLOSE\FormsCRM\Admin\ErrorLog;
use CLOSE\FormsCRM\CRM\AbstractCRM;
use CLOSE\FormsCRM\CRM\Clientify;
use CLOSE\FormsCRM\CRM\Holded;

/**
 * Class Test_Legacy_Aliases
 */
class Test_Legacy_Aliases extends WP_UnitTestCase {

	/**
	 * Legacy CRM class names resolve to the namespaced classes.
	 */
	public function test_legacy_crm_classes_are_aliased() {
		$this->assertTrue( class_exists( 'CRMLIB_HOLDED' ) );
		$this->assertInstanceOf( Holded::class, new CRMLIB_HOLDED() );
		$this->assertInstanceOf( AbstractCRM::class, new CRMLIB_Clientify() );
		$this->assertSame( Clientify::class, get_class( new CRMLIB_Clientify() ) );
	}

	/**
	 * Add-ons can still extend the legacy CRM class names.
	 */
	public function test_legacy_class_can_be_extended() {
		eval( 'class CRMLIB_LEGACY_ALIAS_TEST extends CRMLIB_HOLDED {}' ); // phpcs:ignore Squiz.PHP.Eval.Discouraged
		$this->assertInstanceOf( Holded::class, new CRMLIB_LEGACY_ALIAS_TEST() );
	}

	/**
	 * Legacy admin class names resolve to the namespaced classes.
	 */
	public function test_legacy_error_log_alias() {
		$this->assertTrue( class_exists( 'FORMSCRM_Error_Log' ) );
		$this->assertInstanceOf( ErrorLog::class, $GLOBALS['formscrm_error_log'] );
	}

	/**
	 * GFCRM is not aliased while Gravity Forms is not loaded, so checks such as
	 * class_exists( 'GFCRM' ) keep returning false instead of a fatal error.
	 */
	public function test_gfcrm_alias_requires_gravity_forms() {
		if ( class_exists( 'GFFeedAddOn' ) ) {
			$this->markTestSkipped( 'Gravity Forms is loaded.' );
		}
		$this->assertFalse( class_exists( 'GFCRM' ) );
	}

	/**
	 * Built-in CRMs load through the namespaced class map.
	 */
	public function test_get_api_class_returns_namespaced_crm() {
		$this->assertInstanceOf( Holded::class, formscrm_get_api_class( 'holded' ) );
		$this->assertInstanceOf( Clientify::class, formscrm_get_api_class( 'Clientify' ) );
	}

}
