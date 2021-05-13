<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\Cache;
use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\SHM;

CCanDo::checkRead();

$user = CAppUI::$user;
$latest_cache_key = "$user->_guid-latest_cache";
$latest_cache = SHM::get($latest_cache_key);
foreach($latest_cache["hits"] as &$keys) {
  ksort($keys);
}

//mbTrace($latest_cache["totals"]);

$smarty = new CSmartyDP();
$smarty->assign("all_layers", Cache::$all_layers);
$smarty->assign("latest_cache", $latest_cache);
$smarty->display("latest_cache_hits.tpl");

