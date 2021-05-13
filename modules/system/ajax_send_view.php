<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CView;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\System\CViewSender;

CCanDo::checkEdit();

$view_sender_id = CView::get("view_sender_id", "ref class|CViewSender notNull");

CView::checkin();

$user = CUser::get();

$view_sender = new CViewSender();
$view_sender->load($view_sender_id);

if (!$view_sender || !$view_sender->_id) {
  CAppUI::commonError("CViewSender.none");
}

$view_sender->makeUrl();
$filepath = $view_sender->makeFile();

if ($filepath && filesize($filepath) > 0) {
  $view_sender->sendFile();
}
else {
  CAppUI::stepAjax("CViewSender-response-empty", UI_MSG_WARNING);
}

CAppUI::stepAjax("CViewSender-msg-sent", UI_MSG_OK);