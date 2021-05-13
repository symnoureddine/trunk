<?php
/**
 * @package Mediboard\Forms
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\Cache;
use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Forms\CExClassRefChecker;

CCanDo::checkAdmin();

$ex_class_id = CView::get('ex_class_id', 'ref class|CExClass notNull');
$del         = CView::get('del', 'bool default|0');

CView::checkin();

if (!$ex_class_id) {
  CAppUI::commonError();
}

$cache = new Cache(CExClassRefChecker::PREFIX, CExClassRefChecker::PRE_TBL . $ex_class_id, Cache::DISTR);
if (!$cache->exists()) {
  CAppUI::stepAjax("Vérification des références non commencée", UI_MSG_ERROR);
}

if ($del) {
  $cache->rem();
  CAppUI::stepAjax("Réinitialisation de la vérification", UI_MSG_OK);
  CApp::rip();
}

$data = $cache->get();
if (!is_countable($data['errors']) || !count($data['errors'])) {
  CAppUI::stepAjax("Aucune erreur pour le fomrulaire", UI_MSG_ERROR);
}

$smarty = new CSmartyDP();
$smarty->assign("data", $data);
$smarty->assign("ex_class_id", $ex_class_id);
$smarty->display("vw_ref_errors");
