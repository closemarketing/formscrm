<?php
define('WP_DEBUG', true);
include_once 'debugtest.php';
/*
$settings['gf_crm_type']        = "vTiger 7";
$settings['gf_crm_username']    = "demo";
$settings['gf_crm_url']         = "http://demo7.vtexperts.com/vtigercrm7demo/";
$settings['gf_crm_apipassword'] = "yhGaR9dENYrJGj6v";
$settings['gf_crm_module']      = "Leads";
*/

$settings['gf_crm_type']        = "vTiger 7";
$settings['gf_crm_username']    = "demo";
$settings['gf_crm_url']         = 'http://demo7.vtexperts.com/vtigercrm7demo/';
$settings['gf_crm_apipassword'] = "uNFdpMu4cYGHMUNd";
$settings['gf_crm_module']      = "Leads";

include_once '../crm-vtiger_7.php';
$crmlib = new CRMLIB_VTIGER7();

echo '<p>Login VTIGER:</p>';
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
echo '<pre>';

echo '<p>Create lead from test mergevar</p>';

$test_mergevars = array(
	array('name' => 'firstname', 'value' => 'Name'),
	array('name' => 'lastname', 'value' => 'Lastname'),
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
