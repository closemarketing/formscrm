<?php
/**
 * vTiger connect library
 *
 * Has functions to login, list fields and create lead
 *
 * @author   closemarketing
 * @category Functions
 * @package  Gravityforms CRM
 * @version  1.2.0
 */

include_once 'debug.php';

/* Converts Array to vtiger webservice specification */
function convert_custom_fields( $merge_vars ){
    $i=0;
    $count = count( $merge_vars );
    $jsontext = '{';

    for ( $i = 0; $i < $count; $i++ ){
        $jsontext .= '"'.$merge_vars[$i]['name'].'":"'.$merge_vars[$i]['value'].'"';
        if($i<$count-1) {$jsontext .=', '; }
        //'{"lastname":"#", "email":"david@closemarketing.es","industry":"bla"}'
    }
    $jsontext .= '}';

    return $jsontext;
}

// cURL GET function for vTiger
function call_vtiger_get($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL,$url);
    $data=curl_exec($ch);
    curl_close($ch);
    return $data;
}

// cURL POST function for vTiger
function call_vtiger_post($url,$params) {
   $postData = '';
   //create name value pairs seperated by &
   foreach($params as $k => $v)
   {
      $postData .= $k . '='.$v.'&';
   }
   rtrim($postData, '&');

   $ch = curl_init();
   curl_setopt($ch,CURLOPT_URL,$url);
   curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
   curl_setopt($ch,CURLOPT_HEADER, false);
   curl_setopt($ch, CURLOPT_POST, count($postData));
   curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
   $output=curl_exec($ch);
   curl_close($ch);

   return $output;
}

function vtiger_login($username, $apipassword, $url) {
    $webservice = $url . '/webservice.php';
    $operation = '?operation=getchallenge&username='.$username;
    $result = call_vtiger_get($webservice.$operation);
    $json = json_decode($result, true);
    $challengeToken = $json['result']['token'];

    // Get MD5 checksum of the concatenation of challenge token and user own Access Key
    $accessKey = md5($challengeToken.$apipassword);

    // Define login operation parameters
    $operation2 = array(
        "operation" => "login",
        "username" => $username,
        "accessKey" => $accessKey
        );

    // Execute and get result on server response for login operation
    $result = call_vtiger_post($webservice, $operation2);
    // Decode JSON response

    $json = json_decode($result, true);

    if( $json['success'] == false || $json['success'] == ''){
        echo '<div class="notice notice-error"><p>'.$json['error']['code'].' '.$json['error']['message'].'</p></div>';
        return false;
    } else {
        return $json['result']['sessionName'];
    }

}

function vtiger_listfields($username, $password, $url, $module){

    //Get fields from module
    $login_result = vtiger_login($username, $password, $url);

    $webservice = $url . '/webservice.php';
    $operation = '?operation=describe&sessionName='.$login_result.'&elementType='.$module;

    $result = call_vtiger_get($webservice.$operation);
    $result = json_decode($result);
    $result = get_object_vars($result);

    if( isset($result['error']) ) { //Handle vTiger error
        echo '<div class="error">';
        echo '<p><strong>vTiger ERROR '.$result['error']->code.': </strong> '.$result['error']->message.'</p>';
        echo '</div>';
        return;
    }
    $result = get_object_vars($result['result']);

    $i=0;
    $custom_fields = array();
    foreach ($result['fields'] as $arrayob) {
        $field = get_object_vars($arrayob);


        if($field['mandatory']==1) {
            $custom_fields[$i] = array(
                'label' => $field['label'],
                'name' => $field['name'],
                'required' => true,
                );
        } else {
            $custom_fields[$i] = array(
                'label' => $field['label'],
                'name' => $field['name']
                );
        }
        $i++;
    }
    return $custom_fields;

}

function vtiger_create_lead($username, $password, $url, $module, $merge_vars) {
    $login_result = vtiger_login($username, $password, $url);

    //vTiger Method
    $webservice = $url . '/webservice.php';

    $jsondata = convert_custom_fields( $merge_vars );

    $params = array(
        'operation'     => 'create',
        'sessionName'   => $login_result,
        'element'       => $jsondata,
        'elementType'   => $module
        );

    $result = call_vtiger_post($webservice, $params);
    $json = json_decode($result, true);

    if( $json['success'] == false || $json['success'] == ''){
		debug_email_lead('vTiger',$json['error']['code'].' '.$json['error']['message'],$merge_vars);
        return false;
    } else {
        return $json['result'] ['lead_no'];
    }

}
