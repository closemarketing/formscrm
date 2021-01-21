<?php

$test_username = "hdharna@gmail.com";
$test_password = "admin";
$test_url      = "http://213.136.76.36:8065";
$test_db       = "BTC_CRM";

include '../crm-odoo10.php';

////////////////////////////////

echo '<p>Login Odoo:</p>';
$login_api = odoo10_login($test_username, $test_password, $test_db, $test_url);
print_r($login_api);

echo '<p>List Fields</p>';
$custom_fields = odoo10_listfields($test_username, $test_password, $test_db, $test_url, 'lead');
echo '<pre>';
print_r($custom_fields);
echo '</pre>';

echo '<p>Create lead from test mergevar</p>';

$test_mergevars = array(
	array('name' => 'name', 'value' => 'User test'),
	array('name' => 'phone', 'value' => '6666666666'),
	array('name' => 'partner_address_email', 'value' => 'david@email.es'),
);
$idlead = odoo10_create_lead($test_username, $test_password, $test_db, $test_url, 'crm.lead', $test_mergevars);
echo '<pre>';
print_r($idlead);
echo '</pre>';
