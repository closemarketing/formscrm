<?php

GFForms::include_feed_addon_framework();

class GFCRM extends GFFeedAddOn {

	protected $_version = GF_CRM_VERSION;
	protected $_min_gravityforms_version = '1.9.0';
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
				'description' => __( 'Use this connector with CRM software. Use Gravity Forms to collect customer information and automatically add them to your CRM Leads.', 'gravityformscrm' ),
				'fields'      => array(
					array(
						'name'              => 'gf_crm_type',
						'label'             => __( 'CRM Type', 'gravityformscrm' ),
						'type'              => 'select',
						'class'             => 'medium',
                        'onchange'          => 'SelectChanged()',
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
                                                        'label' => 'SugarCRM7',
                                                        'name'  => 'sugarcrm7'
                                                    ),
                                                    array(
                                                        'label' => 'SuiteCRM',
                                                        'name'  => 'suitecrm'
                                                    ),
                                                    array(
                                                        'label' => 'VTE CRM',
                                                        'name'  => 'vtecrm'
                                                    ),
                                                    array(
                                                        'label' => 'Odoo 8',
                                                        'name'  => 'odoo8'
                                                    ),
                                                    array(
                                                        'label' => 'Microsoft Dynamics CRM',
                                                        'name'  => 'msdynamics'
                                                    ),
                                                    array(
                                                        'label' => 'ESPO CRM',
                                                        'name'  => 'espocrm'
                                                    ),
                                                    array(
                                                        'label' => 'Zoho CRM',
                                                        'name'  => 'zohocrm'
                                                    )
                                                )
					),
					array(
						'name'              => 'gf_crm_url',
						'label'             => __( 'CRM URL', 'gravityformscrm' ),
						'type'              => 'text',
						'class'             => 'medium',
                        'tooltip'       => __( 'Use the URL with http and the ending slash /.', 'gravityformscrm' ),
                        'tooltip_class'     => 'tooltipclass',
                        'dependency' => array( 'field' => 'gf_crm_type', 'values' => array( 'SugarCRM','SugarCRM7', 'Odoo 8','Microsoft Dynamics CRM','ESPO CRM','SuiteCRM','vTiger','VTE CRM') )
					),
					array(
						'name'              => 'gf_crm_username',
						'label'             => __( 'Username', 'gravityformscrm' ),
						'type'              => 'text',
						'class'             => 'medium',
					),
					array(
						'name'  => 'gf_crm_password',
						'label' => __( 'Password', 'gravityformscrm' ),
						'type'  => 'api_key',
						'class' => 'medium',
                        'tooltip'       => __( 'Use the password of the actual user.', 'gravityformscrm' ),
                        'tooltip_class'     => 'tooltipclass',
                        'dependency' => array( 'field' => 'gf_crm_type', 'values' => array( 'SugarCRM','SugarCRM7', 'Odoo 8','Microsoft Dynamics CRM','ESPO CRM','SuiteCRM' ) ),
						'feedback_callback' => $this->is_valid_key()
					),
					array(
						'name'  => 'gf_crm_apipassword',
						'label' => __( 'API Password for User', 'gravityformscrm' ),
						'type'  => 'api_key',
						'class' => 'medium',
						'feedback_callback' => $this->login_api_crm(),
                        'tooltip'       => __( 'Find the API Password in the profile of the user in CRM.', 'gravityformscrm' ),
                        'tooltip_class'     => 'tooltipclass',
                        'dependency' => array( 'field' => 'gf_crm_type', 'values' => array( 'vTiger','VTE CRM','Zoho CRM' ) ),
						'feedback_callback' => $this->is_valid_key()
					),
					array(
						'name'              => 'gf_crm_odoodb',
						'label'             => __( 'Odoo DB Name', 'gravityformscrm' ),
						'type'              => 'text',
						'class'             => 'medium',
                        'dependency' => array( 'field' => 'gf_crm_type', 'values' => array( 'Odoo 8' ) ),
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

		$caption = '<small>' . sprintf( __( "Find a Password or API key depending of CRM.", 'gravityformscrm' ) ) . '</small>';

		if ( $echo ) {
			echo $api_key_field . '</br>' . $caption;
		}

		return $api_key_field . '</br>' . $caption;
	}


	//-------- Form Settings ---------
	public function feed_edit_page( $form, $feed_id ) {

		// ensures valid credentials were entered in the settings page
		if ( $this->login_api_crm() == false ) {
			?>
			<div><?php echo sprintf( __( 'We are unable to login to CRM with the provided API key or URL is incorrect (it must finish with slash / ). Please make sure you have entered a valid API key in the %sSettings Page%s', 'gravityformscrm' ),
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

		$custom_fields = $this->get_custom_fields_crm( );

		return $custom_fields;

	}

	public function get_custom_fields_crm( ) {

        $settings = $this->get_plugin_settings();
        $crm_type  = $settings['gf_crm_type'];
        if (isset($settings['gf_crm_url']) ) $url = $settings['gf_crm_url']; else $url = "";
        if(substr($url, -1) !='/') $url.='/'; //adds slash to url
        if (isset($settings['gf_crm_username']) ) $username = $settings['gf_crm_username']; else $username = "";
        if (isset($settings['gf_crm_apipassword']) ) $apipassword = $settings['gf_crm_apipassword']; else $apipassword ="";
        if (isset($settings['gf_crm_odoodb']) ) $dbname = $settings['gf_crm_odoodb']; else $dbname ="";
        if (isset($settings['gf_crm_password']) ) $password = $settings['gf_crm_password']; else $password="";

        if($crm_type == 'vTiger') { //vtiger Method
            $custom_fields = $this->vtiger_listfields($username, $apipassword, $url, 'Leads');

        } elseif($crm_type == 'SugarCRM'||$crm_type == 'SuiteCRM') {
            $custom_fields = $this->sugarcrm_listfields($username, $password, $url,'Leads');

        } elseif($crm_type == 'SugarCRM7') {
            $custom_fields = $this->sugarcrm_listfields7($username, $password, $url,'Leads');

        } elseif($crm_type == 'Odoo 8') { //Odoo method
            $custom_fields = $this->odoo_listfields($username, $password, $dbname, $url,"lead");

        } elseif($crm_type == 'Microsoft Dynamics CRM') { //MS Dynamics
            $custom_fields = $this->msdyn_listfields($username, $password, $url,"lead");

        } elseif($crm_type == 'VTE CRM') {
             $custom_fields = $this->vte_listfields($username, $apipassword, $url,'Leads');

         } elseif($crm_type == 'ESPO CRM') {
             $custom_fields = $this->espo_listfields($username, $password, $url,'Lead');

         } elseif($crm_type == 'Zoho CRM') {
             $custom_fields = $this->zoho_listfields($username, $apipassword, 'Leads');

        } // From if CRM

        $this->debugcrm($custom_fields);

		return $custom_fields;
	}


	public function feed_list_columns() {
		return array(
			'feedName'		=> __( 'Name', 'gravityformscrm' )
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
        if (isset($settings['gf_crm_url']) ) $url = $settings['gf_crm_url']; else $url = "";
        if(substr($url, -1) !='/') $url.='/'; //adds slash to url
        if (isset($settings['gf_crm_username']) ) $username = $settings['gf_crm_username']; else $username = "";
        if (isset($settings['gf_crm_apipassword']) ) $apipassword = $settings['gf_crm_apipassword']; else $apipassword ="";
        if (isset($settings['gf_crm_odoodb']) ) $dbname = $settings['gf_crm_odoodb']; else $dbname ="";
        if (isset($settings['gf_crm_password']) ) $password = $settings['gf_crm_password']; else $password="";

        if($crm_type == 'vTiger') { //vtiger Method
            $id = $this->vtiger_create_lead($username, $apipassword, $url, 'Leads', $merge_vars);

        } elseif($crm_type == 'SugarCRM'||$crm_type == 'SuiteCRM') {
            $id = $this->sugarcrm_create_lead($username, $password, $url, 'Leads', $merge_vars);

        } elseif($crm_type == 'SugarCRM7') {
            $id = $this->sugarcrm_create_lead7($username, $password, $url, 'Leads', $merge_vars);

        } elseif($crm_type == 'Odoo 8') {
            $id = $this->odoo8_create_lead($username, $password, $dbname, $url, 'lead', $merge_vars);

        } elseif($crm_type == 'Microsoft Dynamics CRM') { //MS Dynamics Method
            $id = $this->msdyn_create_lead($username, $password, $url, "lead", $merge_vars);

        } elseif($crm_type == 'VTE CRM') {
            $id = $this->vte_create_lead($username, $apipassword, $url, 'Leads', $merge_vars);

        } elseif($crm_type == 'ESPO CRM') {
            $id = $this->espo_createlead($username, $password, $url, 'Lead', $merge_vars);

        } elseif($crm_type == 'Zoho CRM') {
            $id = $this->zoho_createlead($username, $apipassword, 'Leads', $merge_vars);
        } // From CRM IF

        //Sends email if it does not create a lead
        //if ($id == false)
        //    $this->send_emailerrorlead($crm_type);
        $this->debugcrm($id);
}

    private function send_emailerrorlead($crm_type) {
        // Sends email if it does not create a lead

        $subject = __('We could not create the lead in ','gravityformscrm').$crm_type;
        $message = __('<p>There was a problem creating the lead in the CRM.</p><p>Try to find where it was the problem in the Wordpress Settings.</p><br/><p><strong>Gravity Forms CRM</strong>','gravityformscrm');

        wp_mail( get_bloginfo('admin_email'), $subject, $message);
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
		if (isset($settings['gf_crm_url']) ) $url = $settings['gf_crm_url']; else $url = "";
    if(substr($url, -1) !='/') $url.='/'; //adds slash to url

    if (isset($settings['gf_crm_username']) ) $username = $settings['gf_crm_username']; else $username = "";
    if (isset($settings['gf_crm_apipassword']) ) $apipassword = $settings['gf_crm_apipassword']; else $apipassword ="";
    if (isset($settings['gf_crm_odoodb']) ) $dbname = $settings['gf_crm_odoodb']; else $dbname ="";
    if (isset($settings['gf_crm_password']) ) $password = $settings['gf_crm_password']; else $password="";

    if($crm_type == 'vTiger') { //vtiger Method
        $login_result = $this->vtiger_login($username, $apipassword, $url);

    } elseif($crm_type == 'SugarCRM'||$crm_type == 'SuiteCRM') { //sugarcrm method
        $login_result = $this->sugarcrm_login($username, $password, $url, 'Leads');

    } elseif($crm_type == 'SugarCRM7') { //sugarcrm7 method
        $login_result = $this->sugarcrm_login7($username, $password, $url, 'Leads');

    } elseif($crm_type == 'Odoo 8') { //Odoo Method
        $login_result = $this->odoo8_login($username, $password, $dbname, $url);

    } elseif($crm_type == 'Microsoft Dynamics CRM') { //MS Dynamics Method
        $login_result = $this-> msdyn_login($username, $password, $url,"lead");

    } elseif($crm_type == 'VTE CRM') {
        $login_result = $this-> vtiger_login($username, $apipassword, $url, 'Leads');

    } elseif($crm_type == 'ESPO CRM') {
        $login_result = $this-> espo_login($username, $password, $url);

    } elseif($crm_type == 'Zoho CRM') {
        $login_result = $this-> zoho_login($username, $apipassword, 'Leads');

    } //OF CRM

    $this->debugcrm($login_result);

    if (!isset($login_result) )
        $login_result="";
    return $login_result;
    }

//////////// Helpers Functions for CRMs ////////////


    /////// SUGAR CRM ///////

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

    private function sugarcrm_login($username, $password, $url) {

        $url = $url.'service/v4_1/rest.php';

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

        $login_token = $login_result->id;

  }

    private function sugarcrm_listfields($username, $password, $url, $module) {

        //get session id
        $login_result = $this->sugarcrm_login($username, $password, $url);

        $url = $url.'service/v4_1/rest.php';

        //retrieve fields --------------------------------
            $get_module_fields_parameters = array(
             'session' => $login_result,
             'module_name' => $module,
            );

        $get_fields = $this->call_sugarcrm("get_module_fields", $get_module_fields_parameters, $url);

        $get_fields = $get_fields->module_fields;

        $i=0;
        $custom_fields = array();
        foreach ($get_fields as $arrayob) {
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
        } //from foreach


        return $custom_fields;
    }

    private function sugarcrm_create_lead($username, $password, $url, $module, $merge_vars) {

        // SugarCRM Method
        $login_result = $this->sugarcrm_login($username, $password, $url);

        $webservice = $url.'service/v4_1/rest.php';

        $set_entry_parameters = array(
             "session" => $login_result,
             "module_name" => $module,
             "name_value_list" => $merge_vars
        );

        $set_entry_result = $this->call_sugarcrm("set_entry", $set_entry_parameters, $webservice);

        return $set_entry_result->id;

    }

    ////////////////////////////////


    ////////// VTIGER CRM //////////

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

    private function vtiger_login($username, $apipassword, $url) {
        $webservice = $url . '/webservice.php';
        $operation = '?operation=getchallenge&username='.$username;
        $result = $this->call_vtiger_get($webservice.$operation);
        $json = json_decode($result, true);
        $challengeToken = $json['result']['token'];

        // Get MD5 checksum of the concatenation of challenge token and user own Access Key
        $accessKey = md5($challengeToken.$apipassword);

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

        if( $json['success'] == false ){
            return false;
        } else {
            return $json['result']['sessionName'];
        }

    }

    function vtiger_listfields($username, $password, $url, $module){

        //Get fields from module
        $login_result = $this->vtiger_login($username, $password, $url);

        $webservice = $url . '/webservice.php';
        $operation = '?operation=describe&sessionName='.$login_result.'&elementType='.$module;

        $result = $this->call_vtiger_get($webservice.$operation);
        $result = json_decode($result);
        $result = get_object_vars($result);

        if( isset($result['error']) ) { //Handle vTiger error
            echo '<div class="error">';
            echo '<p><strong>vTiger ERROR '.$result['error']->code.': </strong> '.$result['error']->message.'</p>';
            echo '</div>';
            return;
        }
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
        return $custom_fields;

    }

    private function vtiger_create_lead($username, $password, $url, $module, $merge_vars) {
        $login_result = $this->vtiger_login($username, $password, $url);

        //vTiger Method
        $webservice = $url . '/webservice.php';

        $jsondata = $this->convert_custom_fields( $merge_vars );

        $params = array(
            'operation'     => 'create',
            'sessionName'   => $login_result,
            'element'       => $jsondata,
            'elementType'   => $module
            );

        $result = $this->call_vtiger_post($webservice, $params);
        $json = json_decode($result, true);

    }

    /////// ODOO CRM ///////
    //Helpers functions
        private function odoo8_login($username, $password, $dbname, $url) {
            //Load Library XMLRPC
            require_once('lib/ripcord.php');

            //Manage Errors from Library
            try {
            $common = ripcord::client($url.'xmlrpc/2/common');
            } catch (Exception $e) {
                echo '<div id="message" class="error below-h2">
                <p><strong>'.__('Error','gravityformscrm').': '.$e->getMessage().'</strong></p></div>';
                return false;
            }

            try {
            $uid = $common->authenticate($dbname, $username, $password, array());
            } catch (Exception $e) {
                echo '<div id="message" class="error below-h2">
                <p><strong>'.__('Error','gravityformscrm').': '.$e->getMessage().'</strong></p></div>';
                return false;
            }

            if (isset($uid) )
                return $uid;
            else
                return false;
        }
        // from login Odoo
        //Converts XML Odoo in array for Gravity Forms Custom Fields
        function convert_XML_odoo8_customfields($xml_odoo){
            $p = xml_parser_create();
            xml_parse_into_struct($p, $xml_odoo, $vals, $index);
            xml_parser_free($p);

            $custom_fields = array();
            $i =0;

            foreach($vals as $field)
            {
                if( $field["tag"] == 'NAME' ) {
                    if ( $field["value"] != 'type' && $field["value"] != 'string' && $field["value"] != 'help')
                    $custom_fields[$i] = array(
                            'label' => $field['value'],
                            'name' => $field['value']
                            );

                }
                $i++;
            } //del foreach
            return $custom_fields;
        } //function

        //// Main Functions
        private function odoo8_listfields($username, $password, $dbname, $url, $module) {
            if(substr($url, -1) !='/') $url.='/'; //adds slash to url
            $uid = $this->odoo8_login($username, $password, $dbname, $url);

            $models = ripcord::client($url.'xmlrpc/2/object');
            $models->execute_kw($dbname, $uid, $password,'crm.lead', 'fields_get', array(), array('attributes' => array('string', 'help', 'type')));

            $custom_fields = $this->convert_XML_odoo8_customfields( $models->_response );

            // Return an array of fields
            return $custom_fields;
        }

        private function odoo8_create_lead($username, $password, $dbname, $url, $module, $merge_vars) {

            //Converts to Array
            $i =0;
            $arraymerge = array();
            foreach($merge_vars as $mergefield) {
                $arraymerge = array_merge($arraymerge,array( $mergefield['name'] => $mergefield['value'] ) );
                $i++;
            }

            if(substr($url, -1) !='/') $url.='/'; //adds slash to url
            $uid = $this->odoo8_login($username, $password, $dbname, $url);

            $models = ripcord::client($url.'xmlrpc/2/object');
            $id = $models->execute_kw($dbname, $uid, $password, 'crm.lead', 'create',
            array($arraymerge));

            return $id;
        }

    ////////////////////////////////
    /////// MS DYNAMICS CRM ///////

    private function msdyn_apiurl($url) {
				$pos = strpos($url, 'api');
				if ($pos == false) {
					$pos = strpos($url, '.');
					$url = substr_replace($url, '.api', $pos, 0);
				}
        $url = $url.'XRMServices/2011/Organization.svc';

				return $url;
    }

    public function msdyn_login($username, $password, $url) {
        include_once "lib/dynamics/LiveIDManager.php";
        include_once "lib/dynamics/EntityUtils.php";

        $url = $this->msdyn_apiurl($url);

        $this->debugcrm($url);

        //Return true or false for logged in
        $liveIDManager = new LiveIDManager();

    $securityData = $liveIDManager->authenticateWithLiveID($url, $username, $password);

    if($securityData!=null && isset($securityData)){
        //echo ("\nKey Identifier:" . $securityData->getKeyIdentifier());
        //echo ("\nSecurity Token 1:" . $securityData->getSecurityToken0());
        //echo ("\nSecurity Token 2:" . $securityData->getSecurityToken1());
        //echo "User Authentication : Succcess.<br>";
        return true;
    }else{
        echo '<div id="message" class="error below-h2">
                <p><strong>'.__('Unable to authenticate LiveId.','gravityformscrm').': </strong></p></div>';
        return false;
    }
    return false;
    }

    function msdyn_listfields($username, $password, $url, $module){
        include_once "lib/dynamics/LiveIDManager.php";
        include_once "lib/dynamics/EntityUtils.php";

        $url = $this->msdyn_apiurl($url);

       //Return true or false for logged in
        $liveIDManager = new LiveIDManager();

    $securityData = $liveIDManager->authenticateWithLiveID($url, $username, $password);

    if($securityData!=null && isset($securityData)){
    }else{
        echo '<div id="message" class="error below-h2">
                <p><strong>'.__('Unable to authenticate LiveId.','gravityformscrm').': </strong></p></div>';
        return;
    }

            $domainname = substr($url,8,-1);

            $pos = strpos($domainname, "/");

            $domainname = substr($domainname,0,$pos);

            $retriveRequest = EntityUtils::getCRMSoapHeader($url, $securityData) .
            '
                  <s:Body>
                        <Execute xmlns="http://schemas.microsoft.com/xrm/2011/Contracts/Services">
                                <request i:type="b:RetrieveEntityRequest" xmlns:b="http://schemas.microsoft.com/xrm/2011/Contracts" xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
                                        <b:Parameters xmlns:c="http://schemas.datacontract.org/2004/07/System.Collections.Generic">
                                                <b:KeyValuePairOfstringanyType>
                                                        <c:key>EntityFilters</c:key>
                                                        <c:value i:type="b:EntityFilters" xmlns:b="http://schemas.microsoft.com/xrm/2011/Metadata">Attributes</c:value>
                                                </b:KeyValuePairOfstringanyType>
                                                <b:KeyValuePairOfstringanyType>
                                                        <c:key>MetadataId</c:key>
                                                        <c:value i:type="d:guid" xmlns:d="http://schemas.microsoft.com/2003/10/Serialization/">00000000-0000-0000-0000-000000000000</c:value>
                                                </b:KeyValuePairOfstringanyType>
                                                <b:KeyValuePairOfstringanyType>
                                                        <c:key>RetrieveAsIfPublished</c:key>
                                                        <c:value i:type="d:boolean" xmlns:d="http://www.w3.org/2001/XMLSchema">true</c:value>
                                                </b:KeyValuePairOfstringanyType>
                                                <b:KeyValuePairOfstringanyType>
                                                        <c:key>LogicalName</c:key>
                                                        <c:value i:type="d:string" xmlns:d="http://www.w3.org/2001/XMLSchema">'.$module.'</c:value>
                                                </b:KeyValuePairOfstringanyType>
                                        </b:Parameters>
                                        <b:RequestId i:nil="true"/><b:RequestName>RetrieveEntity</b:RequestName>
                                </request>
                        </Execute>
                        </s:Body>
                </s:Envelope>
                ';
        $response =  LiveIDManager::GetSOAPResponse("/Organization.svc", $domainname, $url, $retriveRequest);

      $entityArray = array();
            if($response!=null && $response!=""){

                $responsedom = new DomDocument();
                $responsedom->loadXML($response);
                $entities = $responsedom->getElementsbyTagName("AttributeMetadata");

                $record = array();
                //$kvptypes = $entities[0]->getElementsbyTagName("KeyValuePairOfstringanyType");

                foreach($entities as $kvp){
                       if($kvp->getElementsbyTagName("DisplayName")->item(0)!=null&& $kvp->getElementsbyTagName("DisplayName")->item(0)->getElementsbyTagName("Label")->item(0)!=null)
                         $record['label']=$kvp->getElementsbyTagName("DisplayName")->item(0)->getElementsbyTagName("Label")->item(0)->textContent;
                         else
                            continue;
                          //$record['label']="";
                        $record['name']=$kvp->getElementsbyTagName("LogicalName")->item(0)->textContent;

                        $required =$kvp->getElementsbyTagName("RequiredLevel")->item(0)->getElementsbyTagName("Value")->item(0)->textContent;
                        if($required == 'Recommended' || $required == 'ApplicationRequired')
                            $record['required']= true;
                        else
                            $record['required']= false;

                        $entityArray[] = $record;
                }
            }

        return $entityArray;
        }

    function msdyn_create_lead($username, $password, $url, $module, $mergevars) {
        include_once "lib/dynamics/LiveIDManager.php";
        include_once "lib/dynamics/EntityUtils.php";

        $url = $this->msdyn_apiurl($url);
     //Return true or false for logged in
        $liveIDManager = new LiveIDManager();

    $securityData = $liveIDManager->authenticateWithLiveID($url, $username, $password);

    if($securityData!=null && isset($securityData)){
    }else{
        echo '<div id="message" class="error below-h2">
                <p><strong>'.__('Unable to authenticate LiveId.','gravityformscrm').': </strong></p></div>';
        return false;
    }

    $attributedata='';
    foreach($mergevars as $attribute){
        $attributedata=$attributedata.
        '<b:KeyValuePairOfstringanyType>
            <c:key>'.$attribute['name'].'</c:key>
            <c:value i:type="d:string" xmlns:d="http://www.w3.org/2001/XMLSchema">'.$attribute['value'].'</c:value>
        </b:KeyValuePairOfstringanyType>';
    }


      $domainname = substr($url,8,-1);
            $pos = strpos($domainname, "/");
            $domainname = substr($domainname,0,$pos);
            $entityCreateRequest = EntityUtils::getCreateCRMSoapHeader($url, $securityData).
            '
                  <s:Body>
                        <Create xmlns="http://schemas.microsoft.com/xrm/2011/Contracts/Services">
                        <entity xmlns:b="http://schemas.microsoft.com/xrm/2011/Contracts" xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
                            <b:Attributes xmlns:c="http://schemas.datacontract.org/2004/07/System.Collections.Generic">
                                '.$attributedata.'
                            </b:Attributes>
                            <b:EntityState i:nil="true"/>
                            <b:FormattedValues xmlns:c="http://schemas.datacontract.org/2004/07/System.Collections.Generic"/>
                            <b:Id>00000000-0000-0000-0000-000000000000</b:Id>
                            <b:LogicalName>'.$module.'</b:LogicalName>
                            <b:RelatedEntities xmlns:c="http://schemas.datacontract.org/2004/07/System.Collections.Generic"/>
                        </entity>
                        </Create>
                    </s:Body>
                </s:Envelope>
                ';

        $response =  LiveIDManager::GetSOAPResponse("/Organization.svc", $domainname, $url, $entityCreateRequest);

            $createResult ="";
            if($response!=null && $response!=""){
                preg_match('/<CreateResult>(.*)<\/CreateResult>/', $response, $matches);
                $createResult =  $matches[1];
            }

            return $createResult;
    }

    ////////////////////////////////


    /////// VTE CRM ///////

    private function vte_login($username, $password, $url) {

        require_once('vtwsclib/VTEWSClient.php');
        $client = new VTE_WSClient($url);

        $login = $client->doLogin($username,$password);

        return $login;
    }


    private function vte_listfields($username, $apipassword, $url, $module){
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

    private function vte_create_lead($username, $apipassword, $url, $module, $merge_vars) {
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
        }

        return $record;
    }

    ////////////////////////////////


    /////// SUGAR CRM 7///////
		private function call_sugarcrm7($method, $parameters, $url)
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

    private function sugarcrm_login7($username, $password, $url) {

        $url = $url.'service/v4_1/rest.php';

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

        $login_result = $this->call_sugarcrm7("login", $login_parameters, $url);

				if(isset($login_result->name)) {
					echo '<div id="message" class="error below-h2">
                <p><strong>'.$login_result->description.': </strong></p></div>';
					return false;
				} else {
        	$login_token = $login_result->id;
				}

        return $login_token;
    }

    private function sugarcrm_listfields7($username, $password, $url, $module) {

        //get session id
        $login_result = $this->sugarcrm_login7($username, $password, $url);

        $url = $url.'service/v4_1/rest.php';

        //retrieve fields --------------------------------
            $get_module_fields_parameters = array(
             'session' => $login_result,
             'module_name' => $module,
            );

    $get_fields = $this->call_sugarcrm7("get_module_fields", $get_module_fields_parameters, $url);
		$custom_fields = array();

		foreach($get_fields->module_fields as $field){
			if($field->label== 'ID')
				$custom_fields[]=array('label'=> $field->label, 'name' => $field->name);
			else
				$custom_fields[]=array('label'=> $field->label, 'name' => $field->name, 'required' => ($field->required));
		}
    return $custom_fields;
    }

    private function sugarcrm_create_lead7($username, $password, $url, $module, $merge_vars) {

        // SugarCRM Method
        $login_result = $this->sugarcrm_login7($username, $password, $url);

        $webservice = $url.'service/v4_1/rest.php';

        $set_entry_parameters = array(
             "session" => $login_result,
             "module_name" => $module,
             "name_value_list" => $merge_vars
        );

        $set_entry_result = $this->call_sugarcrm7("set_entry", $set_entry_parameters, $webservice);

        return $set_entry_result->id;

    }

    ////////////////////////////////

    /////// ESPO CRM ///////
		private function espo_login($username, $password, $url){
		  $url = $url.'api/v1/App/user';

		  $ch = curl_init($url);
		  curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=UTF-8'));
		  curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		  curl_setopt($ch, CURLOPT_USERPWD, $username.":".$password);
		  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		  $data = curl_exec($ch);
		  curl_close($ch);
		  $userinfo = json_decode($data);
		  if(isset($userinfo->user) && isset($userinfo->user->id)){
		   return true;
		  }
		  else
		    return false;
		}

		private function espo_listfields($username, $password, $url, $module) {
		 // lead fields
		 $leadlistfields = array(
		    array( 'name' => 'acceptanceStatus', 'label' => 'acceptanceStatus', 'required'=>false),
		    array( 'name' => 'accountName', 'label' => 'Account Name', 'required'=>false),
		    array( 'name' => 'address', 'label' => 'Address', 'required'=>false),
		    array( 'name' => 'addressCity', 'label' => 'City', 'required'=>false),
		    array( 'name' => 'addressCountry', 'label' => 'Country', 'required'=>false),
		    array( 'name' => 'addressPostalCode', 'label' => 'Postal Code', 'required'=>false),
		    array( 'name' => 'addressState', 'label' => 'State', 'required'=>false),
		    array( 'name' => 'addressStreet', 'label' => 'Street', 'required'=>false),
		    array( 'name' => 'assignedUser', 'label' => 'Assigned User', 'required'=>false),
		    array( 'name' => 'campaign', 'label' => 'Campaign', 'required'=>false),
		    array( 'name' => 'createdAccount', 'label' => 'Account', 'required'=>false),
		    array( 'name' => 'createdAt', 'label' => 'Created At', 'required'=>false),
		    array( 'name' => 'createdBy', 'label' => 'Created By', 'required'=>false),
		    array( 'name' => 'createdContact', 'label' => 'Contact', 'required'=>false),
		    array( 'name' => 'createdOpportunity', 'label' => 'Opportunity', 'required'=>false),
		    array( 'name' => 'description', 'label' => 'Description', 'required'=>false),
		    array( 'name' => 'doNotCall', 'label' => 'Do Not Call', 'required'=>false),
		    array( 'name' => 'emailAddress', 'label' => 'Email', 'required'=>false),
		    array( 'name' => 'firstName', 'label' => 'First Name', 'required'=>false),
		    array( 'name' => 'lastName', 'label' => 'Last Name', 'required'=>true),
		    array( 'name' => 'modifiedAt', 'label' => 'Modified At', 'required'=>false),
		    array( 'name' => 'modifiedBy', 'label' => 'Modified By', 'required'=>false),
		    array( 'name' => 'name', 'label' => 'Name', 'required'=>false),
		    array( 'name' => 'opportunityAmount', 'label' => 'Opportunity Amount', 'required'=>false),
		    array( 'name' => 'opportunityAmountConverted', 'label' => 'Opportunity Amount (converted)', 'required'=>false),
		    array( 'name' => 'opportunityAmountCurrency', 'label' => 'opportunityAmountCurrency', 'required'=>false),
		    array( 'name' => 'phoneNumber', 'label' => 'Phone', 'required'=>false),
		    array( 'name' => 'salutationName', 'label' => 'Salutation', 'required'=>false),
		    array( 'name' => 'source', 'label' => 'Source', 'required'=>false),
		    array( 'name' => 'status', 'label' => 'Status', 'required'=>false),
		    array( 'name' => 'targetList', 'label' => 'Target List', 'required'=>false),
		    array( 'name' => 'targetLists', 'label' => 'Target Lists', 'required'=>false),
		    array( 'name' => 'teams', 'label' => 'Teams', 'required'=>false),
		    array( 'name' => 'title', 'label' => 'Title', 'required'=>false),
		    array( 'name' => 'website', 'label' => 'Website', 'required'=>false)
		 );

		 if($module == "Lead")
		  return $leadlistfields;
		  else return "";
		}

		private function espo_createlead($username, $password, $url, $module, $merge_vars){
		  $url = $url.'api/v1/'.$module;

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
		  curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

		  //execute post
		  $result = curl_exec($ch);

		  //close connection
		  curl_close($ch);
		  $result= json_decode($result);

		  if(isset($result->id)){
		   return $result->id;
		  }
		  else
		    return 'lead alredy exists with same data';
		}
    ///////////////////////

		/////// ZOHO CRM ///////
    //cURL Function for Zoho CRM

	private function call_zoho_crm($token, $module, $method) {
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

    private function zoho_login($username, $password) {
			$authkey = file_get_contents('https://accounts.zoho.com/apiauthtoken/nb/create?SCOPE=ZohoCRM/crmapi&EMAIL_ID='.$username.'&PASSWORD='.$password);
			$authkey = substr($authkey, strpos($authkey, 'AUTHTOKEN=')+10, 32);
      return true;
    }

    private function zoho_listfields($username, $password, $module) {
          $result = $this->call_zoho_crm($password, $module, 'getFields');
          $result = json_decode($result);

			if(isset($result->error)) {
	        echo '<div id="message" class="error below-h2">
	                <p><strong>'.__('Unable to authenticate LiveId.','gravityformscrm').': </strong></p></div>';
	        return false;
				}
		  $sections =$result->$module->section;
		  foreach($sections as $section){
			$section_fields = $section->FL;
			//print_r($section_fields);
			foreach($section_fields as $section_field){
				if(isset($section_field->dv)){
					 $var_name = str_replace(' ', '_', $section_field->label);
						if($section_field->req=='true')
							$convert_fields[] = array('label' => $section_field->dv, 'name' => $var_name, 'required' => $section_field->req);
						else
							$convert_fields[] = array('label' => $section_field->dv, 'name' => $var_name);

					} //if isset

				}
			} //foreach
		  return $convert_fields;
    }

    private function zoho_createlead($username, $password, $module, $merge_vars) {
      $xmldata = '<'.$module.'><row no="1">';
			print_r($merge_vars);
      $i=0;
      $count = count( $merge_vars );
      for ( $i = 0; $i < $count; $i++ ){
				 			$var_name = str_replace('_', ' ', $merge_vars[$i]['name']);
              $xmldata .= '<FL val="'.$var_name.'">';
              $xmldata .= $merge_vars[$i]['value'].'</FL>';
          }
        $xmldata .= '</row></'.$module.'>';
				echo '<pre>';
				print_r($xmldata);
				echo '</pre>';
        $url = 'https://crm.zoho.com/crm/private/xml/'.$module.'/insertRecords';
		$token =$password;
        $param= 'authtoken='.$token.'&scope=crmapi&xmlData='.$xmldata;
        //print_r('"'.$url.'?'.$param.'"');
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $query = array('newFormat'=>1,'authtoken'=>$token,'scope'=>'crmapi','xmlData'=>$xmldata);

        $query = http_build_query($query);
        //echo '<br/><code>';
        //print_r($url.'?'.$query);
        //echo '</code>';
        curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
        $result = curl_exec($ch);

        return $result;
    }
    ///////////////////////


    private function debugcrm($message) {
            if (WP_DEBUG==true) {
            //Debug Mode
            echo '  <table class="widefat">
                    <thead>
                    <tr class="form-invalid">
                        <th class="row-title">Debug Mode</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                    <td><pre>';
            print_r($message);
            echo '</pre></td></tr></table>';
        }
    }


} //from main class
