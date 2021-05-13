<?php
/**
 * @package Mediboard\Admissions
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbArray;
use Ox\Core\CStoredObject;
use Ox\Core\CView;
use Ox\Mediboard\PlanningOp\CSejour;

CApp::setMemoryLimit("2G");
CApp::setTimeLimit(300);

CCanDo::checkRead();

$sejours_ids = CView::post("sejours_ids", "str");

CView::checkin();

// Chargement des séjours
$sejour = new CSejour();

$where = array();
$where["sejour_id"] = "IN ($sejours_ids)";

/** @var CSejour[] $sejours */
$sejours = $sejour->loadList($where);

$result = "";

CStoredObject::massLoadFwdRef($sejours, "patient_id");

foreach ($sejours as $_sejour) {
  $_sejour->loadRefPatient();
}

// Tri par nom de patient
$sorter_nom    = CMbArray::pluck($sejours, "_ref_patient", "nom");
$sorter_prenom = CMbArray::pluck($sejours, "_ref_patient", "prenom");
array_multisort($sorter_nom, SORT_ASC, $sorter_prenom, SORT_ASC, $sejours);

$last_sejour = end($sejours);

foreach ($sejours as $_sejour) {
  $_operation = $_sejour->loadRefLastOperation();
  
  if (!$_operation->_id) {
    continue;
  }
  
  $consult_anesth = $_operation->loadRefsConsultAnesth();
  
  if ($consult_anesth->_id) {
    $result .= CApp::fetch(
      "dPcabinet", "print_fiche", array (
        "dossier_anesth_id" => $consult_anesth->_id,
        "offline"         => 1,
        "multi"           => 1,
      )
    );

    if ($_sejour->_id != $last_sejour->_id ) {
      $result .= "<hr style=\"border: none; page-break-after: always;\" />";
    }
  }
}

echo $result != "" ?
  $result :
  "<h1>" . CAppUI::tr("CConsultAnesth.none") . "</h1>";