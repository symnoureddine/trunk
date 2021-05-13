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
use Ox\Core\CView;
use Ox\Mediboard\Admin\CUser;

CCanDo::checkAdmin();

$user_id = CView::post('user_id', 'ref class|CUser notNull');

CView::checkin();

$mb_user = new CUser();
$mb_user->load($user_id);

if (!$mb_user || !$mb_user->_id) {
  CAppUI::commonError();
}

// Teh teh teh
if ($mb_user->isSuperAdmin()) {
  CAppUI::commonError();
}

$url = $mb_user->generateActivationToken();

CAppUI::callbackAjax('window.prompt', CAppUI::tr('common-msg-Here is your account activation link :'), $url);

CApp::rip();
