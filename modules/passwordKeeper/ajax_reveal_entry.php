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
use Ox\Mediboard\PasswordKeeper\CKeychainEntry;

CKeychain::checkHTTPS();

CCanDo::checkEdit();

$_passphrase = CKeychain::getPassphrase();

$entry_id = CValue::post('entry_id');

$entry = new CKeychainEntry();
$entry->load($entry_id);

if ($entry && $entry->_id) {
  $entry->needsRead();

  $keychain = $entry->loadKeychain();
  $keychain->checkKeychain($_passphrase);

  $revealed = $entry->getPassword($_passphrase);

  $smarty = new CSmartyDP();
  $smarty->assign('revealed', $revealed);
  $smarty->display('vw_revealed_entry.tpl');
}