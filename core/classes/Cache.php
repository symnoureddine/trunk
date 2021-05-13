<?php
/**
 * @package Mediboard\Core
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core;

use Ox\Core\Composer\CComposerScript;

/**
 * Multi-layers cache utility class
 * Using inner, outer or distributed layer or any combination of those
 */
class Cache {
  /**
   * No cache layer used
   * Useful for testing purposes
   */
  const NONE = 0;
  /**
   * INNER layer will use PHP static storage
   * Cache is available at an HTTP request level
   * Be aware: Values are manipulated by *reference* and subject to contextualisation issues
   */
  const INNER = 1;

  /**
   * OUTER strategy will use the shared memory active engine, like APC or FileSystem
   * Cache is available at an HTTP server level
   * Values are manipulated by copy (serialization)
   */
  const OUTER = 2;

  /**
   * DISTR stragery will user the distributed active shared memory, like Redis or any distributed key-value facility
   * Cache is available at a web servers farm level.
   * Be aware: so far no mechanism would allow the DISTR layer to prune other servers OUTER
   * So use OUTER and DISTR together very cautiously
   * Values are manipulated by copy (serialization)
   */
  const DISTR = 4;

  /**
   * The standard and default INNER and DISTR layer combination
   * Very performant, should be used in cases where you manage clearing
   **/
  const INNER_DISTR = 5;

  /**
   * The standard and default INNER and OUTER layer combination
   * Very performant, should be used in most cases
   **/
  const INNER_OUTER = 3;

  /** @var array[] Count cache usages per key and layer */
  static $hits = array();

  /** @var array[] Count cache totals per key and layer */
  static $totals = array();

  /** @var int Cache overal total usage */
  static $total = 0;

  /** @var array string[] All layers labels */
  static $all_layers = array("NONE", "INNER", "OUTER", "DISTR");

  /** @var array The actual PHP static cache data */
  private static $data = array();

  /** @var string */
  public $prefix;

  /** @var string */
  public $key;

  /** @var integer */
  public $layers;

  /** @var mixed */
  public $value;

  /** @var int|null */
  private $ttl;

  /**
   * Get information about key existence and usage among the different layers
   *
   * @param string $key The key to get information about
   *
   * @return array
   */
  static function info($key) {
    return array(
      "INNER" => array(
        "exist" => isset(self::$data[$key]),
        "usage" => isset(self::$hits[$key][Cache::INNER]) ? self::$hits[$key]["INNER"] : 0,
      ),
      "OUTER" => array(
        "exist" => SHM::exists(self::$data[$key]),
        "usage" => isset(self::$hits[$key][Cache::OUTER]) ? self::$hits[$key]["OUTER"] : 0,
      ),
      "DISTR" => array(
        "exist" => DSHM::exists(self::$data[$key]),
        "usage" => isset(self::$hits[$key][Cache::DISTR]) ? self::$hits[$key]["DISTR"] : 0,
      ),
    );
  }

  /**
   * Construct a cache operator
   *
   * @param string          $prefix Prefix to the key, for categorizing, typically __METHOD__
   * @param string|string[] $key    The key of the value to access, a string or array of strings, typically func_get_args()
   * @param integer         $layers Any combination of cache layers
   * @param int             $ttl    TTL for the key, only for OUTER and DISTR layers
   */
  public function __construct($prefix, $key, $layers, $ttl = null) {
    // no cache when running composer script
    if (CComposerScript::$is_running) {
      // expected when clearing cache with cache manager
      $trace = debug_backtrace();
      if(!isset($trace[1]) || $trace[1]['class'] !== CacheManager::class ){
        $layers = static::NONE;
      }
    }

    $this->key    = is_array($key) ? implode("-", $key) : "$key";
    $this->prefix = $prefix;
    $this->layers = $layers;
    $this->ttl    = $ttl;
  }

