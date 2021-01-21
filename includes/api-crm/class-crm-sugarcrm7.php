<?php
/**
 * SugarCRM 7
 *
 * Has functions to login, list fields and create leadº
 *
 * @author   closemarketing
 * @category Functions
 * @package  Gravityforms CRM
 * @version  1.0.0
 */

include_once 'debug.php';
class CRMLIB_SUGARCRM7 {
        /** 
         * Helper functions
         */
    function call_sugarcrm7($method, $parameters, $url)
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

        debug_message($result);

        $result = explode("\r\n\r\n", $result, 2);

        if($result[0]=="")
            $response = false;
        else
            $response = json_decode($result[1]);

        ob_end_flush();

        return $response;
    }
    /**
	 * Logins to a CRM
	 *
	 * @param  array $settings settings from Gravity Forms options
	 * @return false or id           returns false if cannot login and string if gets token
	 */
	function login( $settings ) {
    
    $url = null;
    if( isset( $settings['gf_crm_url'] ) ) {
      $url = check_url_crm($settings['gf_crm_url']);
    }
    $username = null;
    if( isset( $settings['gf_crm_username'] ) ) {
      $username = $settings['gf_crm_username'];
    }
    $password = null;
    if( isset( $settings['gf_crm_apipassword'] ) ) {
      $password = $settings['gf_crm_apipassword'];
    }
    
    if( $url && $username && $password ) {

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

        $login_result = call_sugarcrm7("login", $login_parameters, $url);

        debug_message($login_result);

        if(isset($login_result->name)) {
          echo '<div id="message" class="error below-h2"><p><strong>'.$login_result->description.': </strong></p></div>';
          return false;
        } else {
          $login_token = $login_result->id;
          return $login_token;
        }

        
      } else {
        return false;
      }
        
    }
    /**
	 * List modules of a CRM
	 *
	 * @param  array $settings settings from Gravity Forms options
	 * @return array           returns an array of mudules
	 */
	function list_modules( $settings ) {
		$url      = check_url_crm( $settings['gf_crm_url'] );
		$username = $settings['gf_crm_username'];
		$password = $settings['gf_crm_apipassword'];
    }    

    /**
	 * List Fields
	 */
	function list_fields( $settings ) {
		$url      = check_url_crm( $settings['gf_crm_url'] );
		$username = $settings['gf_crm_username'];
		$password = $settings['gf_crm_apipassword'];
		if ( isset( $settings['gf_crm_module'] ) ) {
			$module = $settings['gf_crm_module'];
		} else {
			$module = 'lead';
		}


    //get session id
    $login_result = login($username, $password, $url);

    $url = $url.'service/v4_1/rest.php';

    //retrieve fields --------------------------------
        $get_module_fields_parameters = array(
        'session' => $login_result,
        'module_name' => $module,
        );

    $get_fields = call_sugarcrm7("get_module_fields", $get_module_fields_parameters, $url);
    $custom_fields = array();

    foreach($get_fields->module_fields as $field){
        if($field->label== 'ID'||$field->required==0||$field->name=='team_count'||$field->name=='team_name')
            $custom_fields[]=array('label'=> $field->label.' ('.$field->name.')', 'name' => $field->name);
        else
            $custom_fields[]=array('label'=> $field->label.' ('.$field->name.')', 'name' => $field->name, 'required' => ($field->required));
    }
    return $custom_fields;
    }
     	/**
	 * Create Entry
	 */
	function create_entry( $settings, $merge_vars ) {
		$url      = check_url_crm( $settings['gf_crm_url'] );
		$username = $settings['gf_crm_username'];
		$password = $settings['gf_crm_apipassword'];
		if ( isset( $settings['gf_crm_module'] ) ) {
			$module = $settings['gf_crm_module'];
		} else {
			$module = 'lead';
		}

        // SugarCRM Method
        $login_result = login($username, $password, $url);

        $webservice = $url.'service/v4_1/rest.php';

        $set_entry_parameters = array(
            "session" => $login_result,
            "module_name" => $module,
            "name_value_list" => $merge_vars
        );

        $set_entry_result = call_sugarcrm7("set_entry", $set_entry_parameters, $webservice);

        return $set_entry_result->id;

    }
}
