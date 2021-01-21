<?php
$test_username ="";
$test_password = "";
$test_url ="";

/////// SUPEROFFICE CRM ///////

function supoff_login($username, $password, $url) {

    //Return true or false for logged in
}

function supoff_listfields($username, $password, $url, $module) {

    // Return an array of fields
}

function supoff_create_lead($username, $password, $url, $module, $mergevars) {

}

////////////////////////////////

echo '<p>Login SuperOffice:</p>';
$login_api = supoff_login($test_username, $test_password, $test_url);
echo '<pre>';
print_r($login_api);
echo '</pre>';

echo '<p>List Fields</p>';
$list_fields = supoff_listfields($test_username, $test_password, $test_url,'lead');
echo '<pre>';
print_r($list_fields);
echo '</pre>';

echo '<p>Create lead from test mergevar</p>';

$test_mergevars = array(
                array( 'name' => 'name', 'value' => 'User test'),
                array( 'name' => 'name', 'value' => 'User test'),
                array( 'name' => 'name', 'value' => 'User test')
            );

$leadid = supoff_create_lead($test_username, $test_password, "Lead", $test_mergevars);
echo '<pre>';
print_r($leadid);
echo '</pre>';