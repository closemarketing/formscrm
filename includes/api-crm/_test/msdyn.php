<?php
define('WP_DEBUG',true);
include_once 'debugtest.php';

// CRM Online
//$test_url ='https://psl-uk.crm4.dynamics.com/';
//$test_username ="warrenabrahams@psl-uk.co.uk";
//$test_password = "Yuca1985";

/*
$test_url ='https://ceu365.crm4.dynamics.com/';
$test_username ="admin_crm_uao@ceu365.onmicrosoft.com";
$test_password = "12345.aa";


$test_url ='https://bsl.crm.dynamics.com/';
$test_username ="integrationuser@bslusa.com";
$test_password = "pass@word1";

$test_url ='https://nuflow.crm.dynamics.com/';
$test_username ="sf.corporate@nuflowtech.com";
$test_password = "NFA1235!";


$test_url ='https://ntt.crm.dynamics.com';
$test_username ="ntttest@temp.nttinc.com";
$test_password = "Jan292018";
*/

$test_url = 'http://led123.crm3.dynamics.com';
$test_username = 'k@led123.ca';
$test_password = 'Led8899!';

settings['gf_crm_type']        = "msdyn";
$settings['gf_crm_username']    = "k@led123.ca";
$settings['gf_crm_url']         = 'http://led123.crm3.dynamics.com';
$settings['gf_crm_apipassword'] = "Led8899!";
$settings['gf_crm_module']      = "Leads";


require '../crm-msdyn.php';
$crmlib = new CRMLIB_MSDYN();
////////////////////////////////

echo '<p>Login MSDynamics:</p>';
$login_api = $crmlib->login($settings);
print_r($login_api);

echo '<p>List Modules</p>';
$list_modules = $crmlib->list_modules($settings);
echo '<pre>';
print_r($list_modules);
echo '<pre>';

echo '<p>List Fields:</p>';
$list_fields = $crmlib->list_fields($settings);
echo '<pre>';
print_r($list_fields);
echo '</pre>';
echo '<p>Create lead from test mergevar</p>';

$test_mergevars = array(
                array( 'name' => 'subject', 'value' => 'test lead'),
                array( 'name' => 'firstname', 'value' => 'User test'),
                array( 'name' => 'lastname', 'value' => 'TEST')
            );

$leadid = $crmlib->create_entry($settings,$test_mergevars);
echo '<pre>';
print_r($leadid);
echo '<pre>';
?>
