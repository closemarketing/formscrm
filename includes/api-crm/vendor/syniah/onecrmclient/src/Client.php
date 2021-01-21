<?php
/**
 * 1CRM CRM system REST+JSON client.
 * PHP Version 5.4
 * @package OneCRM\Client
 * @author Marcus Bointon <marcus@synchromedia.co.uk>
 * @copyright 2016 Synchromedia Limited
 * @license MIT http://opensource.org/licenses/MIT
 * @link https://github.com/Syniah/OneCRMClient
 */

namespace OneCRM;

/**
 * 1CRM CRM system REST+JSON client class.
 * @package OneCRM\Client
 * @author Marcus Bointon <marcus@synchromedia.co.uk>
 * @license MIT http://opensource.org/licenses/MIT
 * @link http://support.sugarcrm.com/02_Documentation/04_Sugar_Developer/Sugar_Developer_Guide_7.5/70_API/Web_Services/40_Legacy_REST/SOAP_APIs/01_REST/
 */
class Client
{
    /**
     * Set to true (via constructor) to enable debug output.
     * Can also inject a callable to override how debug output is handled:
     * e.g. $c = new Client($endpoint, function ($msg) { echo var_export($msg, true) ."\n"; });
     * @var callable|boolean
     * @access protected
     */
    protected $debug = false;

    /**
     * The URL of the 1CRM service to talk to,
     * usually /service/v4/rest.php in your domain.
     * @var string
     * @access protected
     */
    protected $endpoint = '';

    /**
     * A CURL instance.
     * @var resource
     * @access protected
     */
    protected $curl;

    /**
     * The session ID obtained when logging in, needed for subsequent requests.
     * @var string
     * @access protected
     */
    protected $sessionid = '';

    /**
     * The user name last used for login.
     * @var string
     * @access protected
     */
    protected $username = '';

    /**
     * The password last used for login.
     * @var string
     * @access protected
     */
    protected $password = '';

    /**
     * The login function returns user info which is kept in here.
     * @var array
     * @access protected
     */
    protected $userinfo = [];

    /**
     * The login function returns an array of modules info which is kept in here.
     * @var array
     * @access protected
     */
    protected $modules = [];

    /**
     * How long the most recent request took.
     * @var float
     */
    protected $lastRequestDuration = 0.0;

    /**
     * The HTTP response code of the most recent request.
     * @var integer
     */
    protected $lastResponseCode = 0;

    /**
     * @const A version string for this class
     */
    const VERSION = '1.2.1';

    /**
     * Create a new client instance.
     * @param string  $endpoint The URL of the 1CRM service to talk to
     * @param boolean|callable $debug Whether to enable debugging output
     * @throws ConnectionException
     */
    public function __construct($endpoint, $debug = null)
    {
        if (!is_null($debug)) {
            if (is_callable($debug)) {
                $this->debug = $debug;
            } else {
                $this->debug = (boolean)$debug;
            }
        }
        if (!filter_var($endpoint, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED)) {
            throw new ConnectionException('Invalid endpoint URL given.');
        }
        $this->endpoint = $endpoint;
    }

    /**
     * Clean up.
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Log in to the 1CRM service.
     * This doesn't use call() because logging in is different to all other requests.
     * @param string $username The user name
     * @param string $password A password
     * @return array
     * @throws AuthException
     * @throws ModuleException
     */
    public function login($username, $password)
    {
        //If we're logging in again, perhaps as a different user,
        //make sure we clear up any old connection
        $this->close();
        $params = [
            'method'        => 'login',
            'input_type'    => 'JSON',
            'response_type' => 'JSON',
            'rest_data'     => json_encode(
                [
                    'user_auth' => [
                        'user_name' => $username,
                        //Yes, it really does this...
                        'password'  => md5($password),
                    ],
                ]
            )
        ];
        $result = $this->request($params);
        if (!isset($result->id)) {
            $this->close();
            throw new AuthException(
                "Login failure: $result->name - $result->description."
            );
        }
        $this->sessionid = $result->id;
        $this->username = $username;
        $this->password = $password;
        //name_value_list contains a structure describing
        //modules and actions available to this user
        if (!property_exists($result->name_value_list, 'available_modules')) {
            throw new ModuleException('Module information missing');
        }
        //Translate the modules list returned by the API into something more usable
        $this->modules = [];
        foreach ($result->name_value_list->available_modules as $module) {
            if (property_exists($module, 'module_key')
                and property_exists($module, 'module_label')
            ) {
                $this->modules[$module->module_key] = [
                    'name'  => $module->module_key,
                    'label' => $module->module_label
                ];
            }
        }
        //Remove this list from the user info; no need to store it twice
        unset($result->name_value_list->available_modules);
        $this->userinfo = $result->name_value_list;

        return $result;
    }

