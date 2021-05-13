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
use Ox\Mediboard\Files\CFile;

CCanDo::checkRead();

$file_id       = CView::get("id", "ref class|CFile");
$name_readonly = CView::get("name_readonly", "bool default|0");
$object_class  = CView::get("object_class", "str");
$object_id     = CView::get("object_id", "ref class|$object_class");

CView::checkin();

$file = new CFile();
$file->load($file_id);
$file->canDo();

$smarty = new CSmartyDP();
$smarty->assign("_file",         $file);
$smarty->assign("object_id",     $object_id);
$smarty->assign("object_class",  $object_class);
$smarty->assign("name_readonly", $name_readonly);

$smarty->display("inc_widget_line_file.tpl");
