<?php
/**
 * SuiteCRM connect library API v4_1
 *
 * Has functions to login, list fields and create leadº
 *
 * @author   closemarketing
 * @category Functions
 * @package  Gravityforms CRM
 * @version  1.1.0
 */

include_once 'debug.php';
class CRMLIB_VTIGER7 {
    //function to make cURL request
    function call_suitecrm($method, $parameters, $url)
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

        debug_message($result);

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
		$url      = check_url_crm( $settings['gf_crm_url'] );
		$username = $settings['gf_crm_username'];
		$password = $settings['gf_crm_apipassword'];


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

        $login_result = call_suitecrm("login", $login_parameters, $url);

        if( (isset($login_result->id) && ($login_result->id== 1) ) || 
            (isset($login_result->name) && ($login_result->name=='Invalid Login') )  )  {
            echo '<div id="message" class="error below-h2">
                <p><strong>'.__('Unable to authenticate SuiteCRM.','gravityformscrm').' </strong></p></div>';
            return false;
        }

        return $login_result->id;
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

        if(!$login_result)
        return false;

        $url = $url.'service/v4_1/rest.php';

        //retrieve fields --------------------------------
            $get_module_fields_parameters = array(
            'session' => $login_result,
            'module_name' => $module,
            );

        $get_fields = call_suitecrm("get_module_fields", $get_module_fields_parameters, $url);

        if( isset($get_fields->name) && ($get_fields->name=='Access Denied') ){
        echo '<div id="message" class="error below-h2">
                <p><strong>Access Denied. Error '.$get_fields->number.' '.$get_fields->description.' </strong></p></div>';
        return false;
        }

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

        if(!$login_result)
        return false;

        $webservice = $url.'service/v4_1/rest.php';

        $set_entry_parameters = array(
            "session" => $login_result,
            "module_name" => $module,
            "name_value_list" => $merge_vars
        );
        $response = call_suitecrm("set_entry", $set_entry_parameters, $webservice);

        if( isset($response->name) && ($response->name=='Access Denied') ){
        echo '<div id="message" class="error below-h2">
                <p><strong>Access Denied. Error '.$response->number.' '.$response->description.' </strong></p></div>';
        return false;
        }

        debug_message($response);
        if(is_object($response) && $response->id) {
        return $response->id;
        } elseif($response[1]!=null && $response[1]!=""){
        return $response->id;
        } else {
        debug_email_lead('SuiteCRM','Error: '.$response[0],$merge_vars);
        return false;
        }
        return $response->id;
    }
}
