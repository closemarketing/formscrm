<?php

GFForms::include_feed_addon_framework();

class GFCRM extends GFFeedAddOn {

	protected $_version = GF_CRM_VERSION;
	protected $_min_gravityforms_version = '1.8.17';
	protected $_slug = 'gravityformscrm';
	protected $_path = 'gravityformscrm/crm.php';
	protected $_full_path = __FILE__;
	protected $_url = 'http://www.gravityforms.com';
	protected $_title = 'CRM Add-On';
	protected $_short_title = 'CRM';

	// Members plugin integration
	protected $_capabilities = array( 'gravityforms_crm', 'gravityforms_crm_uninstall' );

	// Permissions
	protected $_capabilities_settings_page = 'gravityforms_crm';
	protected $_capabilities_form_settings = 'gravityforms_crm';
	protected $_capabilities_uninstall = 'gravityforms_crm_uninstall';
	protected $_enable_rg_autoupgrade = true;

	private static $_instance = null;

	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new GFCRM();
		}

		return self::$_instance;
	}

	public function init() {

		parent::init();

	}

	public function init_admin(){
		parent::init_admin();

		$this->ensure_upgrade();
	}

	// ------- Plugin settings -------
	public function plugin_settings_fields() {
		return array(
			array(
				'title'       => __( 'CRM Account Information', 'gravityformscrm' ),
				'description' => sprintf( __( 'CRM is a CRM software. Use Gravity Forms to collect customer information and automatically add them to your crm Leads.', 'gravityformscrm' ),
					'<a href="http://www.crm.com" target="_blank">', '</a>.' ),
				'fields'      => array(
					array(
						'name'              => 'gf_crm_type',
						'label'             => __( 'CRM Type', 'gravityformscrm' ),
						'type'              => 'select',
						'class'             => 'medium',
                        'choices'           => array(
                                                    array(
                                                        'label' => 'vTiger',
                                                        'name'  => 'vtiger'
                                                    ),
                                                    array(
                                                        'label' => 'SugarCRM',
                                                        'name'  => 'sugarcrm'
                                                    )
                                                )
					),
					array(
						'name'              => 'gf_crm_url',
						'label'             => __( 'CRM URL', 'gravityformscrm' ),
						'type'              => 'text',
						'class'             => 'medium',
					),
					array(
						'name'              => 'gf_crm_username',
						'label'             => __( 'Username', 'gravityformscrm' ),
						'type'              => 'text',
						'class'             => 'medium',
					),
					array(
						'name'  => 'gf_crm_password',
						'label' => __( 'API Password for User', 'gravityformscrm' ),
						'type'  => 'api_key',
						'class' => 'medium',
						'feedback_callback' => array( $this, 'is_valid_key' )
					),
				)
			),
		);

	}

	public function settings_api_key( $field, $echo = true ) {

		$field['type'] = 'text';

		$api_key_field = $this->settings_text( $field, false );

		//switch type="text" to type="password" so the key is not visible
		$api_key_field = str_replace( 'type="text"','type="password"', $api_key_field );

		$caption = '<small>' . sprintf( __( "You can find your unique API key by clicking on the 'Account Settings' link at the top of your CRM screen.", 'gravityformscrm' ) ) . '</small>';

		if ( $echo ) {
			echo $api_key_field . '</br>' . $caption;
		}

		return $api_key_field . '</br>' . $caption;
	}


	//-------- Form Settings ---------
	public function feed_edit_page( $form, $feed_id ) {

		// ensures valid credentials were entered in the settings page
		if ( $this->is_valid_key() === false ) {
			?>
			<div><?php echo sprintf( __( 'We are unable to login to CRM with the provided API key. Please make sure you have entered a valid API key in the %sSettings Page%s', 'gravityformscrm' ),
					'<a href="' . $this->get_plugin_settings_url() . '">', '</a>' ); ?>
			</div>
			<?php
			return;
		}

		echo '<script type="text/javascript">var form = ' . GFCommon::json_encode( $form ) . ';</script>';

		parent::feed_edit_page( $form, $feed_id );
	}


	public function feed_settings_fields() {
		return array(
			array(
				'title'       => __( 'CRM Feed', 'gravityformscrm' ),
				'description' => '',
				'fields'      => array(
					array(
						'name'     => 'feedName',
						'label'    => __( 'Name', 'gravityformscrm' ),
						'type'     => 'text',
						'required' => true,
						'class'    => 'medium',
						'tooltip'  => '<h6>' . __( 'Name', 'gravityformscrm' ) . '</h6>' . __( 'Enter a feed name to uniquely identify this setup.', 'gravityformscrm' ),
					),
					array(
						'name'       => 'listFields',
						'label'      => __( 'Map Fields', 'gravityformscrm' ),
						'type'       => 'field_map',
						//'dependency' => 'contactList',
						'field_map'	 => $this->create_list_field_map(),
						'tooltip'    => '<h6>' . __( 'Map Fields', 'gravityformscrm' ) . '</h6>' . __( 'Associate your CRM custom fields to the appropriate Gravity Form fields by selecting the appropriate form field from the list.', 'gravityformscrm' ),
					),
				)
			),
		);
	}

	public function create_list_field_map() {

		$custom_fields = $this->get_custom_fields_vtiger( );

		return $custom_fields;

	}

	public function get_custom_fields_vtiger( ) {
            
        $settings = $this->get_plugin_settings();
        $crm_type  = $settings['gf_crm_type'];
        
        
        if($crm_type == 'vTiger') { //vtiger Method
            $custom_fields = array( //Custom Fields for vTiger
                array( 'label' => __('Email Address', 'gravityformscrm' ), 'name' => 'email', 'required' => true ),
                array( 'label' => __('Full Name', 'gravityformscrm' ) , 'name' => 'fullname' ),
                array( 'label' => __('Phone', 'gravityformscrm' ) , 'name' => 'phone' ),
                array( 'label' => __('Lead Source', 'gravityformscrm' ) , 'name' => 'leadsource' ),
                array( 'label' => __('Birthday', 'gravityformscrm' ) , 'name' => 'birthday' ),
                array( 'label' => __('Address', 'gravityformscrm' ) , 'name' => 'mailingstreet' ),
                array( 'label' => __('City', 'gravityformscrm' ) , 'name' => 'mailingcity' ),
                array( 'label' => __('State', 'gravityformscrm' ) , 'name' => 'mailingstate' ),
                array( 'label' => __('ZIP', 'gravityformscrm' ) , 'name' => 'mailingzip' ),
                array( 'label' => __('Country', 'gravityformscrm' ) , 'name' => 'mailingcountry' ),
                array( 'label' => __('Description', 'gravityformscrm' ) , 'name' => 'description' ),
            );
        } elseif($crm_type == 'SugarCRM') {
            $custom_fields = array( //Custom Fields for SugarCRM
                array( 'label' => __('Email Address', 'gravityformscrm' ), 'name' => 'webtolead_email1', 'required' => true ),
                array( 'label' => __('First Name', 'gravityformscrm' ) , 'name' => 'first_name' ),
                array( 'label' => __('Last Name', 'gravityformscrm' ) , 'name' => 'last_name' ),
                array( 'label' => __('Phone Work', 'gravityformscrm' ) , 'name' => 'phone_work' ),
                array( 'label' => __('Lead Source', 'gravityformscrm' ) , 'name' => 'lead_source' ),
                array( 'label' => __('Address', 'gravityformscrm' ) , 'name' => 'primary_address_street' ),
                array( 'label' => __('City', 'gravityformscrm' ) , 'name' => 'primary_address_city' ),
                array( 'label' => __('State', 'gravityformscrm' ) , 'name' => 'primary_address_state' ),
                array( 'label' => __('ZIP', 'gravityformscrm' ) , 'name' => 'primary_address_postalcode' ),
                array( 'label' => __('Country', 'gravityformscrm' ) , 'name' => 'primary_address_country' ),
                array( 'label' => __('Description', 'gravityformscrm' ) , 'name' => 'description' ),
            );
        }


        
		return $custom_fields;

	}


	public function feed_list_columns() {
		return array(
			'feedName'		=> __( 'Name', 'gravityformscampaignmonitor' )
		);
	}
    
	public function ensure_upgrade(){

		if ( get_option( 'gf_crm_upgrade' ) ){
			return false;
		}

		$feeds = $this->get_feeds();
		if ( empty( $feeds ) ){

			//Force Add-On framework upgrade
			$this->upgrade( '2.0' );
		}

		update_option( 'gf_crm_upgrade', 1 );
	}

	public function process_feed( $feed, $entry, $form ) {

		if ( ! $this->is_valid_key() ) {
			return;
		}

		$this->export_feed( $entry, $form, $feed );

	}

	public function export_feed( $entry, $form, $feed ) {

		$email       = $entry[ $feed['meta']['listFields_email'] ];
		$name        = '';
		if ( ! empty( $feed['meta']['listFields_fullname'] ) ) {
			$name = $this->get_name( $entry, $feed['meta']['listFields_fullname'] );
		}

		$merge_vars = array();
		$field_maps = $this->get_field_map_fields( $feed, 'listFields' );
		foreach ( $field_maps as $var_key => $field_id ) {
			$field = RGFormsModel::get_field( $form, $field_id );
			if ( GFCommon::is_product_field( $field['type'] ) && rgar( $field, 'enablePrice' ) ) {
				$ary          = explode( '|', $entry[ $field_id ] );
				$product_name = count( $ary ) > 0 ? $ary[0] : '';
				$merge_vars[] = array( 'Key' => $var_key, 'Value' => $product_name );
			} else if ( RGFormsModel::get_input_type( $field ) == 'checkbox' ) {
				foreach ( $field['inputs'] as $input ) {
					$index = (string) $input['id'];
					$merge_vars[] = array(
						'Key'   => $var_key,
						'Value' => apply_filters( 'gform_crm_field_value', rgar( $entry, $index ), $form['id'], $field_id, $entry )
					);
				}
			} else if ( ! in_array( $var_key, array( 'email', 'fullname' ) ) ) {
				$merge_vars[] = array(
					'Key'   => $var_key,
					'Value' => apply_filters( 'gform_crm_field_value', rgar( $entry, $field_id ), $form['id'], $field_id, $entry )
				);
			}
		}

		$override_custom_fields = apply_filters( 'gform_crm_override_blank_custom_fields', false, $entry, $form, $feed );
		if ( ! $override_custom_fields ){
			$merge_vars = $this->remove_blank_custom_fields( $merge_vars );
		}

        
            
        $settings = $this->get_plugin_settings();
        $crm_type  = $settings['gf_crm_type'];
        
        
        if($crm_type == 'vTiger') { //vtiger Method
            /*/vTiger Method
            $lead = array(
                'EmailAddress' => $email,
                'lastname'     => $name,
                'CustomFields' => $merge_vars
            );
            //$this->include_api();

            $record = $client_crm->doCreate('Leads', $lead￼);
    ￼
            if( $record ) {
                $recordid = $client_crm->getRecordId($record['id']); 
                $this->log_debug( __('Added Lead ID', 'gravityformscrm' ).' '.$recordid );
            }
            // end vtiger Method    */
        
        } elseif($crm_type == 'SugarCRM') {
            /*/SugarCRM Method
              $login_result = call("login", $login_parameters, $url);

                /*
                echo "<pre>";
                print_r($login_result);
                echo "</pre>";
                */

                //get session id
                $session_id = $login_result->id;

                //create account -------------------------------------     $set_entry_parameters = array(
                     //session id
                     "session" => $session_id,

                     //The name of the module from which to retrieve records.
                     "module_name" => "Accounts",

                     //Record attributes
                     "name_value_list" => array(
                          //to update a record, you will nee to pass in a record id as commented below
                          //array("name" => "id", "value" => "9b170af9-3080-e22b-fbc1-4fea74def88f"),
                          array("name" => "name", "value" => "Test Account"),
                     ),
                );

                $set_entry_result = call("set_entry", $set_entry_parameters, $url);

                echo "<pre>";
                print_r($set_entry_result);
                echo "</pre>";
            // end SugarCRM Method 
            
            */
        }

	}

	private static function remove_blank_custom_fields( $merge_vars ){
		$i=0;
		$count = count( $merge_vars );

		for ( $i = 0; $i < $count; $i++ ){
			if( rgblank( $merge_vars[$i]['Value'] ) ){
				unset( $merge_vars[$i] );
			}
		}
		//resort the array because items could have been removed, this will give an error from CRM if the keys are not in numeric sequence
		sort( $merge_vars );
		return $merge_vars;
	}

	private function get_name( $entry, $field_id ) {

		//If field is simple (one input), simply return full content
		$name = rgar( $entry, $field_id );
		if ( ! empty( $name ) ) {
			return $name;
		}

		//Complex field (multiple inputs). Join all pieces and create name
		$prefix = trim( rgar( $entry, $field_id . '.2' ) );
		$first  = trim( rgar( $entry, $field_id . '.3' ) );
		$last   = trim( rgar( $entry, $field_id . '.6' ) );
		$suffix = trim( rgar( $entry, $field_id . '.8' ) );

		$name = $prefix;
		$name .= ! empty( $name ) && ! empty( $first ) ? " $first" : $first;
		$name .= ! empty( $name ) && ! empty( $last ) ? " $last" : $last;
		$name .= ! empty( $name ) && ! empty( $suffix ) ? " $suffix" : $suffix;

		return $name;
	}

    private function is_valid_key(){
        $result_api = $this->login_api_crm();;
        return $result_api;
    }
    
    private function login_api_crm(){
    
    $settings = $this->get_plugin_settings();
    $crm_type  = $settings['gf_crm_type'];
    $url  = $settings['gf_crm_url'];
    $username = $settings['gf_crm_username'];
    $password = $settings['gf_crm_password'];
        
    if($crm_type == 'vTiger') { //vtiger Method
        include_once('includes/WSClient.php');

        $client_crm = new Vtiger_WSClient( $url );

        $login = $client_crm->doLogin($username , $password );

        if(!$login) {  $login_result = false; } else { $login_result = $login; }
    } elseif($crm_type == 'SugarCRM') {
        $url = $url.'/service/v4_1/rest.php';

        //login ------------------------------     
        $login_parameters = array(
             "user_auth" => array(
                  "user_name" => $username,
                  "password" => md5($password),
                  "version" => "1"
             ),
             "application_name" => "RestTest",
             "name_value_list" => array(),
        );

        $login_result = $this->call_sugarcrm("login", $login_parameters, $url);
    }
        

    return $login_result;
    }
    
    //function to make cURL request
    private function call_sugarcrm($method, $parameters, $url)
    {
        ob_start();
        $curl_request = curl_init();

        curl_setopt($curl_request, CURLOPT_URL, $url);
        curl_setopt($curl_request, CURLOPT_POST, 1);
        curl_setopt($curl_request, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($curl_request, CURLOPT_HEADER, 1);
        curl_setopt($curl_request, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_request, CURLOPT_FOLLOWLOCATION, 0);

        $jsonEncodedData = json_encode($parameters);

        $post = array(
             "method" => $method,
             "input_type" => "JSON",
             "response_type" => "JSON",
             "rest_data" => $jsonEncodedData
        );

        curl_setopt($curl_request, CURLOPT_POSTFIELDS, $post);
        $result = curl_exec($curl_request);
        curl_close($curl_request);

        $result = explode("\r\n\r\n", $result, 2);
        $response = json_decode($result[1]);
        ob_end_flush();

        return $response;
    }
    
}