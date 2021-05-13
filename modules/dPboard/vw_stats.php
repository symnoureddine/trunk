<?php
/**
 * @package Mediboard\Board
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\Module\CModule;
use Ox\Core\CSmartyDP;
use Ox\Core\CValue;

/**
 * dPboard
 */
CAppUI::requireModuleFile("dPboard", "inc_board");
global $prat;
if (!$prat->_id) {
  return;
}

$stats = array(
  "vw_sejours_interventions",
  "vw_stats_consultations",
  "vw_prescripteurs"
);

if (CModule::getActive("dPprescription")) {
  $stats[] = "vw_stats_prescriptions";
}

if (CAppUI::conf("dPplanningOp COperation verif_cote")) {
  $stats[] = "vw_trace_cotes";
}

$stat = CValue::postOrSession("stat", "vw_sejours_interventions");

if (!in_array($stat, $stats)) {
  trigger_error("Unknown stat view '$stat'", E_USER_WARNING);

  return;
}

// Affichage
$smarty = new CSmartyDP();

$smarty->assign("stats", $stats);
$smarty->assign("stat", $stat);

$smarty->display("vw_stats");

CAppUI::requireModuleFile("dPboard", "$stat");
