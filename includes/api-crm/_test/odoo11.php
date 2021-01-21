<?php

$settings['gf_crm_type']     = 'Odoo11';
$settings['gf_crm_username'] = 'demo@newemage.com';
$settings['gf_crm_url']      = 'http://crm.newemage.com/web';
$settings['gf_crm_password'] = 'closemkt909v';
$settings['gf_crm_odoodb']   = 'bitnami_odoo';
$settings['gf_crm_module']   = 'Lead';

require '../crm-odoo11.php';

$crmlib = new CRMLIB_ODOO11();

echo '<p>Login Odoo 11:</p>';
$login_api = $crmlib->login( $settings );
print_r( $login_api );


echo '<p>List Modules</p>';
$list_modules = $crmlib->list_modules( $settings );
echo '<pre>';
print_r( $list_modules );
echo '<pre>';

echo '<p>List Fields</p>';
$list_fields = $crmlib->list_fields( $settings);
echo '<pre>';
print_r( $list_fields );
echo '<pre>';

echo '<p>Create lead from test mergevar</p>';

$test_mergevars = array(
	array(
		'name'  => 'name',
		'value' => 'User test',
	),
	array(
		'name'  => 'phone',
		'value' => '6666666666',
	),
	array(
		'name'  => 'partner_address_email',
		'value' => 'david@test.es',
	),
);
$idlead = $this->create_entry( $settings, $test_mergevars );
echo '<pre>';
print_r( $idlead );
echo '</pre>';
