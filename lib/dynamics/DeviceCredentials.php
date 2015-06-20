<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class DeviceCredentials {
    private $deviceName;
    private $password;

    public function DeviceCredentials($deviceName, $password) {
        $this->deviceName = $deviceName;
        $this->password = $password;
    }

    public function getDeviceName() {
        return $this->deviceName;
    }

    public function getPassword() {
        return $this->password;
    }

}

?>
