<?php
/**
 * @package Mediboard\Developpement
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CValue;
use Ox\Mediboard\System\CErrorLog;

CCanDo::checkAdmin();

$ids = CValue::post("log_ids");

if ($ids) {
  $ids = explode("-", $ids);

  $error_log = new CErrorLog();
  $rows = $error_log->deleteMulti($ids);
  CAppUI::stepAjax("'$rows' rows deleted");
}