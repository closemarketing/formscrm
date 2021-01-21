<?php
/**
 * WPERP connect library
 *
 * Has functions to login, list fields and create leadº
 *
 * @author   closemarketing
 * @category Functions
 * @package  Gravityforms CRM
 * @version  1.0.0
 */

include_once 'debug.php';

Class GFCRM_WPERP
{
    

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
        $fields = array(
        array('name'=>'first_name', 'required'=>false, 'label'=>'First Name'),
        array('name'=>'last_name', 'required'=>false, 'label'=>'Last Name'),
        array('name'=>'email', 'required'=>false, 'label'=>'Email'),
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
        array('name'=>'ASSIGNED_BY_ID', 'required'=>false,  'label'=>'Responsible'),
        array('name'=>'PHONE_WORK', 'required'=>false,  'label'=>'Work Phone'),
        array('name'=>'PHONE_MOBILE', 'required'=>false,    'label'=>'Mobile'),
        array('name'=>'PHONE_FAX', 'required'=>false,   'label'=>'Fax'),
        array('name'=>'PHONE_HOME', 'required'=>false,  'label'=>'Home Phone'),
        array('name'=>'PHONE_PAGER', 'required'=>false, 'label'=>'Pager'),
        array('name'=>'PHONE_OTHER', 'required'=>false, 'label'=>'Other Phone'),
        array('name'=>'WEB_WORK', 'required'=>false,    'label'=>'Corporate Site'),
        array('name'=>'WEB_HOME', 'required'=>false,    'label'=>'Personal Site'),
        array('name'=>'WEB_FACEBOOK', 'required'=>false,    'label'=>'Facebook Page'),
        array('name'=>'WEB_LIVEJOURNAL', 'required'=>false, 'label'=>'LiveJournal Page'),
        array('name'=>'WEB_TWITTER', 'required'=>false, 'label'=>'Twitter Account'),
        array('name'=>'WEB_OTHER', 'required'=>false,'label'=>'Other Site'),
        array('name'=>'EMAIL_WORK', 'required'=>false,'label'=>'Work E-mail'),
        array('name'=>'EMAIL_HOME', 'required'=>false,'label'=>'Personal E-mail'),
        array('name'=>'EMAIL_OTHER', 'required'=>false, 'label'=>'Other E-mail'),
        array('name'=>'IM_SKYPE', 'required'=>false,'label'=>'Skype'),
        array('name'=>'IM_ICQ', 'required'=>false,'label'=>'ICQ'),
        array('name'=>'IM_MSN', 'required'=>false,'label'=>'MSN/Live!'),
        array('name'=>'IM_JABBER', 'required'=>false,'label'=>'Jabber'),
        array('name'=>'IM_OTHER', 'required'=>false,'label'=>'Other Messenger')
        );

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


        echo $this->url;
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->url."wp-json/erp/v1/crm/contacts");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);

        curl_setopt($ch, CURLOPT_POSTFIELDS, '{
          "type": "contact",
          "first_name": "Madisen",
          "last_name": "Nienow",
          "email": "tHarvey@gmail.com",
          "company": "",
          "phone": "517.709.8784",
          "mobile": "",
          "other": "",
          "website": "http://www.Runolfsson.com/",
          "fax": "",
          "notes": "",
          "street_1": "6143 Jan Valleys Suite 827",
          "street_2": "",
          "city": "Yadiraland",
          "state": "FL",
          "postal_code": "97214",
          "country": "US",
          "currency": "ILS",
          "owner": 1,
          "life_stage": "customer"
        }');

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          "Content-Type: application/json"
        ));

        $response = curl_exec($ch);
        curl_close($ch);

        var_dump($response);
    }


} //from Class

