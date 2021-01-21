<?php
/*
$test_username ="admingib";
$test_password = "1UPwkVrpi6GP";
$test_url ="http://panatta.es/crm/";

$test_username ="eileen.siow";
$test_password = "deedee566206";
$test_url ="http://olygen.com/suitecrm/";

$test_username ="admin";
$test_password = "&IS@C%XDGF";
$test_url ="http://crm.upfrontbydesign.com/2016/";
*/
define('WP_DEBUG',true);
include_once 'debugtest.php';

/*
$test_username ="admin";
$test_password = "jurabus0314crm";
$test_url ="https://login.campushelfer.de/crm/";
*/

$settings = array();

$settings['gf_crm_type']           = "Sugarcrm6";
$settings['gf_crm_username']       = "admin";
$settings['gf_crm_url']            = 'http://crm.upfrontbydesign.com/2016/';
$settings['gf_crm_apipassword']    = "&IS@C%XDGF";
$settings['gf_crm_module']         = "Leads";


include '../crm-sugarcrm6.php';
$crmlib = new CRMLIB_SUGARCRM6();
//////////

echo '<p>Login SUGAR CRM :</p>';

$login_api = $crmlib->login($settings);

echo '<pre>';
print_r($login_api);
echo '</pre>';

echo '<p>List Modules </p>';
$list_modules =$crmlib-> list_modules($settings);
var_dump($list_modules);
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
$leadid =$crmlib-> create_entry($settings, $test_mergevars);
echo '<pre>';
print_r($leadid);
echo '<pre>';

