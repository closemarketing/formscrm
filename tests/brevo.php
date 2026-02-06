<?php
/** 
 * API DOCS: https://developers.brevo.com/reference/
 * TEST: http://formscrm.local/wp-content/plugins/formscrm/tests/Brevo.php
 */

include_once 'debugtest.php';


define( 'WP_USE_THEMES', false ); // Don't load theme support functionality.
require '../../../../wp-load.php';

// Testing Credentials.
$credentials = file_get_contents( 'Data/credentials/brevo.json' );
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

echo '<h2>Test 1: Create lead with attributes| prefix (backwards compatibility)</h2>';

$test_mergevars_old = array(
	array( 'name' => 'attributes|NOMBRE', 'value' => 'David'),
	array( 'name' => 'attributes|APELLIDOS', 'value' => 'User test'),
	array( 'name' => 'attributes|EXT_ID', 'value' => '3123131223'),
	array( 'name' => 'email', 'value' => 'david+' . generateRandomString( 4 ) . '@emailtest.com' ),
	array( 'name' => 'attributes|SMS', 'value' => '+34666666666'),
);
echo '<pre> Mergevars (with attributes| prefix):';
print_r($test_mergevars_old);
echo '</pre>';

$leadid_old = $crm->create_entry( $settings, $test_mergevars_old);
echo '<pre>Result:';
print_r($leadid_old);
echo '</pre>';

echo '<h2>Test 2: Create lead without attributes| prefix (auto-detection)</h2>';

$test_mergevars_new = array(
	array( 'name' => 'NOMBRE', 'value' => 'Max'),
	array( 'name' => 'APELLIDOS', 'value' => 'Mustermann'),
	array( 'name' => 'email', 'value' => 'max+' . generateRandomString( 4 ) . '@emailtest.com' ),
	array( 'name' => 'SMS', 'value' => '+34666666666'),
	array( 'name' => 'CUSTOM_FIELD', 'value' => 'Custom Value'),
);
echo '<pre> Mergevars (without attributes| prefix - should auto-detect):';
print_r($test_mergevars_new);
echo '</pre>';

$leadid_new = $crm->create_entry( $settings, $test_mergevars_new);
echo '<pre>Result:';
print_r($leadid_new);
echo '</pre>';

echo '<h2>Test 3: Mixed test - standard fields + custom attributes</h2>';

$test_mergevars_mixed = array(
	array( 'name' => 'email', 'value' => 'mixed+' . generateRandomString( 4 ) . '@emailtest.com' ),
	array( 'name' => 'ext_id', 'value' => 'EXT123456'),
	array( 'name' => 'FIRSTNAME', 'value' => 'John'),
	array( 'name' => 'LASTNAME', 'value' => 'Doe'),
	array( 'name' => 'attributes|COMPANY', 'value' => 'ACME Corp'),
);
echo '<pre> Mergevars (mixed - standard + custom):';
print_r($test_mergevars_mixed);
echo '</pre>';

$leadid_mixed = $crm->create_entry( $settings, $test_mergevars_mixed);
echo '<pre>Result:';
print_r($leadid_mixed);
echo '</pre>';
