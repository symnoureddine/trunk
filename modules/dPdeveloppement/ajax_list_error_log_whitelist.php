<?php
/**
 * @package Mediboard\Developpement
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CStoredObject;
use Ox\Mediboard\System\CErrorLogWhiteList;

CCanDo::checkRead();

$error_log_whitelist = new CErrorLogWhiteList();
$list = $error_log_whitelist->loadList();
CStoredObject::massLoadFwdRef($list, 'user_id');

// Création du template
$smarty = new CSmartyDP();
$smarty->assign('list', $list);
$smarty->display('inc_list_error_log_whitelist.tpl');