<?php
/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CModelObject;
use Ox\Core\Module\CModule;
use Ox\Core\CStoredObject;
use Ox\Core\CView;
use Ox\Core\Handlers\Facades\HandlerManager;
use Ox\Mediboard\Patients\CPatientXMLImport;
use Ox\Mediboard\Sync\CSyncHandler;

CCanDo::checkAdmin();

$step                      = CView::post("step", "num default|100");
$start                     = CView::post("start", "num default|0");
$directory                 = CView::post("directory", "str notNull");
$files_directory           = CView::post("files_directory", "str");
$update_data               = CView::post("update_data", "str");
$patient_id                = CView::post("patient_id", "num");
$link_files_to_op          = CView::post("link_files_to_op", "str");
$correct_files             = CView::post("correct_files", "str");
$handlers                  = CView::post("handlers", "str");
$patients_only             = CView::post("patients_only", "str");
$date_min                  = CView::post("date_min", "date");
$date_max                  = CView::post("date_max", "date");
$uf_replace                = CView::post("uf_replace", "str");
$keep_sync                 = CView::post("keep_sync", "str");
$ignore_classes            = CView::post("ignore_classes", "str");
$no_update_patients_exists = CView::post("no_update_patients_exists", "str default|0");
$ipp_tag                   = CView::post("ipp_tag", "str");
$import_presc              = CView::post("import_presc", "str default|0");
$exclude_duplicate         = CView::post("exclude_duplicate", "str default|0");

if (!is_dir($directory)) {
  CAppUI::stepAjax("'%s' is not a directory", UI_MSG_WARNING, $directory);

  return;
}

if ($files_directory && !is_dir($files_directory)) {
  CAppUI::stepAjax("'%s' is not a directory", UI_MSG_WARNING, $files_directory);

  return;
}

$directory       = str_replace("\\\\", "\\", $directory);
$files_directory = str_replace("\\\\", "\\", $files_directory);

CView::setSession("step", $step);
CView::setSession("start", $start);
CView::setSession("directory", $directory);
CView::setSession("files_directory", $files_directory);
CView::setSession("update_data", $update_data);
CView::setSession("patient_id", $patient_id);
CView::setSession("link_files_to_op", $link_files_to_op);
CView::setSession("correct_files", $correct_files);
CView::setSession("handlers", $handlers);
CView::setSession("patients_only", $patients_only);
CView::setSession("date_min", $date_min);
CView::setSession("date_max", $date_max);

CView::checkin();

CApp::setTimeLimit(600);
CApp::setMemoryLimit("4096M");

$step = min($step, 1000);

if (!$handlers) {
  CApp::disableCacheAndHandlers();
}
else {
  // Disable cache anyway to avoid bugs
  CStoredObject::$useObjectCache = false;
}

if ($keep_sync) {
  HandlerManager::enableObjectHandler('CSyncHandler');
}

// Import ...
if ($ignore_classes) {
  $ignore_classes = explode('|', $ignore_classes);
  foreach ($ignore_classes as $_class) {
    CPatientXMLImport::$_ignored_classes[] = $_class;
  }
}

if (!CModule::getActive("dPprescription") || !$import_presc) {
  CPatientXMLImport::$_ignored_classes = array_merge(CPatientXMLImport::$_ignored_classes, CPatientXMLImport::$_prescription_classes);
}

$options = array(
  "link_file_to_op"           => $link_files_to_op,
  "correct_file"              => $correct_files,
  "date_min"                  => $date_min,
  "date_max"                  => $date_max,
  "uf_replace"                => $uf_replace,
  "no_update_patients_exists" => $no_update_patients_exists,
  "ipp_tag"                   => $ipp_tag,
  "exclude_duplicate"         => $exclude_duplicate,
);
// ... one patient
if ($patient_id) {
  $patient_id = (int)$patient_id;
  $xmlfile    = rtrim($directory, "/\\") . "/CPatient-$patient_id/export.xml";
  if (file_exists($xmlfile)) {
    $xmlfile  = realpath($xmlfile);
    $importer = new CPatientXMLImport($xmlfile);
    $importer->setUpdateData($update_data);
    $importer->setDirectory(dirname($xmlfile));

    if ($files_directory) {
      $importer->setFilesDirectory($files_directory);
    }

    $importer->import(array(), $options);
  }
  else {
    CAppUI::stepAjax("ID patient non valide: %s", UI_MSG_ERROR, $xmlfile);
  }
}
// ... or a lot
else {
  $iterator   = new DirectoryIterator($directory);
  $count_dirs = 0;

  $i = 0;

  foreach ($iterator as $_fileinfo) {
    if ($_fileinfo->isDot()) {
      continue;
    }

    if ($_fileinfo->isDir() && strpos($_fileinfo->getFilename(), "CPatient-") === 0) {
      $i++;
      if ($i <= $start) {
        continue;
      }

      if ($i > $start + $step) {
        break;
      }

      $count_dirs++;

      $xmlfile = $_fileinfo->getRealPath() . "/export.xml";
      if (file_exists($xmlfile)) {
        $importer = new CPatientXMLImport($xmlfile);
        $importer->setUpdateData($update_data);
        $importer->setDirectory($_fileinfo->getRealPath());

        if ($files_directory) {
          $importer->setFilesDirectory($files_directory);
        }

        $importer->import(array(), $options);
      }
    }
  }

  CAppUI::stepAjax("%d patients trouvés à importer", UI_MSG_OK, $count_dirs);

  if ($count_dirs) {
    CAppUI::js("nextStepPatients()");
  }
}

