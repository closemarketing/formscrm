<?php
use Mautic\Auth\ApiAuth;
use Mautic\MauticApi;
require 'mautic/autoload.php';

////////// Mautic CRM //////////
function mautic_login($consumerkey, $consumerskey, $apipassword, $url) {
    $callbackurl = $url."api.php";

    // ApiAuth::initiate will accept an array of OAuth settings
    if ($apipassword) {
        $authkey = $apipassword;
    } else {
        $baseurl = substr($url, 0, -1);
        $settings = array(
            'baseUrl'          => $baseurl,       // Base URL of the Mautic instance
            'version'          => 'OAuth2',  // Version of the OAuth can be OAuth2 or OAuth1a. OAuth2 is the default value.
            'clientKey'        => $consumerkey,       // Client/Consumer key from Mautic
            'clientSecret'     => $consumerskey,       // Client/Consumer secret key from Mautic
            'callback'         => $callbackurl        // Redirect URI/Callback URI for this script
        );
        //// Initiate the auth object
        $authkey = ApiAuth::initiate($settings);

        if ($authkey->validateAccessToken()) {
            // Obtain the access token returned; call accessTokenUpdated() to catch if the token was updated via a
            // refresh token
            // $accessTokenData will have the following keys:
            // For OAuth1.0a: access_token, access_token_secret, expires
            // For OAuth2: access_token, expires, token_type, refresh_token

            if ($authkey->accessTokenUpdated()) {
                $accessTokenData = $authkey->getAccessTokenData();
                //store access token data however you want
                $_SESSION["oauth_s"]=$authkey;
            }
        }
    }
   return $authkey;
}
function mautic_listfields($auth,$url, $module){
   $leadApi = MauticApi::getContext($module, $auth, $url.'/api/');
   return $leadApi->getFieldList();
}
function mautic_create_lead($auth,$url, $module, $merge_vars) {
    $leadApi = MauticApi::getContext($module, $auth, $url.'/api/');
    $vars = array();
    foreach($merge_vars as $var){
        $vars[$var['name']] =  $var['value'];
    }
    $lead= $leadApi->create($vars);

    return   $lead['lead']['id'];
}
   ////////////////////////////////
