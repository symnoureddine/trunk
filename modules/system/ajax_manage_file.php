<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbException;
use Ox\Core\CMbObject;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Interop\Ftp\CSourceFTP;
use Ox\Mediboard\System\CSourceFileSystem;

CCanDo::checkAdmin();

$current_directory = CView::get("current_directory", "str");
$delete            = CView::get("delete"           , "str", false);
$rename            = CView::get("rename"           , "str", false);
$new_name          = CView::get("new_name"         , "str");
$file              = CView::get("file"             , "str");
$source_guid       = CView::get("source_guid"      , "str");
CView::checkin();

/** @var CSourceFTP|CSourceFTP|CSourceFileSystem $source */
$exchange_source = CMbObject::loadFromGuid($source_guid);

try {
  if ($delete && $file) {
    $exchange_source->delFile($file, $current_directory);
  }

  if ($rename && $new_name) {
    $exchange_source->renameFile($file, $new_name, $current_directory);
  }
}
catch (CMbException $e) {
  CAppUI::displayAjaxMsg($e->getMessage()            , UI_MSG_ERROR);
  CAppUI::displayAjaxMsg($exchange_source->getError(), UI_MSG_ERROR);
}

$current_directory = $exchange_source->getCurrentDirectory($current_directory);
$files             = $exchange_source->getListFilesDetails($current_directory);

$smarty = new CSmartyDP();
$smarty->assign("files"            , $files);
$smarty->assign("current_directory", $current_directory);
$smarty->assign("source_guid"      , $source_guid);
$smarty->display("inc_manage_file.tpl");