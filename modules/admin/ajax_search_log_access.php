<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CStoredObject;
use Ox\Core\CView;
use Ox\Mediboard\Admin\CLogAccessMedicalData;
use Ox\Mediboard\System\CUserAuthentication;

CCanDo::checkEdit();

$date_min     = CView::get('_date_min', 'date', true);
$date_max     = CView::get('_date_max', 'date moreThan|_date_min', true);
$object_class = CView::get('object_class', 'str');
$object_id    = CView::get('object_id', 'ref class|CPatient');
$user_id      = CView::get('user_id', 'ref class|CMediusers');
$start        = (int)CView::get('start', 'num default|0');
$step         = (int)CView::get('step', 'num default|100');
CView::checkin();
CView::enforceSlave();

$log_access = new CLogAccessMedicalData();

$ds = $log_access->getDS();

$where = [];

if ($object_class) {
    $where['object_class'] = $ds->prepare("= ?", $object_class);
}

if ($object_id) {
    $where['object_id'] = $ds->prepare("= ?", $object_id);
}

if ($user_id) {
    $where['user_id'] = "= '$user_id'";
}

if ($date_min) {
    $where[] = $ds->prepare('log_access_medical_data.datetime >= ?', "$date_min 00:00:00");
}

if ($date_max) {
    $where[] = $ds->prepare('log_access_medical_data.datetime <= ?', "$date_max 23:59:59");
}

$limit = "{$start}, {$step}";

/** @var CUserAuthentication[] $user_auths */
$log_accesses = $log_access->loadList($where, 'access_id DESC', $limit);
$total        = $log_access->countList($where);

CStoredObject::massLoadFwdRef($log_accesses, 'object_id');
foreach ($log_accesses as $_log_access) {
    $_log_access->loadTargetObject();
    $_log_access->loadRefUser();
    $_log_access->loadRefGroup();
}

$smarty = new CSmartyDP();
$smarty->assign('start', $start);
$smarty->assign('step', $step);
$smarty->assign('total', $total);
$smarty->assign('log_accesses', $log_accesses);
$smarty->display('../../admin/templates/inc_vw_access_history.tpl');
