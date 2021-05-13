<?php
/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbDT;
use Ox\Core\CMbString;
use Ox\Core\Module\CModule;
use Ox\Core\CSmartyDP;
use Ox\Core\CSoundex2;
use Ox\Core\CStoredObject;
use Ox\Core\CValue;
use Ox\Core\CView;
use Ox\Mediboard\Fse\CFseFactory;
use Ox\Mediboard\Mediusers\CFunctions;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\PlanningOp\CSejour;

CCanDo::checkRead();

// Chargement du patient sélectionné
$patient_id = CView::get("patient_id", 'ref class|CPatient', true);
$patient    = new CPatient();

if ($new = CView::get("new", 'bool default|0')) {
  $patient->load();
  CView::setSession("patient_id", null);
  CView::setSession("selClass", null);
  CView::setSession("selKey", null);
}
else {
  $patient->load($patient_id);
}

$pays       = CAppUI::conf("ref_pays");
$field_card = $pays == 2 ? "_avs" : "_matricule";

// Récuperation des patients recherchés
$patient_nom             = trim(CView::get("nom", 'str', true));
$patient_prenom          = trim(CView::get("prenom", 'str', true));
$patient_ville           = CView::get("ville", 'str');
$patient_cp              = CView::get("cp", 'str');
$patient_day             = CView::get("Date_Day", 'str', true);
$patient_month           = CView::get("Date_Month", 'str', true);
$patient_year            = CView::get("Date_Year", 'str', true);
$patient_naissance       = null;
$patient_ipp             = CView::get("patient_ipp", 'str');
$patient_nda             = trim(CView::get("patient_nda", 'str'));
${"patient_$field_card"} = CView::get($field_card, "str");
$useVitale               = CView::get(
  "useVitale",
  'bool default|' . (CModule::getActive("fse") && CAppUI::pref('LogicielLectureVitale') !== 'none' ? 1 : 0)
);
$parturiente             = CView::get("parturiente", "bool default|0");
$prat_id                 = CView::get("prat_id", 'ref class|CMediusers');
$patient_sexe            = CView::get("sexe", 'enum list|m|f');
$useCovercard            = CView::get(
  "usecovercard",
  'bool default|' . (CModule::getActive("fse") && CModule::getActive("covercard") ? 1 : 0)
);
$see_link_prat           = CView::get("see_link_prat", 'bool default|0');

$start = (int)CView::get('start', 'num default|0');

$covercard = CView::get("covercard", "str");
$mode      = CView::get("mode", 'enum list|search|board|selector default|search');

$paginate = CAppUI::gconf('dPpatients CPatient search_paging');

$patient_nom_search    = null;
$patient_prenom_search = null;

$mediuser    = CMediusers::get();

$showCount = 30;
$total     = 0;

$curr_group_id = $mediuser->loadRefFunction()->group_id;

$patVitale = new CPatient();

$limit_char_search = null;

