<?php
/**
 * @package Mediboard\Astreintes
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbArray;
use Ox\Core\CMbDT;
use Ox\Core\CMbString;
use Ox\Core\CSmartyDP;
use Ox\Core\CSQLDataSource;
use Ox\Core\CStoredObject;
use Ox\Core\CView;
use Ox\Mediboard\Astreintes\CCategorieAstreinte;
use Ox\Mediboard\Astreintes\CPlageAstreinte;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\System\CPlanningDay;
use Ox\Mediboard\System\CPlanningEvent;
use Ox\Mediboard\System\CPlanningMonth;
use Ox\Mediboard\System\CPlanningWeekNew;

CCanDo::checkRead();

$date     = CView::get("date", "date default|now", true);
$mode     = CView::get("mode", "enum list|day|week|month|year default|week", true);
$category_id = CView::get("category", "ref class|CCategorieAstreinte default|" . CAppUI::pref("categorie-astreintes"));
$group    = CGroups::loadCurrent();

CView::checkin();

// In the calendar view, the year cannot be selected though in the on call list page you can.
// If the "year" value is kept in session, a warning pops up saying bad CView value.
$mode = ($mode === "year") ? "week" : $mode;

$user      = CMediusers::get();
$ds        = CSQLDataSource::get('std');
$astreinte = new CPlageAstreinte();
$where     = [
  "group_id" => $ds->prepare("= ?", $group->_id)
];
$order     = "type ASC, categorie ASC";

if ($category_id) {
  $where["categorie"] = $ds->prepare("= ?", $category_id);
}

$date_next = CMbDT::date("+1 DAY", $date);
$date_prev = CMbDT::date("-1 DAY", $date);

switch ($mode) {
  case 'day':
    $today_midnight = (new DateTimeImmutable($date))->setTime(0, 0, 0);
    $today_late = (new DateTimeImmutable($date))->setTime(23, 59, 59);

    $where[] = $ds->prepare(
      "(?1 BETWEEN start AND end) OR (start > ?1 AND end < ?2) OR (?2 BETWEEN start AND end)",
      $today_midnight->format('Y-m-d H:i:s'),
      $today_late->format('Y-m-d H:i:s')
    );

    $calendar = new CPlanningDay($date);
    break;

  case 'month':
    $month_first = CMbDT::date("first day of this month", $date);
    $month_last  = CMbDT::date("last day of this month", $month_first);

    $date_next = CMbDT::date("+1 DAY", $month_last);
    $date_prev = CMbDT::date("-1 DAY", $month_first);

    $month_first = new DateTimeImmutable($month_first);
    $month_last  = new DateTimeImmutable($month_last);

    $where[] = $ds->prepare(
      "(?1 BETWEEN start AND end) OR (start > ?2 AND end < ?3) OR (start < ?3 AND end > ?2)",
      $date,
      $month_first->format('Y-m-d 0:0:0'),
      $month_last->format('Y-m-d 23:59:59')
    );

    $calendar = new CPlanningMonth($date, $month_first->format('Y-m-d'), $month_last->format('Y-m-d'));
    break;

  default:  // week
    $week_monday = new DateTimeImmutable(CMbDT::date("this week", $date));
    $week_sunday = new DateTimeImmutable(CMbDT::date("Next Sunday", $week_monday->format('Y-m-d')));
    $week_sunday = $week_sunday->add(new DateInterval("PT23H59M59S"));

    $where[] = $ds->prepare(
      "(?1 BETWEEN start AND end) OR (start < ?2 AND end > ?3)",
      $date,
      $week_sunday->format('Y-m-d 23:59:59'),
      $week_monday->format('Y-m-d 0:0:0')
    );

    $calendar = new CPlanningWeekNew($date, $week_monday->format('Y-m-d 0:0:0'), $week_sunday->format('Y-m-d 23:59:59'));
    break;
}

$calendar->guid  = "CPlanning-$mode-$date";
$calendar->title = "Astreintes-$mode-$date";

$astreintes = $astreinte->loadList($where, $order);

$astreintes_by_type = [];

CStoredObject::massLoadFwdRef($astreintes, "categorie");
foreach ($astreintes as $_astreinte) {
  $_astreinte->loadRefCategory();
  $astreintes_by_type[$_astreinte->type][$_astreinte->_ref_category->name][] = $_astreinte;
}

$i              = [];
$height_medical = 0;

$events             = [];
$events_sorted      = [];
$days               = [];
$astreintes_by_step = [];
$max_height_event   = 0;

/** @var CPlageAstreinte[] $astreintes */
foreach ($astreintes_by_type as $type => $_astreintes_categories) {
  foreach ($_astreintes_categories as $_category => $_astreintes) {
    foreach ($_astreintes as $_astreinte) {
      $start = $_astreinte->start;
      $end   = $_astreinte->end;

      $astreinte_start = new DateTime($_astreinte->start);
      $start_format    = $astreinte_start->format('Y-m-d H:i:s');
      $astreinte_end   = new DateTime($_astreinte->end);
      $end_format      = $astreinte_end->format('Y-m-d H:i:s');

      if ($mode === 'day') {
        $start = ($astreinte_start < $today_midnight) ? $today_midnight->format('Y-m-d H:i:s') : $start_format;
        $end   = ($astreinte_end > $today_late) ? $today_late->format('Y-m-d H:i:s') : $end_format;
      }
      elseif ($mode === "week") {
        $start = ($astreinte_start < $week_monday) ? $week_monday->format('Y-m-d 00:00:00') : $start_format;
        $end   = ($astreinte_end > $week_sunday) ? $week_sunday->format('Y-m-d 23:59:59') : $end_format;
      }
      elseif ($mode === 'month') {
        $start = ($astreinte_start < $month_first) ? $month_first->format('Y-m-d 00:00:00') : $start_format;
        $end   = ($astreinte_end > $month_last) ? $month_last->format('Y-m-d 23:59:59') : $end_format;
      }

      $length = CMbDT::minutesRelative($start, $end);

      //not in the current group
      $_astreinte->loadRefUser();
      $_astreinte->loadRefColor();
      $_astreinte->loadRefCategory();

      $libelle = "<span style=\"text-align:center;\">";
      $libelle .= ($_astreinte->libelle) ? "<strong>$_astreinte->libelle</strong><br/>" : null;
      $libelle .= ($_astreinte->_ref_category) ? "<strong>" . $_astreinte->_ref_category->name . "</strong><br/>" : null;
      $libelle .= $_astreinte->_ref_user . '<br/>' . $_astreinte->phone_astreinte . "</span>";
      $libelle = CMbString::purifyHTML($libelle);

      $plage = new CPlanningEvent($_astreinte->_guid, $start, $length, $libelle, "#" . $_astreinte->_color, true, 'astreinte', false, false);
      $plage->setObject($_astreinte);
      $plage->plage["id"]   = $_astreinte->_id;
      $plage->type          = $_astreinte->type;
      $plage->end           = $end;
      $plage->display_hours = true;

      if (($_astreinte->locked == 0 && CCanDo::edit()) ||
        ($_astreinte->locked == 1 && CCanDo::admin())) {
        $plage->addMenuItem("edit", "Modifier l'astreinte");
      }

      //add the event to the planning
      $calendar->addEvent($plage);
    }

    $calendar->hour_min = "00";
    $calendar->rearrange(true);

    $events_sorted[$type][$_category] = $calendar->events_sorted;
    $events                           = array_merge($events, $calendar->events);
    $days                             = array_merge_recursive($days, $calendar->days);

    //$max_height_event += $calendar->max_height_event;
    $max_height_event += count($_astreintes);

    $calendar->events_sorted = [];
    $calendar->events        = [];
    if ($days) {
      $calendar->days = [];
    }
  }
}

