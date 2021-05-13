<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CMbArray;
use Ox\Core\CMbDT;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\System\CDataSourceLog;
use Ox\Mediboard\System\CDataSourceLogArchive;

CCanDo::checkRead();

$date      = CView::get("date", "date default|now", true);
$groupmod  = CView::get("groupmod", "str default|2", true);
$interval  = CView::get("interval", "enum list|one-day|one-week|eight-weeks|one-year|four-years|twenty-years default|one-day", true);
$human_bot = CView::get("human_bot", "enum list|0|1|2 default|0", true);

// Hour range for daily stats
$hour_min = CView::get("hour_min", "num default|6", true);
$hour_max = CView::get("hour_max", "num default|22", true);
$hours    = range(0, 24);

CView::checkin();
CView::enforceSlave();

$module = null;
if (!is_numeric($groupmod)) {
  $module   = $groupmod;
  $groupmod = 0;
}

$to = CMbDT::date("+1 DAY", $date);
switch ($interval) {
  default:
  case "one-day":
    $today = CMbDT::date("-1 DAY", $to);
    // Hours limitation
    $from = CMbDT::dateTime("+$hour_min HOUR", $today);
    $to   = CMbDT::dateTime("+$hour_max HOUR -1 MINUTE", $today);
    break;

  case "one-week":
    $from = CMbDT::date("-1 WEEK", $to);
    break;

  case "eight-weeks":
    $from = CMbDT::date("-8 WEEKS", $to);
    break;

  case "one-year":
    $from = CMbDT::date("-1 YEAR", $to);
    break;

  case "four-years":
    $from = CMbDT::date("-4 YEARS", $to);
    break;

  case "twenty-years":
    $from = CMbDT::date("-20 YEARS", $to);
    break;
}

$graphs = array();

switch ($groupmod) {
  case 0:
  case 1:
    $access_logs  = CDataSourceLog::loadAggregation($from, $to, $groupmod, $module, $human_bot);
    $archive_logs = CDataSourceLogArchive::loadAggregation($from, $to, $groupmod, $module, $human_bot);
    $logs         = array_merge($access_logs, $archive_logs);
    break;

  default:
  case 2:
    $logs = array(new CDataSourceLog());
    break;
}

