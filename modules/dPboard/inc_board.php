<?php
/**
 * @package Mediboard\Board
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Mediusers\CFunctions;
use Ox\Mediboard\Mediusers\CMediusers;

/**
 * dPboard
 */
global $tab, $a;

CCanDo::checkRead();

$prat_selected     = CView::get("praticien_id", "ref class|CMediusers", true);
$function_selected = CView::get("function_id", "ref class|CFunctions", $tab === "vw_day" || $a === "vw_day");

global $prat;
global $function;

// Chargement de l'utilisateur courant
$user       = CMediusers::get();
$perm_fonct = CAppUI::pref("allow_other_users_board");

if (!$user->isProfessionnelDeSante() && !$user->isSecretaire()) {
  CAppUI::accessDenied();
}

$praticiens = null;
$prat       = new CMediusers();
$function   = new CFunctions();

if ($prat_selected) {
  $function_selected = null;
  $prat->load($prat_selected);
}
elseif ($user->isProfessionnelDeSante() && !$function_selected) {
  $prat = $user;
}

if ($function_selected) {
  $function->load($function_selected);
}

global $smarty;

// Création du template
$smarty = new CSmartyDP();

$smarty->assign("user", $user);
$smarty->assign("prat", $prat);
$smarty->assign("function", $function);
$smarty->assign("perm_fonct", $perm_fonct);

$smarty->display("inc_board");
