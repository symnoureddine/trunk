<?php
/**
 * @package Mediboard\Webservices
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Webservices;

use Exception;
use Ox\Core\Autoload\IShortNameAutoloadable;
use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\Chronometer;
use Ox\Core\CHTTPClient;
use Ox\Core\CMbDT;
use Ox\Core\CMbException;
use Ox\Core\CMbString;
use SoapFault;

/**
 * The factory for the SOAP Clients
 */
class CSOAPClient implements IShortNameAutoloadable {

  /** @var string The type of the client */
  public $type_client;

  /** @var CMbSOAPClient The SOAP client */
  public $client;

  /** @var CSourceSOAP */
  public $_source;

  /**
   * The constructor
   *
   * @param string $type The client type
   *
   * @return self
   */
  function __construct($type = "CMbSOAPClient") {
    $this->type_client = $type;
  }

  /**
   * Test if the WSDL is reachable, and create the object SOAPClient
   *
   * @param string  $rooturl            The url of the WSDL
   * @param string  $login              The login
   * @param string  $password           The password
   * @param string  $type               Exchange type
   * @param array   $options            The options
   * @param boolean $loggable           Log the exchanges
   * @param string  $stream_context     HTTP method (GET, POST, HEAD, PUT, ...)
   * @param string  $local_cert         Path of the certifacte
   * @param string  $passphrase         Pass phrase for the certificate
   * @param boolean $safe_mode          Safe mode
   * @param boolean $verify_peer        Require verification of SSL certificate used
   * @param string  $cafile             Location of Certificate Authority file on local filesystem
   * @param String  $wsdl_external      Location of external wsdl
   * @param int     $socket_timeout     Default timeout (in seconds) for socket based streams
   * @param int     $connection_timeout Default timeout (in seconds) for connection
   * @param string  $location_for_port  Location for port
   *
   * @throws CMbException
   *
   * @return CMbSOAPClient
   */
  public function make(
      $rooturl,
      $login = null,
      $password = null,
      $type = null,
      $options = array(),
      $loggable = null,
      $stream_context = null,
      $local_cert = null,
      $passphrase = null,
      $safe_mode = false,
      $verify_peer = false,
      $cafile = null,
      $wsdl_external = null,
      $socket_timeout = null,
      $connection_timeout = null,
      $location_for_port = null
  ) {
    if (($login && $password) || (array_key_exists('login', $options) && array_key_exists('password', $options))) {
      $login = $login ? $login : $options['login'];
      if (preg_match('#\%u#', $rooturl)) {
        $rooturl = str_replace('%u', $login, $rooturl);
      }
      else {
        $options['login'] = $login;
      }

      $password = $password ? $password : $options['password'];
      if (preg_match('#\%p#', $rooturl)) {
        $rooturl = str_replace('%p', $password, $rooturl);
      }
      else {
        $options['password'] = $password;
      }
    }

    $check_option["local_cert"]  = $local_cert;
    $check_option["ca_cert"]     = $cafile;
    $check_option["passphrase"]  = $passphrase;
    $check_option["username"]    = $login;
    $check_option["password"]    = $password;
    $check_option['verify_peer'] = $verify_peer;

    if (!$safe_mode) {
      if (!CHTTPClient::checkUrl($rooturl, $check_option)) {
        throw new CMbException("CSourceSOAP-unreachable-source", $rooturl);
      }
    }

    switch ($this->type_client) {
      case 'CMbSOAPClient':
      default:
        $this->client = new CMbSOAPClient(
          $rooturl, $type, $options, $loggable, $local_cert, $passphrase, $safe_mode, $verify_peer, $cafile, $wsdl_external,
          $socket_timeout, $connection_timeout, $location_for_port
        );
        break;
    }

    if (!$this->client) {
      throw new CMbException("CSourceSOAP-soapclient-impossible");
    }

    return $this->client;
  }

  /**
   * Calls a SOAP function
   *
   * @param string $function_name  The name of the SOAP function to call
   * @param array  $arguments      An array of the arguments to pass to the function
   * @param array  $options        An associative array of options to pass to the client
   * @param mixed  $input_headers  An array of headers to be sent along with the SOAP request
   * @param array  $output_headers If supplied, this array will be filled with the headers from the SOAP response
   *
   * @throws Exception|SoapFault
   *
   * @return mixed SOAP functions may return one, or multiple values
   */
  public function __soapCall($function_name, $arguments, $options = null, $input_headers = null, &$output_headers = null) {
    return $this->call($function_name, $arguments, $options, $input_headers, $output_headers);
  }

