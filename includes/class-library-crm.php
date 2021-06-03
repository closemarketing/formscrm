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

function formscrm_get_choices() {
	return apply_filters(
		'formscrm_choices',
		array(
			array(
				'label'  => 'Holded',
				'value'  => 'holded',
				'fields' => array(
					array(
						'key'     => 'apipassword',
						'label'   => __( 'API Password for User', 'formscrm' ),
						'type'    => 'api_key',
						'tooltip' => __( 'Find the API Password in the profile of the user in CRM.', 'formscrm' ),
					),
				),
			),
		)
	);
}

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

function formscrm_get_dependency_username() {
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

