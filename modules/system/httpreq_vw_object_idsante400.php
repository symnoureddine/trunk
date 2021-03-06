<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\Module\CModule;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;

CCanDo::check();
$object = mbGetObjectFromGet("object_class", "object_id", "object_guid");
CView::checkin();
CView::enableSlave();

$object->needsRead();

// Cr?ation du template
$smarty = new CSmartyDP();
$smarty->assign("canSante400", CModule::getCanDo("dPsante400"));
$smarty->assign("object", $object);
$smarty->display("inc_object_idsante400.tpl");
