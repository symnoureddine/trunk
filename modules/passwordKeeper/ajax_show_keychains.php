<?php
/**
 * @package Mediboard\PasswordKeeper
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Mediboard\PasswordKeeper\CKeychain;

CKeychain::checkHTTPS();

CCanDo::checkEdit();

$keychain  = new CKeychain();
$keychains = $keychain->loadListWithPerms(PERM_READ);

/** @var CKeychain $_keychain */
foreach ($keychains as $_keychain) {
  $_keychain->loadVisibleKeychainEntries();
  $_keychain->loadAbonnement();
}

$smarty = new CSmartyDP();
$smarty->assign('keychains', $keychains);
$smarty->display('inc_keychains.tpl');