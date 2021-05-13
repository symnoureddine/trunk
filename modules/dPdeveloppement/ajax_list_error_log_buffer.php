<?php
/**
 * @package Mediboard\Developpement
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CError;
use Ox\Core\CMbPath;
use Ox\Core\CMbString;
use Ox\Core\CSmartyDP;

CCanDo::checkRead();

$paths = CError::globWaitingBuffer();
$array_mapped = array_map('filemtime', $paths);
array_multisort($array_mapped, SORT_NUMERIC, SORT_DESC, $paths);

$files = array();
foreach ($paths as $path) {
  $files[$path] = [
    'name' => basename($path),
    'time' => filemtime($path),
    'size' => CMbString::toDecaBinary(filesize($path)),
    'lines' => CMbPath::countLines($path)
  ];
}

// Création du template
$smarty = new CSmartyDP();
$smarty->assign("files", $files);
$smarty->display('inc_list_error_log_buffer.tpl');