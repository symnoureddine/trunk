<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbDT;
use Ox\Core\Module\CModule;
use Ox\Core\CSmartyDP;
use Ox\Core\CValue;
use Ox\Core\CView;

CCanDo::checkRead();

$date     = CValue::getOrSession("date"    , CMbDT::date());
$groupres = CValue::getOrSession("groupres", 1);
$element  = CValue::getOrSession("element" , "duration");
$interval = CValue::getOrSession("interval", "day");
$numelem  = CValue::getOrSession("numelem" , 6);

CView::enableSlave();

CAppUI::requireModuleFile('dPstats', 'graph_ressourceslog');

$next     = CMbDT::date("+1 DAY", $date);
switch ($interval) {
  default:
  case "day":
    $from = CMbDT::date("-1 DAY", $next);
    break;
  case "month":
    $from = CMbDT::date("-1 MONTH", $next);
    break;
  case "year":
    $from = CMbDT::date("-6 MONTH", $next);
    break;
}

// Création du template
$smarty = new CSmartyDP();

$smarty->assign("date"       , $date);
$smarty->assign("groupres"   , $groupres);
$smarty->assign("element"    , $element);
$smarty->assign("interval"   , $interval);
$smarty->assign("numelem"    , $numelem);
$smarty->assign("listModules", CModule::getInstalled());

$smarty->display("view_ressources_logs.tpl");
