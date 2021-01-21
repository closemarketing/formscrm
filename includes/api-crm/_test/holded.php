<?php
/** 
 * API DOCS: https://developers.holded.com/reference
 */
define('WP_DEBUG',true);
include_once 'debugtest.php';


define( 'WP_USE_THEMES', false ); // Don't load theme support functionality
require( '../../../../../../wp-load.php' );

$settings['gf_crm_type'] = "Holded";
$settings['gf_crm_apipassword'] = "981141ec19f4e088567b1f91f7d745ca";
$settings['gf_crm_module'] = "contacts";

include '../class-crm-holded.php';
//////////

$crm_holded = new CRMLIB_HOLDED();

echo '<p>Login HOLDED CRM:</p>';
$login_api = $crm_holded->login($settings);

echo '<pre>';
print_r($login_api);
echo '</pre>';

echo '<p>List Fields</p>';
$list_fields = $crm_holded->list_fields($settings);
echo '<pre>';
print_r($list_fields);
echo '<pre>';

echo '<p>List Modules</p>';
$list_modules = $crm_holded->list_modules($settings);
echo '<pre>';
print_r($list_modules);
echo '<pre>';

echo '<p>Create lead from test mergevar</p>';

$test_mergevars = array(
	array( 'name' => 'name', 'value' => 'User test'),
	array( 'name' => 'tradename', 'value' => 'User test'),
	array( 'name' => 'code', 'value' => 'B1999999'),
	array( 'name' => 'email', 'value' => 'prueba@prueba.com'),
	array( 'name' => 'phone', 'value' => '823322323'),
	array( 'name' => 'mobile', 'value' => '23212323'),
	array( 'name' => 'address', 'value' => 'Calle Turin'),
);
$leadid = $crm_holded->create_entry( $settings, $test_mergevars);
echo '<pre>';
print_r($leadid);
echo '<pre>';