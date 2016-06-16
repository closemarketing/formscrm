<?php
/////// SUGAR CRM ///////

//function to make cURL request
private function call_sugarcrm($method, $parameters, $url)
{
    ob_start();
    $curl_request = curl_init();

    curl_setopt($curl_request, CURLOPT_URL, $url);
    curl_setopt($curl_request, CURLOPT_POST, 1);
    curl_setopt($curl_request, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
    curl_setopt($curl_request, CURLOPT_HEADER, 1);
    curl_setopt($curl_request, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl_request, CURLOPT_FOLLOWLOCATION, 0);

    $jsonEncodedData = json_encode($parameters);

    $post = array(
         "method" => $method,
         "input_type" => "JSON",
         "response_type" => "JSON",
         "rest_data" => $jsonEncodedData
    );

    curl_setopt($curl_request, CURLOPT_POSTFIELDS, $post);
    $result = curl_exec($curl_request);
    curl_close($curl_request);
    $result = explode("\r\n\r\n", $result, 2);

    if($result[0]=="")
        $response = false;
    else
        $response = json_decode($result[1]);

    ob_end_flush();

    return $response;
}

private function sugarcrm_login($username, $password, $url) {

    $url = $url.'service/v4_1/rest.php';

    //login ------------------------------
    $login_parameters = array(
         "user_auth" => array(
              "user_name" => $username,
              "password" => md5($password),
              "version" => "1"
         ),
         "application_name" => "RestTest",
         "name_value_list" => array(),
    );

    $login_result = $this->call_sugarcrm("login", $login_parameters, $url);

    $login_token = $login_result->id;

    return $login_token;
}

private function sugarcrm_listfields($username, $password, $url, $module) {

    //get session id
    $login_result = $this->sugarcrm_login($username, $password, $url);

    $url = $url.'service/v4_1/rest.php';

    //retrieve fields --------------------------------
        $get_module_fields_parameters = array(
         'session' => $login_result,
         'module_name' => $module,
        );

    $get_fields = $this->call_sugarcrm("get_module_fields", $get_module_fields_parameters, $url);

    $get_fields = $get_fields->module_fields;

    $i=0;
    $custom_fields = array();
    foreach ($get_fields as $arrayob) {
        $field = get_object_vars($arrayob);

        if($field['name']=='id') {
        } elseif($field['required']==1) {
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
    } //from foreach


    return $custom_fields;
}

private function sugarcrm_create_lead($username, $password, $url, $module, $merge_vars) {

    // SugarCRM Method
    $login_result = $this->sugarcrm_login($username, $password, $url);

    $webservice = $url.'service/v4_1/rest.php';

    $set_entry_parameters = array(
         "session" => $login_result,
         "module_name" => $module,
         "name_value_list" => $merge_vars
    );

    $set_entry_result = $this->call_sugarcrm("set_entry", $set_entry_parameters, $webservice);

    return $set_entry_result->id;

}

////////////////////////////////
