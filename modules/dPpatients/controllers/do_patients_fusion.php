<?php
/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CDoObjectAddEdit;
use Ox\Core\CValue;
use Ox\Mediboard\Patients\CPatient;


class CDoPatientMerge extends CDoObjectAddEdit {
  /**
   * @inheritdoc
   */
  function __construct() {
    parent::__construct("CPatient");

    if (CValue::post("dialog")) {
      $this->redirectDelete .= $this->redirect . "&a=pat_selector&dialog=1";
      $this->redirectStore  .= $this->redirect . "&a=vw_edit_patients&dialog=1";
    }
    else {
      $this->redirectDelete .= $this->redirect . "&tab=vw_edit_patients";
      $this->redirectStore  .= $this->redirect . "&tab=vw_edit_patients";
    }

    $this->redirectError = "";
  }

  /**
   * @inheritdoc
   */
  function doStore() {
    parent::doStore();

    $dialog     = CValue::post("dialog");
    $isNew      = !CValue::post("patient_id");
    $patient_id = $this->_obj->patient_id;

    if ($isNew) {
      $this->redirectStore .= "&patient_id=$patient_id&created=$patient_id";
    }
    elseif ($dialog) {
      $this->redirectStore .= "&name=" . $this->_obj->nom . "&firstname=" . $this->_obj->prenom;
    }
  }
}

$do = new CDoPatientMerge;

$patient1_id    = CValue::post("patient1_id");
$patient2_id    = CValue::post("patient2_id");
$base_object_id = CValue::post("_base_object_id");

// Erreur sur les ID du patient
$patient1 = new CPatient;
if (!$patient1->load($patient1_id)) {
  $do->errorRedirect("Patient 1 n'existe pas ou plus");
}

$patient2 = new CPatient;
if (!$patient2->load($patient2_id)) {
  $do->errorRedirect("Patient 2 n'existe pas ou plus");
}

if (intval(CValue::post("del"))) {
  $do->errorRedirect("Fusion en mode suppression impossible");
}

$patients = array($patient1, $patient2);

// Check merge
if ($msg = $do->_obj->checkMerge($patients)) {
  CAppUI::setMsg($msg, UI_MSG_ERROR);
  return;
}

if ($base_object_id) {
  $do->_obj->load($base_object_id);

  foreach ($patients as $key => $patient) {
    if ($base_object_id == $patient->_id) {
      unset($patients[$key]);
      unset($_POST["_merging"][$base_object_id]);
    }
  }
}

// Bind au nouveau patient
$do->doBind();

// Fusion effective
if ($msg = $do->_obj->merge($patients)) {
  $do->errorRedirect($msg);
}

$do->doRedirect();
