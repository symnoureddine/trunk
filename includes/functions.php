<?php
/**
 * @package Mediboard\Includes
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CLogger;
use Ox\Core\CMbObject;
use Ox\Core\CMbString;
use Ox\Core\CView;

/**
 * General purpose functions that haven't been namespaced (yet).
 */
/**
 * Returns the CMbObject with given GET params keys, if it doesn't exist, a redirect is made
 *
 * @param string $class_key The class name of the object
 * @param string $id_key    The object ID
 * @param string $guid_key  The object GUID (classname-id)
 * @param bool   $session   From session ?
 *
 * @return CMbObject The object loaded or nothing
 *
 * @throws Exception
 */
function mbGetObjectFromGet($class_key, $id_key, $guid_key = null, $session = false)
{
    $object_class = $class_key ? CView::get($class_key, "str", $session) : null;
    $object_id    = $id_key ? CView::get($id_key, "ref class|$object_class", $session) : null;
    $object_guid  = "$object_class-$object_id";

    if ($guid_key) {
        $object_guid = CView::get($guid_key, "str default|$object_guid", $session);
    }

    $object = CMbObject::loadFromGuid($object_guid);

    // Redirection
    if (!$object || !$object->_id) {
        CAppUI::notFound($object_guid);
    }

    return $object;
}

/**
 * String to bool swiss knife
 *
 * @param mixed $value Any value, preferably string
 *
 * @return bool
 */
function toBool($value)
{
    if (!$value) {
        return false;
    }

    return $value === true || preg_match('/^on|1|true|yes$/i', $value);
}

/**
 * URL to the mediboard.org documentation page
 *
 * @param string $module Module name
 * @param string $action Action name
 *
 * @return string The URL to the requested page
 * @throws Exception
 */
function mbPortalURL($module, $action = null)
{
    if ($module == "tracker") {
        return CAppUI::conf("issue_tracker_url");
    }

    $url = CAppUI::conf("help_page_url");
    if (!$url) {
        return "";
    }

    $pairs = [
        "%m" => $module,
        "%a" => $action,
    ];

    return strtr($url, $pairs);
}

/**
 * Set memory limit alternative with a minimal value approach
 * Shoud *always* be used
 *
 * @param string $limit Memory limit with ini_set() syntax
 *
 * @return string The old value on success, false on failure
 * @TODO : DELETE if not called anymore (check for all modules)
 */
function set_min_memory_limit($limit)
{
    return CApp::setMemoryLimit($limit);
}

/**
 * Check whether a method is overridden in a given class
 *
 * @param mixed  $class  The class or object
 * @param string $method The method name
 *
 * @return bool
 * @throws Exception
 *
 */
function is_method_overridden($class, $method)
{
    $reflection = new ReflectionMethod($class, $method);

    return $reflection->getDeclaringClass()->getName() == $class;
}

/**
 * Strip slashes recursively if value is an array
 *
 * @param mixed $value The value to be stripped
 *
 * @return mixed the stripped value
 **/
function stripslashes_deep($value)
{
    return is_array($value) ?
        array_map("stripslashes_deep", $value) :
        stripslashes($value);
}

/**
 * Copy the hash array content into the object as properties
 * Only existing properties of are filled, when defined in hash
 *
 * @param array   $hash   The input hash
 * @param object &$object The object to feed
 * @param bool    $strict Do not assign non existing properties
 *
 * @return void
 **/
function bindHashToObject($hash, &$object, $strict = true)
{
    foreach ($hash as $k => $v) {
        if (property_exists($object, $k)) {
            $object->$k = $v;
        } elseif (!$strict) {
            $object->$k = $v;
        }
    }
}

/**
 * Check wether a URL exists (200 HTTP Header)
 *
 * @param string $url    URL to check
 * @param string $method HTTP method (GET, POST, HEAD, PUT, ...)
 *
 * @return bool
 */
function url_exists($url, $method = null)
{
    $old = ini_set('default_socket_timeout', 5);

    if ($method) {
        // By default get_headers uses a GET request to fetch the headers.
        // If you want to send a HEAD request instead,
        // you can change method with a stream context
        stream_context_set_default(
            [
                'http' => [
                    'method' => $method,
                ],
            ]
        );
    }

    $headers = @get_headers($url);
    ini_set('default_socket_timeout', $old);

    return (preg_match("|200|", $headers[0]));
}

