<?php
/**
 * @package Mediboard\Core
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core;

//require_once __DIR__ . "/CMbPath.php";
//require_once __DIR__ . "/Shm/ISharedMemory.php";

use Ox\Core\Shm\APCSharedMemory;
use Ox\Core\Shm\APCuSharedMemory;
use Ox\Core\Shm\DiskSharedMemory;
use Ox\Core\Shm\ISharedMemory;
use Ox\Core\Shm\RedisSharedMemory;

/**
 * Shared memory container
 */
abstract class SHM {
  const GZ = "__gz__";

  /** @var ISharedMemory */
  static private $engine;

  /** @var ISharedMemory */
  static private $engineDistributed;

  /** @var string */
  static protected $prefix;

  /**
   * Available engines
   *
   * @var array
   */
  static $availableEngines = array(
    "disk"  => DiskSharedMemory::class,
    "apc"   => APCSharedMemory::class,
    "apcu"  => APCuSharedMemory::class,
    "redis" => RedisSharedMemory::class,
  );

  /**
   * Initialize the shared memory
   *
   * @return void
   */
  public static function init() {
    // legacy
    global $dPconfig;

    $root_dir       = $dPconfig['root_dir'] ?? dirname(__DIR__, 2);
    $prefix         = preg_replace('/[^\w]+/', "_", $root_dir);
    static::$prefix = "$prefix-";

    /* ----- Local shared memory ----- */
    $engine_name = $dPconfig['shared_memory'] ?? null;
    if (!isset(self::$availableEngines[$engine_name])) {
      $engine_name = "disk";
    }

    $class_name = self::$availableEngines[$engine_name];

    /** @var ISharedMemory $engine */
    $engine = new $class_name;

    if (!$engine->init()) {
      $class_name = self::$availableEngines["disk"];

      $engine = new $class_name;
      $engine->init();
    }

    self::$engine = $engine;
  }

  public static function isInit(){
      return (bool) self::$engine;
  }

  /**
   * Prefix accessor
   *
   * @return string
   */
  static public function getPrefix() {
    return static::$prefix;
  }

  /**
   * Initialize distributed shared memory
   *
   * @return void
   */
  static function initDistributed() {
    global $dPconfig;

    $engine_name = $dPconfig['shared_memory'];
    if (!isset(self::$availableEngines[$engine_name])) {
      $engine_name = "disk";
    }

    /* ----- Multi server shared memory ----- */
    $engine_name_distributed = $dPconfig['shared_memory_distributed'];
    if (!$engine_name_distributed || !isset(self::$availableEngines[$engine_name_distributed])) {
      $engine_name_distributed = $engine_name;
    }

    $class_name = self::$availableEngines[$engine_name_distributed];
    //include_once __DIR__ . "/shm/$class_name.class.php";

    /** @var ISharedMemory $engine_distributed */
    $engine_distributed = new $class_name;

    if (!$engine_distributed->init()) {
      $class_name = self::$availableEngines["disk"];
      //include_once __DIR__ . "/shm/$class_name.class.php";

      $engine_distributed = new $class_name;
      $engine_distributed->init();
    }

    self::$engineDistributed = $engine_distributed;
  }

  /**
   * Get a value from the shared memory
   *
   * @param bool   $distributed Distributed
   * @param string $key         Key to get
   *
   * @return mixed
   */
  protected static function _get($distributed, $key) {
    $engine = $distributed ? self::$engineDistributed : self::$engine;
    $value  = $engine->get(static::getPrefix() . $key);

    // If data is compressed
    if (is_array($value) && isset($value[self::GZ])) {
      $value = unserialize(gzuncompress($value[self::GZ]));
    }

    return $value;
  }

  /**
   * Get a value from the shared memory, locally
   *
   * @param string $key The key of the value to get
   *
   * @return mixed
   */
  static function get($key) {
    return self::_get(false, $key);
  }

  /**
   * Get values from the shared memory
   *
   * @param bool  $distributed Distributed
   * @param array $keys        Keys to get
   *
   * @return mixed
   */
  protected static function _multipleGet($distributed, $keys) {
    $engine = $distributed ? self::$engineDistributed : self::$engine;
    $prefix = static::getPrefix();
    $values = $engine->multipleGet(
      array_map(
        function ($v) use ($prefix) {
          return $prefix . $v;
        },
        $keys
      )
    );

    // If data is compressed
    /*if (is_array($value) && isset($value[self::GZ])) {
      $value = unserialize(gzuncompress($value[self::GZ]));
    }*/

    return array_map(
      function ($value) {
        if (is_array($value) && isset($value[self::GZ])) {
          $value = unserialize(gzuncompress($value[self::GZ]));
        }

        return $value;
      },
      $values
    );
  }

  /**
   * Get multiple values from the shared memory, locally
   *
   * @param array $keys Keys array of values
   *
   * @return mixed
   */
  static function multipleGet(array $keys) {
    return self::_multipleGet(false, $keys);
  }

  /**
   * Save a value in the shared memory
   *
   * @param bool   $distributed Distributed
   * @param string $key         The key to pu the value in
   * @param mixed  $value       The value to put in the shared memory
   * @param bool   $compress    Compress data
   * @param int    $ttl         TTL for the key
   *
   * @return bool
   */
  protected static function _put($distributed, $key, $value, $compress = false, $ttl = null) {
    $engine = $distributed ? self::$engineDistributed : self::$engine;

    if ($compress) {
      $value = array(
        self::GZ => gzcompress(serialize($value)),
      );
    }

    return $engine->put(static::getPrefix() . $key, $value, $ttl);
  }

