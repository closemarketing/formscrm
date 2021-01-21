<?php
/*
$test_username ="admin";
$test_password = "david12345";
$test_url ="http://erp.closemarketing.es:8069/";
$test_db = "clmk_db";

$test_username ="frg004@hotmail.com";
$test_password = "testtest";
$test_url ="https://spectacles-pro.odoo.com/";
$test_db = "spectacles-pro";


$test_username ="zaidi.nh@gmail.com";
$test_password = "BtC-1_2016";
$test_url ="http://52.38.202.52:8069/";
$test_db = "BTC-1";
*/
/*
$test_username ="ggharib@ordendigital.com";
$test_password = "Password#1";
$test_url ="http://108.61.91.54:8069/";
$test_db = "convexa2";
*/

$settings['gf_crm_type']        = "odoo9";
$settings['gf_crm_username']    = "ggharib@ordendigital.com";
$settings['gf_crm_url']         = 'http://108.61.91.54:8069/';
$settings['gf_crm_apipassword'] = "Password#1";
$settings['gf_crm_module']      = "Leads";
$settings['gf_crm_odoodb']      ="convexa2";


require '../crm-odoo9.php';

////////////////////////////////
$crmlib = new CRMLIB_ODOO9();
echo '<p>Login Odoo:</p>';
$login_api = $crmlib->login($settings);
print_r($login_api);

echo '<p>List Modules</p>';
$list_modules = $crmlib->list_modules($settings);
echo '<pre>';
print_r($list_modules);
echo '<pre>';

echo '<p>List Fields</p>';
$list_fields =$crmlib->list_fields($settings);
echo '<pre>';
print_r($list_fields);
echo '</pre>';

echo '<p>Create lead from test mergevar</p>';

$test_mergevars = array(
                array( 'name' => 'name', 'value' => 'User test'),
                array( 'name' => 'phone', 'value' => '6666666666'),
                array( 'name' => 'partner_address_email', 'value' => 'david@test.es')
            );
$idlead = $crmlib->create_entry($settings, $test_mergevars);
echo '<pre>';
print_r($idlead);
echo '</pre>';
