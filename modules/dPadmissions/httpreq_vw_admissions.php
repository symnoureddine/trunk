<?php
/**
 * @package Mediboard\Admissions
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\AppFine\Client\CAppFineClient;
use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbArray;
use Ox\Core\CMbDT;
use Ox\Core\Module\CModule;
use Ox\Core\CSmartyDP;
use Ox\Core\CSQLDataSource;
use Ox\Core\CStoredObject;
use Ox\Core\CView;
use Ox\Interop\Dmp\CDMP;
use Ox\Mediboard\Cabinet\CConsultAnesth;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Hospi\CPrestation;
use Ox\Mediboard\Mediusers\CFunctions;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Patients\CDossierMedical;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\PlanningOp\CModeEntreeSejour;
use Ox\Mediboard\PlanningOp\COperation;
use Ox\Mediboard\PlanningOp\CSejour;

CCanDo::checkRead();

$sejour = new CSejour();

// Type d'admission
$services_ids          = CView::get("services_ids", "str", true);
$sejours_ids           = CView::get("sejours_ids", "str", true);
$enabled_service       = CView::get("active_filter_services", "bool default|0", true);
$prat_id               = CView::get("prat_id", "ref class|CMediusers", true);
$prestations_p_ids     = CView::get("prestations_p_ids", array("str", "default" => array()), true);
$selAdmis              = CView::get("selAdmis", "str default|0", true);
$selSaisis             = CView::get("selSaisis", "str default|0", true);
$order_col             = CView::get("order_col", "str default|patient_id", true);
$order_way             = CView::get("order_way", "str default|ASC", true);
$date                  = CView::get("date", "date default|now", true);
$filterFunction        = CView::get("filterFunction", "ref class|CFunctions", true);
$period                = CView::get("period", "str", true);
$type_pec              = CView::get("type_pec", array('str', 'default' => $sejour->_specs["type_pec"]->_list), true);
$print_global          = CView::get("print_global", "bool default|0");
$date_interv_eg_entree = CView::get("date_interv_eg_entree", "bool default|0", true);
$reglement_dh          = CView::get('reglement_dh', 'enum list|all|payed|not_payed');
$circuits_ambu         = CView::get("circuit_ambu", array('str', 'default' => $sejour->_specs["circuit_ambu"]->_list), true);
CView::checkin();

if (is_array($prestations_p_ids)) {
  CMbArray::removeValue("", $prestations_p_ids);
} else {
  $prestations_p_ids = [$prestations_p_ids];
}

// Pour plus de simplicit�, on vide le tableau des filtres de presta si le filtre est "Toutes les prestas"
if (is_array($prestations_p_ids)) {
  CMbArray::removeValue('', $prestations_p_ids);

  if (in_array("all", $prestations_p_ids)) {
    $prestations_p_ids = null;
  }
}

$type_pref = array();

// Liste des types d'admission possibles
$list_type_admission = $sejour->_specs["_type_admission"]->_list;

if (is_array($circuits_ambu)) {
  CMbArray::removeValue("", $circuits_ambu);
}

if (is_array($services_ids)) {
  CMbArray::removeValue("", $services_ids);
}

if (is_array($sejours_ids)) {
  CMbArray::removeValue("", $sejours_ids);

  // recupere les pr�f�rences des differents types de s�jours selectionn�s par l'utilisateur
  foreach ($sejours_ids as $key) {
    if ($key != 0) {
      $type_pref[] = $list_type_admission[$key];
    }
  }
}

$date_actuelle = CMbDT::dateTime("00:00:00");
$date_demain   = CMbDT::dateTime("00:00:00", "+ 1 day");

$hier   = CMbDT::date("- 1 day", $date);
$demain = CMbDT::date("+ 1 day", $date);

$date_min = CMbDT::dateTime("00:00:00", $date);
$date_max = CMbDT::dateTime("23:59:59", $date);

if ($period) {
  $hour = CAppUI::gconf("dPadmissions General hour_matin_soir");
  if ($period == "matin") {
    // Matin
    $date_max = CMbDT::dateTime($hour, $date);
  }
  else {
    // Soir
    $date_min = CMbDT::dateTime($hour, $date);
  }
}

// Entr�es de la journ�e
$sejour = new CSejour();

$group = CGroups::loadCurrent();

$use_perms = CAppUI::gconf("dPadmissions General use_perms");

// Lien avec les patients
$ljoin["patients"] = "sejour.patient_id = patients.patient_id";

// Filtre sur les services
if (count($services_ids) && $enabled_service) {
  $ljoin["affectation"]            = "affectation.sejour_id = sejour.sejour_id AND affectation.entree = sejour.entree";
  $where[] = "affectation.service_id " . CSQLDataSource::prepareIn($services_ids) .
             "  OR (sejour.service_id " . CSQLDataSource::prepareIn($services_ids) . " AND affectation.affectation_id IS NULL)";
}

// Filtre sur le type du s�jour
if (count($type_pref)) {
  $where["sejour.type"] = CSQLDataSource::prepareIn($type_pref);
}
else {
  $where["sejour.type"] = CSQLDataSource::prepareNotIn(array_merge(CSejour::getTypesSejoursUrgence(), ["seances"]));
}

// filtre sur le type de circuit en ambulatoire
if (CAppUI::gconf("dPplanningOp CSejour show_circuit_ambu") && $circuits_ambu && count($circuits_ambu)) {
  $where[] = "(sejour.circuit_ambu " . CSQLDataSource::prepareIn($circuits_ambu) . " AND sejour.type = 'ambu') OR sejour.circuit_ambu IS NULL";
}

// Filtre sur le praticien
if ($prat_id) {
  $user = CMediusers::get($prat_id);

  if ($user->isAnesth()) {
    $ljoin['operations'] = 'sejour.sejour_id = operations.sejour_id';
    $ljoin['plagesop'] = 'plagesop.plageop_id = operations.plageop_id';
    $where[] = " operations.anesth_id = '$prat_id' OR plagesop.anesth_id = '$prat_id' OR sejour.praticien_id = '$prat_id'";
  }
  else {
    $where['sejour.praticien_id'] = " = '$prat_id'";
  }
}

if ($date_interv_eg_entree) {
  if (!isset($ljoin["operations"])) {
    $ljoin['operations'] = 'sejour.sejour_id = operations.sejour_id';
  }
  $where[] = "operations.date = DATE(sejour.entree_prevue)";
}

if ($reglement_dh && $reglement_dh != 'all') {
  if (!isset($ljoin["operations"])) {
    $ljoin['operations'] = 'sejour.sejour_id = operations.sejour_id';
  }
  if ($reglement_dh == 'payed') {
    $where[] = "((operations.depassement > 0 AND operations.reglement_dh_chir != 'non_regle') 
        OR operations.depassement = 0 OR operations.depassement IS NULL)
      AND ((operations.depassement_anesth > 0 AND operations.reglement_dh_anesth != 'non_regle') 
        OR operations.depassement_anesth = 0 OR operations.depassement_anesth IS NULL)
      AND (operations.depassement > 0 OR operations.depassement_anesth > 0)";
  }
  else {
    $where[] = "(operations.depassement > 0 AND operations.reglement_dh_chir = 'non_regle')
      OR (operations.depassement_anesth > 0 AND operations.reglement_dh_anesth = 'non_regle')";
  }
}

$where["sejour.group_id"] = "= '$group->_id'";
$where["sejour.entree"]   = "BETWEEN '$date_min' AND '$date_max'";
$where["sejour.annule"]   = "= '0'";

if ($selAdmis != "0") {
  $where["sejour.entree_reelle"] = " IS NULL OR `sejour`.`entree_reelle` = '0000-00-00 00:00:00'";
}

if ($selSaisis != "0") {
  $where["sejour.entree_preparee"] = "= '0'";
}

if (!in_array($order_col, array("patient_id", "entree_prevue", "praticien_id", "_passage_bloc"))) {
  $order_col = "patient_id";
}
$order = null;
if ($order_col == "patient_id") {
  $order = "patients.nom $order_way, patients.prenom $order_way, sejour.entree_prevue";
}

if ($order_col == "entree_prevue") {
  $order = "sejour.entree_prevue $order_way, patients.nom, patients.prenom";
}

if ($order_col == "praticien_id") {
  $ljoin["users"] = "sejour.praticien_id = users.user_id";
  $order = "users.user_last_name $order_way, users.user_first_name";
}

if (!(in_array('', $type_pec) && count($type_pec) == 1)) {
  $where[] = "sejour.type_pec " . CSQLDataSource::prepareIn($type_pec) . 'OR sejour.type_pec IS NULL';
}

/** @var CSejour[] $sejours */
$sejours = $use_perms ?
  $sejour->loadListWithPerms(PERM_READ, $where, $order, null, "sejour.sejour_id", $ljoin) :
  $sejour->loadList($where, $order, null, "sejour.sejour_id", $ljoin);

