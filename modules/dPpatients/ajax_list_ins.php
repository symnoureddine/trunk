<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCando;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Patients\CSourceIdentite;

CCanDo::checkRead();

$source_identite_id = CView::get('source_identite_id', 'ref class|CSourceIdentite');

CView::checkin();

$source_identite = CSourceIdentite::findOrFail($source_identite_id);

$source_identite->loadRefsPatientsINSNIR();

$smarty = new CSmartyDP();

$smarty->assign('source_identite', $source_identite);

$smarty->display('inc_list_ins');
