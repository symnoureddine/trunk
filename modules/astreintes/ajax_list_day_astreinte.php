<?php
/**
 * @package Mediboard\Astreintes
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbDT;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Astreintes\CCategorieAstreinte;
use Ox\Mediboard\Astreintes\CPlageAstreinte;
use Ox\Mediboard\Etablissement\CGroups;

CCanDo::checkRead();

$date = CView::get("date", "date default|now");
$time = CView::get("time", "time default|now");
CView::checkin();
$group = CGroups::loadCurrent();

// Plages d'astreinte pour l'utilisateur
$plage_astreinte   = new CPlageAstreinte();
$where             = array();
$where["start"]    = "< '$date $time'";
$where["end"]      = "> '$date 00:00:00'";
$where["group_id"] = " = '$group->_id' ";
$plages_astreinte  = $plage_astreinte->loadList($where);

/** @var CPlageAstreinte[] $plages_astreinte */
foreach ($plages_astreinte as $key_plage => $_plage) {
  if ($_plage->end < CMbDT::dateTime()) {
    unset($plages_astreinte[$key_plage]);
  }

  $_plage->loadRefUser();
  $_plage->loadRefColor();
}

$categorie = new CCategorieAstreinte();
$categories = $categorie->loadGroupList() + $categorie->loadList('group_id is null');

// Création du template
$smarty = new CSmartyDP();
$smarty->assign("plages_astreinte", $plages_astreinte);
$smarty->assign("categories", $categories);
$smarty->assign("title", CAppUI::tr("CPlageAstreinte.For") . " " . htmlentities(CMbDT::format($date, CAppUI::conf("longdate"))));
$smarty->assign("date", $date);
$smarty->display("vw_list_day_astreinte");