    /**
     * Fetch an associative array of modules, containing name and label fields, indexed by name.
     * @return array
     * @throws ModuleException
     */
    public function getModules()
    {
        if (empty($this->modules)) {
            throw new ModuleException('No module information available');
        }
        return $this->modules;
    }

    /**
     * Return a formatted list of what modules are available.
     * Lists module label and name.
     * @return string
     */
    public function listModules()
    {
        $out = '';
        foreach ($this->getModules() as $module) {
            $out .= $module['label'] . ' (' . $module['name'] . ")\n";
        }

        return $out;
    }

    /**
     * Check if we have logged in (i.e. that we have a session ID).
     * @throws AuthException
     * @return void
     */
    protected function checkLogin()
    {
        if (empty($this->sessionid)) {
            throw new AuthException('Not logged in.');
        }
    }

    /**
     * Is a module with this name available?
     * @param string $module This is the module name, not the translatable label
     * @return bool
     * @throws DataException
     */
    public function moduleExists($module)
    {
        return array_key_exists($module, $this->modules);
    }

    /**
     * Call a function in a module in the API.
     * @param string $module The module name
     * @param string $method The method name to call
     * @param array $params Additional parameters to pass to the method
     * @return array
     * @throws AuthException
     * @throws ModuleException
     */
    public function call($module, $method, $params = [])
    {
        $this->checkLogin();
        //Check module
        if (!$this->moduleExists($module)) {
            throw new ModuleException('Requested non-existent module.');
        }
        $params['module_name'] = $module;
        $params['session'] = $this->sessionid;
        $postfields = [
            'method'        => $method,
            'input_type'    => 'JSON',
            'response_type' => 'JSON',
            'rest_data'     => json_encode($params)
        ];

        return $this->request($postfields);
    }

    /**
     * Decode a response from the API, simplifying it into an array.
     * This is not especially flexible and may not apply to many calls,
     * but it gives a small example of how to process responses.
     * @param object $response A response object returned by the API
     * @return array
     */
    public function decodeResponse($response)
    {
        $result = [];
        foreach ($response->entry_list as $item) {
            foreach ($item->name_value_list as $field) {
                $result[] = [$field->name => $field->value];
            }
        }
        return $result;
    }

    /**
     * Get the current session ID.
     * @return string
     */
    public function getSessionID()
    {
        return $this->sessionid;
    }

    /**
     * Close the CURL instance.
     * This happens anyway when a script ends, but if we're doing multiple requests
     * over a long period it may be useful to control this manually
     * @return void
     */
    public function close()
    {
        if ($this->curl) {
            curl_close($this->curl);
            $this->curl = null;
        }
        $this->sessionid = '';
    }

    /**
     * Do a generic HTTP request.
     * @param array  $params An array of properties and values to be submitted
     * @param string $type   Which HTTP verb to use: GET, POST, PUT or DELETE, defaults to POST
     * @return mixed
     * @throws DataException
     * @throws ConnectionException
     */
    protected function request($params, $type = 'POST')
    {
        //We can re-use this curl instance without reinitialising it,
        //reducing overhead and permitting keepalive for better performance
        if (!$this->curl) {
            $this->curl = curl_init(); //Note no URL supplied here
            $cookiefile = tempnam(sys_get_temp_dir(), '1crmcookie');
            //These properties remain the same for all requests, so set them now

            //Enable HTTP/2 if it's available
            if (defined('CURL_HTTP_VERSION_2_0')) {
                $http = CURL_HTTP_VERSION_2_0;
            } else {
                $http = CURL_HTTP_VERSION_1_1;
            }
            curl_setopt_array(
                $this->curl,
                [
                    CURLOPT_URL            => $this->endpoint,
                    CURLOPT_FORBID_REUSE   => false,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HEADER         => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_ENCODING       => '',
                    CURLOPT_USERAGENT      => '1CRM PHP client version ' . self::VERSION,
                    CURLOPT_AUTOREFERER    => true,
                    CURLOPT_CONNECTTIMEOUT => 120,
                    CURLOPT_TIMEOUT        => 120,
                    CURLOPT_MAXREDIRS      => 10,
                    CURLOPT_SSL_VERIFYHOST => 2,
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_COOKIEJAR      => $cookiefile,
                    CURLOPT_COOKIEFILE     => $cookiefile,
                    CURLOPT_HTTPHEADER     => ['Expect:'],
                    CURLOPT_VERBOSE        => (true == $this->debug),
                    CURLOPT_HTTP_VERSION   => $http
                ]
            );
        }

        $this->debug($type . ' request params:');
        $this->debug($params);

        //Select HTTP verb
        switch ($type) {
            case 'GET':
                curl_setopt($this->curl, CURLOPT_HTTPGET, true);
                break;
            case 'PUT':
                curl_setopt($this->curl, CURLOPT_PUT, true);
                break;
            case 'DELETE':
                curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
            case 'POST':
            default:
                curl_setopt($this->curl, CURLOPT_POST, true);
                break;
        }

        //Set the request parameters
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $params);
        $starttime = microtime(true);
        //Do the request
        $rawresponse = curl_exec($this->curl);
        $this->lastRequestDuration = microtime(true) - $starttime;
        $this->debug('Request duration: '. $this->lastRequestDuration . ' sec');
        if (!$rawresponse) {
            throw new ConnectionException(
                'Request error: ' .
                curl_errno($this->curl) .
                ': ' .
                curl_error($this->curl)
            );
        }

