<?php
/**
 * @package Mediboard\Ccam
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CValue;
use Ox\Mediboard\SalleOp\CActeCCAM;

$start_time = microtime(true);

CCanDo::checkAdmin();

$date = CValue::get('date');
$step = CValue::get('step', 100);
$codable_class = CValue::get('codable_class');

$start = CValue::getOrSession('start_update_montant', 0);

$act = new CActeCCAM();
$where = array();
$where['execution'] = " > '$date 00:00:00'";
$where['code_association'] = ' > 1';

if ($codable_class) {
  $where['object_class'] = " = '$codable_class'";
}
$total = $act->countList($where);
/** @var CActeCCAM[] $acts */
$acts = $act->loadList($where, 'execution DESC', "$start, $step");

foreach ($acts as $_act) {
  $_act->_calcul_montant_base = 1;
  $_act->store();
}

CValue::setSession('start_update_montant', $start + $step);

$smarty = new CSmartyDP();
$smarty->assign('total', $total);
$smarty->assign('current', $start + $step);
$smarty->display('inc_status_update_montant.tpl');

