<?php
/**
 * @package Mediboard\PasswordKeeper
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CStoredObject;
use Ox\Core\CValue;
use Ox\Mediboard\PasswordKeeper\CKeychain;
use Ox\Mediboard\PasswordKeeper\CKeychainEntry;

CKeychain::checkHTTPS();

CCanDo::checkEdit();

$_passphrase = CKeychain::getPassphrase();

$keychain_id = CValue::post('keychain_id');
$entry_id    = CValue::post('entry_id');
$object_guid = CValue::post('object_guid');

$keychain = new CKeychain();
$keychain->load($keychain_id);

if ($keychain && $keychain->_id) {
  $keychain->checkKeychain($_passphrase);

  $entry = new CKeychainEntry();
  $entry->load($entry_id);
  $entry->needsEdit();

  if (!$entry->_id) {
    $entry->keychain_id = $keychain->_id;
    $entry->public      = $keychain->public;

    if ($object_guid && $object_guid != 'none') {
      $object = CStoredObject::loadFromGuid($object_guid);

      if ($object && $object->_id) {
        $entry->setObject($object);
      }
    }
  }

  $entry->loadTargetObject();
  $entry->loadRefsNotes();
  $entry->loadRefsHyperTextLink();

  $smarty = new CSmartyDP();
  $smarty->assign('keychain', $keychain);
  $smarty->assign('entry', $entry);
  $smarty->display('vw_edit_entry.tpl');
}