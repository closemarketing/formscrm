<?php
/** 
 * API DOCS: https://dinahosting.com/api
 * TEST: http://formscrm.local/wp-content/plugins/formscrm/tests/dinahosting.php
 */

define('WP_DEBUG',true);
include_once 'debugtest.php';


define( 'WP_USE_THEMES', false ); // Don't load theme support functionality
require '../../../../wp-load.php';

// EIP Direccion empresas.
$credentials = file_get_contents( 'credentials/dinahosting.json' );
$settings    = json_decode( $credentials, true );

require '../includes/crm-library/class-crmlib-dinahosting.php';
//////////

$crm_dinahosting = new CRMLIB_Dinahosting();

echo '<h1>Dinahosting CRM:</h1>';
echo '<p>Login dinahosting CRM:</p>';
$login_api = $crm_dinahosting->login($settings);

echo '<pre>login:';
print_r($login_api);
echo '</pre>';

echo '<p>List Modules</p>';
$list_modules = $crm_dinahosting->list_modules($settings);
echo '<pre>';
print_r($list_modules);
echo '<pre>';


echo '<h2>List Fields</h2>';
foreach ( $list_modules as $module ) {
	echo '<h3>List Fields for module: ' . $module['name'] . '</h3>';
	$settings['fc_crm_module'] = $module['name'];
	$list_fields = $crm_dinahosting->list_fields($settings, $module['name'] );
	echo '<pre>';
	print_r($list_fields);
	echo '<pre>';
}
$settings['fc_crm_module'] = "612610";

echo '<h2>Create lead from test mergevar</h2>';

$test_mergevars = array(
	array( 'name' => 'personalData|firstname', 'value' => 'DavidTEST' ),
	array( 'name' => 'personalData|lastname', 'value' => 'TEST' ),
	array( 'name' => 'personalData|NIF', 'value' => '12345678Z' ),
	array( 'name' => 'companyData|company', 'value' => 'Empresa prueba' ),
	array( 'name' => 'companyData|legal_form', 'value' => '434' ),
	array( 'name' => 'companyData|CIF', 'value' => 'B12345678' ),
	array( 'name' => 'companyData|company_phone', 'value' => '+34.669904426' ),
	array( 'name' => 'companyData|company_fax', 'value' => '+34.669904426' ),
	array( 'name' => 'contactData|country_code', 'value' => 'ES' ),
	array( 'name' => 'contactData|state', 'value' => 'Madrid' ),
	array( 'name' => 'contactData|city', 'value' => 'Madrid' ),
	array( 'name' => 'contactData|postal_code', 'value' => '28001' ),
	array( 'name' => 'contactData|address', 'value' => 'Calle de la Princesa, 5' ),
	array( 'name' => 'contactData|phone', 'value' => '669904426' ),
	array( 'name' => 'contactData|fax', 'value' => '669904426' ),
	array( 'name' => 'contactData|email_address', 'value' => 'david+' . generateRandomString( 4 ) . '@close.marketing' ),
);
echo '<pre> Mergevars:';
print_r($test_mergevars);
echo '<pre>';

$leadid = $crm_dinahosting->create_entry( $settings, $test_mergevars);
echo '<pre>';
print_r($leadid);
echo '<pre>';