<?php
/**
 * @package Mediboard\Core\Shm
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Shm;

use Ox\Core\CAppUI;
use Ox\Core\CMbDT;
use Ox\Core\CMbSemaphore;
use Ox\Core\CMbString;

/**
 * Alternative PHP Cache (APC) based Memory class
 */
class APCSharedMemory implements ISharedMemory {
  protected $_cache_key = "info";

  /**
   * @inheritdoc
   */
  function init() {
    return function_exists('apc_fetch') &&
    function_exists('apc_store') &&
    function_exists('apc_delete');
  }

  /**
   * @inheritdoc
   */
  function get($key) {
    $value = apc_fetch($key);
    return $value !== false ? $value : null;
  }

  /**
   * @inheritdoc
   */
  function multipleGet($keys) {
    $results = array();

    foreach ($keys as $_key) {
      $results[] = $this->get($_key);
    }

    return $results;
  }

  /**
   * @inheritdoc
   */
  function put($key, $value, $ttl = null) {
    return apc_store($key, $value, $ttl);
  }

  /**
   * @inheritdoc
   */
  function rem($key) {
    return apc_delete($key);
  }

  /**
   * @inheritdoc
   */
  function remKeys($keys) {
    return apc_delete($keys);
  }

  /**
   * @inheritdoc
   */
  function exists($key) {
    return apc_exists($key);
  }

  /**
   * @inheritdoc
   */
  function listKeys($prefix, $pattern = null) {
    $info       = apc_cache_info("user");
    $cache_list = $info["cache_list"];
    $cache_key  = $this->_cache_key;

    // Convert pattern to Regex, only * is supported
    if ($pattern) {
      $char = chr(255);
      $pattern = str_replace("*", $char, $pattern);
      $pattern = preg_quote("$prefix$pattern", "/");
      $pattern = str_replace($char, ".+", $pattern);
      $pattern = "/^$pattern$/";
    }

    $keys = array();
    foreach ($cache_list as $_cache) {
      $_key = $_cache[$cache_key];

      if (strpos($_key, $prefix) === 0) {
        if (!$pattern || preg_match($pattern, $_key)) {
          $keys[] = $_key;
        }
      }
    }

    sort($keys);

    return $keys;
  }

  /**
   * @inheritdoc
   */
  function modDate($key) {
    $info       = apc_cache_info("user");
    $cache_list = $info["cache_list"];
    $cache_key  = $this->_cache_key;

    foreach ($cache_list as $_cache) {
      $_key = $_cache[$cache_key];

      if ($_key === $key) {
        return strftime(CMbDT::ISO_DATETIME, $_cache["mtime"]);
      }
    }

    return null;
  }

  /**
   * @inheritdoc
   */
  function info($key) {
    $user_cache = apc_cache_info("user");

    if (!$user_cache) {
      return false;
    }

    $cache_key = $this->_cache_key;

    $cache_info = array(
      "creation_date"     => null,
      "modification_date" => null,
      "num_hits"          => null,
      "mem_size"          => null,
      "compressed"        => null
    );

    foreach ($user_cache["cache_list"] as $_cache_info) {
      if ($_cache_info[$cache_key] === $key) {
        $cache_info["creation_date"]     = strftime(CMbDT::ISO_DATETIME, $_cache_info["creation_time"]);
        $cache_info["modification_date"] = strftime(CMbDT::ISO_DATETIME, $_cache_info["mtime"]);
        $cache_info["num_hits"]          = $_cache_info["num_hits"];
        $cache_info["mem_size"]          = $_cache_info["mem_size"];

        break;
      }
    }

    return $cache_info;
  }

  /**
   * @inheritdoc
   */
  function getEngineVersion() {
    return array(
      "APC",
      phpversion("apc"),
    );
  }