if ($patient_ipp || $patient_nda) {
  // Initialisation dans le cas d'une recherche par IPP ou NDA
  $patients             = array();
  $patientsLimited      = array();
  $patientsSoundex      = array();
  $patientsCount        = 0;
  $patientsSoundexCount = 0;

  $patient = new CPatient();

  $patient->getByIPPNDA($patient_ipp, $patient_nda);

  if ($patient->_id) {
    CView::setSession("patient_id", $patient->_id);
    $patients[$patient->_id] = $patient;
  }

  CView::checkin();
}
else {
  $use_function_distinct = CAppUI::isCabinet() && !$mediuser->isAdmin();
  $use_group_distinct = CAppUI::isGroup() && !$mediuser->isAdmin();

  $function_id = $use_function_distinct ? CFunctions::getCurrent()->_id : null;
  // Recheche par traits classiques
  if ($useVitale && CAppUI::pref('LogicielLectureVitale') === 'none' && CModule::getActive("fse")) {
    // Champs vitale
    $cv = CFseFactory::createCV();

    if ($cv) {
      $cv->getPropertiesFromVitale($patVitale);
      $patVitale->updateFormFields();
      $patient_nom    = $patVitale->nom;
      $patient_prenom = $patVitale->prenom;
      CView::setSession("nom", $patVitale->nom);
      CView::setSession("prenom", $patVitale->prenom);
      $cv->loadFromIdVitale($patVitale);
    }
  }

  /* The checkin is made after some data has been put on session, and before the SQL query */
  CView::checkin();

  $where           = array();
  $whereLimited    = array();
  $whereSoundex    = array();
  $ljoin           = array();
  $soundexObj      = new CSoundex2();
  $group_by        = null;
  $lenSearchConfig = false; // Not enough char in string to perform the limited search

  // Because of \w and \W don't match characters with diacritics
  $patient_nom_search    = CMbString::removeDiacritics(trim($patient_nom));
  $patient_prenom_search = CMbString::removeDiacritics(trim($patient_prenom));

  $patient_prenom_search = preg_replace('/[^\w%_]+/', '_', $patient_prenom_search);
  $patient_nom_search    = preg_replace('/[^\w%_]+/', '_', $patient_nom_search);

  // Limitation de la recherche par config :
  $patient_nom_search_limited    = $patient_nom_search;
  $patient_prenom_search_limited = $patient_prenom_search;

  if ($limit_char_search = CAppUI::gconf("dPpatients CPatient limit_char_search")) {
    // Not enough characters
    if (strlen($patient_prenom_search) < $limit_char_search && strlen($patient_nom_search) < $limit_char_search) {
      $lenSearchConfig = true;
    }

    $patient_nom_search_limited    = substr($patient_nom_search, 0, $limit_char_search);
    $patient_prenom_search_limited = substr($patient_prenom_search, 0, $limit_char_search);
  }

  if ($patient_nom_search) {
    $patient_nom_soundex = $soundexObj->build($patient_nom_search);
    $patient_nom_ext     = str_replace(" ", "%", $patient_nom);

    $where[]        = "`nom` LIKE '$patient_nom_search%' OR `nom_jeune_fille` LIKE '$patient_nom_search%'"
      . ($patient_nom_ext === "" ? "" : "OR `nom` LIKE '$patient_nom_ext%' OR `nom_jeune_fille` LIKE '$patient_nom_ext'");
    $whereLimited[] = "`nom` LIKE '$patient_nom_search_limited%' OR `nom_jeune_fille` LIKE '$patient_nom_search_limited%'";
    $whereSoundex[] = "`nom_soundex2` LIKE '$patient_nom_soundex%' OR `nomjf_soundex2` LIKE '$patient_nom_soundex%'";
  }

  if ($patient_prenom_search) {
    $patient_prenom_soundex = $soundexObj->build($patient_prenom_search);

    $where[]                         = "prenom LIKE '$patient_prenom_search%'";
    $whereLimited["prenom"]          = "LIKE '$patient_prenom_search_limited%'";
    $whereSoundex["prenom_soundex2"] = "LIKE '$patient_prenom_soundex%'";
  }

  if ($patient_year || $patient_month || $patient_day) {
    $patient_naissance =
      CValue::first($patient_year, "%") . "-" .
      CValue::first($patient_month, "%") . "-" .
      CValue::first($patient_day, "%");

    $where["naissance"]        = "LIKE '$patient_naissance'";
    $whereSoundex["naissance"] = "LIKE '$patient_naissance'";
    $whereLimited["naissance"] = "LIKE '$patient_naissance'";
  }

  // Ajout des clauses where concernant les parturientes seulement s"il y a déjà des filtres renseignés
  if ($parturiente && count($where)) {
    $_expr = "naissance <= '" . CMbDT::date("-12 years", CMbDT::date()) . "'";

    $where["sexe"]        = "= 'f'";
    $whereSoundex["sexe"] = "= 'f'";
    $whereLimited["sexe"] = "= 'f'";

    $where[]        = $_expr;
    $whereSoundex[] = $_expr;
    $whereLimited[] = $_expr;
  }

  if ($patient_ville) {
    $where["ville"]        = "LIKE '$patient_ville%'";
    $whereSoundex["ville"] = "LIKE '$patient_ville%'";
    $whereLimited["ville"] = "LIKE '$patient_ville%'";
  }

  if ($patient_cp) {
    $where["cp"]        = "LIKE '$patient_cp%'";
    $whereSoundex["cp"] = "LIKE '$patient_cp%'";
    $whereLimited["cp"] = "LIKE '$patient_cp%'";
  }

  if ($prat_id && !$see_link_prat) {
    $ljoin["consultation"] = "`consultation`.`patient_id` = `patients`.`patient_id`";
    $ljoin["plageconsult"] = "`plageconsult`.`plageconsult_id` = `consultation`.`plageconsult_id`";
    $ljoin["sejour"]       = "`sejour`.`patient_id` = `patients`.`patient_id`";

    // Leave it here because of if ($where) testing...
    $where['plageconsult.chir_id']        = "= '$prat_id' OR sejour.praticien_id = '$prat_id'";
    $whereLimited['plageconsult.chir_id'] = "= '$prat_id' OR sejour.praticien_id = '$prat_id'";
    $whereSoundex['plageconsult.chir_id'] = "= '$prat_id' OR sejour.praticien_id = '$prat_id'";

    $group_by = "patient_id";
  }

  if ($patient_sexe && $where) {
    $where["sexe"]        = "= '$patient_sexe'";
    $whereSoundex["sexe"] = "= '$patient_sexe'";
    $whereLimited["sexe"] = "= '$patient_sexe'";
  }

  if (${"patient_$field_card"}){
    //On retire le "_" devant le field_card pour rechercher le champ correspondant en base : "matricule" ou "avs"
    $where[substr($field_card, 1)]        = "LIKE '${"patient_$field_card"}%'";
  }

  /** @var CPatient[] $patients */
  $patients = array();

  /** @var CPatient[] $patientsSoundex */
  $patientsSoundex = array();

  /** @var CPatient[] $patientsLimited */
  $patientsLimited = array();

  if ($patient_nom_search) {
    $order = "LOCATE('$patient_nom_search', nom) DESC, nom, prenom, naissance";
  }
  else {
    $order = "nom, prenom, naissance";
  }

  $pat = new CPatient();

  // Chargement des patients
  if ($where) {
    // Séparation des patients par fonction
    if ($use_function_distinct) {
      $where["function_id"] = "= '$function_id'";
    }
    elseif ($use_group_distinct) {
      $where["patients.group_id"] = "= '$curr_group_id'";
    }

    // Séparation en deux requêtes
    if ($prat_id && !$see_link_prat) {
      $patients_consults = array();

      if (!$patient_nda) {
        // Consultations
        $ljoin_consults = $ljoin;
        $where_consults = $where;

        unset($ljoin_consults['sejour']);
        $where_consults['plageconsult.chir_id'] = "= '$prat_id'";

        $patients_consults = $pat->loadList($where_consults, $order, $showCount, $group_by, $ljoin_consults, null, null, false);
      }

      // Séjours
      $ljoin_sejours = $ljoin;
      $where_sejours = $where;

      unset($ljoin_sejours['consultation']);
      unset($ljoin_sejours['plageconsult']);
      unset($where_sejours['plageconsult.chir_id']);
      $where_sejours['sejour.praticien_id'] = "= '$prat_id'";

      $patients_sejours = $pat->loadList($where_sejours, $order, $showCount, $group_by, $ljoin_sejours, null, null, false);
      $patients         = $patients_consults + $patients_sejours;
    }
    else {
      if ($paginate) {
        $total = $pat->countList($where, $group_by, $ljoin);
        $limit = "{$start}, {$showCount}";

        $patients = $pat->loadList($where, $order, $limit, $group_by, $ljoin, null, null, false);
      }
      else {
        $patients = $pat->loadList($where, $order, $showCount, $group_by, $ljoin, null, null, false);
      }
    }
  }

  // Par soundex
  if ($whereSoundex && (!$paginate || ($paginate && !$start))) {
    // Séparation des patients par fonction
    if ($use_function_distinct) {
      $whereSoundex["function_id"] = "= '$function_id'";
    }
    elseif ($use_group_distinct) {
      $whereSoundex["patients.group_id"] = "= '$curr_group_id'";
    }

    if ($prat_id && !$see_link_prat) {
      $patients_consults = array();

      if (!$patient_nda) {
        // Consultations
        $ljoin_consults = $ljoin;
        $where_consults = $whereSoundex;

        unset($ljoin_consults['sejour']);
        $where_consults['plageconsult.chir_id'] = "= '$prat_id'";

        $patients_consults = $pat->loadList($where_consults, $order, $showCount, $group_by, $ljoin_consults, null, null, false);
      }

      // Séjours
      $ljoin_sejours = $ljoin;
      $where_sejours = $whereSoundex;

      unset($ljoin_sejours['consultation']);
      unset($ljoin_sejours['plageconsult']);
      unset($where_sejours['plageconsult.chir_id']);
      $where_sejours['sejour.praticien_id'] = "= '$prat_id'";

      $patients_sejours = $pat->loadList($where_sejours, $order, $showCount, $group_by, $ljoin_sejours, null, null, false);
      $patientsSoundex  = $patients_consults + $patients_sejours;
    }
    else {
      $patientsSoundex = $pat->loadList($whereSoundex, $order, $showCount, $group_by, $ljoin, null, null, false);
    }

    $patientsSoundex = array_diff_key($patientsSoundex, $patients);
  }

  // Par recherche limitée
  if ($whereLimited && $limit_char_search && !$lenSearchConfig && (!$paginate || ($paginate && !$start))) {
    // Séparation des patients par fonction
    if ($use_function_distinct) {
      $whereLimited["function_id"] = "= '$function_id'";
    }
    elseif ($use_group_distinct) {
      $whereLimited["patients.group_id"] = "= '$curr_group_id'";
    }

    if ($prat_id && !$see_link_prat) {
      $patients_consults = array();

      if (!$patient_nda) {
        // Consultations
        $ljoin_consults = $ljoin;
        $where_consults = $whereLimited;

        unset($ljoin_consults['sejour']);
        $where_consults['plageconsult.chir_id'] = "= '$prat_id'";

        $patients_consults = $pat->loadList($where_consults, $order, $showCount, $group_by, $ljoin_consults, null, null, false);
      }

      // Séjours
      $ljoin_sejours = $ljoin;
      $where_sejours = $whereLimited;

      unset($ljoin_sejours['consultation']);
      unset($ljoin_sejours['plageconsult']);
      unset($where_sejours['plageconsult.chir_id']);
      $where_sejours['sejour.praticien_id'] = "= '$prat_id'";

      $patients_sejours = $pat->loadList($where_sejours, $order, $showCount, $group_by, $ljoin_sejours, null, null, false);
      $patientsLimited  = $patients_consults + $patients_sejours;
    }
    else {
      $patientsLimited = $pat->loadList($whereLimited, $order, $showCount, $group_by, $ljoin, null, null, false);
    }

    $patientsLimited = array_diff_key($patientsLimited, $patients);
  }

  // Sélection du premier de la liste si aucun n'est sélectionné
  if (!$patient->_id && count($patients) === 1) {
    $patient = reset($patients);
  }

  // Patient vitale associé trouvé : prioritaire
  if ($patVitale->_id) {
    $patient = $patVitale;

    // Au cas où il n'aurait pas été trouvé grâce aux champs
    $patients[$patient->_id] = $patient;
  }
}

