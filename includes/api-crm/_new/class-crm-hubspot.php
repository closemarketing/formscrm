<?php
/**
 * Hubspot connect library
 *
 * Has functions to login, list fields and create leadº
 *
 * @author   closemarketing
 * @category Functions
 * @package  Gravityforms CRM
 * @version  1.1.0
 */

require_once 'debug.php';

/**
 * Class for CRM
 */
class CRMLIB_HUBSPOT {

	/**
	 * Logins to a CRM
	 *
	 * @param  array $settings Settings from Gravity Forms options.
	 * @return boolean id returns false if cannot login and string if gets token.
	 */
	public function login( $settings ) {
    
    $password = null;
    if( isset( $settings['gf_crm_apipassword'] ) ) {
      $password = $settings['gf_crm_apipassword'];
    }
    
    if( $password ) {
      $endpoint = 'https://api.hubapi.com/contacts/v1/lists/all/contacts/all?hapikey=' . $password . '&count=1';
      $ch       = curl_init();
      curl_setopt( $ch, CURLOPT_URL, $endpoint );
      curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Content-Type: application/json' ) );
      curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
      curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
      $response    = curl_exec( $ch );
      $status_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
      $curl_errors = curl_error( $ch );
      curl_close( $ch );

      return $status_code == 200;
    } else {
      return false;
    }
    
	}
	/**
	 * List Modules
	 *
	 * @param array $settings Settings from Gravity Forms options.
	 * @return array $custom_modules Array of modules of CRM
	 */
	public function list_modules( $settings ) {
		return array( 'contact' );
	}
	/**
	 * List Fields of actual module of CRM
	 *
	 * @param array $settings Settings from Gravity Forms options.
	 * @return array $custom_fields Array of fields
	 */
	public function list_fields( $settings ) {
		$password = $settings['gf_crm_apipassword'];
		$module   = isset( $settings['gf_crm_module'] ) ? $settings['gf_crm_module'] : $module = 'contact';

		$endpoint = 'https://api.hubapi.com/properties/v1/' . $module . 's/properties?hapikey=' . $password;
		$ch       = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $endpoint );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Content-Type: application/json' ) );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		$response    = curl_exec( $ch );
		$status_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		$curl_errors = curl_error( $ch );
		curl_close( $ch );

		$responsefields = json_decode( $response );
		if ( isset( $responsefields->status ) && (string) $responsefields->status == 'error' ) {
			if ( isset( $responsefields->message ) ) {
				debug_message( $responsefields->message );
				return $responsefields->message;
			} else {
				echo '<div id="message" class="error below-h2"><p><strong>' . __( 'Invalid hapikey!', 'gravityformscrm' ) . '</strong></p></div>';
				return false;
			}
		}

		foreach ( $responsefields as $element ) {
			$custom_fields[] = array(
				'label'    => (string) $element->label,
				'name'     => (string) $element->name,
				'required' => false,
			);
		}

		return $custom_fields;
	}

	/**
	 * Create an entry in a module
	 *
	 * @param array $settings Settings from Gravity Forms options.
	 * @param array $merge_vars Values passed in the form.
	 * @return id ID of created entry.
	 */
	public function create_entry( $settings, $merge_vars ) {
		$password = $settings['gf_crm_apipassword'];
		$module   = isset( $settings['gf_crm_module'] ) ? $settings['gf_crm_module'] : $module = 'contact';

		$vars = array();
		foreach ( $merge_vars as $var ) {
			$vars[ $var['name'] ] = $var['value'];
			$custom_fields[]      = array(
				'property' => $var['name'],
				'value'    => $var['value'],
			);
		}

		$endpoint  = 'https://api.hubapi.com/' . $module . 's/v1/' . $module . '/?hapikey=' . $password;
		$post_json = json_encode( array( 'properties' => $custom_fields ) );
		$ch        = curl_init();
		curl_setopt( $ch, CURLOPT_POST, true );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $post_json );
		curl_setopt( $ch, CURLOPT_URL, $endpoint );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Content-Type: application/json' ) );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		$response    = curl_exec( $ch );
		$status_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		$curl_errors = curl_error( $ch );
		curl_close( $ch );
		$res = json_decode( $response );

		if ( null !== $curl_errors ) {
			return $curl_errors;
		}

		if ( isset( $res->vid ) ) {
			return $res->vid;
		} elseif ( isset( $res->message ) ) {
			debug_email_lead( 'Hubspot', $res->message, $merge_vars );
			debug_message( $res->message );
		}

		return $recordid;
	}
}
