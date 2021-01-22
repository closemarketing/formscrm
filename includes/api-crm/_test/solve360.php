<?php
define('WP_DEBUG',true);
include_once 'debugtest.php';

define( 'WP_USE_THEMES', false ); // Don't load theme support functionality
require( '../../../../../../wp-load.php' );

$test_url ="";
$test_username = "venky4crm@gmail.com";
$test_token ="r6Qcvfc7W7r9Q5ve+7s5HcT7e1Kfg7G8x25a3aW0";

$settings['gf_crm_type']        = "solvecrm";
$settings['gf_crm_username']    = "venky4crm@gmail.com";
$settings['gf_crm_url']         = '';
$settings['gf_crm_apipassword'] = "r6Qcvfc7W7r9Q5ve+7s5HcT7e1Kfg7G8x25a3aW0";
$settings['gf_crm_module']      = "Leads";

require '../class-crm-solve360.php';

/* ------------------------ Functions Calling ---------------------*/
$crmlib = new CRMLIB_SOLVE360();
echo '<p>Login SOLVE 360 CRM:</p>';
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

echo '<p>Create contact from test mergevar</p>';
$test_mergevars = array(
                array( 'name' => 'jobtitle', 'value' => 'PFE'),
                array( 'name' => 'firstname', 'value' => 'VEN1'),
                array( 'name' => 'lastname', 'value' => 'K')
            );
$leadid = $crmlib->create_entry($settings, $test_mergevars);
echo '<pre>';
print_r($leadid);
echo '<pre>';
