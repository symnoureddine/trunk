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

$archive = 'modules/dPcim10/base/cisp.tar.gz';
$destination = 'tmp/cim10/cisp';

/* Extract the archive */
if (null == $nbFiles = CMbPath::extract($archive, $destination)) {
  CAppUI::stepAjax("Erreur, impossible d'extraire l'archive", UI_MSG_ERROR);
}

CAppUI::stepAjax("Extraction de $nbFiles fichier(s)", UI_MSG_OK);

$ds = CSQLDataSource::get('cisp');

/* Creation of the database's structure */
if (!$ds->queryDump("$destination/tables.sql", true)) {
  $msg = $ds->error();
  CAppUI::stepAjax("Erreur SQL lors de la création de la structure de la base : $msg", UI_MSG_ERROR);
}
CAppUI::stepAjax("Création de la structure de la base", UI_MSG_OK);

// Ajout des données de base
if (!$ds->exec(file_get_contents("$destination/data.sql"))) {
  $msg = $ds->error();
  CAppUI::stepAjax("Erreur lors de l'import des données de la base : $msg", UI_MSG_ERROR);
}

CMbPath::remove($destination);

CAppUI::stepAjax('Import des données de la base', UI_MSG_OK);