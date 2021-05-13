<?php
/**
 * @package Mediboard\OpenData
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CView;
use Ox\Mediboard\OpenData\CImportConflict;
use Ox\Mediboard\Patients\CMedecin;

CCanDo::checkAdmin();

$audit      = CView::post('audit', 'bool');
$medecin_id = CView::post('medecin_id', 'ref class|CMedecin notNull');
$version    = CView::post("file_version_$medecin_id", 'str');
$fields     = explode('|', CView::post("fields-$medecin_id", 'str notNull'));

$keeps      = array();
$new_values = array();
foreach ($fields as $_field) {
  $keeps[$_field]      = CView::post("medecin-$medecin_id-$_field", 'enum list|old|new default|old');
  $new_values[$_field] = CView::post("medecin-$medecin_id-$_field-value", 'str');
}

$ignore_rpps = CView::post("medecin-ignore-rpps-$medecin_id", 'bool default|0');

CView::checkin();

$medecin = new CMedecin();
$medecin->load($medecin_id);
$medecin->import_file_version = $version;

if (!$medecin || !$medecin->_id) {
  CAppUI::stepAjax('CMedecinImport-id.none', UI_MSG_ERROR);
}

if ($ignore_rpps && !$medecin->ignore_import_rpps) {
  $medecin->ignore_import_rpps = $ignore_rpps;
  if ($msg = $medecin->store()) {
    CAppUI::stepAjax($msg, UI_MSG_ERROR);
  }
}

if ($medecin->ignore_import_rpps) {
  CAppUI::stepAjax('CMedecinImport-file-ignore-always', UI_MSG_OK);
  CApp::rip();
}

if ($audit) {
  CApp::rip();
}

$update = false;
foreach ($keeps as $_field => $_keep) {
  if ($_keep === 'old') {
    continue;
  }

  $update           = true;
  $medecin->$_field = $new_values[$_field];
}

if ($update) {
  if ($msg = $medecin->store()) {
    CAppUI::stepAjax($msg, UI_MSG_ERROR);
  }
}

$conflict  = new CImportConflict();
$conflict->setObject($medecin);
$conflict->import_tag = 'import_rpps';
$conflicts = $conflict->loadMatchingList();

foreach ($conflicts as $_conflit) {
  $_conflit->delete();
}

CAppUI::stepAjax('CMedecinImport-ok', UI_MSG_OK);