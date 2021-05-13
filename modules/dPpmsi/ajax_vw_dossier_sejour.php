<?php
/**
 * @package Mediboard\Pmsi
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\Module\CModule;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Interop\Imeds\CImeds;
use Ox\Mediboard\Admin\CAccessMedicalData;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Fse\CFseFactory;
use Ox\Mediboard\Maternite\CNaissance;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\PlanningOp\CSejour;
use Ox\Mediboard\System\Forms\CExClassEvent;

CCanDo::checkEdit();
$patient_id = CView::get('patient_id', 'ref class|CPatient');
$sejour_id = CView::get('sejour_id', 'ref class|CSejour');
CView::checkin();

$group = CGroups::loadCurrent();

$naissance_enf = new CNaissance();

// Chargement du patient
$patient = new CPatient();
$patient->load($patient_id);
$patient->loadIPP();
$patient->loadRefsCorrespondants();
$patient->loadRefPhotoIdentite();
$patient->loadPatientLinks();
$patient->countINS();
$patient->updateBMRBHReStatus();
if (CModule::getActive("fse")) {
  $cv = CFseFactory::createCV();
  if ($cv) {
    $cv->loadIdVitale($patient);
  }
}

// Chargement du séjour
$sejour  = new CSejour();
$sejour_maman  = new CSejour();
$sejour->load($sejour_id);

CAccessMedicalData::logAccess($sejour);

if ($sejour->patient_id == $patient->_id) {
  $sejour->canDo();
  $sejour->loadNDA();
  $sejour->loadExtDiagnostics();
  $sejour->loadRefsAffectations();
  $sejour->loadSuiviMedical();
  $sejour->_ref_patient = $patient;
  foreach ($sejour->loadRefsOperations() as $_op) {
    $_op->loadRefChirs();
    $_op->loadRefPlageOp();
    $_op->loadRefAnesth();
    $_op->loadRefsConsultAnesth();
    $_op->loadRefBrancardage();
  }
  $sejour->loadRefsConsultAnesth();
  foreach ($sejour->loadRefsActesCCAM() as $_acte) {
    $_acte->loadRefExecutant();
  }

  /**
   * Gestion des séjours obstétriques
   **/

  // Dans le cadre où le dossier pmsi est celui de l'enfant
  $naissance_enf = $sejour->loadUniqueBackRef("naissance");
  if ($naissance_enf && $naissance_enf->_id) {
    /** @var CNaissance $naissance_enf */
    $naissance_enf->canDo();
    $naissance_enf->loadRefGrossesse();
    $sejour_enf = $naissance_enf->loadRefSejourEnfant();
    $sejour_enf->loadRelPatient();
    $sejour_enf->loadRefUFHebergement();
    $sejour_enf->loadRefUFMedicale();
    $sejour_enf->loadRefUFSoins();
    $sejour_enf->loadRefService();
    $sejour_enf->loadRefsNotes();

    // Chargement du séjour de la maman
    $sejour_maman = $naissance_enf->loadRefSejourMaman();
    if ($sejour_maman && $sejour_maman->_id) {
      $sejour_maman->loadRefGrossesse();
      $sejour_maman->_ref_grossesse->canDo();
      $grossesse = $sejour_maman->_ref_grossesse;

      $grossesse->loadLastAllaitement();
      $grossesse->loadFwdRef("group_id");

      $sejour_maman->canDo();
      $sejour_maman->_ref_patient = $grossesse->loadRefParturiente();
      $sejour_maman->loadRefUFHebergement();
      $sejour_maman->loadRefUFMedicale();
      $sejour_maman->loadRefUFSoins();
      $sejour_maman->loadRefService();
      $sejour_maman->loadRefsNotes();

      foreach ( $grossesse->loadRefsNaissances() as $_naissance) {
        $_naissance->loadRefSejourEnfant();
        $_naissance->_ref_sejour_enfant->loadRelPatient();
      }
    }
  }

  // Dans le cadre où le dossier pmsi est celui de la maman
  if ($sejour->grossesse_id) {
    $sejour->canDo();
    $sejour->loadRefUFHebergement();
    $sejour->loadRefUFMedicale();
    $sejour->loadRefUFSoins();
    $sejour->loadRefService();
    $sejour->loadRefsNotes();
    $sejour->loadRefGrossesse();
    $sejour->_ref_grossesse->canDo();

    $grossesse = $sejour->_ref_grossesse;
    $grossesse->loadLastAllaitement();
    $grossesse->loadFwdRef("group_id");

    foreach ($grossesse->loadRefsNaissances() as $_naissance) {
      $_naissance->loadRefSejourEnfant();
      $_naissance->_ref_sejour_enfant->loadRelPatient();
    }
  }
}
else {
  $sejour = new CSejour();
}

$form_tabs = array();
if (CModule::getActive("forms")) {
  $objects = array(
    array("tab_dossier_soins", $sejour),
  );

  $form_tabs = CExClassEvent::getTabEvents($objects);
}

if (is_array($sejour->_ref_suivi_medical)) {
  krsort($sejour->_ref_suivi_medical);
}

// Création du template
$smarty = new CSmartyDP("modules/dPpmsi");

$smarty->assign("canPatients"     , CModule::getCanDo("dPpatients"));
$smarty->assign("hprim21installed", CModule::getActive("hprim21"));
$smarty->assign("isImedsInstalled", (CModule::getActive("dPImeds") && CImeds::getTagCIDC($group)));
$smarty->assign("patient"         , $patient);
$smarty->assign("sejour"          , $sejour);
$smarty->assign("sejour_maman"    , $sejour_maman);
$smarty->assign("naissance"       , $naissance_enf);
$smarty->assign("form_tabs"       , $form_tabs);

$smarty->display("inc_vw_dossier_sejour");
