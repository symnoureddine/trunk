<?php
/**
 * @package Mediboard\Board
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CMbDT;
use Ox\Core\CSmartyDP;
use Ox\Core\CValue;
use Ox\Core\CView;
use Ox\Mediboard\PlanningOp\COperation;
use Ox\Mediboard\PlanningOp\CSejour;

/**
 * dPboard
 */
CAppUI::requireModuleFile('dPstats', 'graph_patpartypehospi');
CAppUI::requireModuleFile('dPstats', 'graph_activite');

global $prat;

$filterSejour    = new CSejour();
$filterOperation = new COperation();

$filterSejour->_date_min_stat = CValue::getOrSession("_date_min_stat", CMbDT::date("-1 YEAR"));
$rectif                       = CMbDT::transform("+0 DAY", $filterSejour->_date_min_stat, "%d") - 1;
$filterSejour->_date_min_stat = CMbDT::date("-$rectif DAYS", $filterSejour->_date_min_stat);

$filterSejour->_date_max_stat = CValue::getOrSession("_date_max_stat", CMbDT::date());
$rectif                       = CMbDT::transform("+0 DAY", $filterSejour->_date_max_stat, "%d") - 1;
$filterSejour->_date_max_stat = CMbDT::date("-$rectif DAYS", $filterSejour->_date_max_stat);
$filterSejour->_date_max_stat = CMbDT::date("+1 MONTH", $filterSejour->_date_max_stat);
$filterSejour->_date_max_stat = CMbDT::date("-1 DAY", $filterSejour->_date_max_stat);


$filterSejour->praticien_id   = $prat->_id;
$filterSejour->type           = CValue::getOrSession("type", 1);
$filterOperation->_codes_ccam = strtoupper(CValue::getOrSession("_codes_ccam", ""));
CView::checkin();

$graphs = array(
  graphPatParTypeHospi(
    $filterSejour->_date_min_stat, $filterSejour->_date_max_stat, $filterSejour->praticien_id,
    null, $filterSejour->type, null, null, null, 'prevue', $filterOperation->_codes_ccam
  ),
  graphActivite(
    $filterSejour->_date_min_stat, $filterSejour->_date_max_stat, $filterSejour->praticien_id,
    null, null, null, null, $filterOperation->_codes_ccam, null, 0
  ),
);

// Variables de templates
$smarty = new CSmartyDP();

$smarty->assign("filterSejour", $filterSejour);
$smarty->assign("filterOperation", $filterOperation);
$smarty->assign("prat", $prat);
$smarty->assign("graphs", $graphs);

$smarty->display("vw_sejours_interventions");
