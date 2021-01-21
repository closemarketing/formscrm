<?php
define('WP_DEBUG',true);

# Enter your domain name , agile email and agile api key
define("AGILE_DOMAIN", "YOUR_AGILE_DOMAIN");  # Example : define("domain","jim");
define("AGILE_USER_EMAIL", "YOUR_AGILE_USER_EMAIL");
define("AGILE_REST_API_KEY", "YOUR_AGILE_REST_API_KEY");

include_once '../crm-agile.php';

echo '<p>create a contact</p>';
$contact_array = array(
  "lead_score"=>"80",
  "star_value"=>"5",
  "tags"=>array("Player","Winner"),
  "properties"=>array(
    array(
      "name"=>"first_name",
      "value"=>"Ronaldo",
      "type"=>"SYSTEM"
    ),
    array(
      "name"=>"last_name",
      "value"=>"de Lima",
      "type"=>"SYSTEM"
    ),
    array(
      "name"=>"email",
      "value"=>"ronaldo100@gmail.com",
      "type"=>"SYSTEM"
    ),
    array(
        "name"=>"title",
        "value"=>"footballer",
        "type"=>"SYSTEM"
    ),
	array(
        "name"=>"address",
        "value"=>json_encode(
            array(
              "address"=>"Avenida Álvares Cabral 1777",
              "city"=>"Belo Horizonte",
              "state"=>"Minas Gerais",
              "country"=>"Brazil"
            )
        ),
        "type"=>"SYSTEM"
    ),
  )
);

$contact_json = json_encode($contact_array);
$leadid = agile_curl_wrap("contacts", $contact_json, "POST", "application/json");
echo '<pre>';
print_r($leadid);
echo '<pre>';


echo '<p>fetch contact data by email</p>';
$list_fields = agile_curl_wrap("contacts/search/email/test@email.com", null, "GET", "application/json");
echo '<pre>';
print_r($list_fields);
echo '<pre>';
