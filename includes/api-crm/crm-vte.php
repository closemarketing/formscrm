<?php
/**
 * VTE connect library
 *
 * Has functions to login, list fields and create lead
 *
 * @author   closemarketing
 * @category Functions
 * @package  Gravityforms CRM
 * @version  1.2.0
 */

include_once 'debug.php';

function vte_login($username, $password, $url) {

    require_once('vtwsclib/VTEWSClient.php');
    $client = new VTE_WSClient($url);

    $login = $client->doLogin($username,$password);

    return $login;
}


function vte_listfields($username, $apipassword, $url, $module){
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

function vte_create_lead($username, $apipassword, $url, $module, $merge_vars) {
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
    } else {
		    debug_email_lead('VTE','Error',$merge_vars);
    }

    return $record;
}
