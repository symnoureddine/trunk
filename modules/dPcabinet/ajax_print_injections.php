<?php
/**
 * @package Mediboard\Cabinet
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CStoredObject;
use Ox\Core\CView;
use Ox\Mediboard\Patients\CPatient;

CCanDo::checkRead();

$patient_id = CView::getRefCheckRead("patient_id", "ref class|CPatient");

CView::checkin();

$patient = CPatient::findOrFail($patient_id);
$injections = $patient->loadRefInjections();

CStoredObject::massLoadBackRefs($injections, "vaccinations");
$vaccinated = [];
foreach ($injections as $_injection) {
  $_injection->loadRefVaccinations();
  foreach ($_injection->_ref_vaccinations as $_vaccination) {
    $_vaccination->loadRefVaccine();
  }
  if ($_injection->isVaccinated()) {
    $vaccinated[] = $_injection->_id;
  }
}

$smarty = new CSmartyDP();
$smarty->assign("injections", $injections);
$smarty->assign("vaccinated", $vaccinated);
$smarty->display("vaccination/print_injection");