$series_by_module = array();
$graphs_by_module = array();
foreach ($logs as $log) {
  switch ($groupmod) {
    case 0:
      $_graph = call_user_func(array(get_class($log), "graphDataSourceLog"), $log->_module, $log->_action, $from, $to, $interval, $human_bot);

      if (!isset($_graph["series"])) {
        continue 2;
      }

      if (!isset($graphs_by_module[$log->_module . "-" . $log->_action])) {
        // 1st iteration => graph initialisation
        $graphs_by_module[$log->_module . "-" . $log->_action] = $_graph;

        // In order to know which series are already initialised
        $series_by_module[$log->_module . "-" . $log->_action] = CMbArray::pluck($_graph["series"], "label");
      }
      else {
        // Merging of module series and datetime_by_index
        $_series = CMbArray::pluck($_graph["series"], "label");

        foreach ($_series as $_k => $_one_serie) {
          // If series doesn't exist, simply push it
          if (!in_array($_one_serie, $series_by_module[$log->_module . "-" . $log->_action])) {
            $graphs_by_module[$log->_module . "-" . $log->_action]["series"][] = $_graph["series"][$_k];
          }
          else {
            foreach ($graphs_by_module[$log->_module . "-" . $log->_action]["series"] as $_k1 => $_serie) {

              if ($_serie["label"] == $_one_serie) {
                foreach ($_serie["data"] as $_k2 => $_data) {
                  // We merge the associated data witch has different indexes according to the graph ($_k1 and $_k)
                  $graphs_by_module[$log->_module . "-" . $log->_action]["series"][$_k1]["data"][$_k2][1] += $_graph["series"][$_k]["data"][$_k2][1];
                }
              }
            }
          }
          $graphs_by_module[$log->_module . "-" . $log->_action]["datetime_by_index"] += $_graph["datetime_by_index"];
        }
      }
      break;

    case 1:
      $_graph = call_user_func(array(get_class($log), "graphDataSourceLog"), $log->_module, null, $from, $to, $interval, $human_bot);

      if (!isset($_graph["series"])) {
        continue 2;
      }

      if (!isset($graphs_by_module[$log->_module])) {
        // 1st iteration => graph initialisation
        $graphs_by_module[$log->_module] = $_graph;

        // In order to know which series are already initialised
        $series_by_module[$log->_module] = CMbArray::pluck($_graph["series"], "label");
      }
      else {
        // Merging of module series and datetime_by_index
        $_series = CMbArray::pluck($_graph["series"], "label");

        foreach ($_series as $_k => $_one_serie) {
          // If series doesn't exist, simply push it
          if (!in_array($_one_serie, $series_by_module[$log->_module])) {
            $graphs_by_module[$log->_module]["series"][] = $_graph["series"][$_k];
          }
          else {
            foreach ($graphs_by_module[$log->_module]["series"] as $_k1 => $_serie) {

              if ($_serie["label"] == $_one_serie) {
                foreach ($_serie["data"] as $_k2 => $_data) {
                  // We merge the associated data witch has different indexes according to the graph ($_k1 and $_k)
                  $graphs_by_module[$log->_module]["series"][$_k1]["data"][$_k2][1] += $_graph["series"][$_k]["data"][$_k2][1];
                }
              }
            }
          }
          $graphs_by_module[$log->_module]["datetime_by_index"] += $_graph["datetime_by_index"];
        }
      }
      break;

    default:
    case 2:
      $_graph         = CDataSourceLog::graphDataSourceLog(null, null, $from, $to, $interval, $human_bot);
      $_archive_graph = CDataSourceLogArchive::graphDataSourceLog(null, null, $from, $to, $interval, $human_bot);

      if (!isset($_graph["series"]) && !isset($_archive_graph["series"])) {
        continue 2;
      }

      $graphs_by_module["all"] = $_graph;

      $_series         = CMbArray::pluck($_graph["series"], "label");
      $_archive_series = CMbArray::pluck($_archive_graph["series"], "label");

      foreach ($_archive_series as $_k => $_one_serie) {
        // If series doesn't exist, simply push it
        if (!in_array($_one_serie, $_series)) {
          $graphs_by_module["all"]["series"][] = $_archive_graph["series"][$_k];
        }
        else {
          foreach ($graphs_by_module["all"]["series"] as $_k1 => $_serie) {

            if ($_serie["label"] == $_one_serie) {
              foreach ($_serie["data"] as $_k2 => $_data) {
                // We merge the associated data witch has different indexes according to the graph ($_k1 and $_k)
                $graphs_by_module["all"]["series"][$_k1]["data"][$_k2][1] += $_archive_graph["series"][$_k]["data"][$_k2][1];
              }
            }
          }
        }
        $graphs_by_module["all"]["datetime_by_index"] += $_archive_graph["datetime_by_index"];
      }

      $graphs_by_module["ping"] = array(
        'series'            => array(),
        'options'           => $_graph['options'],
        'module'            => $_graph['module'],
        'datetime_by_index' => $_graph['datetime_by_index'],
      );

      $graphs_by_module['ping']['options']['yaxis']['title'] = 'Ping';
      foreach ($_graph['extra'] as $_serie) {
        $graphs_by_module["ping"]['series'][] = $_serie;
      }
      foreach ($_archive_graph['extra'] as $_serie) {
        $graphs_by_module["ping"]['series'][] = $_serie;
      }
  }
}

$graphs = array();
foreach ($graphs_by_module as $_graph) {
  $graphs[] = $_graph;
}

// Ajustements cosmétiques
foreach ($graphs as &$_graph) {
  foreach ($_graph["series"] as &$_series) {
    if (isset($_series["lines"])) {
      $_series["points"] = array(
        "show"      => true,
        "radius"    => 2,
        "lineWidth" => 1,
      );
    }

    foreach ($_series["data"] as &$_data) {
      if ($_data[1] === 0) {
        $_data[1] = null;
      }
    }
  }
}

$smarty = new CSmartyDP();
$smarty->assign("graphs", $graphs);
$smarty->assign("groupmod", $groupmod);
$smarty->display("vw_graph_datasource_logs.tpl");
