<?php
/**
 * @package Mediboard\Astreintes
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

//CCanDo::checkRead();

use Ox\Core\CAppUI;
use Ox\Core\CMbDT;
use Ox\Core\CSmartyDP;
use Ox\Core\CSQLDataSource;
use Ox\Core\CValue;
use Ox\Mediboard\Astreintes\CPlageAstreinte;
use Ox\Mediboard\Mediusers\CMediusers;

$choix           = CValue::get("choix", "mois");
$filter          = new CPlageAstreinte();
$filter->user_id = CValue::get("user_id", CAppUI::$user->_id);
$filter->start   = CValue::get("date_debut", CMbDT::date());

// Tableau des jours fériés sur 2 ans, car
// en mode semaine : 31 décembre - 1 janvier
$bank_holidays = array_merge(CMbDT::getHolidays($filter->start), CMbDT::getHolidays((CMbDT::transform("+1 YEAR", $filter->start, "%Y-%m-%d"))));

$mediuser = new CMediusers();
switch ($filter->user_id) {
  case '-1':
    $mediusers       = $mediuser->loadProfessionnelDeSante();
    $filter->user_id = "";
    break;

  case '-2':
    $mediusers       = $mediuser->loadNonProfessionnelDeSante();
    $filter->user_id = "";
    break;

  default:
    $mediusers = $mediuser->loadListFromType();
    break;
}

if (!$filter->start) {
  $filter->start = Date("Y-m-d");
}

// Si la date rentrée par l'utilisateur est un lundi,
// on calcule le dimanche d'avant et on rajoute un jour.
$tab_start = array();
if ($choix == "semaine") {
  $last_sunday   = CMbDT::transform('last sunday', $filter->start, '%Y-%m-%d');
  $last_monday   = CMbDT::transform('+1 day', $last_sunday, '%Y-%m-%d');
  $debut_periode = $last_monday;

  $fin_periode = CMbDT::transform('+6 day', $debut_periode, '%Y-%m-%d');
}
else {
  if ($choix == "annee") {
    list($year, $m, $j) = explode("-", $filter->start);
    $debut_periode = "$year-01-01";
    $fin_periode   = "$year-12-31";
    $j             = 1;
    for ($i = 1; $i < 13; $i++) {
      if (!date("w", mktime(0, 0, 0, $i, 1, $year))) {
        $tab_start[$j] = 7;
      }
      else {
        $tab_start[$j] = date("w", mktime(0, 0, 0, $i, 1, $year));
      }
      $j++;
      $tab_start[$j] = date("t", mktime(0, 0, 0, $i, 1, $year));
      $j++;
    }
  }
  else {
    list($a, $m, $j) = explode("-", $filter->start);
    $debut_periode = "$a-$m-01";
    $fin_periode   = CMbDT::transform('+1 month', $debut_periode, '%Y-%m-%d');
    $fin_periode   = CMbDT::transform('-1 day', $fin_periode, '%Y-%m-%d');
  }
}

$tableau_periode = array();

for ($i = 0; $i < CMbDT::daysRelative($debut_periode, $fin_periode) + 1; $i++) {
  $tableau_periode[$i] = CMbDT::transform('+' . $i . 'day', $debut_periode, '%Y-%m-%d');
}


$where            = array();
$where[]          = "((date_debut >= '$debut_periode' AND date_debut <= '$fin_periode'" .
  ")OR (date_fin >= '$debut_periode' AND date_fin <= '$fin_periode')" .
  "OR (date_debut <='$debut_periode' AND date_fin >= '$fin_periode'))";
$where["user_id"] = CSQLDataSource::prepareIn(array_keys($mediusers), $filter->user_id);

$plageastreinte = new CPlageAstreinte();
$plagesconge    = array();
$orderby        = "user_id";
/** @var CPlageAstreinte[] $plagesastreintes */
$plagesastreintes      = $plageastreinte->loadList($where, $orderby);
$tabUser_plage         = array();
$tabUser_plage_indices = array();

foreach ($plagesastreintes as $_plage) {
  $_plage->loadRefUser();
  $_plage->_ref_user->loadRefFunction();
  $_plage->_deb   = CMbDT::daysRelative($debut_periode, $_plage->start);
  $_plage->_fin   = CMbDT::daysRelative($_plage->start, $_plage->end) + 1;
  $_plage->_duree = CMbDT::daysRelative($_plage->start, $_plage->end) + 1;
}

$smarty = new CSmartyDP();

$smarty->assign("debut_periode", $debut_periode);
$smarty->assign("filter", $filter);
$smarty->assign("plagesastreinte", $plagesastreintes);
$smarty->assign("choix", $choix);
$smarty->assign("mediusers", $mediusers);
$smarty->assign("tableau_periode", $tableau_periode);
$smarty->assign("tab_start", $tab_start);
$smarty->assign("bank_holidays", $bank_holidays);

if (($choix == "semaine" || $choix == "mois")) {
  $smarty->display("inc_planning");
}
else {
  $smarty->display("inc_planning_annee");
}
