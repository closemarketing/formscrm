<?php
/**
 * vTiger connect library
 *
 * Has functions to login, list fields and create lead
 *
 * @author   closemarketing
 * @category Functions
 * @package  Gravityforms CRM
 * @version  1.1.0
 */

include_once 'debug.php';

class CRMLIB_VTIGER_6 {

	/* Converts Array to vtiger webservice specification */
	function convert_custom_fields($merge_vars) {
		$i        = 0;
		$count    = count($merge_vars);
		$jsontext = '{';

		for ($i = 0; $i < $count; $i++) {
			$jsontext .= '"' . $merge_vars[$i]['name'] . '":"' . htmlspecialchars($merge_vars[$i]['value']) . '"';
			if ($i < $count - 1) {$jsontext .= ', ';}
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
		curl_setopt($ch, CURLOPT_URL, $url);
		$data = curl_exec($ch);
		curl_close($ch);
		
		
		return $data;
	}

	// cURL POST function for vTiger
	function call_vtiger_post($url, $params) {
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
		
		$output = curl_exec($ch);
		
		curl_close($ch);
		
		return $output;
		
	}

	/**
	 * Logins to a CRM
	 * @param  array $settings settings from Gravity Forms options
	 * @return false or id           returns false if cannot login and string if gets token
	 */
	function login($settings) {
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
      $webservice     = $url . '/webservice.php';
      $operation      = '?operation=getchallenge&username=' . $username;
      $result         = $this->call_vtiger_get($webservice . $operation);
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
      $result = $this->call_vtiger_post($webservice, $operation2);
      // Decode JSON response
      debug_message($result);

      $json = json_decode($result, true);

    }

    if( isset($json) ) {
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
	function list_modules($settings) {
		$url      = check_url_crm($settings['gf_crm_url']);
		$username = $settings['gf_crm_username'];
		$password = $settings['gf_crm_apipassword'];

		$login_result = $this->login($settings);

		$webservice = $url . 'webservice.php';
		$operation  = '?operation=listtypes&sessionName=' . $login_result;

		$result = $this->call_vtiger_get($webservice . $operation);
		$result = json_decode($result);
		$result = get_object_vars($result);

		if (isset($result['error'])) {
			//Handle vTiger error
			echo '<div class="error">';
			echo '<p><strong>vTiger ERROR ' . $result['error']->code . ': </strong> ' . $result['error']->message . '</p>';
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

		debug_message(__('Login result:', 'gravityforms-crm') . $login_result);
		debug_message(__('Module active:', 'gravityforms-crm') . $module);

		$webservice = $url . 'webservice.php';
		$operation  = '?operation=describe&sessionName=' . $login_result . '&elementType=' . $module;

		$result = $this->call_vtiger_get($webservice . $operation);
		$result = json_decode($result);
		$result = get_object_vars($result);

		if (isset($result['error'])) {
			//Handle vTiger error
			echo '<div class="error">';
			echo '<p><strong>vTiger ERROR ' . $result['error']->code . ': </strong> ' . $result['error']->message . '</p>';
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
	function create_entry($settings, $merge_vars) {
		$url      = check_url_crm($settings['gf_crm_url']);
		$username = $settings['gf_crm_username'];
		$password = $settings['gf_crm_apipassword'];
		if (isset($settings['gf_crm_module'])) {
			$module = $settings['gf_crm_module'];
		} else {
			$module = 'lead';
		}

		$login_result = $this->login($settings);

		//vTiger Method
		$webservice = $url . '/webservice.php';

		$jsondata = $this->convert_custom_fields($merge_vars);

		debug_message($jsondata);

		$params = array(
			'operation'   => 'create',
			'sessionName' => $login_result,
			'element'     => $jsondata,
			'elementType' => $module,
		);

		$result = $this->call_vtiger_post($webservice, $params);
		$json   = json_decode($result, true);

		debug_message($json);

		if ($json['success']) {
			$recordid = $json['result']['id'];
		} else {
			debug_email_lead('vTiger', 'Error ' . $json['error']['message'], $merge_vars);
			return false;
		}
		return $recordid;
	}
}
