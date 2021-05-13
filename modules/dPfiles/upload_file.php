<?php
/**
 * @package Mediboard\Files
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Files\CFile;
use Ox\Mediboard\Files\CFilesCategory;

$object = mbGetObjectFromGet("object_class", "object_id", "object_guid", true);

$file_category_id = CView::get("file_category_id", "ref class|CFilesCategory", true);
$_rename          = CView::get("_rename", "str", true);
$uploadok         = CView::get("uploadok", "bool");
$private          = CView::get("private", "bool");
$named            = CView::get("named", "bool");
$ext_cabinet_id   = CView::get("ext_cabinet_id", "num");

CView::checkin();

$listCategory = CFilesCategory::listCatClass($object->_class);

$file = new CFile();
$file->private = $private;

$file->file_category_id = $file_category_id ? : CFilesCategory::getDefautCat(null, $object->_class)->_id;
$file->_ext_cabinet_id = $ext_cabinet_id;

// Création du template
$smarty = new CSmartyDP();

$smarty->assign("object"          , $object);
$smarty->assign("file_category_id", $file_category_id);
$smarty->assign("uploadok"        , $uploadok);
$smarty->assign("listCategory"    , $listCategory);
$smarty->assign("_rename"         , $_rename);
$smarty->assign("named"           , $named);
$smarty->assign("file"            , $file);
$smarty->display("upload_file");
