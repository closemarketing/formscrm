<?php

if (!defined('VTWSC_BASEDIR')) {
	define('VTWSC_BASEDIR', dirname(__FILE__));
}

require_once(VTWSC_BASEDIR.'/WSClient.php');

/**
 * VTE Webservice Client
 */
class VTE_WSClient extends Vtiger_WSClient {
	
	function doUpdate($valuemap) {
		// Perform re-login if required.
		$this->__checkLogin();

		// Assign record to logged in user if not specified
		if(!isset($valuemap['assigned_user_id'])) {
			$valuemap['assigned_user_id'] = $this->_userid;
		}

		$postdata = Array(
			'operation' => 'updateRecord',
			'sessionName' => $this->_sessionid,
			'id' => $id,
			'columns' => $this->toJSONString($valuemap)
		);
		$resultdata = $this->_client->doPost($postdata, true);
		if($this->hasError($resultdata)) {
			return false;
		}		
		return $resultdata[result];
	}
	
	function doUpdateOld($valuemap) {
		return  parent::doUpdate($valuemap);
	}

	/**
	 * Do login using username and password
	 */
	function doLoginPwd($username, $password) {
		// Do the challenge before login
		if($this->__doChallenge($username) === false) return false;
		
		$postdata = Array(
			'operation' => 'login_pwd',
			'username'  => $username,
			'password' => $password
		);
		$resultdata = $this->_client->doPost($postdata, true);

		if($this->hasError($resultdata)) {
			return false;
		}
		$accesskey = $resultdata['result'][0] ?: $resultdata[0];
		if (empty($accesskey)) return false;

		return $this->doLogin($username, $accesskey);
	}

}

?>