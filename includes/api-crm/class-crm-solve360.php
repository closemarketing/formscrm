<?php
/**
 * Solve360 connect library
 *
 * Has functions to login, list fields and create lead
 *
 * @author   closemarketing
 * @category Functions
 * @package  Gravityforms CRM
 * @version  1.2.0
 */

include_once 'debug.php';
Class CRMLIB_SOLVE360 {
			 /**
			 * Logins to a CRM
			 *
			 * @param  array $settings settings from Gravity Forms options
			 * @return false or id           returns false if cannot login and string if gets token
			 */
			function login( $settings ) {
        
        $username = null;
        if( isset( $settings['gf_crm_username'] ) ) {
          $username = $settings['gf_crm_username'];
        }
        $password = null;
        if( isset( $settings['gf_crm_apipassword'] ) ) {
          $password = $settings['gf_crm_apipassword'];
        }
    
    if( $username && $password ) {

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

			if(isset($doc->getElementsByTagName("errors")->item(0)->nodeValue))
				$errorDetails = $doc->getElementsByTagName("errors")->item(0)->nodeValue;
			else
				$errorDetails = '';

			if(!empty($errorDetails)){
				echo '<div id="message" class="error below-h2">
						<p><strong>'.$errorDetails.': </strong></p></div>';
				return false;
			}
			else{
				echo '<div id="message" class="updated below-h2"><p><strong>'.__('Logged correctly in', 'gravityformscrm').' Solve360</strong></p></div>';
				return true;
			}
      
    } else {
      return false;
    }
      
      
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
						debug_email_lead('Solve360',$result_array['errors'],$merge_vars);
					}
					else{
						return $result_array['item']['id'];
					}
				} else {
					// Something went wrong and we haven't got xml in the rsolvense
					debug_email_lead('Solve360',__('System error while working with Solve360 service','gravityformscrm'),$merge_vars);
				}
				return NULL;
			}
}
///////////////// Solve360 CRM ////////////////////////////////
