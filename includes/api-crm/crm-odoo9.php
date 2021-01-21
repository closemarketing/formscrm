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

function odoo9_login($username, $password, $dbname, $url) {
	//Load Library XMLRPC
	require_once('ripcord/ripcord.php');
	if(substr($url, -1) !='/') $url.='/'; //adds slash to url

	//Manage Errors from Library
	try {
	$common = ripcord::client($url.'xmlrpc/2/common');
	} catch (Exception $e) {
		echo '<div id="message" class="error below-h2">
		<p><strong>Error: '.$e->getMessage().'</strong></p></div>';
		return false;
	}

	try {
	$uid = $common->authenticate($dbname, $username, $password, array());
	} catch (Exception $e) {
		echo '<div id="message" class="error below-h2">
		<p><strong>Error: '.$e->getMessage().'</strong></p></div>';
		return false;
	}

	if (isset($uid) )
		return $uid;
	else
		return false;
}
// from login Odoo
//Converts XML Odoo in array for Gravity Forms Custom Fields
function convert_XML_odoo9_customfields($xml_odoo){
	$p = xml_parser_create();
	xml_parse_into_struct($p, $xml_odoo, $vals, $index);
	xml_parser_free($p);

	$custom_fields = array();
	$i =0;

	foreach($vals as $field)
	{
		if( $field["tag"] == 'NAME' ) {
			if ( $field["value"] != 'type' && $field["value"] != 'string' && $field["value"] != 'help' && $field["value"] != 'id')
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
function convert_odoo9_merge($merge_vars){
	$i =0;
	$arraymerge = array();
	foreach($merge_vars as $mergefield) {
		$arraymerge = array_merge($arraymerge,array( $mergefield['name'] => $mergefield['value'] ) );
		$i++;
	}

	return $arraymerge;
} //function

function odoo9_listfields($username, $password, $dbname, $url, $module) {
	if(substr($url, -1) !='/') $url.='/'; //adds slash to url
	$uid = odoo9_login($username, $password, $dbname, $url);

	if($uid != false) {
		$models = ripcord::client($url.'xmlrpc/2/object');
		$models->execute_kw($dbname, $uid, $password,'crm.lead', 'fields_get', array(), array('attributes' => array('string', 'help', 'type')));

		$custom_fields = convert_XML_odoo9_customfields( $models->_response );
	}
	// Return an array of fields
	return $custom_fields;
}

function odoo9_create_lead($username, $password, $dbname, $url, $module, $merge_vars) {

	//Converts to Array
	$i =0;
	$arraymerge = array();
	foreach($merge_vars as $mergefield) {
		$arraymerge = array_merge($arraymerge,array( $mergefield['name'] => $mergefield['value'] ) );
		$i++;
	}

	if(substr($url, -1) !='/') $url.='/'; //adds slash to url
	$uid = odoo9_login($username, $password, $dbname, $url);

	if($uid != false) {
		$models = ripcord::client($url.'xmlrpc/2/object');
		$id = $models->execute_kw($dbname, $uid, $password, $module, 'create', array($arraymerge));
	} else {
		debug_email_lead('Odoo9','Error',$merge_vars);
		return false;
	}

	return $id;
}

///////////////// odoo9 ////////////////////////////////////////
