<?php
/**
 * @package Mediboard\Urgences
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbDT;
use Ox\Core\Module\CModule;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Interop\Imeds\CImeds;
use Ox\Mediboard\Admin\CAccessMedicalData;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Hospi\CService;
use Ox\Mediboard\Hospi\CUniteFonctionnelle;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Patients\CAntecedent;
use Ox\Mediboard\Patients\CTraitement;
use Ox\Mediboard\Mpm\CPrescriptionLineMedicament;
use Ox\Mediboard\System\Forms\CExClassEvent;
use Ox\Mediboard\Urgences\CChapitreMotif;
use Ox\Mediboard\Urgences\CMotif;
use Ox\Mediboard\Urgences\CRPU;

CCanDo::checkRead();
$sejour_id       = CView::get("sejour_id", "ref class|CSejour");
$rpu_id          = CView::get("rpu_id", "ref class|CRPU", true);
$_responsable_id = CView::get("_responsable_id", "ref class|CMediusers");
$fragment        = CView::get("fragment", "str");

CView::checkin();

$group = CGroups::get();
$user  = CMediusers::get();

$rpu = new CRPU();
if ($rpu_id && !$rpu->load($rpu_id)) {
  global $m, $tab;
  CAppUI::setMsg("Ce RPU n'est pas ou plus disponible", UI_MSG_WARNING);
  CAppUI::redirect("m=$m&tab=$tab&rpu_id=0");
}

// Création d'un RPU pour un séjour existant
if ($sejour_id && !$rpu->_id) {
  $rpu            = new CRPU();
  $rpu->sejour_id = $sejour_id;
}

$sejour  = $rpu->loadRefSejour();
$patient = $sejour->loadRefPatient();

CAccessMedicalData::logAccess($sejour);

if ($rpu->_id) {
  $patient->loadRefLatestConstantes(null, array('poids', 'taille', "clair_creatinine"));
  $patient->loadRefDossierMedical();
  $patient->loadRefsNotes();
  if ($patient->_ref_dossier_medical->_id) {
    $patient->_ref_dossier_medical->canDo();
    $patient->_ref_dossier_medical->loadRefsAllergies();
    $patient->_ref_dossier_medical->loadRefsAntecedents();
    $patient->_ref_dossier_medical->countAntecedents();
    $patient->_ref_dossier_medical->countAllergies();
  }
  // Chargement de l'IPP ($_IPP)
  $patient->loadIPP();
  $patient->countINS();

  $sejour->loadPatientBanner();
}

$services         = array();
$listResponsables = array();
if (CAppUI::conf("ref_pays") == 2) {
  if (!$rpu->_id) {
    $rpu->updateFormFields();
    $rpu->_entree = CMbDT::dateTime();
  }
  $rpu->loadRefEchelleTri();
  $rpu->loadRefMotif();
  $rpu->loadRefsReponses();
  $rpu->loadCanValideRPU();
  $rpu->orderCtes();
  $rpu->loadConstantesByDegre();
  $rpu->loadRefIDEResponsable();
  $rpu->loadRefIOA();
  $chapitre  = new CChapitreMotif();
  $chapitres = $chapitre->loadList();
  $motif     = new CMotif();
  if ($rpu->code_diag) {
    $motif->chapitre_id = $rpu->_ref_motif->chapitre_id;
  }
  $motifs = $motif->loadMatchingList();

  if (CAppUI::conf("dPurgences view_rpu_uhcd") || (in_array($sejour->type, array("comp", "ambu")) && $sejour->UHCD)) {
    // Affichage des services UHCD et d'urgence
    $services = CService::loadServicesUHCD();
  }
  else {
    // Urgences pour un séjour "urg"
    $services = CService::loadServicesUrgence();
  }

  if (CAppUI::conf("dPurgences CRPU imagerie_etendue", $group)) {
    $services += CService::loadServicesImagerie();
  }

  $listResponsables = CAppUI::conf("dPurgences only_prat_responsable") ?
    $user->loadPraticiens(PERM_READ, $group->service_urgences_id, null, true) :
    $user->loadListFromType(null, PERM_READ, $group->service_urgences_id, null, true, true);
}

$nb_printers = 0;

if (CModule::getActive("maternite")) {
  $patient->loadLastGrossesse();
}

if (CModule::getActive("printing")) {
  // Chargement des imprimantes pour l'impression d'étiquettes
  $user_printers = CMediusers::get();
  $function      = $user_printers->loadRefFunction();
  $nb_printers   = $function->countBackRefs("printers");
}

$form_tabs = array();
if (CModule::getActive('forms')) {
  $objects = array(
    array('tab_dossier_infirmier', $rpu),
  );

  $form_tabs = CExClassEvent::getTabEvents($objects);
}

// Création du template
$smarty = new CSmartyDP();

if (CAppUI::conf("ref_pays") == 2) {
  $smarty->assign("chapitre_id", 0);
  $smarty->assign("chapitres", $chapitres);
  $smarty->assign("motif_id", 0);
  $smarty->assign("motifs", $motifs);
  $smarty->assign("services", $services);
  $smarty->assign("listResponsables", $listResponsables);
}

$smarty->assign("group", $group);
if (CModule::getActive("dPprescription")) {
  $sejour->loadRefPrescriptionSejour()->loadLinesElementImportant();
}

$smarty->assign("userSel", $user);
$smarty->assign("_responsable_id", $_responsable_id);
$smarty->assign("rpu", $rpu);
$smarty->assign("sejour", $sejour);
$smarty->assign("patient", $patient);
$smarty->assign("traitement", new CTraitement());
$smarty->assign("antecedent", new CAntecedent());
$smarty->assign("isPrescriptionInstalled", CModule::getActive("dPprescription"));
$smarty->assign("isImedsInstalled", (CModule::getActive("dPImeds") && CImeds::getTagCIDC(CGroups::loadCurrent())));
$smarty->assign("ufs", CUniteFonctionnelle::getUFs($sejour));
$smarty->assign("fragment", $fragment);
/* Verification de l'existance de la base DRC (utilisée dans les antécédents */
$smarty->assign('drc', array_key_exists('drc', CAppUI::conf('db')));
$smarty->assign('cisp', array_key_exists('cisp', CAppUI::conf('db')));
$smarty->assign("nb_printers", $nb_printers);
$smarty->assign('form_tabs', $form_tabs);

$smarty->display("vw_aed_rpu");
