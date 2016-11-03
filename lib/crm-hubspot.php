<?php
/**
 * Hubspot connect library
 *
 * Has functions to login, list fields and create leadº
 *
 * @author   closemarketing
 * @category Functions
 * @package  Gravityforms CRM
 * @version  1.0.0
 */

include_once 'debug.php';

function hubspot_login($apipassword) {
	 $endpoint = 'https://api.hubapi.com/contacts/v1/lists/all/contacts/all?hapikey=' . $apipassword.'&count=1';
	 $ch = @curl_init();
	 @curl_setopt($ch, CURLOPT_URL, $endpoint);
	 @curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
	 @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	 @curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	 $response = @curl_exec($ch);
	 $status_code = @curl_getinfo($ch, CURLINFO_HTTP_CODE);
	 $curl_errors = curl_error($ch);
	 @curl_close($ch);
	 return $status_code==200;
}

function hubspot_listfields($apipassword, $module) {
	 $endpoint = 'https://api.hubapi.com/properties/v1/'.$module.'s/properties?hapikey=' . $apipassword;
	 $ch = @curl_init();
	 @curl_setopt($ch, CURLOPT_URL, $endpoint);
	 @curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
	 @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	 @curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	 $response = @curl_exec($ch);
	 $status_code = @curl_getinfo($ch, CURLINFO_HTTP_CODE);
	 $curl_errors = curl_error($ch);
	 @curl_close($ch);

	 $responsefields= json_decode( $response);
	 if(isset($responsefields->status) && (string)$responsefields->status=='error')
	 {
			 if(isset($responsefields->message)) {
				   debug_message($responsefields->message);
					 return $responsefields->message;
			 } else {
	         echo '<div id="message" class="error below-h2"><p><strong>'.__('Invalid hapikey!','gravityformscrm').'</strong></p></div>';
	         return false;
			 }
	 }

	 foreach ($responsefields as $element) {
			 //if($element->formField==TRUE)
					 $fields[]=array(
							 'label' => (string)$element->label,
							 'name' =>  (string)$element->name,
							 'required' => FALSE
					 );
			 }

	 return $fields;
}

function hubspot_create_lead($apipassword, $module, $merge_vars) {
	 $vars = array();
	 foreach($merge_vars as $var){
			 $vars[$var['name']] =  $var['value'];
			 $fields[]=array(
							 'property' => $var['name'],
							 'value' => $var['value']
					 );
	 }

	 $endpoint = 'https://api.hubapi.com/'.$module.'s/v1/'.$module.'/?hapikey='. $apipassword;
	 $post_json = json_encode(array('properties'=>$fields));
	 $ch = @curl_init();
	 @curl_setopt($ch, CURLOPT_POST, true);
	 @curl_setopt($ch, CURLOPT_POSTFIELDS, $post_json);
	 @curl_setopt($ch, CURLOPT_URL, $endpoint);
	 @curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
	 @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	 @curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	 $response = @curl_exec($ch);
	 $status_code = @curl_getinfo($ch, CURLINFO_HTTP_CODE);
	 $curl_errors = curl_error($ch);
	 @curl_close($ch);
	 $res =json_decode($response);

	 if($curl_errors != NULL)
	 return $curl_errors;

	 if(isset($res->vid)) {
			 return $res->vid;
	 } elseif(isset($res->message)) {
		   debug_email_lead('Hubspot',$res->message,$merge_vars);
			 debug_message($res->message);
	 }
}
