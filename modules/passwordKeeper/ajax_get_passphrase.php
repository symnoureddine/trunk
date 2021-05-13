<?php
/**
 * @package Mediboard\PasswordKeeper
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CValue;
use Ox\Mediboard\PasswordKeeper\CKeychain;

CKeychain::checkHTTPS();

CCanDo::checkEdit();

$keychain_id = CValue::post('keychain_id');

$keychain = new CKeychain();
$keychain->load($keychain_id);

$passphrase = '';
if ($keychain && $keychain->_id) {
  $keychain->needsRead();

  $_passphrase = CKeychain::getPassphrase();

  if ($_passphrase && $keychain->checkPassphrase($_passphrase)) {
    $passphrase = $_passphrase;
  }
}

echo json_encode(utf8_encode($passphrase));