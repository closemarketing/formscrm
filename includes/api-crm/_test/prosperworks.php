<?php

define('WP_DEBUG',true);


$test_url ="https://api.prosperworks.com/developer_api/v1";
$test_username = "rajeswari.onteru@gmail.com";     
$test_token ="002d921d9ac73b332984359b2d0b7efd";   

$settings['gf_crm_type']        = "posperworks";
$settings['gf_crm_username']    = "rajeswari.onteru@gmail.com";
$settings['gf_crm_url']         = 'https://api.prosperworks.com/developer_api/v1';
$settings['gf_crm_apipassword'] = "002d921d9ac73b332984359b2d0b7efd";
$settings['gf_crm_module']      = "Leads";

/////// FACTURA DIRECTA ///////
include_once('../crm-prosperworks.php');
$crmlib = new CRMLIB_PROSPERWORKS();

/* ------------------------ Functions Calling ---------------------*/
echo '<p>Login Prosperworks CRM:</p>';
$login_api =$crmlib->login($settings);
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
                array( 'name' => 'name', 'value' => 'Lead via Integration 1'), 
                array( 'name' => 'title', 'value' => 'Api Lead'),                 
                array( 'name' => 'email', 'value' => 'v@v.com'), 
                array( 'name' => 'phone_mobile', 'value' => '123456'), 
                array( 'name' => 'phone_work', 'value' => '123456'),                                  
            );
$leadid = $crmlib->create_entry($settings, $test_mergevars);
echo '<pre>';
print_r($leadid);
echo '<pre>';