<?php

define('WP_DEBUG',true);
include_once 'debugtest.php';

define( 'WP_USE_THEMES', false ); // Don't load theme support functionality
require( '../../../../../../wp-load.php' );

/*
$test_accountname ="a533035";
$test_username = "venky4crm@gmail.com";
$test_password = "biz@02556";
*/
/*
$test_url ="https://closemarketing.facturadirecta.com/";
$test_username = "david@closemarketing.es";
$test_password = "9BF6Q9vdy";
$test_token = "7c6159752e4c63b4843ffc42c1967fd7";
*/
$settings['gf_crm_type']        = "facturadirecta";
$settings['gf_crm_username']    = "david@closemarketing.es";
$settings['gf_crm_url']         = 'https://closemarketing.facturadirecta.com/';
$settings['gf_crm_apipassword'] = "9BF6Q9vdy";
$settings['gf_crm_module']      = "Leads";


/////// FACTURA DIRECTA ///////
include_once('../class-crm-facturadirecta.php');
$crmlib = new CRMLIB_FACTURADIRECTA();

echo '<p>Login Factura Directa:</p>';
$login_api =$crmlib->login($settings);
echo '<pre>';
print_r($login_api);
echo '</pre>';

echo'<p>List Modules</p>';
$list_modules = $crmlib->list_modules($settings);
echo '<pre>';
print_r($list_modules);
echo '<pre>';

echo '<p>List Fields</p>';
$fields= $crmlib->list_fields($settings);
echo '<pre>';
print_r($fields) ;
echo '<pre>';


 echo '<p>Create client from test mergevar</p>';
$test_mergevars = array(
            array( 'name' => 'First Name', 'value' => 'David Prueba'),
            array( 'name' => 'Last Name', 'value' => 'K')
        );
$clientid = $crmlib->create_entry($settings, $test_mergevars);
echo '<pre>';
print_r($clientid);
echo '<pre>';
