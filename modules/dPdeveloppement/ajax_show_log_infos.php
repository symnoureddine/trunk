<?php
/**
 * @package Mediboard\Developpement
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CLogger;
use Ox\Core\CView;

CCanDo::checkEdit();
$json = CView::post("json", "str");
CView::checkin();

$json = urldecode($json);
$log  = unserialize($json);

$date    = isset($log['date']) ? $log['date'] : null;
$level   = isset($log['level']) ? $log['level'] : null;
$color   = isset($log['color']) ? $log['color'] : null;
$message = isset($log['message']) ? $log['message'] : null;

if (isset($log['extra_json'])) {
  $extra = json_decode($log['extra_json'], true);
}
else {
  $extra = null;
}

if (isset($log['context_json'])) {
  $context = json_decode($log['context_json'], true);
  $context = CLogger::decodeContext($context);
}
else {
  $context = null;
}

$log_display = array(
  "Date de création" => $date,
  "Level"            => $level,
  "Message"          => $message,
  "Context"          => $context,
  "Extra"            => $extra,
);

foreach ($log_display as $key => $value) {
  echo "<div style='margin:10px;'>";
  echo "<b>{$key} :</b>";
  echo "<pre>" . print_r($value, true) . "</pre>";
  echo "</div>";
}