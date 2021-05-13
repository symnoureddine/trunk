<?php
/**
 * @package Mediboard\Astreintes
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

// Get filter
use Ox\Core\CAppUI;
use Ox\Core\CMbDT;
use Ox\Core\CSmartyDP;
use Ox\Core\CSQLDataSource;
use Ox\Core\CValue;
use Ox\Mediboard\Astreintes\CPlageAstreinte;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Mediusers\CMediusers;

$filter = new CPlageAstreinte;
$year   = date("Y");
$group  = CGroups::loadCurrent();
$today  = CMbDT::dateTime();


$filter->user_id = CValue::get("user_id", CAppUI::$user->_id);
$filter->_id     = CValue::get("plage_id", "");
$filter->start   = CValue::get("start", "$year-01-01");
$filter->end     = CValue::get("end", "$year-12-31");

// load available users
$mediuser  = new CMediusers();
$mediusers = $mediuser->loadListFromType();

$user = CMediusers::get($filter->user_id);

// load ref function
foreach ($mediusers as $mid => $_medius) {
  $_medius->loadRefFunction();
}

// Query
$where             = array();
$where["user_id"]  = CSQLDataSource::prepareIn(array_keys($mediusers), $filter->user_id);
$where["group_id"] = " = '$group->_id'";

$debut = CValue::first($filter->start, $filter->end);
$fin   = CValue::first($filter->end, $filter->start);

if ($fin || $debut) {
  $where["start"] = "<= '$fin'";
  $where["end"]   = ">= '$debut'";
}

/** @var CPlageAstreinte[] $plages */
$plages = $filter->loadList($where, "start DESC", "0,100");

// Regrouper par utilisateur
$found_users     = array();
$plages_per_user = array();
foreach ($plages as $_plage) {
  if (!isset($found_users[$_plage->user_id])) {
    $found_users[$_plage->user_id] = null;
  }
  $found_users[$_plage->user_id] = $mediusers[$_plage->user_id];
  $_plage->_ref_user             = $_plage->loadRefUser();
  $_plage->loadRefColor();
  $_plage->getDuration();

  if (!isset($plages_per_user[$_plage->user_id])) {
    $plages_per_user[$_plage->user_id] = 0;
  }
  $plages_per_user[$_plage->user_id]++;
}

$nbusers     = count($found_users);
$page        = intval(CValue::get('page', 0));
$found_users = array_slice($found_users, $page, 20, true);

// Création du template
$smarty = new CSmartyDP();
$smarty->assign("user", $user);
$smarty->assign("mediusers", $mediusers);
$smarty->assign("found_users", $found_users);
$smarty->assign("plages", $plages);
$smarty->assign("filter", $filter);
$smarty->assign("plages_per_user", $plages_per_user);
$smarty->assign("nbusers", $nbusers);
$smarty->assign("page", $page);
$smarty->assign("today", $today);
$smarty->display("vw_idx_plages_astreinte");