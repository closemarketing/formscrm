<?php
/**
 * VTE connect library
 *
 * Has functions to login, list fields and create lead
 *
 * @author   closemarketing
 * @category Functions
 * @package  Gravityforms CRM
 * @version  1.2.0
 */

include_once 'debug.php';
class CRMLIB_VTIGER7 {
    /**
	 * Logins to a CRM
	 *
	 * @param  array $settings settings from Gravity Forms options
	 * @return false or id           returns false if cannot login and string if gets token
	 */
	function login( $settings ) {
		$url      = check_url_crm( $settings['gf_crm_url'] );
		$username = $settings['gf_crm_username'];
		$password = $settings['gf_crm_apipassword'];


        require_once('vtwsclib/VTEWSClient.php');
        $client = new VTE_WSClient($url);

        $login = $client->doLogin($username,$password);

        return $login;
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

        require_once('vtwsclib/VTEWSClient.php');

        $client = new VTE_WSClient($url);
        $login = $client->doLogin($username, $apipassword);

        $describe = $client->doDescribe($module);

        $cancreate = $describe['createable'];
        $canupdate = $describe['updateable'];
        $candelete = $describe['deleteable'];
        $canread   = $describe['retrieveable'];
        $fields    = $describe['fields'];

        $i=0;
        $custom_fields = array();
        foreach ($fields as $field) {

            if($field['mandatory']==1) {
                $custom_fields[$i] = array(
                    'label' => $field['label'].' ('.$field['name'].')',
                    'name' => $field['name'],
                    'required' => true,
                    );
            } else {
                $custom_fields[$i] = array(
                    'label' => $field['label'].' ('.$field['name'].')',
                    'name' => $field['name']
                    );
            }
            $i++;
        } //foreach

        return $custom_fields;
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
        require_once('vtwsclib/VTEWSClient.php');

        $client = new VTE_WSClient($url);
        $login = $client->doLogin($username, $apipassword);

        $array_lead = array();

        //Convert for VTE way
        $i=0;
        foreach($merge_vars as $arraymerge){
            $array_lead[$arraymerge['name']] = $arraymerge['value'];
        }

        $record = $client->doCreate($module, $array_lead);
        if($record) {
            $recordid = $client->getRecordId($record['id']);
        } else {
                debug_email_lead('VTE','Error',$merge_vars);
        }

        return $record;
    }
}