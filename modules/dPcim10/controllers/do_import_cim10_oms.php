<?php
/**
 * @package Mediboard\Cim10
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbPath;
use Ox\Core\CSQLDataSource;
use Ox\Core\CView;
use Ox\Mediboard\Cim10\CImportCim10;

CCanDo::checkAdmin();

$action = CView::post('action', 'enum list|import|update default|update');

CView::checkin();
CApp::setTimeLimit(360);

if ($action == 'import') {
  $sourcePath = "modules/dPcim10/base/cim10.tar.gz";
  $targetDir = "tmp/cim10/oms";
  $targetPath = "tmp/cim10/oms/cim10.sql";

  // Extract the SQL dump
  if (null == $nbFiles = CMbPath::extract($sourcePath, $targetDir)) {
    CAppUI::stepAjax("Erreur, impossible d'extraire l'archive", UI_MSG_ERROR);
  }

  CAppUI::stepAjax("Extraction de $nbFiles fichier(s)", UI_MSG_OK);

  $ds = CSQLDataSource::get("cim10");
  if (null == $lineCount = $ds->queryDump($targetPath)) {
    $msg = $ds->error();
    CAppUI::stepAjax("Erreur de requête SQL: $msg", UI_MSG_ERROR);
  }
  else {
    CAppUI::stepAjax("Import effectué avec succès de $lineCount lignes", UI_MSG_OK);
  }

  CMbPath::remove($targetDir);
}
else {
  // Extraction des codes supplémentaires de l'ATIH
  $targetDir = 'tmp/cim10/oms';
  $sourcePath = 'modules/dPcim10/base/cim10_modifs.tar.gz';
  $targetPath = 'tmp/cim10/oms/cim10_modifs.csv';
  if (null == $nbFiles = CMbPath::extract($sourcePath, $targetDir)) {
    CAppUI::stepAjax("Erreur, impossible d'extraire l'archive cim10_modifs.csv", UI_MSG_ERROR);
  }
  CAppUI::stepAjax("Extraction de $nbFiles fichier(s) [cim10_modifs.csv]", UI_MSG_OK);

  $ds = CSQLDataSource::get('cim10', true);
  if (!$ds) {
    CAppUI::stepAjax('La source de données cim10 n\'existe pas.', UI_MSG_ERROR);
    CApp::rip();
  }

  $import = new CImportCim10($targetPath, $ds);
  $import->run();

  CMbPath::remove($targetDir);
}

CApp::rip();