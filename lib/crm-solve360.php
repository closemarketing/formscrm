<?php

///////////////// Solve360 CRM ////////////////////////////////
private function solve360_login($username, $password){
	$url = 'https://secure.solve360.com/contacts?limit=1';
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/xml; charset=UTF-8'));
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($ch, CURLOPT_USERPWD, $username.":".$password);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$data = curl_exec($ch);
	curl_close($ch);
	$doc = new DomDocument();
	$doc->loadXML($data);

	$this->debugcrm($doc);
	if(isset($doc->getElementsByTagName("errors")->item(0)->nodeValue))
		$errorDetails = $doc->getElementsByTagName("errors")->item(0)->nodeValue;
	else
		$errorDetails = '';

	if(!empty($errorDetails)){
		echo '<div id="message" class="error below-h2">
				<p><strong>'.$errorDetails.': </strong></p></div>';
		return FALSE;
	}
	else{
		return TRUE;
	}
}
private function solve360_listfields($username, $password, $module) {
	$url = 'https://secure.solve360.com/'.$module."/fields";
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/xml; charset=UTF-8'));
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($ch, CURLOPT_USERPWD, $username.":".$password);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$data = curl_exec($ch);
	curl_close($ch);
	$this->debugcrm($data);
	if ($data) {
		$xml = simplexml_load_string($data);
		$json_string = json_encode($xml);
		$result_array = json_decode($json_string, TRUE);
		if (isset($result_array['errors'])) {
			echo '<div id="message" class="error below-h2">';
			echo 'Error while retriving fields'.'<br/>Error: '.$result_array['errors'];
			echo '</div>';
		}
		else{
			foreach ($xml->fields->field as $element) {
				$fields[]=array(
					'label' => (string)$element->label,
					'name' =>  (string)$element->name,
					'required' => FALSE
				);
			}
		}
	} else {
		// Something went wrong and we haven't got xml in the rsolvense
		throw new Exception('System error while working with Solve360 service');
	}
	return $fields;
}
private function solve360_createcontact($username, $password, $module, $merge_vars){
	$url = 'https://secure.solve360.com/'.$module;
	$vars = array();
	foreach($merge_vars as $var){
		$vars[$var['name']] =  $var['value'];
	}
	$data_string = json_encode($vars);
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json',
		'Content-Length: ' .strlen($data_string))
			   );
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($ch, CURLOPT_USERPWD, $username.":".$password);
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	//execute post
	$result = curl_exec($ch);
	//close connection
	curl_close($ch);

	if ($result) {
		$xml = simplexml_load_string($result);
		$json_string = json_encode($xml);
		$result_array = json_decode($json_string, TRUE);
		if (isset($result_array['errors'])) {
			echo 'Error while creating record'.'<br/>Error: '.$result_array['errors'];
		}
		else{
			 return $result_array['item']['id'];
		}
	} else {
		// Something went wrong and we haven't got xml in the rsolvense
		throw new Exception('System error while working with Solve360 service');
	}
	return NULL;
}
///////////////// Solve360 CRM ////////////////////////////////
