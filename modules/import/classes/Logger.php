<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Import\Framework;

use Exception;
use Ox\Core\CAppUI;
use Ox\Core\CLogger;

/**
 * Description
 */
class Logger {
  private const CHANNEL_IMPORT = "import";

  /** @var CLogger */
  private static $logger;

  /**
   * @param string $message Message to log
   * @param mixed  $data    Data to add to the log
   * @param int    $level   Use CLogger::const
   *
   * @return bool
   */
  static function log($message, $data = null, $level = CLogger::LEVEL_INFO) {
    // init logger
    if (is_null(static::$logger)) {
      try {
        static::setLogger();
      }
      catch (Exception $e) {
        trigger_error($e->getMessage(), E_USER_ERROR);
      }
    }

    // force array necessary for monolog's & parse log
    if (!is_null($data)) {
      $data = $data === false ? 0 : $data;
      $data = is_array($data) ? $data : array($data);
    }

    // log
    return static::$logger->log($message, $data, $level);
  }

  /**
   * Set logger for mediboard channel
   *
   * @return void
   * @throws Exception
   */
  private static function setLogger() {
    $logger = new CLogger(self::CHANNEL_IMPORT);
    $logger->setLineFormatter("[%datetime%] [%level_name%] %message% [context:%context%] [extra:%extra%]\n", "Y-m-d H:i:s.u");
    $logger->setIntrospectionProcessor();
    $logger->setMediboardProcessor();

    $file = static::getPathImportLog();
    $logger->setStreamFile($file);

    static::$logger = $logger;
  }

  /**
   * @return string
   */
  static function getPathImportLog() {
    $file_name = 'import.log';
    $dir = CAppUI::conf('root_dir') . DIRECTORY_SEPARATOR . "tmp";

    return $dir . DIRECTORY_SEPARATOR . $file_name;
  }
}