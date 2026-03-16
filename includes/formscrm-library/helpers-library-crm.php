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

if ( ! function_exists( 'formscrm_get_dependency_redsys' ) ) {
	/**
	 * Returns dependencies for Redsys-specific fields (FUC, terminal, SHA secret, mode).
	 *
	 * @return array
	 */
	function formscrm_get_dependency_redsys() {
		return apply_filters(
			'formscrm_dependency_redsys',
			array()
		);
	}
}

if ( ! function_exists( 'formscrm_get_crm_field_definitions' ) ) {
	/**
	 * Returns the canonical list of CRM connection fields for use across all form integrations.
	 * Each definition: name (short key), label, type (text|api_key|select), dependency (function name), tooltip, choices (for select).
	 *
	 * @return array<int, array{name: string, label: string, type: string, dependency: string, tooltip?: string, choices?: array<int, array{label: string, value: string}>}>
	 */
	function formscrm_get_crm_field_definitions() {
		$definitions = array(
			array(
				'name'       => 'url',
				'label'      => __( 'CRM URL', 'formscrm' ),
				'type'       => 'text',
				'dependency' => 'formscrm_get_dependency_url',
				'tooltip'    => __( 'Use the URL with http and the ending slash /.', 'formscrm' ),
			),
			array(
				'name'       => 'username',
				'label'      => __( 'Username', 'formscrm' ),
				'type'       => 'text',
				'dependency' => 'formscrm_get_dependency_username',
			),
			array(
				'name'       => 'password',
				'label'      => __( 'Password', 'formscrm' ),
				'type'       => 'api_key',
				'dependency' => 'formscrm_get_dependency_password',
				'tooltip'    => __( 'Use the password of the actual user.', 'formscrm' ),
			),
			array(
				'name'       => 'apipassword',
				'label'      => __( 'API Password for User', 'formscrm' ),
				'type'       => 'api_key',
				'dependency' => 'formscrm_get_dependency_apipassword',
				'tooltip'    => __( 'Find the API Password in the profile of the user in CRM.', 'formscrm' ),
			),
			array(
				'name'       => 'apisales',
				'label'      => __( 'Password and Security Key', 'formscrm' ),
				'type'       => 'api_key',
				'dependency' => 'formscrm_get_dependency_apisales',
				'tooltip'    => __( '"Password""SecurityKey" Go to My Settings / Reset my Security Key.', 'formscrm' ),
			),
			array(
				'name'       => 'odoodb',
				'label'      => __( 'Odoo DB Name', 'formscrm' ),
				'type'       => 'text',
				'dependency' => 'formscrm_get_dependency_odoodb',
			),
			array(
				'name'       => 'fuc',
				'label'      => __( 'Commerce number FUC', 'formscrm' ),
				'type'       => 'text',
				'dependency' => 'formscrm_get_dependency_redsys',
			),
			array(
				'name'       => 'terminal',
				'label'      => __( 'Terminal number', 'formscrm' ),
				'type'       => 'text',
				'dependency' => 'formscrm_get_dependency_redsys',
			),
			array(
				'name'       => 'sha_secret',
				'label'      => __( 'SHA Secret Key', 'formscrm' ),
				'type'       => 'api_key',
				'dependency' => 'formscrm_get_dependency_redsys',
				'tooltip'    => __( 'Secret key for SHA-256 signature. Keep it private.', 'formscrm' ),
			),
			array(
				'name'       => 'redsys_mode',
				'label'      => __( 'Mode', 'formscrm' ),
				'type'       => 'select',
				'dependency' => 'formscrm_get_dependency_redsys',
				'choices'    => array(
					array(
						'label' => __( 'Test', 'formscrm' ),
						'value' => 'test',
					),
					array(
						'label' => __( 'Production', 'formscrm' ),
						'value' => 'production',
					),
				),
			),
		);

		return apply_filters( 'formscrm_crm_field_definitions', $definitions );
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
