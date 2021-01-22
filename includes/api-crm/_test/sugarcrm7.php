<?php
define('WP_DEBUG',true);
include_once 'debugtest.php';

define( 'WP_USE_THEMES', false ); // Don't load theme support functionality
require( '../../../../../../wp-load.php' );

/*
$test_username ="venky";
$test_password = "Sugar_02556";
$test_url ="https://eeyipr5135.trial.sugarcrm.eu/";
*/

$test_username ="jdinh";
$test_password = "chau1505S";
$test_url ="https://konanmedical.sugarondemand.com/";

$settings['gf_crm_type']        = "SugarCRM 7";
$settings['gf_crm_username']    = "jdinh";
$settings['gf_crm_url']         = 'https://konanmedical.sugarondemand.com/';
$settings['gf_crm_apipassword'] = "chau1505S";
$settings['gf_crm_module']      = "Leads";

include '../class-crm-sugarcrm7.php';

$crmlib = new CRMLIB_SUGARCRM7();

echo '<p>Login SUGAR CRM 7:</p>';
$login_api =$crmlib-> login($settings);

echo '<pre>';
print_r($login_api);
echo '</pre>';

echo '<p>List Fields</p>';
$list_fields =$crmlib-> list_fields($settings);
echo '<pre>';
print_r($list_fields);
echo '<pre>';

echo '<p>Create lead from test mergevar</p>';

$test_mergevars = array(
                array( 'name' => 'name', 'value' => 'User test'),
                array( 'name' => 'description', 'value' => 'User test'),
                array( 'name' => 'status', 'value' => 'New')
            );
$leadid = $crmlib->create_entry($settings, $test_mergevars);
echo '<pre>';
print_r($leadid);
echo '<pre>';
