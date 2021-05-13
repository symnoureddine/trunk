<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CMbArray;
use Ox\Core\CSmartyDP;
use Ox\Core\CSQLDataSource;
use Ox\Core\CStoredObject;
use Ox\Core\CValue;
use Ox\Core\CView;
use Ox\Mediboard\System\CLongRequestLog;
use Ox\Mediboard\System\CModuleAction;

CCanDo::checkAdmin();

$start = (int)CValue::get("start", 0);

$datetime_start_min = CValue::get("_datetime_start_min");
$datetime_start_max = CValue::get("_datetime_start_max");
$datetime_end_min   = CValue::get("_datetime_end_min");
$datetime_end_max   = CValue::get("_datetime_end_max");
$user_id            = CValue::get("user_id");
$duration_operand   = CValue::get("duration_operand");
$duration           = CValue::get("duration");
$module             = CValue::get('filter_module');
$module_action_id   = CValue::get('module_action_id');
$user_type          = CView::get('_user_type', 'enum list|all|human|bot');

CView::checkin();

CView::enableSlave();

$ds = CSQLDataSource::get('std');

$filter = new CLongRequestLog();
$table  = $filter->getSpec()->table;
$key    = $filter->getSpec()->key;

$where = [];
$ljoin = [];

if ($user_id) {
  $where["user_id"] = $ds->prepare("= ?", $user_id);
}

if ($datetime_start_min) {
  $where[] = $ds->prepare("`datetime_start` >= ?", $datetime_start_min);
}

if ($datetime_start_max) {
  $where[] = $ds->prepare("`datetime_start` <= ?", $datetime_start_max);
}

if ($datetime_end_min) {
  $where[] = $ds->prepare("`datetime_end` >= ?", $datetime_end_min);
}

if ($datetime_end_max) {
  $where[] = $ds->prepare("`datetime_end` <= ?", $datetime_end_max);
}

if ($duration && in_array($duration_operand, array('<', '<=', '=', '>', '>='))) {
  $where['duration'] = $ds->prepare("$duration_operand ?", (int)$duration);
}

if ($module_action_id) {
  $where['module_action_id'] = $ds->prepare("= ?", $module_action_id);
}
elseif ($module) {
  $module_action         = new CModuleAction();
  $module_action->module = $module;

  $module_actions = $module_action->loadMatchingListEsc();
  if ($module_actions) {
    $where['module_action_id'] = $ds->prepareIn(CMbArray::pluck($module_actions, '_id'));
  }
}

switch ($user_type) {
  case 'human':
    $ljoin = [
      'users' => "{$table}.user_id = users.user_id",
    ];

    $where[] = "users.is_robot = '0'";
    break;

  case 'bot':
    $ljoin = [
      'users' => "{$table}.user_id = users.user_id",
    ];

    $where[] = "users.is_robot = '1'";
    break;

  case 'all':
  default:
}

$order    = "datetime_start DESC";
$group_by = "{$table}.{$key}";

/** @var CLongRequestLog[] $logs */
$logs       = $filter->loadList($where, $order, "$start, 50", $group_by, $ljoin);
$list_count = $filter->countMultipleList($where, null, $group_by, $ljoin);
$list_count = count($list_count);

CStoredObject::massLoadFwdRef($logs, 'user_id');
CStoredObject::massLoadFwdRef($logs, 'module_action_id');

foreach ($logs as $_log) {
  $_log->loadRefUser()->loadRefFunction();
  $_log->getModuleAction();
  $_log->loadRefSession();

  $_log->computePerformanceRatio();
}

$smarty = new CSmartyDP();

$smarty->assign("start", $start);
$smarty->assign("list_count", $list_count);
$smarty->assign("filter", $filter);
$smarty->assign("logs", $logs);

$smarty->display("inc_list_long_request_logs.tpl");