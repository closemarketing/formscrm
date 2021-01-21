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
if (!function_exists('debug_message')) {
  function debug_message($message) {
          if (WP_DEBUG==true) {
          //Debug Mode
          echo '  <table class="widefat">
                  <thead>
                  <tr class="form-invalid">
                      <th class="row-title">'.__('Message Debug Mode','gravityformscrm').'</th>
                  </tr>
                  </thead>
                  <tbody>
                  <tr>
                  <td><pre>';
          print_r($message);
          echo '</pre></td></tr></table>';
      }
  }
}

//* Sends an email to administrator when it not creates the lead
if (!function_exists('debug_email_lead')) {
  function debug_email_lead($crm, $error, $data) {
      $to = get_option('admin_email');
      $subject = 'GravityForms CRM - '.__('Error creating the Lead','gravityformscrm');
      $body = '<p>'.__('There was an error creating the Lead in the CRM','gravityformscrm').' '.$crm.':</p><p><strong>'.$error.'</strong></p><p>'.__('Lead Data','gravityformscrm').':</p>';
      foreach($data as $dataitem){
          $body .= '<p><strong>'.$dataitem['name'].': </strong>'.$dataitem['value'].'</p>';
      }
      $body .= '</br/><br/>GravityForms CRM';
      $headers = array('Content-Type: text/html; charset=UTF-8');

      wp_mail( $to, $subject, $body, $headers );
  }
}

if (!function_exists('testserver')) {
  function testserver() {
          //test curl
      if(!function_exists('curl_version'))
      echo '<div id="message" class="error below-h2">
          <p><strong>'.__('curl is not Installed in your server. It is needed to work with CRM Libraries.' ,'gravityformscrm').'</strong></p></div>';
  }
}

/* Checks CRM URL to see that is correct */
if (!function_exists('check_url_crm')) {
function check_url_crm($url) {

    if (!isset($url) ) $url = "";
    if (substr($url, -1) !='/') $url.='/'; //adds slash to url

    return $url;
}
}