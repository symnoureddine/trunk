<?php
/**
 * @package Mediboard\Core\Shm
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Shm;


use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CMbDT;
use Ox\Core\CMbPath;
use Ox\Core\CMbSemaphore;

/**
 * Disk based shared memory
 */
class DiskSharedMemory implements ISharedMemory {
  const HEADER = "*MBC"; // For "* Mediboard Cache"

  private $dir;

  /**
   * @inheritdoc
   */
  function __construct() {
    global $dPconfig;
    $dir = $dPconfig['root_dir'] ?? dirname(__DIR__, 3);
    $this->dir = "{$dir}/tmp/shared/";
  }

  /**
   * Produce the path string based on key
   *
   * @param string $key The key to get the path of
   *
   * @return string
   */
  private function _path($key) {
    return $this->dir . CMbPath::sanitizeBaseName($key);
  }

  /**
   * @inheritdoc
   */
  function init() {
    if (!CMbPath::forceDir($this->dir)) {
      trigger_error("Shared memory could not be initialized, ensure that '$this->dir' is writable");
      CApp::rip();
    }

    return true;
  }

  /**
   * @inheritdoc
   */
  function get($key) {
    $path = $this->_path($key);
    // Special case for DiskSharedMemory in order to handle file deletion when TTL is reached
    // Done in get() for performance purpose
    $ttl_ok = true;

    if (file_exists($path)) {
      // Read header if any
      $fp      = fopen($path, 'rb');
      $content = fread($fp, 16);

      if (strpos($content, self::HEADER) === 0) {
        $ttl_ok  = $this->checkTTL($content);
        $content = '';
      }

      while (!feof($fp)) {
        $content .= fread($fp, 8192);
      }

      fclose($fp);

      // TTL reached, deletes the file but returns the content
      if (!$ttl_ok) {
        unlink($path);
      }

      return unserialize($content);
    }

    return null;
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
    $path = $this->_path($key);

    $prefix = "";

    if ($ttl) {
      // Put the special header with this format : [char[4] HEADER] [uint32 EXPIRE] [char[8] RESERVED]
      $prefix = self::HEADER . pack("V", time() + $ttl) . "\0\0\0\0\0\0\0\0";
    }

    return file_put_contents($path, $prefix . serialize($value)) !== false;
  }

  /**
   * @inheritdoc
   */
  function rem($key) {
    $path = $this->_path($key);
    if (is_writable($path)) {
      return unlink($path);
    }

    return false;
  }

  /**
   * @inheritdoc
   */
  function remKeys($keys) {
    $success = true;

    foreach ($keys as $_key) {
      $success = $success && $this->rem($_key);
    }

    return $success;
  }

  /**
   * @inheritdoc
   */
  function exists($key) {
    $path = $this->_path($key);

    if (file_exists($path)) {
      // Read header if any
      $fp      = fopen($path, 'rb');
      $content = fread($fp, 16);
      fclose($fp);

      if (strpos($content, self::HEADER) === 0) {
        $ttl_ok = $this->checkTTL($content);
        // Delete file if TTL has expired
        if (!$ttl_ok) {
          unlink($path);

          return false;
        }

        return true;
      }

      // No TTL
      return true;
    }

    // File does not exists
    return false;
  }

  /**
   * Check if the ttl is still ok
   *
   * @param string $header Header to check
   *
   * @return bool Return false if the ttl is outdated
   */
  protected function checkTTL($header) {
    $unpack = unpack("c4header/Vexpire/Vempty/Vempty", $header);
    $expire = $unpack['expire'];

    return !($expire && ($expire <= time()));
  }

  /**
   * @inheritdoc
   */
  function listKeys($prefix, $pattern = null) {
    $key          = $prefix . ($pattern ?: '*');
    $pattern_glob = $this->_path($key);

    return array_map('basename', glob($pattern_glob));
  }

  /**
   * @inheritdoc
   */
  function modDate($key) {
    $path = $this->_path($key);
    clearstatcache(true, $path);
    if (!file_exists($path)) {
      return null;
    }

    return strftime(CMbDT::ISO_DATETIME, filemtime($path));
  }

