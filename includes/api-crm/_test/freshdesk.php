<?php
define('WP_DEBUG', true);
include_once 'debugtest.php';

/*
 */

$settings['gf_crm_type']        = "FreshDesk";
$settings['gf_crm_url']         = "https://closemarketing.freshdesk.com";
$settings['gf_crm_password']    = "B66WQ>b4Lr";
$settings['gf_crm_apipassword'] = "4i8ckjhOZe9lipGGaU4k";
$settings['gf_crm_module']      = "ticket";

include_once '../crm-freshdesk.php';
$crmlib = new CRMLIB_FRESHDESK();

echo '<p>Login FRESHDESK:</p>';
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
$list_fields = $crmlib->list_fields($settings, $settings['gf_crm_module']);
echo '<pre>';
print_r($list_fields);
echo '<pre>';

echo '<p>Create lead from test mergevar</p>';

$test_mergevars = array(
	array('name' => 'email', 'value' => 'david@closemarketing.es'),
	array('name' => 'subject', 'value' => 'Subject User &test'),
	array('name' => 'status', 'value' => 2),
	array('name' => 'priority', 'value' => 1),
	array('name' => 'description', 'value' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Mauris placerat, arcu vitae scelerisque lobortis, mi urna maximus nulla, vitae tristique elit leo nec lorem. Vestibulum sagittis tortor mauris, sed condimentum ex ornare a. Interdum et malesuada fames ac ante ipsum primis in faucibus. Pellentesque eget quam cursus, tristique nulla sit amet, efficitur erat.'),

);
$leadid = $crmlib->create_entry($settings, $test_mergevars);
echo '<pre>';
print_r($leadid);
echo '<pre>';
