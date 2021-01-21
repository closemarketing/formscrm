<?php
/**
 * Prosperworks connect library
 *
 * Has functions to login, list fields and create lead
 *
 * @author   closemarketing
 * @category Functions
 * @package  Gravityforms CRM
 * @version  1.0.0
 */

include_once 'debug.php';
Class CRMLIB_PROSPERWORKS {
    /** 
	 * Helper functions
	 */
    function format_headers(array $headers)
    {        
        if (empty($headers)) {
            return '';
        }    
        $headers_array = array();
        foreach ($headers as $key => $val) {
            $headers_array[] = $key . ': ' . $val;
        }
        return $headers_array;
    }
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
        
        $url = $url.'/users'; 
        $headers=array(
            'Content-Type' => 'application/json',
            'X-PW-Application' => 'developer_api',
            'X-PW-AccessToken' => $password,
            'X-PW-UserEmail' => $username
        );        

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, format_headers($headers)); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $recordsinfo = json_decode($response);    
        //echo '<pre>';    
        //print_r($recordsinfo);        
        //echo '</pre>'; 

        if(isset($recordsinfo->message)){
            echo $recordsinfo->message;
            return FALSE;
        }
        else {
            return TRUE;
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
        
    $fields = array(    	
        array('name'=>'title', 'required'=>false, 'label'=>'Title'),
        array('name'=>'name', 'required'=>true, 'label'=>'Name'),
        array('name'=>'email', 'required'=>false, 'label'=>'Email'),
        array('name'=>'email_category', 'required'=>false, 'label'=>'Email Category'),	
        array('name'=>'company_name', 'required'=>false, 'label'=>'Company'),
        array('name'=>'customer_source_id', 'required'=>false, 'label'=>'Customer Source'),
        array('name'=>'details', 'required'=>false, 'label'=>'Description'),
        array('name'=>'monetary_value', 'required'=>false, 'label'=>'The expected monetary value of business with the Lead'),

        //Phone
        array('name'=>'phone_mobile', 'required'=>false, 'label'=>'Mobile Phone'),
        array('name'=>'phone_work', 'required'=>false, 'label'=>'Work Phone'),
        array('name'=>'phone_home', 'required'=>false, 'label'=>'Home Phone'),
        array('name'=>'phone_other', 'required'=>false, 'label'=>'Other Phone'),

        //Social
        array('name'=>'social_linkedin', 'required'=>false, 'label'=>'Linkedin'),
        array('name'=>'social_twitter', 'required'=>false, 'label'=>'Twitter'),
        array('name'=>'social_googleplus', 'required'=>false, 'label'=>'Googleplus'),
        array('name'=>'social_facebook', 'required'=>false, 'label'=>'Facebook'),
        array('name'=>'social_youtube', 'required'=>false, 'label'=>'Youtube'),
        array('name'=>'social_quora', 'required'=>false, 'label'=>'Quora'),
        array('name'=>'social_foursquare', 'required'=>false, 'label'=>'Foursquare'),
        array('name'=>'social_klout', 'required'=>false, 'label'=>'Klout'),
        array('name'=>'social_gravatar', 'required'=>false, 'label'=>'Gravatar'),
        array('name'=>'social_other', 'required'=>false, 'label'=>'Other'),

        //Websites
        array('name'=>'websites_work', 'required'=>false, 'label'=>'Work Website'),
        array('name'=>'websites_personal', 'required'=>false, 'label'=>'Personal Website'),
        array('name'=>'websites_other', 'required'=>false, 'label'=>'Other Website'),
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
        
        $url = $url.'/'.$module; 
        $headers=array(
            'Content-Type' => 'application/json',
            'X-PW-Application' => 'developer_api',
            'X-PW-AccessToken' => $password,
            'X-PW-UserEmail' => $username
        );    

        $vars = array();        
        //foreach($merge_vars as $var){
        //    $vars[$var['name']] =  $var['value'];  
        //}
        foreach($merge_vars as $var){
            $fieldname = $var['name'];    
            if(stripos($fieldname, "phone_")!==FALSE){
                $phones[]=array('number'=>$var['value'], 'category'=>str_ireplace("phone_", "", $fieldname));
            }
            elseif(stripos($fieldname, "social_")!==FALSE){
                $social[]=array('url'=>$var['value'], 'category'=>str_ireplace("social_", "", $fieldname));
            }
            elseif(stripos($fieldname, "websites_")!==FALSE){
                $websites[]=array('url'=>$var['value'], 'category'=>str_ireplace("websites_", "", $fieldname));
            }
            elseif($fieldname=="email"){
                $email = $var['value'];               
            }
            elseif($fieldname=="email_category"){
                $email_category = $var['value'];               
            }
            else{
            $vars[$fieldname] =  $var['value'];
            }
        }

        if(isset($email)){
            if(isset($email_category)){
                $vars['email']= array( 'email' => $email, 'category' => $email_category);
            }
            else{
                $vars['email']=array( 'email' => $email, 'category' => 'work');
            }
        }

        $vars['phone_numbers']=$phones;
        $vars['socials']=$social;
        $vars['websites']=$websites;
        $data_string = json_encode($vars);  

        $ch = curl_init($url);  
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, format_headers($headers));             
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        //execute post
        $result = curl_exec($ch);
        //close connection
        curl_close($ch);    
        $recordsinfo = json_decode($result);

        //error message
        if(isset($recordsinfo->message)){
            return $recordsinfo->message;
        }

        if(isset($recordsinfo->id)){          
            return  $recordsinfo->id;            
        }     
        return;
    }
}    