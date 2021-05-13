<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CSmartyDP;
use Ox\Core\CValue;
use Ox\Mediboard\System\Cron\CCronJob;

$identifiant = CValue::get("identifiant");
$list_ip     = trim(CAppUI::conf("servers_ip"));
$address     = array();

if ($list_ip) {
  $address = preg_split("/\s*,\s*/", $list_ip, -1, PREG_SPLIT_NO_EMPTY);
}

$cronjob = new CCronJob();
$cronjob->load($identifiant);

$cronjob->loadRefToken();

$smarty = new CSmartyDP();
$smarty->assign("cronjob", $cronjob);
$smarty->assign("address", $address);
$smarty->display("inc_edit_cronjob.tpl");