// Mass preloading
$patients = CStoredObject::massLoadFwdRef($sejours, "patient_id");
CStoredObject::massLoadFwdRef($sejours, "etablissement_entree_id");
$praticiens = CStoredObject::massLoadFwdRef($sejours, "praticien_id");
$functions  = CStoredObject::massLoadFwdRef($praticiens, "function_id");
$affectations = CStoredObject::massLoadBackRefs($sejours, "affectations", "sortie DESC");
if (CModule::getActive("maternite")) {
  $parent_affectations = CStoredObject::massLoadFwdRef($affectations, "parent_affectation_id");
  $parent_sejours      = CStoredObject::massLoadFwdRef($parent_affectations, "sejour_id");
  CStoredObject::massLoadFwdRef($parent_sejours, "patient_id");
}
CStoredObject::massLoadBackRefs($sejours, "appels");

// Chargement optimis� des prestations
CSejour::massLoadPrestationSouhaitees($sejours);

CStoredObject::massLoadBackRefs($sejours, "notes");
CStoredObject::massLoadBackRefs($patients, "dossier_medical");
CStoredObject::massLoadBackRefs($patients, "bmr_bhre");

if (CModule::getActive('appFineClient') && CAppUI::gconf("appFineClient Sync allow_appfine_sync")) {
  CStoredObject::massLoadBackRefs($patients, "status_patient_user");
  $group_id = CGroups::loadCurrent()->_id;
  CAppFineClient::massloadIdex($sejours, $group_id);
  CAppFineClient::massloadIdex($patients, $group_id);
  CStoredObject::massLoadBackRefs($sejours, 'folder_liaison', null, array("type" => " = 'pread' "), null,  "folder_liaison_pread");
}

