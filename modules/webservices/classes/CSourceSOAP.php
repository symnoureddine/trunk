<?php
/**
 * @package Mediboard\Webservices
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Webservices;

use Exception;
use Ox\Core\CAppUI;
use Ox\Core\CHTTPClient;
use Ox\Core\CMbArray;
use Ox\Core\CMbException;
use Ox\Core\CMbObjectSpec;
use Ox\Mediboard\System\CExchangeSource;
use SimpleXMLElement;
use SoapFault;
use SoapHeader;

/**
 * Class CSourceSOAP
 * Source SOAP
 */
class CSourceSOAP extends CExchangeSource
{
    // Source type
    public const TYPE = 'soap';

    // DB Table key
    public $source_soap_id;

    // DB Fields
    public $wsdl_external;
    public $evenement_name;
    public $single_parameter;
    public $encoding;
    public $stream_context;
    public $type_soap;
    public $local_cert;
    public $passphrase;
    public $iv_passphrase;
    public $safe_mode;
    public $return_mode;
    public $soap_version;
    public $xop_mode;
    public $use_tunnel;
    public $socket_timeout;
    public $connection_timeout;
    public $feature;
    public $port_name;

    // Options de contexte SSL
    public $verify_peer;
    public $cafile;

    /** @var CSOAPClient */
    protected $_soap_client;

    public $_headerbody = array();

    /** @var array An array of namespaces (prefix => uri) */
    public $_namespaces = array();

    /** @var string The last request send by the source */
    public $_last_request = null;

    /** @var string The last response received by the source */
    public $_last_response;

    /**
     * Initialize object specification
     *
     * @return CMbObjectSpec the spec
     */
    function getSpec()
    {
        $spec        = parent::getSpec();
        $spec->table = 'source_soap';
        $spec->key   = 'source_soap_id';

        return $spec;
    }

    /**
     * Get properties specifications as strings
     *
     * @return array
     * @see parent::getProps()
     *
     */
    function getProps()
    {
        $specs = parent::getProps();

        $specs["wsdl_external"]      = "str";
        $specs["evenement_name"]     = "str";
        $specs["single_parameter"]   = "str";
        $specs["encoding"]           = "enum list|UTF-8|ISO-8859-1|ISO-8859-15 default|UTF-8";
        $specs["type_soap"]          = "enum list|CMbSOAPClient default|CMbSOAPClient notNull";
        $specs["iv_passphrase"]      = "str show|0 loggable|0";
        $specs["safe_mode"]          = "bool default|0";
        $specs["return_mode"]        = "enum list|normal|raw|file";
        $specs["soap_version"]       = "enum list|SOAP_1_1|SOAP_1_2 default|SOAP_1_1 notNull";
        $specs["xop_mode"]           = "bool default|0";
        $specs["use_tunnel"]         = "bool default|0";
        $specs["socket_timeout"]     = "num min|1";
        $specs["connection_timeout"] = "num min|1";
        $specs["feature"]            = "enum list|SOAP_SINGLE_ELEMENT_ARRAYS|SOAP_USE_XSI_ARRAY_TYPE|SOAP_WAIT_ONE_WAY_CALLS";
        $specs["port_name"]          = "str";

        $specs["local_cert"] = "str";
        $specs["passphrase"] = "password show|0 loggable|0";

        $specs["verify_peer"] = "bool default|0";
        $specs["cafile"]      = "str";

        $specs["stream_context"] = "str";

        return $specs;
    }

    /**
     * Calls a SOAP function
     *
     * @param string $function  Function name
     * @param array  $arguments Arguments
     *
     * @return void
     */
    function __call($function, array $arguments = array())
    {
        $this->setData(reset($arguments));
        $this->send($function);
    }

    /**
     * Encrypt fields
     *
     * @return void
     */
    function updateEncryptedFields()
    {
        if ($this->passphrase === "") {
            $this->passphrase = null;
        } else {
            if (!empty($this->passphrase)) {
                $this->passphrase = $this->encryptString($this->passphrase, "iv_passphrase");
            }
        }
    }

    /**
     * Set SOAP header
     *
     * @param string $namespace      The namespace of the SOAP header element.
     * @param string $name           The name of the SoapHeader object
     * @param array  $data           A SOAP header's content. It can be a PHP value or a SoapVar object
     * @param bool   $mustUnderstand Value must understand
     * @param null   $actor          Value of the actor attribute of the SOAP header element
     *
     * @return void
     */
    function setHeaders($namespace, $name, $data, $mustUnderstand = false, $actor = null)
    {
        if ($actor) {
            $this->_headerbody[] = new SoapHeader($namespace, $name, $data, $mustUnderstand, $actor);
        } else {
            $this->_headerbody[] = new SoapHeader($namespace, $name, $data);
        }
    }

    /**
     * Set the given namespaces
     *
     * @param array $namespaces Array in the format prefix => uri
     *
     * @return void
     */
    public function setNamespaces($namespaces = array())
    {
        $this->_namespaces = array_merge($this->_namespaces, $namespaces);
    }

    /**
     * Set the given namespace
     *
     * @param string $prefix The prefix to use for the namespace
     * @param string $uri    The uri (or url) of the namespace
     *
     * @return void
     */
    public function setNamespace($prefix, $uri)
    {
        $this->_namespaces[$prefix] = $uri;
    }

    /**
     * @return CSOAPClient
     */
    function getSoapClient()
    {
        return $this->_soap_client;
    }