  /**
   * Calls a SOAP function
   *
   * @param string $function_name  The name of the SOAP function to call
   * @param array  $arguments      An array of the arguments to pass to the function
   * @param array  $options        An associative array of options to pass to the client
   * @param mixed  $input_headers  An array of headers to be sent along with the SOAP request
   * @param array  $output_headers If supplied, this array will be filled with the headers from the SOAP response
   *
   * @throws Exception|SoapFault
   *
   * @return mixed SOAP functions may return one, or multiple values
   */
  public function call($function_name, $arguments, $options = null, $input_headers = null, &$output_headers = null) {
    $client = $this->client;

    if (!is_array($arguments)) {
      $arguments = array($arguments);
    }

    /* @todo Lors d'un appel d'une méthode RPC le tableau $arguments contient un élement vide array( [0] => )
     * posant problème lors de l'appel d'une méthode du WSDL sans argument */
    if (isset($arguments[0]) && empty($arguments[0])) {
      $arguments = array();
    }

    if ($client->flatten && isset($arguments[0]) && !empty($arguments[0])) {
      $arguments = $arguments[0];
    }

    $output = null;

    $echange_soap = new CEchangeSOAP();

    $echange_soap->date_echange = CMbDT::dateTime();
    $echange_soap->emetteur     = CAppUI::conf("mb_id");
    $echange_soap->destinataire = $client->wsdl_url;
    $echange_soap->type         = $client->type_echange_soap;
    $echange_soap->source_id    = $this->_source->_id;
    $echange_soap->source_class = $this->_source->_class;

    $url                            = parse_url($client->wsdl_url);
    $path                           = explode("/", $url['path']);
    $echange_soap->web_service_name = end($path);

    $echange_soap->function_name = $function_name;

    // Truncate input and output before storing
    $arguments_serialize = array_map_recursive(array(CSOAPClient::class, "truncate"), $arguments);

    $echange_soap->input = serialize($arguments_serialize);

    if ($client->loggable) {
      $echange_soap->store();
    }

    CApp::$chrono->stop();
    $chrono = new Chronometer();
    $chrono->start();

    $this->_source->startCallTrace();
    try {
      $output = $client->call($function_name, $arguments, $options, $input_headers, $output_headers);
        $this->_source->stopCallTrace();

      if (!$client->loggable) {
        CApp::$chrono->start();

        return $output;
      }
    }
    catch (SoapFault $fault) {
        $this->_source->stopCallTrace();
      // trace
      if (CAppUI::conf("webservices trace")) {
        $client->getTrace($echange_soap);
      }
      $chrono->stop();
      $echange_soap->response_datetime = CMbDT::dateTime();
      $echange_soap->output            = $fault->faultstring;
      $echange_soap->soapfault         = 1;
      $echange_soap->response_time     = $chrono->total;
      $echange_soap->store();

      CApp::$chrono->start();

      throw $fault;
    }

    $chrono->stop();
    CApp::$chrono->start();

    // trace
    if (CAppUI::conf("webservices trace")) {
      $client->getTrace($echange_soap);
    }

    // response time
    $echange_soap->response_time     = $chrono->total;
    $echange_soap->response_datetime = CMbDT::dateTime();

    if ($echange_soap->soapfault != 1) {
      $echange_soap->output = serialize(array_map_recursive(array(CSOAPClient::class, "truncate"), $output));
    }
    $echange_soap->store();

    return $output;
  }

  /**
   * Truncate a string to a given maximum length
   *
   * @param string $string The string to truncate
   *
   * @return string The truncated string
   */
  static public function truncate($string) {
    if (!is_string($string)) {
      return $string;
    }

    // Truncate
    $max    = 1024;
    $result = CMbString::truncate($string, $max);

    // Indicate true size
    $length = strlen($string);
    if ($length > 1024) {
      $result .= " [$length bytes]";
    }

    return $result;
  }

  /**
   * Return the list of the operation of the WSDL
   *
   * @return array An array who contains all the operation of the WSDL
   */
  public function getFunctions() {
    return $this->client->__getFunctions();
  }

  /**
   * Returns an array of functions described in the WSDL for the Web service.
   *
   * @return array The array of SOAP function prototype
   */
  public function getTypes() {
    return $this->client->__getTypes();
  }

  /**
   * Defines headers to be sent along with the SOAP requests
   *
   * @param array $soapheaders The headers to be set
   *
   * @return boolean True on success or False on failure
   */
  public function setHeaders($soapheaders) {
    $this->client->setHeaders($soapheaders);
  }

  /**
   * Set the namespaces that will be used in the SOAP Envelope.
   * If a namespace is already present, it's prefix will be replaced by the one specified
   *
   * @param array $namespaces An array of namespaces (prefix => uri)
   *
   * @return void
   */
  public function setNamespaces($namespaces = array()) {
    $this->client->setNamespaces($namespaces);
  }

  /**
   * Check service availability
   *
   * @throws CMbException
   *
   * @return void
   */
  public function checkServiceAvailability() {
  }
}
