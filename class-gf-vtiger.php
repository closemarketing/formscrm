<?php

GFForms::include_feed_addon_framework();

class GFvTiger extends GFFeedAddOn {

	protected $_version = GF_VTIGER_VERSION;
	protected $_min_gravityforms_version = '1.8.17';
	protected $_slug = 'gravityformsvtiger';
	protected $_path = 'gravityformsvtiger/vtiger.php';
	protected $_full_path = __FILE__;
	protected $_url = 'http://www.gravityforms.com';
	protected $_title = 'vTiger Add-On';
	protected $_short_title = 'vTiger';

	// Members plugin integration
	protected $_capabilities = array( 'gravityforms_vtiger', 'gravityforms_vtiger_uninstall' );

	// Permissions
	protected $_capabilities_settings_page = 'gravityforms_vtiger';
	protected $_capabilities_form_settings = 'gravityforms_vtiger';
	protected $_capabilities_uninstall = 'gravityforms_vtiger_uninstall';
	protected $_enable_rg_autoupgrade = true;

	private static $_instance = null;

	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new GFvTiger();
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
				'title'       => __( 'vTiger Account Information', 'gravityformsvtiger' ),
				'description' => sprintf( __( 'vTiger is a CRM software. Use Gravity Forms to collect customer information and automatically add them to your vtiger Leads.', 'gravityformsvtiger' ),
					'<a href="http://www.vtiger.com" target="_blank">', '</a>.' ),
				'fields'      => array(
					array(
						'name'              => 'gf_vtiger_url',
						'label'             => __( 'CRM URL', 'gravityformsvtiger' ),
						'type'              => 'text',
						'class'             => 'medium',
					),
					array(
						'name'              => 'gf_vtiger_username',
						'label'             => __( 'Username', 'gravityformsvtiger' ),
						'type'              => 'text',
						'class'             => 'medium',

					),
					array(
						'name'  => 'gf_vtiger_password',
						'label' => __( 'API Password for User', 'gravityformsvtiger' ),
						'type'  => 'api_key',
						'class' => 'medium',
						//'feedback_callback' => array( $this, 'is_valid_key' )
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

		$caption = '<small>' . sprintf( __( "You can find your unique API key by clicking on the 'Account Settings' link at the top of your vTiger screen.", 'gravityformsvtiger' ) ) . '</small>';

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
			<div><?php echo sprintf( __( 'We are unable to login to vTiger with the provided API key. Please make sure you have entered a valid API key in the %sSettings Page%s', 'gravityformsvtiger' ),
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
				'title'       => __( 'vTiger Feed', 'gravityformsvtiger' ),
				'description' => '',
				'fields'      => array(
					array(
						'name'     => 'feedName',
						'label'    => __( 'Name', 'gravityformsvtiger' ),
						'type'     => 'text',
						'required' => true,
						'class'    => 'medium',
						'tooltip'  => '<h6>' . __( 'Name', 'gravityformsvtiger' ) . '</h6>' . __( 'Enter a feed name to uniquely identify this setup.', 'gravityformsvtiger' ),
					),
					array(
						'name'       => 'listFields',
						'label'      => __( 'Map Fields', 'gravityformsvtiger' ),
						'type'       => 'field_map',
						'dependency' => 'contactList',
						'field_map'	 => $this->create_list_field_map(),
						'tooltip'    => '<h6>' . __( 'Map Fields', 'gravityformsvtiger' ) . '</h6>' . __( 'Associate your vTiger custom fields to the appropriate Gravity Form fields by selecting the appropriate form field from the list.', 'gravityformsvtiger' ),
					),
				)
			),
		);
	}

	public function create_list_field_map() {

		//$list_id = $this->get_setting( 'contactList' );

		$custom_fields = $this->get_custom_fields( );

		return $custom_fields;

	}

	public function settings_option_resubscribe( $field, $echo = true ) {

		$field['type'] = 'checkbox';

		$options          = array(
			array(
				'label' => __( 'Resubscribe', 'gravityformsvtiger' ),
				'name'  => 'resubscribe',
			),
		);
		$field['choices'] = $options;
		$html             = $this->settings_checkbox( $field, false );

		$tooltip_content = '<h6>' . __( 'Resubscribe', 'gravityformsvtiger' ) . '</h6>' . __( 'When this option is enabled, if the subscriber is in an inactive state or has previously been unsubscribed, they will be re-added to the active list. Therefore, this option should be used with caution and only when appropriate.', 'gravityformsvtiger' );
		$tooltip         = gform_tooltip( $tooltip_content, '', true );

		$html = str_replace( '</div>', $tooltip . '</div>', $html );

		$resubscribe_warning_style = $this->get_setting( 'resubscribe' ) ? '' : 'display:none';
		$html .= '<small><span id="vtiger_resubscribe_warning" style="' . $resubscribe_warning_style . '">' . __( 'This option will re-subscribe users that have been unsubscribed. Use with caution and only when appropriate.', 'gravityformsvtiger' ) . '</span></small>';

		if ( $echo ) {
			echo $html;
		}

		return $html;

	}

