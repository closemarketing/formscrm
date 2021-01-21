<?php
/*
Acceso Zoho.
https://crm.zoho.com
Usuario: david@closemarketing.es
Contraseña: gZEh2MQLys6zJs
*/
$settings['gf_crm_type']        = "Zoho";
$settings['gf_crm_username']    = "closemarketing";
$settings['gf_crm_email']       = "david@closemarketing";
$settings['gf_crm_module']      = "Leads";

$settings['gf_crm_clientid']    = '1000.FE5OON33LQHL779845S0Q0ZI1EC1CH';
$settings['gf_crm_apipassword'] = 'e7778de4f7e2e52a238f300b5e59c6f202cf8a2b65';
$settings['gf_crm_redirecturi'] = 'http://localhost/oauthcallback';

////////////////////////////////
include_once '../class-crm-zoho.php';
$crmlib = new CRMLIB_ZOHO();

echo '<h1>Login ZOHO CRM:</h1>';
$login_api = $crmlib->login( $settings );
echo '<pre>';
print_r( $login_api );
echo '</pre>';

echo '<h2>List Modules</h2>';
$list_modules = $crmlib->list_modules( $settings );
echo '<pre>';
print_r($list_modules);
echo '<pre>';

echo '<h2>List Fields</h2>';
$list_fields = $crmlib->list_fields( $settings );
echo '<pre>';
print_r($list_fields);
echo '<pre>';

echo '<h2>Create lead from test mergevar</h2>';
$test_mergevars = array(
			array( 'name' => 'First Name', 'value' => 'Nombre'),
			array( 'name' => 'Last Name', 'value' => 'K')
		);

$leadid = $crmlib->create_entry($settings, $test_mergevars);

echo '<pre>';
print_r($leadid);
echo '<pre>';
