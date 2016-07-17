<?php
/**
 * Bitrix connect library
 *
 * Has functions to login, list fields and create leadº
 *
 * @author   closemarketing
 * @category Functions
 * @package  Gravityforms CRM
 * @version  1.1.0
 */

include_once 'debug.php';

function bitrix_login($username, $password, $url, $crmport) {
// open socket to CRM
	if(substr($url, 0, 4) == 'http') {
		//Url normal
		$url = parse_url($url);
		$url = $url['host'];
	}
	if(substr($url, -1) =='/') $url = substr($url, 0, -1); //removes slash to url

	$fp = fsockopen("ssl://".$url, $crmport, $errno, $errstr, 30);
	if ($fp) {
		echo '<div id="message" class="updated below-h2"><p><strong>'.__('Logged correctly in', 'gravityformscrm').' Bitrix</strong></p></div>';
		return true;
		}
	return false;
}
function bitrix_listfields($username, $password, $url, $module) {
	$fields = array(
	//array('name'=>'LOGIN', 'required'=>true, 'label'=>'Login'),
	//array('name'=>'PASSWORD', 'required'=>true,'label'=>'Password'),
	array('name'=>'TITLE', 'required'=>true, 'label'=>'Title'),
	array('name'=>'COMPANY_TITLE', 'required'=>false, 'label'=>'Company Name'),
	array('name'=>'NAME', 'required'=>false, 'label'=>'First Name'),
	array('name'=>'LAST_NAME', 'required'=>false, 'label'=>'Last Name'),
	array('name'=>'SECOND_NAME', 'required'=>false, 'label'=>'Second Name'),
	array('name'=>'POST', 'required'=>false, 'label'=>'Position'),
	array('name'=>'ADDRESS', 'required'=>false, 'label'=>'Address'),
	array('name'=>'COMMENTS', 'required'=>false, 'label'=>'Comment'),
	array('name'=>'SOURCE_DESCRIPTION', 'required'=>false, 'label'=>'Source Description'),
	array('name'=>'STATUS_DESCRIPTION', 'required'=>false, 'label'=>'Status Description'),
	array('name'=>'OPPORTUNITY', 'required'=>false, 'label'=>'Opportunity'),
	array('name'=>'CURRENCY_ID', 'label'=>'Currency'),
	array('name'=>'PRODUCT_ID', 'label'=>'Product'),
	array('name'=>'SOURCE_ID', 'label'=>'Source'),
	array('name'=>'STATUS_ID', 'label'=>'Lead Status'),
	array('name'=>'ASSIGNED_BY_ID',	'required'=>false,	'label'=>'Responsible'),
	array('name'=>'PHONE_WORK', 'required'=>false,	'label'=>'Work Phone'),
	array('name'=>'PHONE_MOBILE', 'required'=>false,	'label'=>'Mobile'),
	array('name'=>'PHONE_FAX', 'required'=>false,	'label'=>'Fax'),
	array('name'=>'PHONE_HOME', 'required'=>false,	'label'=>'Home Phone'),
	array('name'=>'PHONE_PAGER', 'required'=>false,	'label'=>'Pager'),
	array('name'=>'PHONE_OTHER', 'required'=>false,	'label'=>'Other Phone'),
	array('name'=>'WEB_WORK', 'required'=>false,	'label'=>'Corporate Site'),
	array('name'=>'WEB_HOME', 'required'=>false,	'label'=>'Personal Site'),
	array('name'=>'WEB_FACEBOOK', 'required'=>false,	'label'=>'Facebook Page'),
	array('name'=>'WEB_LIVEJOURNAL', 'required'=>false,	'label'=>'LiveJournal Page'),
	array('name'=>'WEB_TWITTER', 'required'=>false,	'label'=>'Twitter Account'),
	array('name'=>'WEB_OTHER', 'required'=>false,'label'=>'Other Site'),
	array('name'=>'EMAIL_WORK', 'required'=>false,'label'=>'Work E-mail'),
	array('name'=>'EMAIL_HOME', 'required'=>false,'label'=>'Personal E-mail'),
	array('name'=>'EMAIL_OTHER', 'required'=>false,	'label'=>'Other E-mail'),
	array('name'=>'IM_SKYPE', 'required'=>false,'label'=>'Skype'),
	array('name'=>'IM_ICQ', 'required'=>false,'label'=>'ICQ'),
	array('name'=>'IM_MSN', 'required'=>false,'label'=>'MSN/Live!'),
	array('name'=>'IM_JABBER', 'required'=>false,'label'=>'Jabber'),
	array('name'=>'IM_OTHER', 'required'=>false,'label'=>'Other Messenger')
	);

	return $fields;
}
function bitrix_create_lead($username, $password, $url, $crmport, $module, $merge_vars) {
	// get lead data from the form
	$postData = array();
	foreach($merge_vars as $attribute){
	$postData[$attribute['name']]=$attribute['value'];
	}
	$crm_path = '/crm/configs/import/lead.php';

	$postData['LOGIN'] = $username;
	$postData['PASSWORD'] = $password;

	// open socket to CRM
	if(substr($url, 0, 4) == 'http') {
		//Url normal
		$url = parse_url($url);
		$url = $url['host'];
	}
	if(substr($url, -1) =='/') $url = substr($url, 0, -1); //removes slash to url
	$fp = fsockopen("ssl://".$url, $crmport, $errno, $errstr, 30);
	if ($fp)
	{
		// prepare POST data
		$strPostData = '';
		foreach ($postData as $key => $value)
			$strPostData .= ($strPostData == '' ? '' : '&').$key.'='.urlencode($value);

		// prepare POST headers
		$str = "POST ".$crm_path." HTTP/1.0\r\n";
		$str .= "Host: ".$url."\r\n";
		$str .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$str .= "Content-Length: ".strlen($strPostData)."\r\n";
		$str .= "Connection: close\r\n\r\n";

		$str .= $strPostData;

		// send POST to CRM
		fwrite($fp, $str);

		// get CRM headers
		$result = '';
		while (!feof($fp))
		{
			$result .= fgets($fp, 128);
		}
		fclose($fp);

		// cut response headers
		$response = explode("\r\n\r\n", $result);

		//$output = '<pre>'.print_r($response[1], 1).'</pre>';
		$retValue =str_replace("'",'"', $response[1]);
		$retValue = json_decode($retValue);

		debug_message($retValue);

		if ($retValue->error<>201) { // if error
			echo '<div id="message" class="error below-h2"><p><strong>'.$retValue->error.' '.$retValue->error_message.': </strong></p></div>';
		}

		return $retValue->ID;
	}
	else
	{
		echo 'Connection Failed! '.$errstr.' ('.$errno.')';
	}
}
///////////////// Bitrix 24 CRM ////////////////////////////////
