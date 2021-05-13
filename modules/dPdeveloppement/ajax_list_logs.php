<?php
/**
 * @package Mediboard\Developpement
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CCanDo;
use Ox\Core\CLogger;
use Ox\Core\CMbPath;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;

CCanDo::checkRead();

$file           = CApp::getPathMediboardLog();
$file_grep      = str_replace(".log", ".grep.log", $file);
$log_start      = CView::get("log_start", "str");
$grep_search    = CView::get("grep_search", "str");
$grep_regex     = CView::get("grep_regex", "bool default|0");
$grep_sensitive = CView::get("grep_sensitive", "bool default|0");
$session_grep   = isset($_SESSION['dPdeveloppement_log_grep']) ? $_SESSION['dPdeveloppement_log_grep'] : "";
$time_start     = microtime(true);
$words          = array();

if ($grep_search) {
  if ($grep_search != $session_grep || true) {
    // new grep file
    $_SESSION['dPdeveloppement_log_grep'] = $grep_search;

    $cmd = "grep ";
    if (!$grep_sensitive) {
      $cmd .= " -i ";
    }

    if (!$grep_regex) {
      if (strpos($grep_search, " ") !== false) {
        $words = array_unique(explode(" ", $grep_search));
      }
      else {
        $words = array($grep_search);
      }

      $cmd_repeat = ' | ' . $cmd;
      foreach ($words as $key => $_word) {
        $_word = str_replace(".", "\.", $_word);
        $_word = str_replace("[", "\[", $_word);
        $_word = str_replace("]", "\]", $_word);

        if ($key === 0) {
          $cmd .= '"' . $_word . '"' . ' ' . $file;
        }
        else {
          $cmd .= $cmd_repeat . '"' . $_word . '"';
        }
      }
    }
    else {
      $cmd .= " \"{$grep_search}\" {$file} ";
    }

    $cmd .= " > {$file_grep}";
    shell_exec($cmd);
  }

  $file = $file_grep;
}
else {
  $_SESSION['dPdeveloppement_log_grep'] = $grep_search;
}

CView::checkin();

$nb_lines     = 1000;
$logs_display = array();

$logs = CMbPath::tailWithSkip($file, $nb_lines, $log_start);
$logs = explode("\n", $logs);
$logs = array_reverse($logs);
$logs = array_filter($logs);

$nb_logs = count($logs);

$exec_time = microtime(true) - $time_start;
$exec_time = round($exec_time, 3) * 1000;

foreach ($logs as $_key => $_log) {
  // init
  $parsed_log = array(
    'date'         => null,
    'level'        => null,
    'color'        => null,
    'message'      => null,
    'context'      => '[]',
    'context_json' => '[]',
    'extra'        => '[]',
    'extra_json'   => '[]',
    'infos'        => null,
  );

  // level date color
  foreach (CLogger::getLevelsColors() as $_key_color => $_color) {
    $_s         = strtoupper('[' . $_key_color . ']');
    $_pos_level = strpos($_log, $_s);
    if ($_pos_level !== false) {
      $parsed_log['level'] = trim($_s);
      $parsed_log['date']  = trim(substr($_log, 0, $_pos_level));
      $parsed_log['color'] = $_color;
      break;
    }
  }
  $log_sub = substr($_log, strlen($parsed_log['date'] . ' ' . $parsed_log['level']));

  // extra
  $extra_pos                = strpos($log_sub, '[extra:');
  $extra_json               = substr($log_sub, $extra_pos + 7, -1);
  $parsed_log['extra_json'] = $extra_json;
  $parsed_log['extra']      = strlen($extra_json) > 2 ? "[extra:" . strlen($extra_json) . "]" : "";
  $log_sub                  = substr($log_sub, 0, $extra_pos);

  // context
  $context_pos                = strpos($log_sub, '[context:');
  $context_json               = substr($log_sub, $context_pos + 9, -2);
  $parsed_log['context_json'] = $context_json;
  $parsed_log['context']      = strlen($context_json) > 2 ? "[context:" . strlen($context_json) . "]" : "";
  $log_sub                    = substr($log_sub, 0, $context_pos);

  // message
  $parsed_log['message'] = trim($log_sub);

  // infos
  $parsed_log['infos'] = urlencode(serialize($parsed_log));

  $logs_display[] = $parsed_log;
}

// hightlight
if (!$grep_regex && !empty($words)) {
  foreach ($logs_display as $_key_log => &$_log) {
    foreach ($words as $_word) {
      $_log['date']    = highlight($_word, $_log['date']);
      $_log['level']   = highlight($_word, $_log['level']);
      $_log['message'] = highlight($_word, $_log['message']);

      if ($_log['context_json'] !== highlight($_word, $_log['context_json'])) {
        $_log['context'] = highlight($_log['context'], $_log['context']);
      }

      if ($_log['extra_json'] !== highlight($_word, $_log['extra_json'])) {
        $_log['extra'] = highlight($_log['extra'], $_log['extra']);
      }
    }
  }
}

/**
 * @param $word
 * @param $subject
 *
 * @return string|string[]|null
 */
function highlight($word, $subject) {
  $pos = stripos($subject, $word);

  if ($pos === false) {
    return $subject;
  }

  $replace = substr($subject, $pos, strlen($word));

  return str_ireplace($word, '<span style="background-color:yellow">' . $replace . '</span>', $subject);
}


// template
$smarty = new CSmartyDP();
$smarty->assign("logs", $logs_display);
$smarty->assign("nb_logs", $nb_logs);
$smarty->assign("exec_time", $exec_time);
$smarty->display('inc_list_logs.tpl');
