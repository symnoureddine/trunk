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
use Ox\Core\CView;
use Ox\Mediboard\PasswordKeeper\CKeychain;
use Ox\Mediboard\PasswordKeeper\CKeychainEntry;

CKeychain::checkHTTPS();

CCanDo::checkRead();

$object = mbGetObjectFromGet("object_class", "object_id", "object_guid");

CView::checkin();

/** @var CKeychainEntry[] $entries */
$entries = $object->loadAvailableKeychainEntries();

CStoredObject::massLoadFwdRef($entries, "keychain_id");
CStoredObject::massLoadFwdRef($entries, "object_id");
CStoredObject::massLoadBackRefs($entries, "hypertext_links");

foreach ($entries as $_entry) {
  $_entry->loadKeychain();
  $_entry->loadTargetObject();
  $_entry->loadRefsHyperTextLink();
}

$smarty = new CSmartyDP();
$smarty->assign('entries', $entries);
$smarty->display('inc_tooltip_keychain_entries.tpl');