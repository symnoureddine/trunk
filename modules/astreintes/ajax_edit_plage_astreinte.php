<?php
/**
 * @package Mediboard\Astreintes
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CValue;
use Ox\Mediboard\Astreintes\CCategorieAstreinte;
use Ox\Mediboard\Astreintes\CPlageAstreinte;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Mediusers\CMediusers;

CCanDo::checkEdit();


$plage_id      = CValue::get("plage_id");
$plage_date    = CValue::get("date");
$plage_hour    = CValue::get("hour");
$plage_minutes = CValue::get("minutes");
$user_id       = CValue::get("user_id");

$user  = CMediusers::get($user_id);
$group = CGroups::loadCurrent();

$users = [$user];

$ljoin = [
  "users"               => "users.user_id = users_mediboard.user_id",
  "functions_mediboard" => "functions_mediboard.function_id = users_mediboard.function_id"
];
$where = [
  "users_mediboard.actif" => "= '1' ",
  "group_id"              => " = '$group->_id' or group_id is null "
];

$where_count              = $where;
$where_count["astreinte"] = "= '1'";
$count_oncall_configured  = $user->countList($where_count, null, $ljoin);
if ($count_oncall_configured > 0) {
  $where["astreinte"] = "= '1'";
}

$users = $user->loadListWithPerms(PERM_EDIT, $where, "users.user_last_name", null, null, $ljoin);

$plageastreinte = new CPlageAstreinte();

// edition
if ($plage_id) {
  $plageastreinte->load($plage_id);
  $plageastreinte->loadRefsNotes();
  $plageastreinte->countDuplicatedPlages();
}

// creation
if (!$plageastreinte->_id) {
  // phone
  $plageastreinte->phone_astreinte = $user->_user_astreinte;

  $plageastreinte->group_id = CGroups::loadCurrent()->_id;

  // date & hour
  if ($plage_date && $plage_hour) {
    $plageastreinte->start = "$plage_date $plage_hour:$plage_minutes:00";
  }

  // user
  if (in_array($user->_id, array_keys($users))) {
    $plageastreinte->user_id = $user->_id;
  }
}

$plageastreinte->loadRefGroup();

$categorie  = new CCategorieAstreinte();
$categories = $categorie->loadGroupList() + $categorie->loadList('group_id is null');

$smarty = new CSmartyDP();
$smarty->assign("users", $users);
$smarty->assign("user", $user);
$smarty->assign("group", CGroups::get());
$smarty->assign("plageastreinte", $plageastreinte);
$smarty->assign("categories", $categories);
$smarty->display("inc_edit_plage_astreinte");
