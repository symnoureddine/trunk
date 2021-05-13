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
use Ox\Core\CView;
use Ox\Core\Handlers\Facades\HandlerManager;
use Ox\Interop\Imeds\CImeds;
use Ox\Mediboard\Admin\CAccessMedicalData;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Hospi\CAffectation;
use Ox\Mediboard\Hospi\CChambre;
use Ox\Mediboard\Hospi\CLit;
use Ox\Mediboard\Hospi\CService;
use Ox\Mediboard\Mediusers\CDiscipline;
use Ox\Mediboard\Mediusers\CFunctions;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\PlanningOp\CSejour;
use Ox\Mediboard\Prescription\CPrescription;

global $m;
global $current_m;

// Redirection pour gérer le cas ou le volet par defaut est l'autre affichage des sejours
if (CAppUI::pref("vue_sejours") == "global" && $m == "soins") {
  CAppUI::redirect("m=soins&tab=vw_sejours");
}

CAppUI::requireModuleFile("dPhospi", "inc_vw_affectations");

CCanDo::checkRead();
$group = CGroups::loadCurrent();

// Filtres
$date             = CView::get("date", "date", true);
$mode             = CView::get("mode", "num default|0", true);
$service_id       = CView::get("service_id", "str", true);
$praticien_id     = CView::get("praticien_id", "str", true);
$type_admission   = CView::get("type", "str", true);
$function_id      = CView::get("func_id", "ref class|CFunctions", true);
$discipline_id    = CView::get("discipline_id", "ref class|CDiscipline", true);
$my_patient       = CView::get("my_patient", "str", true);
$services_ids     = CView::get("services_ids", "str", true);
$order_col_sejour = CView::get("order_col_sejour", "enum list|praticien_id|patient_id|_entree|_date_consult|order_sent default|patient_id", true);
$order_way_sejour = CView::get("order_way_sejour", "enum list|ASC|DESC default|ASC", true);

// Chargement de l'utilisateur courant
$userCourant = CMediusers::get();

$_is_praticien = $userCourant->isPraticien();
$_is_anesth    = $userCourant->isAnesth();

// Préselection du contexte
if (!$praticien_id && !$function_id && !$discipline_id) {
  switch (CAppUI::pref("preselect_me_care_folder")) {
    case "1":
      if ($_is_praticien) {
        $praticien_id = $userCourant->user_id;
      }
      break;
    case "2":
      $function_id = $userCourant->function_id;
      break;
    case "3":
      if ($_is_praticien) {
        $discipline_id = $userCourant->discipline_id;
      }
      break;
    default:
  }
}

if ($praticien_id == 'none') {
  $praticien_id = '';
}

$changeSejour = @CView::get("service_id", "ref class|CService") || @CView::get("praticien_id", "ref class|CMediusers");
$changeSejour = $changeSejour || (!$service_id && is_countable($services_ids) && !count($services_ids) && !$praticien_id);

if ($changeSejour) {
  $sejour_id = null;
  CView::setSession("sejour_id");
}
else {
  $sejour_id = CView::get("sejour_id", "ref class|CSejour", true);
}

if (CAppUI::conf("soins Sejour select_services_ids", $group)) {
  $services_ids = CService::getServicesIdsPref($services_ids);
  if ($services_ids) {
    $service_id = null;
  }

  if ($discipline_id || $function_id) {
    $services_ids = array();
  }
}
else {
  $services_ids = array();
}

CView::checkin();

// récuperation du service par défaut dans les préférences utilisateur
$default_services_id = CAppUI::pref("default_services_id", "{}");

// Ne pas prendre en compte le service pour le filtre sur discipline ou spécialité
if ($function_id || $discipline_id) {
  $default_services_id = "";
}

// Récuperation du service à afficher par défaut (on prend le premier s'il y en a plusieurs)
$default_service_id  = "";
$default_services_id = json_decode($default_services_id);
if (isset($default_services_id->{"g$group->_id"})) {
  $default_service_id = explode("|", $default_services_id->{"g$group->_id"});
  $default_service_id = reset($default_service_id);
}

if (!$service_id && $default_service_id && !$praticien_id && !CAppUI::conf("soins Sejour select_services_ids", $group)) {
  $service_id = $default_service_id;
}

if (!$date) {
  $date = CMbDT::date();
}

$alert_handler = HandlerManager::isObjectHandlerActive('CPrescriptionAlerteHandler');

$prescription_active = CModule::getActive("dPprescription");

