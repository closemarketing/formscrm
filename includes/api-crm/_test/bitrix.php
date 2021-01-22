<?php
// CRM server conection data
define('CRM_PATH', '/crm/configs/import/lead.php'); // CRM server REST service path
define('WP_DEBUG',true);
include_once 'debugtest.php';

define( 'WP_USE_THEMES', false ); // Don't load theme support functionality
require( '../../../../../../wp-load.php' );

/*
$test_username ="venky4crm@gmail.com";
$test_password = "bitrix@02556";
$test_url ="kol.bitrix24.com";
$test_crmport ="443";*/
/*
$test_username ="david@closemarketing.es";
$test_password = "cHob0culK0";
$test_url ="closemarketing.bitrix24.es"; */
/*
$test_username ="kevin.frea@gmail.com";
$test_password = "0rg1va2911";
$test_url ="energy.bitrix24.com";
*/


$settings['gf_crm_type']        = "bitrix24";
$settings['gf_crm_username']    = "zifit-leads@zifitinfraredfitness.com";
$settings['gf_crm_url']         = 'http://zifit.bitrix24.com';
$settings['gf_crm_apipassword'] = "P@ssword1";
$settings['gf_crm_module']      = "Leads";



include '../class-crm-bitrix24.php';
$crmlib = new CRMLIB_BITRIX24();
///////////////// Test Bitrix 24 CRM ////////////////////////////////
echo '<p>Login Bitrix CRM :</p>';

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

echo '<p>Create lead from test mergevar</p>';

$test_mergevars = array(
				array( 'name' => 'TITLE', 'value' => 'David Test API'),
                array( 'name' => 'NAME', 'value' => 'User test'),
                array( 'name' => 'LAST_NAME', 'value' => 'User test'),
                array( 'name' => 'SOURCE_DESCRIPTION', 'value' => 'User test'),
                array( 'name' => 'STATUS_ID', 'value' => 'NEW'),
                array( 'name' => 'UF_CRM_1466790078', 'value' => 58),
                array( 'name' => 'UF_CRM_1466862617', 'value' => 72),


            );
$leadid = $crmlib->create_entry($settings, $test_mergevars);
echo '<pre>';
print_r($leadid);
echo '<pre>';
