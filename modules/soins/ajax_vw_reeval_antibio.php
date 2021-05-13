<?php
/**
 * @package Mediboard\Soins
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CMbDT;
use Ox\Core\CSmartyDP;
use Ox\Core\CSQLDataSource;
use Ox\Core\CValue;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Prescription\CPrescription;
use Ox\Mediboard\Mpm\CPrescriptionLineMedicament;
use Ox\Mediboard\Mpm\CPrescriptionLineMix;

CCanDo::checkRead();
$praticien_id = CValue::getOrSession("prat_id");
$date_reeval  = CValue::getOrSession("date_reeval", CMbDT::date());

$group_id = CGroups::loadCurrent()->_id;
// Recherche des prescriptions
$where                      = $ljoin = array();
$ljoin["sejour"]            = "sejour.sejour_id = prescription.object_id AND prescription.object_class = 'CSejour'";
$where["prescription.type"] = " = 'sejour'";
$where["sejour.entree"]     = " <= '$date_reeval 23:59:00'";
$where["sejour.sortie"]     = " >= '$date_reeval 00:00:00'";
$where["sejour.group_id"]   = " = '$group_id'";

$prescription     = new CPrescription();
$prescription_ids = $prescription->loadIds($where, null, null, "prescription_id", $ljoin);

$where                    = array();
$where["type_antibio"]    = " = 'curatif'";
$where["praticien_id"]    = " = '$praticien_id'";
$where["prescription_id"] = CSQLDataSource::prepareIn($prescription_ids);

$line_med          = new CPrescriptionLineMedicament();
$lines_med_antibio = $line_med->loadList($where, "praticien_id, debut", null, "prescription_line_medicament_id");
$line_mix          = new CPrescriptionLineMix();
$lines_mix_antibio = $line_mix->loadList($where, "praticien_id, date_debut", null, "prescription_line_mix_id");

// Fusion des deux tableaux de lignes
$lines_antibio = array_merge($lines_mix_antibio, $lines_med_antibio);

$now = CMbDT::dateTime();

$prescriptions    = array();
$current_datetime = CMbDT::dateTime("+12 HOURS");
foreach ($lines_antibio as $_line) {
  /* @var CPrescriptionLineMedicament|CPrescriptionLineMix $_line */
  if (!$_line->isAntibio() || ($_line->_fin_reelle <= $now)) {
    continue;
  }
  $debut_antibio = $_line->debut_antibio;
  if (!$debut_antibio) {
    $debut_antibio = $_line instanceof CPrescriptionLineMix ? $_line->_debut : $_line->_debut_reel;
  }
  if ($_line->reevaluation_antibio) {
    $date_debut    = $_line instanceof CPrescriptionLineMix ? $_line->date_debut : $_line->debut;
    $debut_antibio = $_line->debut_antibio ? CMbDT::date($_line->debut_antibio) : $date_debut;
  }

  $min_first_reeval = CMbDT::dateTime("+24 HOURS", $debut_antibio);
  $min_last_reeval  = CMbDT::dateTime("+7 DAYS", $debut_antibio);
  $alert            = false;
  // Si la ligne n'est pas terminée dans 12h ou pas réévaluée
  if ($_line->_fin_reelle > $current_datetime || !$_line->reevaluation_antibio) {
    if ($current_datetime > $min_last_reeval && $_line->reevaluation_antibio < $min_last_reeval) {
      $alert = true;
    }
    if ($_line->type_antibio_curative != "doc" && $current_datetime > $min_first_reeval && $_line->reevaluation_antibio < $min_first_reeval) {
      $alert = true;
    }
  }

  if ($alert) {
    $prescriptions[$_line->_ref_prescription->_guid] = $_line->_ref_prescription;
  }
}

foreach ($prescriptions as $_prescription) {
  /* @var CPrescription $_prescription */
  $_prescription->loadRefPatient();
  $_prescription->loadJourOp(CMbDT::date());

  $patient = $_prescription->_ref_patient;
  $sejour  = $_prescription->_ref_object;

  $patient->loadIPP();
  $patient->loadRefPhotoIdentite();
  $sejour->loadRefPraticien();
  $sejour->checkDaysRelative(CMbDT::date());
  $sejour->loadSurrAffectations('$date_reeval 00:00:00');
  $sejour->loadNDA();
  $patient->loadRefDossierMedical();
  $dossier_medical = $patient->_ref_dossier_medical;

  if ($dossier_medical->_id) {
    $dossier_medical->loadRefsAllergies();
    $dossier_medical->loadRefsAntecedents();
    $dossier_medical->countAntecedents();
    $dossier_medical->countAllergies();
  }
}

$smarty = new CSmartyDP();

$smarty->assign("prescriptions", $prescriptions);
$smarty->assign("date", $date_reeval);
$smarty->assign("type", "reeval_antibio");

$smarty->display("vw_reeval_antibio");
