<?php
/**
 * @package Mediboard\Hl7
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Hl7;

use Exception;
use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\Chronometer;
use Ox\Core\CMbArray;
use Ox\Core\CMbException;
use Ox\Mediboard\System\CSocketSource;

class CSourceMLLP extends CSocketSource
{
    // Source type
    public const TYPE = 'mllp';

    /**
     * Start of an MLLP message
     */
    const TRAILING = "\x0B";     // \v Vertical Tab (VT, decimal 11)

    /**
     * End of an MLLP message
     */
    const LEADING = "\x1C\x0D"; // File separator (FS, decimal 28), \r Carriage return (CR, decimal 13)

    public $source_mllp_id;

    public $ssl_enabled;

    /** @var int Délai d'expiration, en secondes, pour l'appel système connect() */
    public $timeout_socket;
    /** @var int Délai d'expiration lors de la lecture/écriture de données via un socket */
    public $timeout_period_stream;
    /** @var int Configure le mode bloquant d'un flux */
    public $set_blocking;

    /** @var CExchangeMLLP $_exchange_mllp MLLP exchange */
    public $_exchange_mllp;

    /** @var Chronometer */
    public $chrono;

    /**
     * @see parent::getSpec()
     */
    public function getSpec()
    {
        $spec        = parent::getSpec();
        $spec->table = 'source_mllp';
        $spec->key   = 'source_mllp_id';

        return $spec;
    }

    /**
     * @see parent::getProps()
     */
    public function getProps()
    {
        $specs                          = parent::getProps();
        $specs["port"]                  = "num default|7001";
        $specs["ssl_enabled"]           = "bool notNull default|0";
        $specs["ssl_certificate"]       = "str";
        $specs["ssl_passphrase"]        = "password show|0 loggable|0";
        $specs["iv_passphrase"]         = "str show|0 loggable|0";
        $specs["timeout_socket"]        = "num default|5";
        $specs["timeout_period_stream"] = "num";
        $specs["set_blocking"]          = "bool default|0";

        return $specs;
    }

    /**
     * @see parent::updateFormFields()
     */
    public function updateFormFields()
    {
        parent::updateFormFields();
        $this->_view = $this->port;
    }

    /**
     * @see parent::updateEncryptedFields()
     */
    public function updateEncryptedFields()
    {
        if ($this->ssl_passphrase === "") {
            $this->ssl_passphrase = null;
        } else {
            if (!empty($this->ssl_passphrase)) {
                $this->ssl_passphrase = $this->encryptString($this->ssl_passphrase, "iv_passphrase");
            }
        }
    }

    /**
     * @see parent::getData()
     */
    public function getData($path = null)
    {
        return $this->recv();
    }

    public function recv()
    {
        $servers = [$this->getSocketClient()];

        if ($this->timeout_period_stream) {
            stream_set_timeout($this->_socket_client, $this->timeout_period_stream);
        }

        $data = "";
        $this->startCallTrace();
        $this->onBeforeRequest('stream_get_contents');
        do {
            while (@stream_select($servers, $write = null, $except = null, $this->timeout_socket) === false) {
                ;
            }
            $buf  = stream_get_contents($this->_socket_client);
            $data .= $buf;
        } while ($buf);
        $this->onAfterRequest($data);

        $this->stopCallTrace();

        return $data;
    }

    /**
     * @return resource|false
     */
    public function getSocketClient()
    {
        if ($this->_socket_client) {
            return $this->_socket_client;
        }

        $address = "$this->host:$this->port";
        $this->startCallTrace();

        $context = stream_context_create();

        if ($this->ssl_enabled && $this->ssl_certificate && is_readable($this->ssl_certificate)) {
            $address = "tls://$address";

            stream_context_set_option($context, 'ssl', 'local_cert', $this->ssl_certificate);

            if ($this->ssl_passphrase) {
                $ssl_passphrase = $this->getPassword($this->ssl_passphrase, "iv_passphrase");
                stream_context_set_option($context, 'ssl', 'passphrase', $ssl_passphrase);
            }
        }

        $this->onBeforeRequest('stream_socket_client');
        /** @var resource|false $socket_client */
        $socket_client = $this->_socket_client = @stream_socket_client(
            $address,
            $errno,
            $errstr,
            $this->timeout_socket,
            STREAM_CLIENT_CONNECT,
            $context
        );
        $this->onAfterRequest($socket_client);

        if (!$socket_client) {
            $this->stopCallTrace();
            throw new CMbException("CSourceMLLP-unreachable-source", $this->name, $errno, $errstr);
        }

        stream_set_blocking($socket_client, $this->set_blocking);
        $this->stopCallTrace();

        return $socket_client;
    }

    /**
     * @throws CMbException
     */
    public function send()
    {
        $data = self::TRAILING . $this->_data . self::LEADING;

        $this->startCallTrace();
        $socket = $this->getSocketClient();

        if ($this->timeout_period_stream) {
            stream_set_timeout($socket, $this->timeout_period_stream);
        }

        $this->onBeforeRequest('fwrite', false, $data);
        $return = fwrite($socket, $data, strlen($data));
        $this->onAfterRequest($return);
        $this->stopCallTrace();

        $acq = $this->recv();

        $this->_acquittement = trim(str_replace("\x1C", "", $acq));
    }

    public function isAuthentificate()
    {
        return $this->isReachableSource();
    }

    public function isReachableSource()
    {
        try {
            $this->getSocketClient();
        } catch (Exception $e) {
            $this->_reachable = 0;
            $this->_message   = $e->getMessage();

            return false;
        }

        return true;
    }

    function getResponseTime()
    {
        $this->_response_time = url_response_time($this->host, $this->port);
    }

    /**
     * @see parent::isSecured()
     */
    public function isSecured()
    {
        return ($this->ssl_enabled && $this->ssl_certificate && is_readable($this->ssl_certificate));
    }

    /**
     * @see parent::getProtocol()
     */
    public function getProtocol()
    {
        return 'tcp';
    }

    /**
     * Recording the exchange before the request
     *
     * @param string $stream_type Stream function
     * @param bool   $server
     * @param string $input       Input
     *
     * @return void
     * @throws Exception
     */
    public function onBeforeRequest(string $stream_type, bool $server = false, string $input = null): void
    {
        if (!$this->loggable) {
            return;
        }

        $exchange_mllp                = new CExchangeMLLP();
        $exchange_mllp->date_echange  = "now";
        $exchange_mllp->emetteur      = $server ? "$this->host:$this->port" : CAppUI::conf("mb_id");
        $exchange_mllp->function_name = $stream_type;
        $exchange_mllp->source_class  = $this->_class;
        $exchange_mllp->source_id     = $this->_id;
        $exchange_mllp->destinataire  = $server ? CAppUI::conf("mb_id") : "$this->host:$this->port";
        $exchange_mllp->input        = serialize($input);
        $exchange_mllp->store();

        $this->_exchange_mllp = $exchange_mllp;

        CApp::$chrono->stop();

        $this->chrono = new Chronometer();
        $this->chrono->start();
    }

    /**
     * Recording the exchange after the request
     *
     * @param string|resource|false $result Result
     *
     * @return void
     * @throws Exception
     */
    public function onAfterRequest($result): void
    {
        if (!$this->loggable) {
            return;
        }

        $this->chrono->stop();
        CApp::$chrono->start();

        $exchange_mllp                = $this->_exchange_mllp;
        $exchange_mllp->date_echange  = "now";
        $exchange_mllp->response_time = $this->chrono->total;
        $exchange_mllp->output        = serialize($result);
        $exchange_mllp->store();
    }
}
