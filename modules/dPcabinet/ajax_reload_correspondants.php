<?php
/**
 * @package Mediboard\Cabinet
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Admin\CAccessMedicalData;
use Ox\Mediboard\Cabinet\CConsultation;

CCanDo::checkRead();

$consultation_id = CView::getRefCheckRead("consultation_id", "ref class|CConsultation");

CView::checkin();

$consultation = new CConsultation;
$consultation->load($consultation_id);

CAccessMedicalData::logAccess($consultation);

$patient = $consultation->loadRefPatient();
$patient->loadRefsCorrespondants();

// Si medecin correspondant devient le medecin traitant, ne pas l'afficher 2 fois
foreach ($patient->_ref_medecins_correspondants as $_corresp) {
  if ($patient->_ref_medecin_traitant->_id == $_corresp->medecin_id) {
    unset($patient->_ref_medecins_correspondants[$_corresp->_id]);
  }
}

$smarty = new CSmartyDP();

$smarty->assign("consult", $consultation);
$smarty->assign("patient", $patient);

$smarty->display("inc_list_patient_medecins.tpl");