$tab_sejour = array();

// Récupération de la liste des services
$where              = array();
$where["externe"]   = "= '0'";
$where["cancelled"] = "= '0'";
$service            = new CService();

if ($_is_praticien) {
  $services = $service->loadGroupList($where);
}
else {
  $services = $service->loadListWithPerms(PERM_READ, $where);
}

// Récupération du service à ajouter/éditer
$totalLits = 0;

// A passer en variable de configuration
$heureLimit = "16:00:00";

// Initialisation
$service                = new CService();
$groupSejourNonAffectes = array();
$sejoursParService      = array();

// Chargement de la liste de praticiens
$prat       = new CMediusers();
$praticiens = $prat->loadPraticiens(PERM_READ);

/* Handle the list of mediusers for the view displayed in the board module */
if ($current_m === 'dPboard') {
  $board_access = CAppUI::pref("allow_other_users_board");
  if ($userCourant->isProfessionnelDeSante() && $board_access == 'only_me') {
    $praticiens = [$userCourant->_id => $userCourant];
  }
  elseif ($userCourant->isProfessionnelDeSante() && $board_access == 'same_function') {
    $praticiens = $prat->loadPraticiens(PERM_READ, $userCourant->function_id);
  }
  elseif ($userCourant->isProfessionnelDeSante() && $board_access == 'write_right') {
    $praticiens = $prat->loadPraticiens(PERM_EDIT);
  }
}

// Restructuration minimal des services
global $sejoursParService, $all_sejours;
$sejoursParService = array();
$all_sejours = array();
$count_my_patient  = 0;

// Chargement du praticien
$praticien = new CMediusers();
if ($praticien_id) {
  $praticien->load($praticien_id);
}

$anesth        = new CMediusers();
$anesthesistes = array_keys($anesth->loadAnesthesistes());

$ids_all_sejours = array();
// Si seulement le praticien est indiqué
if (($praticien_id || $function_id || $discipline_id) && !$service_id && !count($services_ids)) {
  $sejours           = array();
  $sejour            = new CSejour();
  $where             = array();
  $ljoin             = array();
  $where["group_id"] = "= '$group->_id'";

  if ($praticien->_id) {
    $whereUser = $praticien->getUserSQLClause();
  }

  if ($praticien->isAnesth()) {
    $ljoin["operations"] = "operations.sejour_id = sejour.sejour_id";
    $ljoin["plagesop"]   = "operations.plageop_id = plagesop.plageop_id";
    $where[100]             = "operations.anesth_id $whereUser OR (operations.anesth_id IS NULL AND plagesop.anesth_id $whereUser)
                OR sejour.praticien_id $whereUser";
  }
  elseif ($function_id || $discipline_id) {
    $where["sejour.praticien_id"] = CSQLDataSource::prepareIn(array_keys($praticiens), $praticien_id);
  }
  else {
    $where["sejour.praticien_id"] = $whereUser;
  }

  $where["sejour.entree"] = " <= '$date 23:59:59'";
  $where["sejour.sortie"] = " >= '$date 00:00:00'";
  $where["annule"] = " = '0'";
  $where[]         = $type_admission ?
    "type = '$type_admission'" : ("type " . CSQLDataSource::prepareNotIn(CSejour::getTypesSejoursUrgence()) . " AND type != 'exte'");

  if ($function_id || $discipline_id) {
    $ljoin["users_mediboard"]    = "sejour.praticien_id = users_mediboard.user_id";
    $ljoin["secondary_function"] = "sejour.praticien_id = secondary_function.user_id";

    if ($function_id) {
      $where[101] = "$function_id IN (users_mediboard.function_id, secondary_function.function_id)";
    }
    else {
      $where[102] = "users_mediboard.discipline_id = '$discipline_id'";
    }
  }

  if ($praticien->isAnesth() || $function_id || $discipline_id) {
    $sejours = $sejour->loadList($where, null, null, "sejour.sejour_id", $ljoin);
  }
  else {
    $sejours = $sejour->loadList($where);
  }

  if (CAppUI::gconf("dPplanningOp CSejour use_prat_aff")) {
    $ljoin["affectation"] = "affectation.sejour_id = sejour.sejour_id";

    if ($praticien->isAnesth()) {
      $where[100] = "operations.anesth_id $whereUser OR (operations.anesth_id IS NULL AND plagesop.anesth_id $whereUser)
                OR affectation.praticien_id $whereUser";
    }
    elseif ($function_id || $discipline_id) {
      unset($ljoin["users_mediboard"]);
      unset($ljoin["secondary_function"]);
      $ljoin["users_mediboard"]    = "affectation.praticien_id = users_mediboard.user_id";
      $ljoin["secondary_function"] = "affectation.praticien_id = secondary_function.user_id";
    }
    else {
      $where["affectation.praticien_id"] = $where["sejour.praticien_id"];
      unset($where["sejour.praticien_id"]);
    }

    $sejours += $sejour->loadList($where, null, null, "sejour.sejour_id", $ljoin);
  }

  $dtnow = CMbDT::dateTime();
  $dnow  = CMbDT::date();

  foreach ($sejours as $_sejour) {
    $ids_all_sejours[$_sejour->_id] = $_sejour;
    $count_my_patient               += count($_sejour->loadRefsUserSejour($userCourant, $date, $mode));
    /* @var CSejour $_sejour */
    if ($_is_anesth || ($_is_praticien && $_sejour->praticien_id == $userCourant->user_id)) {
      $tab_sejour[$_sejour->_id] = $_sejour;
    }
    $affectations                   = array();
    $affectation                    = new CAffectation();
    $where                          = array();
    $where["affectation.sejour_id"] = " = '$_sejour->_id'";
    $where["affectation.entree"]    = "<= '$date 23:59:59'";
    $where["affectation.sortie"]    = ">= '$date 00:00:00'";
    $ljoin                          = array();
    $complement                     = "";
    if ($date == $dnow) {
      $ljoin["sejour"] = "affectation.sejour_id = sejour.sejour_id";
      $complement      = ($mode == "0" ? "OR " : "") .
        "(sejour.sortie_reelle >= '$dtnow' AND affectation.sortie >= '$dtnow')";
    }

    if ($complement || $mode === "0") {
      $where[] = ($mode == "0" ? "affectation.effectue = '0' " : "") . $complement;
    }

    $affectations = $affectation->loadList($where, null, null, null, $ljoin);

    if (count($affectations) >= 1) {
      foreach ($affectations as $_affectation) {
        /* @var CAffectation $_affectation */
        $_affectation->loadRefsAffectations();
        cacheLit($_affectation);
      }
    }
    else {
      $_sejour->loadRefsPrescriptions();
      $_sejour->loadRefPatient();
      $_sejour->loadRefPraticien();
      $_sejour->_ref_praticien->loadRefFunction();
      $_sejour->loadNDA();
      $sejoursParService["NP"][$_sejour->_id] = $_sejour;
    }
  }
}

