<?php

/**
 * @package Mediboard\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CCanDo;
use Ox\Core\CMbDT;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Erp\SourceCode\CSourceCodeGraph;

CCanDo::checkRead();

$startDate = CView::get('start_date', 'date default|' . CMbDT::date("-6 months"));
$endDate   = CView::get('end_date', 'date default|' . CMbDT::date());

CView::checkin();
CView::enableSlave();

try {
  $graph = new CSourceCodeGraph();
  $graph->initGraph($startDate, $endDate);
  $smarty = new CSmartyDP();
  $smarty->assign('graph', $graph);
  $smarty->display('vw_sourcecode.tpl');
} catch (Exception $e) {
  CApp::error($e);
}