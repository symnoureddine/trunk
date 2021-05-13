<?php
/**
 * @package Mediboard\OpenData
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbArray;
use Ox\Core\CView;
use Ox\Mediboard\Files\CFile;
use Ox\Mediboard\OpenData\CImportConflict;
use Ox\Mediboard\OpenData\CMedecinImport;

CCanDo::checkEdit();

$count        = CView::get('count', 'enum list|' . implode('|', CMedecinImport::$import_counts) . ' notNull');
$last_id      = CView::get('last_id', 'num min|0 default|0');
$dry_run      = CView::get('dry_run', 'bool');
$continue     = CView::get('continue', 'bool');
$no_update    = CView::get('no_update', 'bool');
$version      = CView::get('version', 'str');
$type         = CView::get('type', 'enum list|all|rpps|adeli default|all');
$default_cp   = CView::get('default_cp', 'str');
$cp_mandatory = CView::get('cp_mandatory', 'bool');

CView::checkin();

$start_time = microtime(true);

$files = glob(rtrim(CFile::$directory, '/\\') . '/upload/' . CMedecinImport::MEDECIN_FILE_NAME . '_*.txt');

$file_path = reset($files);

if (!is_file($file_path)) {
  CAppUI::stepAjax('CMedecinImport-no-file', UI_MSG_ERROR);
}

$size = filesize($file_path);

$cps = preg_split("/\s*[\s\|,]\s*/", $default_cp);
CMbArray::removeValue("", $cps);

$last_id = ($last_id != "") ? $last_id : 0;

$medecin_import = new CMedecinImport($file_path, $last_id, $dry_run, !$no_update, $version, $type, $cps, $cp_mandatory);
$last_line      = $medecin_import->parseMedecinFile($count); // Check if it's the last line or not


if ($dry_run) {
  CAppUI::setMsg("CMedecinImport-audit", UI_MSG_OK);
}

echo CAppUI::getMsg();

$next            = $last_id + $count;
$import_conflict = new CImportConflict();

$conflicts_audit = $import_conflict->getCountConflictsByObject('CMedecin', 'import_rpps', true);
$conflicts       = $import_conflict->getCountConflictsByObject('CMedecin', 'import_rpps', false);

$total_nb_conflicts_audit         = ($conflicts_audit) ? array_sum(CMbArray::pluck($conflicts_audit, 'total')) : 0;
$total_nb_conflicts               = ($conflicts) ? array_sum(CMbArray::pluck($conflicts, 'total')) : 0;
$total_nb_conflicts_medecin_audit = count($conflicts_audit);
$total_nb_conflicts_medecin       = count($conflicts);

CAppUI::js(
  "ImportMedecins.updateDisplay('$last_line', '$total_nb_conflicts_audit', '$total_nb_conflicts',"
  . " '$total_nb_conflicts_medecin_audit', '$total_nb_conflicts_medecin')"
);

$time = microtime(true) - $start_time;

$medecin_import->setStatsInSHM($time); // Set the stats

$actual_stats = $medecin_import->getStats();
$nb_news      = $actual_stats['nb_news'];
$nb_exists    = $actual_stats['nb_exists'];
$nb_conflicts = $actual_stats['nb_exists_conflict'];
$nb_used      = $actual_stats['nb_exists_used'];
$nb_unused    = $actual_stats['nb_exists_unused'];
$nb_rpss      = $actual_stats['nb_rpps_ignored'];
$nb_tel_error = $actual_stats['nb_tel_error'];

// Update the display of the stats
CAppUI::js(
  "ImportMedecins.updateStats('$nb_news', '$nb_exists', '$nb_conflicts', '$nb_used', '$nb_unused', '$nb_rpss', $time, "
  . " '$nb_tel_error')"
);

if (!$last_line) {
  CAppUI::stepAjax("CMedecinImport-end", UI_MSG_OK);
}
else {
  CMedecinImport::setStartOffset($last_line);
  CAppUI::js('setTimeout("ImportMedecins.nextImport()", 2000)');
}

