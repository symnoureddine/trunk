<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CacheManager;
use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;

CCanDo::checkAdmin();

$cache_keys = CacheManager::$cache_values;
$modules_cache = array();
$servers_ip = preg_split("/\s*,\s*/", CAppUI::conf("servers_ip"), -1, PREG_SPLIT_NO_EMPTY);

try {
  $module_cache_classes = CacheManager::getModuleCacheClasses();
  foreach ($module_cache_classes as $module_cache_class) {
    $module_cache = new $module_cache_class;
    $modules_cache[$module_cache->module] = array(
      "class"    => $module_cache_class
    );
  }
} catch (Exception $e) {
  CAppUI::displayAjaxMsg($e->getMessage(), UI_MSG_WARNING);
}

$smarty = new CSmartyDP();
$smarty->assign("cache_keys", $cache_keys);
$smarty->assign("modules_cache", $modules_cache);
$smarty->assign("servers_ip", $servers_ip);
$smarty->display("view_cache.tpl");