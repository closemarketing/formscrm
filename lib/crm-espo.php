<?php
/////// ESPO CRM ///////
function espo_login($username, $password, $url){
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
    echo '<div id="message" class="updated below-h2"><p><strong>'.__('Logged correctly in', 'gravityformscrm').' ESPO CRM</strong></p></div>';
   return true;
  }
  else {
    echo '<div id="message" class="error below-h2"><p><strong>'.__('Cannot Login in', 'gravityformscrm').' ESPO CRM</strong></p></div>';

    return false;
    }
}

function espo_listfields($username, $password, $url, $module) {
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

function espo_createlead($username, $password, $url, $module, $merge_vars){
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
