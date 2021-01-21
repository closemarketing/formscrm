<?php
use Mautic\Auth\ApiAuth;
use Mautic\MauticApi;
require 'mautic/autoload.php';

////////// Mautic CRM //////////
function mautic_login($username, $password, $url, $callbackurl) {

    // ApiAuth::initiate will accept an array of OAuth settings
    $settings = array(
        'baseUrl'          => $url,       // Base URL of the Mautic instance
        'version'          => 'OAuth2',  // Version of the OAuth can be OAuth2 or OAuth1a. OAuth2 is the default value.
        'clientKey'        => $username,       // Client/Consumer key from Mautic
        'clientSecret'     => $password,       // Client/Consumer secret key from Mautic
        'callback'         => $callbackurl        // Redirect URI/Callback URI for this script
    );
    //// Initiate the auth object
    $auth = ApiAuth::initiate($settings);

    if ($auth->validateAccessToken()) {
        // Obtain the access token returned; call accessTokenUpdated() to catch if the token was updated via a
        // refresh token
        // $accessTokenData will have the following keys:
        // For OAuth1.0a: access_token, access_token_secret, expires
        // For OAuth2: access_token, expires, token_type, refresh_token
        if ($auth->accessTokenUpdated()) {
            $accessTokenData = $auth->getAccessTokenData();
            //store access token data however you want
            $_SESSION["oauth_s"]=$auth;
            return $auth;
        }
    }
   return $auth;

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
