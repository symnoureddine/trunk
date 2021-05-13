<?php
/**
 * @package Mediboard\Hl7
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Hl7;
use Ox\Core\Autoload\IShortNameAutoloadable;
use Ox\Core\Cache;
use Ox\Core\CMbDT;
use Ox\Core\CRequest;
use Ox\Core\CSQLDataSource;
use Ox\Core\CValue;

/**
 * Root class of all the HL7v2 entities
 */
abstract class CHL7v2 implements IShortNameAutoloadable {
  static $debug = false;

  const LIB_HL7               = "modules/hl7/resources";
  const PREFIX_MESSAGE_NAME   = "message";
  const PREFIX_SEGMENT_NAME   = "segment";
  const PREFIX_COMPOSITE_NAME = "composite";

  static $versions = array(
    // International
    "2.1",
    "2.2",
    "2.3",
    "2.3.1",
    "2.4",
    "2.5",
    "2.5.1",
    "2.6",
    "2.7",

    // Extension française
    "FRA_2.3",
    "FRA_2.4",
    "FRA_2.5",
    "FRA_2.6",
    "FRA_2.7",
    "FRA_2.8",
    "FRA_2.9",
  );

  static $schemas = array();

  static $ds = false;

  //protected $keep_original = array();

  /**
   * When explode() is passed an empty $string, it returns a one element array
   *
   * @param string  $delimiter  The delimiter
   * @param string  $data       The data to split
   * @param boolean $dont_split Return a single element array with all the data
   *
   * @return array The splitted data
   */
  static function split($delimiter, $data, $dont_split = false) {
    if ($data === "" || $data === null) {
      return array();
    }

    return ($dont_split ? array($data) : explode($delimiter, $data));
  }

  /**
   * Create an array with filtered keys based on having given prefix
   *
   * @param array  $array  The array to filter
   * @param string $prefix The prefix that has to start key strings
   *
   * @return array The filtered array
   */
  static function filterPrefix($array, $prefix) {
    $values = array();
    foreach ($array as $value) {
      if (strpos($value, $prefix) === 0) {
        $values[] = $value;
      }
    }
    return $values;
  }

  /**
   * Get international HL7 versions
   *
   * @return array
   */
  static function getInternationalVersions() {
    return self::filterPrefix(self::$versions, "2");
  }

  /**
   * Get french HL7 versions
   *
   * @return array
   */
  static function getFRAVersions() {
    return self::filterPrefix(self::$versions, "FRA");
  }

  /**
   * Get the HL7v2 mapping table values
   *
   * @param string  $table           The table to get
   * @param boolean $from_mb         Get the reversed table (MB => HL7)
   * @param bool    $get_description Return the escriptions instead of the value
   *
   * @return array|null The table
   * @throws CHL7v2Exception
   */
  static function getTable($table, $from_mb = true, $get_description = false){
    $cache = new Cache(__METHOD__, func_get_args(), Cache::INNER);
    if ($cache->exists()) {
      return $cache->get();
    }

    if (self::$ds === null) {
      return null;
    }

    if (self::$ds === false) {
      self::$ds = CSQLDataSource::get("hl7v2");
    }

    if (self::$ds === null) {
      throw new CHL7v2Exception(CHL7v2Exception::INVALID_DATA_SOURCE);
    }

    $where = array(
      "number" => self::$ds->prepare("=%", $table)
    );

    if ($from_mb) {
      $cols = array("code_mb_from",  $get_description ? "description" : "code_hl7_to");
    }
    else {
      $cols = array("code_hl7_from", $get_description ? "description" : "code_mb_to");
    }

    $req = new CRequest;
    $req->addSelect($cols);
    $req->addTable("table_entry");
    $req->addWhere($where);

    $result = self::$ds->loadHashList($req->makeSelect());

    return $cache->put($result);
  }

  /**
   * Return the Mediboard value from an HL7 value
   *
   * @param string $table The table to get the value from
   * @param string $value The HL7 value
   *
   * @return string The Mediboard value
   */
  static function getTableMbValue($table, $value) {
    $data = self::getTable($table, false);

    if (empty($data)) {
      return null;
    }

    return CValue::read($data, $value, false);
  }

  /**
   * Return the HL7 value from a Mediboard value
   *
   * @param string $table The table to get the value from
   * @param string $value The Mediboard value
   *
   * @return string The HL7 value
   */
  static function getTableHL7Value($table, $value) {
    $data = self::getTable($table, true);

    if (empty($data)) {
      return null;
    }

    return CValue::read($data, $value, false);
  }

  /**
   * Return the Mediboard value from an HL7 value
   *
   * @param string $table The table to get the value from
   * @param string $value The HL7 value
   *
   * @return string The Mediboard value
   */
  static function getTableDescription($table, $value) {
    $data = self::getTable($table, false, true);

    if (empty($data)) {
      return null;
    }

    return CValue::read($data, $value, false);
  }

  /**
   * Returns a structured HL7 version
   *
   * @param string $version The version string
   *
   * @return array|string The structure or the version if it's not valid
   */
  static function prepareHL7Version($version) {
    if (preg_match("/([A-Z]{3})_(.*)/", $version, $matches)) {
      return array(
        array (
          "2.5",
          // Internationalization Code
          $matches[1],
          // International Version ID
          $matches[2],
        )
      );
    }

    return $version;
  }

  /**
   * Get the specs of the entity
   *
   * @return CHL7v2DOMDocument The spec
   */
  abstract function getSpecs();

  /**
   * Get the version of the entity
   *
   * @return string The version
   */
  abstract function getVersion();

  /**
   * Debug output
   *
   * @param string $str   The debugging message
   * @param string $color The color of the message
   *
   * @return void
   */
  static function d($str, $color = null) {
    if (!self::$debug) {
      return;
    }

    echo "<pre style='color:$color;'>$str</pre>";
  }

  /**
   * Transforms absolute or relative date into a HL7 format
   *
   * @param string $relative A relative time
   * @param string $ref      An absolute time to transform
   *
   * @return string The transformed date
   **/
  static function getDate($relative = null, $ref = null) {
    return CMbDT::transform($relative, $ref, "%Y%m%d");
  }

  /**
   * Transforms absolute or relative datetime into a HL7 format
   *
   * @param string $relative A relative datetime
   * @param string $ref      An absolute datetime to transform
   *
   * @return string The transformed date
   **/
  static function getDateTime($relative = null, $ref = null) {
    return CMbDT::transform($relative, $ref, "%Y%m%d%H%M%S");
  }
}