/**
 * Forge an HTTP POST query
 *
 * @param string $url  Destination URL
 * @param mixed  $data Array or object containing properties
 *
 * @return bool
 */
function http_request_post($url, $data)
{
    $data_url    = http_build_query($data);
    $data_length = strlen($data_url);
    $options     = [
        "https" => [
            "method"  => "POST",
            "header"  => [
                "Content-Type: application/x-www-form-urlencoded",
                "Content-Length: $data_length",
                "User-Agent: " . $_SERVER["HTTP_USER_AGENT"],
            ],
            "content" => $data_url,
        ],
    ];

    $context = stream_context_create($options);
    $content = file_get_contents($url, false, $context);

    return $content;
}

/**
 * Check response time from a web server
 *
 * @param string $url  Server URL
 * @param string $port Server port
 *
 * @return int Response time in milliseconds
 */
function url_response_time($url, $port)
{
    $parse_url = parse_url($url);
    if (isset($parse_url["port"])) {
        $port = $parse_url["port"];
    }

    $url = isset($parse_url["host"]) ? $parse_url["host"] : $url;

    $starttime = microtime(true);
    $file      = @fsockopen($url, $port, $errno, $errstr, 5);
    $stoptime  = microtime(true);

    if (!$file) {
        $response_time = -1;  // Site is down
    } else {
        fclose($file);
        $response_time = ($stoptime - $starttime) * 1000;
        $response_time = floor($response_time);
    }

    return $response_time;
}

/**
 * Build a url string based on components in an array
 * (see PHP parse_url() documentation)
 *
 * @param array $components Components, as of parse_url
 *
 * @return string
 */
function make_url($components)
{
    $url = $components["scheme"] . "://";

    if (isset($components["user"])) {
        $url .= $components["user"] . ":" . $components["pass"] . "@";
    }

    $url .= $components["host"];

    if (isset($components["port"])) {
        $url .= ":" . $components["port"];
    }

    $url .= $components["path"];

    if (isset($components["query"])) {
        $url .= "?" . $components["query"];
    }

    if (isset($components["fragment"])) {
        $url .= "#" . $components["fragment"];
    }

    return $url;
}

/**
 * Check wether a IP address is in intranet-like form
 *
 * @param string $ip IP address to check
 *
 * @return bool
 */
function is_intranet_ip($ip)
{
    // ipv6 en local
    if ($ip === '::1' || $ip === '0:0:0:0:0:0:0:1') {
        return true;
    }

    $ip = explode('.', $ip);

    return
        ($ip[0] == 127) ||
        ($ip[0] == 10) ||
        ($ip[0] == 172 && $ip[1] >= 16 && $ip[1] < 32) ||
        ($ip[0] == 192 && $ip[1] == 168);
}

/**
 * Retrieve a server value from multiple sources
 *
 * @param string $key Value key
 *
 * @return string|null
 */
function get_server_var($key)
{
    if (isset($_SERVER[$key])) {
        return $_SERVER[$key];
    }

    if (isset($_ENV[$key])) {
        return $_ENV[$key];
    }

    if (getenv($key)) {
        return getenv($key);
    }

    if (function_exists('apache_getenv') && apache_getenv($key, true)) {
        return apache_getenv($key, true);
    }

    return null;
}

/**
 * Get browser remote IPs using most of available methods
 *
 * @param bool $remove_scope_id Remove the Scope ID of the IP addresses
 *
 * @return array Array with proxy, client and remote keys as IP adresses
 */
