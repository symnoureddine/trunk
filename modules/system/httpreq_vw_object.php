<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CMbArray;
use Ox\Core\CMbObject;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;

CCanDo::check();
$object = mbGetObjectFromGet("object_class", "object_id", "object_guid");
CView::checkin();
CView::enableSlave();

$object->needsRead();

// Look for view options
$options = CMbArray::filterPrefix($_GET, "view_");

$object->loadView();

// If no template is defined, use generic
$template = $object->makeTemplatePath("view");
if (!is_file("modules/{$template}")) {
  $template = $object instanceof CMbObject ? "system/templates/CMbObject_view.tpl" : "system/templates/CStoredObject_view.tpl";
}

// Création du template
$smarty = new CSmartyDP();
$smarty->assign("object", $object);
$smarty->display(__DIR__ . "/../$template");
