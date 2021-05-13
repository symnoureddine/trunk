<?php
/**
 * @package Mediboard\Developpement
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CView;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Mediusers\CMediusers;

CCanDo::checkAdmin();

$userTestName      = CView::post("user_username_0", "str");
$userTestFunction  = CView::post("function_id_0", "ref class|CFunctions");
$userTestProfile   = CView::post("profile_id_0", "ref class|CUser");

$praticienName     = CView::post("user_username_1", "str");
$praticienFunction = CView::post("function_id_1", "ref class|CFunctions");
$praticienProfile  = CView::post("profile_id_1", "ref class|CUser");
CView::checkin();

$group = CGroups::loadCurrent();

if ($praticienFunction) {
  $new_user = new CMediusers();
  $new_user->function_id = $praticienFunction;
  $new_user->_group_id = $group->group_id;
  $new_user->_user_username = $praticienName;
  $new_user->_user_last_name = "CHIR";
  $new_user->_user_first_name = "Test";
  $new_user->_user_password = "P7rxM6Xc";
  $new_user->_user_type = 3;
  $new_user->_profile_id = $praticienProfile;

  if ($msg = $new_user->store()) {
    CAppUI::setMsg($msg, UI_MSG_WARNING);
  }
  else {
    CAppUI::setMsg("{$new_user->_class}-msg-create", UI_MSG_OK);
  }
}

if ($userTestFunction) {
  $new_user = new CMediusers();
  $new_user->function_id = $userTestFunction;
  $new_user->_group_id = $group->group_id;
  $new_user->_user_username = $userTestName;
  $new_user->_user_last_name = 'XUnit';
  $new_user->_user_first_name = 'PHPUnit';
  $new_user->_user_password = null;
  $new_user->_user_type = 1;
  $new_user->_profile_id = $userTestProfile;

  if ($msg = $new_user->store()) {
    CAppUI::setMsg($msg, UI_MSG_WARNING);
  }
  else {
    CAppUI::setMsg("{$new_user->_class}-msg-create", UI_MSG_OK);
  }
}

echo CAppUI::getMsg();

CApp::rip();
