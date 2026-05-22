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

// Register UTM First/Last as columns in the Gravity Forms entries list.
add_filter( 'gform_entry_meta', 'formscrm_register_utm_entry_meta', 10, 2 );
if ( ! function_exists( 'formscrm_register_utm_entry_meta' ) ) {
	/**
	 * Registers UTM First and Last as entry meta columns in Gravity Forms.
	 *
	 * @param array $entry_meta Existing entry meta.
	 * @param int   $form_id    Form ID.
	 * @return array
	 */
	function formscrm_register_utm_entry_meta( $entry_meta, $form_id ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$entry_meta['formscrm_utm_last'] = array(
			'label'                      => __( 'UTM (Last)', 'formscrm' ),
			'is_numeric'                 => false,
			'is_default_column'          => false,
			'filter'                     => array( 'operators' => array( 'is', 'isnot', 'contains' ) ),
			'update_entry_meta_callback' => 'formscrm_update_utm_last_meta',
		);
		$entry_meta['formscrm_utm_first'] = array(
			'label'                      => __( 'UTM (First)', 'formscrm' ),
			'is_numeric'                 => false,
			'is_default_column'          => false,
			'filter'                     => array( 'operators' => array( 'is', 'isnot', 'contains' ) ),
			'update_entry_meta_callback' => 'formscrm_update_utm_first_meta',
		);
		return $entry_meta;
	}
}

if ( ! function_exists( 'formscrm_format_utm_for_column' ) ) {
	/**
	 * Formats UTM values for display in the entries list column.
	 *
	 * @param string $prefix Cookie prefix ('' for last, 'first_' for first).
	 * @return string
	 */
	function formscrm_format_utm_for_column( $prefix ) {
		$parts  = array();
		$map    = array(
			'utm_source'   => 'src',
			'utm_medium'   => 'mdm',
			'utm_campaign' => 'cmp',
		);
		foreach ( $map as $param => $short ) {
			$cookie_key = 'fcrm_' . $prefix . $param;
			if ( ! empty( $_COOKIE[ $cookie_key ] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$parts[] = $short . ': ' . sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_key ] ) );
			}
		}
		return implode( ' / ', $parts );
	}
}

if ( ! function_exists( 'formscrm_update_utm_last_meta' ) ) {
	/**
	 * Callback to populate formscrm_utm_last entry meta on submission.
	 *
	 * @param string $key      Meta key.
	 * @param array  $lead     Entry data.
	 * @param array  $form     Form data.
	 * @return string
	 */
	function formscrm_update_utm_last_meta( $key, $lead, $form ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		return formscrm_format_utm_for_column( '' );
	}
}

if ( ! function_exists( 'formscrm_update_utm_first_meta' ) ) {
	/**
	 * Callback to populate formscrm_utm_first entry meta on submission.
	 *
	 * @param string $key      Meta key.
	 * @param array  $lead     Entry data.
	 * @param array  $form     Form data.
	 * @return string
	 */
	function formscrm_update_utm_first_meta( $key, $lead, $form ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		return formscrm_format_utm_for_column( 'first_' );
	}
}

// UTM fields for WPForms and Elementor via their existing merge_vars filters.
add_filter( 'formscrm_wpforms_merge_vars', 'formscrm_append_utm_to_merge_vars', 10, 1 );
add_filter( 'formscrm_elementor_merge_vars', 'formscrm_append_utm_to_merge_vars', 10, 1 );
if ( ! function_exists( 'formscrm_append_utm_to_merge_vars' ) ) {
	/**
	 * Appends UTM cookie values to merge_vars via filter.
	 *
	 * @param array $merge_vars Existing merge vars.
	 * @return array
	 */
	function formscrm_append_utm_to_merge_vars( $merge_vars ) {
		return array_merge( $merge_vars, formscrm_get_utm_merge_vars() );
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