foreach ($sejoursParService as $key => $_service) {
  if ($key != "NP") {
    CMbArray::pluckSort($_service->_ref_chambres, SORT_ASC, "nom");
    foreach ($_service->_ref_chambres as $_chambre) {
      foreach ($_chambre->_ref_lits as $_lit) {
        foreach ($_lit->_ref_affectations as $_affectation) {
          $_affectation->loadRefsAffectations();
          $_affectation->loadRefSejour();
          $_sejour                        = $_affectation->_ref_sejour;
          $ids_all_sejours[$_sejour->_id] = $_sejour;
          if ($_is_anesth || ($_is_praticien && $_sejour->praticien_id == $userCourant->user_id)) {
            $tab_sejour[$_sejour->_id] = $_sejour;
          }
          loadSejour($_sejour, $_affectation, $temp_count, $userCourant, $date, $mode, $alert_handler);
        }
      }
    }
  }
}

// Tri des sejours par nom de services
$sejoursParService = CMbArray::ksortByArray($sejoursParService, array_merge(array_keys($services), array('NP')));

// Récuperation du sejour sélectionné
$sejour = new CSejour();
$sejour->load($sejour_id);

if ($service_id || count($services_ids)) {
  // Chargement des séjours à afficher
  if ($service_id && !in_array($service_id, $services_ids)) {
    $services_ids[] = $service_id;
  }

  foreach ($services_ids as $_service_id) {
    if ($_service_id == "NP") {
      // Liste des patients à placer

      // Admissions de la veille
      $dayBefore = CMbDT::date("-1 days", $date);
      $where     = array(
        "entree_prevue" => "BETWEEN '$dayBefore 00:00:00' AND '$date 00:00:00'",
        "type"          => $type_admission ? " = '$type_admission'" : "!= 'exte'",
        "annule"        => "= '0'"
      );

      $groupSejourNonAffectes["veille"] = loadSejourNonAffectes($where, null, $praticien_id);

      // Admissions du matin
      $where = array(
        "entree_prevue" => "BETWEEN '$date 00:00:00' AND '$date " . CMbDT::time("-1 second", $heureLimit) . "'",
        "type"          => $type_admission ? " = '$type_admission'" : "!= 'exte'",
        "annule"        => "= '0'"
      );

      $groupSejourNonAffectes["matin"] = loadSejourNonAffectes($where, null, $praticien_id);

      // Admissions du soir
      $where = array(
        "entree_prevue" => "BETWEEN '$date $heureLimit' AND '$date 23:59:59'",
        "type"          => $type_admission ? " = '$type_admission'" : "!= 'exte'",
        "annule"        => "= '0'"
      );

      $groupSejourNonAffectes["soir"] = loadSejourNonAffectes($where, null, $praticien_id);

      // Admissions antérieures
      $twoDaysBefore = CMbDT::date("-2 days", $date);
      $where         = array(
        "entree_prevue" => "<= '$twoDaysBefore 23:59:59'",
        "sortie_prevue" => ">= '$date 00:00:00'",
        //"'$twoDaysBefore' BETWEEN entree_prevue AND sortie_prevue",
        "annule"        => "= '0'",
        "type"          => $type_admission ? " = '$type_admission'" : "!= 'exte'"
      );

      $groupSejourNonAffectes["avant"] = loadSejourNonAffectes($where, null, $praticien_id);

      foreach ($groupSejourNonAffectes as $moment => $_groupSejourNonAffectes) {
        CMbArray::pluckSort($groupSejourNonAffectes[$moment], SORT_ASC, "entree_prevue");
      }

      foreach ($groupSejourNonAffectes as $sejours_by_moment) {
        foreach ($sejours_by_moment as $_sejour) {
          if (($_is_praticien || $_is_anesth) && (($_sejour->praticien_id == $userCourant->user_id) || $_is_anesth)) {
            $tab_sejour[$_sejour->_id] = $_sejour;
          }

          $ids_all_sejours[$_sejour->_id] = $_sejour;
        }
      }
      $service = new CService();
    }
    else {
      $service = new CService();
      $service->load($_service_id);
      loadServiceComplet($service, $date, $mode, $praticien_id, $type_admission);
      loadAffectationsPermissions($service, $date, $mode);
    }

    if ($service->_id) {
      foreach ($service->_ref_chambres as $_chambre) {
        foreach ($_chambre->_ref_lits as $_lits) {
          CStoredObject::massLoadFwdRef($_lits->_ref_affectations, "parent_affectation_id");
          foreach ($_lits->_ref_affectations as $_affectation) {
            $_affectation->loadRefParentAffectation();
            if ($_affectation->_ref_sejour->annule) {
              unset($_lits->_ref_affectations[$_affectation->_id]);
              continue;
            }
            if ($_is_anesth || ($_is_praticien && $_affectation->_ref_sejour->praticien_id == $userCourant->user_id)) {
              $tab_sejour[$_affectation->_ref_sejour->_id] = $_affectation->_ref_sejour;
            }
            $sejour                        = $_affectation->_ref_sejour;
            $ids_all_sejours[$sejour->_id] = $sejour;
            loadSejour($sejour, $_affectation, $count_my_patient, $userCourant, $date, $mode, $alert_handler);
          }
        }
      }

      $service->loadRefsAffectationsCouloir($date, $mode, true);

      $_sejours  = CStoredObject::massLoadFwdRef($service->_ref_affectations_couloir, "sejour_id");
      $_patients = CStoredObject::massLoadFwdRef($_sejours, "patient_id");
      CStoredObject::massLoadBackRefs($_patients, "bmr_bhre");

      foreach ($service->_ref_affectations_couloir as $_affectation) {
        $_affectation->loadRefSejour();
        if ($_affectation->_ref_sejour->annule || ($praticien_id && $_affectation->_ref_sejour->praticien_id != $praticien_id)) {
          unset($service->_ref_affectations_couloir[$_affectation->_id]);
          continue;
        }
        if ($_is_anesth || ($_is_praticien && $_affectation->_ref_sejour->praticien_id == $userCourant->user_id)) {
          $tab_sejour[$_affectation->_ref_sejour->_id] = $_affectation->_ref_sejour;
        }
        $sejour                        = $_affectation->_ref_sejour;
        $ids_all_sejours[$sejour->_id] = $sejour;
        loadSejour($sejour, $_affectation, $count_my_patient, $userCourant, $date, $mode, $alert_handler);
      }
    }

    $sejoursParService[$service->_id] = $service;
  }
}

