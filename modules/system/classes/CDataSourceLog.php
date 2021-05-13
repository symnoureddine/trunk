<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Mediboard\System;

use Ox\Core\CAppUI;
use Ox\Core\CMbDT;
use Ox\Core\CSQLDataSource;

/**
 * Data source resource usage log
 */
class CDataSourceLog extends CModuleActionLog {
  public $datasourcelog_id;

  // log unique logical key fields (signature)
  public $module_action_id;
  public $datasource;
  public $period;
  public $aggregate;
  public $bot;

  // Log data fields
  public $requests;
  public $duration;
  public $connections;
  public $ping_duration;
  public $connection_time;

  // Object Reference
  public $_module;
  public $_action;

  /**
   * @inheritdoc
   */
  function getSpec() {
    $spec           = parent::getSpec();
    $spec->loggable = false;
    $spec->table    = 'datasource_log';
    $spec->key      = 'datasourcelog_id';

    return $spec;
  }

  /**
   * @inheritdoc
   */
  function getProps() {
    $props                     = parent::getProps();
    $props["datasource"]       = "str notNull";
    $props["period"]           = "dateTime notNull";
    $props["requests"]         = "num";
    $props["duration"]         = "float";
    $props["connections"]      = "num";
    $props["ping_duration"]    = "float";
    $props["connection_time"]  = "float";
    $props["aggregate"]        = "num min|0 default|10";
    $props["bot"]              = "enum list|0|1 default|0";
    $props['module_action_id'] = "ref notNull class|CModuleAction back|datasource_logs";

    $props["_module"] = "str";
    $props["_action"] = "str";

    return $props;
  }

  /**
   * @inheritdoc
   */
  static function getSignatureFields() {
    return array("module_action_id", "period", "datasource", "aggregate", "bot");
  }

  /**
   * Load aggregated statistics
   *
   * @param string $start     Start date
   * @param string $end       End date
   * @param int    $groupmod  Grouping mode
   * @param null   $module    Module name
   * @param string $human_bot Human/bot filter
   *
   * @return CAccessLog[]
   */
  static function loadAggregation($start, $end, $groupmod = 0, $module = null, $human_bot = null) {
    $dl    = new static;
    $table = $dl->_spec->table;

    switch ($groupmod) {
      case 2:
        $query = "SELECT
            `datasourcelog_id`,
            $table.`module_action_id`,
            `period`,
            0 AS grouping
          FROM $table
          WHERE $table.`period` BETWEEN '$start' AND '$end'";
        break;

      case 0:
      case 1:
        $query = "SELECT
          `datasourcelog_id`,
          $table.`module_action_id`,
          `module_action`.`module` AS _module,
          `module_action`.`action` AS _action,
          `period`,
          0 AS grouping
        FROM $table
        LEFT JOIN `module_action` ON $table.`module_action_id` = `module_action`.`module_action_id`
        WHERE $table.`period` BETWEEN '$start' AND '$end'";
    }

    // 2 means for both of them
    if ($human_bot === '0' || $human_bot === '1') {
      $query .= "\nAND $table.`bot` = '$human_bot' ";
    }

    if ($module && !$groupmod) {
      $query .= "\nAND `module_action`.`module` = '$module' ";
    }

    switch ($groupmod) {
      case 2:
        $query .= "GROUP BY grouping ";
        break;
      case 1:
        $query .= "GROUP BY `module_action`.`module` ORDER BY `module_action`.`module` ";
        break;
      case 0:
        $query .= "GROUP BY `module_action`.`module`, `module_action`.`action` ORDER BY `module_action`.`module`, `module_action`.`action` ";
        break;
    }

    return $dl->loadQueryList($query);
  }

  /**
   * Build aggregated stats for a period
   *
   * @param string $start         Start date time
   * @param string $end           End date time
   * @param string $period_format Period format
   * @param string $module_name   Module name
   * @param string $action_name   Action name
   * @param string $human_bot     Human/bot filter
   *
   * @return CAccessLog[]
   */
  static function loadPeriodAggregation($start, $end, $period_format, $module_name, $action_name, $human_bot = null) {
    $dl    = new static;
    $table = $dl->_spec->table;

    // Convert date format from PHP to MySQL
    $period_format = str_replace("%M", "%i", $period_format);

    $query = "SELECT
        $table.`datasourcelog_id`,
        $table.`period`,
        $table.`datasource`,
        SUM($table.`requests`) AS requests,
        SUM($table.`duration`) AS duration,
        SUM($table.`connections`) AS connections,
        SUM($table.`ping_duration`) AS ping_duration,
        SUM($table.`connection_time`) AS connection_time,
        DATE_FORMAT($table.`period`, '$period_format') AS `gperiod`
      FROM $table
      WHERE $table.`period` BETWEEN '$start' AND '$end'";

    if ($module_name) {
      $actions = CModuleAction::getActions($module_name);
      if ($action_name) {
        $action_id = $actions[$action_name];
        $query .= "\nAND `module_action_id` = '$action_id'";
      }
      else {
        $query .= "\nAND `module_action_id` " . CSQLDataSource::prepareIn(array_values($actions));
      }
    }

    // 2 means for both of them
    if ($human_bot === '0' || $human_bot === '1') {
      $query .= "\nAND bot = '$human_bot' ";
    }

    $query .= "\nGROUP BY `gperiod`, $table.`datasource` ORDER BY `period`";

    return $dl->loadQueryList($query);
  }

