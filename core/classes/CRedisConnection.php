<?php
/**
 * @package Mediboard\Core
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core;

use Ox\Core\Redis\Yampee\Exception\Yampee_Redis_Exception_Connection;
use Ox\Core\Redis\Yampee\Yampee_Redis_Connection;

/**
 * Redis client
 */
class CRedisConnection extends Yampee_Redis_Connection {
  /**
   * Constructor
   *
   * @param string $host
   * @param int    $port
   * @param int    $timeout
   *
   * @throws Yampee_Redis_Exception_Connection
   */
  public function __construct($host = 'localhost', $port = 6379, $timeout = 5) {
    $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);

    if (!$socket) {
      throw new Yampee_Redis_Exception_Connection($host, $port);
    }

    $this->socket = $socket;
  }
}
