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
use Ox\Core\DSHM;
use Ox\Core\SHM;

CCanDo::checkAdmin();

$smarty = new CSmartyDP();
$smarty->assign("shm_global_info",    SHM::getInfo());
$smarty->assign("dshm_global_info",   DSHM::getInfo());
$smarty->assign("opcode_global_info", CacheInfo::getOpcodeCacheInfo());
$smarty->assign("assets_global_info", CacheInfo::getAssetsCacheInfo());
$smarty->display("vw_cache.tpl");
