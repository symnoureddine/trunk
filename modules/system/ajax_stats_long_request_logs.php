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
use Ox\Core\CRequest;
use Ox\Core\CSmartyDP;
use Ox\Core\CSQLDataSource;
use Ox\Core\CValue;
use Ox\Core\CView;
use Ox\Mediboard\System\CLongRequestLog;
use Ox\Mediboard\System\CLongRequestLogGraph;
use Ox\Mediboard\System\CModuleAction;

CCanDo::checkAdmin();
CView::enableSlave();

$date             = CValue::getOrSession('date', CMbDT::date());
$group_mod        = CValue::getOrSession('group_mod', 1);
$interval         = CValue::getOrSession('interval', 'day');
$limit            = CValue::getOrSession('limit', 6);
$threshold        = CValue::getOrSession('threshold', 5);
$duration_operand = CValue::getOrSession('duration_operand');
$duration         = CValue::getOrSession('duration');

$limit = intval($limit);

if (!$threshold || $threshold < 0 || $threshold > 100) {
  $threshold = 5;
}

$threshold = ((int)$threshold) / 100;

// Parameters get by clicking on access logs graphic bar
$from             = CValue::get('from');
$to               = CValue::get('to');
$from_access_logs = CValue::get('from_access_logs');

$for_access_logs = false;
if ($from && $to && $from_access_logs) {
  $next            = $to;
  $for_access_logs = true;
}
else {
  $next = CMbDT::dateTime('+1 DAY', $date);
  switch ($interval) {
    default:
    case 'day':
      $from = CMbDT::dateTime('-1 DAY', $next);
      break;

    case 'week':
      $from = CMbDT::dateTime('-1 WEEK', $next);
      break;

    case 'month':
      $from = CMbDT::dateTime('-1 MONTH', $next);
  }
}

$ds = CSQLDataSource::get('std');

$where = array(
  'datetime_start' => $ds->prepare('BETWEEN ?1 AND ?2', $from, $next)
);

if ($duration && in_array($duration_operand, array('<', '<=', '=', '>', '>='))) {
  $where['duration'] = $ds->prepare("$duration_operand ?", (int)$duration);
}

$log = new CLongRequestLog();

$request = new CRequest(false);
$request->addTable($log->_spec->table);

// Multiple requests (group_mod = 2)
$requests = array();

switch ($group_mod) {
  // Get all
  case '1':
    $request->addSelect('`module_action_id`, SUM(`duration`) AS `duration`');
    $group_by = '`module_action_id`';
    $order_by = "SUM(`duration`) DESC LIMIT {$limit}";

    $request->addGroup($group_by);
    $request->addOrder($order_by);

    $stats_by_module = CLongRequestLogGraph::getDurationByModule($log, $where, $limit);
    break;

  // Group by module
  case '2':
    $stats_by_module = CLongRequestLogGraph::getDurationByModule($log, $where);

    foreach ($stats_by_module as $_module => $_duration) {
      $_request = new CRequest();
      $_request->addTable($log->_spec->table);

      $_request->addSelect('`module_action_id`, SUM(`duration`) AS `duration`');
      $group_by = '`module_action_id`';
      $order_by = "SUM(`duration`) DESC LIMIT {$limit}";

      $_request->addGroup($group_by);
      $_request->addOrder($order_by);

      if ($_module == 'null') {
        $where['module_action_id'] = 'IS NULL';
      }
      else {
        $_actions = CModuleAction::getActions($_module);

        if ($_actions) {
          $where['module_action_id'] = $ds->prepareIn($_actions);
        }
      }

      $_request->addWhere($where);

      $requests[$_module] = $_request;
    }
    break;

  // Module name is provided, show details
  default:
    $request->addSelect('`module_action_id`, SUM(`duration`) AS `duration`');
    $group_by = '`module_action_id`';
    $order_by = "SUM(`duration`) DESC LIMIT {$limit}";

    $request->addGroup($group_by);
    $request->addOrder($order_by);

    $actions = CModuleAction::getActions($group_mod);
    if ($actions) {
      $where['module_action_id'] = $ds->prepareIn($actions);
    }
}

switch ($group_mod) {
  default:
  case '1':
    $request->addWhere($where);

    $query = $request->makeSelect();
    $logs  = $ds->loadList($query);

    $module_actions = array();
    if ($logs) {
      $_ids = CMbArray::pluck($logs, 'module_action_id');

      foreach ($_ids as $_id) {
        $module_action        = new CModuleAction();
        $module_actions[$_id] = $module_action->load($_id);
      }
    }
    break;

  case '2':
    $logs = array();

    /** @var CRequest $_request */
    foreach ($requests as $_module => $_request) {
      $_query = $_request->makeSelect();
      $_logs  = $ds->loadList($_query);

      $_module_actions = array();
      if ($_logs) {
        $_ids = CMbArray::pluck($_logs, 'module_action_id');

        foreach ($_ids as $_id) {
          $_module_action        = new CModuleAction();
          $_module_actions[$_id] = $_module_action->load($_id);
        }
      }

      $logs[$_module] = array(
        'logs'           => $_logs,
        'module_actions' => $_module_actions
      );
    }
}

$graphs = array();

switch ($group_mod) {
  // Get all
  case '1':
    if ($for_access_logs) {
      $graphs[] = CLongRequestLogGraph::getDurationSeries($logs, $module_actions, $threshold, null, $from, $next);
    }
    else {
      $graphs[] = CLongRequestLogGraph::getTotalDurationSeries($stats_by_module, $threshold);
      $graphs[] = CLongRequestLogGraph::getDurationSeries($logs, $module_actions, $threshold);
    }
    break;

  // Group by module
  case '2':
    foreach ($logs as $_module => $_logs) {
      $graphs[] = CLongRequestLogGraph::getDurationSeries($_logs['logs'], $_logs['module_actions'], $threshold, $_module);
    }
    break;

  // Module name is provided, show details
  default:
    $graphs[] = CLongRequestLogGraph::getDurationSeries($logs, $module_actions, $threshold, $group_mod);
}

$smarty = new CSmartyDP();

$smarty->assign('log', $log);
$smarty->assign('graphs', $graphs);
$smarty->assign('interval', $interval);
$smarty->assign('group_mod', $group_mod);
$smarty->assign('date', $date);
$smarty->assign('min_date', $from);
$smarty->assign('max_date', $next);

$smarty->display('inc_stats_long_request_logs.tpl');