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
 * Access Log
 */
class CAccessLog extends CModuleActionLog {
  public $accesslog_id;
  // DB Fields

  // log unique logical key fields (signature)
  public $module_action_id;
  public $period;
  public $bot;
  public $aggregate;

  // Log data fields
  public $hits;
  public $duration;
  public $request;
  public $nb_requests;
  public $nosql_time;
  public $nosql_requests;
  public $io_time;
  public $io_requests;
  public $peak_memory;
  public $size;
  public $errors;
  public $warnings;
  public $notices;
  public $session_wait;
  public $session_read;
  public $transport_tiers_nb;
  public $transport_tiers_time;

  // todo Remove those useless fields
  public $processus;
  public $processor;

  // Derived fields
  public $_module;
  public $_action;
  public $_php_duration;

  // Average fields
  public $_average_hits;
  public $_average_duration;
  public $_average_request;
  public $_average_nb_requests;
  public $_average_peak_memory;
  public $_average_size;
  public $_average_errors;
  public $_average_warnings;
  public $_average_notices;
  public $_average_session_wait;
  public $_average_session_read;
  public $_average_processus;
  public $_average_processor;
  public $_average_php_duration;
  public $_average_nosql_time;
  public $_average_nosql_requests;
  public $_average_transport_tiers_nb;
  public $_average_transport_tiers_time;


  static $left_modes = array(
    "duration_mode",
    "error_mode",
    "memory_mode",
    "data_mode",
    "session_mode",
  );

  static $right_modes = array(
    "hits",
    "size",
  );

  /** @var self */
  static $_current;

  /**
   * @inheritdoc
   */
  function getSpec() {
    $spec           = parent::getSpec();
    $spec->loggable = false;
    $spec->table    = 'access_log';
    $spec->key      = 'accesslog_id';

    return $spec;
  }

  /**
   * @inheritdoc
   */
  function getProps() {
    $props                     = parent::getProps();
    $props["module_action_id"] = "ref class|CModuleAction notNull back|access_logs";
    $props["period"]           = "dateTime notNull";
    $props["hits"]             = "num pos notNull";
    $props["duration"]         = "float notNull";
    $props["session_wait"]     = "float";
    $props["session_read"]     = "float";
    $props["request"]          = "float notNull";
    $props["nb_requests"]      = "num";
    $props["nosql_time"]       = "float";
    $props["nosql_requests"]   = "num";
    $props["io_time"]          = "float";
    $props["io_requests"]      = "num";
    $props["processus"]        = "float";
    $props["processor"]        = "float";
    $props["peak_memory"]      = "num min|0";
    $props["size"]             = "num min|0";
    $props["errors"]           = "num min|0";
    $props["warnings"]         = "num min|0";
    $props["notices"]          = "num min|0";
    $props["aggregate"]        = "num min|0 default|10";
    $props["bot"]              = "enum list|0|1 default|0";

    $props["transport_tiers_nb"]   = "num min|0";
    $props["transport_tiers_time"] = "float";

    $props["_module"]       = "str";
    $props["_action"]       = "str";
    $props["_php_duration"] = "float notNull";

    $props["_average_duration"]             = "num min|0";
    $props["_average_session_wait"]         = "num min|0";
    $props["_average_session_read"]         = "num min|0";
    $props["_average_request"]              = "num min|0";
    $props["_average_peak_memory"]          = "num min|0";
    $props["_average_nb_requests"]          = "num min|0";
    $props["_average_nosql_time"]           = "num min|0";
    $props["_average_nosql_requests"]       = "num min|0";
    $props["_average_transport_tiers_nb"]   = "num min|0";
    $props["_average_transport_tiers_time"] = "num min|0";

    return $props;
  }

  /**
   * @inheritdoc
   */
  static function getSignatureFields() {
    return array("module_action_id", "period", "aggregate", "bot");
  }

