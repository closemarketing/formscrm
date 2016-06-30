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
                                                        'label' => 'Odoo 9',
                                                        'name'  => 'odoo9'
                                                    ),
                                                    array(
                                                        'label' => 'Microsoft Dynamics CRM',
                                                        'name'  => 'msdynamics'
                                                    ),
                                                    array(
                                                        'label' => 'Microsoft Dynamics CRM ON Premise',
                                                        'name'  => 'msdynamicspfe'
                                                    ),
                                                    array(
                                                        'label' => 'ESPO CRM',
                                                        'name'  => 'espocrm'
                                                    ),
                                                    array(
                                                        'label' => 'Zoho CRM',
                                                        'name'  => 'zohocrm'
                                                    ),
                                                    array(
                                                        'label' => 'Salesforce',
                                                        'name'  => 'salesforce'
                                                    ),
                                                    array(
                                                        'label' => 'Bitrix24',
                                                        'name'  => 'bitrix24'
                                                    ),
                                                    array(
                                                        'label' => 'Solve360',
                                                        'name'  => 'solve360'
                                                    ),
                                                    array(
                                                        'label' => 'FacturaDirecta',
                                                        'name'  => 'facturadirecta'
                                                    ),
/*                                                    array(
                                                        'label' => 'amoCRM',
                                                        'name'  => 'amocrm'
                                                    )*/
                                                )
					),
					array(
						'name'              => 'gf_crm_url',
						'label'             => __( 'CRM URL', 'gravityformscrm' ),
						'type'              => 'text',
						'class'             => 'medium',
                        'tooltip'       => __( 'Use the URL with http and the ending slash /.', 'gravityformscrm' ),
                        'tooltip_class'     => 'tooltipclass',
                        'dependency' => array( 'field' => 'gf_crm_type', 'values' => array( 'SugarCRM','SugarCRM7', 'Odoo 8', 'Odoo 9','Microsoft Dynamics CRM','Microsoft Dynamics CRM ON Premise','ESPO CRM','SuiteCRM','vTiger','VTE CRM','Bitrix24', 'FacturaDirecta','amoCRM') )
					),
					array(
						'name'              => 'gf_crm_username',
						'label'             => __( 'Username', 'gravityformscrm' ),
						'type'              => 'text',
						'class'             => 'medium',
						'feedback_callback' => $this->login_api_crm()
					),
					array(
						'name'  => 'gf_crm_password',
						'label' => __( 'Password', 'gravityformscrm' ),
						'type'  => 'api_key',
						'class' => 'medium',
                        'tooltip'       => __( 'Use the password of the actual user.', 'gravityformscrm' ),
                        'tooltip_class'     => 'tooltipclass',
                        'dependency' => array( 'field' => 'gf_crm_type', 'values' => array( 'SugarCRM','SugarCRM7', 'Odoo 8', 'Odoo 9', 'Microsoft Dynamics CRM','Microsoft Dynamics CRM ON Premise','ESPO CRM','SuiteCRM','Zoho CRM','Bitrix24', 'FacturaDirecta' ) )
					),
					array(
						'name'  => 'gf_crm_apipassword',
						'label' => __( 'API Password for User', 'gravityformscrm' ),
						'type'  => 'api_key',
						'class' => 'medium',
						//'feedback_callback' => $this->login_api_crm(),
                        'tooltip'       => __( 'Find the API Password in the profile of the user in CRM.', 'gravityformscrm' ),
                        'tooltip_class'     => 'tooltipclass',
                        'dependency' => array( 'field' => 'gf_crm_type', 'values' => array( 'vTiger','VTE CRM','Solve360','amoCRM' ) ),
					),
					array(
						'name'  => 'gf_crm_apisales',
						'label' => __( 'Password and Security Key', 'gravityformscrm' ),
						'type'  => 'api_key',
						'class' => 'medium',
                        'tooltip'       => __( '"Password""SecurityKey" Go to My Settings / Reset my Security Key.', 'gravityformscrm' ),
                        'tooltip_class'     => 'tooltipclass',
                        'dependency' => array( 'field' => 'gf_crm_type', 'values' => array( 'Salesforce' ) ),
					),
					array(
						'name'              => 'gf_crm_odoodb',
						'label'             => __( 'Odoo DB Name', 'gravityformscrm' ),
						'type'              => 'text',
						'class'             => 'medium',
                        'dependency' => array( 'field' => 'gf_crm_type', 'values' => array( 'Odoo 8', 'Odoo 9' ) ),
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
			<div class="notice notice-error">
				<?php
					_e( 'We are unable to login to CRM.', 'gravityformscrm' );
					echo ' <a href="' . $this->get_plugin_settings_url() . '">'.__('Use Settings Page','gravityformscrm').'</a>' ?>
			</div>
			<?php
			//Test server settings
			$this->testserver();

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
        if (isset($settings['gf_crm_apisales']) ) $apisales = $settings['gf_crm_apisales']; else $apisales="";

        if($crm_type == 'vTiger') { //vtiger Method
			include_once('lib/crm-vtiger.php');
            $custom_fields = vtiger_listfields($username, $apipassword, $url, 'Leads');

        } elseif($crm_type == 'SugarCRM'||$crm_type == 'SuiteCRM') {
			include_once('lib/crm-sugarcrm6.php');
            $custom_fields = sugarcrm_listfields($username, $password, $url,'Leads');

        } elseif($crm_type == 'SugarCRM7') {
			include_once('lib/crm-sugarcrm7.php');
            $custom_fields = sugarcrm_listfields7($username, $password, $url,'Leads');

        } elseif($crm_type == 'Odoo 8') { //Odoo method
			include_once('lib/crm-odoo8.php');
            $custom_fields = odoo8_listfields($username, $password, $dbname, $url,"lead");

        } elseif($crm_type == 'Odoo 9') { //Odoo method
			include_once('lib/crm-odoo9.php');
            $custom_fields = odoo9_listfields($username, $password, $dbname, $url,"lead");

        } elseif($crm_type == 'Microsoft Dynamics CRM') { //MS Dynamics
			include_once('lib/crm-msdyn.php');
            $custom_fields = msdyn_listfields($username, $password, $url,"lead");

        } elseif($crm_type == 'Microsoft Dynamics CRM ON Premise') { //MS Dynamics
			include_once('lib/crm-msdynpfe.php');
            $custom_fields = msdynpfe_listfields($username, $password, $url,"lead");

        } elseif($crm_type == 'VTE CRM') {
			include_once('lib/crm-vte.php');
            $custom_fields = vte_listfields($username, $apipassword, $url,'Leads');

         } elseif($crm_type == 'ESPO CRM') {
 			include_once('lib/crm-espo.php');
            $custom_fields = espo_listfields($username, $password, $url,'Lead');

         } elseif($crm_type == 'Zoho CRM') {
 			include_once('lib/crm-zoho.php');
            $custom_fields = zoho_listfields($username, $apipassword, 'Leads');

         } elseif($crm_type == 'Salesforce') {
 			include_once('lib/crm-salesforce.php');
            $custom_fields = salesforce_listfields($username, $apisales, 'Lead');

         } elseif($crm_type == 'Bitrix24') {
 			include_once('lib/crm-bitrix.php');
            $custom_fields = bitrix_listfields($username, $password, $url, 'Leads');

         } elseif($crm_type == 'Solve360') {
 			include_once('lib/crm-solve360.php');
            $custom_fields = solve360_listfields($username, $apipassword, 'contacts');

         } elseif($crm_type == 'FacturaDirecta') {
 			include_once('lib/crm-facturadirecta.php');
            $custom_fields = facturadirecta_listfields($url, $apipassword);

         } elseif($crm_type == 'amoCRM') {
 			include_once('lib/crm-amocrm.php');
            $custom_fields = amocrm_listfields($username, $apipassword, $url, "contacts");

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
				$value = '';
				foreach ( $field['inputs'] as $input ) {
					$index = (string) $input['id'];
					$value_n = apply_filters( 'gform_crm_field_value', rgar( $entry, $index ), $form['id'], $field_id, $entry );
					$value .= $value_n;
					if ($value_n) $value .= '|';
				}
				$value = substr($value, 0, -1);
				$merge_vars[] = array(
					'name'   => $var_key,
					'value' => $value
				);
			} else if ( RGFormsModel::get_input_type( $field ) == 'multiselect' ) {
				$value = apply_filters( 'gform_crm_field_value', rgar( $entry, $field_id ), $form['id'], $field_id, $entry );
				$value = str_replace(',', '|', $value);

				$merge_vars[] = array(
					'name'   => $var_key,
					'value' => $value
				);
			} else if ( RGFormsModel::get_input_type( $field ) == 'textarea' ) {
				$value = apply_filters( 'gform_crm_field_value', rgar( $entry, $field_id ), $form['id'], $field_id, $entry );
				$value = str_replace( array("\r", "\n"), ' ', $value);
				$merge_vars[] = array(
					'name'   => $var_key,
					'value' => $value
				);
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
        if (isset($settings['gf_crm_apisales']) ) $apisales = $settings['gf_crm_apisales']; else $apisales="";

		$this->debugcrm($settings);

		$this->debugcrm($merge_vars);

        if($crm_type == 'vTiger') { //vtiger Method
			include_once('lib/crm-vtiger.php');
            $id = vtiger_create_lead($username, $apipassword, $url, 'Leads', $merge_vars);

        } elseif($crm_type == 'SugarCRM'||$crm_type == 'SuiteCRM') {
			include_once('lib/crm-sugarcrm6.php');
            $id = sugarcrm_create_lead($username, $password, $url, 'Leads', $merge_vars);

        } elseif($crm_type == 'SugarCRM7') {
			include_once('lib/crm-sugarcrm7.php');
            $id = sugarcrm_create_lead7($username, $password, $url, 'Leads', $merge_vars);

        } elseif($crm_type == 'Odoo 8') {
			include_once('lib/crm-odoo8.php');
            $id = odoo8_create_lead($username, $password, $dbname, $url, 'lead', $merge_vars);

        } elseif($crm_type == 'Odoo 9') {
			include_once('lib/crm-odoo9.php');
            $id = odoo9_create_lead($username, $password, $dbname, $url, 'crm.lead', $merge_vars);

        } elseif($crm_type == 'Microsoft Dynamics CRM') { //MS Dynamics Method
			include_once('lib/crm-msdyn.php');
            $id = msdyn_create_lead($username, $password, $url, "lead", $merge_vars);

        } elseif($crm_type == 'Microsoft Dynamics CRM ON Premise') { //MS Dynamics Method
			include_once('lib/crm-msdynpfe.php');
            $id = msdynpfe_create_lead($username, $password, $url, "lead", $merge_vars);

        } elseif($crm_type == 'VTE CRM') {
			include_once('lib/crm-vte.php');
            $id = vte_create_lead($username, $apipassword, $url, 'Leads', $merge_vars);

        } elseif($crm_type == 'ESPO CRM') {
			include_once('lib/crm-espo.php');
            $id = espo_createlead($username, $password, $url, 'Lead', $merge_vars);

        } elseif($crm_type == 'Zoho CRM') {
			include_once('lib/crm-zoho.php');
            $id = zoho_createlead($username,  $apipassword, 'Leads', $merge_vars);

        } elseif($crm_type == 'Salesforce') {
			include_once('lib/crm-salesforce.php');
            $id = salesforce_create_lead($username, $apisales, 'Lead', $merge_vars);

        } elseif($crm_type == 'Bitrix24') {
			include_once('lib/crm-bitrix.php');
			$crmport ="443";
			$id = bitrix_create_lead($username, $password, $url, $crmport, "Leads", $merge_vars);

        } elseif($crm_type == 'Solve360') {
			include_once('lib/crm-solve360.php');
            $id = solve360_createcontact($username, $apipassword, 'contacts', $merge_vars);

        } elseif($crm_type == 'FacturaDirecta') {
			include_once('lib/crm-facturadirecta.php');
            $id = facturadirecta_createlead($url, $apipassword, $merge_vars);

		} elseif($crm_type == 'amoCRM') {
			include_once('lib/crm-amocrm.php');
            $id = amocrm_createlead($username, $apipassword, $url, "contacts", $merge_vars);

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
    if (isset($settings['gf_crm_apisales']) ) $apisales = $settings['gf_crm_apisales']; else $apisales="";
	$login_result = false;
	$this->debugcrm($settings);

    if($crm_type == 'vTiger') { //vtiger Method
		include_once('lib/crm-vtiger.php');
        $login_result = vtiger_login($username, $apipassword, $url);

    } elseif($crm_type == 'SugarCRM'||$crm_type == 'SuiteCRM') { //sugarcrm method
		include_once('lib/crm-sugarcrm6.php');
        $login_result = sugarcrm_login($username, $password, $url, 'Leads');

    } elseif($crm_type == 'SugarCRM7') { //sugarcrm7 method
		include_once('lib/crm-sugarcrm7.php');
        $login_result = sugarcrm_login7($username, $password, $url, 'Leads');

    } elseif($crm_type == 'Odoo 8') { //Odoo 8 Method
		include_once('lib/crm-odoo8.php');
        $login_result = odoo8_login($username, $password, $dbname, $url);

    } elseif($crm_type == 'Odoo 9') { //Odoo 9 Method
		include_once('lib/crm-odoo9.php');
        $login_result = odoo9_login($username, $password, $dbname, $url);

    } elseif($crm_type == 'Microsoft Dynamics CRM') { //MS Dynamics Method
		include_once('lib/crm-msdyn.php');
        $login_result = msdyn_login($username, $password, $url,"lead");

    } elseif($crm_type == 'Microsoft Dynamics CRM ON Premise') { //MS Dynamics Method
		include_once('lib/crm-msdynpfe.php');
        $login_result = msdynpfe_login($username, $password, $url,"lead");

    } elseif($crm_type == 'VTE CRM') {
		include_once('lib/crm-vte.php');
        $login_result = vte_login($username, $apipassword, $url);

    } elseif($crm_type == 'ESPO CRM') {
		include_once('lib/crm-espo.php');
        $login_result = espo_login($username, $password, $url);

    } elseif($crm_type == 'Zoho CRM') {
		include_once('lib/crm-zoho.php');
        $login_result = zoho_login($username, $password, $apipassword);
		
		$settings['gf_crm_apipassword'] = $login_result;
		$this->update_plugin_settings($settings);

    } elseif($crm_type == 'Salesforce') {
		include_once('lib/crm-salesforce.php');
        $login_result = salesforce_login($username, $apisales);

    } elseif($crm_type == 'Bitrix24') {
		include_once('lib/crm-bitrix.php');
		$crmport ="443"; //Assumed by default
        $login_result = bitrix_login($username, $password, $url, $crmport);

    } elseif($crm_type == 'Solve360') {
		include_once('lib/crm-solve360.php');
        $login_result = solve360_login($username, $apipassword);

    } elseif($crm_type == 'FacturaDirecta') {
		include_once('lib/crm-facturadirecta.php');

		$login_result = facturadirecta_login($url, $username, $password, $apipassword);
        $settings['gf_crm_apipassword'] = $login_result;
        $this->update_plugin_settings($settings);

	} elseif($crm_type == 'amoCRM') {
		include_once('lib/crm-amocrm.php');
        $login_result = amocrm_login($username, $apipassword, $url);

	} //OF CRM

    $this->debugcrm($login_result);

	$this->testserver();

    if (!isset($login_result) )
        $login_result="";
    return $login_result;
    }

//////////// Helpers Functions ////////////

    private function debugcrm($message) {
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

    private function testserver() {
            //test curl
		    if(!function_exists('curl_version'))
				echo '<div id="message" class="error below-h2">
						<p><strong>'.__('curl is not Installed in your server. It is needed to work with CRM Libraries.' ,'gravityformscrm').'</strong></p></div>';
    }


} //from main class
