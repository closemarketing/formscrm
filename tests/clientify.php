<?php
/** 
 * API DOCS: https://developer.clientify.com/
 * TEST: http://formscrm.local/wp-content/plugins/formscrm/tests/clientify.php
 */

define('WP_DEBUG',true);
include_once 'debugtest.php';


define( 'WP_USE_THEMES', false ); // Don't load theme support functionality
require '../../../../wp-load.php';

// EIP Direccion empresas.
$credentials = file_get_contents( 'credentials/clientify.json' );
$settings    = json_decode( $credentials, true );

require '../includes/crm-library/class-crmlib-clientify.php';
//////////

$crm_clientify = new CRMLIB_Clientify();

echo '<h1>Clientify CRM:</h1>';
echo '<p>Login clientify CRM:</p>';
$login_api = $crm_clientify->login($settings);

echo '<pre>login:';
print_r($login_api);
echo '</pre>';

echo '<p>List Modules</p>';
$list_modules = $crm_clientify->list_modules($settings);
echo '<pre>';
print_r($list_modules);
echo '<pre>';


echo '<h2>List Fields</h2>';
foreach ( $list_modules as $module ) {
	echo '<h3>List Fields for module: ' . $module['name'] . '</h3>';
	$settings['fc_crm_module'] = $module['name'];
	$list_fields = $crm_clientify->list_fields($settings, $module['name'] );
	echo '<pre>';
	print_r($list_fields);
	echo '<pre>';
}
$settings['fc_crm_module'] = "contacts";

echo '<h2>Create lead from test mergevar</h2>';

$test_mergevars = array(
	array( 'name' => 'first_name', 'value' => 'David Prueba'),
	array( 'name' => 'last_name', 'value' => 'User test'),
	array( 'name' => 'status', 'value' => 'cold-lead'),
	array( 'name' => 'email', 'value' => 'david+' . generateRandomString( 4 ) . '@emailtest.com' ),
	array( 'name' => 'phone', 'value' => '666666666'),
	array( 'name' => 'custom_fields|Programa', 'value' => 'prueba programa' ),
);
echo '<pre> Mergevars:';
print_r($test_mergevars);
echo '<pre>';

$leadid = $crm_clientify->create_entry( $settings, $test_mergevars);
echo '<pre>';
print_r($leadid);
echo '<pre>';

echo '<h2>Create contact and lead from test mergevar</h2>';

$settings['fc_crm_module'] = "contacts-deals";
$test_mergevars[] = array( 'name' => 'deal|name', 'value' => 'Nombre oportunidad' );
$test_mergevars[] = array( 'name' => 'deal|amount', 'value' => rand( 100, 2500 ) );
echo '<pre> Mergevars Contact and Lead:';
print_r($test_mergevars);
echo '<pre>';

$leadid = $crm_clientify->create_entry( $settings, $test_mergevars);
echo '<pre>';
print_r($leadid);
echo '<pre>';