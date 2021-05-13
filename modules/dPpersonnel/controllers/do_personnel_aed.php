<?php
/**
 * @package Mediboard\Personnel
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CDoObjectAddEdit;
use Ox\Core\CView;
use Ox\Mediboard\Admin\CPermObject;
use Ox\Mediboard\Etablissement\CGroups;

$user_id = CView::post('user_id', 'ref class|CMediusers');
CView::checkin();

$group = CGroups::loadCurrent();
CPermObject::loadUserPerms($user_id);
$cand_red_etab = CPermObject::getPermObject($group, PERM_READ, null, $user_id);

if ($cand_red_etab) {
  $do = new CDoObjectAddEdit("CPersonnel", "personnel_id");
  $do->doIt();
} else {
  CAppUI::setMsg(CAppUI::tr("CPersonnel-msg-This person does not have the rights to be created in this establishment"), UI_MSG_ERROR);
  echo CAppUI::getMsg();
  CApp::rip();
}
