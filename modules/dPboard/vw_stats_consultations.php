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
use Ox\Mediboard\Cabinet\CConsultation;

/**
 * dPboard
 */
global $prat;

CAppUI::requireModuleFile('dPstats', 'graph_consultations');

$filterConsultation = new CConsultation();

$filterConsultation->_date_min = CValue::getOrSession("_date_min", CMbDT::date("-1 YEAR"));
$rectif                        = CMbDT::transform("+0 DAY", $filterConsultation->_date_min, "%d") - 1;
$filterConsultation->_date_min = CMbDT::date("-$rectif DAYS", $filterConsultation->_date_min);

$filterConsultation->_date_max = CValue::getOrSession("_date_max", CMbDT::date());
$rectif                        = CMbDT::transform("+0 DAY", $filterConsultation->_date_max, "%d") - 1;
$filterConsultation->_date_max = CMbDT::date("-$rectif DAYS", $filterConsultation->_date_max);
$filterConsultation->_date_max = CMbDT::date("+ 1 MONTH", $filterConsultation->_date_max);
$filterConsultation->_date_max = CMbDT::date("-1 DAY", $filterConsultation->_date_max);

CView::checkin();

$filterConsultation->_praticien_id = $prat->_id;

$graphs = array(
  graphConsultations($filterConsultation->_date_min, $filterConsultation->_date_max, $filterConsultation->_praticien_id),
);

// Variables de templates
$smarty = new CSmartyDP();

$smarty->assign("filterConsultation", $filterConsultation);
$smarty->assign("prat", $prat);
$smarty->assign("graphs", $graphs);

$smarty->display("vw_stats_consultations");
