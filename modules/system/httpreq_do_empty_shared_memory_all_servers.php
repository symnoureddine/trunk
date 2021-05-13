<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CacheManager;
use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;

CCanDo::checkAdmin();

$ips_list   = CView::get('ips_list', 'str');
$cache_keys = CView::get(
  'keys',
  'enum list|' . implode('|', array_keys(CacheManager::$cache_values)).' default|all'
);

$modules = CView::get('modules', 'str default|all');
$modules_keys = implode('|', explode('|', stripslashes($modules)));

CView::checkin();

$get["m"]       = "system";
$get["a"]       = "httpreq_do_empty_shared_memory";
$get["keys"]    = $cache_keys;
$get["modules"] = $modules_keys;
$get["ajax"]    = "1";

if (empty($ips_list)) {
  $ips_list = trim(CAppUI::conf("servers_ip"));
}

$result_send = CApp::multipleServerCall($ips_list, $get, null);

$smarty = new CSmartyDP();
$smarty->assign("result_send", $result_send);
$smarty->display("inc_do_empty_shared_all_servers.tpl");