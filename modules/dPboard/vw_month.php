<?php
/**
 * @package Mediboard\Board
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbDT;
use Ox\Core\CSmartyDP;
use Ox\Core\CValue;
use Ox\Mediboard\Mediusers\CFunctions;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\System\CPlanningMonth;

CCanDo::checkRead();

$user       = CMediusers::get();
$perm_fonct = CAppUI::pref("allow_other_users_board");

$user->isSecretaire();
$user->isProfessionnelDeSante();

$date        = CValue::getOrSession('date', CMbDT::date());
$prat_id     = CValue::getOrSession('praticien_id');
$function_id = CValue::get("function_id");

$prat = new CMediusers();
$prat->load($prat_id);

$function = new CFunctions();
$listFunc = array();
if ($perm_fonct == "same_function") {
  $listFunc[$user->function_id] = $user->loadRefFunction();
}
elseif ($perm_fonct == "write_right") {
  $listFunc = CMediusers::loadFonctions(PERM_EDIT);
}
elseif ($perm_fonct != 'only_me') {
  $listFunc = CMediusers::loadFonctions(PERM_READ);
}


/** @var CMediusers[] $listPrat */

if ($perm_fonct == 'only_me') {
  $listPrat[$user->_id] = $user;
  $prat                 = $user;
}
elseif ($perm_fonct == "same_function") {
  $listPrat = $prat->loadProfessionnelDeSante(PERM_READ, $user->function_id);
}
elseif ($perm_fonct == "write_right") {
  $listPrat = $prat->loadProfessionnelDeSante(PERM_EDIT, null);
}
else {
  $listPrat = $prat->loadProfessionnelDeSante(PERM_READ, null);
}


usort($listPrat, function ($a, $b) {
  return strcmp($a->_user_last_name, $b->_user_last_name);
});

$calendar = new CPlanningMonth($date);

// smarty
$smarty = new CSmartyDP();
$smarty->assign("date", $date);
$smarty->assign("prev", $date);
$smarty->assign("next", $date);
$smarty->assign("prat", $prat);
$smarty->assign("listPrat", $listPrat);

$smarty->assign("user", $user);

$smarty->assign("listFunc", $listFunc);
$smarty->assign("function_id", $function_id);

$smarty->display("vw_month");