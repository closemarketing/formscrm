<?php
/** 
 * API DOCS: https://developers.brevo.com/reference/
 * TEST: http://formscrm.local/wp-content/plugins/formscrm/tests/Brevo.php
 */

include_once 'debugtest.php';


define( 'WP_USE_THEMES', false ); // Don't load theme support functionality.
require '../../../../wp-load.php';

// Testing Credentials.
$credentials = file_get_contents( 'credentials/brevo.json' );
$settings    = json_decode( $credentials, true );

require '../includes/crm-library/class-crmlib-brevo.php';


$crm = new CRMLIB_Brevo();

echo '<h1>Brevo CRM:</h1>';
echo '<p>Login Brevo CRM:</p>';
$login_api = $crm->login( $settings );

echo '<pre>login:';
print_r($login_api);
echo '</pre>';

echo '<p>List Modules</p>';
$list_modules = $crm->list_modules($settings);
echo '<pre>';
print_r($list_modules);
echo '<pre>';


echo '<h2>List Groups</h2>';
foreach ( $list_modules as $module ) {
	echo '<h3>List Fields for module: ' . $module['name'] . '</h3>';
	$settings['fc_crm_module'] = $module['name'];
	$list_fields = $crm->list_fields($settings, $module['name'] );
	echo '<pre>';
	print_r($list_fields);
	echo '<pre>';
}
$settings['fc_crm_module'] = 3;

echo '<h2>Create lead from test mergevar</h2>';

$test_mergevars = array(
	array( 'name' => 'attributes|NOMBRE', 'value' => 'David'),
	array( 'name' => 'attributes|APELLIDOS', 'value' => 'User test'),
	array( 'name' => 'attributes|EXT_ID', 'value' => '3123131223'),
	array( 'name' => 'email', 'value' => 'david+' . generateRandomString( 4 ) . '@emailtest.com' ),
	array( 'name' => 'attributes|SMS', 'value' => '+34666666666'),
);
echo '<pre> Mergevars:';
print_r($test_mergevars);
echo '<pre>';

$leadid = $crm->create_entry( $settings, $test_mergevars);
echo '<pre>';
print_r($leadid);
echo '<pre>';
