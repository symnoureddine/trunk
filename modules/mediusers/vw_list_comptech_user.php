<?php
/**
 * @package Mediboard\Mediusers
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Mediusers\CMediusers;

CCanDo::checkRead();
$user_id = CView::get('user_id', 'ref class|CMediusers notNull');
CView::checkin();

$user = CMediusers::get($user_id);
$user->loadRefsCompteCh();

$smarty = new CSmartyDP();
$smarty->assign('user', $user);
$smarty->display('vw_list_comptech_user');