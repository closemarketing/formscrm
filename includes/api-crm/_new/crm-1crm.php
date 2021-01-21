<?php
/**
 * 1CRM connect library
 *
 * Has functions to login, list fields and create lead
 *
 * @author   closemarketing
 * @category Functions
 * @package  Gravityforms CRM
 * @version  1.0
 */

include_once 'debug.php';



/*
use OneCRM\APIClient\Authentication;
use OneCRM\APIClient;
*/

class CRMLIB_1CRM {
	private $oneCRM_Client;

	/**
	 * Construct and intialize
	 */
	public function __construct() {
		require_once 'vendor/autoload.php';

	}

	/**
	 * Logins to a CRM
	 * @param  array $settings settings from Gravity Forms options
	 * @return false or id           returns false if cannot login and string if gets token
	 */
	function login($settings) {
		$url      = check_url_crm($settings['gf_crm_url']);
		$username = $settings['gf_crm_username'];
		$password = $settings['gf_crm_password'];

		$this->oneCRM_Client = new OneCRM\Client($url.'/service/v4/rest.php', true);
		try {
		    $this->oneCRM_Client->login($username, $password);
		    return $this->oneCRM_Client->getSessionID();

		} catch (OneCRM\Exception $e) {
		    echo 'An error occurred: '. get_class($e) . ': ' . $e->getMessage();
		    return false;
		}
	}
	/**
	 * List modules of a CRM
	 *
	 * @param  array $settings settings from Gravity Forms options
	 * @return array           returns an array of mudules
	 */
	function list_modules($settings) {
		$url      = check_url_crm($settings['gf_crm_url']);
		$username = $settings['gf_crm_username'];
		$password = $settings['gf_crm_password'];

		$login_result = $this->login($settings);
		$i=0;
		$custom_modules = array();
		foreach ($this->oneCRM_Client->getModules() as $module) {

			$custom_modules[$i] = array(
				'label' => $module['label'],
				'name'  => $module['name'],
			);
			$i++;

		}
		return $custom_modules;
	}

	/**
	 * List Fields
	 */
	function list_fields($settings) {
		if (isset($settings['gf_crm_module']))
			$module = $settings['gf_crm_module'];
		else
			$module = 'Leads';

		//Get fields from module
		$login_result = $this->login($settings);

		debug_message(__('Login result:', 'gravityforms-crm') . $login_result);
		debug_message(__('Module active:', 'gravityforms-crm') . $module);

		$response = $this->oneCRM_Client->call(
		        $module,
		        'get_module_fields'
		    );

		$i = 0;
		$custom_fields = array();
		foreach ($response->module_fields as $arrayob) {
			$custom_fields[$i] = array(
				'label'    => $arrayob->label.'('.$arrayob->name.')',
				'name'     => $arrayob->name
			);
			if ($arrayob->required == 1) 
				$custom_fields[$i]['required'] = true;
				
			$i++;
		}
		return $custom_fields;

	}

	/**
	 * Create Entry
	 */
	function create_entry($settings, $merge_vars) {
		$url      = check_url_crm( $settings['gf_crm_url'] );
		$username = $settings['gf_crm_username'];
		$password = $settings['gf_crm_apipassword'];
		if (isset($settings['gf_crm_module']))
			$module = $settings['gf_crm_module'];
		else
			$module = 'Leads';

		$login_result = $this->login($settings);

		$response = $this->oneCRM_Client->call(
		        $module,
		        'set_entry',
		        array( "name_value_list" => $merge_vars )
		    );

		print_r($response);

		return;
	}
}
