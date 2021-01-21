<?php
define('WP_DEBUG', true);
include_once 'debugtest.php';

$settings['gf_crm_type']        = "OfiWeb";
$settings['gf_crm_url']         = 'https://ofiweb.com.es/cls/imsol/savedatosform.asp';
$settings['gf_crm_module']      = "Leads";

include_once '../class-crm-ofiweb.php';
$crmlib = new CRMLIB_OFIWEB();

echo '<p>Login Ofiweb:</p>';
$login_api = $crmlib->login( $settings );


echo '<pre>';
print_r( $login_api );
echo '</pre>';

echo '<p>List Modules</p>';
$list_modules = $crmlib->list_modules( $settings );
echo '<pre>';
print_r( $list_modules );
echo '<pre>';

echo '<p>List Fields</p>';
$list_fields = $crmlib->list_fields( $settings );
echo '<pre>';
print_r( $list_fields );
echo '<pre>';

echo '<p>Create lead from test mergevar</p>';

$test_mergevars = array(
	array(
		'name'     => 'nombre',
		'value'    => 'David prueba',
		'required' => true,
	),
	array(
		'value'    => 'perez',
		'name'     => 'apellidos',
		'required' => false,
	),
	array(
		'value'    => 'Española',
		'name'     => 'nacion',
		'required' => false,
	),
	array(
		'value'    => '7654632N',
		'name'     => 'nif',
		'required' => false,
	),
	array(
		'value'    => '27/12/1978',
		'name'     => 'nacimiento',
		'required' => false,
	),
	array(
		'value'    => 'H',
		'name'     => 'sexo',
		'required' => false,
	),
	array(
		'value'    => 'Jose Luis Perez Pujadas',
		'name'     => 'calle',
		'required' => false,
	),
	array(
		'value'    => 'Granada',
		'name'     => 'local',
		'required' => false,
	),
	array(
		'value'    => 'Granada',
		'name'     => 'provincia',
		'required' => false,
	),
	array(
		'value'    => '18002',
		'name'     => 'cp',
		'required' => false,
	),
	array(
		'value'    => 'España',
		'name'     => 'pais',
		'required' => false,
	),
	array(
		'value'    => 'Granada',
		'name'     => 'zona',
		'required' => false,
	),
	array(
		'value'    => '858958383',
		'name'     => 'tfno',
		'required' => false,
	),
	array(
		'value'    => '669904426',
		'name'     => 'movil',
		'required' => false,
	),
	array(
		'value'    => 'david@closemarketing.es',
		'name'     => 'email',
		'required' => false,
	),
	array(
		'value'    => 'clas1',
		'name'     => 'clasif1',
		'required' => false,
	),
	array(
		'value'    => 'clas2',
		'name'     => 'clasif2',
		'required' => false,
	),
	array(
		'value'    => 'clas3',
		'name'     => 'clasif3',
		'required' => false,
	),
);
$leadid = $crmlib->create_entry($settings, $test_mergevars);
echo '<pre>';
print_r($leadid);
echo '<pre>';
