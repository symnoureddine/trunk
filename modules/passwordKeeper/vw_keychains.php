<?php
/**
 * @package Mediboard\PasswordKeeper
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\PasswordKeeper\CKeychain;

CKeychain::checkHTTPS();

CCanDo::checkEdit();

$keychain_id = CView::get('keychain_id', 'ref class|CKeychain');
$challenge   = CView::get('challenge', 'bool default|0');

CView::checkin();

$smarty = new CSmartyDP();
$smarty->assign('keychain_id', $keychain_id);
$smarty->assign('challenge', $challenge);
$smarty->display('vw_keychains.tpl');