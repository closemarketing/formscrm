<?php
/**
 * Odoo8 connect library
 *
 * Has functions to login, list fields and create lead
 *
 * @author   closemarketing
 * @category Functions
 * @package  Gravityforms CRM
 * @version  1.2.0
 */

include_once 'debug.php';

class CRMLIB_ODOO8 {

        //Helpers functions
        /**
             * Logins to a CRM
             *
             * @param  array $settings settings from Gravity Forms options
             * @return false or id           returns false if cannot login and string if gets token
             */
    function login( $settings ) {

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
      if( $url && $username && $password ) {

            //Load Library XMLRPC
            require_once('ripcord/ripcord.php');

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

      } else {
        return false;
      }

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
             * List modules of a CRM
             *
             * @param  array $settings settings from Gravity Forms options
             * @return array           returns an array of mudules
             */
            function list_modules( $settings ) {
                $url      = check_url_crm( $settings['gf_crm_url'] );
                $username = $settings['gf_crm_username'];
                $password = $settings['gf_crm_apipassword'];
                
            if(substr($url, -1) !='/') $url.='/'; //adds slash to url
            $uid = $this->login($settings);

            $models = ripcord::client($url.'xmlrpc/2/object');
            $models->execute_kw($dbname, $uid, $password,'crm.lead', 'fields_get', array(), array('attributes' => array('string', 'help', 'type')));

            $custom_fields = convert_XML_odoo8_customfields( $models->_response );

            // Return an array of fields
            return $custom_fields;
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

            //Converts to Array
            $i =0;
            $arraymerge = array();
            foreach($merge_vars as $mergefield) {
                $arraymerge = array_merge($arraymerge,array( $mergefield['name'] => $mergefield['value'] ) );
                $i++;
            }

            if(substr($url, -1) !='/') $url.='/'; //adds slash to url
            $uid = odoo8_login($username, $password, $dbname, $url);

            $models = ripcord::client($url.'xmlrpc/2/object');
            $id = $models->execute_kw($dbname, $uid, $password, 'crm.lead', 'create', array($arraymerge));

            return $id;
        }
}
