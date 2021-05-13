<?php
/**
 * @package Mediboard\PasswordKeeper
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CValue;
use Ox\Mediboard\PasswordKeeper\CKeychain;

CKeychain::checkHTTPS();

CCanDo::checkEdit();

$_passphrase = CKeychain::getPassphrase();

$keychain_id = CValue::post('keychain_id');

$keychain = new CKeychain();
$keychain->load($keychain_id);

if ($keychain && $keychain->_id) {
  $keychain->needsRead();
  $keychain->checkKeychain($_passphrase);

  $smarty = new CSmartyDP();
  $smarty->assign('keychain', $keychain);
  $smarty->display('vw_keychain_entries.tpl');
}