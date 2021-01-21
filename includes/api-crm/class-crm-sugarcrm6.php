<?php
/**
 * SugarCRM 6 and SuiteCRM connect library
 *
 * Has functions to login, list fields and create leadº
 *
 * @author   closemarketing
 * @category Functions
 * @package  Gravityforms CRM
 * @version  1.2.0
 */

require_once 'debug.php';
class CRMLIB_SUGARCRM6 {

    
/* Converts Array to sugarcrm webservice specification */
function convert_custom_fields( $merge_vars ) {
    $i        = 0;
    $count    = count( $merge_vars );
    $jsontext = '{';

    for ( $i = 0; $i < $count; $i++ ) {
        $jsontext .= '"' . $merge_vars[ $i ]['name'] . '":"' . htmlspecialchars( $merge_vars[ $i ]['value'] ) . '"';
        if ( $i < $count - 1 ) {
            $jsontext .= ', ';}
        // '{"lastname":"#", "email":"david@closemarketing.es","industry":"bla"}'
    }
    $jsontext .= '}';

    return $jsontext;
}




    // cURL GET function for sugarcrm
	function call_sugarcrm_get($url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $url);
		$data = curl_exec($ch);
        curl_close($ch);
       
		return $data;
    }
    
    // cURL POST function for sugarcrm
	function call_sugarcrm_post($url, $params) {
		$postData = '';
		//create name value pairs seperated by &
		foreach ($params as $k => $v) {
			$postData .= $k . '=' . $v . '&';
		}
		rtrim($postData, '&');

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_POST, count($postData));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
		var_dump($url);
		$output = curl_exec($ch);

		
		
		curl_close($ch);

		return $output;
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

      $webservice     = $url . 'service/v4_1/rest.php';
      $operation      = '?operation=getchallenge&username=' . $username;
      $result         = $this->call_sugarcrm_get($webservice . $operation);
      $json           = json_decode($result, true);
      $challengeToken = $json['result']['token'];
          
      // Get MD5 checksum of the concatenation of challenge token and user own Access Key
      $accessKey = md5($challengeToken . $password);

      // Define login operation parameters
      $operation2 = array(
        "operation" => "login",
        "username"  => $username,
        "accessKey" => $accessKey,
      );

      // Execute and get result on server response for login operation
      $result = $this->call_sugarcrm_post($webservice, $operation2);
      // Decode JSON response
    
      debug_message($result);

      $json = json_decode($result, true);
      
      if ($json['success'] == false) {
        return false;
      } else {
        $this->apikey = $json['result']['sessionName'];
        return $json['result']['sessionName'];
      }
      
  } else {
    return false;
  }
      
       
	}


    /**
	 * List Modules
	 */
	function list_modules( $settings ) {
		$url      = check_url_crm($settings['gf_crm_url']);
		$username = $settings['gf_crm_username'];
		$password = $settings['gf_crm_apipassword'];

		$login_result = $this->login($settings);
	

		$webservice = $url . 'service/v4_1/rest.php';
		$operation  = '?operation=listtypes&sessionName=' . $login_result;

		$result = $this->call_sugarcrm_get($webservice . $operation);
		$result = json_decode($result);
		$result = get_object_vars($result);;

		if (isset($result['error'])) {
			// Handle sugarcrm error.
			echo '<div class="error">';
			echo '<p><strong>Sugarcrm ERROR ' . $result['error']->code . ': </strong> ' . $result['error']->message . '</p>';
			echo '</div>';
			return;
		}
		$result = get_object_vars($result['result']);
		
		debug_message(__('Result Modules:', 'gravityforms-crm') . $result);

		$i              = 0;
		$custom_modules = array();
		foreach ($result['types'] as $field) {
			$custom_modules[$i] = array(
				'label' => $field,
				'name'  => $field,
			);
			$i++;

		}
		return $custom_modules;
	}



	/**
	 * List Fields
	 */
	function list_fields($settings) {
		$url      = check_url_crm($settings['gf_crm_url']);
		$username = $settings['gf_crm_username'];
		$password = $settings['gf_crm_apipassword'];
		$module = $settings['gf_crm_module'];

		//Get fields from module
		$login_result = $this->login($settings);
		//el login result es false, no se llega a logear?
		

		debug_message(__('Login result:', 'gravityforms-crm') . $login_result);
		debug_message(__('Module active:', 'gravityforms-crm') . $module);

		
		$webservice = $url . 'service/v4_1/rest.php';
		$operation  = '?operation=describe&sessionName=' . $login_result . '&elementType=' . $module;

		$result = $this->call_sugarcrm_get($webservice . $operation);
		$result = json_decode($result);
		$result = get_object_vars($result);

		if (isset($result['error'])) {
			//Handle sugarcrm error
			echo '<div class="error">';
			echo '<p><strong>sugarcrm ERROR ' . $result['error']->code . ': </strong> ' . $result['error']->message . '</p>';
			echo '</div>';
			return;
		}
		$result = get_object_vars($result['result']);

		$i             = 0;
		$custom_fields = array();
		foreach ($result['fields'] as $arrayob) {
			$field = get_object_vars($arrayob);

			if ($field['mandatory'] == 1) {
				$custom_fields[$i] = array(
					'label'    => $field['label'],
					'name'     => $field['name'],
					'required' => true,
				);
			} else {
				$custom_fields[$i] = array(
					'label' => $field['label'],
					'name'  => $field['name'],
				);
			}
			$i++;
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
	}
    
	
}
