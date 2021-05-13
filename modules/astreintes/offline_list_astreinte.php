<?php
/**
 * @package Mediboard\Astreintes
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CMbDT;
use Ox\Core\CSmartyDP;
use Ox\Core\CSQLDataSource;
use Ox\Core\CView;
use Ox\Mediboard\Astreintes\CPlageAstreinte;
use Ox\Mediboard\Etablissement\CGroups;

CCanDo::checkEdit();

$date       = CView::get("date", "date default|now", true);
$mode       = CView::get("mode", "enum list|day|week|month|year default|day", true);
$type_names = CView::get("type_names", "str");
CView::checkin();

$group = CGroups::loadCurrent();

$astreinte         = new CPlageAstreinte;
$where             = array();
$where["group_id"] = " = '$group->_id' ";

if ($type_names && reset($type_names) != "all") {
  $where["type"] = CSQLDataSource::prepareIn($type_names);
}

$order = "start DESC,end";

switch ($mode) {
  case 'year':
    $year_first     = CMbDT::transform(null, $date, "%Y-01-01");
    $year_last      = CMbDT::transform(null, $date, "%Y-12-31");
    $where["start"] = "< '$year_last 23:59:00'";
    $where["end"]   = "> '$year_first 00:00:00'";
    $date_next      = CMbDT::date("+1 YEAR", $date);
    $date_prev      = CMbDT::date("-1 YEAR", $date);
    break;

  case 'month':
    $month_monday   = CMbDT::date("first day of this month", $date);
    $month_sunday   = CMbDT::date("last day of this month", $month_monday);
    $where["start"] = "< '$month_sunday 23:59:00'";
    $where["end"]   = "> '$month_monday 00:00:00'";
    $date_next      = CMbDT::date("+1 MONTH", $month_monday);
    $date_prev      = CMbDT::date("-1 MONTH", $month_monday);
    break;

  case 'week':
    $week_monday    = CMbDT::date("this week", $date);
    $week_sunday    = CMbDT::date("next sunday", $week_monday);
    $where["start"] = "<= '$week_sunday 23:59:00'";
    $where["end"]   = ">= '$week_monday 00:00:00'";
    $date_next      = CMbDT::date("+1 WEEK", $date);
    $date_prev      = CMbDT::date("-1 WEEK", $date);
    break;

  default:
    $where["start"] = "< '$date 23:59:00'";
    $where["end"]   = "> '$date 00:00:00'";
    $date_next      = CMbDT::date("+1 DAY", $date);
    $date_prev      = CMbDT::date("-1 DAY", $date);
    break;
}

$astreintes = $astreinte->loadList($where, $order);

/**
 * @var CPlageAstreinte $_astreinte
 */
foreach ($astreintes as $_astreinte) {
  $_astreinte->loadRefUser();
  $_astreinte->loadRefColor();
  $_astreinte->getCollisions();
  $_astreinte->loadRefCategory();
}

//smarty
$smarty = new CSmartyDP();
$smarty->assign("astreinte", $astreinte);
$smarty->assign("astreintes", $astreintes);
$smarty->assign("start_period", $where["start"]);
$smarty->assign("end_period", (isset($where["end"])) ? $where["end"] : null);
$smarty->display("offline_list_astreintes");
