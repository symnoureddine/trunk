<?php 
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CMbArray;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Core\DSHM;
use Ox\Core\SHM;

CCanDo::checkAdmin();
$type = CView::get("type", "enum notNull list|shm|dshm");
$key  = CView::get("key", "str notNull");
$key = stripslashes($key);

CView::checkin();

switch ($type) {
  default:
  case "shm":
    $value = SHM::get($key);
    break;
  case "dshm":
    $value = DSHM::get($key);
    break;
}

$smarty = new CSmartyDP();
$smarty->assign('type', $type);
$smarty->assign('key', $key);
$smarty->assign('value', CMbArray::toJSON($value, true, JSON_PRETTY_PRINT));
$smarty->display('inc_vw_cache_entry_value.tpl');