<?php
/**
 * @package Mediboard\Core
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core;

use Ox\Core\Shm\ISharedMemory;

/**
 * Distributed shared memory container
 */
abstract class DSHM extends SHM {
  /**
   * Get a value from the shared memory, using the distributed engine
   *
   * @param string $key  The key of the value to get
   *
   * @return mixed
   */
  static function get($key) {
    return self::_get(true, $key);
  }

  /**
   * Get a value from the shared memory, using the distributed engine
   *
   * @param array $keys Keys of the values to get
   *
   * @return mixed
   */
  static function multipleGet(array $keys) {
    return self::_multipleGet(true, $keys);
  }

  /**
   * Save a value in the shared memory, using the distributed engine
   *
   * @param string $key      The key to pu the value in
   * @param mixed  $value    The value to put in the shared memory
   * @param bool   $compress Compress data
   * @param int    $ttl      TTL for the key
   *
   * @return bool
   */
  static function put($key, $value, $compress = false, $ttl = null) {
    return self::_put(true, $key, $value, $compress, $ttl);
  }

  /**
   * Remove a value from the distributed shared memory
   *
   * @param string $key  The key to remove
   *
   * @return bool
   */
  static function rem($key) {
    return self::_rem(true, $key);
  }

  /**
   * Check if given key exists in distributed shared memory
   *
   * @param string $key  Key to check
   *
   * @return bool
   */
  static function exists($key) {
    return self::_exists(true, $key);
  }

  /**
   * List all the keys in the distributed shared memory
   *
   * @param string $pattern Pattern to match
   *
   * @return array
   */
  static function listKeys($pattern = null) {
    return self::_listKeys(true, $pattern);
  }

  /**
   * Remove a list of keys corresponding to a pattern (* is a wildcard)
   *
   * @param string $pattern Pattern with "*" wildcards
   *
   * @return int The number of removed key/value pairs
   */
  static function remKeys($pattern) {
    return self::_remKeys(true, $pattern);
  }

  /**
   * Get modification date of a distributed key
   *
   * @param string $key The key to get the modification date of
   *
   * @return string
   */
  static function modDate($key) {
    return self::_modDate(true, $key);
  }

  /**
   * Get information about key
   * Creation date, modification date, number of hits, size in memory, compressed or not
   *
   * @param string $key The key to get information about
   *
   * @return array
   */
  static function info($key) {
    return self::_info(true, $key);
  }

  /**
   * Get general information
   *
   * @return array Information
   */
  static function getInfo() {
    return self::_getInfo(true);
  }

  /**
   * Get the engine
   *
   * @return ISharedMemory
   */
  static function getEngine() {
    return self::_getEngine(true);
  }

  /**
   * Get keys information
   *
   * @param string $prefix Only keys matching this prefix
   *
   * @return array Information
   */
  static function getKeysInfo($prefix = null) {
    return self::_getKeysInfo(true, $prefix);
  }
}
