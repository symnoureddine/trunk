<?php
/**
 * @package Mediboard\Astreintes
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Mediusers\CMediusers;

CCanDo::checkRead();

$user_id = CView::get("user_id", "ref class|CMediusers");
CView::checkin();

$muser = new CMediusers();
$muser->load($user_id);

$user = $muser->loadRefUser();
$muser->loadRefFunction()->loadRefGroup();

$phones = array();
addPhone("CUser-user_astreinte", $user->user_astreinte, $phones);
addPhone("CUser-user_astreinte_autre", $user->user_astreinte_autre, $phones);
addPhone("CUser-user_mobile", $user->user_mobile, $phones);
addPhone("CUser-user_phone", $user->user_phone, $phones);
addPhone("CFunctions-tel", $muser->_ref_function->tel, $phones);

function addPhone($field_str, $field, &$phones) {
  if ($field && !in_array($field, $phones)) {
    $phones[$field_str] = $field;
  }
}

//smarty
$smarty = new CSmartyDP();
$smarty->assign("phones", $phones);
$smarty->assign("user", $user);
$smarty->display("inc_list_phones");