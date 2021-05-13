<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CSmartyDP;
use Ox\Core\CStoredObject;
use Ox\Mediboard\System\Cron\CCronJob;

$cronjob = new CCronJob();
/** @var CCronJob[] $cronjobs */
$cronjobs = $cronjob->loadList();

CStoredObject::massLoadFwdRef($cronjobs, 'token_id');

foreach ($cronjobs as $_cronjob) {
  $_cronjob->loadRefToken();
  $_cronjob->getNextDate();
  $_cronjob->loadLastsStatus();
}

$smarty = new CSmartyDP();
$smarty->assign("cronjobs", $cronjobs);
$smarty->display("inc_list_cronjobs.tpl");