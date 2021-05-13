<?php
/**
 * @package Mediboard\PasswordKeeper
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbDT;
use Ox\Core\CView;
use Ox\Mediboard\PasswordKeeper\CKeychain;

CKeychain::checkHTTPS();

CCanDo::checkEdit();

$_passphrase = CKeychain::getPassphrase();
$keychain_id = CView::post('keychain_id', 'ref class|CKeychain notNull');

CView::checkin();

$keychain = new CKeychain();
$keychain->load($keychain_id);

if (!$keychain || !$keychain->_id) {
  CAppUI::commonError();
}

$keychain->needsRead();

$keychain->checkKeychain($_passphrase);

$challenge                    = $keychain->loadUserChallenge();
$challenge->last_success_date = CMbDT::dateTime();

if ($msg = $challenge->store()) {
  CAppUI::setMsg($msg, UI_MSG_ERROR);
}
else {
  CAppUI::setMsg("{$challenge->_class}-msg-modify", UI_MSG_OK);
  CAppUI::callbackAjax('Control.Modal.close');
}

echo CAppUI::getMsg();
CApp::rip();
