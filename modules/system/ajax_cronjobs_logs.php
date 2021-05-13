<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CValue;
use Ox\Mediboard\System\Cron\CCronJobLog;

CCanDo::checkAdmin();

$status     = CValue::get("status");
$severity   = CValue::get("severity");
$cronjob_id = CValue::get("cronjob_id");
$date_min   = CValue::get("_date_min");
$date_max   = CValue::get("_date_max");
$page       = (int)CValue::get("page", 0);
$where      = array();

if ($status) {
  $where["status"] = "= '$status'";
}
if ($severity) {
  $where["severity"] = "= '$severity'";
}
if ($cronjob_id) {
  $where["cronjob_id"] = "= '$cronjob_id'";
}
if ($date_min) {
  $where["start_datetime"] = ">= '$date_min'";
}
if ($date_max) {
  $where["start_datetime"] = $date_min ? $where["start_datetime"]."AND start_datetime <= '$date_max'" : "<= '$date_max'";
}

$log    = new CCronJobLog();
/** @var CCronJobLog[] $logs */
$nb_log = $log->countList($where);
$logs   = $log->loadList($where, "start_datetime DESC", "$page, 30");

CCronJobLog::massLoadFwdRef($logs, "cronjob_id");
foreach ($logs as $_log) {
  $_log->loadRefCronJob();
}

$smarty = new CSmartyDP();
$smarty->assign("logs"  , $logs);
$smarty->assign("page"  , $page);
$smarty->assign("nb_log", $nb_log);
$smarty->display("inc_cronjobs_logs.tpl");