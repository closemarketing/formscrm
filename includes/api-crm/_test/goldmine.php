<?php

$test_username ='';
$test_password = '';
$test_url ="";

/* Include */
include 'crm-goldmine.php';

echo '<p>Login goldmine:</p>';
$login_result = goldmine_login($test_username, $test_password, $test_url,$test_callbackurl);
echo '<pre>';
print_r($login_result);
echo '</pre>';

echo '<p>List Fields goldmine:</p>';
$fields = goldmine_listfields($authToken,$test_url, "leads");
echo '<pre>';
print_r($fields);
echo '</pre>';

echo '<p>Create lead from test mergevar</p>';
$test_mergevars = array(
            array( 'name' => 'firstname', 'value' => 'VENK'),
            array( 'name' => 'lastname', 'value' => 'K')
        );
$leadid = goldmine_create_lead($authToken,$test_url, "leads", $test_mergevars);
echo '<pre>';
print_r($leadid);
echo '<pre>';

?>
