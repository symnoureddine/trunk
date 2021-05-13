<?php
/**
 * @package Mediboard\Core
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core;

/**
 * Cache information class
 */
class CacheInfo {
  /**
   * Retrieves PHP opcode cache information
   *
   * @return array
   */
  public static function getOpcodeCacheInfo() {
    $root = dirname(realpath(__DIR__));

    $opcache_info = array(
      "engine"  => "none",
      "version" => null,

      // Global
      "total"         => 0,
      "used"          => 0,
      "total_rate"    => 0,

      "free"          => 0,
      "wasted"        => 0,
      "start_time"    => 0,

      "instance_size"  => 0,
      "instance_count" => 0,

      // Scripts
      "entries" => 0,
      "hits"    => 0,
      "misses"  => 0,
      "hit_rate"  => 0,

      "entries_by_prefix" => array(),
    );

    /* ------- Zend OPcache -------  */
    if (extension_loaded("Zend OPcache") && ini_get("opcache.enable")) {
      $opcache_info["engine"]  = "OPcache";
      $opcache_info["version"] = phpversion("Zend OPcache");

      $opcache_status = opcache_get_status(true);
      $opcache_config = opcache_get_configuration();

      //$isb  = $opcache_status["interned_strings_usage"];
      $memory  = $opcache_status["memory_usage"];
      $stat    = $opcache_status["opcache_statistics"];
      $scripts = isset($opcache_status["scripts"]) ? $opcache_status["scripts"] : array();

      $hit_rate = 0;
      if (intval($stat["hits"] + $stat["misses"]) > 0) {
        $hit_rate = round(($stat["hits"]/($stat["misses"]+$stat["hits"]))*100, 2);
      }

      // Global
      $opcache_info["total"]         = $opcache_config["directives"]["opcache.memory_consumption"];
      $opcache_info["used"]          = $memory["used_memory"];
      $opcache_info["total_rate"]    = 100 * $memory["used_memory"] / $opcache_config["directives"]["opcache.memory_consumption"];

      $opcache_info["free"]          = $memory["free_memory"];
      $opcache_info["wasted"]        = $memory["wasted_memory"];
      $opcache_info["start_time"]    = $stat["start_time"];

      $opcache_info["instance_size"]  = 0;
      $opcache_info["instance_count"] = 0;

      // Interned string buffer
      /*"isb_total"     => $isb["buffer_size"],
      "isb_used"      => $isb["used_memory"],
      "isb_free"      => $isb["free_memory"],
      "isb_count"     => $isb["number_of_strings"],*/

      // Scripts
      $opcache_info["entries"] = $stat["num_cached_scripts"];
      $opcache_info["hits"]    = $stat["hits"];
      $opcache_info["misses"]  = $stat["misses"];
      $opcache_info["hit_rate"] = $hit_rate;

      $entries_by_prefix = array();
      $prefix = preg_replace('/[^\w]+/', "_", CAppUI::conf("root_dir"));

      foreach ($scripts as $_key => $_info) {
        if (strpos($_key, $root) !== 0) {
          continue;
        }

        $opcache_info["instance_size"] += $_info["memory_consumption"];
        $opcache_info["instance_count"]++;

        $_unprefixed = ltrim(substr($_key, strlen($prefix)+1), "\\/");

        $_pos = strpos($_unprefixed, "\\");
        if ($_pos !== false) {
          $_prefix = substr($_unprefixed, 0, $_pos+1);
        }
        else {
          $_prefix = $_unprefixed;
        }

        if (!isset($entries_by_prefix[$_prefix])) {
          $entries_by_prefix[$_prefix] = array(
            "count" => 0,
            "size"  => 0,
          );
        }

        $entries_by_prefix[$_prefix]["count"]++;
        $entries_by_prefix[$_prefix]["size"] += $_info["memory_consumption"];
      }

      $opcache_info["entries_by_prefix"] = $entries_by_prefix;
      
      return $opcache_info;
    }

    /* ------- APC-------  */
    if (extension_loaded("apc") && !extension_loaded("apcu") && ini_get("apc.enabled")) {
      $opcache_info["engine"]  = "APC";
      $opcache_info["version"] = phpversion("apc");

      $opcache_status = apc_cache_info();
      $mem = apc_sma_info();

      $hit_rate = 0;
      if (intval($opcache_status["num_hits"] + $opcache_status["num_misses"]) > 0) {
        $hit_rate = round(($opcache_status["num_hits"]/($opcache_status["num_misses"]+$opcache_status["num_hits"]))*100, 2);
      }

      // Global
      $opcache_info["total"]         = CMbString::fromDecaBinary(ini_get("apc.shm_size"));
      $opcache_info["used"]          = $mem['num_seg'] * $mem['seg_size'];
      $opcache_info["total_rate"]    = 100 * $opcache_info["used"] / $opcache_info["total"];

      $opcache_info["free"]          = $mem['avail_mem'];
      $opcache_info["wasted"]        = null;
      $opcache_info["start_time"]    = null;

      $opcache_info["instance_size"]  = 0;
      $opcache_info["instance_count"] = 0;

      // Scripts
      $opcache_info["entries"] = count($opcache_info["cache_list"]);
      $opcache_info["hits"]    = $opcache_status["num_hits"];
      $opcache_info["misses"]  = $opcache_status["num_misses"];
      $opcache_info["hit_rate"] = $hit_rate;

      $entries_by_prefix = array();
      $prefix = preg_replace('/[^\w]+/', "_", CAppUI::conf("root_dir"));

      foreach ($opcache_info["cache_list"] as $_info) {
        $_key = $_info["filename"];
        if (strpos($_key, $root) !== 0) {
          continue;
        }

        $opcache_info["instance_size"] += $_info["mem_size"];
        $opcache_info["instance_count"]++;

        $_unprefixed = ltrim(substr($_key, strlen($prefix)+1), "\\/");

        $_pos = strpos($_unprefixed, "\\");
        if ($_pos !== false) {
          $_prefix = substr($_unprefixed, 0, $_pos+1);
        }
        else {
          $_prefix = $_unprefixed;
        }

        if (!isset($entries_by_prefix[$_prefix])) {
          $entries_by_prefix[$_prefix] = array(
            "count" => 0,
            "size"  => 0,
          );
        }

        $entries_by_prefix[$_prefix]["count"]++;
        $entries_by_prefix[$_prefix]["size"] += $_info["mem_size"];
      }

      $opcache_info["entries_by_prefix"] = $entries_by_prefix;

      return $opcache_info;
    }

    return $opcache_info;
  }

