<?php
/**
 * Odoo9 connect library
 *
 * Has functions to login, list fields and create lead
 *
 * @author   closemarketing
 * @category Functions
 * @package  Gravityforms CRM
 * @version  1.2.0
 */

include_once 'debug.php';

class CRMLIB_ODOO9 {
    /**
     * Logins to a CRM
     * @param  array $settings settings from Gravity Forms options
     * @return false or id           returns false if cannot login and string if gets token
     */
    function login($settings) {

      $url = null;
      if( isset( $settings['gf_crm_url'] ) ) {
        $url = check_url_crm($settings['gf_crm_url']);
      }
      $username = null;
      if( isset( $settings['gf_crm_username'] ) ) {
        $username = $settings['gf_crm_username'];
      }
      $password = null;
      if( isset( $settings['gf_crm_apipassword'] ) ) {
        $password = $settings['gf_crm_apipassword'];
      }
      $dbname = null;
      if( isset( $settings['gf_crm_odoodb'] ) ) {
        $dbname = $settings['gf_crm_odoodb'];
      }
      if( $url && $username && $password && $dbname ) {

        //Load Library XMLRPC
        require_once 'ripcord/ripcord.php';
        if (substr($url, -1) != '/') {
          $url .= '/';
        }
        //adds slash to url

        //Manage Errors from Library
        try {
          $common = ripcord::client($url . 'xmlrpc/2/common');
        } catch (Exception $e) {
          echo '<div id="message" class="error below-h2">
          <p><strong>Error: ' . $e->getMessage() . '</strong></p></div>';
          return false;
        }

        try {
          $uid = $common->authenticate($dbname, $username, $password, array());
        } catch (Exception $e) {
          echo '<div id="message" class="error below-h2">
          <p><strong>Error: ' . $e->getMessage() . '</strong></p></div>';
          return false;
        }

        if (isset($uid)) {
          return $uid;
        } else {
          return false;
        }
      } else {
        return false;
      }
    }
    // from login Odoo
    /**
     * List Modules
     */
    function list_modules($settings) {
        $url      = check_url_crm( $settings['gf_crm_url'] );
		$username = $settings['gf_crm_username'];
		$password = $settings['gf_crm_apipassword'];
        $dbname = $settings['gf_crm_odoodb'];

    }
    /**
     * List Fields
     */
    function list_fields($settings) {
        $url      = check_url_crm($settings['gf_crm_url']);
        $username = $settings['gf_crm_username'];
        $password = $settings['gf_crm_apipassword'];
        $dbname   = $settings['gf_crm_odoodb'];
        $module = $settings['gf_crm_module'];

    	if (substr($url, -1) != '/') {
    		$url .= '/';
    	}
    	//adds slash to url
    	$uid = $this->login($settings);

    	if ($uid != false) {
    		$models = ripcord::client($url . 'xmlrpc/2/object');
    		$models->execute_kw($dbname, $uid, $password, 'crm.lead', 'fields_get', array(), array('attributes' => array('string', 'help', 'type')));

    		$custom_fields = $this->convert_XML_odoo9_customfields($models->_response);
    	}
    	if( isset( $custom_fields ) ) {
        // Return an array of fields
        return $custom_fields;
      } else {
        return false;
      }
    }
    /**
     * Create Entry
     */
    function create_entry($settings, $merge_vars) {
      $url      = check_url_crm($settings['gf_crm_url']);
      $username = $settings['gf_crm_username'];
      $password = $settings['gf_crm_apipassword'];
        $dbname = $settings['gf_crm_odoodb'];

    	//Converts to Array
    	$i          = 0;
    	$arraymerge = array();
    	foreach ($merge_vars as $mergefield) {
    		$arraymerge = array_merge($arraymerge, array($mergefield['name'] => $mergefield['value']));
    		$i++;
    	}

    	if (substr($url, -1) != '/') {
    		$url .= '/';
    	}
    	//adds slash to url
    	$uid = $this->login($username, $password, $dbname, $url);

    	if ($uid != false) {
    		$models = ripcord::client($url . 'xmlrpc/2/object');
    		$id     = $models->execute_kw($dbname, $uid, $password, $module, 'create', array($arraymerge));
    	} else {
    		debug_email_lead('Odoo9', 'Error', $merge_vars);
    		return false;
    	}

    	return $id;
    }

    /* # Helpers
    ---------------------------------------------------------------------------------------------------- */

    //Converts XML Odoo in array for Gravity Forms Custom Fields
    function convert_XML_odoo9_customfields($xml_odoo) {
        $p = xml_parser_create();
        xml_parse_into_struct($p, $xml_odoo, $vals, $index);
        xml_parser_free($p);

        $custom_fields = array();
        $i             = 0;

        foreach ($vals as $field) {
            if ($field["tag"] == 'NAME') {
                if ($field["value"] != 'type' && $field["value"] != 'string' && $field["value"] != 'help' && $field["value"] != 'id') {
                    $custom_fields[$i] = array(
                        'label' => $field['value'],
                        'name'  => $field['value'],
                    );
                }

            }
            $i++;
        } //del foreach
        return $custom_fields;
    } //function

    //Converts Gravity Forms Array to Odoo 8 Array to create field
    function convert_odoo9_merge($merge_vars) {
        $i          = 0;
        $arraymerge = array();
        foreach ($merge_vars as $mergefield) {
            $arraymerge = array_merge($arraymerge, array($mergefield['name'] => $mergefield['value']));
            $i++;
        }

        return $arraymerge;
    } //function


}// class