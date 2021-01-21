<?php
/**
 * FreshDesk connect library
 *
 * Has functions to login, list fields and create lead
 *
 * @author   closemarketing
 * @category Functions
 * @package  Gravityforms CRM
 * @version  1.1.0
 */

include_once 'debug.php';

Class CRMLIB_FRESHDESK {

	/** 
	 * Helper functions
	 */

	// cURL GET function for vTiger
	function call_freshdesk_get($url, $apipassword, $password) {
		$ch = curl_init($url);
		$header[] = "Content-type: application/json";
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_USERPWD, "$apipassword:$password");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$server_output = curl_exec($ch);

		$info = curl_getinfo($ch);

		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$headers = substr($server_output, 0, $header_size);
		$response = substr($server_output, $header_size);

		curl_close($ch);

		return $response;
	}
	function call_freshdesk_post($url, $apipassword, $password, $data) {

		$ch = curl_init($url);
		$header[] = "Content-type: application/json";
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_USERPWD, "$apipassword:$password");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$server_output = curl_exec($ch);

		$info = curl_getinfo($ch);

		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$headers = substr($server_output, 0, $header_size);
		$response = substr($server_output, $header_size);

		curl_close($ch);

		return $response;
	}

	function convert_custom_fields($data_convert) {
		$i        = 0;
		$count    = count($data_convert);
		$jsontext = '{';	

		print_r($data_convert);	

		for ($i = 0; $i < $count; $i++) {
			if(is_numeric($data_convert[$i]['value'])) 
				$value = $data_convert[$i]['value']; 
			else 
				$value = '"'.$data_convert[$i]['value'].'"';

			$jsontext .= '"' . $data_convert[$i]['name'] . '":' . $value;
			if ($i < $count - 1) {$jsontext .= ', ';}
		}
		$jsontext .= '}';

		return $jsontext;
	}
	/**
	 * Login
	 */
	function login($settings) {
		$url      = check_url_crm($settings['gf_crm_url']);
		$password = $settings['gf_crm_password'];
		$apipassword = $settings['gf_crm_apipassword'];

		$webservice = 'api/v2/settings/helpdesk';
		$response = $this->call_freshdesk_get($url.$webservice, $apipassword, $password);

		debug_message($response);

		$json = json_decode($response, true);

		if (isset($json['code']) && $json['code'] == 'invalid_credentials') {
			echo '<div id="message" class="error below-h2"><p><strong>'.$json['message'].' </strong></p></div>';
			return false;
		} else {
			return true;
		}

	}
	/**
	 * List Modules
	 */
	function list_modules($settings) {

		$custom_modules = array(
			array(
				'label' => 'ticket',
				'name' => 'ticket',
			),
		);


		return $custom_modules;
	}

	/**
	 * List Fields
	 */
	function list_fields($settings) {
		$url      = check_url_crm($settings['gf_crm_url']);
		$password = $settings['gf_crm_password'];
		$apipassword = $settings['gf_crm_apipassword'];
		if ( isset( $settings['gf_crm_module'] ) ) {
			$module = $settings['gf_crm_module'];
		} else {
			$module = 'lead';
		}

		//Get fields from module
		$login_result = $this->login($settings);

		debug_message(__('Login result: ', 'gravityforms-crm') . $login_result);
		debug_message(__('Module active: ', 'gravityforms-crm') . $module);

		$webservice = 'api/v2/'.$module.'_fields';

		$response = $this->call_freshdesk_get($url.$webservice, $apipassword, $password);

		debug_message($response);

		$custom_fields = '';
		if (isset($json['code']) && $json['code'] == 'invalid_credentials') {
			echo '<div id="message" class="error below-h2"><p><strong>'.$json['message'].' </strong></p></div>';
			return false;
		}
		$json = json_decode($response, true);

		$i = 1;
		$custom_fields = array();
		$custom_fields[0] = array (
			'label'    => 'Email',
			'name'     => 'email',
			'required' => true,

		);
		foreach ($json as $field) {
			if($field['name']=='requester'||$field['name']=='company') {
				$custom_fields[$i] = array(
					'label'    => $field['label'],
					'name'     => $field['name'],
					'required' => false,
				);
			} elseif ($field['required_for_agents'] == 1) {
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
		$password = $settings['gf_crm_password'];
		$apipassword = $settings['gf_crm_apipassword'];

		if (isset($settings['gf_crm_module'])) {
			$module = $settings['gf_crm_module'];
		} else {
			$module = 'ticket';
		}
		
		$data = $this->convert_custom_fields($merge_vars);
		debug_message($data);

		$webservice = 'api/v2/'.$module.'s';
		$response = $this->call_freshdesk_post($url.$webservice, $apipassword, $password, $data);
		$json   = json_decode($response, true);

		debug_message($json);

		if (!isset($json['errors'])) {
			$recordid = $json['id'];
		} else {
			$error_text = '';
			foreach( $json['errors'] as $error) {
				$error_text .= implode(" ",$error);
				$error_text .= '<br/>';
			} 
			debug_email_lead('FreshDesk', 'Error ' . $error_text, $merge_vars);
			return false;
		}
		return $recordid;
	}
} // from class