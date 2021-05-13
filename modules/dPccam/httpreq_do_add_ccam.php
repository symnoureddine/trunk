<?php
/**
 * @package Mediboard\Ccam
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

$archive  = 'modules/dPccam/base/ccam.tar.gz';
$path     = 'tmp/ccam';

$tables     = 'tmp/ccam/tables.sql';
$basic_data = 'tmp/ccam/basedata.sql';
$base       = 'tmp/ccam/base.sql';
$pmsi       = 'tmp/ccam/pmsi.sql';

// Extract the SQL dump
if (null == $nbFiles = CMbPath::extract($archive, $path)) {
  CAppUI::stepAjax("Erreur, impossible d'extraire l'archive", UI_MSG_ERROR);
}

CAppUI::stepAjax("Extraction de $nbFiles fichier(s)", UI_MSG_OK);

$ds = CSQLDataSource::get("ccamV2");

// Cr�ation des tables
if (null == $lineCount = $ds->queryDump($tables, false)) {
  $msg = $ds->error();
  CAppUI::stepAjax("Import des tables - erreur de requ�te SQL: $msg", UI_MSG_ERROR);
}
CAppUI::stepAjax("Cr�ation de $lineCount tables", UI_MSG_OK);

// Ajout des donn�es de base
if (null == $lineCount = $ds->queryDump($basic_data, false)) {
  $msg = $ds->error();
  CAppUI::stepAjax("Import des donn�es de base - erreur de requ�te SQL: $msg", UI_MSG_ERROR);
}
CAppUI::stepAjax("Import des donn�es de base effectu� avec succ�s ($lineCount lignes)", UI_MSG_OK);

// Ajout des donn�es de la base CCAM
if (null == $lineCount = $ds->queryDump($base, false)) {
  $msg = $ds->error();
  CAppUI::stepAjax("Import des donn�es CCAM - erreur de requ�te SQL: $msg", UI_MSG_ERROR);
}
CAppUI::stepAjax("Import des donn�es CCAM effectu� avec succ�s ($lineCount lignes)", UI_MSG_OK);

// Ajout des extensions PMSI de l'ATIH
if (null == $lineCount = $ds->queryDump($pmsi, false)) {
  $msg = $ds->error();
  CAppUI::stepAjax("Import des extensions PMSI - erreur de requ�te SQL: $msg", UI_MSG_ERROR);
}
CAppUI::stepAjax("Import des extensions PMSI effectu� avec succ�s ($lineCount lignes)", UI_MSG_OK);
CApp::rip();