$see_my_patient = $count_my_patient && $my_patient && ($userCourant->isSageFemme() || $userCourant->isAideSoignant() || $userCourant->isInfirmiere() || $userCourant->isKine() || $userCourant->isPraticien());

$sejours_show_anesth_interv = array();
foreach ($sejoursParService as $key => $_service) {
  if ($key != "NP") {
    foreach ($_service->_ref_chambres as $key_chambre => $_chambre) {
      foreach ($_chambre->_ref_lits as $key_lit => $_lit) {
        foreach ($_lit->_ref_affectations as $key_affectation => $_affectation) {
          $_sejour = $_affectation->loadRefSejour();
          if (!count($_sejour->_ref_users_sejour) && $see_my_patient) {
            unset($_lit->_ref_affectations[$key_affectation]);
          }
          else {
            $sejours_show_anesth_interv[$_sejour->_id] = $_sejour;
          }
        }
        if (!count($_lit->_ref_affectations) && $see_my_patient) {
          unset($_chambre->_ref_lits[$key_lit]);
        }
      }
    }
  }
  else {
    foreach ($_service as $_sejour) {
      if (!count($_sejour->_ref_users_sejour) && $see_my_patient) {
        unset($_lit->_ref_affectations[$key_affectation]);
      }
      else {
        $sejours_show_anesth_interv[$_sejour->_id] = $_sejour;
      }
    }
  }
}

