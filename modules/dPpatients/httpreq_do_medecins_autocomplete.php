<?php
/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbArray;
use Ox\Core\CSmartyDP;
use Ox\Core\CValue;
use Ox\Core\CView;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Patients\CMedecin;

CCanDo::checkRead();

$current_user = CMediusers::get();

$keywords         = CValue::post("_view");
$all_departements = CValue::post("all_departements", 0);
$function_id      = CValue::get("function_id", $current_user->function_id);
$type             = CValue::get("type", "medecin");

CView::enableSlave();

if ($keywords == "") {
  $keywords = "%%";
}

$medecin = new CMedecin();
$order   = 'nom';
$group   = CGroups::loadCurrent();

$where             = array();
$medecin_cps_prefs = (!$all_departements) ? CAppUI::pref("medecin_cps_pref") : "";

$where["actif"] = "= '1'";

if ($type == "pharmacie") {
  $where['type'] = " = 'pharmacie'";
}
elseif ($medecin_cps_prefs != "") {
  $cps = preg_split("/\s*[\s\|,]\s*/", $medecin_cps_prefs);
  CMbArray::removeValue("", $cps);

  if (count($cps)) {
    $where_cp = array();
    foreach ($cps as $cp) {
      $where_cp[] = "cp LIKE '" . $cp . "%'";
    }
    $where[] = "(" . implode(" OR ", $where_cp) . ")";
  }
}
else {
  if ($group->_cp_court && !$all_departements) {
    $where['cp'] = "LIKE '" . $group->_cp_court . "%'";
  }
}

$is_admin = $current_user->isAdmin();
if (CAppUI::isCabinet()) {
  $where["function_id"] = "= '$function_id'";
}
elseif (CAppUI::isGroup()) {
  $user_group_id = $current_user->loadRefFunction()->group_id;
  $where["group_id"] = "= '$user_group_id'";
}

$matches = $medecin->seek($keywords, $where, 50, null, null, $order);

// Création du template
$smarty = new CSmartyDP();

$smarty->assign("keywords", $keywords);
$smarty->assign("matches", $matches);
$smarty->assign("nodebug", true);

$smarty->display("httpreq_do_medecins_autocomplete.tpl");

