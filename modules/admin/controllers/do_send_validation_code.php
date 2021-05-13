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
use Ox\Core\CMbException;
use Ox\Core\CView;
use Ox\Mediboard\Admin\CAuthenticationFactor;

CCanDo::check();

$factor_id = CView::post('factor_id', 'ref class|CAuthenticationFactor notNull');

CView::checkin();

$authentication_factor = new CAuthenticationFactor();
$authentication_factor->load($factor_id);
$authentication_factor->needsEdit();

$authentication_factor->_is_resending = true;

try {
  $authentication_factor->sendValidationCode();
  CAppUI::setMsg('CAuthenticationFactor-msg-Validation code resent', UI_MSG_OK);
}
catch (CMbException $e) {
  CAppUI::stepAjax($e->getMessage(), UI_MSG_ERROR);
}

echo CAppUI::getMsg();

CApp::rip();