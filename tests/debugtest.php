<?php
/**
 * Debug functions
 *
 * Functions to debug library CRM
 *
 * @author   closemarketing
 * @category Functions
 * @package  Gravityforms CRM
 * @version  1.0.0
 */

function formscrm_debug_message($message) {
        if (WP_DEBUG==true) {
        //Debug Mode
        echo '  <table class="widefat">
                <thead>
                <tr class="form-invalid">
                    <th class="row-title">Message Debug Mode</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                <td><pre>';
        print_r($message);
        echo '</pre></td></tr></table>';
    }
}
function formscrm_check_url_crm( $url ) {

	if ( ! isset( $url ) ) {
		$url = '';
	}
	if ( substr( $url, -1 ) !== '/' ) {
		$url .= '/'; // adds slash to url.
	}

	return $url;
}

//* Sends an email to administrator when it not creates the lead
function formscrm_debug_email_lead($crm, $error, $data) {
    $to = 'david@closemarketing.es';
    $subject = 'GravityForms CRM - '.__('Error creating the Lead','gravityformscrm');
    $body = '<p>'.__('There was an error creating the Lead in the CRM','gravityformscrm').' '.$crm.':</p><p><strong>'.$error.'</strong></p><p>'.__('Lead Data','gravityformscrm').':</p>';
    foreach($data as $dataitem){
        $body .= '<p><strong>'.$dataitem['name'].': </strong>'.$dataitem['value'].'</p>';
    }
    $body .= '</br/><br/>GravityForms CRM';
    $headers  = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
    $headers .= 'To: david@closemarketing.es' . "\r\n";
    $headers .= 'From: GravityForms CRM <web@closemarketing.es>' . "\r\n";

    mail($to, $subject, $body, $headers);
}
function generateRandomString($length = 10) {
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
	    $randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
 }