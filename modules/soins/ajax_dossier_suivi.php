<?php
/**
 * @package Mediboard\Soins
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\Module\CModule;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Admin\CAccessMedicalData;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\PlanningOp\CSejour;
use Ox\Mediboard\PlanSoins\CAdministration;
use Ox\Mediboard\Prescription\CPrescription;

CCanDo::checkRead();

$group = CGroups::loadCurrent();

$sejour_id = CView::get("sejour_id", "ref class|CSejour");
$date      = CView::get("date", "date");

CView::checkin();

$sejour = new CSejour();
$sejour->load($sejour_id);

CAccessMedicalData::logAccess($sejour);

$sejour->countTasks();
$sejour->countRDVExternes();
$sejour->countObjectifsSoins();
$sejour->countObjectifsDelayWeek();
$sejour->countAlertsNotHandled("medium", "observation");
$sejour->isInPermission();

$prescription_active = CModule::getActive("dPprescription");
$plan_soins_active   = $prescription_active ? CPrescription::isPlanSoinsActive() : false;
$prescription        = $sejour->loadRefPrescriptionSejour();

$count_perop_adm = 0;
if ($prescription_active && $plan_soins_active) {
  if (CAppUI::conf("dPprescription general show_perop_suivi_soins", $group->_guid) && $prescription->_id) {
    $count_perop_adm = CAdministration::countPerop($prescription->_id);
  }

  if (CAppUI::gconf("dPprescription general show_perop_suivi_soins")) {
    $last_operation = $sejour->loadRefLastOperation();
    $last_operation->loadRefsAnesthPerops();

    if ($last_operation->_id && $last_operation->_ref_anesth_perops) {
      $count_perop_adm += $last_operation->_count_anesth_perops;
    }
  }
}

// Création du template
$smarty = new CSmartyDP();
$smarty->assign("sejour"           , $sejour);
$smarty->assign("date"             , $date);
$smarty->assign("count_perop_adm"  , $count_perop_adm);
$smarty->assign("plan_soins_active", $plan_soins_active);
$smarty->assign("prescription"     , $prescription);
$smarty->display("inc_dossier_suivi");
