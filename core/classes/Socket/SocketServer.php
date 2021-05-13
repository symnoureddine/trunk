<?php
/**
 * @package Classes
 * @author  Fabien Ménager <fabien.menager@gmail.com>
 * @author  Aleksey V. Zapparov AKA ixti <http://ixti.ru/>
 * @license http://www.gnu.org/licenses/ GNU GPLv3
 * @link http://blog.ixti.ru/?p=116
 * @link http://php-socket.googlecode.com/
 */

namespace Ox\Core\Socket;

use Exception;

/**
 * Socket server wrapper class
 */
class SocketServer {
  /**
   * Socket resource created by {@link socket_create()}
   *
   * @see socket_create()
   * @var resource
   */
  public $__socket;

  /**
   * Tells whenever {@link $__socket} is binded or not.
   *
   * @see SocketServer::bind()
   * @var boolean
   */
  private $__isBinded = false;

  /**
   * Handler function of incoming requests. Returned value will be sent client
   * as response message.
   *
   * @see SocketServer::setRequestHandler()
   * @var mixed
   */
  private $__handler = null;

  /**
   * Function to be called upon new connection arrives.
   *
   * @see SocketServer::setOnOpenHandler()
   * @var mixed
   */
  private $__onOpen = null;

  /**
   * Function to be called upon connection cleanup.
   *
   * @see SocketServer::setOnCleanupHandler()
   * @var mixed
   */
  private $__onCleanup = null;

  /**
   * Function to be called upon client disconnection.
   *
   * @see SocketServer::setOnCloseHandler()
   * @var mixed
   */
  private $__onClose = null;

  /**
   * Function to be called upon response write error
   *
   * @see SocketServer::setOnWriteErrorHandler()
   * @var mixed
   */
  private $__onWriteError = null;

  /**
   * Welome message to be displayed to new clients.
   *
   * @var string|null
   */
  private $__motd = null;

  /**
   * Socket read per time amount
   *
   * @see http://www.phpclasses.org/discuss/package/5758/thread/2/
   * @var integer
   */
  private $__readAmount = 2048;

  /**
   * Socket read mode
   *
   * @see http://www.phpclasses.org/discuss/package/5758/thread/2/
   * @var integer
   */
  private $__readMode = PHP_NORMAL_READ;

  /**
   * @var boolean Auto-close after response mode.
   *
   * @link http://www.phpclasses.org/discuss/package/5758/thread/3/
   */
  private $__autoClose = false;

  /**
   * Class constructor.
   *
   * Creates a socket resource. Simple wraper of {@link socket_create()},
   * which creates a resource and keep it as private property.
   *
   * Example:
   * <code>
   * $protocol = getprotobyname('udp');
   * $server   = new SocketServer(AF_INET, SOCK_DGRAM, $protocol);
   * </code>
   *
   * Please reffer to {@link socket_create()} manual for more details, as this
   * is just a wrapper of that function.
   *
   * @param integer $domain   Protocol family to be used by the socket.
   * @param integer $type     Type of communication to be used by the socket.
   * @param integer $protocol Protocol within the specified $domain.
   *
   * @see    socket_create()
   * @throws Exception If {@link socket_create()} failed
   */
  public function __construct($domain, $type, $protocol) {
  }

  /**
   * Class destructor
   *
   * Close socket if it was created.
   *
   * @see socket_close()
   */
  public function __destruct() {
    @socket_close($this->__socket);
  }

  /**
   * Set welcome message for new clients.
   *
   * @param string|null $msg Welcome message
   *
   * @return SocketServer self-reference
   */
  public function setMotd($msg) {
    $msg = trim($msg);

    $this->__motd = (0 !== $msg) ? "\n" . $msg . "\n" : null;

    return $this;
  }

  /**
   * Throws {@link Exception} with last socket error or specified message.
   *
   * Close socket, if it was opened and then throws {@link Exception}. If
   * $msg is not specified or NULL, last sockt error will be used as message.
   *
   * @param string $msg (optional)
   *
   * @throws Exception
   *
   * @return void
   */
  private function raiseError($msg = null) {
    if (null === $msg) {
      $msg = socket_strerror(socket_last_error());
    }

    throw new Exception($msg);
  }

