<?php
define('WP_DEBUG', true);
include_once 'debugtest.php';

$settings['gf_crm_type']        = "Insightly";
$settings['gf_crm_apipassword'] = "9a6bd658-cbb6-4932-8cee-330872f0a1dd";
$settings['gf_crm_module']      = "Leads";

include_once '../crm-insightly.php';
$crmlib = new CRMLIB_INSIGHTLY();

echo '<p>Login INSIGHTLY:</p>';
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
	array('name' => 'SALUTATION', 'value' => 'Mr'),
	array('name' => 'FIRST_NAME', 'value' => 'Prueba'),
	array('name' => 'LAST_NAME', 'value' => 'Prueba 2'),

);
$leadid = $crmlib->create_entry($settings, $test_mergevars);
echo '<pre>';
print_r($leadid);
echo '<pre>';