$operations = CStoredObject::massLoadBackRefs($sejours_show_anesth_interv, "operations", "date ASC");
if (is_array($operations) && count($operations)) {
  $plages_ops = CStoredObject::massLoadFwdRef($operations, "plageop_id");
  CStoredObject::massLoadFwdRef($operations, "chir_id");
  CStoredObject::massLoadFwdRef($operations, "anesth_id");
  CStoredObject::massLoadFwdRef($plages_ops, "anesth_id");
}

foreach ($sejours_show_anesth_interv as $other_sejour) {
  $anesths = array();
  $chirs   = array();
  foreach ($other_sejour->loadRefsOperations() as $_interv) {
    if (!isset($chirs[$_interv->chir_id])) {
      $_interv->loadRefPraticien()->loadRefFunction();
      $chirs[$_interv->chir_id] = 1;
    }
    $_interv->loadRefAnesth()->loadRefFunction();
    if (!isset($anesths[$_interv->_ref_anesth->_id])) {
      $anesths[$_interv->_ref_anesth->_id] = 1;
    }
    else {
      $_interv->_ref_anesth = null;
    }
  }
}

if ($prescription_active && $alert_handler) {
  CPrescription::massCountAlertsNotHandled(CMbArray::pluck($all_sejours, "_ref_prescriptions", "sejour"));
  CPrescription::massCountAlertsNotHandled(CMbArray::pluck($all_sejours, "_ref_prescriptions", "sejour"), "high");
}

if ($prescription_active) {
  CPrescription::massLoadLinesElementImportant(
    array_combine(
      CMbArray::pluck($all_sejours, "_ref_prescriptions", "sejour", "_id"),
      CMbArray::pluck($all_sejours, "_ref_prescriptions", "sejour")
    )
  );
}

if (!$count_my_patient && $my_patient) {
  $my_patient = 0;
}

// Chargement des visites pour les séjours courants
$visites = CSejour::countVisitesUser($tab_sejour, $date, $userCourant);

$can_view_dossier_medical =
  CModule::getCanDo('dPcabinet')->edit ||
  CModule::getCanDo('dPbloc')->edit ||
  CModule::getCanDo('dPplanningOp')->edit ||
  $userCourant->isFromType(array("Infirmière"));

/**
 * Mettre en cache les lits
 *
 * @param CAffectation $affectation Affectation
 *
 * @return void
 */