  /**
   * Sets socket_read limit
   *
   * @param integer $limit Limit to read
   *
   * @return void
   */
  public function setReadAmount($limit) {
    $this->__readAmount = $limit * 1;
  }

  /**
   * Sets socket_read mode.
   *
   * @param integer $mode PHP_NORMAL_READ or PHP_BINARY_READ
   *
   * @see http://www.phpclasses.org/discuss/package/5758/thread/2/
   */
  public function setReadMode($mode) {
    if (PHP_NORMAL_READ !== $mode && PHP_BINARY_READ !== $mode) {
      $this->raiseError('Unknown read mode.');
    }
  }

  /**
   * Sets auto-close after response mode.
   *
   * @param boolean $autoClose Auto close
   *
   * @link http://www.phpclasses.org/discuss/package/5758/thread/3/
   *
   * @return void
   */
  public function setAutoClose($autoClose = true) {
    $this->__autoClose = (boolean)$autoClose;
  }

  /**
   * Binds a name to a socket.
   *
   * Binds the name given in $address to the socket. This has to be done
   * before starting server with {@link SocketServer::run()}.
   *
   * @param string  $address     Address name to be binded to socket.
   *                             - If the socket is of the AF_INET family, the address is an IP in
   *                             dotted-quad notation (e.g. 127.0.0.1).
   *                             - If the socket is of the AF_UNIX family, the address is the path
   *                             of a Unix-domain socket (e.g. /tmp/my.sock).
   * @param integer $port        (optional) The port parameter is only used when
   *                             connecting to an AF_INET socket, and designates the port on the
   *                             remote host to which a connection should be made.
   * @param string  $certificate Certificate file
   * @param string  $passphrase  Certificate's passphrase
   * @param string  $ca          Certificate authority file
   *
   * @return SocketServer If <a href='psi_element://socket_bind()'>socket_bind()</a> failed
   * failed
   * @throws Exception If <a href='psi_element://socket_bind()'>socket_bind()</a> failed
   * @link   socket_bind()
   */
  public function bind($address, $port = null, $certificate = null, $passphrase = null, $ca = null) {
    $context = stream_context_create();

    $protocol = "tcp";

    if ($certificate) {
      $protocol = "tls";

      stream_context_set_option($context, 'ssl', 'local_cert', $certificate);
      //stream_context_set_option($context, 'ssl', 'local_pk', "private.key");

      if ($passphrase) {
        stream_context_set_option($context, 'ssl', 'passphrase', $passphrase);
      }

      stream_context_set_option($context, 'ssl', 'cafile', $ca);

      stream_context_set_option($context, 'ssl', 'allow_self_signed', false);
      stream_context_set_option($context, 'ssl', 'verify_peer', true);
      //stream_context_set_option($context, 'ssl', 'peer_name', "CN=foobar");

      //stream_context_set_option($context, 'ssl', 'capture_peer_cert', true);
      //stream_context_set_option($context, 'ssl', 'capture_peer_cert_chain', true);

      //stream_context_set_option($context, 'ssl', 'peer_certificate', true);
      //stream_context_set_option($context, 'ssl', 'peer_certificate_chain', "ca.pem");*/

      //stream_context_set_option($context, 'ssl', 'SNI_enabled', true);
      //stream_context_set_option($context, 'ssl', 'SNI_server_name', "CN=foobar");
    }

    $this->__socket = stream_socket_server(
      "$protocol://$address:$port",
      $errno,
      $errstr,
      STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
      $context
    );

    if (false === $this->__socket) {
      $this->raiseError($errstr);
    }

    $this->__isBinded = true;

    return $this;
  }

  /**
   * Run server.
   *
   * Calls {@link socket_listen()} and then run main daemon loop. Please refer
   * to {@link socket_listen()} about $backlog argument.
   *
   * Bind socket with {@link SocketServer::bind()} method and set request's
   * handler with {@link SocketServer::setRequestHandler()} before running a
   * server.
   *
   * @param integer $backlog (optional) A maximum of incoming connections.
   *
   * @throws Exception If {@link $__handler} was not set
   * @throws Exception If socket was not binded
   * @throws Exception If {@link socket_listen()} failed
   *
   * @see SocketServer::bind()
   * @see SocketServer::setRequestHandler()
   *
   * @return void
   */
  public function run($backlog = null) {
    if (null === $this->__handler) {
      $this->raiseError('Handler must be set first');
    }

    if (false === $this->__isBinded) {
      $this->raiseError('Socket must be binded first');
    }

    $this->runPrivate();
  }

