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

$choices_crm = array(
	//array('label' => 'Bitrix24','value'  => 'bitrix24'),
	//array('label' => 'ESPO CRM','value'  => 'espo_crm'),
	//array('label' => 'FacturaDirecta','value'  => 'facturadirecta'),
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
	//array('label' => 'HubSpot','value'  => 'hubspot'),
	//array('label' => 'Microsoft Dynamics CRM','value'  => 'msdyn'),
	//array('label' => 'Microsoft Dynamics CRM (on premise)','value'  => 'mspfe'),
	//array('label' => 'Odoo 8','value'  => 'odoo8'),
	//array('label' => 'Odoo 9','value'  => 'odoo9'),
	array('label' => 'OFIWEB','value'  => 'ofiweb'),
	//array('label' => 'Salesforce','value'  => 'salesforce'),
	//array('label' => 'Solve360','value'  => 'solve360'),
	//array('label' => 'SugarCRM 6', 'value' => 'sugarcrm6'),
	//array('label' => 'SugarCRM 7', 'value' => 'sugarcrm7'),
	//array('label' => 'SuiteCRM 3', 'value'  => 'suitecrm_3_1'),
	//array('label' => 'SuiteCRM 4', 'value'  => 'suitecrm_4_1'),
	//array('label' => 'vTiger 6', 'value' => 'vtiger_6'),
	//array('label' => 'Zoho CRM','value'  => 'zoho')
);