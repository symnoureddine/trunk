<?php
/**
 * @package Mediboard\Core
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core;

use Ox\Core\Redis\Yampee\Yampee_Redis_Client;

/**
 * Redis client
 */
class CRedisClient extends Yampee_Redis_Client {
  /** @var Chronometer */
  static $chrono = null;

  /** @var bool */
  static $log = false;

  /** @var bool */
  static $trace = false;

  /** @var bool */
  static $report = false;

  /** @var array[] */
  static $log_entries = array();

  /** @var array[] */
  static $report_data = array(
    'totals'  => array(
      'count'    => 0,
      'duration' => 0,
    ),
    'queries' => array(),
  );

  /**
   * @inheritdoc
   */
  function __construct($host = 'localhost', $port = 6379) {
    if (!static::$chrono) {
      static::$chrono = new Chronometer();
    }

    parent::__construct($host, $port);
  }

  /**
   * Connect (or reconnect) to Redis with given parameters
   *
   * @param int $timeout Timeout
   *
   * @return static
   */
  public function connect($timeout = 5) {
    static::$chrono->start();
    $this->connection = new CRedisConnection($this->host, $this->port, $timeout);
    static::$chrono->stop();

    // Chrono messaging
    if (static::$trace) {
      // todo import in dev toolBar ?
      //echo utf8_decode(CMbString::highlightCode('http', 'CONNECT', false, 'white-space: pre-wrap;'));

      $step  = static::$chrono->latestStep * 1000;
      $total = static::$chrono->total * 1000;

      $pace    = floor(2 * log10($step));
      $pace    = max(0, min(6, $pace));
      $message = "nosql-query-pace-{$pace}";
      $type    = floor(($pace + 3) / 2);
      // todo import in dev toolBar ?
      //CAppUI::stepMessage($type, $message, $step, $total);
    }

    // Query log, durations in micro-seconds
    if (static::$log) {
      static::$log_entries[] = array('CONNECT', round(static::$chrono->latestStep * 1000000));
    }

    return $this;
  }

  /**
   * Check whether client is connected or not
   *
   * @return bool
   */
  public function isConnected() {
    return !!$this->connection;
  }

  /**
   * Close connection
   *
   * @return void
   */
  public function close() {
    $this->connection = null;
  }

  /**
   * @inheritdoc
   */
  function execute(array $arguments) {
    static::$chrono->start();
    $return = parent::execute($arguments);
    static::$chrono->stop();

    // Chrono messaging
    if (static::$trace) {
      $step  = static::$chrono->latestStep * 1000;
      $total = static::$chrono->total * 1000;

      $pace    = floor(2 * log10($step));
      $pace    = max(0, min(6, $pace));
      $message = "nosql-query-pace-{$pace}";
      //$type    = floor(($pace + 3) / 2);

      $query = static::sampleQuery(implode(' ', $arguments));

      CApp::queryTrace($message, 'nosql', $step, $total, $query);
    }

    // Query log, durations in micro-seconds
    if (static::$log) {
      static::$log_entries[] = array(implode(' ', $arguments), round(static::$chrono->latestStep * 1000000));
    }

    return $return;
  }

  /**
   * Set a value without overwriting if it already exists
   *
   * @param string $key   Key
   * @param mixed  $value Value
   *
   * @return mixed
   */
  function setNX($key, $value) {
    return $this->send("SETNX", array($key, $value));
  }

  /**
   * Renames key to newkey if newkey does not yet exist.
   * It returns an error under the same conditions as RENAME.
   *
   * @param string $key     Key
   * @param string $new_key New key
   *
   * @return mixed
   */
  function renameNX($key, $new_key) {
    return $this->send("RENAMENX", array($key, $new_key));
  }

  /**
   * Set a timeout on key. After the timeout has expired, the key will automatically be deleted.
   *
   * @param string $key     Key
   * @param float  $seconds Seconds
   *
   * @return int 1 or 0
   */
  function expire($key, $seconds) {
    return $this->send("EXPIRE", array($key, $seconds));
  }

  /**
   * Atomic get / set
   *
   * @param string $key   Key
   * @param mixed  $value Value
   *
   * @return mixed The old value
   */
  function getSet($key, $value) {
    return $this->send("GETSET", array($key, $value));
  }

  /**
   * Start a transaction
   *
   * @return void
   */
  function multi() {
    $this->send("MULTI");
  }

  /**
   * Exec a transaction, atomically
   *
   * @return array An array of the results
   */
  function exec() {
    return $this->send("EXEC");
  }

  /**
   * Parse a multi line response
   *
   * @param string $response    Response
   * @param bool   $with_titles With titles or not
   *
   * @return array
   */
  function parseMultiLine($response, $with_titles = false) {
    $lines = preg_split('/[\r\n]+/', trim($response));

    $values = array();

    if ($with_titles) {
      $_current_group = array();
      $_current_title = null;
      foreach ($lines as $_line) {
        if ($_line[0] === "#") {
          if ($_current_title && !empty($_current_group)) {
            $values[$_current_title] = $_current_group;
          }

          $_current_title = trim(substr($_line, 1));
          $_current_group = array();
          continue;
        }

        list($_key, $_value) = explode(":", $_line, 2);
        $_current_group[$_key] = $_value;
      }

      if ($_current_title && !empty($_current_group)) {
        $values[$_current_title] = $_current_group;
      }
    }
    else {
      $lines = array_filter(
        $lines,
        function ($v) {
          return strpos($v, "#") !== 0;
        }
      );

      foreach ($lines as $_line) {
        list($_key, $_value) = explode(":", $_line, 2);
        $values[$_key] = $_value;
      }
    }

    return $values;
  }

