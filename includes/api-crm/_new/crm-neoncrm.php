<?php
/**
 * NEONCRM connect library
 *
 * Has functions to login, list fields and create leadº
 *
 * @author   closemarketing
 * @category Functions
 * @package  Gravityforms CRM
 * @version  1.0.0
 */

include_once 'debug.php';

Class GFCRM_LIB
{
    /**
     * Variables
     *
     * @var string
     */

    /**
     * Construct and intialize
     */
    public function __construct() {

    }
    /*
     * Logins to CRM
     *
     * Logins to a crm with the data given
     *
     * @username (string)
     * @password (string)
     * @url    (type)
     * @dbname (type)
     * @return (string) ID of connection
     */
    function login($username, $password, $url, $dbname) {

    }
    /*
     * Logins to CRM
     *
     * Logins to a crm with the data given
     *
     * @username (string)
     * @password (string)
     * @url    (type)
     * @dbname (type)
     * @return (string) ID of connection
     */
    function list_modules($username, $password, $url, $dbname) {
      return array('');
    }
    /*
     * Logins to CRM
     *
     * Logins to a crm with the data given
     *
     * @param (string) $username
     * @param (string)
     * @param (string)
     * @param (string)
     * @return (string) ID of connection
     */
    function list_fields($username, $password, $url, $dbname,$module) {

        return $fields;
    }
    /*
     * Logins to CRM
     *
     * Logins to a crm with the data given
     *
     * @username (string)
     * @password (string)
     * @url    (type)
     * @dbname (type)
     * @return (string) ID of entry created
     */
    function create_lead($username, $password, $url, $dbname, $module, $merge_vars) {

    }


} //from Class GFCRM