        $this->debug('Raw response');
        $this->debug($rawresponse);
        //Decode the entire HTTP response
        $response = self::parseResponse($rawresponse);

        //Check HTTP code
        $this->lastResponseCode = $response['code'];
        $this->debug('Response code: ' . $this->lastResponseCode);
        if ('200' != $response['code']) {
            throw new ConnectionException('Response error: ' . $response['code'].' '.$response['body']);
        }

        //Extract and decode the JSON data in the response body
        if (empty($response['body'])) {
            throw new DataException('Empty response.');
        }
        /** @noinspection PhpUsageOfSilenceOperatorInspection */
        $result = @json_decode($response['body']);
        $this->debug($result);
        if (!$result) {
            throw new DataException('Error decoding response.');
        }

        //Return the complete decoded response
        return $result;
    }

    /**
     * Parse an HTTP response into code, headers and body.
     * Returns an array in the following format which varies
     * depending on headers returned
     * @param string $response A full HTTP response including headers and body
     * @return array
     * @author Paul Ebermann <paul.ebermann@esperanto.de>
     * @link http://uk.php.net/manual/en/function.curl-setopt.php
     * @link http://www.webreference.com/programming/php/cookbook/chap11/1/3.html
     */
    protected static function parseResponse($response)
    {
        do {
            // Split response into header and body sections
            list($headers, $body) = explode("\r\n\r\n", $response, 2);
            $header_lines = explode("\r\n", $headers);

            // First line of headers is the HTTP response code
            $matches = [];
            $http_response_line = array_shift($header_lines);
            if (preg_match(
                '@^HTTP/([0-9](\.[0-9])?) ([0-9]{3})@',
                $http_response_line,
                $matches
            )) {
                $code = (integer)$matches[3];
            } else {
                $code = 'Error';
            }
            //Skip 1xx error codes that some MS IIS servers give
        } while (substr($code, 0, 1) == '1');

        // Put the rest of the headers in an array
        $header_array = [];
        foreach ($header_lines as $header_line) {
            list($header, $value) = explode(': ', $header_line, 2);
            $header_array[$header] = $value;
        }
        return ['code' => $code, 'header' => $header_array, 'body' => $body];
    }

    /**
     * Return how long the last request took, in seconds.
     * @return float
     */
    public function getLastRequestDuration()
    {
        return $this->lastRequestDuration;
    }

    /**
     * Get the HTTP response code for the last request.
     * @return int
     */
    public function getLastResponseCode()
    {
        return $this->lastResponseCode;
    }

    /**
     * Escape HTML output.
     * @param string $string The string to escape
     * @return string
     */
    protected function escapeOutput($string)
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Display debug output.
     * If you run via a web browser, this output will appear in
     * your web server's error log, not on your page.
     * @param $msg
     */
    protected function debug($msg)
    {
        if (is_callable($this->debug)) {
            call_user_func($this->debug, $msg);
            return;
        }
        if (!$this->debug) {
            return;
        }
        //Deal with undefined stream constants
        if (!defined('STDERR')) {
            define('STDERR', fopen('php://stderr', 'w'));
        }

        if (!is_string($msg)) {
            $msg = var_export($msg, true);
        }
        fputs(STDERR, gmdate('Y-m-d H:i:s'). "\t". $msg . "\n");
    }
}
