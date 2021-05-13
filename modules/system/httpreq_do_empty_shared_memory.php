<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CacheManager;
use Ox\Core\CApp;
use Ox\Core\CCanDo;
use Ox\Core\CView;

CCanDo::checkAdmin();

$cache_keys = CView::get(
  'keys',
  'enum list|' . implode('|', array_keys(CacheManager::$cache_values)) . ' default|all'
);

$modules      = CView::get('modules', 'str default|all');
$modules_keys = implode('|', explode('|', stripslashes($modules)));

CView::checkin();

CacheManager::cacheClear($cache_keys, $modules_keys);

CApp::log("Cache Manager clear cache", array(
  'keys'    => $cache_keys,
  'modules' => $modules_keys,
));