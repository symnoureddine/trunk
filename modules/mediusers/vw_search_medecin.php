<?php
/**
 * @package Mediboard\Mediusers
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Mediusers\CMediusers;

CCanDo::checkRead();

$user_id = CView::get('user_id', 'ref class|CMediusers');

CView::checkin();

$mediuser = new CMediusers();

if ($user_id) {
  $mediuser->load($user_id);
  if (!$mediuser->_id) {
    CAppUI::stepAjax('CMediusers.none', UI_MSG_ERROR);
  }
  $mediuser->loadRefUser();
}

$smarty = new CSmartyDP();
$smarty->assign('mediuser', $mediuser);
$smarty->display('vw_search_medecin.tpl');