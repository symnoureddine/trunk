<?php
/**
 * @package Mediboard\Pmsi
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CMbArray;
use Ox\Core\CSmartyDP;
use Ox\Core\CStoredObject;
use Ox\Core\CView;
use Ox\Mediboard\Pmsi\CRelancePMSI;

$chir_id = CView::get("chir_id", "ref class|CMediusers");

CView::checkin();

$relances = CRelancePMSI::loadRelances($chir_id);

CStoredObject::massLoadFwdRef($relances, "patient_id");
CStoredObject::massLoadFwdRef($relances, "sejour_id");

foreach ($relances as $_relance) {
  $_relance->loadRefPatient();
  $_relance->loadRefSejour();
}

array_multisort(
  CMbArray::pluck($relances, "_ref_patient", "nom")   , SORT_ASC,
  CMbArray::pluck($relances, "_ref_patient", "prenom"), SORT_ASC,
  $relances
);

$smarty = new CSmartyDP();

$smarty->assign("relances", $relances);

$smarty->display("inc_vw_relances");