$operations = CStoredObject::massLoadBackRefs($sejours, "operations", "date ASC", array("annulee" => "= '0'"));
CStoredObject::massLoadBackRefs($operations, "actes_ngap", "lettre_cle DESC");

$order = "code_association, code_acte,code_activite, code_phase, acte_id";
CStoredObject::massLoadBackRefs($operations, "actes_ccam", $order);

if (CModule::getActive("dmp")) {
  CStoredObject::massLoadBackRefs($patients, "state_dmp");
}

/** @var COperation[] $operations_total */
$operations_total = array();

// Chargement des NDA
CSejour::massLoadNDA($sejours);
// Chargement des IPP
CPatient::massLoadIPP($patients);
foreach ($sejours as $sejour_id => $_sejour) {
  $praticien = $_sejour->loadRefPraticien();
  if ($filterFunction && $filterFunction != $praticien->function_id) {
    unset($sejours[$sejour_id]);
    continue;
  }

  if ($prestations_p_ids && isset($_sejour->_back["items_liaisons"])) {
    //Filtre sur les prestations
    $is_in_filter = false;
    if (in_array("none", $prestations_p_ids) && !$_sejour->_back["items_liaisons"]) {
      $is_in_filter = true;
    }
    foreach ($_sejour->_back["items_liaisons"] as $_item_liaison) {
      $item_presta = $_item_liaison->_ref_item;
      if ($item_presta && in_array($item_presta->object_id, $prestations_p_ids)) {
        $is_in_filter = true;
        break;
      }
    }
    if (!$is_in_filter) {
      //si les items du sejours ne correspondent pas au filtre, on l'unset de la liste
      unset($sejours[$sejour_id]);
      continue;
    }
  }

  // Chargement du patient
  $patient = $_sejour->loadRefPatient();
  $patient->loadRefsPatientHandicaps();

  if (CModule::getActive('appFineClient') && CAppUI::gconf("appFineClient Sync allow_appfine_sync")) {
    $_sejour->_ref_patient->loadRefStatusPatientUser();
    $_sejour->loadRefFolderLiaison("pread");
  }
  $patient->countINS();

  $patient->updateBMRBHReStatus();

  $patient->loadStateDMP();

  // Dossier m�dical
  $dossier_medical = $patient->loadRefDossierMedical(false);

  // Chargement des notes sur le s�jourw
  $_sejour->loadRefsNotes();

  // Chargement des modes d'entr�e
  $_sejour->loadRefEtablissementProvenance();

  // Chargement du praticien r�f�rent
  $_sejour->loadRefAdresseParPraticien();

  // Chargement de l'affectation
  $affectation = $_sejour->loadRefFirstAffectation();
  if (CModule::getActive("maternite")) {
    $affectation->loadRefParentAffectation()->loadRefSejour()->loadRefPatient();
  }

  // Chargement des appels
  $_sejour->loadRefsAppel('admission');

  // Chargement des interventions
  $whereOperations = array("annulee" => "= '0'");
  $operations      = $_sejour->loadRefsOperations($whereOperations);
  $operations_total += $operations;
  $_sejour->getPassageBloc();
}

