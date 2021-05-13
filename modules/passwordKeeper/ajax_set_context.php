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
use Ox\Core\CStoredObject;
use Ox\Core\CValue;
use Ox\Mediboard\PasswordKeeper\CKeychain;
use Ox\Mediboard\PasswordKeeper\CKeychainEntry;

CKeychain::checkHTTPS();

CCanDo::checkRead();

$object_guid = CValue::get('object_guid');

$object = CStoredObject::loadFromGuid($object_guid);

if (!$object || !$object->_id) {
  CAppUI::stepAjax('common-error-Invalid object', UI_MSG_ERROR);
}

$keeper  = new CKeychain();
$keepers = $keeper->loadListWithPerms(PERM_READ);

$entry         = new CKeychainEntry();
$entry->public = '1';
$entry->setObject($object);

$smarty = new CSmartyDP();
$smarty->assign('object', $object);
$smarty->assign('keychains', $keepers);
$smarty->assign('entry', $entry);
$smarty->display('vw_available_keychains.tpl');