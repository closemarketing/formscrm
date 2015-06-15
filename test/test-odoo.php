<?php
$test_username ="info@gibam.es";
$test_password = "admin";
$test_url ="http://central.panatta.es:8069/";
$test_db = "panatta_db";

/////// ODOO CRM ///////
//Helpers functions
    function odoo8_login($username, $password, $dbname, $url) {
        //Load Library XMLRPC
        require_once('../lib/ripcord.php');
        
        //Manage Errors from Library
		try {
        $common = ripcord::client($url.'xmlrpc/2/common');
            		print_r($common);
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
        
        //echo '<pre>';
        //print_r($vals);
        //echo '</pre>';
            
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
    
    //Converts Gravity Forms Array to Odoo 8 Array to create field
    function convert_odoo8_merge($merge_vars){
        $i =0;
        $arraymerge = array();
        foreach($merge_vars as $mergefield) {
            $arraymerge = array_merge($arraymerge,array( $mergefield['name'] => $mergefield['value'] ) );
            $i++;
        }
        
        return $arraymerge;
    } //function

//// Main Functions
function odoo8_listfields($username, $password, $dbname, $url, $module) {
    if(substr($url, -1) !='/') $url.='/'; //adds slash to url
    $uid = odoo8_login($username, $password, $dbname, $url); 

    $models = ripcord::client($url.'xmlrpc/2/object');
    $models->execute_kw($dbname, $uid, $password,'crm.lead', 'fields_get', array(), array('attributes' => array('string', 'help', 'type')));

    $custom_fields = convert_XML_odoo8_customfields( $models->_response );

    // Return an array of fields
    return $custom_fields;
}

function odoo8_create_lead($username, $password, $dbname, $url, $module, $merge_vars) {
    
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
    $id = $models->execute_kw($dbname, $uid, $password, 'crm.lead', 'create',
    array($arraymerge));
    
    return $id;
}

////////////////////////////////

echo '<p>Login Odoo:</p>';
$login_api = odoo8_login($test_username, $test_password, $test_db, $test_url);
print_r($login_api);

echo '<p>List Fields</p>';
$custom_fields = odoo8_listfields($test_username, $test_password, $test_db, $test_url, 'lead');
echo '<pre>';
print_r($custom_fields);
echo '</pre>';

echo '<p>Create lead from test mergevar</p>';

$test_mergevars = array(
                array( 'name' => 'name', 'value' => 'User test'),
                array( 'name' => 'phone', 'value' => '6666666666'),
                array( 'name' => 'partner_address_email', 'value' => 'david@panatta.es')
            );
$idlead = odoo8_create_lead($test_username, $test_password, $test_db, $test_url, 'lead', $test_mergevars);
echo '<pre>';
print_r($idlead);
echo '</pre>';