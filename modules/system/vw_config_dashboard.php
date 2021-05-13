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
use Ox\Core\SHM;
use Ox\Mediboard\System\CTableStatus;

CCanDo::checkAdmin();

$shm_infos    = SHM::getInfo();
$config_infos = SHM::getKeysInfo('config');

$modules = array();
foreach ($config_infos as $_key => $_infos) {
  $key     = explode('-', $_key);
  $_prefix = $key[0];
  $_mod    = $key[1];

  if ($_prefix == 'module' && !array_key_exists($_mod, $modules)) {
    $table_status       = new CTableStatus();
    $table_status->name = 'configuration-' . $_mod;
    $table_status->loadMatchingObjectEsc();

    $modules[$_mod] = array(
      'size'           => $_infos['size'],
      'hash'           => array(),
      'last_db_update' => $table_status->update_time,
      'contexts'       => array('global' => array()),
    );
  }
  elseif ($_prefix == 'values' && array_key_exists($_mod, $modules)) {
    $modules[$_mod]['size']           += $_infos['size'];

    if (isset($key[2]) && $key[2] == 'global') {
      $hash = md5(serialize(SHM::get('config-' . $_key)));
      $modules[$_mod]['contexts']['global'] = array($hash);
      $modules[$_mod]['hash'][] = $hash ;
    }
    elseif (isset($key[2]) && $key[2] != '__HOSTS__') {
      if (!array_key_exists($key[2], $modules[$_mod]['contexts'])) {
        $modules[$_mod]['contexts'][$key[2]] = array();
      }

      $hash = md5(serialize(SHM::get('config-' . $_key)));
      $modules[$_mod]['contexts'][$key[2]][$key[3]] = $hash;
      $modules[$_mod]['hash'][] = $hash;
    }
  }
}

foreach ($modules as $_mod => &$_infos) {
  CMbArray::naturalSort($_infos['hash']);

  $_infos['hash'] = md5(serialize($_infos['hash']));
}

$smarty = new CSmartyDP();
$smarty->assign('config_shm_size', $shm_infos['entries_by_prefix']['config']['size']);
$smarty->assign('modules', $modules);
$smarty->display('vw_config_dashboard');