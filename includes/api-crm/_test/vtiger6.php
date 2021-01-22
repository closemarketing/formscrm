<?php
define('WP_DEBUG', true);
include_once 'debugtest.php';

define( 'WP_USE_THEMES', false ); // Don't load theme support functionality
require( '../../../../../../wp-load.php' );

/*
vTiger 6.1
$test_username ="admin";
$test_password = "L6lg5pEWVh8FBzT8";
$test_url ="http://crm.bonotel.org";
 */

/*
vTiger 6.3
$test_username ="admin";
$test_password = "3w02ufmmp5UN9Znp";
$test_url ="https://exclusivecarrent.be/crm/";
 */

/*
vTiger 6.4 *

$test_url = "https://www.aucklandhomestay.co.nz/manage/";
$test_username = "ktokuo";
$test_password = "OsabTiDyc5Lgyod1";

$test_username ="robot_admin";
$test_password = "TWwUZko3971z3Rrg";
$test_url ="http://saliscale.it/gestioneclienti/";

$test_username ="Jeffrey.speck@canea.com";
$test_password = "VdTdqbroH2MlKHr4";
$test_url ="http://caneanorthamerica.od2.vtiger.com";

$test_username ="support@ecomobile.com";
$test_password = "AyYsL2KCTsPTN1Dp";
$test_url ="https://ecomobile.od2.vtiger.com";

vTiger 7
[gf_crm_type] => vTiger 6
[gf_crm_url] => http://vtiger.hostingk.net
[gf_crm_username] => admin
[gf_crm_apipassword] => 0c5KsFCnop51c9Io


$settings['gf_crm_type']        = "vTiger 6";
$settings['gf_crm_username']    = "admin";
$settings['gf_crm_url']         = "http://vtiger.hostingk.net";
$settings['gf_crm_apipassword'] = "0c5KsFCnop51c9Io";
$settings['gf_crm_module']      = "Leads";

*/

$settings['gf_crm_type']        = "vTiger 6";
$settings['gf_crm_username']    = "web";
$settings['gf_crm_url']         = 'https://crm.clinicagalena.com/';
$settings['gf_crm_apipassword'] = "mzmXbG8cmoJxFr4D";
$settings['gf_crm_module']      = "Leads";


include_once '../class-crm-vtiger_6.php';

$crmlib = new CRMLIB_VTIGER_6();

echo '<p>Login VTIGER:</p>';
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
	array('name' => 'firstname', 'value' => 'User &test'),
	array('name' => 'lastname', 'value' => 'User &test'),
	array('name' => 'description', 'value' => 'User test'),
	array('name' => 'lastname', 'value' => '#'),
	array('name' => 'status', 'value' => 'New'),
	array('name' => 'assigned_user_id', 'value' => '19x1'),
	array('name' => 'cf_757', 'value' => 'No'),

);
$leadid = $crmlib->create_entry($settings, $test_mergevars);
echo '<pre>';
print_r($leadid);
echo '<pre>';