<?php
/**
 * @package Mediboard\Developpement
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbDT;
use Ox\Core\CView;
use Ox\Mediboard\System\CErrorLog;
use Ox\Mediboard\System\CErrorLogWhiteList;

CCanDo::checkAdmin();

$id = CView::get('error_log_id', 'num');
CView::checkin();
$error_log = new CErrorLog();
$error_log->load($id);

$error_log_whitelist       = new CErrorLogWhiteList();
$error_log_whitelist->hash = $error_log->signature_hash;
$error_log_whitelist->loadMatchingObject();

if ($error_log_whitelist->_id) {
  $error_log_whitelist->delete();
}else{
  $error_log_whitelist->text        = $error_log->text;
  $error_log_whitelist->type        = $error_log->error_type;
  $error_log_whitelist->file_name   = $error_log->file_name;
  $error_log_whitelist->line_number = $error_log->line_number;
  $error_log_whitelist->user_id     = CAppUI::$user->user_id;
  $error_log_whitelist->datetime    = CMbDT::dateTime();
  $error_log_whitelist->count       = 0;

  $msg = $error_log_whitelist->store();

  if ($error_log_whitelist->_id) {
    CAppUI::displayAjaxMsg('CErrorLog.whitelist_added', UI_MSG_OK);
  }
}

CApp::rip();