<?php
/*
$test_username ="admin";
$test_password = "1";
$test_url ="http://demo.espocrm.com/basic/";
*/
define('WP_DEBUG',true);
include_once 'debugtest.php';

/*
$test_username ="website";
$test_password = "powermaster";
$test_url ="https://crm.collatero.de/";
*/
$settings = array();



$settings['gf_crm_type']        = "ESPO CRM";
$settings['gf_crm_username']    = "admin";
$settings['gf_crm_url']         = 'http://demo.espocrm.com/basic/';
$settings['gf_crm_apipassword'] = "1";
$settings['gf_crm_module']      = "Leads";

include '../class-crm-espo_crm.php';
$crmlib = new CRMLIB_ESPOCRM();




/* ------------------------ Functions Calling ---------------------*/

echo '<p>Login ESPOCRM:</p>';
$login_api = $crmlib->login($settings);

echo '<pre>';
print_r($login_api);
echo '</pre>';

echo '<p>List Modules</p>';
$list_modules = $crmlib->list_modules($settings);
echo '<pre>';
print_r($list_modules);

echo '</pre>';

echo '<p>List Fields</p>';
$list_fields = $crmlib->list_fields($settings);
echo '<pre>';
print_r($list_fields);
echo '<pre>';

echo '<p>Create lead from test mergevar</p>';
$test_mergevars = array(
	array('name' => 'firstname', 'value' => 'User &test'),
	array('name' => 'lastname', 'value' => 'User &test'),
	array('name' => 'description', 'value' => 'User test'),
	array('name' => 'lastname', 'value' => '#'),
	array('name' => 'status', 'value' => 'New'),
	array('name' => 'assigned_user_id', 'value' => '19x1'),
	array('name' => 'cf_757', 'value' => 'No'),

);

$leadid = $crmlib->create_entry($settings, $test_mergevars);
echo '<pre>';

print_r($leadid);
echo '<pre>';