function get_remote_address($remove_scope_id = true)
{
    $address = [
        "proxy"  => null,
        "client" => null,
        "remote" => null,
    ];

    $address["client"] = ($client = get_server_var("HTTP_CLIENT_IP")) ? $client : get_server_var("REMOTE_ADDR");
    $address["remote"] = $address["client"];

    $forwarded = [
        "HTTP_X_FORWARDED_FOR",
        "HTTP_FORWARDED_FOR",
        "HTTP_X_FORWARDED",
        "HTTP_FORWARDED",
        "HTTP_FORWARDED_FOR_IP",
        "X_FORWARDED_FOR",
        "FORWARDED_FOR",
        "X_FORWARDED",
        "FORWARDED",
        "FORWARDED_FOR_IP",
    ];

    $client = null;

    foreach ($forwarded as $name) {
        if ($client = get_server_var($name)) {
            break;
        }
    }

    if ($client) {
        $address["proxy"]  = $address["client"];
        $address["client"] = $client;
    }

    // To handle weird IPs sent by iPhones, in the form "10.10.10.10, 10.10.10.10"
    $proxy  = explode(",", $address["proxy"]);
    $client = explode(",", $address["client"]);
    $remote = explode(",", $address["remote"]);

    $address["proxy"]  = reset($proxy);
    $address["client"] = reset($client);
    $address["remote"] = reset($remote);

    if ($remove_scope_id) {
        foreach ($address as $_type => $_address) {
            if ($_address && ($pos = strpos($_address, "%"))) {
                $address[$_type] = substr($_address, 0, $pos);
            }
        }
    }

    return $address;
}

/**
 * CRC32 alternative handling 32bit platform limitations
 *
 * @param string $data The data
 *
 * @return int CRC32 checksum
 */
function mb_crc32($data)
{
    $crc = crc32($data);

    // if 32bit platform
    if (PHP_INT_MAX <= pow(2, 31) - 1 && $crc < 0) {
        $crc += pow(2, 32);
    }

    return $crc;
}

/**
 * Traces variable using preformated text prefixed with a label
 *
 * @param mixed  $var   Data to dump
 * @param string $label Add an optional label
 * @param bool   $log   Log to file or echo data
 *
 * @return string|int The processed log or the size of the data written in the log file
 **/
function mbTrace($var, $label = null, $log = false)
{
    $var = print_r($var, true);

    $export = CMbString::htmlSpecialChars($var);
    $time   = date("Y-m-d H:i:s");
    $msg    = "\n<pre>[$time] $label: $export</pre>";

    echo $msg;
}

/**
 * @param mixed  $var   Data to dump
 * @param string $label Add an optional label
 *
 * @return int The size of the data written in the log file
 **@throws Exception
 *
 * @deprecated use CApp::log();
 *             Log shortcut to mbTrace
 */
function mbLog($var, $label = null)
{
    if ($label) {
        $message = $label;
        $data    = $var;
    } else {
        if (is_scalar($var)) {
            $message = $var;
            $data    = null;
        } else {
            $message = "Log from mbLog";
            $data    = $var;
        }
    }

    return CApp::log($message, $data);
}

/**
 * @alias for CApp::log
 *
 * @param mixed $message Message to log
 * @param mixed $data    Data to add to the log
 * @param int   $level   Use CLogger::const
 *
 * @return bool
 * @throws Exception
 */
function l($message, $data = null, $level = CLogger::LEVEL_INFO)
{
    return CApp::log($message, $data, $level);
}

/**
 * @alias for CApp::dump
 *
 * @param mixed  $var anything to dump in dev toolbar
 * @param string $msg comment about the dump
 *
 */
function d($var, $msg = null)
{
    CApp::dump($var, $msg);
}


function dt()
{
    d((new Exception())->getTraceAsString());
}


/**
 * Hide password param in HTTP param string
 *
 * @param string $str HTTP params
 *
 * @return string Sanitized HTTP
 **/
function hideUrlPassword($str)
{
    return preg_replace("/(.*)password=([^&]+)(.*)/", '$1password=***$3', $str);
}

/**
 * Hide some params according to regexp
 *
 * @param array &$params Params to check
 *
 * @return void
 */
function filterInput(&$params)
{
    $patterns = [
        "/password|passphrase|pwd/i",
        "/login/i",
    ];

    $replacements = [
        ["/.*/", "***"],
        ["/([^:]*):(.*)/i", "$1:***"],
    ];

    // We replace passwords with a mask
    foreach ($params as $_type => $_params) {
        foreach ($_params as $_key => $_value) {
            foreach ($patterns as $_k => $_pattern) {
                if (!empty($_value) && preg_match($_pattern, $_key)) {
                    $params[$_type][$_key] = preg_replace($replacements[$_k][0], $replacements[$_k][1], $_value);
                }
            }
        }
    }
}
