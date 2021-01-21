<?php
/**
 * Salesforce connect library
 *
 * Has functions to login, list fields and create lead
 *
 * @author   closemarketing
 * @category Functions
 * @package  Gravityforms CRM
 * @version  1.2.0
 */

include_once 'debug.php';
class CRMLIB_VTIGER7 {
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
		
		require_once ('salesforce/SforcePartnerClient.php');
		require_once ('salesforce/SforceHeaderOptions.php');

		//Return true or false for logged in
		try {
				$mySforceConnection = new SforcePartnerClient();
				$mySoapClient = $mySforceConnection->createConnection(plugin_dir_path( __FILE__ ).'salesforce/partner.wsdl.xml');
				$mylogin = $mySforceConnection->login($username, $password);

				return $mylogin->userInfo->userId;
			}
			catch (Exception $e)
			{
				echo '<div id="message" class="error below-h2">
					<p><strong>Salesforce CRM: Code '.$e.' </strong></p></div>';
			}

			return false;
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

		require_once ('salesforce/SforcePartnerClient.php');
		require_once ('salesforce/SforceHeaderOptions.php');

		try {
				$mySforceConnection = new SforcePartnerClient();
				$mySoapClient = $mySforceConnection->createConnection(plugin_dir_path( __FILE__ ).'salesforce/partner.wsdl.xml');
				$mylogin = $mySforceConnection->login($username, $password);
				$myobj= $mySforceConnection->describeSObject($module);

				$entityArray = array();
				foreach($myobj->fields as $field){
					$entityArray[]=array('label'=> $field->label, 'name' => $field->name, 'required' => !($field->nillable==1)&&($field->defaultedOnCreate!=1)&&($field->name!='Name') );
				}
				echo '<div id="message" class="updated below-h2"><p><strong>'.__('Logged correctly in', 'gravityformscrm').' Salesforce</strong></p></div>';
				return $entityArray;

			}
		catch (Exception $e)
		{
			echo '<div id="message" class="error below-h2">
				<p><strong>Salesforce CRM: Code '.$e.' </strong></p></div>';
		}
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
		require_once ('salesforce/SforcePartnerClient.php');
		require_once ('salesforce/SforceHeaderOptions.php');

		try {
				$mySforceConnection = new SforcePartnerClient();
				$mySoapClient = $mySforceConnection->createConnection(plugin_dir_path( __FILE__ ).'salesforce/partner.wsdl.xml');
				$mylogin = $mySforceConnection->login($username, $password);

				$fieldsArray = array();
				foreach($mergevars as $attribute){
					$fieldsArray[$attribute['name']]=$attribute['value'];
				}

				$sObject = new SObject();
				$sObject->fields = $fieldsArray;
				$sObject->type = $module;

				$createResponse = $mySforceConnection->create(array($sObject));

				debug_message($createResponse);

				return $createResponse[0]->id;
			}
			catch (Exception $e) {
				debug_email_lead('Salesforce',$e->faultstring,$mergevars);
				return false;
			}
	}
}	
