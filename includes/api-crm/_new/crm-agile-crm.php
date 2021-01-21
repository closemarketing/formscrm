<?php
/**
 * AGILE connect library
 *
 * Has functions to login, list fields and create leadº
 *
 * @author   closemarketing
 * @category Functions
 * @package  Gravityforms CRM
 * @version  1.0.0
 */

include_once 'debug.php';

# Enter your domain name , agile email and agile api key
define("AGILE_DOMAIN", "YOUR_AGILE_DOMAIN");  # Example : define("domain","jim");
define("AGILE_USER_EMAIL", "YOUR_AGILE_USER_EMAIL");
define("AGILE_REST_API_KEY", "YOUR_AGILE_REST_API_KEY");


Class GFCRM_LIB
{
    /**
     * Variables
     *
     * @var string
     */
    private $apikey;
    private $hclass;

    /**
     * Construct and intialize
     */
    public function __construct() {

    }

    function curl_wrap($entity, $data, $method, $content_type) {
        if ($content_type == NULL) {
            $content_type = "application/json";
        }
        
        $agile_url = "https://" . AGILE_DOMAIN . ".agilecrm.com/dev/api/" . $entity;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_UNRESTRICTED_AUTH, true);
        switch ($method) {
            case "POST":
                $url = $agile_url;
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                break;
            case "GET":
                $url = $agile_url;
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
                break;
            case "PUT":
                $url = $agile_url;
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                break;
            case "DELETE":
                $url = $agile_url;
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                break;
            default:
                break;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-type : $content_type;", 'Accept : application/json'
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, AGILE_USER_EMAIL . ':' . AGILE_REST_API_KEY);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }
    /**
     * Login
     */
    function login($username, $password, $url, $dbname = NULL) {

    }
    function list_modules($username, $password, $url, $dbname) {
      return array('');
    }
    /**
     * List Fields
     */
    function list_fields($username, $password, $url, $dbname,$module) {

      if($module == 'contacts') {
      // lead fields
       return array(
          //Contact Info
          array( 'name' => 'name',  'label' => __('Name','gravityforms-crm'), 'required'=>true),
          array( 'name' => 'tradename', 'label' => __('Fiscal name','gravityforms-crm'), 'required'=>false),
          array( 'name' => 'code',      'label' => __('VAT No','gravityforms-crm'), 'required'=>false),
          array( 'name' => 'address',    'label' => __('Address','gravityforms-crm'), 'required'=>false),
          array( 'name' => 'mobile',     'label' => __('Mobile','gravityforms-crm'), 'required'=>false),
          array( 'name' => 'city',       'label' => __('City','gravityforms-crm'), 'required'=>false),
          array( 'name' => 'cp',         'label' => __('ZIP','gravityforms-crm'), 'required'=>false),
          array( 'name' => 'province',   'label' => __('Province','gravityforms-crm'), 'required'=>false),
          array( 'name' => 'country',    'label' => __('Country','gravityforms-crm'), 'required'=>false),
          array( 'name' => 'email',      'label' => __('Email','gravityforms-crm'), 'required'=>false),
          array( 'name' => 'phone',      'label' => __('Phone','gravityforms-crm'), 'required'=>false),
          array( 'name' => 'mobile',     'label' => __('Mobile','gravityforms-crm'), 'required'=>false),
          array( 'name' => 'moreinfo',   'label' => __('More Info','gravityforms-crm'), 'required'=>false),
          array( 'name' => 'tags',       'label' => __('Tags','gravityforms-crm'), 'required'=>false),

          //Bank
          array( 'name' => 'sepaiban',          'label' => __('IBAN','gravityforms-crm'), 'required'=>false),
          array( 'name' => 'sepaswift',         'label' => __('SWIFT','gravityforms-crm'), 'required'=>false),
          array( 'name' => 'separef',           'label' => __('SEPA Ref','gravityforms-crm'), 'required'=>false),
          array( 'name' => 'sepadate',          'label' => __('SEPA Date','gravityforms-crm'), 'required'=>false),
       );
      } // module contacts
    }
    /**
     * Create lead
     */
    function create_lead($username, $password, $url, $dbname, $module, $merge_vars) {

      $contact = array();

      foreach($merge_vars as $element) {
        $contact[ $element['name'] ] = (string)$element['value'];
      }

      $result = $this->get('/add/contact',$contact);
      
      return $result['id'];

    }


} //from Class