    /**
     * Send SOAP event
     *
     * @param string $event_name Event name
     * @param bool   $flatten    Flat args
     *
     * @return bool|void
     * @throws CMbException
     * @throws SoapFault
     */
    function send($event_name = null, $flatten = false)
    {
        if (!$this->_id) {
            throw new CMbException("CSourceSOAP-no-source", $this->name);
        }

        if (!$event_name) {
            $event_name = $this->evenement_name;
        }

        if (!$event_name) {
            throw new CMbException("CSourceSOAP-no-evenement", $this->name);
        }

        if ($this->single_parameter) {
            $this->_data = array("$this->single_parameter" => $this->_data);
        }

        if (!$this->_data) {
            $this->_data = array();
        }

        $options = array(
            "encoding"    => $this->encoding,
            "return_mode" => "normal",
            'user_agent'  => 'PhpSoapClient'
        );

        if ($this->return_mode) {
            $options["return_mode"] = $this->return_mode;
        }

        if ($this->soap_version) {
            $options["soap_version"] = constant($this->soap_version);
        }

        if ($this->xop_mode) {
            $options["xop_mode"] = true;
        }

        if ($this->use_tunnel) {
            $options["use_tunnel"] = true;
        }

        if ($this->feature) {
            $options["features"] = constant($this->feature);
        }

        $location_for_port = null;
        if ($this->port_name) {
            $location_for_port = $this->getLocationForPort($this->host, $this->port_name);
        }

        $soap_client          = new CSOAPClient($this->type_soap);
        $this->_soap_client   = $soap_client;
        $soap_client->_source = $this;

        $password   = $this->getPassword();
        $passphrase = $this->getPassword($this->passphrase, "iv_passphrase");

        $soap_client->make(
            $this->host, $this->user, $password, $this->type_echange, $options, null, $this->stream_context, $this->local_cert, $passphrase,
            $this->safe_mode, $this->verify_peer, $this->cafile, $this->wsdl_external, $this->socket_timeout, $this->connection_timeout,
            $location_for_port
        );

        if ($soap_client->client->soap_client_error) {
            throw new CMbException("CSourceSOAP-unreachable-source", $this->name);
        }

        // Applatissement du tableau $arguments qui contient un élément vide array([0] => ...) ?
        $soap_client->client->flatten = $flatten;

        // Définit un ent-ête à utiliser dans les requêtes ?
        if ($this->_headerbody) {
            $soap_client->setHeaders($this->_headerbody);
        }

        if ($this->_namespaces) {
            $soap_client->setNamespaces($this->_namespaces);
        }

        // Aucun log à produire ?
        $soap_client->client->loggable = $this->loggable;

        $this->_acquittement = $soap_client->call($event_name, $this->_data);
        if (!$this->_acquittement) {
            return true;
        }

        if (is_object($this->_acquittement)) {
            $acquittement = (array)$this->_acquittement;
            if (count($acquittement) == 1) {
                $this->_acquittement = reset($acquittement);
            }
        }

        return true;
    }

    /**
     * If source is reachable
     *
     * @return bool|void
     */
    function isReachableSource()
    {
        $check_option["local_cert"] = $this->local_cert;
        $check_option["ca_cert"]    = $this->cafile;
        $check_option["passphrase"] = $this->getPassword($this->passphrase, "iv_passphrase");
        $check_option["username"]   = $this->user;
        $check_option["password"]   = $this->getPassword();

        if (!$this->safe_mode) {
            if (!CHTTPClient::checkUrl($this->host, $check_option, true)) {
                $this->_reachable = 0;
                $this->_message   = CAppUI::tr("CSourceSOAP-unreachable-source", $this->host);

                return false;
            }
        }

        return true;
    }

    /**
     * If is authentificate
     *
     * @return bool|void
     */
    function isAuthentificate()
    {
        $options = array(
            "encoding"   => $this->encoding,
            'user_agent' => 'PhpSoapClient'
        );

        try {
            $soap_client = new CSOAPClient($this->type_soap);

            $password = $this->getPassword();
            $soap_client->make(
                $this->host, $this->user, $password, $this->type_echange, $options,
                null, null, $this->local_cert, $this->passphrase, false, $this->verify_peer,
                $this->cafile, $this->wsdl_external
            );

            $soap_client->checkServiceAvailability();
        } catch (Exception $e) {
            $this->_reachable = 1;
            $this->_message   = $e->getMessage();

            return false;
        }

        return true;
    }

    /**
     * Get response time
     *
     * @return int
     */
    function getResponseTime()
    {
        return $this->_response_time = url_response_time($this->host, 80);
    }

    /**
     * Get location for port
     *
     * @param string $wsdl     WSDL
     * @param string $portName Port name
     *
     * @return bool|string
     */
    function getLocationForPort($wsdl, $portName)
    {
        $file = file_get_contents($wsdl);

        $xml = new SimpleXmlElement($file);

        $query   = "wsdl:service/wsdl:port[@name='$portName']/soap:address";
        $address = $xml->xpath($query);
        if (!empty($address)) {
            return (string)CMbArray::get(CMbArray::get($address, 0), "location");
        }

        return false;
    }

    /**
     * Get the last request and response from the soap client.
     *
     * @return void
     */
    public function getTraces()
    {
        $this->_last_request  = $this->_soap_client->client->__getLastRequest();
        $this->_last_response = $this->_soap_client->client->__getLastResponse();
    }
}
