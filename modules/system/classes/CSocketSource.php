<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\System;

use Ox\Core\CMbException;
use Ox\Core\Socket\SocketClient;

/**
 * Class CSocketSource
 */
class CSocketSource extends CExchangeSource
{
    public $port;
    public $protocol;
    public $ssl_certificate;
    public $ssl_passphrase;
    public $iv_passphrase;

    public $_socket_client;

    function recv()
    {
        $servers = [$this->getSocketClient()];

        $data = "";
        $this->startCallTrace();
        do {
            while (@stream_select($servers, $write = null, $except = null, 5) === false) {
                ;
            }
            $buf  = stream_get_contents($this->_socket_client);
            $data .= $buf;
        } while ($buf);

        $this->stopCallTrace();

        return $data;
    }

    /**
     * Get socket client
     *
     * @return SocketClient
     * @throws CMbException
     */
    function getSocketClient()
    {
        if ($this->_socket_client) {
            return $this->_socket_client;
        }

        $address = $this->getProtocol() . "://$this->host:$this->port";
        $context = stream_context_create();

        if ($this->isSecured()) {
            stream_context_set_option($context, 'ssl', 'local_cert', $this->ssl_certificate);

            if ($this->ssl_passphrase) {
                $ssl_passphrase = $this->getPassword($this->ssl_passphrase, "iv_passphrase");
                stream_context_set_option($context, 'ssl', 'passphrase', $ssl_passphrase);
            }
        }

        $this->startCallTrace();
        $this->_socket_client = @stream_socket_client(
            $address,
            $errno,
            $errstr,
            ($this->timeout) ? $this->timeout : 5,
            STREAM_CLIENT_CONNECT,
            $context
        );
        $this->stopCallTrace();
        if (!$this->_socket_client) {
            throw new CMbException("common-error-Unreachable source", $this->name);
        }

        $this->startCallTrace();
        stream_set_blocking($this->_socket_client, 1);
        $this->stopCallTrace();

        return $this->_socket_client;
    }

    /**
     * Get transport protocol to use
     *
     * @return string
     */
    function getProtocol()
    {
    }

    /**
     * Check if source is secured
     *
     * @return bool
     */
    function isSecured()
    {
    }
}
