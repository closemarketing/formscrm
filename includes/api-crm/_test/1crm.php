<?php
define('WP_DEBUG', true);
include_once 'debugtest.php';
/*
$settings['gf_crm_username']    = "admin";
$settings['gf_crm_url']         = "https://crm.thegeekhive.io/";
$settings['gf_crm_apipassword'] = "admin1";
*/
$settings['gf_crm_type']        = "1CRM";
$settings['gf_crm_module']      = "Leads";
$settings['gf_crm_username']    = "admin";
$settings['gf_crm_url']         = "https://crm.thegeekhive.io";
$settings['gf_crm_password'] = "admin1";

include_once '../crm-1CRM.php';
$crmlib = new CRMLIB_1CRM();

echo '<p>Login 1CRM:</p>';
$login_api = $crmlib->login($settings);

echo '<pre>';
print_r($login_api);
echo '</pre>';

echo '<p>List Modules</p>';
$list_modules = $crmlib->list_modules($settings);
echo '<pre>';
//print_r($list_modules);
echo '<pre>';

echo '<p>List Fields</p>';
$list_fields = $crmlib->list_fields($settings);
echo '<pre>';
print_r($list_fields);
echo '<pre>';

echo '<p>Create lead from test mergevar</p>';


$test_mergevars = array(
                array( 'name' => 'name', 'value' => 'User test'),
                array( 'name' => 'description', 'value' => 'User test'),
                array( 'name' => 'status', 'value' => 'New'),
                array( 'name' => 'last_name', 'value' => 'New')
            );
$leadid = $crmlib->create_entry($settings, $test_mergevars);
echo '<pre>';
print_r($leadid);
echo '<pre>';