  /**
   * Save a value in the shared memory, locally
   *
   * @param string $key      The key to pu the value in
   * @param mixed  $value    The value to put in the shared memory
   * @param bool   $compress Compress data
   * @param int    $ttl      TTL for the key
   *
   * @return bool
   */
  static function put($key, $value, $compress = false, $ttl = null) {
    return self::_put(false, $key, $value, $compress, $ttl);
  }

  /**
   * Remove a value from the shared memory
   *
   * @param bool   $distributed Distributed
   * @param string $key         The key to remove
   *
   * @return bool
   */
  protected static function _rem($distributed, $key) {
    $engine = $distributed ? self::$engineDistributed : self::$engine;

    return $engine->rem(static::getPrefix() . $key);
  }

  /**
   * Remove a value from the local shared memory
   *
   * @param string $key The key to remove
   *
   * @return bool
   */
  static function rem($key) {
    return self::_rem(false, $key);
  }

  /**
   * Check if given key exists
   *
   * @param bool   $distributed Is distributed?
   * @param string $key         Key to check
   *
   * @return bool
   */
  protected static function _exists($distributed, $key) {
    $engine = $distributed ? self::$engineDistributed : self::$engine;

    return $engine->exists(static::getPrefix() . $key);
  }

  /**
   * Check if given key exists in shared memory
   *
   * @param string $key Key to check
   *
   * @return bool
   */
  static function exists($key) {
    return self::_exists(false, $key);
  }

  /**
   * List all the keys in the shared memory
   *
   * @param bool   $distributed Distributed
   * @param string $pattern     Pattern to match
   *
   * @return array
   */
  protected static function _listKeys($distributed, $pattern = null) {
    $engine = $distributed ? self::$engineDistributed : self::$engine;

    return $engine->listKeys(static::getPrefix(), $pattern);
  }

  /**
   * List all the keys in the shared memory
   *
   * @param string $pattern Pattern to match
   *
   * @return array
   */
  static function listKeys($pattern = null) {
    return self::_listKeys(false, $pattern);
  }

  /**
   * Remove a list of keys corresponding to a pattern (* is a wildcard)
   *
   * @param bool   $distributed Distributed
   * @param string $pattern     Pattern with "*" wildcards
   *
   * @return int The number of removed key/value pairs
   */
  protected static function _remKeys($distributed, $pattern) {
    $engine = $distributed ? self::$engineDistributed : self::$engine;
    $list = $engine->listKeys(static::getPrefix(), $pattern);

    if (empty($list)) {
      return 0;
    }

    $engine->remKeys($list);

    return count($list);
  }

  /**
   * Remove a list of keys corresponding to a pattern (* is a wildcard)
   *
   * @param string $pattern Pattern with "*" wildcards
   *
   * @return int The number of removed key/value pairs
   */
  static function remKeys($pattern) {
    return self::_remKeys(false, $pattern);
  }

  /**
   * Get modification date
   *
   * @param bool   $distributed Distributed
   * @param string $key         The key to get the modification date of
   *
   * @return string
   */
  protected static function _modDate($distributed, $key) {
    $engine = $distributed ? self::$engineDistributed : self::$engine;

    return $engine->modDate(static::getPrefix() . $key);
  }

  /**
   * Get modification date of a local key
   *
   * @param string $key The key to get the modification date of
   *
   * @return string
   */
  static function modDate($key) {
    return self::_modDate(false, $key);
  }

  /**
   * Get information about key
   *
   * @param bool   $distributed Distributed
   * @param string $key         The key to get information about
   *
   * @return array
   */
  protected static function _info($distributed, $key) {
    $engine = $distributed ? self::$engineDistributed : self::$engine;

    return $engine->info(static::getPrefix() . $key);
  }

  /**
   * Get information about key
   * Creation date, modification date, number of hits, size in memory, compressed or not
   *
   * @param string $key Key
   *
   * @return array Information
   */
  static function info($key) {
    return self::_info(false, $key);
  }

  /**
   * Get general information
   *
   * @param bool $distributed Distributed
   *
   * @return array
   */
  protected static function _getInfo($distributed) {
    $engine = $distributed ? self::$engineDistributed : self::$engine;

    return $engine->getInfo();
  }

  /**
   * Get general information
   *
   * @return array Information
   */
  static function getInfo() {
    return self::_getInfo(false);
  }

  /**
   * Get keys information
   *
   * @param bool   $distributed Distributed
   * @param string $prefix      Only keys matching this prefix
   *
   * @return array
   */
  protected static function _getKeysInfo($distributed, $prefix = null) {
    $engine = $distributed ? self::$engineDistributed : self::$engine;

    return $engine->getKeysInfo($prefix);
  }

  /**
   * Get keys information
   *
   * @param string $prefix Only keys matching this prefix
   *
   * @return array Information
   */
  static function getKeysInfo($prefix = null) {
    return self::_getKeysInfo(false, $prefix);
  }

  /**
   * Get the engine
   *
   * @return ISharedMemory
   */
  static function getEngine() {
    return self::_getEngine(false);
  }

  /**
   * Get the engine
   *
   * @param bool $distributed Distributed
   *
   * @return ISharedMemory
   */
  static function _getEngine($distributed) {
    return $distributed ? self::$engineDistributed : self::$engine;
  }

  /**
   * Get the engines infos
   *
   */
  static function getEngines() {
    return [
      'engine'             => self::$engine,
      'engine_distributed' => self::$engineDistributed,
    ];
  }
}
