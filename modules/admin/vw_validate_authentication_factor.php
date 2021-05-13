<?php
/**
 * @package Mediboard\Admin
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbException;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Admin\CAuthenticationFactor;

CCanDo::check();

$factor_id = CView::get('factor_id', 'ref class|CAuthenticationFactor notNull');
$callback  = CView::get('callback', 'str default|login');
$send      = CView::post('send', 'bool default|0');

CView::checkin();

$authentication_factor = new CAuthenticationFactor();
$authentication_factor->load($factor_id);
$authentication_factor->needsEdit();

if (!$authentication_factor->_id) {
  CAppUI::commonError();
}

if ($callback == 'enable') {
  $authentication_factor->_is_enabling = true;
}

if ($send) {
  try {
    $authentication_factor->sendValidationCode();
  }
  catch (CMbException $e) {
    CAppUI::stepAjax($e->getMessage(), UI_MSG_ERROR);
  }
}

$user                        = $authentication_factor->loadRefUser();
$factors                     = $user->loadRefAuthenticationFactors(false);
$next_authentication_factors = array();

if (isset($factors[$authentication_factor->_id])) {
  unset($factors[$authentication_factor->_id]);
  $next_authentication_factors = $factors;
}

$smarty = new CSmartyDP('modules/admin');
$smarty->assign('authentication_factor', $authentication_factor);
$smarty->assign('next_authentication_factors', $next_authentication_factors);
$smarty->assign('callback', $callback);
$smarty->display('vw_validate_authentication_factor.tpl');