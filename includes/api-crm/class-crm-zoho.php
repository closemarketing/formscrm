<?php
/**
 * Zoho connect library
 *
 * Has functions to login, list fields and create lead
 *
 * @author   closemarketing
 * @category Functions
 * @package  Gravityforms CRM
 * @version  1.2.0
 */

include_once 'debug.php';
use zcrmsdk\crm\setup\restclient\ZCRMRestClient;
require_once __DIR__ . '/vendor/autoload.php';

class CRMLIB_ZOHO {
	//cURL Function for Zoho CRM
	function call_zoho_crm( $token, $module, $method ) {
		$request_url = 'https://crm.zoho.com/crm/private/json/'.$module.'/'.$method;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$request_parameters = array('authtoken' => $token,'scope' => 'crmapi');
		$request_url .= '?' . http_build_query($request_parameters);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
		curl_setopt($ch, CURLOPT_URL, $request_url);
		curl_setopt($ch, CURLOPT_HEADER, TRUE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$response = curl_exec($ch);
		$response_info = curl_getinfo($ch);
		curl_close($ch);
		$response_body = substr($response, $response_info['header_size']);
		return $response_body;
	}

	/**
	 * Logins to a CRM
	 *
	 * @param  array $settings settings from Gravity Forms options.
	 * @return false or id           returns false if cannot login and string if gets token
	**/
	public function login( $settings ) {
		$username = $settings['gf_crm_username'];
		$password = $settings['gf_crm_apipassword'];
		$email    = $settings['gf_crm_email'];

		$configuration = array(
			'client_id'        => $settings['gf_crm_clientid'],
			'client_secret'    => $settings['gf_crm_apipassword'],
			'redirect_uri'     => $settings['gf_crm_redirecturi'],
			'currentUserEmail' => $settings['gf_crm_email'],
		);
		ZCRMRestClient::initialize( $configuration );

		if ( $password ) {
			$authkey = $password;
		} else {
			$authkey = file_get_contents('https://accounts.zoho.com/apiauthtoken/nb/create?SCOPE=ZohoCRM/crmapi&EMAIL_ID='.$username.'&PASSWORD='.$password);
			$authkey_exist = strpos($authkey, 'AUTHTOKEN=');

			if( $authkey_exist=== false ) {
				$cause = substr( $authkey, strpos($authkey, 'CAUSE=')+6, strpos($authkey, 'RESULT=')-strpos( $authkey, 'CAUSE=') -7 );
				echo '<div id="message" class="error below-h2">
				<p><strong>'.__('Zoho Error','gravityformscrm').': '.$cause.'</strong></p></div>';
				$authkey = false;
			} else {
				$authkey = substr( $authkey, strpos( $authkey, 'AUTHTOKEN=')+10, 32);
			}
		}
		return $authkey;
	}

	/**
	 * List modules of a CRM
	 *
	 * @param  array $settings settings from Gravity Forms options.
	 * @return array           returns an array of mudules.
	 */
	public function list_modules( $settings ) {
		$password = $settings['gf_crm_apipassword'];
		$rest = ZCRMRestClient::getInstance(); // to get the rest client
		$modules = $rest->getAllModules()->getData();
	
		$modules_array = array();
		foreach ( $modules as $module ) {
			$modules_array[] = array(
				'name'  => $module->getModuleName(),
				'label' => $module->getSingularLabel(),
			);
		}
			/*
		$modules = array(
			array(
				'name'  => 'Leads',
				'label' => 'Leads',
			),
			array(
				'name'  => 'Accounts',
				'label' => 'Accounts',
			),
			array(
				'name'  => 'Contacts',
				'label' => 'Contacts',
			),
			array(
				'name'  => 'Potentials',
				'label' => 'Potentials',
			),
			array(
				'name'  => 'Visits',
				'label' => 'Visits',
			),
			array(
				'name'  => 'Activities',
				'label' => 'Activities',
			),
		);*/

		return $modules;
	}

	/**
	 * List fields of Zoho
	 *
	 * @param  array $settings settings from Gravity Forms options.
	 * @return array           returns an array of fields.
	 */
	public function list_fields( $settings ) {
		$username = $settings['gf_crm_username'];
		$password = $settings['gf_crm_apipassword'];
		if ( isset( $settings['gf_crm_module'] ) ) {
			$module = $settings['gf_crm_module'];
		} else {
			$module = 'lead';
		}

		$result = $this->login( $settings );

            if( isset( $result->response->error ) ) {
			echo '<div id="message" class="error below-h2">
				<p><strong>Zoho CRM: Code '.$result->response->error->code.' - '.$result->response->error->message.' </strong></p></div>';
            	return false;
		    }
		    var_dump($result);
		$sections =$result->$module->section;
		foreach($sections as $section){
			$section_fields = $section->FL;
			foreach($section_fields as $section_field){
			if(isset($section_field->dv)){
				$var_name = str_replace(' ', '_', $section_field->label);
					if($section_field->req=='true'){
					$convert_fields[] = array('label' => $section_field->dv, 'name' => $var_name, 'required' => $section_field->req);
					echo '<h1>si</h1>';
					}
					else{
					$convert_fields[] = array('label' => $section_field->dv, 'name' => $var_name);
					echo '<h1>no</h1>';
					}
				} //if isset

			}
		} //foreach
		return $convert_fields;
	}

        /**
         * Create Entry
         */
       function create_entry( $settings, $merge_vars ) {
            $username = $settings['gf_crm_username'];
		$password = $settings['gf_crm_apipassword'];
            if ( isset( $settings['gf_crm_module'] ) ) {
                $module = $settings['gf_crm_module'];
            } else {
                $module = 'lead';
            }
            $xmldata = '<'.$module.'><row no="1">'; 
            $i=0;
            $count = count( $merge_vars );
            for ( $i = 0; $i < $count; $i++ ){
                                    $var_name = str_replace('_', ' ', $merge_vars[$i]['name']);
                    $xmldata .= '<FL val="'.$var_name.'">';
                    $xmldata .= $merge_vars[$i]['value'].'</FL>';
                }
		$xmldata .= '</row></'.$module.'>';

		$url = 'https://crm.zoho.com/crm/private/xml/'.$module.'/insertRecords';
			$token =$password;
		$param= 'authtoken='.$token.'&scope=crmapi&xmlData='.$xmldata;

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$query = array('newFormat'=>1,'authtoken'=>$token,'scope'=>'crmapi','xmlData'=>$xmldata);

		$query = http_build_query($query);

		curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
		$result = curl_exec($ch);
		return $result;
    }
}
