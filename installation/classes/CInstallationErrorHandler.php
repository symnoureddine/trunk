<?php
/**
 * @package Mediboard\Installation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Installation;

use ErrorException;

/**
 * Convert error to exception
 * CInstallErrorHandler
 */
class CInstallationErrorHandler {

  /**
   * @param string $severity
   * @param string $message
   * @param string $file
   * @param string $line
   *
   * @throws ErrorException
   */
  public static function exception_error_handler($severity, $message, $file, $line): void {
    $error_reporting = error_reporting();
    if (!$error_reporting) {
      return;
    }

    throw new ErrorException($message, 0, $severity, $file, $line);
  }

  /**
   * setHandlers
   */
  public static function setHandlers(): void {
    set_error_handler(array(static::class, 'exception_error_handler'));
  }

}
