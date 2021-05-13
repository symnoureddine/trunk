<?php
/**
 * @package Mediboard\Cli
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Cli\SvnClient;

use DOMDocument;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Class Util
 *
 * @package Ox\Cli\SvnClient
 */
class Util {

  /**
   * Execute an SVN command
   *
   * @param string      $cmd       Command name (update, info, etc)
   * @param array       $arguments An array of argument (path, url, etc)
   * @param array       $options   An array of options
   * @param string      $path      Working directory
   * @param string|bool $output    Output
   * @param integer     $timeout   Timeout
   *
   * @return string
   * @throws ProcessFailedException
   */
  public static function exec($cmd, $arguments = array(), $options = array(), $path = null, $output = false, $timeout = null) {
    if (!is_array($arguments)) {
      $arguments = array($arguments);
    }

    $arguments = array_map("escapeshellarg", $arguments);

    $new_options = array();
    foreach ($options as $key => $value) {
      $new_options[] = preg_replace("/[^-\w]/", "", $key);

      if ($value !== true) {
        $new_options[] = escapeshellarg($value);
      }
    }

    $cmdline = "svn $cmd " . implode(" ", $arguments) . " " . implode(" ", $new_options);

    $process = new Process($cmdline, $path, null, null, $timeout);
    $process->run();

    if (!$process->isSuccessful()) {
      throw new ProcessFailedException($process);
    }

    return $process->getOutput();
  }

  /**
   * @param DOMDocument $xml XML
   *
   * @return \SimpleXMLElement[]
   */
  public static function parseXML($xml) {
    $dom = simplexml_load_string($xml);

    foreach ($dom->children() as $child) {
      return $child;
    }
  }
}