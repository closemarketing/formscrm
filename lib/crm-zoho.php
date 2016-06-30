<?php
////////////////////////
/////// ZOHO CRM ///////
////////////////////////


//cURL Function for Zoho CRM

function call_zoho_crm($token, $module, $method) {
    $request_url = 'https://crm.zoho.com/crm/private/json/'.$module.'/'.$method;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $request_parameters = array('authtoken' => $token,'scope' => 'crmapi');
    $request_url .= '?' . http_build_query($request_parameters);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
    curl_setopt($ch, CURLOPT_URL, $request_url);
    curl_setopt($ch, CURLOPT_HEADER, TRUE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $response_info = curl_getinfo($ch);
    curl_close($ch);
    $response_body = substr($response, $response_info['header_size']);
    return $response_body;
}

function zoho_login($username, $password, $apipassword) {

        if ($apipassword) {
            $authkey = $apipassword;
        } else {
            $authkey = file_get_contents('https://accounts.zoho.com/apiauthtoken/nb/create?SCOPE=ZohoCRM/crmapi&EMAIL_ID='.$username.'&PASSWORD='.$password);
            $authkey_exist = strpos($authkey, 'AUTHTOKEN=');

            if( $authkey_exist=== false ) {
                $cause = substr($authkey, strpos($authkey, 'CAUSE=')+6, strpos($authkey, 'RESULT=')-strpos($authkey, 'CAUSE=')-7);
                echo '<div id="message" class="error below-h2">
                <p><strong>'.__('Zoho Error','gravityformscrm').': '.$cause.'</strong></p></div>';
                $authkey = false;
            } else {
            $authkey = substr($authkey, strpos($authkey, 'AUTHTOKEN=')+10, 32);
            }
        }
  return $authkey;
}

function zoho_listfields($username, $password, $module) {
  $result = call_zoho_crm($password, $module, 'getFields');
  $result = json_decode($result);

        if(isset($result->response->error)) {
        echo '<div id="message" class="error below-h2">
                <p><strong>Zoho CRM: Code '.$result->response->error->code.' - '.$result->response->error->message.' </strong></p></div>';
        return false;
            }
      $sections =$result->$module->section;
      foreach($sections as $section){
        $section_fields = $section->FL;

        foreach($section_fields as $section_field){
            if(isset($section_field->dv)){
                 $var_name = str_replace(' ', '_', $section_field->label);
                    if($section_field->req=='true')
                        $convert_fields[] = array('label' => $section_field->dv, 'name' => $var_name, 'required' => $section_field->req);
                    else
                        $convert_fields[] = array('label' => $section_field->dv, 'name' => $var_name);

                } //if isset

            }
        } //foreach
      return $convert_fields;
}

function zoho_createlead($username, $password, $module, $merge_vars) {
  $xmldata = '<'.$module.'><row no="1">';
  $i=0;
  $count = count( $merge_vars );
  for ( $i = 0; $i < $count; $i++ ){
                        $var_name = str_replace('_', ' ', $merge_vars[$i]['name']);
          $xmldata .= '<FL val="'.$var_name.'">';
          $xmldata .= $merge_vars[$i]['value'].'</FL>';
      }
    $xmldata .= '</row></'.$module.'>';

    $url = 'https://crm.zoho.com/crm/private/xml/'.$module.'/insertRecords';
            $token =$password;
    $param= 'authtoken='.$token.'&scope=crmapi&xmlData='.$xmldata;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $query = array('newFormat'=>1,'authtoken'=>$token,'scope'=>'crmapi','xmlData'=>$xmldata);

    $query = http_build_query($query);

    curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
    $result = curl_exec($ch);

    return $result;
}
///////////////////////
