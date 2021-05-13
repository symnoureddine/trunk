<?php
/**
 * @package Mediboard\Files
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CMbObject;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;

CCanDo::checkEdit();
$patient_guid = CView::get('patient_guid', 'guid class|CPatient');
CView::checkin();

$patient = CMbObject::loadFromGuid($patient_guid);

$smarty = new CSmartyDP();
$smarty->assign('patient', $patient);
$smarty->display("id_interpreter");
