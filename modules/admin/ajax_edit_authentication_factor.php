<?php
/**
 * @package Mediboard\Admin
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Admin\CAuthenticationFactor;
use Ox\Mediboard\Admin\CUser;

CCanDo::check();

$factor_id = CView::get('factor_id', 'ref class|CAuthenticationFactor');

CView::checkin();

$authentication_factor = new CAuthenticationFactor();
$authentication_factor->load($factor_id);
$authentication_factor->needsEdit();

if (!$authentication_factor->_id) {
  $authentication_factor->user_id = CUser::get()->_id;
  $authentication_factor->type    = 'email';
  $authentication_factor->enabled = '0';
  $authentication_factor->setNextPriority();
}

$authentication_factor->loadRefsNotes();

$smarty = new CSmartyDP();
$smarty->assign('authentication_factor', $authentication_factor);
$smarty->display('edit_authentication_factor.tpl');