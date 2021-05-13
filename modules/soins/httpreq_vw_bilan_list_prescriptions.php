<?php
/**
 * @package Mediboard\Soins
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbArray;
use Ox\Core\CMbDT;
use Ox\Core\Module\CModule;
use Ox\Core\CSmartyDP;
use Ox\Core\CSQLDataSource;
use Ox\Core\CStoredObject;
use Ox\Core\CValue;
use Ox\Core\CView;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\PlanningOp\CSejour;
use Ox\Mediboard\Prescription\CPrescription;

CCanDo::checkRead();
$user              = CMediusers::get();
$praticien_id      = CView::get("prat_bilan_id", "ref class|CMediusers default|$user->_id", true);
$date_min          = CView::get("_date_entree_prevue", "date default|now", true);  // par default, date du jour
$date_max          = CView::get("_date_sortie_prevue", "date default|now", true);
$board             = CView::get("board", "bool default|0");
$signee            = CView::get("signee", "bool default|0", true); // par default les non signees
$type_prescription = CView::get("type_prescription", "enum list|pre_admission|sejour|sortie default|sejour", true); // sejour - externe - sortie_manquante
CView::enableSlave();
CView::checkin();

$date_min = $date_min . " 00:00:00";
$date_max = $date_max . " 23:59:59";

$prescriptions = array();
$prescription  = new CPrescription();

// Recherche des prescriptions
$where = array();

$group_id = CGroups::loadCurrent()->_id;

$ljoin[] = "sejour ON prescription.object_id = sejour.sejour_id AND prescription.object_class = 'CSejour'";

$where["prescription.type"] = " = 'sejour'";
$where["sejour.entree"]     = " <= '$date_max'";
$where["sejour.sortie"]     = " >= '$date_min'";
$where["sejour.group_id"]   = " = '$group_id'";

$wheres = array();

$dmi_active = CModule::getActive("dmi") && CAppUI::gconf("dmi CDM active");

if ($signee == "0") {
  if ($praticien_id) {
    $wheres = array(
      "prescription_line_element.praticien_id    = '$praticien_id' AND prescription_line_element.signee     != '1' AND prescription_line_element.child_id IS NULL"
    );
    if (CPrescription::isMPMActive()) {
        $wheres[] =
            "prescription_line_medicament.praticien_id = '$praticien_id' AND prescription_line_medicament.signee  != '1' AND prescription_line_medicament.variante_active = '1' AND prescription_line_medicament.substituted = '0' AND prescription_line_medicament.child_id IS NULL";
        $wheres[] =
            "prescription_line_mix.praticien_id        = '$praticien_id' AND prescription_line_mix.signature_prat != '1' AND prescription_line_mix.variante_active = '1' AND prescription_line_mix.substituted = '0' AND prescription_line_mix.next_line_id IS NULL";
    }

    if ($dmi_active) {
      $wheres[] = "administration_dm.praticien_id = '$praticien_id' AND administration_dm.signed != '1'";
    }
  }
  else {
    $wheres = array(
      "prescription_line_element.signee     != '1' AND prescription_line_element.child_id IS NULL"
    );
    if (CPrescription::isMPMActive()) {
        $wheres[] =
            "prescription_line_medicament.signee  != '1' AND prescription_line_medicament.variante_active = '1' AND prescription_line_medicament.substituted = '0' AND prescription_line_medicament.child_id IS NULL";
        $wheres[] =
            "prescription_line_mix.signature_prat != '1' AND prescription_line_mix.variante_active = '1' AND prescription_line_mix.substituted = '0' AND prescription_line_mix.next_line_id IS NULL";
    }

    if ($dmi_active) {
      $wheres[] = "administration_dm.signed != '1'";
    }
  }
}
else {
  if ($praticien_id) {
    $wheres = array(
      "prescription_line_element.praticien_id    = '$praticien_id'",
      "prescription_line_medicament.praticien_id = '$praticien_id'",
      "prescription_line_mix.praticien_id        = '$praticien_id'",
    );

    if ($dmi_active) {
      $wheres[] = "administration_dm.praticien_id = '$praticien_id'";
    }
  }
}

$classes = array(
  "CPrescriptionLineElement"
);

if (CPrescription::isMPMActive()) {
    $classes[] = "CPrescriptionLineMedicament";
    $classes[] = "CPrescriptionLineMix";
}

if ($dmi_active) {
  $classes[] = "CAdministrationDM";
}

if (count($wheres)) {
  $wheres[0]        .= " AND prescription_line_element.active = '1'";
  $wheres[1]        .= " AND prescription_line_medicament.active = '1'";
  $wheres[2]        .= " AND prescription_line_mix.active = '1'";
  $prescription_ids = $prescription->loadIds($where, null, null, null, $ljoin);

  foreach ($wheres as $_i => $_where) {
    $where = array(
      $_where,
      "prescription_id" => CSQLDataSource::prepareIn($prescription_ids),
    );

    $object = new $classes[$_i];
    $lines  = $object->loadList($where, null, null, "prescription_id");

    foreach ($lines as $_line) {
      $prescriptions[$_line->prescription_id] = $_line->loadRefPrescription();
    }
  }
}
else {
  $prescriptions = $prescription->loadList($where, null, null, "prescription_id", $ljoin);
}

$sejours = array();

foreach ($prescriptions as $_prescription) {
  $sejours[$_prescription->object_id] = $_prescription->_ref_object;
}

$patients = CStoredObject::massLoadFwdRef($sejours, "patient_id");
CStoredObject::massLoadBackRefs($patients, "bmr_bhre");
CSejour::massLoadNDA($sejours);
CPatient::massLoadIPP($patients);
CPatient::massCountPhotoIdentite($patients);

/** @var CSejour $_sejour */
foreach ($sejours as $_sejour) {
  $_sejour->loadRefPatient()->updateBMRBHReStatus();
}

if (count($prescriptions)) {
  CMbArray::pluckSort($prescriptions, SORT_ASC, "_ref_object", "_ref_patient", "nom");
}

if ($type_prescription == "sortie_manquante") {
  foreach ($prescriptions as $_prescription) {
    // Recherche d'une prescription de sortie correspondant à la prescription de sejour
    $_prescription_sortie               = new CPrescription();
    $_prescription_sortie->type         = "sortie";
    $_prescription_sortie->object_id    = $_prescription->object_id;
    $_prescription_sortie->object_class = $_prescription->object_class;
    $_prescription_sortie->loadMatchingObject();
    if ($_prescription_sortie->_id) {
      unset($prescriptions[$_prescription->_id]);
    }
  }
}

$sejour            = new CSejour();
$sejour->_date_min = $date_min;
$sejour->_date_max = $date_max;

if (!$praticien_id && $user->isPraticien()) {
  $praticien_id = $user->_id;
}

$now = CMbDT::date();

// Reorder by practionner type
$prescriptions_by_praticiens_type = array();

foreach ($prescriptions as $_prescription) {
  $_prescription->loadRefPatient();

  $patient = $_prescription->_ref_patient;
  $sejour  = $_prescription->_ref_object;

  $patient->loadRefPhotoIdentite();

  $praticien = $sejour->loadRefPraticien();
  $sejour->checkDaysRelative($now);
  $sejour->loadSurrAffectations($date_min);

  if ($_prescription->_id) {
    $_prescription->loadJourOp($now);
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
$smarty->assign("counter_prescription"            , $counter_prescription);
$smarty->display('inc_vw_bilan_list_prescriptions');
