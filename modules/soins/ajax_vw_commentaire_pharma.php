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
$date         = CValue::getOrSession("date_com_pharma", CMbDT::date());

$group_id = CGroups::loadCurrent()->_id;
// Recherche des prescriptions
$where                      = $ljoin = array();
$ljoin["sejour"]            = "sejour.sejour_id = prescription.object_id AND prescription.object_class = 'CSejour'";
$where["prescription.type"] = " = 'sejour'";
$where["sejour.entree"]     = " <= '$date 23:59:00'";
$where["sejour.sortie"]     = " >= '$date 00:00:00'";
$where["sejour.group_id"]   = " = '$group_id'";

$prescription     = new CPrescription();
$prescription_ids = $prescription->loadIds($where, null, null, "prescription_id", $ljoin);


$where                       = array();
$where["commentaire_pharma"] = " IS NOT NULL";
$where["praticien_id"]       = " = '$praticien_id'";
$where["prescription_id"]    = CSQLDataSource::prepareIn($prescription_ids);

$line_med  = new CPrescriptionLineMedicament();
$lines_med = $line_med->loadList($where, "praticien_id, debut", null, "prescription_line_medicament_id");
$line_mix  = new CPrescriptionLineMix();
$lines_mix = $line_mix->loadList($where, "praticien_id, date_debut", null, "prescription_line_mix_id");

// Fusion des deux tableaux de lignes
$lines_com_pharma = array_merge($lines_mix, $lines_med);

$prescriptions = array();
foreach ($lines_com_pharma as $_line) {
  /* @var CPrescriptionLineMedicament|CPrescriptionLineMix $_line */
  $prescriptions[$_line->_ref_prescription->_guid] = $_line->_ref_prescription;
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
  $sejour->loadSurrAffectations('$date 00:00:00');
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
$smarty->assign("date", $date);
$smarty->assign("type", "com_pharma");

$smarty->display("vw_reeval_antibio");