  /**
   * Registers request handler.
   *
   * $handler will be called with passing request as the only argument.
   *
   * - Client will be disconnected upon $func will return NULL.
   * - Server will be stopped upon $func will return boolean false.
   * - Else returned value will be sent as a response.
   *
   * @param mixed $func Request handler function or method. Can be either
   *                    the name of a function stored in a string variable, or an object
   *                    and the name of a method within the object, like this:
   *                    array($SomeObject, 'MethodName')
   *
   * @throws Exception If specified $func can't be called
   *
   * @return SocketServer self-reference
   */
  public function setRequestHandler($func) {
    if (!is_callable($func)) {
      $this->raiseError('Request handler is not callable.');
    }

    $this->__handler = $func;

    return $this;
  }

  /**
   * Sets handler to be called upon new connection.
   *
   * Function will be called with passing it three arguments:
   *  - integer: Connection id, to determine which connection is requesting
   *    handler call
   *  - string: Socket's address name of remote end, e.g. '127.0.0.1'
   *  - integer: (optional) Socket's port in case of INET* socket
   *
   * Example:
   * <code>
   * function conn_open_handler($id, $addr, $port = null)
   * {
   *     // ...
   * }
   * </code>
   *
   * @param mixed $func onOpen handler function or method. Can be either
   *                    the name of a function stored in a string variable, or an object
   *                    and the name of a method within the object, like this:
   *                    array($SomeObject, 'MethodName')
   *
   * @throws Exception If specified $func can't be called
   *
   * @return SocketServer self-reference
   */
  public function setOnOpenHandler($func) {
    if (!is_callable($func)) {
      $this->raiseError('onOpen handler is not callable.');
    }

    $this->__onOpen = $func;

    return $this;
  }

  /**
   * Open handler executor.
   *
   * Will execute open handler with specified resource id, address, and port.
   *
   * @param integer  $id Socket ID
   * @param resource $socket
   *
   * @see SocketServer::setOnOpenHandler()
   *
   * @return void
   */
  private function open($id, $socket) {
    if (null !== $this->__onOpen) {
      $peer = stream_socket_get_name($socket, true);
      list($addr, $port) = explode(":", $peer);

      return call_user_func($this->__onOpen, $id, $addr, $port);
    }
  }

  /**
   * Sets handler to be called upon pool cleanup.
   *
   * Function will be called with passing only one param - connection id.
   *
   * Example:
   * <code>
   * function conn_cleanup_handler($id)
   * {
   *     // ...
   * }
   * </code>
   *
   * @param mixed $func onCleanup handler function or method. Can be either
   *                    the name of a function stored in a string variable, or an object
   *                    and the name of a method within the object, like this:
   *                    array($SomeObject, 'MethodName')
   *
   * @throws Exception If specified $func can't be called
   *
   * @return SocketServer self-reference
   */
  public function setOnCleanupHandler($func) {
    if (!is_callable($func)) {
      $this->raiseError('onCleanup handler is not callable.');
    }

    $this->__onCleanup = $func;

    return $this;
  }

  /**
   * Cleanup handler executor.
   *
   * Will execute cleanup handler with specified resource id.
   *
   * @param integer $id
   *
   * @see SocketServer::setOnCleanupHandler()
   *
   * @return void
   */
  private function cleanup($id) {
    if (null !== $this->__onCleanup) {
      call_user_func($this->__onCleanup, $id);
    }
  }

  /**
   * Sets handler to be called after closing a connection with client.
   *
   * Function will be called with passing only one param - connection id.
   *
   * Example:
   * <code>
   * function conn_close_handler($id)
   * {
   *     // ...
   * }
   * </code>
   *
   * @param mixed $func onClose handler function or method. Can be either
   *                    the name of a function stored in a string variable, or an object
   *                    and the name of a method within the object, like this:
   *                    array($SomeObject, 'MethodName')
   *
   * @throws Exception If specified $func can't be called
   *
   * @return SocketServer self-reference
   */
  public function setOnCloseHandler($func) {
    if (!is_callable($func)) {
      $this->raiseError('onClose handler is not callable.');
    }

    $this->__onClose = $func;

    return $this;
  }

