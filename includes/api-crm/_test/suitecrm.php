<?php
define('WP_DEBUG',true);
include_once 'debugtest.php';

//$test_username ="admingib";
//$test_password = "1UPwkVrpi6GP";
//$test_url ="http://panatta.es/crm/";
/*
$test_username ="eileen.siow";
$test_password = "deedee566206";
$test_url ="http://olygen.com/suitecrm/";
*/

//$test_username ="admingib";
//$test_password = "1UPwkVrpi6GP";
//$test_url ="http://panatta.es/crm/";

//$test_username ="eileen.siow";
//$test_password = "deedee566206";
//$test_url ="http://olygen.com/suitecrm/";
/*
$test_username ="Integrator";
$test_password = "xca.1513";
$test_url ="http://crm.xcait.com/";

$test_username ="paceadmin";
$test_password = "pace%admin";
$test_url ="http://crmpro.appfarm.ru/alton/";

$test_username ="admin";
$test_password = "jurabus0314crm";
$test_url ="https://login.campushelfer.de/crm/";
*/

$test_username ="xowvdu";
$test_password = "xowvdu";
$test_url ="http://www.australiansupersearch.com.au/crm/";

$settings['gf_crm_type']        = "suitecrm";
$settings['gf_crm_username']    = "xowvdu";
$settings['gf_crm_url']         = 'http://www.australiansupersearch.com.au/crm/';
$settings['gf_crm_apipassword'] = "xowvdu";
$settings['gf_crm_module']      = "Leads";

//include_once 'crm-suitecrm_3_1.php';
include_once '../crm-suitecrm_4_1.php';
$crmlib = new CRMLIB_VTIGER7();

////////////////////////////////

echo '<p>Login SUITE CRM:</p>';
$login_api = login($settings);

echo '<pre>';
print_r($login_api);
echo '</pre>';

echo '<p>List Modules</p>';
$list_modules = $crmlib->list_modules($settings);
echo '<pre>';
print_r($list_modules);
echo '<pre>';

echo '<p>List Fields</p>';
$list_fields = list_fields($settings);
echo '<pre>';
print_r($list_fields);
echo '<pre>';

echo '<p>Create lead from test mergevar</p>';

$test_mergevars = array(
                array( 'name' => 'name', 'value' => 'User test'),
                array( 'name' => 'description', 'value' => 'User test'),
                array( 'name' => 'status', 'value' => 'New')
            );
$leadid = create_entry($settings, $test_mergevars);
echo '<pre>';
print_r($leadid);
echo '<pre>';
