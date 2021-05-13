<?php
/**
 * @package Mediboard\Soins
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CMbArray;
use Ox\Core\CMbDT;
use Ox\Core\CSmartyDP;
use Ox\Core\CSQLDataSource;
use Ox\Core\CStoredObject;
use Ox\Core\CView;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\PlanningOp\CSejour;
use Ox\Mediboard\Prescription\CPrescription;

CCanDo::checkRead();
$user         = CMediusers::get();
$praticien_id = CView::get("prat_bilan_id", "ref class|CMediusers default|$user->_id", true);
$date_min     = CView::get("_date_entree_prevue", "date default|now", true);  // par default, date du jour
$date_max     = CView::get("_date_sortie_prevue", "date default|now", true);
$board        = CView::get("board", "bool default|0");
CView::checkin();
CView::enableSlave();

$group_id = CGroups::loadCurrent()->_id;

$date_min = $date_min . " 00:00:00";
$date_max = $date_max . " 23:59:59";

$sejour = new CSejour();
$where  = array(
  "sejour.entree"   => "<= '$date_max'",
  "sejour.sortie"   => ">= '$date_min'",
  "sejour.group_id" => "= '$group_id'",
  "sejour.annule"   => "= '0'"
);

$sejours_ids = $sejour->loadIds($where);

$prescriptions = array();
$prescription  = new CPrescription();

// Recherche des prescriptions
$where = array(
  "prescription.object_class" => "= 'CSejour'",
  "prescription.type"         => "= 'sejour'",
  "prescription.object_id"    => CSQLDataSource::prepareIn($sejours_ids)
);

$leftjoins = array(
  "prescription_line_element ON prescription_line_element.prescription_id = prescription.prescription_id",
);

if (CPrescription::isMPMActive()) {
    $leftjoins[] =
        "prescription_line_medicament ON prescription_line_medicament.prescription_id = prescription.prescription_id";
    $leftjoins[] =
        "prescription_line_mix ON prescription_line_mix.prescription_id = prescription.prescription_id";
}

$wheres = array(
  array("prescription_line_element.praticien_id    = '$praticien_id'",
    "prescription_line_element.inscription     = '1'",
    "prescription_line_element.active          = '1'")
);

if (CPrescription::isMPMActive()) {
    $wheres[] =
        array("prescription_line_medicament.praticien_id = '$praticien_id'",
            "prescription_line_medicament.inscription  = '1'",
            "prescription_line_medicament.active       = '1'");
    $wheres[] =
        array("prescription_line_mix.praticien_id        = '$praticien_id'",
            "prescription_line_mix.inscription         = '1'",
            "prescription_line_mix.active              = '1'");
}

CPrescription::$_load_lite = true;

foreach ($wheres as $_i => $_where) {
  $ljoin    = array();
  $where[0] = $_where[0];
  $where[1] = $_where[1];

  if (isset($leftjoins[$_i])) {
    $ljoin = $leftjoins[$_i];
  }

  $prescriptions += $prescription->loadList($where, null, null, "prescription_id", $ljoin);
}

CPrescription::$_load_lite = false;

$sejours = CStoredObject::massLoadFwdRef($prescriptions, "object_id", "CSejour");
CStoredObject::massLoadFwdRef($sejours, "praticien_id");
CSejour::massLoadNDA($sejours);
$patients = CStoredObject::massLoadFwdRef($sejours, "patient_id");
CStoredObject::massLoadBackRefs($patients, "dossier_medical");
CStoredObject::massLoadBackRefs($patients, "bmr_bhre");
CPatient::massLoadIPP($patients);
CPatient::massCountPhotoIdentite($patients);
foreach ($prescriptions as $_presc) {
  $_presc->loadRefObject();
  $_presc->loadRefPatient();
}

if (count($prescriptions)) {
  CMbArray::pluckSort($prescriptions, SORT_ASC, "_ref_object", "_ref_patient", "nom");
}

$sejour            = new CSejour();
$sejour->_date_min = $date_min;
$sejour->_date_max = $date_max;

// Reorder by practionner type
$prescriptions_by_praticiens_type = array();

/* @var CPrescription $_prescription */
foreach ($prescriptions as $_prescription) {
  $patient = $_prescription->_ref_patient;
  $sejour  = $_prescription->_ref_object;

  $patient->loadRefPhotoIdentite();
  $patient->updateBMRBHReStatus();

  $praticien = $sejour->loadRefPraticien();
  $sejour->checkDaysRelative(CMbDT::date());
  $sejour->loadSurrAffectations($date_min);

  if ($_prescription->_id) {
    $_prescription->loadJourOp(CMbDT::date());
  }

  $patient->loadRefDossierMedical();
  $dossier_medical = $patient->_ref_dossier_medical;

  if ($dossier_medical->_id) {
    $dossier_medical->loadRefsAllergies();
    $dossier_medical->loadRefsAntecedents();
    $dossier_medical->countAntecedents();
    $dossier_medical->countAllergies();
  }

  $prescriptions_by_praticiens_type[$praticien->_user_type_view][$_prescription->_id] = $_prescription;
}

$counter_prescription = $prescriptions && count($prescriptions) ? count($prescriptions) : 0;

// Smarty template
$smarty = new CSmartyDP();
$smarty->assign("prescriptions_by_praticiens_type", $prescriptions_by_praticiens_type);
$smarty->assign("board"                           , $board);
$smarty->assign("date"                            , $date_min);
$smarty->assign("default_tab"                     , "prescription_sejour");
$smarty->assign("default_id"                      , "inscriptions");
$smarty->assign("counter_prescription"            , $counter_prescription);
$smarty->display('inc_vw_bilan_list_prescriptions');
