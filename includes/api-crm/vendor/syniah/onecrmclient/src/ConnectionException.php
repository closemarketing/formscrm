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
 * Thrown when curl connections fail: DNS failure, HTTP timeout etc.
 */
class ConnectionException extends Exception
{
}
