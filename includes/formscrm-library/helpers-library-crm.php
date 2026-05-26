<?php
/**
 * Array of CRMS
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2021 Closemarketing
 * @version    1.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'formscrm_get_choices' ) ) {
	/**
	 * Returns dependecies CRM Choices.
	 *
	 * @return array
	 */
	function formscrm_get_choices() {
		return apply_filters(
			'formscrm_choices',
			array()
		);
	}
}

if ( ! function_exists( 'formscrm_get_crmlib_path' ) ) {
	/**
	 * Returns dependecies CRM Choices.
	 *
	 * @return array
	 */
	function formscrm_get_crmlib_path() {
		return apply_filters(
			'formscrm_crmlib_path',
			array()
		);
	}
}

if ( ! function_exists( 'formscrm_get_dependency_url' ) ) {
	/**
	 * Returns dependecies URL for forms depending of CRM.
	 *
	 * @return array
	 */
	function formscrm_get_dependency_url() {
		return apply_filters(
			'formscrm_dependency_url',
			array(
				'bitrix24',
				'espo_crm',
				'facturadirecta',
				'msdyn',
				'mspfe',
				'odoo',
				'ofiweb',
				'sugarcrm6',
				'sugarcrm7',
				'suitecrm_3_1',
				'suitecrm_4_1',
				'vtiger_6',
			)
		);
	}
}

if ( ! function_exists( 'formscrm_get_dependency_username' ) ) {
	/**
	 * Returns dependecies Username for forms depending of CRM.
	 *
	 * @return array
	 */
	function formscrm_get_dependency_username() {
		return apply_filters(
			'formscrm_dependency_username',
			array(
				'bitrix24',
				'espo_crm',
				'facturadirecta',
				'msdyn',
				'mspfe',
				'odoo',
				'salesforce',
				'solve360',
				'sugarcrm6',
				'sugarcrm7',
				'suitecrm_3_1',
				'suitecrm_4_1',
				'vtiger_6',
				'zoho',
			)
		);
	}
}

if ( ! function_exists( 'formscrm_get_dependency_password' ) ) {
	/**
	 * Returns dependecies Password for forms depending of CRM.
	 *
	 * @return array
	 */
	function formscrm_get_dependency_password() {
		return apply_filters(
			'formscrm_dependency_password',
			array(
				'bitrix24',
				'espo_crm',
				'facturadirecta',
				'msdyn',
				'mspfe',
				'sugarcrm6',
				'sugarcrm7',
				'suitecrm_3_1',
				'suitecrm_4_1',
				'zoho',
			)
		);
	}
}

if ( ! function_exists( 'formscrm_get_dependency_apipassword' ) ) {
	/**
	 * Returns dependecies API Password for forms depending of CRM.
	 *
	 * @return array
	 */
	function formscrm_get_dependency_apipassword() {
		return apply_filters(
			'formscrm_dependency_apipassword',
			array(
				'hubspot',
				'solve360',
				'vtiger_6',
				'odoo',
			)
		);
	}
}

if ( ! function_exists( 'formscrm_get_dependency_apisales' ) ) {
	/**
	 * Returns dependecies API Password for forms depending of CRM.
	 *
	 * @return array
	 */
	function formscrm_get_dependency_apisales() {
		return apply_filters(
			'formscrm_dependency_apisales',
			array(
				'salesforce',
			)
		);
	}
}

if ( ! function_exists( 'formscrm_get_dependency_odoodb' ) ) {
	/**
	 * Returns dependecies Odoo DB for forms depending of CRM.
	 *
	 * @return array
	 */
	function formscrm_get_dependency_odoodb() {
		return apply_filters(
			'formscrm_dependency_odoodb',
			array(
				'odoo',
			)
		);
	}
}

// Auto-inject UTM fields into merge_vars for all form integrations before sending to CRM.
add_filter( 'formscrm_merge_vars_before_send', 'formscrm_auto_inject_utm_merge_vars', 10, 3 );
if ( ! function_exists( 'formscrm_auto_inject_utm_merge_vars' ) ) {
	/**
	 * Adds UTM values to merge_vars for every form submission before it reaches the CRM.
	 * Reads cookies first (synchronous forms: CF7, WPForms, Elementor).
	 * Falls back to Gravity Forms entry meta when cookies are unavailable (async feed processing).
	 *
	 * @param array $merge_vars  Current merge vars array.
	 * @param mixed $extra       Field maps or settings (depends on form integration).
	 * @param array $entry       GF entry array (only passed by Gravity Forms).
	 * @return array
	 */
	function formscrm_auto_inject_utm_merge_vars( $merge_vars, $extra, $entry = array() ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$utm_vars = formscrm_get_utm_merge_vars();

		if ( empty( $utm_vars ) && ! empty( $entry['id'] ) && function_exists( 'gform_get_meta' ) ) {
			$utm_last_raw = gform_get_meta( $entry['id'], 'formscrm_utm_last' );
			if ( ! empty( $utm_last_raw ) ) {
				$utm_key_map = array( 'src' => 'utm_source', 'mdm' => 'utm_medium', 'cmp' => 'utm_campaign' );
				foreach ( explode( ' / ', $utm_last_raw ) as $part ) {
					$kv = explode( ': ', $part, 2 );
					if ( 2 === count( $kv ) && isset( $utm_key_map[ $kv[0] ] ) && '' !== $kv[1] ) {
						$utm_vars[] = array(
							'name'  => $utm_key_map[ $kv[0] ],
							'value' => sanitize_text_field( $kv[1] ),
						);
					}
				}
			}
		}

		if ( empty( $utm_vars ) ) {
			return $merge_vars;
		}

		$existing_names = array_column( $merge_vars, 'name' );
		foreach ( $utm_vars as $utm_var ) {
			if ( ! in_array( $utm_var['name'], $existing_names, true ) ) {
				$merge_vars[] = $utm_var;
			}
		}

		return $merge_vars;
	}
}

// Visitor Key.
add_action( 'init', 'formscrm_visitorkey_session', 1 );
if ( ! function_exists( 'formscrm_visitorkey_session' ) ) {
	/**
	 * Adds visitor key to the session.
	 *
	 * @return void
	 */
	function formscrm_visitorkey_session() {
		global $wp_session; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- External session library variable.

		$visitor_key = isset( $_COOKIE['vk'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['vk'] ) ) : '';
		if ( $visitor_key && ! isset( $wp_session['clientify_visitor_key'] ) ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- External session library variable.
			$wp_session['clientify_visitor_key'] = $visitor_key; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- External session library variable.
		}
	}
}
