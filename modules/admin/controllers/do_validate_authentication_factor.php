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
use Ox\Core\CMbString;
use Ox\Core\CView;
use Ox\Mediboard\Admin\CAuthenticationFactor;

CCanDo::check();

$factor_id       = CView::post('factor_id', 'ref class|CAuthenticationFactor notNull');
$validation_code = trim(CMbString::upper(CView::post('_validation_code', 'str notNull')));
$callback        = CView::post('callback', 'str notNull default|login');

CView::checkin();

$authentication_factor = new CAuthenticationFactor();
$authentication_factor->load($factor_id);
$authentication_factor->needsEdit();

if ($callback == 'enable') {
  if ($authentication_factor->isEnabled()) {
    CAppUI::stepAjax('CAuthenticationFactor-error-Object is already enabled', UI_MSG_ERROR);
  }

  $authentication_factor->_is_enabling = true;
}

try {
  if (!$authentication_factor->validateCode($validation_code)) {
    $authentication_factor->incrementAttempts();

    switch ($callback) {
      case 'login':
//        // Max attempts have been reached
//        if (!$authentication_factor->checkAttempts()) {
//          // Locking account
//          $user                    = $authentication_factor->loadRefUser();
//          $user->user_login_errors = 100;
//          $user->store();
//
//          // End current session
//          CSessionHandler::start();
//          CSessionHandler::end(true);
//        }

        CAppUI::callbackAjax('window.location.reload');
        break;

      case 'enable':
        CAppUI::callbackAjax('Control.Modal.refresh');
        break;

      default:
    }

    CAppUI::stepAjax('CAuthenticationFactor-error-Invalid code', UI_MSG_ERROR);
  }
}
catch (CMbException $e) {
  CAppUI::stepAjax($e->getMessage(), UI_MSG_ERROR);
}

switch ($callback) {
  case 'login':
    CAppUI::$instance->_authentication_factor_success = true;
    CAppUI::stepAjax('CAuthenticationFactor-msg-Validity code accepted', UI_MSG_OK);

    CAppUI::callbackAjax('AuthenticationFactor.reloadPage');
    break;

  case 'enable':
    if ($msg = $authentication_factor->enableAuthenticationFactor()) {
      CAppUI::stepAjax($msg, UI_MSG_ERROR);
    }

    CAppUI::callbackAjax('Control.Modal.close');
    CAppUI::callbackAjax('AuthenticationFactor.urlEdit.refreshModal');
    CAppUI::stepAjax('CAuthenticationFactor-msg-Object enabled', UI_MSG_OK);
    break;

  default:
}

CApp::rip();