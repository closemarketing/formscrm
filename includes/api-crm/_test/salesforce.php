<?php
define('WP_DEBUG',true);
include_once 'debugtest.php';

define( 'WP_USE_THEMES', false ); // Don't load theme support functionality
require( '../../../../../../wp-load.php' );

//$test_username ="david@closemarketing.es";
//$test_password = "granada10";
//$test_url ="https://eu5.salesforce.com/";
/*
$test_username ="dev@closemarketing.es";
$password = "Pt3Haqm3";
$token = "F3C6ZyL35VndqICTiXfcyY7a4";
$test_password = $password.$token;
*/

$settings['gf_crm_type']        = "vTiger 7";
$settings['gf_crm_username']    = "dev@closemarketing.es";
$settings['gf_crm_url']         = 'http://demo7.vtexperts.com/vtigercrm7demo/';
$settings['gf_crm_apipassword'] = "Pt3Haqm3F3C6ZyL35VndqICTiXfcyY7a4";
$settings['gf_crm_module']      = "Leads";

require '../class-crm-salesforce.php';


////////////////////////////////
$crmlib = new CRMLIB_SALESFORCE();
echo '<p>Login SALESFORCE CRM:</p>';
$login_api = $crmlib->login($settings);

echo '<pre>';
print_r($login_api);
echo '</pre>';

echo '<p>List Modules</p>';
$list_modules = $crmlib->list_modules($settings);
echo '<pre>';
print_r($list_modules);
echo '<pre>';

echo '<p>List Fields</p>';
$list_fields = $crmlib->list_fields($settings);
echo '<pre>';
print_r($list_fields);
echo '</pre>';

echo '<p>Create lead from test mergevar</p>';


$test_mergevars = array(
                array( 'name' => 'FirstName', 'value' => 'Kola'),
                array( 'name' => 'LastName', 'value' => 'V'),
				array( 'name' => 'Phone', 'value' => '510-555-5555'),
				array( 'name' => 'Salutation', 'value' => 'Mr.'),
				array( 'name' => 'Company', 'value' => 'Closemarketing'),
			);

$leadid = $crmlib->create_entry($settings, $test_mergevars);
echo '<pre>';
print_r($leadid);
echo '<pre>';

