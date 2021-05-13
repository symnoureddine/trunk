<?php
/**
 * @package Mediboard\Forms
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Forms\CExClassRefChecker;
use Ox\Mediboard\System\Forms\CExClass;
use Ox\Mediboard\System\Forms\CExObject;

CCanDo::checkAdmin();

$ex_class = new CExClass();
$ds = $ex_class->getDS();
$ex_classes = $ex_class->loadList(['group_id' => $ds->prepare("= ?", CGroups::loadCurrent()->_id)]);

$ex_class_check = CExClassRefChecker::getKeys($ex_classes);

CExObject::checkLocales();

$smarty = new CSmartyDP();
$smarty->assign('ex_classes', $ex_classes);
$smarty->assign('ex_class_check', $ex_class_check);
$smarty->assign('prefix', CExClassRefChecker::PREFIX);
$smarty->assign('pre_tbl', CExClassRefChecker::PRE_TBL);
$smarty->display('vw_ref_checker');
