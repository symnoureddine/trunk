<?php
/**
 * @package Mediboard\Cabinet
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CCanDo;
use Ox\Core\CMbArray;
use Ox\Core\CMbDT;
use Ox\Core\CSQLDataSource;
use Ox\Core\CStoredObject;
use Ox\Core\CView;
use Ox\Mediboard\Cabinet\CPlageconsult;

CCanDo::checkRead();

$function_id   = CView::get("function_id", "ref class|CFunctions");
$prats_ids     = CView::get("prats_ids", "str", true);
$days          = CView::get("days", "str", true);
$times         = CView::get("times", "str", true);
$libelle_plage = CView::get("libelle_plage", "str", true);
$week_number   = CView::get("week_number", "str", true);
$year          = CView::get("year", "str");
$week_num      = CView::get("week_num", "str");

CView::checkin();
CView::enableSlave();

// Récupération des différents tableaux
$prats_ids = explode(",", $prats_ids);
$days      = explode(",", $days);
$times     = explode(",", $times);

if ($week_num > 52) {
  CApp::json(array());
  CApp::rip();
}

$dates_week = CMbDT::dateFromWeekNumber($week_number, $year);
$monday     = CMbDT::date("this week", $dates_week["start"]);

foreach ($days as $_day) {

  if ($_day == "Monday") {
    $dates[] = $monday;
  }
  else {
    $dates[] = CMbDT::date("next $_day", $monday);
  }
}

// days
$name_days = array("Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday");

$where  = array();
$slots  = array();
$plages = array();
$plage  = new CPlageconsult();

$where["chir_id"] = CSQLDataSource::prepareIn($prats_ids);
$where['date']    = CSQLDataSource::prepareIn($dates);

if ($libelle_plage) {
  $where['libelle'] = " LIKE '%$libelle_plage%'";
}

CMbArray::removeValue('', $times);

if ($times) {
  $where_time = array();

  foreach ($times as $_time) {
    $where_time[] = "'$_time' BETWEEN debut AND fin";
  }
  $where[] = implode(' OR ', $where_time);
}

$where['locked'] = " != '1' ";

$plages = $plage->loadList($where, null, null);

CStoredObject::massLoadFwdRef($plages, "chir_id");
CStoredObject::massLoadBackRefs($plages, "consultations", "heure");

if ($plages) {
  foreach ($plages as $_plage) {

    $datetime = $_plage->date . " " . $_plage->debut;
    $prat_id  = $_plage->chir_id;

    // Chargement des places disponibles
    $slots[$prat_id][$datetime] = $_plage->getEmptySlots();

    // retrait des créneaux en dehors des heures demandées
    // Ne pas afficher des créneaux dans le passés
    foreach ($slots[$prat_id] as $_datetime) {
      foreach ($_datetime as $_key => $_slot) {
        if (!in_array(CMbDT::transform(null, $_slot["hour"], '%H:00:00'), $times) ||
          ($_slot["date"] . " " . $_slot["hour"] < CMbDT::dateTime())) {
          unset($slots[$prat_id][$datetime][$_key]);
        }
      }
    }

    // retire les array vides
    if (empty($slots[$prat_id][$datetime])) {
      unset($slots[$prat_id][$datetime]);
    }
  }
}

// tri du tableau par dateTime
foreach ($slots as $_slot) {
  array_multisort(array_keys($_slot), SORT_ASC, $_slot);
}

// Vérification de l'existance de la prochaine semaine
$next_monday = CMbDT::date("next monday", $dates_week["start"]);

$nb_week          = $plage->getNumberNextWeek($next_monday, $week_number);
$slots["year"]    = $year;
$slots["nb_week"] = $nb_week;

sleep(0.5);

CApp::json($slots);