  /**
   * Record usage for a key and layer, for stats and info purpose
   *
   * @param int $layer The used layer to retrieve the value
   *
   * @return void
   */
  private function _hit($layer) {
    if (!isset(self::$hits[$this->prefix][$this->key][$layer])) {
      self::$hits[$this->prefix][$this->key] = array_fill_keys(Cache::$all_layers, 0);
    }

    if (!isset(self::$totals[$this->prefix][$layer])) {
      self::$totals[$this->prefix] = array_fill_keys(Cache::$all_layers, 0);
    }

    self::$hits[$this->prefix][$this->key][$layer]++;
    self::$totals[$this->prefix][$layer]++;
    self::$total++;
  }

  /**
   * Inform whether value if available in one of the defined cache layers
   *
   * @return bool
   */
  public function exists() {
    $layers = $this->layers;

    // Inner cache
    if ($layers & Cache::INNER) {
      if (array_key_exists($this->prefix, self::$data) && array_key_exists($this->key, self::$data[$this->prefix])) {
        return true;
      }
    }

    // Flat key for outer and distributed layers
    $key = "$this->prefix-$this->key";

    // Outer cache
    if ($layers & Cache::OUTER) {
      if (SHM::exists($key)) {
        return true;
      }
    }

    // Distributed cache
    if ($layers & Cache::DISTR) {
      if (DSHM::exists($key)) {
        return true;
      }
    }

    return false;
  }

  /**
   * Get a value from the cache
   *
   * @return mixed
   */
  public function get() {
    $layers = $this->layers;

    // Inner cache
    if ($layers & Cache::INNER) {
      if (isset(self::$data[$this->prefix][$this->key])) {
        $this->_hit("INNER");

        return self::$data[$this->prefix][$this->key];
      }
    }

    // Flat key for outer and distributed layers
    $key = "$this->prefix-$this->key";

    // Outer cache
    if ($layers & Cache::OUTER) {
      if (null !== $value = SHM::get($key)) {
        if ($layers & Cache::INNER) {
          self::$data[$this->prefix][$this->key] = $value;
        }

        $this->_hit("OUTER");

        return $value;
      }
    }

    // Distributed cache
    if ($layers & Cache::DISTR) {
      if (null !== $value = DSHM::get($key)) {
        if ($layers & Cache::OUTER) {
          SHM::put($key, $value);
        }

        if ($layers & Cache::INNER) {
          self::$data[$this->prefix][$this->key] = $value;
        }

        $this->_hit("DISTR");

        return $value;
      }
    }

    $this->_hit("NONE");

    return null;
  }

  /**
   * Put a value to the cache
   *
   * @param mixed $value    The value to set
   * @param bool  $compress Compress data for copy strategy layers
   *
   * @return mixed The value, for return chaining
   */
  public function put($value, $compress = false) {
    $layers = $this->layers;

    // Inner cache
    if ($layers & Cache::INNER) {
      self::$data[$this->prefix][$this->key] = $value;
    }

    // Flat key for outer and distributed layers
    $key = "$this->prefix-$this->key";

    $ttl = $this->ttl;

    // Outer cache
    if ($layers & Cache::OUTER) {
      SHM::put($key, $value, $compress, $ttl);
    }

    // Distributed cache
    if ($layers & Cache::DISTR) {
      DSHM::put($key, $value, $compress, $ttl);
    }

    return $value;
  }

  /**
   * Remove a value from all defined layers of the cache
   *
   * @return void
   */
  public function rem() {
    $layers = $this->layers;

    // Inner cache
    if ($layers & Cache::INNER) {
      unset(self::$data[$this->prefix][$this->key]);
    }

    // Flat key for outer and distributed layers
    $key = "$this->prefix-$this->key";

    // Outer cache
    if ($layers & Cache::OUTER) {
      SHM::rem($key);
    }

    // Distributed cache
    if ($layers & Cache::DISTR) {
      DSHM::rem($key);
    }
  }

  /**
   * Empty the INNER static cache
   *
   * @return void
   */
  public static function flushInner() {
    static::$data = [];
  }
}
