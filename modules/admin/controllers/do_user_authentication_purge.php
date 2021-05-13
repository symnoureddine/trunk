<?php
/**
 * @package Mediboard\Admin
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CRequest;
use Ox\Core\CValue;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\System\CUserAuthentication;

CCanDo::checkAdmin();

$user_id = CValue::post("user_id");

$user = CUser::get($user_id);

if (!$user->_id || !$user->dont_log_connection) {
  CAppUI::stepAjax("CUser-msg-Cannot purge real user authentications", UI_MSG_ERROR);
}

$auth = new CUserAuthentication();
$ds = $auth->getDS();

$request = new CRequest();

$where = array(
  "user_id" => $ds->prepare("=?", $user_id),
);
$request->addWhere($where);
$request->setLimit(10000);

$query = $request->makeDelete($auth);

$ds->exec($query);

CAppUI::stepAjax("CUser-msg-%d authentications deleted", UI_MSG_OK, $ds->affectedRows());

CApp::rip();