  /**
   * Compute Flotr graph
   *
   * @param string  $module_name Module name
   * @param string  $action_name Action name
   * @param integer $startx      Start date
   * @param integer $endx        End date
   * @param string  $interval    Interval
   * @param bool    $human_bot   Human/bot filter
   *
   * @return array
   */
  static function graphDataSourceLog($module_name, $action_name, $startx, $endx, $interval, $human_bot) {
    $dl = new static;

    switch ($interval) {
      default:
      case "one-day":
        $step          = "+10 MINUTES";
        $period_format = "%H:%M";
        $ticks_modulo  = 4;
        break;
      case "one-week":
        $step          = "+1 HOUR";
        $period_format = "%a %d %Hh";
        $ticks_modulo  = 8;
        break;
      case "eight-weeks":
        $step          = "+1 DAY";
        $period_format = "%d/%m";
        $ticks_modulo  = 4;
        break;
      case "one-year":
        $step          = "+1 WEEK";
        $period_format = "%Y S%U";
        $ticks_modulo  = 4;
        break;
      case "four-years":
        $step          = "+1 MONTH";
        $period_format = "%m/%Y";
        $ticks_modulo  = 4;
        break;
      case "twenty-years":
        $step          = "+1 YEAR";
        $period_format = "%Y";
        $ticks_modulo  = 1;
        break;
    }

    $datax = array();
    $i     = 0;
    for ($d = $startx; $d <= $endx; $d = CMbDT::dateTime($step, $d)) {
      $datax[] = array($i, CMbDT::format($d, $period_format));
      $i++;
    }

    /** @var CDataSourceLog[] $logs */
    $logs = $dl::loadPeriodAggregation($startx, $endx, $period_format, $module_name, $action_name, $human_bot);

    $duration        = array();
    $requests        = array();
    $ping_duration   = array();
    $connection_time = array();

    $datetime_by_index = array();

    foreach ($datax as $x) {
      $x0 = $x[0];

      // Needed
      foreach ($logs as $log) {
        $_dsn = $log->datasource;

        $duration       [$_dsn][$x0] = array($x0, 0);
        $requests       [$_dsn][$x0] = array($x0, 0);
        $ping_duration  [$_dsn][$x0] = array($x0, 0);
        $connection_time[$_dsn][$x0] = array($x0, 0);
      }

      foreach ($logs as $log) {
        if ($x[1] == CMbDT::format($log->period, $period_format)) {
          $_dsn = $log->datasource;

          $duration     [$_dsn][$x0] = array($x0, $log->duration);
          $requests     [$_dsn][$x0] = array($x0, $log->requests);

          if ($log->connections > 0) {
            $ping_duration  [$_dsn][$x0] = array($x0, $log->ping_duration   / $log->connections);
            $connection_time[$_dsn][$x0] = array($x0, $log->connection_time / $log->connections);
          }

          $datetime_by_index[$x0] = $log->period;
        }
      }
    }

    foreach ($datax as $i => &$x) {
      if ($i % $ticks_modulo) {
        $x[1] = '';
      }
    }

    $title = '';
    if ($module_name) {
      $title .= CAppUI::tr("module-$module_name-court");
    }
    if ($action_name) {
      $title .= " - $action_name";
    }

    $subtitle = CMbDT::format($endx, CAppUI::conf("longdate"));

    $options = array(
      'title'       => $title,
      'subtitle'    => $subtitle,
      'shadowSize'  => 0,
      'xaxis'       => array(
        'labelsAngle' => 45,
        'ticks'       => $datax,
      ),
      'yaxis'       => array(
        'min'             => 0,
        'title'           => 'Temps de réponse / CT',
        'autoscaleMargin' => 1
      ),
      'y2axis'      => array(
        'min'             => 0,
        'title'           => 'Requêtes',
        'autoscaleMargin' => 1
      ),
      'grid'        => array(
        'verticalLines' => false,
      ),
      'HtmlText'    => false,
      'spreadsheet' => array(
        'show'             => true,
        'csvFileSeparator' => ';',
        'decimalSeparator' => ','
      )
    );

    $series = array();
    $extra = array();

    // Right axis (before in order the lines to be on top)
    foreach ($requests as $datasource => $_requests) {
      $series[] = array(
        'type' => "req",
        'label' => "Req. [$datasource]",
        'data'  => $_requests,
        'bars'  => array(
          'show'    => true,
          'stacked' => true
        ),
        'yaxis' => 2
      );

      // Left axis
      $series[] = array(
        'type' => "sql",
        'label' => "SQL (s) [$datasource]",
        'data'  => $duration[$datasource],
        'lines' => array(
          'show' => true
        ),
      );

      // Left axis
      $extra[] = array(
        'type' => "ping",
        'label' => "Ping (ms) [$datasource]",
        'data'  => $ping_duration[$datasource],
        'lines' => array(
          'show' => true
        ),
      );

      // Left axis
      $extra[] = array(
        'type' => "ping",
        'label' => "Connection time (ms) [$datasource]",
        'data'  => $connection_time[$datasource],
        'lines' => array(
          'show' => true
        ),
      );
    }

    return array(
      'series'            => $series,
      'options'           => $options,
      'module'            => $module_name,
      'datetime_by_index' => $datetime_by_index,
      'extra'             => $extra,
    );
  }
}
