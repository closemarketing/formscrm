# 1CRM REST Interface PHP Client Class

[1CRM](http://1crm.com/) is a powerful open-source CRM system originally derived from SugarCRM, and it retains compatibility with its legacy REST API. This class provides a wrapper to make connecting and calling API methods easier, faster, safer, and more efficient. SugarCRM provides its own PHP client class that will work when talking to SugarCRM or 1CRM, however, it's poorly designed, outdated and inefficient, so I wrote this class as a better alternative. 

1CRM supports the [SugarCRM v4 REST API](http://support.sugarcrm.com/02_Documentation/04_Sugar_Developer/Sugar_Developer_Guide_7.5/70_API/Web_Services/40_Legacy_REST/SOAP_APIs/01_REST/), not the newer v10 API. Prior to v10, SugarCRM's REST interface is a misnomer; there's nothing REST-like about it. It's simply a wrapper around the SOAP API that supports JSON instead of XML for requests and responses, so don't expect to be able to use POST/PUT/GET/DELETE HTTP verbs!

Because of this, the best way to discover available functions and parameters is to browse the SOAP WSDL available at `/soap.php` in your 1CRM installation. All the functions and parameters are described there and may be passed to the `call` function as appropriate.

The class is compatible with TLS (which you should be using anyway!), HTTP/2, SPDY, IPv4 and IPv6, and makes use of HTTP compression and keep-alive for greatest efficiency. Certificate verification is enabled, so you will get errors if you try to use a self-signed, invalid, or expired TLS certificate.

## Requirements
The class requires that you are running PHP 5.4 or later, and have the PHP `curl` extension enabled. If you are serving your site over HTTP/2, and have a recent enough CURL library with nghttp2 support compiled into PHP, this client will use HTTP/2.

## Usage

This class is available in [composer](https://getcomposer.org) via [packagist](https://packagist.org/packages/syniah/onecrmclient); either run `composer require syniah/onecrmclient "~1.0"` or add this line to your `composer.json` file manually, then run `composer update`:

    "syniah/onecrmclient": "~1.0"

The class is structured according to the PSR-4 convention, uses the PSR-2 coding standard, is compatible with PHP 5.4 and later, and uses the `OneCRM` namespace.

```php
<?php
require 'vendor/autoload.php';

$c = new OneCRM\Client('https://1crm.example.com/service/v4/rest.php', false);
try {
    $c->login('demo', 'demo');
    echo $c->listModules();
    //Find the first 10 accounts
    $response = $c->call(
        'Accounts',
        'get_entry_list',
        array('select_fields' => array('id', 'name'), 'max_results' => 10)
    );
    foreach ($response->entry_list as $item) {
        foreach ($item->name_value_list as $field) {
            echo $field->name, ': ', $field->value . " ";
        }
        echo "\n";
    }
} catch (OneCRM\Exception $e) {
    echo 'An error occurred: '. get_class($e) . ': ' . $e->getMessage();
}
```

## Debugging
There is a built-in debug facility that outputs events, data structures and timings to STDERR - just pass `true` as the second parameter to the constructor: `$c = new Client($url, true);`. Because this goes to `STDERR`, this will not usually appear if you're running via a web server, but will appear in your server's error log; debug output will be visible if you're running via a CLI. 

Alternatively you can inject your own debug facility by passing in a callable that accepts a single parameter containing the debug item (which may not be a string), which you can then handle as you like, for example:

```php
$c = new OneCRM\Client($endpoint, function ($msg) {
    echo var_export($msg, true) . "\n";
});
```

## Contributing
Please submit any bug reports or pull requests to [the GitHub project](https://github.com/Syniah/OneCRMClient).

## Author
This class was written by Marcus Bointon of [Synchromedia Limited](https://www.syniah.com/). Synchromedia has been a UK partner for 1CRM since 2006.

## License
This code is distributed under the MIT open-source license; see the LICENSE file for details.