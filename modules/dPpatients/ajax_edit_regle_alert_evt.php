<?php
/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CSmartyDP;
use Ox\Core\CSQLDataSource;
use Ox\Core\CView;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Patients\CProgrammeClinique;
use Ox\Mediboard\Patients\CRegleAlertePatient;

$regle_id = CView::get("regle_id", "ref class|CRegleAlertePatient");
CView::checkin();

$user = CMediusers::get();

$regle = new CRegleAlertePatient();
$regle->load($regle_id);
$regle->loadRefsUsers();
if (!$regle->_id) {
  $regle->group_id            = CGroups::loadCurrent()->_id;
  $regle->function_id         = $user->function_id;
  $regle->nb_anticipation     = 1;
  $regle->periode_refractaire = 2;
}

$users                    = $user->loadListWithPerms(PERM_EDIT);
$where                    = array();
$where["coordinateur_id"] = CSQLDataSource::prepareIn(array_keys($users));
$where["annule"]          = " = '0'";
$programme                = new CProgrammeClinique();
$programmes               = $programme->loadList($where, "nom");

if ($regle->programme_clinique_id && !isset($programmes[$regle->programme_clinique_id])) {
  $programmes[$regle->programme_clinique_id] = $regle->loadRefProgramme();
}

$smarty = new CSmartyDP();
$smarty->assign("regle", $regle);
$smarty->assign("programmes", $programmes);
$smarty->display("vw_edit_regle_alert_evt.tpl");
