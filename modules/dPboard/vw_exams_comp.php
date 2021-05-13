<?php
/**
 * @package Mediboard\Board
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

//Récupération des paramètres
use Ox\Core\CMbDT;
use Ox\Core\CSmartyDP;
use Ox\Core\CStoredObject;
use Ox\Core\CValue;
use Ox\Mediboard\Cabinet\CConsultAnesth;
use Ox\Mediboard\Cabinet\CConsultation;
use Ox\Mediboard\Cabinet\CExamComp;
use Ox\Mediboard\Mediusers\CMediusers;

$_date_min = CValue::getOrSession("_date_min", CMbDT::date());
$date      = CValue::getOrSession("date");
$list      = CValue::get("list", 0);

$user = CMediusers::get();

if ($_date_min) {
  $date = null;
}
if (!$_date_min && !$date) {
  $_date_min = CMbDT::date();
}

$filter            = new CConsultation();
$filter->_date_min = $_date_min;

$ljoin                        = array();
$ljoin["consultation"]        = "consultation.consultation_id = exams_comp.consultation_id";
$ljoin["plageconsult"]        = "consultation.plageconsult_id = plageconsult.plageconsult_id";
$ljoin["consultation_anesth"] = "consultation_anesth.consultation_id = consultation.consultation_id";
$ljoin[]                      = "sejour AS sejour_consult ON sejour_consult.sejour_id = consultation.sejour_id";
$ljoin[]                      = "sejour AS sejour_anesth ON sejour_anesth.sejour_id = consultation_anesth.sejour_id";
if ($date && !$_date_min) {
  $ljoin[] = "operations AS op_anesth ON op_anesth.sejour_id = sejour_anesth.sejour_id";
  $ljoin[] = "operations AS op_consult ON op_consult.sejour_id = sejour_consult.sejour_id";
}

$where                       = array();
$where["exams_comp.exam_id"] = " IS NOT NULL";
if ($_date_min) {
  $where[] = " (sejour_consult.entree BETWEEN '$_date_min 00:00:00' AND '$_date_min 23:59:00')
OR (sejour_anesth.entree BETWEEN '$_date_min 00:00:00' AND '$_date_min 23:59:00')";
}
elseif ($date) {
  $where[] = "(op_consult.date = '$date') OR (op_anesth.date = '$date')";
}

$where[] = "plageconsult.chir_id = '$user->_id'";

$exam    = new CExamComp();
$examens = $exam->loadList($where, null, null, "exam_id", $ljoin);

$consultations = CStoredObject::massLoadFwdRef($examens, "consultation_id");
CStoredObject::massLoadFwdRef($consultations, "patient_id");
CStoredObject::massLoadFwdRef($consultations, "sejour_id");
$plages = CStoredObject::massLoadFwdRef($consultations, "plageconsult_id");
CStoredObject::massLoadFwdRef($plages, "chir_id");

foreach ($examens as $_exam) {
  /* @var CExamComp $_exam */
  $consult = $_exam->loadRefConsult();
  $consult->loadRefPlageConsult()->loadRefChir()->loadRefFunction();
  $consult->loadRefPatient();
  $dossiers                     = $consult->loadRefsDossiersAnesth();
  $consult->_ref_consult_anesth = new CConsultAnesth();
  foreach ($dossiers as $_dossier) {
    $_dossier->loadRefOperation();
    if (($_date_min && $_dossier->_ref_sejour->entree >= CMbDT::dateTime("$_date_min 00:00:00") && $_dossier->_ref_sejour->entree <= CMbDT::dateTime("$_date_min 23:59:00"))
      || ($date && $_dossier->_ref_operation->date == $date)) {
      $_dossier->loadRefSejour();
      $consult->_ref_consult_anesth = $_dossier;
      continue;
    }
  }

  if ((!$consult->_ref_consult_anesth || !$consult->_ref_consult_anesth->sejour_id) && $consult->sejour_id) {
    $consult->loadRefSejour()->loadRefPraticien()->loadRefFunction();
  }
}

// Création du template
$smarty = new CSmartyDP();

$smarty->assign("filter", $filter);
$smarty->assign("examens", $examens);
$smarty->assign("date", $date);

if ($list) {
  $smarty->display("vw_list_exams_comp");
}
else {
  $smarty->display("vw_exams_comp");
}