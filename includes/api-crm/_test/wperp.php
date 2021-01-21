<?php

define('WP_DEBUG',true);
include_once 'debugtest.php';
/*
$test_username ="David";
$test_password = "mgxC0[KWh";
$test_url ="https://erp.closemarketing.es/";
*/

$settings['gf_crm_type']        = "wperp";
$settings['gf_crm_username']    = "David";
$settings['gf_crm_url']         = 'https://erp.closemarketing.es/';
$settings['gf_crm_apipassword'] = "mgxC0[KWh";
$settings['gf_crm_module']      = "Leads";


include '../crm-wperp.php';
//////////

$crmlib = new GFCRM_WPERP();

echo '<p>Login WPERP CRM:</p>';
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
                array( 'name' => 'name', 'value' => 'User test'),
                array( 'name' => 'description', 'value' => 'User test'),
                array( 'name' => 'status', 'value' => 'New')
            );
$leadid = $crmlib->create_lead($settings, $test_mergevars);
echo '<pre>';
print_r($leadid);
echo '<pre>';