function cacheLit(CAffectation $affectation) {
  // Cache des lits
  $lit_id = $affectation->lit_id;
  static $lits = array();
  if (!array_key_exists($lit_id, $lits)) {
    $lit = new CLit();
    $lit->load($lit_id);
    $lits[$lit_id] = $lit;
  }

  $lit                                       = $lits[$lit_id];
  $lit->_ref_affectations[$affectation->_id] = $affectation;

  // Cache des chambres
  $chambre_id = $lit->chambre_id;
  static $chambres = array();
  if (!array_key_exists($chambre_id, $chambres)) {
    $chambre = new CChambre();
    $chambre->load($chambre_id);
    $chambres[$chambre_id] = $chambre;
  }

  $chambre                     = $chambres[$chambre_id];
  $chambre->_ref_lits[$lit_id] = $lit;

  // Cache de services
  global $sejoursParService;
  $service_id = $chambre->service_id ? $chambre->service_id : $affectation->service_id;
  if (!array_key_exists($service_id, $sejoursParService)) {
    $service = new CService();
    $service->load($service_id);
    $sejoursParService[$service_id] = $service;
  }

  $service                             = $sejoursParService[$service_id];
  $service->_ref_chambres[$chambre_id] = $chambre;
}

/**
 * Divers chargement lié au séjour
 *
 * @param CSejour      $sejour           Séjour
 * @param CAffectation $_affectation     Affectation
 * @param int          $count_my_patient Nombre de séjours
 * @param CMediusers   $userCourant      Utilisateur courant
 * @param string       $date             Date des séjours chargés
 * @param string       $mode             Type de vue
 * @param bool         $alert_handler    Gestion manuelle des alertes
 *
 * return void
 */
function loadSejour(&$sejour, &$_affectation, &$count_my_patient, $userCourant, $date, $mode, $alert_handler) {
  global $all_sejours;
  if ($sejour->_id) {
    $all_sejours[$sejour->_id] = $sejour;
  }
  $sejour->loadRefPraticien();
  $sejour->loadRefPatient()->updateBMRBHReStatus();
  $sejour->loadRefsPrescriptions();
  $sejour->loadLastAutorisationPermission();
  $sejour->_ref_praticien->loadRefFunction();
  $sejour->countAlertsNotHandled("medium", "observation");
  $count_my_patient += count($sejour->loadRefsUserSejour($userCourant, $date, $mode));
  $_affectation->loadRefsAffectations();
  if ($_affectation->_ref_sejour->_ref_prescriptions) {
    if (array_key_exists('sejour', $_affectation->_ref_sejour->_ref_prescriptions)) {
      $prescription_sejour = $_affectation->_ref_sejour->_ref_prescriptions["sejour"];
      $prescription_sejour->countNoValideLines();
      CPrescription::massAlertConfirmation($prescription_sejour);

      if (!$alert_handler) {
        $prescription_sejour->countFastRecentModif();
        $prescription_sejour->countFastUrgence();
      }
    }
  }
}

// liste des cabinets
$functions = array();
$function  = new CFunctions();
$functions = $function->loadSpecialites();

// liste des spécialités médicale
$listDisciplines = new CDiscipline();
$listDisciplines = $listDisciplines->loadUsedDisciplines();

// Création du template
$smarty = new CSmartyDP();
$smarty->assign("default_service_id", $default_service_id);
$smarty->assign("_is_praticien", $_is_praticien);
$smarty->assign("anesthesistes", $anesthesistes);
$smarty->assign("praticiens", $praticiens);
$smarty->assign("praticien_id", $praticien_id);
$smarty->assign("function_id", $function_id);
$smarty->assign("discipline_id", $discipline_id);
$smarty->assign("object", $sejour);
$smarty->assign("mode", $mode);
$smarty->assign("totalLits", $totalLits);
$smarty->assign("date", $date);
$smarty->assign("isImedsInstalled", (CModule::getActive("dPImeds") && CImeds::getTagCIDC($group)));
$smarty->assign("can_view_dossier_medical", $can_view_dossier_medical);
$smarty->assign("demain", CMbDT::date("+ 1 day", $date));
$smarty->assign("services", $services);
$smarty->assign("sejoursParService", $sejoursParService);
$smarty->assign("service_id", $service_id);
$smarty->assign("groupSejourNonAffectes", $groupSejourNonAffectes);
$smarty->assign("visites", $visites);
$smarty->assign("my_patient", $my_patient);
$smarty->assign("count_my_patient", $count_my_patient);
$smarty->assign("services_ids", $services_ids);
$smarty->assign("listDisciplines", $listDisciplines);
$smarty->assign("listFuncs", $functions);
$smarty->assign("current_m", ($current_m) ?: null);
$smarty->assign("order_col_sejour", $order_col_sejour);
$smarty->assign("order_way_sejour", $order_way_sejour);
$smarty->assign("type_admission", $type_admission);

$smarty->display("vw_idx_sejour");
