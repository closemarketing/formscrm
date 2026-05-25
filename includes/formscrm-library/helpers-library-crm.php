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

// Register UTM First/Last, Website Referrer, Click Identifier and Conversion Lag as columns in the Gravity Forms entries list.
add_filter( 'gform_entry_meta', 'formscrm_register_utm_entry_meta', 10, 2 );
if ( ! function_exists( 'formscrm_register_utm_entry_meta' ) ) {
	/**
	 * Registers UTM and tracking columns as entry meta in Gravity Forms.
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
		$entry_meta['formscrm_referrer'] = array(
			'label'                      => __( 'Website Referrer', 'formscrm' ),
			'is_numeric'                 => false,
			'is_default_column'          => false,
			'filter'                     => array( 'operators' => array( 'is', 'isnot', 'contains' ) ),
			'update_entry_meta_callback' => 'formscrm_update_referrer_meta',
		);
		$entry_meta['formscrm_click_id'] = array(
			'label'                      => __( 'Click Identifier', 'formscrm' ),
			'is_numeric'                 => false,
			'is_default_column'          => false,
			'filter'                     => array( 'operators' => array( 'is', 'isnot', 'contains' ) ),
			'update_entry_meta_callback' => 'formscrm_update_click_id_meta',
		);
		$entry_meta['formscrm_conversion_lag'] = array(
			'label'                      => __( 'Conversion Lag', 'formscrm' ),
			'is_numeric'                 => false,
			'is_default_column'          => false,
			'update_entry_meta_callback' => 'formscrm_update_conversion_lag_meta',
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

if ( ! function_exists( 'formscrm_update_referrer_meta' ) ) {
	/**
	 * Callback to populate formscrm_referrer entry meta on submission.
	 *
	 * @param string $key  Meta key.
	 * @param array  $lead Entry data.
	 * @param array  $form Form data.
	 * @return string
	 */
	function formscrm_update_referrer_meta( $key, $lead, $form ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( ! empty( $_COOKIE['fcrm_referrer'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			return sanitize_text_field( wp_unslash( $_COOKIE['fcrm_referrer'] ) );
		}
		return '';
	}
}

if ( ! function_exists( 'formscrm_update_click_id_meta' ) ) {
	/**
	 * Callback to populate formscrm_click_id entry meta on submission.
	 *
	 * @param string $key  Meta key.
	 * @param array  $lead Entry data.
	 * @param array  $form Form data.
	 * @return string
	 */
	function formscrm_update_click_id_meta( $key, $lead, $form ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( ! empty( $_COOKIE['fcrm_click_id'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			return sanitize_text_field( wp_unslash( $_COOKIE['fcrm_click_id'] ) );
		}
		return '';
	}
}

if ( ! function_exists( 'formscrm_update_conversion_lag_meta' ) ) {
	/**
	 * Callback to populate formscrm_conversion_lag entry meta on submission.
	 *
	 * @param string $key  Meta key.
	 * @param array  $lead Entry data.
	 * @param array  $form Form data.
	 * @return string
	 */
	function formscrm_update_conversion_lag_meta( $key, $lead, $form ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( empty( $_COOKIE['fcrm_first_visit'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			return '';
		}
		$first_visit = intval( $_COOKIE['fcrm_first_visit'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		return max( 0, time() - $first_visit );
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

// Fallback: populate AFL UTM Gravity Forms column data from FormsCRM entry meta when AFL UTM has no data.
add_filter( 'afl_wc_utm_gravityforms_get_conversion_attribution', 'formscrm_afl_utm_fallback_attribution', 10, 3 );
if ( ! function_exists( 'formscrm_afl_utm_fallback_attribution' ) ) {
	/**
	 * When AFL UTM has no UTM data for an entry, populate its column attribution
	 * from FormsCRM's stored UTM entry meta so the entry list columns are not empty.
	 *
	 * @param array  $meta_whitelist AFL UTM attribution array keyed by meta slug.
	 * @param int    $entry_id       Gravity Forms entry ID.
	 * @param string $scope          Attribution scope (e.g. 'converted').
	 * @return array
	 */
	function formscrm_afl_utm_fallback_attribution( $meta_whitelist, $entry_id, $scope ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( ! empty( $meta_whitelist['utm_source']['value'] ) ) {
			return $meta_whitelist;
		}

		$utm_map = array( 'src' => 'utm_source', 'mdm' => 'utm_medium', 'cmp' => 'utm_campaign' );

		$utm_last = gform_get_meta( $entry_id, 'formscrm_utm_last' );
		if ( ! empty( $utm_last ) ) {
			foreach ( explode( ' / ', $utm_last ) as $part ) {
				$kv = explode( ': ', $part, 2 );
				if ( 2 === count( $kv ) && isset( $utm_map[ $kv[0] ], $meta_whitelist[ $utm_map[ $kv[0] ] ] ) ) {
					$meta_whitelist[ $utm_map[ $kv[0] ] ]['value'] = $kv[1];
				}
			}
		}

		$utm_first = gform_get_meta( $entry_id, 'formscrm_utm_first' );
		if ( ! empty( $utm_first ) ) {
			$first_map = array( 'src' => 'utm_source_1st', 'mdm' => 'utm_medium_1st', 'cmp' => 'utm_campaign_1st' );
			foreach ( explode( ' / ', $utm_first ) as $part ) {
				$kv = explode( ': ', $part, 2 );
				if ( 2 === count( $kv ) && isset( $first_map[ $kv[0] ], $meta_whitelist[ $first_map[ $kv[0] ] ] ) ) {
					$meta_whitelist[ $first_map[ $kv[0] ] ]['value'] = $kv[1];
				}
			}
		}

		$conversion_lag = gform_get_meta( $entry_id, 'formscrm_conversion_lag' );
		if ( '' !== $conversion_lag && false !== $conversion_lag && isset( $meta_whitelist['conversion_lag'] ) ) {
			$lag_int = (int) $conversion_lag;
			if ( ! is_numeric( $conversion_lag ) && preg_match( '/^(\d+)\s*(\w+)/', trim( (string) $conversion_lag ), $m ) ) {
				$multipliers = array( 'day' => 86400, 'hour' => 3600, 'minute' => 60, 'second' => 1 );
				foreach ( $multipliers as $unit => $mult ) {
					if ( false !== strpos( strtolower( $m[2] ), $unit ) ) {
						$lag_int = (int) $m[1] * $mult;
						break;
					}
				}
			}
			$meta_whitelist['conversion_lag']['value'] = $lag_int;
		}

		return $meta_whitelist;
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
