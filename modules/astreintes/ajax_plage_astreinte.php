<?php
/**
 * @package Mediboard\Astreintes
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

//CCanDo::checkRead();

use Ox\Core\CMbDT;
use Ox\Core\CSmartyDP;
use Ox\Core\CValue;
use Ox\Mediboard\Astreintes\CPlageAstreinte;
use Ox\Mediboard\Mediusers\CMediusers;

$user_id = CValue::getorSession("user_id");
$user    = new CMediusers();
$user->load($user_id);
$today = CMbDT::dateTime();

// Plages d'astreinte pour l'utilisateur
$plage_astreinte          = new CPlageAstreinte();
$plage_astreinte->user_id = $user_id;
$plages_astreinte         = $plage_astreinte->loadMatchingList("start DESC", 100);

$new_plageastreinte = new CPlageAstreinte();

$plage_id = CValue::get("plage_id");

// Création du template
$smarty = new CSmartyDP();
$smarty->assign("user", $user);
$smarty->assign("plages_astreinte", $plages_astreinte);
$smarty->assign("new_plageastreinte", $new_plageastreinte);
$smarty->assign("plage_id", $plage_id);
$smarty->assign("today", $today);
$smarty->display("inc_liste_plages_astreinte");