  /**
   * @inheritdoc
   */
  function getInfo() {
    $info = apc_cache_info("user");
    list($engine, $version) = $this->getEngineVersion();

    $hit_rate = 0;
    if (intval($info["num_hits"] + $info["num_misses"]) > 0) {
      $hit_rate = round(($info["num_hits"]/($info["num_misses"]+$info["num_hits"]))*100, 2);
    }

    $total = CMbString::fromDecaBinary(ini_get("apc.shm_size"));
    $shm_global_info = array(
      "_all_"      => $info,
      "engine"     => $engine,
      "version"    => $version,

      "hits"       => $info["num_hits"],
      "misses"     => $info["num_misses"],
      "hit_rate"   => $hit_rate,

      "entries"    => $info["num_entries"],
      "expunges"   => isset($info["num_expunges"]) ? $info["num_expunges"] : $info["expunges"],
      "start_time" => $info["start_time"],

      "used"       => $info["mem_size"],
      "total"      => $total,
      "total_rate" => 100 * $info["mem_size"] / $total,

      "instance_count" => 0,
      "instance_size"  => 0,
    );

    $prefix = preg_replace('/[^\w]+/', "_", CAppUI::conf("root_dir"));

    $instance_count = 0;
    $instance_size = 0;

    $shm_entries_by_prefix = array();

    foreach ($info["cache_list"] as $_file) {
      $_key = $_file["info"];
      if (strpos($_key, $prefix) !== 0) {
        continue;
      }

      $_mem = $_file["mem_size"];
      $instance_count++;
      $instance_size += $_mem;

      $_prefix = substr($_key, strlen($prefix)+1);

      if (($pos = strpos($_prefix, '-')) !== false) {
        $_prefix = substr($_prefix, 0, $pos);
      }

      if (!isset($shm_entries_by_prefix[$_prefix])) {
        $shm_entries_by_prefix[$_prefix] = array(
          "count" => 0,
          "size"  => 0,
        );
      }

      $shm_entries_by_prefix[$_prefix]["count"]++;
      $shm_entries_by_prefix[$_prefix]["size"] += $_mem;
    }

    $shm_global_info["instance_size"]     = $instance_size;
    $shm_global_info["instance_count"]    = $instance_count;

    $shm_global_info["entries_by_prefix"] = $shm_entries_by_prefix;

    return $shm_global_info;
  }

  /**
   * Get information about keys
   *
   * @param string $prefix Only keys matching this prefix
   *
   * @return array
   */
  function getKeysInfo($prefix = null) {
    $info = apc_cache_info("user");

    $root_prefix = preg_replace('/[^\w]+/', "_", CAppUI::conf("root_dir"));

    $entries = array();

    foreach ($info["cache_list"] as $_cache) {
      $_key = $_cache['info'];
      //$_key = str_replace("\\", "/", $_cache["info"]);
      $_prefix = "$root_prefix-$prefix";

      $split_key = explode('-', $_key);

      // Key not separated by "-", probably because of an other application which also uses APC
      if (!array_key_exists(1, $split_key)) {
        continue;
      }

      $_key_prefix = $split_key[1];

      // Keys of another MB instance
      if ($split_key[0] !== $root_prefix) {
        continue;
      }

      if ($prefix !== $_key_prefix) {
        continue;
      }

      $_subkey = substr($_key, strlen($_prefix) + 1);

      $_entry = array(
        "ctime"     => strftime(CMbDT::ISO_DATETIME, isset($_cache["ctime"]) ? $_cache["ctime"] : $_cache["creation_time"]),
        "mtime"     => strftime(CMbDT::ISO_DATETIME, isset($_cache["mtime"]) ? $_cache["mtime"] : $_cache["modification_time"]),
        "atime"     => strftime(CMbDT::ISO_DATETIME, $_cache["access_time"]),
        "size"      => $_cache["mem_size"],
        "hits"      => $_cache["num_hits"],
        "ttl"       => $_cache["ttl"],
        "ref_count" => $_cache["ref_count"],
        "key"       => substr($_key, strlen($root_prefix) + 1),
      );

      $entries[$_subkey] = $_entry;
    }

    ksort($entries);

    return $entries;
  }
}