	public function get_custom_fields( ) {

		$custom_fields = array(
			array( 'label' => __('Email Address', 'gravityformsvtiger' ), 'name' => 'email', 'required' => true ),
			array( 'label' => __('Full Name', 'gravityformsvtiger' ) , 'name' => 'fullname' ),
			array( 'label' => __('Phone', 'gravityformsvtiger' ) , 'name' => 'phone' ),
			array( 'label' => __('Lead Source', 'gravityformsvtiger' ) , 'name' => 'leadsource' ),
			array( 'label' => __('Birthday', 'gravityformsvtiger' ) , 'name' => 'birthday' ),
			array( 'label' => __('Address', 'gravityformsvtiger' ) , 'name' => 'mailingstreet' ),
			array( 'label' => __('City', 'gravityformsvtiger' ) , 'name' => 'mailingcity' ),
			array( 'label' => __('State', 'gravityformsvtiger' ) , 'name' => 'mailingstate' ),
			array( 'label' => __('ZIP', 'gravityformsvtiger' ) , 'name' => 'mailingzip' ),
			array( 'label' => __('Country', 'gravityformsvtiger' ) , 'name' => 'mailingcountry' ),
			array( 'label' => __('Description', 'gravityformsvtiger' ) , 'name' => 'description' ),
		);

		/*$response = $api->get_custom_fields();
		if ( ! $response->was_successful() ) {
			return $custom_fields;
		}

		$custom_field_objects = $response->response;

		foreach ( $custom_field_objects as $custom_field ) {
			$name            = str_replace( '[', '', $custom_field->Key );
			$name            = str_replace( ']', '', $name );
			$custom_fields[] = array( 'label' => $custom_field->FieldName, 'name' => $name );
		}*/

		return $custom_fields;

	}

	public function ensure_upgrade(){

		if ( get_option( 'gf_vtiger_upgrade' ) ){
			return false;
		}

		$feeds = $this->get_feeds();
		if ( empty( $feeds ) ){

			//Force Add-On framework upgrade
			$this->upgrade( '2.0' );
		}

		update_option( 'gf_vtiger_upgrade', 1 );
	}

	public function process_feed( $feed, $entry, $form ) {

		if ( ! $this->is_valid_key() ) {
			return;
		}

		$this->export_feed( $entry, $form, $feed );

	}

	public function export_feed( $entry, $form, $feed ) {

		$resubscribe = $feed['meta']['resubscribe'] ? true : false;
		$email       = $entry[ $feed['meta']['listFields_email'] ];
		$name        = '';
		if ( ! empty( $feed['meta']['listFields_fullname'] ) ) {
			//$name = $this->get_name( $entry, $feed['meta']['listFields_fullname'] );
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
						'Value' => apply_filters( 'gform_vtiger_field_value', rgar( $entry, $index ), $form['id'], $field_id, $entry )
					);
				}
			} else if ( ! in_array( $var_key, array( 'email', 'fullname' ) ) ) {
				$merge_vars[] = array(
					'Key'   => $var_key,
					'Value' => apply_filters( 'gform_vtiger_field_value', rgar( $entry, $field_id ), $form['id'], $field_id, $entry )
				);
			}
		}

		$override_custom_fields = apply_filters( 'gform_vtiger_override_blank_custom_fields', false, $entry, $form, $feed );
		if ( ! $override_custom_fields ){
			$merge_vars = $this->remove_blank_custom_fields( $merge_vars );
		}

		$subscriber = array(
			'EmailAddress' => $email,
			'Name'         => $name,
			'CustomFields' => $merge_vars,
			'Resubscribe'  => $resubscribe,
		);
        $subscriber = apply_filters( 'gform_vtiger_override_subscriber', $subscriber, $entry, $form, $feed );

		$this->include_api();
		$api = new CS_REST_Subscribers( $feed['meta']['contactList'], $this->get_api_key() );
		$this->log_debug( 'Adding subscriber.' );
		$api->add( $subscriber );
		$this->log_debug( 'Subscriber added.' );
	}

	private static function remove_blank_custom_fields( $merge_vars ){
		$i=0;
		$count = count( $merge_vars );

		for ( $i = 0; $i < $count; $i++ ){
			if( rgblank( $merge_vars[$i]['Value'] ) ){
				unset( $merge_vars[$i] );
			}
		}
		//resort the array because items could have been removed, this will give an error from vTiger if the keys are not in numeric sequence
		sort( $merge_vars );
		return $merge_vars;
	}
/*
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
*/
    private static function is_valid_key(){
        $result_api = self::login_api_vtiger();;
        return $result_api;
    }

    private static function get_crm_url(){
		$settings = $this->get_plugin_settings();
		$url  = $settings['gf_vtiger_url'];
        return $url;
    }

    private static function get_username(){
		$settings = $this->get_plugin_settings();
        $username = $settings['gf_vtiger_username'];
        return $username;
    }

    private static function get_password(){
		$settings = $this->get_plugin_settings();
        $password = $settings['gf_vtiger_password'];
        return $password;
    }


    private static function login_api_vtiger(){

	include_once('includes/WSClient.php');

	$client = new Vtiger_WSClient( self::get_crm_url() );

	$login = $client->doLogin(self::get_username(), self::get_password() );

	if(!$login) {  $login_result = false; } else { $login_result = $login; }

    return $login_result;
    }
    
    
}