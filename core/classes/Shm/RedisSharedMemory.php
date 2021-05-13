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
use Ox\Core\CRedisClient;
use Ox\Core\Mutex\CMbRedisMutex;
use Ox\Mediboard\System\CRedisServer;

/**
 * Redis based Shared Memory
 */
class RedisSharedMemory implements ISharedMemory {
  /** @var CRedisClient */
  public $conn;

  /**
   * @inheritdoc
   */
  function init() {
    // Don't use autloader
    include_once __DIR__ . "/../CRedisClient.php";

    return (bool)$this->getClient();
  }

  /**
   * Gets the Redis client
   *
   * @return CRedisClient|null
   */
  private function getClient() {
    if (!$this->conn || !$this->conn->isConnected()) {
      $this->conn = CRedisServer::getClient();
    }

    return $this->conn;
  }

  /**
   * @inheritdoc
   */
  function get($key) {
    $client = $this->getClient();

    if (!$client->has($key)) {
      return null;
    }

    $value = unserialize($client->get($key));

    if (isset($value["content"])) {
      return $value["content"];
    }

    // Handle visualisation of session data
    if (isset($value["data"])) {
      return $value["data"];
    }

    return null;
  }

  /**
   * @inheritdoc
   */
  function multipleGet($keys) {
    $client = $this->getClient();

    $values = $client->send('mget', $keys);

    $results = array();
    foreach ($values as $_i => $_value) {
      $_value = unserialize($_value);

      if (isset($_value["content"])) {
        $_value = $_value["content"];
      }

      $results[$_i] = $_value;
    }

    return $results;
  }

  /**
   * @inheritdoc
   */
  function put($key, $value, $ttl = null) {
    $data = array(
      "content" => $value,
      "ctime"   => time(),
    );

    return $this->getClient()->set($key, serialize($data), $ttl);
  }

  /**
   * @inheritdoc
   */
  function rem($key) {
    return $this->getClient()->remove($key);
  }

  /**
   * @inheritdoc
   */
  function remKeys($keys) {
    return $this->getClient()->send('del', $keys);
  }

  /**
   * @inheritdoc
   */
  function exists($key) {
    return $this->getClient()->has($key);
  }

  /**
   * @inheritdoc
   */
  function listKeys($prefix, $pattern = null) {
    return $this->getClient()->findKeys($prefix.($pattern ?: "*"));
  }

  /**
   * @inheritdoc
   */
  function modDate($key) {
    $client = $this->getClient();

    if (!$client->has($key)) {
      return null;
    }

    $data = unserialize($client->get($key));

    if (empty($data["ctime"])) {
      return null;
    }

    return strftime(CMbDT::ISO_DATETIME, $data["ctime"]);
  }

  /**
   * @inheritdoc
   */
  function info($key) {
    return false;
  }

  /**
   * @inheritdoc
   */
  function getEngineVersion() {
    $conn = $this->getClient();
    $info = $conn->parseMultiLine($conn->getStats());

    return array(
      "Redis",
      $info["redis_version"],
    );
  }

  /**
   * @inheritdoc
   */
  function getInfo() {
    $conn = $this->getClient();
    $info = $conn->parseMultiLine($conn->getStats());
    $cache_list = $conn->findKeys("*");

    $hit_rate = 0;
    if (intval($info["keyspace_hits"] + $info["keyspace_misses"]) > 0) {
      $hit_rate = round(($info["keyspace_hits"]/($info["keyspace_misses"]+$info["keyspace_hits"]))*100, 2);
    }

    $shm_global_info = array(
      "_all_"      => $info,
      "engine"     => "Redis",
      "version"    => $info["redis_version"],

      "hits"       => $info["keyspace_hits"],
      "misses"     => $info["keyspace_misses"],
      "hit_rate"   => $hit_rate,

      "entries"    => count($cache_list),
      "expunges"   => $info["evicted_keys"],
      "start_time" => $info["uptime_in_seconds"],

      "used"       => $info["used_memory"],
      "total"      => null,
      "total_rate" => null,

      "instance_count" => 0,
      "instance_size"  => 0,
    );

    $prefix = preg_replace('/[^\w]+/', "_", CAppUI::conf("root_dir"));

    $instance_count = 0;
    $instance_size = 0;

    $shm_entries_by_prefix = array();

    foreach ($cache_list as $_key) {
      if (!$conn->has($_key) || (strpos($_key, $prefix) !== 0)) {
        continue;
      }

      $_mem = strlen($conn->get($_key));
      $instance_count++;
      $instance_size += $_mem;

      $_prefix = substr($_key, strlen($prefix)+1);
      // Correction pour la vue des entrées de cache
      $_prefix = str_replace('\\', '\\\\', $_prefix);

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
    $entries = array();

    $root_prefix = preg_replace('/[^\w]+/', "_", CAppUI::conf("root_dir"));

    $pattern = "-$prefix*";
    $keys = $this->listKeys($root_prefix, $pattern);
    $_prefix  = $root_prefix.$pattern;

    $_client = $this->getClient();

    foreach ($keys as $_key) {
      $_value = $_client->get($_key);

      $_entry = array(
        "ctime"     => null,
        "mtime"     => null,
        "atime"     => null,
        "size"      => strlen($_value),
        "hits"      => null,
        "ttl"       => $_client->send("TTL", array($_key)),
        "ref_count" => null,
        "key"       => substr($_key, strlen($root_prefix) + 1),
      );

      $entries[substr($_key, strlen($_prefix))] = $_entry;
    }

    return $entries;
  }
}