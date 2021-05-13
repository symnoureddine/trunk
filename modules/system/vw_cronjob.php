<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CMbDT;
use Ox\Core\CSmartyDP;
use Ox\Mediboard\System\Cron\CCronJobLog;

CCanDo::checkAdmin();

$log_cron = new CCronJobLog();
$log_cron->_date_min = CMbDT::dateTime("-7 DAY");
$log_cron->_date_max = CMbDT::dateTime("+1 DAY");

$log_purge = new CCronJobLog();
$log_purge->_date_max = CMbDT::date('last day of last month') . ' 23:59:59';

$smarty = new CSmartyDP();
$smarty->assign("log_cron", $log_cron);
$smarty->assign("log_purge", $log_purge);
$smarty->display("vw_cronjob.tpl");