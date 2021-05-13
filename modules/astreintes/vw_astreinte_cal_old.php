<?php
/**
 * @package Mediboard\Astreintes
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

// CCanDo::checkRead();
use Ox\Core\CCanDo;
use Ox\Core\CMbDT;
use Ox\Core\CSmartyDP;
use Ox\Core\CValue;
use Ox\Mediboard\Astreintes\CPlageAstreinte;

CCanDo::checkEdit();

$date = CValue::get("date", CMbDT::date());
$mode = CValue::get("mode", "day");


$astreinte = new CPlageAstreinte;
$where     = array();
$plage     = array();


switch ($mode) {
  case 'day':
    for ($i = 0; $i < 24; $i++) {
      $plage[] = $i;
    }
    $where["start"] = "< '$date 23:59:00'";
    $where["end"]   = "> '$date 00:00:00'";
    $fstDay         = "$date 00:00:00";
    $lstDay         = "$date 23:59:59";
    break;

  case 'week':
    $week_monday = CMbDT::date("this week", $date);
    $week_sunday = CMbDT::date("next sunday", $week_monday);

    for ($i = CMbDT::transform(null, $week_monday, "%d"); $i < CMbDT::transform(null, $week_monday, "%d") + 7; $i++) {
      $a       = str_pad($i, 2, '0', STR_PAD_LEFT);
      $plage[] = CMbDT::transform(null, $date, "%Y-%m-$a");
    }
    $where["start"] = "> '$week_monday 00:00:00'";
    $where["end"]   = "< '$week_sunday 23:59:00'";
    $fstDay         = "$week_monday 00:00:00";
    $lstDay         = "$week_sunday 23:59:59";
    break;

  case 'month':
    $month_first = CMbDT::date("first day of this month", $date);
    $month_last  = CMbDT::date("last day of this month", $month_first);
    for ($i = CMbDT::transform(null, $month_first, "%d"); $i <= CMbDT::transform(null, $month_last, "%d"); $i++) {
      $a       = str_pad($i, 2, '0', STR_PAD_LEFT);
      $plage[] = CMbDT::transform(null, $month_last, "%Y-%m-$a");
    }
    $where["start"] = "> '$month_first 00:00:00'";
    $where["end"]   = "< '$month_last 23:59:00'";
    $fstDay         = "$month_first 00:00:00";
    $lstDay         = "$month_last 23:59:59";
    break;

  case 'year':
    $year_first     = CMbDT::transform(null, $date, "%Y-01-01");
    $year_last      = CMbDT::transform(null, $date, "%Y-12-31");
    $where["start"] = "> '$year_first 00:00:00'";
    $where["end"]   = "< '$year_last 23:59:00'";
    break;
}
$order = "start,end";


$astreintes = $astreinte->loadList($where, $order);
/**
 * @var CPlageAstreinte $_astreinte
 */
$list_for_rearange = array();
foreach ($astreintes as $_astreinte) {
  $list_for_rearange[$_astreinte->_id] = array("lower" => $_astreinte->start, "upper" => $_astreinte->end);

  if ($mode == "day") {
    if ($_astreinte->start < CMbDT::date() . " 00:00:00") {
      $_astreinte->start = CMbDT::date() . " 00:00:00";
    }
    if ($_astreinte->end > CMbDT::date() . " 23:59:00") {
      $_astreinte->end = CMbDT::date() . " 23:59:00";
    }
  }

  $_astreinte->loadRefColor();
  $_astreinte->getHours();
  $_astreinte->loadRefUser();
}

/*
*/
//smarty
$smarty = new CSmartyDP();
$smarty->assign("firstDay", $fstDay);
$smarty->assign("lastDay", $lstDay);
$smarty->assign("astreintes", $astreintes);
$smarty->assign("now", CMbDT::dateTime());
$smarty->assign("mode", $mode);
$smarty->assign("plage", $plage);
$smarty->display("vw_calendar_$mode");