/** @var CPatient[] $all_patients */
// Ne pas utiliser array_merge, les clés sont perdues
$all_patients = $patients + $patientsSoundex + $patientsLimited;

// Vérification du droit de lecture
foreach ($all_patients as $key => $_patient) {
  if (!$_patient->canDo()->read) {
    unset($all_patients[$key]);
    unset($patients[$key]);
    unset($patientsSoundex[$key]);
    unset($patientsLimited[$key]);
  }
}

CPatient::massLoadIPP($all_patients);

CStoredObject::massLoadBackRefs($all_patients, "correspondants");
CStoredObject::massLoadBackRefs($all_patients, "notes");
CStoredObject::massLoadBackRefs($all_patients, "bmr_bhre");

foreach ($all_patients as $_patient) {
  $_patient->loadRefsNotes();
  $_patient->updateBMRBHReStatus();
  if ($see_link_prat) {
    $_patient->countConsultationPrat($prat_id);
  }

  if ($mode === "selector") {
    $today = CMbDT::date();

    // Chargement des consultations du jour 
    $where     = array(
      "plageconsult.date" => "= '$today'",
    );
    $_consults = $_patient->loadRefsConsultations($where);
    foreach ($_consults as $_consult) {
      $_consult->loadRefPraticien()->loadRefFunction();
    }

    // Chargement des admissions du jour
    $where    = array(
      "entree" => "LIKE '$today __:__:__'",
    );
    $_sejours = $_patient->loadRefsSejours($where);
    foreach ($_sejours as $_sejour) {
      $_sejour->loadRefPraticien()->loadRefFunction();
    }
  }
}

