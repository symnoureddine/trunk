<?php
/**
 * @package Mediboard\Maternite
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CValue;
use Ox\Mediboard\Patients\CPatient;

CCanDo::checkRead();

$patient_id = CValue::get("patient_id");

$patient = new CPatient();
$patient->load($patient_id);

$patient->loadLastGrossesse();
$patient->loadLastAllaitement();

$smarty = new CSmartyDP();

$smarty->assign("patient", $patient);

$smarty->display("inc_fieldset_etat_actuel.tpl");