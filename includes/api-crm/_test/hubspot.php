<?php
define('WP_DEBUG', true);

$settings['gf_crm_apipassword'] = "e5ac4924-8391-4937-87e2-ea0ee60df5da";
$settings['gf_crm_module']      = "contact";

require_once '../crm-hubspot.php';
$crmlib = new CRMLIB_HUBSPOT();

echo '<p>Login HUBSPOT:</p>';
$login_api = $crmlib->login( $settings );

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
	array( 'name' => 'firstname', 'value' => 'David test'),
	array( 'name' => 'lastname', 'value' => 'Perez'),
	array( 'name' => 'email', 'value' => 'david@closemarketing.es'),
	array( 'name' => 'lifecyclestage', 'value' => 'lead')
);
$leadid = $crmlib->create_entry($settings, $test_mergevars);
echo '<pre>';
print_r( $leadid );
echo '<pre>';
