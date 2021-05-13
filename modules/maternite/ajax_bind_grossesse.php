<?php
/**
 * @package Mediboard\Maternite
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;

/**
 * Mapping entre la parturiente et la grossesse
 */

CCAnDo::checkRead();
$parturiente_id = CView::get("parturiente_id", "ref class|CPatient");
$object_guid    = CView::get("object_guid", "str");
$grossesse_id   = CView::get("grossesse_id", "ref class|CGrossesse");
CView::checkin();

$smarty = new CSmartyDP();
$smarty->assign("parturiente_id", $parturiente_id);
$smarty->assign("object_guid"   , $object_guid);
$smarty->assign("grossesse_id"  , $grossesse_id);
$smarty->display("inc_bind_grossesse");
