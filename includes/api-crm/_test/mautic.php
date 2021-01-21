<?php
session_name("oauth_api");
session_start();
$test_username ='2_20htr6hcg9pcgkwckws8s844sck4ow4wgcggwck848csgw4wwk';       // Client/Consumer key from Mautic
$test_password = '2g15l2zxrsrocgg4k8kkgss8gkccw8wogw48wck04k8k48w4cw';       // Client/Consumer secret key from Mautic
$test_url ="https://venky.mautic.com";
$test_callbackurl ="http://localhost:49686/mautic/api.php";
//Venky
$test_username ='1_5togv2ikl98gwk8c4wwoww4wwwgw0sgow0wcsko0so4cwsgko4';       // Client/Consumer key from Mautic
$test_password = '2ivt09kvj62osskkskcos8s0og0sg4s44ws0cwcg0gkwgos8k0';       // Client/Consumer secret key from Mautic
$test_url ="https://www.closemarketing.es/mk/";
$test_callbackurl ="https://www.closemarketing.es/mk/api.php";

/* Include */
include_once('crm-mautic.php');

echo '<p>Login Mautic:</p>';
$authToken = mautic_login($test_username, $test_password, $test_url,$test_callbackurl);
echo '<pre>';
print_r($authToken->getAccessTokenData()['access_token']);
echo '</pre>';

echo '<p>List Fields Mautic:</p>';
$fields = mautic_listfields($authToken,$test_url, "leads");
echo '<pre>';
print_r($fields);
echo '</pre>';

echo '<p>Create lead from test mergevar</p>';
$test_mergevars = array(
            array( 'name' => 'firstname', 'value' => 'VENK'),
            array( 'name' => 'lastname', 'value' => 'K')
        );
$leadid = mautic_create_lead($authToken,$test_url, "leads", $test_mergevars);
echo '<pre>';
print_r($leadid);
echo '<pre>';

?>
