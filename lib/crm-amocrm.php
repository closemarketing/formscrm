<?php

///////////////// amocrm ////////////////////////////////////////

function amocrm_login($username, $password, $url){
    $url = $url.'private/api/auth.php?type=json';
    $user=array('USER_LOGIN'=>$username, 'USER_HASH'=>$password);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=UTF-8'));
    curl_setopt($ch,CURLOPT_CUSTOMREQUEST,'POST');
    curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($user));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);

    $userinfo = json_decode($response);

    $userinforesponse= $userinfo->response;

    curl_close($ch);

    if(isset($userinforesponse->error))
        return $userinforesponse->error;

    if(isset($userinforesponse->auth))
        return $userinforesponse->auth;

}
function amocrm_listfields($username, $password, $url, $module) {
    //$url = $url.'/private/api/v2/json/'.$module.'/list?limit_rows=1&USER_LOGIN='.$username.'&USER_HASH='.$password;
    //$ch = curl_init($url);
    //curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    //curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=UTF-8'));
    //curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    //$response = curl_exec($ch);
    //$moduleinfo = json_decode($response);
    //$moduleinforesponse= $moduleinfo->response;
    //curl_close($ch);
    //echo '<pre>';
    //print_r( $moduleinforesponse);
    //echo '</pre>';

    if($module=="leads"){
        $fields=array(
            array('label' => "Lead name",'name' =>  "name",  'required' => TRUE),
            array('label' => "Date of creation",'name' =>  "date_create",  'required' => FALSE),
            array('label' => "Date of the last modification",'name' =>  "last_modified",  'required' => FALSE),
            array('label' => "Lead status",'name' =>  "status_id",  'required' => FALSE),
            array('label' => "Lead budget",'name' =>  "price",  'required' => FALSE),
            array('label' => "Responsible user",'name' =>  "responsible_user_id",  'required' => FALSE),
            array('label' => "Tag names separated by commas",'name' =>  "tags",  'required' => FALSE),
            array('label' => "Custom fields",'name' =>  "custom_fields",  'required' => FALSE)
        );
    }
    else if ($module=="contacts"){
        $fields=array(
            array('label' => "Contact name",'name' =>  "name",  'required' => TRUE),
            array('label' => "Date of creation",'name' =>  "date_create",  'required' => FALSE),
            array('label' => "Date of the last modification",'name' =>  "last_modified",  'required' => FALSE),
            array('label' => "Lead ID",'name' =>  "linked_leads_id",  'required' => FALSE),
            array('label' => "Company name",'name' =>  "company_name",  'required' => FALSE),
            array('label' => "Responsible user",'name' =>  "responsible_user_id",  'required' => FALSE),
            array('label' => "Tag names separated by commas",'name' =>  "tags",  'required' => FALSE),
            array('label' => "Unique contact identifier",'name' =>  "id",  'required' => FALSE),
            array('label' => "Unique lead identifier",'name' =>  "id",  'required' => FALSE),
        );
    }
     else if ($module=="company"){
        $fields=array(
            array('label' => "Company name",'name' =>  "name",  'required' => TRUE),
            array('label' => "Date of creation",'name' =>  "date_create",  'required' => FALSE),
            array('label' => "Date of the last modification",'name' =>  "last_modified",  'required' => FALSE),
            array('label' => "Lead ID",'name' =>  "linked_leads_id",  'required' => FALSE),
            array('label' => "Responsible user",'name' =>  "responsible_user_id",  'required' => FALSE),
            array('label' => "Tag names separated by commas",'name' =>  "tags",  'required' => FALSE),
            array('label' => "Unique contact identifier",'name' =>  "id",  'required' => FALSE),
            array('label' => "Unique lead identifier",'name' =>  "id",  'required' => FALSE),
        );
    }

    return $fields;
}
function amocrm_createlead($username, $password, $url, $module, $merge_vars){
    $url = $url.'private/api/v2/json/'.$module.'/set?USER_LOGIN='.$username.'&USER_HASH='.$password;
    $vars = array();
    foreach($merge_vars as $var){
        $vars[$var['name']] =  $var['value'];
    }

    $leads['request']['leads']['add']=array( $vars);
    $data_string = json_encode($leads);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' .strlen($data_string)
        ));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    //execute post
    $result = curl_exec($ch);
    //close connection
    curl_close($ch);

    $recordsinfo = json_decode($result);
    if(isset($recordsinfo->response)){
        $recordsinforesponse= $recordsinfo->response;
        if(isset($recordsinforesponse->$module->add) &&count($recordsinforesponse->$module->add)>0)
        return  $recordsinforesponse->$module->add[0]->id;
    }

    return;
}


///////////////// amocrm ////////////////////////////////////////
