<?php
/**
 * @package Mediboard\Core
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core;

use Exception;

define("DEBUG_PATH", __DIR__ . "/../../tmp/mb-debug.html");

/**
 * @deprecated
 * Class CMbDebug
 * used to manage debug log
 */
class CMbDebug {
  const DEBUG_PATH = DEBUG_PATH;
  const DEBUG_SIZE_LIMIT = 5242880; // 1024*1024*5

  /**
   * @deprecated use CApp::log with debug level
   *
   * Process the exported data
   *
   * @throws Exception
   * @param string $export         Data
   * @param string $label          Add an optionnal label
   * @param bool   $onlyPlainField Only get DB fields and there values if export is object
   *
   * @return int The size of the data written in the log file
   **/
  static function log($export, $label = null, $onlyPlainField = false) {
    if ($label) {
      $message = $label;
      $data    = $export;
    }
    else {
      if (is_scalar($export)) {
        $message = $export;
        $data    = null;
      }
      else {
        $message = "Log from CmbDebug::log";
        $data    = $export;
      }
    }

    return CApp::log($message, $data, CLogger::LEVEL_DEBUG);

//        if ($export instanceof CMbObject && $onlyPlainField) {
//          $export = $export->getPlainFields();
//        }
//
//        $export = print_r($export, true);
//        $export = CMbString::htmlSpecialChars($export);
//        $time = date("Y-m-d H:i:s");
//        $msg = "\n<pre>[$time] $label: $export</pre>";
//
//        return file_put_contents(DEBUG_PATH, $msg, FILE_APPEND);

  }


}