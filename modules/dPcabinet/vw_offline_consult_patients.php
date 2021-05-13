<?php
/**
 * @package Mediboard\Cabinet
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CCanDo;
use Ox\Core\CMbDT;
use Ox\Core\CSmartyDP;
use Ox\Core\CSQLDataSource;
use Ox\Core\CStoredObject;
use Ox\Core\CView;
use Ox\Mediboard\Cabinet\CConsultation;
use Ox\Mediboard\Cabinet\CPlageconsult;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Patients\CConstantesMedicales;

CCanDo::checkRead();

CApp::setMemoryLimit("512M");

$function_id = CView::get("function_id", "ref class|CFunctions");
$chir_ids    = CView::get("chir_ids", "str");
$date        = CView::get("date", "date default|" . CMbDT::date());

CView::checkin();
CView::enforceSlave();

// Praticiens sélectionnés
$user = new CMediusers();
$praticiens = array();
if ($function_id) {
  $praticiens = CConsultation::loadPraticiens(PERM_EDIT, $function_id);
}
if ($chir_ids) {
  $praticiens = $user->loadAll(explode("-", $chir_ids));
}

//plages de consultation
$where = array();
$where["chir_id"] = CSQLDataSource::prepareIn(array_keys($praticiens));
$where["date"] = "= '$date'";
$plage = new CPlageconsult();
$plages = $plage->loadList($where, array("debut"));

$nbConsultations = 0;
$resumes_patient = array();

$prats = CStoredObject::massLoadFwdRef($plages, "chir_id");
CStoredObject::massLoadFwdRef($prats, "function_id");

/** @var $plages CPlageConsult[] */
foreach ($plages as $_plage_consult) {
  $_plage_consult->loadRefsConsultations(false, false);
  $_plage_consult->loadRefChir();
  $_plage_consult->countPatients();
  $_plage_consult->_ref_chir->loadRefFunction();
  $_plage_consult->loadFillRate();

  /** @var $consultations CConsultation[] */
  $patients = CStoredObject::massLoadFwdRef($_plage_consult->_ref_consultations, "patient_id");
  CStoredObject::massLoadBackRefs($patients, "dossier_medical");
  CStoredObject::massCountBackRefs($patients, "files");
  CStoredObject::massCountBackRefs($patients, "documents");
  CStoredObject::massLoadBackRefs($patients, "correspondants");

  foreach ($_plage_consult->_ref_consultations as $_consult) {
    if (!$_consult->patient_id || isset($resumes_patient[$_consult->patient_id])) {
      continue;
    }

    $patient = $_consult->loadRefPatient();
    $patient->loadDossierComplet();
    $patient->loadRefDossierMedical();

    foreach ($patient->_ref_consultations as $__consult) {
      $_latest_constantes = CConstantesMedicales::getLatestFor($patient, null, array("poids", "taille"), $__consult);
      $__consult->_latest_constantes = $_latest_constantes[0];

      $__consult->loadRefsDocItems(false);
      $__consult->countDocItems();
      $__consult->loadRefsActesCCAM();
      $__consult->loadRefsActesNGAP();
      $__consult->loadRefFacture()->loadRefsReglements();
      $__consult->loadRefPlageConsult();
    }


    $smarty = new CSmartyDP();
    $smarty->assign("offline", 1);
    $smarty->assign("patient", $_consult->_ref_patient);
    $resumes_patient[$patient->_id] = $smarty->fetch("vw_resume.tpl");  //dynamic assignment

    if ($_consult->patient_id) {
      $nbConsultations++;
    }
  }
}

//smarty global
$smarty = new CSmartyDP();

$smarty->assign("plages"         , $plages);
$smarty->assign("nbConsultations", $nbConsultations);
$smarty->assign("praticiens"     , $praticiens);
$smarty->assign("resumes_patient", $resumes_patient);
$smarty->assign("date"           , $date);

$smarty->display("vw_offline/consult_patients.tpl");