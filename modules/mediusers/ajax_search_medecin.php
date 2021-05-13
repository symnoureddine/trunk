<?php
/**
 * @package Mediboard\Mediusers
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CStoredObject;
use Ox\Core\CView;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Patients\CMedecin;

CCanDo::checkRead();

// Search fields
$rpps        = CView::get('rpps', 'str');
$nom         = CView::get('nom', 'str');
$prenom      = CView::get('prenom', 'str');
$cp          = CView::get('cp', 'str');
$ville       = CView::get('ville', 'str');
$disciplines = CView::get('disciplines', 'str');

$user_id     = CView::get('user_id', 'ref class|CMediusers');

// Pagination fields
$start       = CView::get('start', 'num default|0');
$step = CView::get('step', 'num default|10');
$type = CView::get('type', 'enum list|close|exact');

CView::checkin();

$function_id = CAppUI::isCabinet() ? CMediusers::get($user_id)->function_id : null;
$group_id    = CAppUI::isGroup()   ? CMediusers::get($user_id)->loadRefFunction()->group_id : null;

if (!$function_id) {
  if (!$function_id && !$group_id) {
    CAppUI::stepAjax('CMedecin-search-function_id.none', UI_MSG_ERROR);
  }
}

$medecin = new CMedecin();
$ds      = $medecin->getDS();

$where = array();

$where[] = $ds->prepare('`user_id` IS NULL OR `user_id` = ?', $user_id);

if ($nom) {
  $where['nom'] = $ds->prepareLike("%$nom%");
}

if ($prenom) {
  $where['prenom'] = $ds->prepareLike("%$prenom%");
}

if ($cp) {
  $where['cp'] = $ds->prepareLike("$cp%");
}

if ($ville) {
  $where['ville'] = $ds->prepareLike("%$ville%");
}

if ($disciplines) {
  $where['disciplines'] = $ds->prepareLike("%$disciplines%");
}

if ($rpps) {
  $where['rpps'] = $ds->prepareLike("$rpps%");
}

if ($function_id) {
  $where['function_id'] = $ds->prepare('= ?', $function_id);
}
else {
  $where['group_id']    = $ds->prepare('= ?', $group_id);
}
$count    = (!$type || $type == 'exact') ? $medecin->countList($where) : 0;
$medecins = (!$type || $type == 'exact') ? $medecin->loadList($where, 'nom, prenom, cp', "$start,$step") : [];

$where['function_id'] = 'IS NULL';

$close_count    = (!$type || $type == 'close') ? $medecin->countList($where) : 0;
$close_medecins = (!$type || $type == 'close') ? $medecin->loadList($where, 'nom, prenom, cp', "$start,$step") : [];

$smarty = new CSmartyDP();
$smarty->assign("medecins", $medecins);
$smarty->assign("medecins_close", $close_medecins);
$smarty->assign("user_id", $user_id);
$smarty->assign('page', $start);
$smarty->assign("total", ['exact' => $count, 'close' => $close_count]);
$smarty->assign("step", $step);
$smarty->assign("type", $type);
$smarty->display('inc_search_medecin.tpl');
