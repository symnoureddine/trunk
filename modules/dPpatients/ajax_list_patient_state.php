<?php
/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CMbArray;
use Ox\Core\CSmartyDP;
use Ox\Core\CSQLDataSource;
use Ox\Core\CValue;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\Patients\CPatientLink;
use Ox\Mediboard\Patients\CPatientState;

if (!CAppUI::pref("allowed_modify_identity_status")) {
  CAppUI::accessDenied();
}

$state          = CValue::get("state");
$page           = (int)CValue::get("page", 0);
$date_min       = CValue::session("patient_state_date_min");
$date_max       = CValue::session("patient_state_date_max");
$patients       = array();
$patients_state = array();
$where          = array();
$leftjoin       = null;
$patient        = new CPatient();

if ($date_min) {
  $where[]            = "entree >= '$date_min'";
  $leftjoin["sejour"] = "patients.patient_id = sejour.patient_id";
}

if ($date_max) {
  $where[]            = "entree <= '$date_max'";
  $leftjoin["sejour"] = "patients.patient_id = sejour.patient_id";
}

$patients_count = CPatientState::getAllNumberPatient($date_min, $date_max);

if ($patients_count[$state] > 0) {
  /** @var CPatient[] $patients */
  $where["status"] = " = '$state'";

  if ($state != "vali") {
    $where["vip"] = "= '0'";
  }

  if ($state == "cach") {
    $where["vip"]    = "= '1'";
    $where["status"] = "!= 'VALI'";
  }

  if ($state == "dpot") {
    unset($where["status"]);
    unset($where["vip"]);
    $leftjoin["sejour"] = "sejour.patient_id = patient_link.patient_id1";

    $patient_link  = new CPatientLink();
    $patient_links = $patient_link->loadList($where, null, "$page, 30", "patient_link.patient_link_id", $leftjoin);
    $patient_ids1  = CMbArray::pluck($patient_links, "patient_id1");
    $patient_ids2  = CMbArray::pluck($patient_links, "patient_id2");
    $where         = array("patient_id" => CSQLDataSource::prepareIn(array_merge($patient_ids1, $patient_ids2)));
    $patients      = $patient->loadList($where, null, null, "patients.patient_id");
  }
  else {
    $patients = $patient->loadList($where, "nom, prenom", "$page, 30", "patients.patient_id", $leftjoin);
  }

  CPatient::massLoadIPP($patients);
  /** @var CPatientState $patients_state */
  $patients_state = CPatient::massLoadBackRefs($patients, "patient_state", "datetime DESC");
  $mediusers      = CPatientState::massLoadFwdRef($patients_state, "mediuser_id");

  /** @var CPatientLink[] $link1 */
  $link1 = CPatient::massLoadBackRefs($patients, "patient_link1");
  /** @var CPatientLink[] $link2 */
  $link2         = CPatient::massLoadBackRefs($patients, "patient_link2");
  $patient_link1 = CPatientLink::massLoadFwdRef($link1, "patient_id2");
  $patient_link2 = CPatientLink::massLoadFwdRef($link2, "patient_id1");
  $patient_link  = $patient_link1 + $patient_link2;
  CPatient::massLoadIPP($patient_link);

  if ($link1) {
    foreach ($link1 as $_link1) {
      $_link1->_ref_patient_doubloon = $patient_link[$_link1->patient_id2];
    }
  }

  if ($link2) {
    foreach ($link2 as $_link2) {
      $_link2->_ref_patient_doubloon = $patient_link[$_link2->patient_id1];
    }
  }

  if ($patients_state) {
    foreach ($patients_state as $_patient_state) {
      /** @var CPatient $patient */
      $patient = $patients[$_patient_state->patient_id];

      $_patient_state->_ref_patient  = $patient;
      $_patient_state->_ref_mediuser = $mediusers[$_patient_state->mediuser_id];
    }
  }

  foreach ($patients as $_patient) {
    $_patient->_ref_last_patient_states = current($_patient->_back["patient_state"]);
    if ($state == "dpot") {
      $_patient->_ref_patient_links = array_merge($_patient->_back["patient_link1"], $_patient->_back["patient_link2"]);
    }
  }
}

$smarty = new CSmartyDP();
$smarty->assign("patients_count", $patients_count);
$smarty->assign("count", $patients_count[$state]);
$smarty->assign("patients", $patients);
$smarty->assign("state", $state);
$smarty->assign("page", $page);
$smarty->display("patient_state/inc_list_patient_state.tpl");