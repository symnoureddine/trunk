<?php
/**
 * @package Mediboard\Etablissement
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CView;
use Ox\Mediboard\Etablissement\CGroupsImport;

CCanDo::checkAdmin();

$uid     = CView::post("file_uid", "str notNull");
$from_db = CView::post("fromdb", "str");
$options = CView::post("options", "str");

CView::checkin();

$options = stripslashes_deep($options);

$uid = preg_replace('/[^\d]/', '', $uid);
$temp = CAppUI::getTmpPath("group_import");
$file = "$temp/$uid";

try {
  $import = new CGroupsImport($file);
  $import->import($from_db, $options);
}
catch (Exception $e) {
  CAppUI::stepAjax($e->getMessage(), UI_MSG_WARNING);
}