foreach ($events_sorted as $type => $_events_by_type) {
  foreach ($_events_by_type as $_category => $_events_by_category) {
    foreach ($_events_by_category as $_events_by_day) {
      foreach ($_events_by_day as $_events_by_hour) {

        $heights = CMbArray::pluck($_events_by_hour, "height");

        if (isset($astreintes_by_step[$type][$_category])) {
          $heights = array_merge($heights, [$astreintes_by_step[$type][$_category]]);
        }
        $astreintes_by_step[$type][$_category] = max($heights);
      }
    }
  }
}

$i        = 0;
$num      = 0;
$test     = 0;
$date_fin = CMbDT::date("-1 days", $date);

foreach ($events_sorted as $type => $_events_by_type) {
  foreach ($_events_by_type as $_category => $_events_by_category) {
    if (!isset($astreintes_by_step[$type][$_category])) {
      $astreintes_by_step[$type] = 0;
    }

    if ($i == 0) {
      $i++;
      $num = $astreintes_by_step[$type][$_category] + 1;
      continue;
    }

    foreach ($_events_by_category as $_events_by_day) {
      foreach ($_events_by_day as $_events_by_hour) {
        foreach ($_events_by_hour as $_event) {
          $_event->height += $num;
        }
      }
    }

    $num += $astreintes_by_step[$type][$_category] + 1;
  }
}

$events_merged = [];
foreach ($events_sorted as $_events) {
  $events_merged = array_merge_recursive($events_merged, $_events);
}

$calendar->events_sorted = $events_merged;
$calendar->days          = $days ? $days : $calendar->days;
$calendar->events        = $events;

$calendar->max_height_event = $max_height_event; /* - count(array_keys($events_merged))*/

$categoryOnCall = new CCategorieAstreinte();
$categories     = $categoryOnCall->loadGroupList() + $categoryOnCall->loadList('group_id is null');

$smarty = new CSmartyDP();
$smarty->assign("date", $date);
$smarty->assign("next", $date_next);
$smarty->assign("prev", $date_prev);
$smarty->assign("planning", $calendar);
$smarty->assign("height_planning_astreinte", CAppUI::pref("planning_resa_height", 1500));
$smarty->assign("mode", $mode);
$smarty->assign("categories", $categories);
$smarty->assign("current_category_id", $category_id);
$smarty->display("vw_calendar");
