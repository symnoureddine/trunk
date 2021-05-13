<?php
/**
 * @package Mediboard\Forms
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CValue;
use Ox\Mediboard\Forms\CExClassImport;

CCanDo::checkEdit();

$uid     = CValue::post("file_uid");
$from_db = CValue::post("fromdb");
$options = CValue::post("options");

$options = stripslashes_deep($options);

set_time_limit(600);

$options["ignore_disabled_fields"] = isset($options["ignore_disabled_fields"]);

$uid  = preg_replace('/[^\d]/', '', $uid);
$temp = CAppUI::getTmpPath("ex_class_import");
$file = "$temp/$uid";

try {
  $import = new CExClassImport($file);
  $import->import($from_db, $options);
}
catch (Exception $e) {
  CAppUI::stepAjax($e->getMessage(), UI_MSG_WARNING);
} 