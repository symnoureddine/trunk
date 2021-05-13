<?php
/**
 * @package Mediboard\Cda
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CValue;
use Ox\Interop\Cda\CCdaTools;

CCanDo::checkAdmin();

$action      = CValue::get("action", "null");
$result      = null;
$resultSynth = null;

if ($action !== "null") {
  $result      = CCdaTools::createTest($action);
  $resultSynth = CCdaTools::syntheseTest($result);
}

$smarty = new CSmartyDP();

$smarty->assign("result"     , $result);
$smarty->assign("resultsynth", $resultSynth);
$smarty->assign("action"     , $action);

$smarty->display("vw_testdatatype.tpl");