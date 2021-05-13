<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CacheInfo;
use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Core\DSHM;
use Ox\Core\SHM;

CCanDo::checkAdmin();

$type   = CView::get("type", "enum notNull list|shm|dshm|opcode");
$prefix = CView::get("prefix", "str notNull");

$prefix = stripslashes($prefix);

CView::checkin();


switch ($type) {
  default:
  case "shm":
    $detail = SHM::getKeysInfo($prefix);
    break;
  case "dshm":
    $detail = DSHM::getKeysInfo($prefix);
    break;
  case "opcode":
    $detail = CacheInfo::getOpcodeKeysInfo($prefix);
    break;
}


$smarty = new CSmartyDP();
$smarty->assign("detail", $detail);
$smarty->assign("type", $type);
$smarty->display("inc_vw_cache_detail.tpl");
