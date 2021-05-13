<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCando;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;

CCanDo::checkEdit();

$patient_id = CView::getRefCheckEdit('patient_id', 'ref class|CPatient');

CView::checkin();

$smarty = new CSmartyDP();

$smarty->assign('patient_id', $patient_id);

$smarty->display('vw_add_justificatif');
