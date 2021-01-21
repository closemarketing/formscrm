<?php
//$test_username ="david@closemarketing.es";
//$test_password = "granada10";
//$test_url ="https://eu5.salesforce.com/";
/*
$test_username ="dev@closemarketing.es";
$password = "Pt3Haqm3";
$token = "F3C6ZyL35VndqICTiXfcyY7a4";
$test_password = $password.$token;
*/

$settings['gf_crm_type']        = "vTiger 7";
$settings['gf_crm_username']    = "dev@closemarketing.es";
$settings['gf_crm_url']         = 'http://demo7.vtexperts.com/vtigercrm7demo/';
$settings['gf_crm_apipassword'] = "Pt3Haqm3F3C6ZyL35VndqICTiXfcyY7a4";
$settings['gf_crm_module']      = "Leads";

    /////// SALESFORCE CRM ///////
	Class CRMLIB_SALESFORCE {
	/** 
	 * login
	 */
		function salesforce_login($username, $password) {
			require_once ('lib/salesforce/SforcePartnerClient.php');
			require_once ('lib/salesforce/SforceHeaderOptions.php');

			//Return true or false for logged in
			try {
					$mySforceConnection = new SforcePartnerClient();
					$mySoapClient = $mySforceConnection->createConnection('lib/salesforce/partner.wsdl.xml');
					$mylogin = $mySforceConnection->login($username, $password);
					//echo '<pre>';
					//print_r($mylogin->userInfo);
					//echo '</pre>';
					return true;
				}
				catch (Exception $e)
				{
					print_r($e);
				}

				return false;
		}
		/** 
	 	* list field
		 */
		function salesforce_listfields($username, $password, $module) {
			require_once ('lib/salesforce/SforcePartnerClient.php');
			require_once ('lib/salesforce/SforceHeaderOptions.php');

			// Return an array of fields
			/*        Array(
						array('label'=> valuefieldlabel, 'name' => valuenamefield, 'required' => true),
						array('label'=> valuelabel, 'name' => valuenamefield),
						array('label'=> valuelabel, 'name' => valuenamefield)
						)
						*/

			try {
					$mySforceConnection = new SforcePartnerClient();
					$mySoapClient = $mySforceConnection->createConnection('lib/salesforce/partner.wsdl.xml');
					$mylogin = $mySforceConnection->login($username, $password);
					$myobj= $mySforceConnection->describeSObject($module);
					//return $myobj->fields;
					$entityArray = array();
					foreach($myobj->fields as $field){
						$entityArray[]=array('label'=> $field->label, 'name' => $field->name, 'required' => !($field->nillable==1));
					}

					return $entityArray;

				}
			catch (Exception $e)
			{
				print_r($e);
			}
		}
		/** 
	 	* create lead
		 */
		function salesforce_create_lead($username, $password, $module, $mergevars) {
			require_once ('lib/salesforce/SforcePartnerClient.php');
			require_once ('lib/salesforce/SforceHeaderOptions.php');

			try {
					$mySforceConnection = new SforcePartnerClient();
					$mySoapClient = $mySforceConnection->createConnection('lib/salesforce/partner.wsdl.xml');
					$mylogin = $mySforceConnection->login($username, $password);

					$fieldsArray = array();
					foreach($mergevars as $attribute){
					$fieldsArray[$attribute['name']]=$attribute['value'];
					}

					$sObject = new SObject();
					$sObject->fields = $fieldsArray;
					$sObject->type = $module;

					print_r($sObject);
					$createResponse = $mySforceConnection->create(array($sObject));

					return $createResponse[0]->id;

				}
				catch (Exception $e) {
					echo $mySforceConnection->getLastRequest();
					echo $e->faultstring;
				}
		}
	}	
	


    ////////////////////////////////
$crmlib = new CRMLIB_SALESFORCE();
echo '<p>Login SALESFORCE CRM:</p>';
$login_api = $crmlib->login($settings);

echo '<pre>';
print_r($login_api);
echo '</pre>';

echo '<p>List Modules</p>';
$list_modules = $crmlib->list_modules($settings);
echo '<pre>';
print_r($list_modules);
echo '<pre>';

echo '<p>List Fields</p>';
$list_fields = $crmlib->list_fields($settings);
echo '<pre>';
print_r($list_fields);
echo '</pre>';

echo '<p>Create lead from test mergevar</p>';


$test_mergevars = array(
                array( 'name' => 'FirstName', 'value' => 'Kola'),
                array( 'name' => 'LastName', 'value' => 'V'),
				array( 'name' => 'Phone', 'value' => '510-555-5555'),
				array( 'name' => 'Salutation', 'value' => 'Mr.'),
				array( 'name' => 'Company', 'value' => 'Closemarketing'),
			);

$leadid = $crmlib->create_entry($settings, $test_mergevars);
echo '<pre>';
print_r($leadid);
echo '<pre>';
?>
