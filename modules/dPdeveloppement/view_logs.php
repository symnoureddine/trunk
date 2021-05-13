<?php
/**
 * @package Mediboard\Developpement
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CCanDo;
use Ox\Core\CError;
use Ox\Core\CMbDT;
use Ox\Core\CMbPath;
use Ox\Core\CMbString;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\System\CErrorLog;

CCanDo::checkRead();

// Error (db)
$spec_error_type = array(
  "str",
  "default" => array()
);

$error_type = CView::get("error_type", $spec_error_type, true);
$text       = CView::get("text", "str", true);
$server_ip  = CView::get("server_ip", "str", true);

$spec_datetime_min = array(
  "dateTime",
  "default" => CMbDT::dateTime("-1 WEEK")
);

$datetime_min  = CView::get("_datetime_min", $spec_datetime_min, true);
$datetime_max  = CView::get("_datetime_max", "dateTime", true);
$order_by      = CView::get("order_by", "enum list|date|quantity", true);
$group_similar = CView::get("group_similar", "enum list|similar|signature|no default|similar", true);
$user_id       = CView::get("user_id", "ref class|CMediusers", true);
$human         = CView::get("human", "bool", true);
$robot         = CView::get("robot", "bool", true);

CView::checkin();

$error_log                = new CErrorLog();
$error_log->text          = $text;
$error_log->server_ip     = $server_ip;
$error_log->_datetime_min = $datetime_min;
$error_log->_datetime_max = $datetime_max;

// Error (buffer)
$files_error_buffer     = CError::globWaitingBuffer();
$count_error_log_buffer = CError::countWaitingBuffer();

// log (file)
$log_size       = 0;
$file           = CApp::getPathMediboardLog();
$first_log_date = null;
$last_log_date  = null;

if (file_exists($file)) {
  // Last logs
  $logs          = CMbPath::tailCustom($file, 1);
  $logs          = explode("\n", $logs);
  $logs          = is_array($logs) ? $logs : array();
  $last_log      = $logs[0];
  $pos           = strpos($last_log, ']');
  $last_log_date = substr($last_log, 1, $pos - 1);
  // first log
  $handle = fopen($file, "r");
  if ($handle) {
    $line           = fgets($handle);
    $pos            = strpos($line, ']');
    $first_log_date = substr($line, 1, $pos - 1);
    $log_size       = filesize($file);
    fclose($handle);
  }
}


// Récupération de la liste des utilisateurs disponibles
$user           = new CUser();
$user->template = "0";
$order          = "user_last_name, user_first_name";
$list_users     = $user->loadMatchingList($order);

// Recherche dans les logs
$enable_grep = stripos(PHP_OS, "WIN") === 0 ? false : true;

// Création du template
$smarty = new CSmartyDP();
$smarty->assign("error_log", $error_log);
$smarty->assign("count_error_log_buffer", $count_error_log_buffer);
$smarty->assign("error_type", $error_type);
$smarty->assign("server_ip", $server_ip);
$smarty->assign("order_by", $order_by);
$smarty->assign("group_similar", $group_similar);
$smarty->assign("error_types", CError::getErrorTypesByCategory());
$smarty->assign("user_id", $user_id);
$smarty->assign("list_users", $list_users);
$smarty->assign("human", $human);
$smarty->assign("robot", $robot);
$smarty->assign("first_log_date", $first_log_date);
$smarty->assign("last_log_date", $last_log_date);
$smarty->assign("log_size", CMbString::toDecaBinary($log_size));
$smarty->assign("log_file_path", $file);
$smarty->assign("enable_grep", $enable_grep);
$smarty->display('view_logs.tpl');

