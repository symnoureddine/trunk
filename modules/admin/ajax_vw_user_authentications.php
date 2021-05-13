<?php
/**
 * @package Mediboard\Admin
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CStoredObject;
use Ox\Core\CView;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\System\CUserAuthentication;

CCanDo::checkEdit();

// Récuperation de l'utilisateur sélectionné
$user_id = CView::get("user_id", "ref class|CMediusers");
$start   = CView::get("start", "num");

CView::checkin();

$user = CUser::get($user_id);
$user->countConnections();

$user_authentication = new CUserAuthentication();
$ds = $user_authentication->getDS();

$where = array(
  "user_id" => $ds->prepare("= ?", $user_id),
);

$limit = intval($start).", 30";
$list = $user_authentication->loadList($where, "datetime_login DESC", $limit);
CStoredObject::massLoadFwdRef($list, 'user_agent_id');

foreach ($list as $_list) {
  /** @var CUserAuthentication $_list */
  $_list->loadRefUserAgent();
}

$smarty = new CSmartyDP();

$smarty->assign("list", $list);
$smarty->assign("user", $user);

$smarty->display("inc_vw_user_authentications.tpl");
