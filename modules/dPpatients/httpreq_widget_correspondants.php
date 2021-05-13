<?php
/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\OpenData\CImportConflict;
use Ox\Mediboard\Patients\CPatient;

CCanDo::checkEdit();

$patient_id = CView::get("patient_id", "ref class|CPatient", true);
$widget_id  = CView::get("widget_id", "str");

CView::checkin();

$patient = new CPatient();
$patient->load($patient_id);
if ($patient->_id) {
  $patient->loadRefsCorrespondants();

  foreach ($patient->_ref_medecins_correspondants as $curr_corresp) {
    $curr_corresp->_ref_medecin->loadRefSpecCPAM();
  }
}

$conflicts   = array();
$medecin_ids = array();
if (class_exists('CImportConflict')) {
  $result      = CImportConflict::getConflictsForPatient($patient, true);
  $conflicts   = $result['conflicts'];
  $medecin_ids = $result['medecin_ids'];
}

$user = CMediusers::get();
$user->isMedecin();

// Création du template
$smarty = new CSmartyDP();

$smarty->assign("patient", $patient);
$smarty->assign("widget_id", $widget_id);
$smarty->assign('user', $user);
$smarty->assign('conflicts', $conflicts);
$smarty->assign('medecin_ids', implode('|', $medecin_ids));

$smarty->display("inc_widget_correspondants.tpl");