  /**
   * Close handler executor.
   *
   * Will execute close handler with specified resource id.
   *
   * @param integer $id
   *
   * @see SocketServer::setOnCloseHandler()
   *
   * @return void
   */
  private function close($id) {
    if (null !== $this->__onClose) {
      call_user_func($this->__onClose, $id);
    }
  }

  /**
   * Sets handler to be called upon response write error.
   *
   * Function will be called with passing only one param - connection id.
   *
   * Example:
   * <code>
   * function conn_write_error_handler($id)
   * {
   *     // ...
   * }
   * </code>
   *
   * @param mixed $func onWriteError handler function or method. Can be
   *                    either the name of a function stored in a string variable, or
   *                    an object and the name of a method within the object, like this:
   *                    array($SomeObject, 'MethodName')
   *
   * @throws Exception If specified $func can't be called
   *
   * @return SocketServer self-reference
   */
  public function setOnWriteErrorHandler($func) {
    if (!is_callable($func)) {
      $this->raiseError('onWriteError handler is not callable.');
    }

    $this->__onWriteError = $func;

    return $this;
  }

  /**
   * Write error handler executor.
   *
   * Will execute write error handler with specified resource id.
   *
   * @param integer $id
   *
   * @see SocketServer::setOnWriteErrorHandler()
   *
   * @return void
   */
  public function writeError($id) {
    if (null !== $this->__onWriteError) {
      call_user_func($this->__onWriteError, $id);
    }
  }
  
  /**
   * Server's main loop.
   *
   * Taken from first version as it was described on my blog and leaved almost
   * untouched :))
   *
   * @link http://blog.ixti.ru/?p=105 Socket reader in PHP
   *
   * @return void
   */
  private function runPrivate() {
    // Client connections' pool
    $pool = array($this->__socket);

    // Main cycle
    while (is_resource($this->__socket)) {
      // Clean-up pool
      foreach ($pool as $conn_id => $conn) {
        if (!is_resource($conn)) {
          $this->cleanup($conn_id);
          unset($pool[$conn_id]);
        }
      }

      // Create a copy of pool for socket_select()
      $active = $pool;

      // Halt execution if socket_select() failed
      if (false === @stream_select($active, $w, $e, null)) {
        $this->raiseError();
      }

      // Register new client in the pool
      if (in_array($this->__socket, $active)) {
        $peername = "";
        $conn     = stream_socket_accept($this->__socket, 5, $peername);
        stream_set_blocking($conn, 1);

        echo "Peername: $peername\n";

        if (is_resource($conn)) {
          if (null !== $this->__motd) {
            // Send welcome message
            fwrite($conn, $this->__motd, strlen($this->__motd));
          }

          $conn_id = (integer)$conn;

          if ($this->open($conn_id, $conn)) {
            $pool[$conn_id] = $conn;
          }
          else {
            $this->close($conn_id);
            @fclose($conn);
          }
        }
        unset($active[array_search($this->__socket, $active)]);
      }

      // Handle every active client
      foreach ($active as $conn) {
        $conn_id = (integer)$conn;
        $request = @fread($conn, $this->__readAmount);

        // If connection is closed, mark it for cleanup and continue
        if (false === $request || $request === "") {
          $pool[$conn_id] = false;
          continue;
        }

        // Skip to next if client tells nothing
        if (0 == strlen($request)) {
          continue;
        }

        $response = call_user_func($this->__handler, $request, $conn_id);

        // Request handler asks to close conection
        if (null === $response) {
          fclose($conn);
          $this->close($conn_id);

          unset($pool[$conn_id]);
          continue;
        }

        // Request handler asks to shutdown server
        if (false === $response) {
          // Tell everyone that server is shutting down
          foreach ($pool as $_conn) {
            if ($this->__socket !== $_conn) {
              $msg = '*** Server is shutting down by request' . "\n";
              @fwrite($_conn, $msg, strlen($msg));
              @fclose($_conn);
            }
          }

          $this->__destruct();

          return;
        }

        $test = @fwrite($conn, $response, strlen($response));
        if (false === $test) {
          $this->writeError($conn_id);
        }

        if ($this->__autoClose) {
          @fclose($conn);
        }
      }
    }
  }
}