  /**
   * @inheritdoc
   */
  function updateFormFields() {
    parent::updateFormFields();

    $this->_php_duration = $this->duration - $this->request - $this->nosql_time - $this->transport_tiers_time;
    if ($this->hits) {
      $this->_average_duration             = $this->duration / $this->hits;
      $this->_average_session_wait         = $this->session_wait / $this->hits;
      $this->_average_session_read         = $this->session_read / $this->hits;
      $this->_average_processus            = $this->processus / $this->hits;
      $this->_average_processor            = $this->processor / $this->hits;
      $this->_average_request              = $this->request / $this->hits;
      $this->_average_nb_requests          = $this->nb_requests / $this->hits;
      $this->_average_peak_memory          = $this->peak_memory / $this->hits;
      $this->_average_errors               = $this->errors / $this->hits;
      $this->_average_warnings             = $this->warnings / $this->hits;
      $this->_average_notices              = $this->notices / $this->hits;
      $this->_average_php_duration         = $this->_php_duration / $this->hits;
      $this->_average_nosql_time           = $this->nosql_time / $this->hits;
      $this->_average_nosql_requests       = $this->nosql_requests / $this->hits;
      $this->_average_transport_tiers_nb   = $this->transport_tiers_nb / $this->hits;
      $this->_average_transport_tiers_time = $this->transport_tiers_time / $this->hits;
    }

    // If time period == 1 hour
    $this->_average_hits = $this->hits / 3600; // hits per sec
    $this->_average_size = $this->size / 3600; // size per sec
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
   * @todo A partir de cette méthode, il faut compléter les champs de session
   *
   */
  static function loadAggregation($start, $end, $groupmod = 0, $module = null, $human_bot = null) {
    $al    = new static;
    $table = $al->_spec->table;

    switch ($groupmod) {
      case 2:
        $query = "SELECT
            $table.`accesslog_id`,
            $table.`module_action_id`,
            SUM($table.`hits`)                   AS hits,
            SUM($table.`size`)                   AS size,
            SUM($table.`duration`)               AS duration,
            SUM($table.`session_read`)           AS session_read,
            SUM($table.`session_wait`)           AS session_wait,
            SUM($table.`processus`)              AS processus,
            SUM($table.`processor`)              AS processor,
            SUM($table.`request`)                AS request,
            SUM($table.`nb_requests`)            AS nb_requests,
            SUM($table.`nosql_time`)             AS nosql_time,
            SUM($table.`nosql_requests`)         AS nosql_requests,
            SUM($table.`peak_memory`)            AS peak_memory,
            SUM($table.`errors`)                 AS errors,
            SUM($table.`warnings`)               AS warnings,
            SUM($table.`notices`)                AS notices,
            SUM($table.`transport_tiers_nb`)     AS transport_tiers_nb,
            SUM($table.`transport_tiers_time`)   AS transport_tiers_time,
            0 AS grouping
          FROM $table
          WHERE $table.`period` BETWEEN '$start' AND '$end'";
        break;

      case 0:
      case 1:
        $query = "SELECT
          $table.`accesslog_id`,
          $table.`module_action_id`,
          `module_action`.`module`           AS `_module`,
          `module_action`.`action`           AS `_action`,
          SUM($table.`hits`)                 AS `hits`,
          SUM($table.`size`)                 AS `size`,
          SUM($table.`duration`)             AS `duration`,
          SUM($table.`session_read`)         AS `session_read`,
          SUM($table.`session_wait`)         AS `session_wait`,
          SUM($table.`processus`)            AS `processus`,
          SUM($table.`processor`)            AS `processor`,
          SUM($table.`request`)              AS `request`,
          SUM($table.`nb_requests`)          AS `nb_requests`,
          SUM($table.`nosql_time`)           AS `nosql_time`,
          SUM($table.`nosql_requests`)       AS `nosql_requests`,
          SUM($table.`peak_memory`)          AS `peak_memory`,
          SUM($table.`errors`)               AS `errors`,
          SUM($table.`warnings`)             AS `warnings`,
          SUM($table.`notices`)              AS `notices`,
          SUM($table.`transport_tiers_nb`)   AS transport_tiers_nb,
          SUM($table.`transport_tiers_time`) AS transport_tiers_time,
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

    return $al->loadQueryList($query);
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
  static function loadPeriodAggregation($start, $end, $period_format, $module_name, $action_name, $human_bot) {
    $al    = new static;
    $table = $al->_spec->table;

    // Convert date format from PHP to MySQL
    $period_format = str_replace("%M", "%i", $period_format);

    $query = "SELECT
        `accesslog_id`,
        `period`,
        SUM(`hits`)           AS `hits`,
        SUM(`size`)           AS `size`,
        SUM(`duration`)       AS `duration`,
        SUM(`session_read`)   AS `session_read`,
        SUM(`session_wait`)   AS `session_wait`,
        SUM(`processus`)      AS `processus`,
        SUM(`processor`)      AS `processor`,
        SUM(`request`)        AS `request`,
        SUM(`nb_requests`)    AS `nb_requests`,
        SUM(`nosql_time`)     AS `nosql_time`,
        SUM(`nosql_requests`) AS `nosql_requests`,
        SUM(`peak_memory`)    AS `peak_memory`,
        SUM(`errors`)         AS `errors`,
        SUM(`warnings`)       AS `warnings`,
        SUM(`notices`)        AS `notices`,       
        SUM(`transport_tiers_nb`)   AS transport_tiers_nb,
        SUM(`transport_tiers_time`) AS transport_tiers_time,
        DATE_FORMAT(`period`, '$period_format') AS `gperiod`
      FROM $table
      WHERE `period` BETWEEN '$start' AND '$end'";

    // 2 means for both of them
    if ($human_bot === '0' || $human_bot === '1') {
      $query .= "\nAND bot = '$human_bot' ";
    }

    if ($module_name) {
      $actions = CModuleAction::getActions($module_name);
      if ($action_name) {
        $action_id = $actions[$action_name];
        $query     .= "\nAND `module_action_id` = '$action_id'";
      }
      else {
        $query .= "\nAND `module_action_id` " . CSQLDataSource::prepareIn(array_values($actions));
      }
    }

    $query .= "\nGROUP BY `gperiod`";

    return $al->loadQueryList($query);
  }

  /**
   * Compute Flotr graph
   *
   * @param string  $module_name Module name
   * @param string  $action_name Action name
   * @param integer $startx      Start date
   * @param integer $endx        End date
   * @param string  $interval    Interval
   * @param array   $left        Left axis
   * @param array   $right       Right axis
   * @param bool    $human_bot   Human/bot filter
   *
   * @return array
   */
  static function graphAccessLog($module_name, $action_name, $startx, $endx, $interval, $left, $right, $human_bot) {
    $al = new static;

    switch ($interval) {
      default:
      case "one-day":
        $step          = "+10 MINUTES";
        $period_format = "%H:%M";
        $hours         = 1 / 6;
        $ticks_modulo  = 4;
        break;
      case "one-week":
        $step          = "+1 HOUR";
        $period_format = "%a %d %Hh";
        $hours         = 1;
        $ticks_modulo  = 8;
        break;

      case "eight-weeks":
        $step          = "+1 DAY";
        $period_format = "%d/%m";
        $hours         = 24;
        $ticks_modulo  = 3;
        break;

      case "one-year":
        $step          = "+1 WEEK";
        $period_format = "%Y S%U";
        $hours         = 24 * 7;
        $ticks_modulo  = 3;
        break;

      case "four-years":
        $step          = "+1 MONTH";
        $period_format = "%m/%Y";
        $hours         = 24 * 30;
        $ticks_modulo  = 2;
        break;

      case "twenty-years":
        $step          = "+1 YEAR";
        $period_format = "%Y";
        $hours         = 24 * 30 * 12;
        $ticks_modulo  = 1;
        break;
    }

    $datax = array();
    $i     = 0;
    for ($d = $startx; $d <= $endx; $d = CMbDT::dateTime($step, $d)) {
      $datax[] = array($i, CMbDT::format($d, $period_format));
      $i++;
    }

    $logs = $al::loadPeriodAggregation($startx, $endx, $period_format, $module_name, $action_name, $human_bot);

    $duration       = array();
    $session_wait   = array();
    $session_read   = array();
    $processus      = array();
    $processor      = array();
    $request        = array();
    $nb_requests    = array();
    $nosql_time     = array();
    $nosql_requests = array();
    $peak_memory    = array();
    $errors         = array();
    $warnings       = array();
    $notices        = array();
    $_php_duration  = array();

    $transport_tiers_nb   = array();
    $transport_tiers_time = array();

    $hits = array();
    $size = array();

    $datetime_by_index = array();

    $errors_total = 0;
    foreach ($datax as $x) {
      // Needed
      $duration       [$x[0]] = array($x[0], 0);
      $session_wait   [$x[0]] = array($x[0], 0);
      $session_read   [$x[0]] = array($x[0], 0);
      $processus      [$x[0]] = array($x[0], 0);
      $processor      [$x[0]] = array($x[0], 0);
      $request        [$x[0]] = array($x[0], 0);
      $nb_requests    [$x[0]] = array($x[0], 0);
      $nosql_time     [$x[0]] = array($x[0], 0);
      $nosql_requests [$x[0]] = array($x[0], 0);
      $peak_memory    [$x[0]] = array($x[0], 0);
      $errors         [$x[0]] = array($x[0], 0);
      $warnings       [$x[0]] = array($x[0], 0);
      $notices        [$x[0]] = array($x[0], 0);
      $_php_duration  [$x[0]] = array($x[0], 0);


      $transport_tiers_nb     [$x[0]] = array($x[0], 0);
      $transport_tiers_time   [$x[0]] = array($x[0], 0);

      $hits[$x[0]] = array($x[0], 0);
      $size[$x[0]] = array($x[0], 0);


      foreach ($logs as $log) {
        if ($x[1] == CMbDT::format($log->period, $period_format)) {
          $duration       [$x[0]] = array($x[0], $log->{($left[1] == 'mean' ? '_average_' : '') . 'duration'});
          $session_wait   [$x[0]] = array($x[0], $log->{($left[1] == 'mean' ? '_average_' : '') . 'session_wait'});
          $session_read   [$x[0]] = array($x[0], $log->{($left[1] == 'mean' ? '_average_' : '') . 'session_read'});
          $processus      [$x[0]] = array($x[0], $log->{($left[1] == 'mean' ? '_average_' : '') . 'processus'});
          $processor      [$x[0]] = array($x[0], $log->{($left[1] == 'mean' ? '_average_' : '') . 'processor'});
          $request        [$x[0]] = array($x[0], $log->{($left[1] == 'mean' ? '_average_' : '') . 'request'});
          $nb_requests    [$x[0]] = array($x[0], $log->{($left[1] == 'mean' ? '_average_' : '') . 'nb_requests'});
          $nosql_time     [$x[0]] = array($x[0], $log->{($left[1] == 'mean' ? '_average_' : '') . 'nosql_time'});
          $nosql_requests [$x[0]] = array($x[0], $log->{($left[1] == 'mean' ? '_average_' : '') . 'nosql_requests'});
          $peak_memory    [$x[0]] = array($x[0], $log->{($left[1] == 'mean' ? '_average_' : '') . 'peak_memory'});
          $errors         [$x[0]] = array($x[0], $log->{($left[1] == 'mean' ? '_average_' : '') . 'errors'});
          $warnings       [$x[0]] = array($x[0], $log->{($left[1] == 'mean' ? '_average_' : '') . 'warnings'});
          $notices        [$x[0]] = array($x[0], $log->{($left[1] == 'mean' ? '_average_' : '') . 'notices'});
          $_php_duration  [$x[0]] = array($x[0], $log->{($left[1] == 'mean' ? '_average' : '') . '_php_duration'});

          $transport_tiers_nb    [$x[0]] = array($x[0], $log->{($left[1] == 'mean' ? '_average_' : '') . 'transport_tiers_nb'});
          $transport_tiers_time  [$x[0]] = array($x[0], $log->{($left[1] == 'mean' ? '_average_' : '') . 'transport_tiers_time'});


          $errors_total += $log->_average_errors + $log->_average_warnings + $log->_average_notices;

          $hits[$x[0]] = array($x[0], $log->{($right[1] == 'mean' ? '_average_' : '') . 'hits'} / ($right[1] == 'mean' ? $hours : 1));
          $size[$x[0]] = array($x[0], $log->{($right[1] == 'mean' ? '_average_' : '') . 'size'} / ($right[1] == 'mean' ? $hours : 1));

          $datetime_by_index[$x[0]] = $log->period;
        }
      }
    }

    // Removing some xaxis ticks
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
      'xaxis'       => array(
        'labelsAngle' => 45,
        'ticks'       => $datax,
      ),
      'yaxis'       => array(
        'min'             => 0,
        'title'           => CAppUI::tr("CAccessLog-left_modes-" . $left[0]) . " " . ($left[1] == 'mean' ? '(par hit)' : ''),
        'autoscaleMargin' => 1
      ),
      'y2axis'      => array(
        'min'             => 0,
        'title'           => CAppUI::tr("CAccessLog-right_modes-" . $right[0]) . " " . ($left[1] == 'mean' ? '(par seconde)' : ''),
        'autoscaleMargin' => 1
      ),
      'grid'        => array(
        'verticalLines' => false
      ),
      /*'mouse' => array(
        'track' => true,
        'relative' => true
      ),*/
      'HtmlText'    => false,
      'spreadsheet' => array(
        'show'             => true,
        'csvFileSeparator' => ';',
        'decimalSeparator' => ','
      )
    );

    $series = array();

    // Right modes (before in order the lines to be on top)
    switch ($right[0]) {
      case 'hits':
        $series[] = array(
          'label' => CAppui::tr("CAccessLog-hits"),
          'data'  => $hits,
          'bars'  => array(
            'show' => true
          ),
          'yaxis' => 2
        );
        break;

      default:
        $series[] = array(
          'label' => CAppui::tr("CAccessLog-size"),
          'data'  => $size,
          'bars'  => array(
            'show' => true
          ),
          'yaxis' => 2
        );
    }

    // Left modes
    switch ($left[0]) {
      default:
      case "duration_mode":
        $series[] = array(
          'label' => CAppui::tr("CAccessLog-duration"),
          'data'  => $duration,
          'lines' => array(
            'show' => true
          ),
        );
        $series[] = array(
          'label' => CAppui::tr("CAccessLog-_php_duration"),
          'data'  => $_php_duration,
          'lines' => array(
            'show' => true
          ),
        );
        $series[] = array(
          'label' => CAppui::tr("CAccessLog-request"),
          'data'  => $request,
          'lines' => array(
            'show' => true
          ),
        );
        $series[] = array(
          'label' => CAppui::tr("CAccessLog-nosql_time"),
          'data'  => $nosql_time,
          'lines' => array(
            'show' => true
          ),
        );
        $series[] = array(
          'label' => CAppui::tr("CAccessLog-transport_tiers_time"),
          'data'  => $transport_tiers_time,
          'lines' => array(
            'show' => true
          ),
        );
        break;
      case "error_mode":
        if ($errors_total == 0) {
          $options['yaxis']['max'] = 1;
        }

        $series[] = array(
          'label' => CAppui::tr("CAccessLog-errors"),
          'data'  => $errors,
          'color' => 'red',
          'lines' => array(
            'show' => true
          ),
        );
        $series[] = array(
          'label' => CAppui::tr("CAccessLog-warnings"),
          'data'  => $warnings,
          'color' => 'orange',
          'lines' => array(
            'show' => true
          ),
        );
        $series[] = array(
          'label' => CAppui::tr("CAccessLog-notices"),
          'data'  => $notices,
          'color' => 'yellow',
          'lines' => array(
            'show' => true
          ),
        );
        break;
      case "memory_mode":
        $series[] = array(
          'label' => CAppui::tr("CAccessLog-peak_memory"),
          'data'  => $peak_memory,
          'lines' => array(
            'show' => true
          ),
        );
        break;
      case "data_mode":
        $series[] = array(
          'label' => CAppui::tr("CAccessLog-nb_requests"),
          'data'  => $nb_requests,
          'lines' => array(
            'show' => true
          ),
        );
        $series[] = array(
          'label' => CAppui::tr("CAccessLog-nosql_requests"),
          'data'  => $nosql_requests,
          'lines' => array(
            'show' => true
          ),
        );
        $series[] = array(
          'label' => CAppui::tr("CAccessLog-transport_tiers_nb"),
          'data'  => $transport_tiers_nb,
          'lines' => array(
            'show' => true
          ),
        );
        break;
      case "session_mode":
        $series[] = array(
          'label' => CAppui::tr("CAccessLog-session_wait"),
          'data'  => $session_wait,
          'lines' => array(
            'show' => true
          ),
        );
        $series[] = array(
          'label' => CAppui::tr("CAccessLog-session_read"),
          'data'  => $session_read,
          'lines' => array(
            'show' => true
          ),
        );
        break;
    }

    return array('series' => $series, 'options' => $options, 'module' => $module_name, 'datetime_by_index' => $datetime_by_index);
  }

  /**
   * Ugly method combining two graphs, considering rates to preserve
   *
   * @param $groupmod
   * @param $graph_1
   * @param $graph_2
   *
   * @return mixed
   */
  static function combineGraphs($groupmod, $graph_1, $graph_2, $log = null) {
    switch ($groupmod) {
      case 0:
        $hits            = array();
        $graphic         = array();
        $archive_graphic = array();

        // Gets the data (hits-unrelated) and hits from 1st graph
        foreach ($graph_1[$log->_module . "-" . $log->_action]["series"] as $_k1 => $_serie) {
          $graphic[$_serie['label']] = array();

          foreach ($_serie['data'] as $_k2 => $_data) {
            if (!isset($graphic[$_serie['label']][$_k2])) {
              $graphic[$_serie['label']][$_k2] = array('hits' => 0, 'data' => 0);
            }

            if ($_serie['label'] == 'Hits') {
              $hits[$_k2] = $_data[1];
            }
            else {
              $graphic[$_serie['label']][$_k2]['hits'] = $hits[$_k2];
              $graphic[$_serie['label']][$_k2]['data'] = $_data[1];
            }
          }
        }

        // Gets the data (hits-unrelated) and hits from 2nd graph
        foreach ($graph_2["series"] as $_k1 => $_serie) {
          $archive_graphic[$_serie['label']] = array();

          foreach ($_serie['data'] as $_k2 => $_data) {
            if (!isset($archive_graphic[$_serie['label']][$_k2])) {
              $archive_graphic[$_serie['label']][$_k2] = array('hits' => 0, 'data' => 0);
            }

            if ($_serie['label'] == 'Hits') {
              $hits[$_k2] = $_data[1];
            }
            else {
              $archive_graphic[$_serie['label']][$_k2]['hits'] = $hits[$_k2];
              $archive_graphic[$_serie['label']][$_k2]['data'] = $_data[1];
            }
          }
        }

        unset($graphic['Hits']);
        unset($archive_graphic['Hits']);

        // Computes combination of the two graphs
        $total = array();
        foreach ($graphic as $_label => $_point) {
          $total[$_label] = array();

          foreach ($_point as $_k => $_data) {
            $total[$_label][$_k] = array(
              'hits' => $_data['hits'],
              'data' => $_data['data'] * $_data['hits']
            );
          }
        }

        foreach ($archive_graphic as $_label => $_point) {
          if (!isset($total[$_label])) {
            $total[$_label] = array();
          }

          foreach ($_point as $_k => $_data) {
            if ($total[$_label][$_k]['hits'] + $_data['hits'] > 0) {
              $total[$_label][$_k]['data'] = ($total[$_label][$_k]['data'] + $_data['data'] * $_data['hits']) / ($total[$_label][$_k]['hits'] + $_data['hits']);
            }
            else {
              $total[$_label][$_k]['data'] = 0;
            }

            $total[$_label][$_k]['hits'] += $_data['hits'];
          }
        }

        // Re-assembles graphic with hits and data
        foreach ($total as $_label => $_values) {
          foreach ($graph_1[$log->_module . "-" . $log->_action]['series'] as $_k1 => $_serie) {
            if ($_serie['label'] == 'Hits') {
              foreach ($_serie['data'] as $_k2 => $_data) {
                $graph_1[$log->_module . "-" . $log->_action]['series'][$_k1]['data'][$_k2][1] = $_values[$_k2]['hits'];
              }
            }
          }

          foreach ($graph_1[$log->_module . "-" . $log->_action]['series'] as $_k1 => $_serie) {
            if ($_serie['label'] == $_label) {
              foreach ($_serie['data'] as $_k2 => $_data) {
                $graph_1[$log->_module . "-" . $log->_action]['series'][$_k1]['data'][$_k2][1] = $_values[$_k2]['data'];
              }
            }
          }
        }
        break;

      case 1:
        $hits            = array();
        $graphic         = array();
        $archive_graphic = array();

        // Gets the data (hits-unrelated) and hits from 1st graph
        foreach ($graph_1[$log->_module]["series"] as $_k1 => $_serie) {
          $graphic[$_serie['label']] = array();

          foreach ($_serie['data'] as $_k2 => $_data) {
            if (!isset($graphic[$_serie['label']][$_k2])) {
              $graphic[$_serie['label']][$_k2] = array('hits' => 0, 'data' => 0);
            }

            if ($_serie['label'] == 'Hits') {
              $hits[$_k2] = $_data[1];
            }
            else {
              $graphic[$_serie['label']][$_k2]['hits'] = $hits[$_k2];
              $graphic[$_serie['label']][$_k2]['data'] = $_data[1];
            }
          }
        }

        // Gets the data (hits-unrelated) and hits from 2nd graph
        foreach ($graph_2["series"] as $_k1 => $_serie) {
          $archive_graphic[$_serie['label']] = array();

          foreach ($_serie['data'] as $_k2 => $_data) {
            if (!isset($archive_graphic[$_serie['label']][$_k2])) {
              $archive_graphic[$_serie['label']][$_k2] = array('hits' => 0, 'data' => 0);
            }

            if ($_serie['label'] == 'Hits') {
              $hits[$_k2] = $_data[1];
            }
            else {
              $archive_graphic[$_serie['label']][$_k2]['hits'] = $hits[$_k2];
              $archive_graphic[$_serie['label']][$_k2]['data'] = $_data[1];
            }
          }
        }

        unset($graphic['Hits']);
        unset($archive_graphic['Hits']);

        // Computes combination of the two graphs
        $total = array();
        foreach ($graphic as $_label => $_point) {
          $total[$_label] = array();

          foreach ($_point as $_k => $_data) {
            $total[$_label][$_k] = array(
              'hits' => $_data['hits'],
              'data' => $_data['data'] * $_data['hits']
            );
          }
        }

        foreach ($archive_graphic as $_label => $_point) {
          if (!isset($total[$_label])) {
            $total[$_label] = array();
          }

          foreach ($_point as $_k => $_data) {
            if ($total[$_label][$_k]['hits'] + $_data['hits'] > 0) {
              $total[$_label][$_k]['data'] = ($total[$_label][$_k]['data'] + $_data['data'] * $_data['hits']) / ($total[$_label][$_k]['hits'] + $_data['hits']);
            }
            else {
              $total[$_label][$_k]['data'] = 0;
            }

            $total[$_label][$_k]['hits'] += $_data['hits'];
          }
        }

        // Re-assembles graphic with hits and data
        foreach ($total as $_label => $_values) {
          foreach ($graph_1[$log->_module]['series'] as $_k1 => $_serie) {
            if ($_serie['label'] == 'Hits') {
              foreach ($_serie['data'] as $_k2 => $_data) {
                $graph_1[$log->_module]['series'][$_k1]['data'][$_k2][1] = $_values[$_k2]['hits'];
              }
            }
          }

          foreach ($graph_1[$log->_module]['series'] as $_k1 => $_serie) {
            if ($_serie['label'] == $_label) {
              foreach ($_serie['data'] as $_k2 => $_data) {
                $graph_1[$log->_module]['series'][$_k1]['data'][$_k2][1] = $_values[$_k2]['data'];
              }
            }
          }
        }
        break;

      case 2:
        $hits            = array();
        $graphic         = array();
        $archive_graphic = array();

        // Gets the data (hits-unrelated) and hits from 1st graph
        foreach ($graph_1["series"] as $_k1 => $_serie) {
          $graphic[$_serie['label']] = array();

          foreach ($_serie['data'] as $_k2 => $_data) {
            if (!isset($graphic[$_serie['label']][$_k2])) {
              $graphic[$_serie['label']][$_k2] = array('hits' => 0, 'data' => 0);
            }

            if ($_serie['label'] == 'Hits') {
              $hits[$_k2] = $_data[1];
            }
            else {
              $graphic[$_serie['label']][$_k2]['hits'] = $hits[$_k2];
              $graphic[$_serie['label']][$_k2]['data'] = $_data[1];
            }
          }
        }

        // Gets the data (hits-unrelated) and hits from 2nd graph
        foreach ($graph_2["series"] as $_k1 => $_serie) {
          $archive_graphic[$_serie['label']] = array();

          foreach ($_serie['data'] as $_k2 => $_data) {
            if (!isset($archive_graphic[$_serie['label']][$_k2])) {
              $archive_graphic[$_serie['label']][$_k2] = array('hits' => 0, 'data' => 0);
            }

            if ($_serie['label'] == 'Hits') {
              $hits[$_k2] = $_data[1];
            }
            else {
              $archive_graphic[$_serie['label']][$_k2]['hits'] = $hits[$_k2];
              $archive_graphic[$_serie['label']][$_k2]['data'] = $_data[1];
            }
          }
        }

        unset($graphic['Hits']);
        unset($archive_graphic['Hits']);

        // Computes combination of the two graphs
        $total = array();
        foreach ($graphic as $_label => $_point) {
          $total[$_label] = array();

          foreach ($_point as $_k => $_data) {
            $total[$_label][$_k] = array(
              'hits' => $_data['hits'],
              'data' => $_data['data'] * $_data['hits']
            );
          }
        }

        foreach ($archive_graphic as $_label => $_point) {
          if (!isset($total[$_label])) {
            $total[$_label] = array();
          }

          foreach ($_point as $_k => $_data) {
            if ($total[$_label][$_k]['hits'] + $_data['hits'] > 0) {
              $total[$_label][$_k]['data'] = ($total[$_label][$_k]['data'] + $_data['data'] * $_data['hits']) / ($total[$_label][$_k]['hits'] + $_data['hits']);
            }
            else {
              $total[$_label][$_k]['data'] = 0;
            }

            $total[$_label][$_k]['hits'] += $_data['hits'];
          }
        }

        // Re-assembles graphic with hits and data
        foreach ($total as $_label => $_values) {
          foreach ($graph_1['series'] as $_k1 => $_serie) {
            if ($_serie['label'] == 'Hits') {
              foreach ($_serie['data'] as $_k2 => $_data) {
                $graph_1['series'][$_k1]['data'][$_k2][1] = $_values[$_k2]['hits'];
              }
            }
          }

          foreach ($graph_1['series'] as $_k1 => $_serie) {
            if ($_serie['label'] == $_label) {
              foreach ($_serie['data'] as $_k2 => $_data) {
                $graph_1['series'][$_k1]['data'][$_k2][1] = $_values[$_k2]['data'];
              }
            }
          }
        }
        break;
    }

    return $graph_1;
  }

  /**
   * Append SQL data series from DataSourceLog graph to AccessLog graph data series
   *
   * @param array $graph_access_log     Access log graph data
   * @param array $graph_datasource_log Datasource log graph data
   * @param array $left                 Left axis
   *
   * @return void
   */
  static function appendDataSourceSeries(&$graph_access_log, &$graph_datasource_log, $left) {
    foreach ($graph_datasource_log['series'] as &$_serie) {
      $keep_serie = false;

      if ($_serie['type'] === 'sql') {
        foreach ($_serie['data'] as $_k => &$_data) {
          if ($left[1] == "mean") {
            $denominator = $graph_access_log['series'][0]['data'][$_k][1];
            if ($denominator > 0) {
              $_data[1] = round($_data[1] / $denominator, 3);
            }
            if ($_data[1] != 0) {
              $keep_serie = true;
            }
            else {
              $_data[1] = null;
            }
          }
        }
        if ($keep_serie) {
          $graph_access_log['series'][] = $_serie;
        }
      }
    }
  }
}
