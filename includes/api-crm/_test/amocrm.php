<?php
$settings['gf_crm_type']     = "AMOCRM";
$settings['gf_crm_username'] = "venky4crm@gmail.com";
$settings['gf_crm_url']      = 'https://venky4crm.amocrm.com';
$settings['gf_crm_apipassword'] = "94ef044669475dd181ee1c5d37ae12a1";
$settings['gf_crm_module']   = "lead";

include_once '../crm-amocrm.php';
$crmlib = new CRMLIB_AMOCRM();
/* ------------------------ Functions Calling ---------------------*/

echo '<p>Login AMO CRM:</p>';
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
//
echo '<p>Create lead from test mergevar</p>';    
$test_mergevars = array(                   
			array( 'name' => 'name', 'value' => 'Lead via Integration')                    
		);
		
$leadid = $crmlib->create_entry($settings);
echo '<pre>';
print_r($leadid);
echo '<pre>';
?>