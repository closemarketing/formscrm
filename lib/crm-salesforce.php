<?php
//////////////////////////////
/////// SALESFORCE CRM ///////
//////////////////////////////

private function salesforce_login($username, $password) {
    require_once ('lib/salesforce/SforcePartnerClient.php');
    require_once ('lib/salesforce/SforceHeaderOptions.php');

    //Return true or false for logged in
	try {
			$mySforceConnection = new SforcePartnerClient();
			$mySoapClient = $mySforceConnection->createConnection(plugin_dir_path( __FILE__ ).'lib/salesforce/partner.wsdl.xml');
			$mylogin = $mySforceConnection->login($username, $password);

            $this->debugcrm($mylogin->userInfo);
			return true;
		}
		catch (Exception $e)
		{
            echo '<div id="message" class="error below-h2">
                <p><strong>Salesforce CRM: Code '.$e.' </strong></p></div>';
		}

		return false;
}

private function salesforce_listfields($username, $password, $module) {
    require_once ('lib/salesforce/SforcePartnerClient.php');
    require_once ('lib/salesforce/SforceHeaderOptions.php');

	try {
			$mySforceConnection = new SforcePartnerClient();
			$mySoapClient = $mySforceConnection->createConnection(plugin_dir_path( __FILE__ ).'lib/salesforce/partner.wsdl.xml');
			$mylogin = $mySforceConnection->login($username, $password);
			$myobj= $mySforceConnection->describeSObject($module);
			//return $myobj->fields;
            $this->debugcrm($myobj->fields);
			$entityArray = array();
			foreach($myobj->fields as $field){
				$entityArray[]=array('label'=> $field->label, 'name' => $field->name, 'required' => !($field->nillable==1)&&($field->defaultedOnCreate!=1)&&($field->name!='Name') );
			}

			return $entityArray;

		}
	catch (Exception $e)
	{
        echo '<div id="message" class="error below-h2">
            <p><strong>Salesforce CRM: Code '.$e.' </strong></p></div>';
	}
}

private function salesforce_create_lead($username, $password, $module, $mergevars) {
    require_once ('lib/salesforce/SforcePartnerClient.php');
    require_once ('lib/salesforce/SforceHeaderOptions.php');

	try {
			$mySforceConnection = new SforcePartnerClient();
			$mySoapClient = $mySforceConnection->createConnection(plugin_dir_path( __FILE__ ).'lib/salesforce/partner.wsdl.xml');
			$mylogin = $mySforceConnection->login($username, $password);

			$fieldsArray = array();
			foreach($mergevars as $attribute){
			    $fieldsArray[$attribute['name']]=$attribute['value'];
			}

			$sObject = new SObject();
			$sObject->fields = $fieldsArray;
			$sObject->type = $module;

			$createResponse = $mySforceConnection->create(array($sObject));

            $this->debugcrm($createResponse);

			return $createResponse[0]->id;
		}
		catch (Exception $e) {
            echo '<div id="message" class="error below-h2">
                <p><strong>Salesforce CRM: Code  '.$e->faultstring.' </strong></p></div>';
            return false;
		}
}

////////////////////////////////
