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

CCanDo::checkAdmin();

CView::checkin();
CApp::setTimeLimit(360);

$archive = 'modules/dPcim10/base/cim10_gm.tar.gz';
$path = 'tmp/cim10/gm';

/* Archive extraction */
if (null == $files = CMbPath::extract($archive, $path)) {
  CAppUI::stepAjax("Erreur, impossible d'extraire l'archive", UI_MSG_ERROR);
}

CAppUI::stepAjax("Extraction de $files fichier(s)", UI_MSG_OK);

$ds = CSQLDataSource::get("cim10");
if (null == $lines = $ds->queryDump("$path/tables.sql")) {
  $msg = $ds->error();
  CAppUI::stepAjax("Erreur de requête SQL: $msg", UI_MSG_ERROR);
}

CAppUI::stepAjax('Création des tables effectuée avec succès', UI_MSG_OK);

if (null == $lines = $ds->queryDump("$path/data.sql")) {
  $msg = $ds->error();
  CAppUI::stepAjax("Erreur de requête SQL: $msg", UI_MSG_ERROR);
}
else {
  CAppUI::stepAjax("Import effectué avec succès : $lines requêtes", UI_MSG_OK);
}

CMbPath::remove($path);
CApp::rip();