// Optimisation du chargement des interventions
/** @var CConsultAnesth[] $dossiers_anesth_total */
$dossiers_anesth_total = array();

$ljoin = array(
  "consultation" => "consultation.consultation_id = consultation_anesth.consultation_id",
  "plageconsult" => "consultation.plageconsult_id = plageconsult.plageconsult_id"
);
CStoredObject::massLoadBackRefs($operations_total, "dossiers_anesthesie", "date DESC", null, $ljoin);
CStoredObject::massLoadFwdRef($operations_total, 'plageop_id');

foreach ($operations_total as $operation) {
  $operation->loadRefsActes();
  $consult_anesth                              = $operation->loadRefsConsultAnesth();
  $dossiers_anesth_total[$consult_anesth->_id] = $consult_anesth;
  $operation->loadRefPlageOp();
}

// Optimisation du chargement des dossiers d'anesth�sie
$consultations = CStoredObject::massLoadFwdRef($dossiers_anesth_total, "consultation_id");
CStoredObject::massLoadFwdRef($consultations, "plageconsult_id");
foreach ($dossiers_anesth_total as $dossier_anesth) {
  $consultation = $dossier_anesth->loadRefConsultation();
  $consultation->loadRefPlageConsult();
  $consultation->loadRefPraticien()->loadRefFunction();
}

if (CAppUI::gconf("dPadmissions General show_deficience")) {
  $dossiers = CMbArray::pluck($sejours, "_ref_patient", "_ref_dossier_medical");
  CDossierMedical::massCountAntecedentsByType($dossiers, "deficience");
}

// Si la fonction selectionn�e n'est pas dans la liste des fonction, on la rajoute
if ($filterFunction && !array_key_exists($filterFunction, $functions)) {
  $_function = new CFunctions();
  $_function->load($filterFunction);
  $functions[$filterFunction] = $_function;
}

$list_mode_entree = array();
if (CAppUI::conf("dPplanningOp CSejour use_custom_mode_entree")) {
  $mode_entree      = new CModeEntreeSejour();
  $where            = array(
    "actif" => "= '1'",
  );
  $list_mode_entree = $mode_entree->loadGroupList($where);
}

if ($order_col == "_passage_bloc") {
    CMbArray::pluckSort($sejours, constant("SORT_" . $order_way), "_passage_bloc");
}
// Cr�ation du template
$smarty = new CSmartyDP();

$smarty->assign("hier"              , $hier);
$smarty->assign("demain"            , $demain);
$smarty->assign("date_min"          , $date_min);
$smarty->assign("date_max"          , $date_max);
$smarty->assign("date_demain"       , $date_demain);
$smarty->assign("date_actuelle"     , $date_actuelle);
$smarty->assign("date"              , $date);
$smarty->assign("selAdmis"          , $selAdmis);
$smarty->assign("selSaisis"         , $selSaisis);
$smarty->assign("order_col"         , $order_col);
$smarty->assign("order_way"         , $order_way);
$smarty->assign("sejours"           , $sejours);
$smarty->assign("prestations"       , CPrestation::loadCurrentList());
$smarty->assign("canAdmissions"     , CModule::getCanDo("dPadmissions"));
$smarty->assign("canPatients"       , CModule::getCanDo("dPpatients"));
$smarty->assign("canPlanningOp"     , CModule::getCanDo("dPplanningOp"));
$smarty->assign("functions"         , $functions);
$smarty->assign("filterFunction"    , $filterFunction);
$smarty->assign("period"            , $period);
$smarty->assign("list_mode_entree"  , $list_mode_entree);
$smarty->assign('enabled_service'   , $enabled_service);
$smarty->assign('print_global'      , $print_global);
$smarty->assign('circuits_ambu'     , $circuits_ambu);

$smarty->display("inc_vw_admissions.tpl");
