<?php
/**
 * Gravity Forms global helpers.
 *
 * @package    FormsCRM
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2020 Closemarketing
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'gf_crm' ) ) {
	/**
	 * Returns the Gravity Forms CRM instance.
	 *
	 * @return \CLOSE\FormsCRM\Integrations\GravityForms\AddOn The CRM instance.
	 */
	function gf_crm() { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Legacy function name for Gravity Forms compatibility.
		return \CLOSE\FormsCRM\Integrations\GravityForms\AddOn::get_instance();
	}
}
