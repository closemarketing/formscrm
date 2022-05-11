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

/**
 * Returns dependecies CRM Choices.
 *
 * @return array
 */
function formscrm_get_choices() {
	return apply_filters(
		'formscrm_choices',
		array(
			array(
				'label' => 'Holded',
				'value' => 'holded',
			),
			array(
				'label' => 'Clientify',
				'value' => 'clientify',
			),
			array(
				'label' => 'AcumbaMail',
				'value' => 'acumbamail',
			),
		)
	);
}
/**
 * Returns dependecies CRM Choices.
 *
 * @return array
 */
function formscrm_get_crmlib_path() {
	return apply_filters(
		'formscrm_crmlib_path',
		array(
			'holded'     => 'class-crmlib-holded.php',
			'clientify'  => 'class-crmlib-clientify.php',
			'acumbamail' => 'class-crmlib-acumbamail.php',
		)
	);
}

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
			'odoo8',
			'odoo9',
			'ofiweb',
			'sugarcrm6',
			'sugarcrm7',
			'suitecrm_3_1',
			'suitecrm_4_1',
			'vtiger_6',
		),
	);
}

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
			'odoo8',
			'odoo9',
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
			'odoo8',
			'odoo9',
			'sugarcrm6',
			'sugarcrm7',
			'suitecrm_3_1',
			'suitecrm_4_1',
			'zoho',
		)
	);
}

/**
 * Returns dependecies API Password for forms depending of CRM.
 *
 * @return array
 */
function formscrm_get_dependency_apipassword() {
	return apply_filters(
		'formscrm_dependency_apipassword',
		array(
			'holded',
			'clientify',
			'hubspot',
			'solve360',
			'vtiger_6',
		)
	);
}

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

/**
 * Returns dependecies Odoo DB for forms depending of CRM.
 *
 * @return array
 */
function formscrm_get_dependency_odoodb() {
	return apply_filters(
		'formscrm_dependency_odoodb',
		array(
			'odoo8',
			'odoo9',
		)
	);
}
