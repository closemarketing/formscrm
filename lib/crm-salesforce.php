<?php
//////////////////////////////
/////// SALESFORCE CRM ///////
//////////////////////////////

function salesforce_login($username, $password) {
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

function salesforce_listfields($username, $password, $module) {
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

function salesforce_create_lead($username, $password, $module, $mergevars) {
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

            if (WP_DEBUG==true) { print_r($createResponse); }

			return $createResponse[0]->id;
		}
		catch (Exception $e) {
            echo '<div id="message" class="error below-h2">
                <p><strong>Salesforce CRM: Code  '.$e->faultstring.' </strong></p></div>';
            return false;
		}
}

////////////////////////////////