  /**
   * Retrieves PHP opcode cache information
   *
   * @param string $prefix Prefix to match
   *
   * @return array
   */
  public static function getOpcodeKeysInfo($prefix = null) {
    $root = rtrim(str_replace("\\", "/", dirname(realpath(__DIR__))), "/");
    $prefix = rtrim(str_replace("\\", "/", $prefix), "/");

    $entries = array();

    /* ------- APC-------  */
    if (extension_loaded("apc") && !extension_loaded("apcu") && ini_get("apc.enabled")) {
      $opcache_status = apc_cache_info();

      foreach ($opcache_status["cache_list"] as $_info) {
        $_key = $_info["filename"];

        if (strpos($_key, "$root/$prefix") !== 0) {
          continue;
        }

        $_unrooted = ltrim(substr($_key, strlen($root)+1), "\\/");

        $entries[] = $_info;
      }
    }

    /* ------- Zend OPcache -------  */
    if (extension_loaded("Zend OPcache") && ini_get("opcache.enable")) {
      $opcache_status = opcache_get_status(true);
      $scripts = $opcache_status["scripts"];

      foreach ($scripts as $_key => $_info) {
        $_key = str_replace("\\", "/", $_key);
        $_prefix = "$root/$prefix";

        if (strpos($_key, $_prefix) !== 0) {
          continue;
        }

        $_subkey = substr($_key, strlen($_prefix)+1);

        $_entry = array(
          "ctime"     => $_info["timestamp"] ? strftime(CMbDT::ISO_DATETIME, $_info["timestamp"]) : null,
          "mtime"     => $_info["timestamp"] ? strftime(CMbDT::ISO_DATETIME, $_info["timestamp"]) : null,
          "atime"     => strftime(CMbDT::ISO_DATETIME, $_info["last_used_timestamp"]),
          "size"      => $_info["memory_consumption"],
          "hits"      => $_info["hits"],
          "ttl"       => null,
          "ref_count" => null,
        );

        $entries[$_subkey] = $_entry;
      }
    }

    ksort($entries);

    return $entries;
  }

  /**
   * Retrieves assets (JS, CSS) cache information
   *
   * @return array
   */
  public static function getAssetsCacheInfo() {
    $tmp = realpath(__DIR__."/../../tmp");

    $info = array(
      "versionKey" => CApp::getVersionKey(),
      "css" => array(),
      "css_total" => 0,
      "js"  => array(),
      "js_total" => 0,
    );

    $css_files = glob("$tmp/*.css");
    foreach ($css_files as $_file) {
      $_entry = array(
        "name" => basename($_file),
        "size" => filesize($_file),
        "date" => strftime(CMbDT::ISO_DATETIME, filemtime($_file)),
      );

      $info["css_total"] += $_entry["size"];

      $info["css"][] = $_entry;
    }

    $js_files  = glob("$tmp/*.js");
    foreach ($js_files as $_file) {
      $_entry = array(
        "name" => basename($_file),
        "size" => filesize($_file),
        "date" => strftime(CMbDT::ISO_DATETIME, filemtime($_file)),
      );

      $info["js_total"] += $_entry["size"];

      $info["js"][] = $_entry;
    }

    return $info;
  }
}