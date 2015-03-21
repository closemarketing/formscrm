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
        //loading translations
            load_plugin_textdomain('gravityforms-crm', FALSE, '/gravityforms-crm/languages' );

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
                                                    ),
                                                    array(
                                                        'label' => 'Odoo',
                                                        'name'  => 'odoo'
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
						'feedback_callback' => $this->is_valid_key()
					),
					array(
						'name'              => 'gf_crm_odoo_db',
						'label'             => __( 'Odoo DB Name', 'gravityformscrm' ),
						'type'              => 'text',
						'class'             => 'medium',
					)
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
		if ( $this->is_valid_key() == false ) {
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
        $url  = $settings['gf_crm_url'];
        
        if($crm_type == 'vTiger') { //vtiger Method
            //Get fields from module
            $login_result = $this->login_api_crm();   
    
            $webservice = $url . '/webservice.php';
            $operation = '?operation=describe&sessionName='.$login_result.'&elementType=Leads';

            $result = $this->call_vtiger_get($webservice.$operation);
            $result = json_decode($result);
            $result = get_object_vars($result);
            $result = get_object_vars($result['result']);
            $i=0;
            $custom_fields = array();
            foreach ($result['fields'] as $arrayob) {
                $field = get_object_vars($arrayob);

                
                if($field['mandatory']==1) { 
                    $custom_fields[$i] = array(
                        'label' => $field['label'],
                        'name' => $field['name'],
                        'required' => true,
                        );
                } else {
                    $custom_fields[$i] = array(
                        'label' => $field['label'],
                        'name' => $field['name']
                        );
                }
                $i++;
            }
            
        } elseif($crm_type == 'SugarCRM') {
        
            //get session id
            $login_result = $this->login_api_crm();


            $session_id = $login_result->id;
            $url = $url.'/service/v4_1/rest.php';

            //retrieve fields --------------------------------     
                $get_module_fields_parameters = array(
                 'session' => $session_id,
                 'module_name' => 'Leads',
                );

            $get_module_fields_result = $this->call_sugarcrm("get_module_fields", $get_module_fields_parameters, $url);
            $get_module_fields_result = $get_module_fields_result->module_fields;
            $get_module_fields_result = get_object_vars($get_module_fields_result);

                        echo "<pre>";
            //print_r($get_module_fields_result);
            echo "</pre>";
            $i=0;
            $custom_fields = array();
            foreach ($get_module_fields_result as $arrayob) {
                $field = get_object_vars($arrayob);

                if($field['name']=='id') {
                } elseif($field['required']==1) { 
                    $custom_fields[$i] = array(
                        'label' => $field['label'],
                        'name' => $field['name'],
                        'required' => true,
                        );
                } else {
                    $custom_fields[$i] = array(
                        'label' => $field['label'],
                        'name' => $field['name']
                        );
                }
                $i++;
            } //from SugarCRM

        } elseif($crm_type == 'Odoo') {
        

        } //Odoo method
        
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

		//$email       = $entry[ $feed['meta']['listFields_email'] ];
		//$name        = '';
		if ( ! empty( $feed['meta']['listFields_first_name'] ) ) {
			$name = $this->get_name( $entry, $feed['meta']['listFields_first_name'] );
		}

		$merge_vars = array();
		$field_maps = $this->get_field_map_fields( $feed, 'listFields' );
        
		foreach ( $field_maps as $var_key => $field_id ) {
			$field = RGFormsModel::get_field( $form, $field_id );
			if ( GFCommon::is_product_field( $field['type'] ) && rgar( $field, 'enablePrice' ) ) {
				$ary          = explode( '|', $entry[ $field_id ] );
				$product_name = count( $ary ) > 0 ? $ary[0] : '';
				$merge_vars[] = array( 'name' => $var_key, 'value' => $product_name );
			} else if ( RGFormsModel::get_input_type( $field ) == 'checkbox' ) {
				foreach ( $field['inputs'] as $input ) {
					$index = (string) $input['id'];
					$merge_vars[] = array(
						'name'   => $var_key,
						'value' => apply_filters( 'gform_crm_field_value', rgar( $entry, $index ), $form['id'], $field_id, $entry )
					);
				}
			} else  {
				$merge_vars[] = array(
					'name'   => $var_key,
					'value' => apply_filters( 'gform_crm_field_value', rgar( $entry, $field_id ), $form['id'], $field_id, $entry )
				);
			}
		}

		$override_custom_fields = apply_filters( 'gform_crm_override_blank_custom_fields', false, $entry, $form, $feed );
		if ( ! $override_custom_fields ){
			$merge_vars = $this->remove_blank_custom_fields( $merge_vars );
		}

        
            
        $settings = $this->get_plugin_settings();
        $crm_type  = $settings['gf_crm_type'];
        $url  = $settings['gf_crm_url'];
        
        $login_result = $this->login_api_crm();
        
        
        if($crm_type == 'vTiger') { //vtiger Method
            //vTiger Method            
            $webservice = $url . '/webservice.php';
            
            $jsondata = $this->convert_custom_fields( $merge_vars );

            $params = array(
                'operation'     => 'create',
                'sessionName'   => $login_result,
                'element'       => $jsondata,
                'elementType'   => 'Leads'
                );

            $result = $this->call_vtiger_post($webservice, $params);
            $json = json_decode($result, true);

            var_dump($json);

            // end vtiger Method   
        
        } elseif($crm_type == 'SugarCRM') {
            // SugarCRM Method
            $webservice = $url.'/service/v4_1/rest.php';
            $session_id = $login_result->id;
            
            $set_entry_parameters = array(
                 "session" => $session_id,
                 "module_name" => "Leads",
                 "name_value_list" => $merge_vars
            );
            
            $set_entry_result = $this->call_sugarcrm("set_entry", $set_entry_parameters, $webservice);

        } // end SugarCRM Method 

	}
    
    /* Converts Array to vtiger webservice specification */
    private static function convert_custom_fields( $merge_vars ){
        $i=0;
		$count = count( $merge_vars );
        $jsontext = '{';

		for ( $i = 0; $i < $count; $i++ ){
            $jsontext .= '"'.$merge_vars[$i]['name'].'":"'.$merge_vars[$i]['value'].'"';
            if($i<$count-1) {$jsontext .=', '; } 
            //'{"lastname":"#", "email":"david@closemarketing.es","industry":"bla"}'
        }
        $jsontext .= '}';
        
        return $jsontext;
    }

	private static function remove_blank_custom_fields( $merge_vars ){
		$i=0;

		$count = count( $merge_vars );

		for ( $i = 0; $i < $count; $i++ ){
            if( rgblank( $merge_vars[$i]['value'] ) ){
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
        $result_api = $this->login_api_crm();
   
        return $result_api;
    }
    
    private function login_api_crm(){
    
    $settings = $this->get_plugin_settings();
    $crm_type  = $settings['gf_crm_type'];
    $url  = $settings['gf_crm_url'];
    $username = $settings['gf_crm_username'];
    $password = $settings['gf_crm_password'];
    $dbname = $settings['gf_crm_odoo_db'];
        
    if($crm_type == 'vTiger') { //vtiger Method
        $webservice = $url . '/webservice.php';
        $operation = '?operation=getchallenge&username='.$username;
        $result = $this->call_vtiger_get($webservice.$operation);
        $json = json_decode($result, true);
        $challengeToken = $json['result']['token'];

        // Get MD5 checksum of the concatenation of challenge token and user own Access Key
        $accessKey = md5($challengeToken.$password);

        // Define login operation parameters
        $operation2 = array(
            "operation" => "login",
            "username" => $username,
            "accessKey" => $accessKey
            );

        // Execute and get result on server response for login operation    
        $result = $this->call_vtiger_post($webservice, $operation2);
        // Decode JSON response
        
        $json = json_decode($result, true);
       
        if( $result == false ){
            $login_result = false;
        } else {
            $login_result = $json['result']['sessionName'];
        }
        
    } elseif($crm_type == 'SugarCRM') { //sugarcrm method
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
        if( $login_result == 1 )
            $login_result = false;
        
    } elseif($crm_type == 'Odoo') { //Odoo Method

        $server_url = $url .'/xmlrpc/';
        
        if(isset($_COOKIE["user_id"]) == true)  {
           if($_COOKIE["user_id"]>0) {
           return $_COOKIE["user_id"];
           }
        }

        $sock = new xmlrpc_client($server_url.'common');
        $msg = new xmlrpcmsg('login');
        $msg->addParam(new xmlrpcval($dbname, "string"));
        $msg->addParam(new xmlrpcval($username, "string"));
        $msg->addParam(new xmlrpcval($password, "string"));
        $resp =  $sock->send($msg);
        $val = $resp->value();
        $id = $val->scalarval();
        setcookie("user_id",$id,time()+3600);
        if($id > 0) {
            $login_result = $id;
        }else{
           $login_result = false;
        }

    } //Odoo Method
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
        
        if($result[0]=="") 
            $response = false;
        else      
            $response = json_decode($result[1]);

        ob_end_flush();

        return $response;
    }
    // cURL GET function for vTiger
    private function call_vtiger_get($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL,$url);
        $data=curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    // cURL POST function for vTiger
    private function call_vtiger_post($url,$params) {
       $postData = '';
       //create name value pairs seperated by &
       foreach($params as $k => $v) 
       { 
          $postData .= $k . '='.$v.'&'; 
       }
       rtrim($postData, '&');

       $ch = curl_init();  
       curl_setopt($ch,CURLOPT_URL,$url);
       curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
       curl_setopt($ch,CURLOPT_HEADER, false); 
       curl_setopt($ch, CURLOPT_POST, count($postData));
       curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);    
       $output=curl_exec($ch);
       curl_close($ch);
        
       return $output;
    }
    
}