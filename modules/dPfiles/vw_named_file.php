<?php
/**
 * @package Mediboard\Files
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;

CCanDo::checkRead();

$object = mbGetObjectFromGet("object_class", "object_id", "object_guid", true);
$name   = CView::get("name", "str");
$size   = CView::get("size", "num");
$mode   = CView::get("mode", "str");

CView::checkin();

$object->loadNamedFile($name);

// Création du template
$smarty = new CSmartyDP();

$smarty->assign("object", $object);
$smarty->assign("name"  , $name);
$smarty->assign("size"  , $size);
$smarty->assign("mode"  , $mode);

$smarty->display("inc_named_file.tpl");
