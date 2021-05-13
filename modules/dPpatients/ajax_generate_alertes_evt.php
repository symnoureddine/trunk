<?php
/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CMbDT;
use Ox\Core\CSQLDataSource;
use Ox\Core\CStoredObject;
use Ox\Core\CView;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Patients\CDossierMedical;
use Ox\Mediboard\Patients\CEvenementAlerteUser;
use Ox\Mediboard\Patients\CEvenementPatient;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\Patients\CRegleAlertePatient;

$function_id = CView::get("function_id", "ref class|CFunctions");

CView::checkin();

$user = CMediusers::get();
$group_id = CGroups::loadCurrent()->_id;

$functions = $function_id ? array() : $user->loadFonctions(PERM_EDIT, $group_id);
$functions_ids = array_keys($functions);

$now = CMbDT::date();

// Récupération des règles
$where          = array();
$where["actif"] = " = '1'";
$where[] = " group_id = '$group_id' OR function_id ".CSQLDataSource::prepareIn($functions_ids, $function_id);
$regle          = new CRegleAlertePatient();
$regles         = $regle->loadList($where);

$users_alerte_regle = CStoredObject::massLoadBackRefs($regles, "users_alert_evt");
CStoredObject::massLoadFwdRef($users_alerte_regle, "user_id");

$patient = new CPatient();
$dossier_medical = new CDossierMedical();

$where_regle = array(
  "patients.function_id" => CSQLDataSource::prepareIn($functions_ids, $function_id)
);

$ljoin_regle = array(
  "dossier_medical" => "dossier_medical.object_class = 'CPatient' AND dossier_medical.object_id = patients.patient_id"
);

foreach ($regles as $_regle) {
  $_regle->loadRefsUsers();
  if ($_regle->function_id) {
    $where = array("patients.function_id" => " = '$_regle->function_id'");
  }
  else {
    $where = $where_regle;
  }

  $ljoin = $ljoin_regle;

  // Sexe
  if ($_regle->sexe) {
    $where["sexe"] = " = '$_regle->sexe'";
  }

  // Age
  if ($_regle->age_valeur) {
    $date_naissance              = CMbDT::transform("-$_regle->age_valeur years", $now, "%Y-%m-%d");
    $operateur                   = $_regle->age_operateur == "sup" ? "<" : ">";
    $where["patients.naissance"] = " $operateur '$date_naissance'";
  }

  // CIM
  if ($_regle->diagnostics) {
    $where["dossier_medical.dossier_medical_id"] = "IS NOT NULL";
  }

  // Programme clinique
  if ($_regle->programme_clinique_id) {
    $ljoin["inclusion_programme"]                       = "inclusion_programme.patient_id = patients.patient_id";
    $where["inclusion_programme.programme_clinique_id"] = " = '$_regle->programme_clinique_id'";
  }

  $patients = $patient->loadIds($where, null, null, "patients.patient_id", $ljoin);

  $date_min = CMbDT::date("-" . ($_regle->periode_refractaire + $_regle->nb_anticipation) . " days");

  foreach ($patients as $_patient_id) {
    $dossier_medical_id = CDossierMedical::dossierMedicalId($_patient_id, "CPatient");

    // Il ne faut pas prendre en compte les patients ayant des évenements avec la règle durant la période réfractaire
    $where                       = array();
    $where["dossier_medical_id"] = " = '$dossier_medical_id'";
    $where["regle_id"]           = " = '$_regle->_id'";
    $where["date"]               = " >= '$date_min'";
    $evt                         = new CEvenementPatient();
    $evt->loadObject($where);

    if ($evt->_id) {
      continue;
    }

    // L'intégralité des codes cim paramétré doivent être présent dans le dossier médical du patient pour le prendre en compte
    if ($_regle->diagnostics) {
      $dossier_medical->load($dossier_medical_id);
      $all_cim_in_dm = true;

      foreach ($_regle->_ext_diagnostics as $code_cim => $_cim_regle) {
        if (!isset($dossier_medical->_ext_codes_cim[$code_cim])) {
          $all_cim_in_dm = false;
        }
      }
      if (!$all_cim_in_dm) {
        continue;
      }
    }

    // Création de l'évenement pateint
    $evt                     = new CEvenementPatient();
    $evt->libelle            = $_regle->name;
    $evt->dossier_medical_id = $dossier_medical_id;
    $evt->date               = CMbDT::date("+$_regle->nb_anticipation days");
    $evt->alerter            = '1';
    $evt->regle_id           = $_regle->_id;
    $evt->store();


    if ($evt->_id) {

      // Ajout de la liste des utilisateurs à alerter
      foreach ($_regle->_ref_users as $_user) {
        $alerte_user               = new CEvenementAlerteUser();
        $alerte_user->object_id    = $evt->_id;
        $alerte_user->object_class = $evt->_class;
        $alerte_user->user_id      = $_user->_id;
        $alerte_user->store();
      }
    }
  }
}
