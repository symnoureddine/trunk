<?php
/**
 * @package Mediboard\Soins
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CMbDT;
use Ox\Core\Module\CModule;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Interop\Imeds\CImeds;
use Ox\Mediboard\Admin\CAccessMedicalData;
use Ox\Mediboard\Admin\CBrisDeGlace;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\PlanningOp\COperation;
use Ox\Mediboard\PlanningOp\CSejour;
use Ox\Mediboard\PlanSoins\CAdministration;
use Ox\Mediboard\Prescription\CPrescription;
use Ox\Mediboard\Soins\CObjectifSoin;
use Ox\Mediboard\System\Forms\CExClassEvent;

$sejour_id           = CView::get("sejour_id", "ref class|CSejour");
$date                = CView::get("date", "date");
$defined_default_tab =
  "dossier_traitement" . (CAppUI::gconf("soins Other vue_condensee_dossier_soins") ? "_compact" : "");

if (CAppUI::gconf("soins dossier_soins tab_prescription_med") && CMediusers::get()->isPraticien()) {
  $defined_default_tab = "prescription_sejour";
}

$default_tab       = CView::get("default_tab", "str default|$defined_default_tab");
$popup             = CView::get("popup", "bool default|0");
$modal             = CView::get("modal", "bool default|0");
$operation_id      = CView::get("operation_id", "ref class|COperation");
$mode_pharma       = CView::get("mode_pharma", "bool default|0");
$mode_protocole    = CView::get("mode_protocole", "bool default|0", true);
$type_prescription = CView::get("type_prescription", "enum list|pre_admission|sejour|sortie default|sejour");
$line_guid_open    = CView::get("line_guid_open", "str");

CView::checkin();

$sejour = new CSejour();
$sejour->load($sejour_id);

CAccessMedicalData::logAccess($sejour);

$sejour->loadNDA(); // Needed in the head banner

if (CBrisDeGlace::isBrisDeGlaceRequired() && !CAccessMedicalData::checkForSejour($sejour)) {
  CAppUI::accessDenied();
}
CAccessMedicalData::logAccess($sejour);

$isPrescriptionInstalled = CModule::getActive("dPprescription");

if ($isPrescriptionInstalled) {
  CPrescription::$_load_lite = true;
}

// A faire avant le traitement de la prescription car elle est écrasée au chargement
$form_tabs = array();
if (CModule::getActive("forms")) {
  $objects = array(
    array("tab_dossier_soins_obs_entree", $sejour->loadRefObsEntree()),
    array("tab_dossier_soins", $sejour),
  );

  $form_tabs = CExClassEvent::getTabEvents($objects);
}

$sejour->loadRefPraticien();
$sejour->loadJourOp($date);
if ($isPrescriptionInstalled) {
  $prescription_sejour = $sejour->loadRefPrescriptionSejour();
  $prescription_sejour->loadJourOp($date);
  $prescription_sejour->loadRefCurrentPraticien();
  $prescription_sejour->loadLinesElementImportant();
}
$sejour->_ref_prescription_sejour->loadRefsLinesElement();

$patient = $sejour->loadRefPatient();
$patient->countINS();
$patient->loadRefsNotes();

$sejour->loadRefsOperations();
$sejour->loadRefCurrAffectation();
$sejour->loadRefsActesCCAM();
$sejour->loadRefsActesNGAP();
$sejour->loadPatientBanner();
$sejour->loadRefsRDVExternes();

if (CModule::getActive("maternite")) {
  $sejour->loadRefGrossesse();
  $naissance = $sejour->loadRefNaissance();
  $naissance->loadRefGrossesse();
  $naissance->loadRefSejourMaman();
  $patient->loadLastGrossesse();

  if ($patient->civilite == "enf") {
    $sejour->_ref_naissance->_ref_sejour_maman->loadRefPatient()->loadLastGrossesse();
  }
}

$operation = new COperation();
if ($operation->load($operation_id)) {
  CAccessMedicalData::logAccess($operation);

  $operation->loadRefPlageOp();
  $operation->_ref_anesth->loadRefFunction();
}
$is_praticien = CAppUI::$user->isPraticien();

if ($isPrescriptionInstalled) {
  CPrescription::$_load_lite = false;
}

$sejour->countAlertsNotHandled("medium", "observation");
$sejour->checkAnqsEmpty();

//On récupère le nombre d'objectifs de soin dont l'échéance est dépassée
$where_late_objectifs_soins = array(
  "delai"     => "< '" . CMbDT::date() . "'",
  "sejour_id" => "= $sejour->_id",
  "statut"    => "= 'ouvert'"
);
$objectif                   = new CObjectifSoin();
$late_objectifs             = $objectif->loadList($where_late_objectifs_soins);

$smarty = new CSmartyDP();

$smarty->assign("sejour", $sejour);
$smarty->assign("patient", $patient);
$smarty->assign("date", $date);
$smarty->assign("default_tab", $default_tab);
$smarty->assign("popup", $popup);
$smarty->assign("modal", $modal);
$smarty->assign("operation_id", $operation_id);
$smarty->assign("mode_pharma", $mode_pharma);
$smarty->assign("is_praticien", $is_praticien);
$smarty->assign("mode_protocole", $mode_protocole);
$smarty->assign("operation", $operation);
$smarty->assign("form_tabs", $form_tabs);
$smarty->assign("late_objectifs", $late_objectifs);
$smarty->assign("isImedsInstalled", (CModule::getActive("dPImeds") && CImeds::getTagCIDC(CGroups::loadCurrent())));
$smarty->assign("isPrescriptionInstalled", $isPrescriptionInstalled);
$smarty->assign("type_prescription", $type_prescription);
$smarty->assign("line_guid_open", $line_guid_open);

$smarty->display("inc_dossier_sejour");
