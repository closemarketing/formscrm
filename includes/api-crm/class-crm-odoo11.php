<?php
/**
 * Odoo11 connect library
 *
 * Has functions to login, list fields and create lead
 *
 * @author   closemarketing
 * @category Functions
 * @package  Gravityforms CRM
 * @version  1.1.0
 */

require_once 'debug.php';

class CRMLIB_ODOO11 {
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
		$dbname   = $settings['gf_crm_odoodb'];

		// Load Library XMLRPC.
		require_once 'ripcord/ripcord.php';

		// adds slash to url.
		// Manage Errors from Library.
		try {
			$common = ripcord::client( $url . 'xmlrpc/2/common' );
		} catch ( Exception $e ) {
			echo '<div id="message" class="error below-h2">
			<p><strong>Error: ' . $e->getMessage() . '</strong></p></div>';
			return false;
		}

		try {
			$uid = $common->authenticate( $dbname, $username, $password, array() );
		} catch ( Exception $e ) {
			echo '<div id="message" class="error below-h2">
			<p><strong>Error: ' . $e->getMessage() . '</strong></p></div>';
			return false;
		}

		if ( isset( $uid ) ) {
			return $uid;
		} else {
			return false;
		}
	}
	// from login Odoo
	/**
	 * List Modules
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
		$dbname   = $settings['gf_crm_odoodb'];
		$module   = $settings['gf_crm_module'];

		if ( substr( $url, -1 ) != '/' ) {
			$url .= '/';
		}
		// adds slash to url
		$uid = $this->login( $settings );

		if ( $uid != false ) {
			$models = ripcord::client( $url . 'xmlrpc/2/object' );
			$models->execute_kw( $dbname, $uid, $password, 'crm.lead', 'fields_get', array(), array( 'attributes' => array( 'string', 'help', 'type' ) ) );

			$custom_fields = $this->convert_XML_odoo11_customfields( $models->_response );
		}
		// Return an array of fields
		return $custom_fields;
	}
	/**
	 * Create Entry
	 */
	function create_entry( $settings, $merge_vars ) {

		// Converts to Array
		$i          = 0;
		$arraymerge = array();
		foreach ( $merge_vars as $mergefield ) {
			$arraymerge = array_merge( $arraymerge, array( $mergefield['name'] => $mergefield['value'] ) );
			$i++;
		}
		$uid = $this->login( $username, $password, $dbname, $url );

		if ( false !== $uid ) {
			$models = ripcord::client( $url . 'xmlrpc/2/object' );
			$id     = $models->execute_kw( $dbname, $uid, $password, $module, 'create', array( $arraymerge ) );
		} else {
			debug_email_lead( 'odoo11', 'Error', $merge_vars );
			return false;
		}
		return $id;
	}

	/*
	 # Helpers
	---------------------------------------------------------------------------------------------------- */

	// Converts XML Odoo in array for Gravity Forms Custom Fields
	function convert_XML_odoo11_customfields( $xml_odoo ) {
		$p = xml_parser_create();
		xml_parse_into_struct( $p, $xml_odoo, $vals, $index );
		xml_parser_free( $p );

		$custom_fields = array();
		$i             = 0;

		foreach ( $vals as $field ) {
			if ( $field['tag'] == 'NAME' ) {
				if ( $field['value'] != 'type' && $field['value'] != 'string' && $field['value'] != 'help' && $field['value'] != 'id' ) {
					$custom_fields[ $i ] = array(
						'label' => $field['value'],
						'name'  => $field['value'],
					);
				}
			}
			$i++;
		} //del foreach
		return $custom_fields;
	} //function

	// Converts Gravity Forms Array to Odoo 8 Array to create field
	function convert_odoo11_merge( $merge_vars ) {
		$i          = 0;
		$arraymerge = array();
		foreach ( $merge_vars as $mergefield ) {
			$arraymerge = array_merge( $arraymerge, array( $mergefield['name'] => $mergefield['value'] ) );
			$i++;
		}

		return $arraymerge;
	} //function


}//end class