// Si la configuration "Limiter la recherche" est activé (n'afficher qu'un résultat pour éviter les doublons)
if ($limit_char_search) {
  foreach ($patientsLimited as $_pat_limited) {
    foreach ($patientsSoundex as $_pat_soundex) {
      if ($_pat_limited->_id == $_pat_soundex->_id) {
        unset($patientsSoundex[$_pat_limited->_id]);
      }
    }
  }
}

$sejours_avenir  = CSejour::checkIncomingSejours($all_patients);
$sejours_encours = CSejour::checkIncomingSejours($all_patients, true);

$smarty = new CSmartyDP();
$smarty->assign("canPatients", CModule::getCanDo("dPpatients"));
$smarty->assign("canAdmissions", CModule::getCanDo("dPadmissions"));
$smarty->assign("canPlanningOp", CModule::getCanDo("dPplanningOp"));
$smarty->assign("canCabinet", CModule::getCanDo("dPcabinet"));

$smarty->assign("nom", $patient_nom);
$smarty->assign("prenom", $patient_prenom);
$smarty->assign("naissance", $patient_naissance);
$smarty->assign("ville", $patient_ville);
$smarty->assign("cp", $patient_cp);
$smarty->assign("nom_search", $patient_nom_search);
$smarty->assign("prenom_search", $patient_prenom_search);
$smarty->assign("covercard", $covercard);
$smarty->assign("sexe", $patient_sexe);
$smarty->assign("prat_id", $prat_id);

$smarty->assign("useVitale", $useVitale);
$smarty->assign("useCoverCard", $useCovercard);
$smarty->assign("patVitale", $patVitale);
$smarty->assign("patients", $patients);
$smarty->assign("patientsLimited", $patientsLimited);
$smarty->assign("patientsSoundex", $patientsSoundex);

$smarty->assign("patient", $patient);
$smarty->assign("mode", $mode);
$smarty->assign("patient_ipp", $patient_ipp);
$smarty->assign("patient_nda", $patient_nda);
$smarty->assign("sejours_avenir", $sejours_avenir);
$smarty->assign("sejours_encours", $sejours_encours);

if (!$prat_id && $paginate) {
  $smarty->assign('step', $showCount);
  $smarty->assign('start', $start);
  $smarty->assign('total', $total);
}

$smarty->display("inc_search_patients.tpl");
