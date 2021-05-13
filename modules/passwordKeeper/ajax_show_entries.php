<?php
/**
 * @package Mediboard\PasswordKeeper
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CValue;
use Ox\Mediboard\PasswordKeeper\CKeychain;

CKeychain::checkHTTPS();

CCanDo::checkEdit();

$_passphrase = CKeychain::getPassphrase();

$keychain_id = CValue::get('keychain_id');

$keychain = new CKeychain();
$keychain->load($keychain_id);

if ($keychain && $keychain->_id) {
  $keychain->needsRead();
  $keychain->checkKeychain($_passphrase);

  $keychain->loadVisibleKeychainEntries();

  $contextes          = array(
    'none' => CAppUI::tr('common-No context'),
  );
  $entries_by_context = array(
    'none' => array(),
  );

  foreach ($keychain->_ref_available_keychain_entries as $_entry) {
    $_entry->loadRefsHyperTextLink();
    $_entry->loadTargetObject();

    $_context = 'none';
    $_view    = CAppUI::tr('common-No context');

    if ($_entry->_ref_object && $_entry->_ref_object->_id) {
      $_context = $_entry->_ref_object->_guid;
      $_view    = $_entry->_ref_object->_view;
    }

    if (!isset($entries_by_context[$_context])) {
      $entries_by_context[$_context] = array();
      $contextes[$_context]          = $_view;
    }

    $entries_by_context[$_context][] = $_entry;
  }

  $smarty = new CSmartyDP();
  $smarty->assign('keychain', $keychain);
  $smarty->assign('entries_by_context', $entries_by_context);
  $smarty->assign('contextes', $contextes);
  $smarty->display('inc_vw_keychain_entries.tpl');
}