  /**
   * @inheritdoc
   */
  function info($key) {
    $cache_info = array(
      "creation_date"     => null,
      "modification_date" => null,
      "num_hits"          => null,
      "mem_size"          => null,
      "compressed"        => null
    );

    $path = $this->_path($key);
    clearstatcache(true, $path);

    if (!file_exists($path)) {
      return false;
    }

    $stats = stat($path);

    $cache_info["creation_date"]     = strftime(CMbDT::ISO_DATETIME, $stats["mtime"]);
    $cache_info["modification_date"] = strftime(CMbDT::ISO_DATETIME, $stats["ctime"]);
    $cache_info["mem_size"]          = $stats["size"];

    return $cache_info;
  }

  /**
   * @inheritdoc
   */
  function getInfo() {
    list($engine, $version) = $this->getEngineVersion();

    $total   = disk_free_space($this->dir);
    $entries = array_fill_keys(scandir($this->dir), null);

    $total_size = 0;
    foreach ($entries as $_file => $_size) {
      $_filesize       = filesize($this->dir . $_file);
      $entries[$_file] = $_filesize;
      $total_size      += $_filesize;
    }

    $shm_global_info = array(
      "_all_"   => array(),
      "engine"  => $engine,
      "version" => $version,

      "hits"     => null,
      "misses"   => null,
      "hit_rate" => null,

      "entries"    => count($entries),
      "expunges"   => null,
      "start_time" => null,

      "used"       => $total_size,
      "total"      => $total,
      "total_rate" => 100 * $total_size / $total,

      "instance_count" => 0,
      "instance_size"  => 0,
    );

    $prefix = preg_replace('/[^\w]+/', "_", CAppUI::conf("root_dir"));

    $instance_count = 0;
    $instance_size  = 0;

    $shm_entries_by_prefix = array();

    foreach ($entries as $_file => $_size) {
      if (strpos($_file, $prefix) !== 0) {
        continue;
      }

      $instance_count++;
      $instance_size += $_size;

      $_unprefixed = substr($_file, strlen($prefix) + 1);

      $_prefix = substr($_unprefixed, 0, strpos($_unprefixed, "-"));

      if (!isset($shm_entries_by_prefix[$_prefix])) {
        $shm_entries_by_prefix[$_prefix] = array(
          "count" => 0,
          "size"  => 0,
        );
      }

      $shm_entries_by_prefix[$_prefix]["count"]++;
      $shm_entries_by_prefix[$_prefix]["size"] += $_size;
    }

    $shm_global_info["instance_size"]  = $instance_size;
    $shm_global_info["instance_count"] = $instance_count;

    $shm_global_info["entries_by_prefix"] = $shm_entries_by_prefix;

    return $shm_global_info;
  }

  /**
   * @inheritdoc
   */
  function getEngineVersion() {
    return array(
      "Disk",
      "n/a",
    );
  }

  /**
   * @inheritdoc
   */
  function getKeysInfo($prefix = null) {
    $entries = array();

    $root_prefix = preg_replace('/[^\w]+/', "_", CAppUI::conf("root_dir"));

    $keys = $this->listKeys("$root_prefix-$prefix");
    foreach ($keys as $_key) {
      $split_key   = explode('-', $_key);
      $_key_prefix = $split_key[1];
      if ($prefix !== $_key_prefix) {
        continue;
      }

      $content = null;
      $path = $this->_path($_key);
      if (file_exists($path)) {
        // Read header if any
        $fp      = fopen($path, 'rb');
        $content = fread($fp, 16);

        if (strpos($content, self::HEADER) === 0) {
          $unpack  = unpack("c4header/Vexpire/Vempty/Vempty", $content);
          $expire  = $unpack['expire'];
          $content = '';
        }

        while (!feof($fp)) {
          $content .= fread($fp, 8192);
        }

        fclose($fp);
      }

      $_entry = array(
        "ctime"     => null,
        "mtime"     => null,
        "atime"     => null,
        "size"      => strlen($content),
        "hits"      => null,
        "ttl"       => null,
        "ref_count" => null,
        "key"       => substr($_key, strlen($root_prefix) + 1),
      );

      $entries[substr($_key, strlen("$root_prefix-$prefix") + 1)] = $_entry;
    }

    ksort($entries);

    return $entries;
  }
}
