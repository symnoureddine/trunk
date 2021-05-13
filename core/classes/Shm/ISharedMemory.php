<?php
/**
 * @package Mediboard\Core\Shm
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Shm;
/**
 * Shared Memory interface
 */
interface ISharedMemory {

  /**
   * Initialize the shared memory
   * Returns true if shared memory is available
   *
   * @return bool
   */
  function init();

  /**
   * Get a variable from shared memory
   *
   * @param string $key Key of value to retrieve
   *
   * @return mixed the value, null if failed
   */
  function get($key);

  /**
   * Get a value from the shared memory
   *
   * @param array $keys Keys of the values to get
   *
   * @return mixed
   */
  function multipleGet($keys);

  /**
   * Put a variable into shared memory
   *
   * @param string $key   Key of value to store
   * @param mixed  $value The value
   * @param int    $ttl   TTL in seconds
   *
   * @return bool job-done
   */
  function put($key, $value, $ttl = null);

  /**
   * Remove a variable from shared memory
   *
   * @param string $key Key of value to remove
   *
   * @return bool job-done
   */
  function rem($key);

  /**
   * Removes a list of variable from shared memory
   *
   * @param array $keys Array of Keys to remove
   *
   * @return bool job-done
   */
  function remKeys($keys);

  /**
   * Tell if a key exists in shared memory
   *
   * @param string $key Key of value to chek if exists
   *
   * @return bool
   */
  function exists($key);

  /**
   * Return the list of keys
   *
   * @param string $prefix  The keys' prefix
   * @param string $pattern Pattern to match to
   *
   * @return array Keys list
   */
  function listKeys($prefix, $pattern = null);

  /**
   * Get modification date
   *
   * @param string $key Key
   *
   * @return string ISO date
   */
  function modDate($key);

  /**
   * Get information about key
   *
   * @param string $key Key
   *
   * @return array Information
   */
  function info($key);

  /**
   * Get engine version information (name, version)
   *
   * @return array
   */
  function getEngineVersion();

  /**
   * Get information about configuration and instance metrics
   *
   * @return array
   */
  function getInfo();

  /**
   * Get information about keys
   *
   * @param string $prefix Only keys matching this prefix
   *
   * @return array
   */
  function getKeysInfo($prefix = null);
}