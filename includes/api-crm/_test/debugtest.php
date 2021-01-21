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

function debug_message($message) {
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

//* Sends an email to administrator when it not creates the lead
function debug_email_lead($crm, $error, $data) {
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
