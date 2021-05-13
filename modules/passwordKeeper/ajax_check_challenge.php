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
use Ox\Core\CView;
use Ox\Mediboard\PasswordKeeper\CKeychain;

CKeychain::checkHTTPS();

CCanDo::checkEdit();

$keychain_id = CView::get('keychain_id', 'ref class|CKeychain notNull');

CView::checkin();

$keychain = new CKeychain();
$keychain->load($keychain_id);

if (!$keychain || !$keychain->_id) {
  CAppUI::commonError();
}

$keychain->needsRead();
$keychain->loadUserChallenge();

$smarty = new CSmartyDP();
$smarty->assign('keychain', $keychain);
$smarty->display('inc_check_challenge.tpl');