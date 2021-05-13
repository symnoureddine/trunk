<?php
/**
 * @package Mediboard\PlanningOp
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Admin\CAccessMedicalData;
use Ox\Mediboard\PlanningOp\CConsommationMateriel;
use Ox\Mediboard\PlanningOp\CMaterielOperatoire;
use Ox\Mediboard\PlanningOp\COperation;

CCanDo::checkEdit();

$operation_id = CView::get("operation_id", "ref class|COperation");
$mode         = CView::get("mode", "str");

CView::checkin();

$operation = new COperation();
$operation->load($operation_id);

CAccessMedicalData::logAccess($operation);

$operation->loadRefsProtocolesOperatoires();
$operation->loadRefsConsultAnesth();
$operation->loadRefChir();
$operation->canDo();

$sejour = $operation->loadRefSejour();
$sejour->loadRefPatient();
$sejour->loadPatientBanner();
$sejour->_ref_patient->loadRefsNotes();

$materiel_op = new CMaterielOperatoire();
$materiel_op->operation_id = $operation->_id;

$readonly = 0;

if ($operation->consommation_user_id) {
  $readonly = 1;
}

if (CAppUI::gconf("dPsalleOp COperation numero_panier_mandatory") && !$operation->numero_panier) {
  $readonly = 1;
}

// Création du template
$smarty = new CSmartyDP();

$smarty->assign("operation", $operation);
$smarty->assign("materiel_op", $materiel_op);
$smarty->assign("mode", $mode);
$smarty->assign("consommation", new CConsommationMateriel());
$smarty->assign("readonly", $readonly);

$smarty->display("vw_materiel_operation");