  /**
   * Makes a hash from the query
   *
   * @param string $query The query to hash
   *
   * @return string The hash
   */
  static function hashQuery($query) {
    // Remove SHM prefix
    $query = str_replace(DSHM::getPrefix(), '', $query);

    $_query  = explode(' ', $query);
    $command = (isset($_query[0])) ? $_query[0] : '';
    $key     = (isset($_query[1])) ? $_query[1] : '';

    // Remove hexadecimal suffix at the end
    $key = preg_replace('/\-([\da-f]+)$/i', '<hexa>', $key);

    return md5("{$command} {$key}");
  }

  /**
   * Makes a sample from the query
   *
   * @param string $query The query
   *
   * @return string The sample
   */
  static function sampleQuery($query) {
    // Remove SHM prefix
    $query = str_replace(DSHM::getPrefix(), '', $query);

    $_query  = explode(' ', $query);
    $command = (isset($_query[0])) ? $_query[0] : '';
    $key     = (isset($_query[1])) ? $_query[1] : '';
    $values  = (isset($_query[2])) ? mb_strimwidth(implode(' ', array_slice($_query, 2)), 0, 25, '[...]') : '';

    return trim("{$command} {$key} {$values}");
  }

  /**
   * Build the report
   *
   * @param null $limit Limit/truncate the number of queries signatures, starting from the most time consuming
   *
   * @return void
   */
  static function buildReport($limit = null) {
    $queries =& static::$report_data['queries'];
    $totals  =& static::$report_data['totals'];

    // Compute queries data
    foreach (static::$log_entries as $_entry) {
      list($sample, $duration) = $_entry;
      $hash = static::hashQuery($sample);

      if (!isset($queries[$hash])) {
        $queries[$hash] = array(
          'sample'       => static::sampleQuery($sample),
          'duration'     => 0,
          'distribution' => array(),
        );
      }

      $query             =& $queries[$hash];
      $query['duration'] += $duration;

      $level = (int)floor(log10($duration) + .5);
      if (!isset($query['distribution'][$level])) {
        $query['distribution'][$level] = 0;
      }
      $query['distribution'][$level]++;

      // Update totals
      $totals['count']++;;
      $totals['duration'] += $duration;
    }

    // SORT_DESC with SORT_NUMERIC won't work so array is reversed in a second pass
    array_multisort($queries, SORT_ASC, SORT_NUMERIC, CMbArray::pluck($queries, 'duration'));
    $queries = array_values(array_reverse($queries));

    if ($limit) {
      $queries = array_slice($queries, 0, $limit);
    }
  }

  /**
   * Displays the report
   *
   * @param array $report_data Report data as input instead of current view report data
   * @param bool  $inline      Echo inline the report
   *
   * @return void
   */
  static function displayReport($report_data = null, $inline = true) {
    $current_report_data = static::$report_data;

    if ($report_data) {
      static::$report_data = $report_data;
    }

    $queries =& static::$report_data["queries"];
    $totals  =& static::$report_data["totals"];

    // Get counts per query
    foreach ($queries as &$_query) {
      $_query["count"] = array_sum($_query["distribution"]);
    }

    // Report might be truncated versus all query log
    $report_totals = array(
      "count"    => array_sum(CMbArray::pluck($queries, "count")),
      "duration" => array_sum(CMbArray::pluck($queries, "duration")),
    );

    if ($inline) {
      echo '<h2>NoSQL</h2>';
    }

    if (($report_totals["count"] != $totals["count"]) && $inline) {
      CAppUI::stepMessage(
        UI_MSG_WARNING,
        "Report is truncated to the %d most time consuming query signatures (%d%% of queries count, %d%% of queries duration).",
        count($queries),
        $report_totals["count"] * 100 / $totals["count"],
        $report_totals["duration"] * 100 / $totals["duration"]
      );
    }
    // Unset to prevent second foreach confusion
    unset($_query);

    // Print the report
    foreach ($queries as $_index => $_query) {
      $_dist = $_query["distribution"];
      ksort($_dist);
      $ticks = array("  1&micro; ", " 10&micro; ", "100&micro; ", "  1ms ", " 10ms ", "100ms ", "  1s ", " 10s ", "100s ");
      $lines = array();
      foreach ($_dist as $_level => $_count) {
        $line = $ticks[$_level];
        $max  = 100;
        while ($_count > $max) {
          $line   .= str_pad("", $max, "#") . "\n      ";
          $_count -= $max;
        }
        $line    .= str_pad("", $_count, "#");
        $lines[] = $line;
      }
      $distribution = "<pre>" . implode("\n", $lines) . "</pre>";

      if ($inline) {
        CAppUI::stepMessage(
          UI_MSG_OK,
          "Query %d: was called %d times [%d%%] for %01.3fms [%d%%]",
          $_index + 1,
          $_query["count"],
          $_query["count"] * 100 / $totals["count"],
          $_query["duration"] / 1000,
          $_query["duration"] * 100 / $totals["duration"]
        );

        echo utf8_decode(CMbString::highlightCode('http', $_query["sample"], false, "white-space: pre-wrap;"));


        echo "<pre>" . implode("\n", $distribution) . "</pre>";
      }
      else {
        $info = CAppUI::tr(
          "NOSQL Query %d: was called %d times [%d%%] for %01.3fms [%d%%]",
          $_index + 1,
          $_query["count"],
          $_query["count"] * 100 / $totals["count"],
          $_query["duration"] / 1000,
          $_query["duration"] * 100 / $totals["duration"]
        );

        CApp::queryReport($info, $_query["sample"], $distribution);
      }
    }

    static::$report_data = $current_report_data;
  }
}
