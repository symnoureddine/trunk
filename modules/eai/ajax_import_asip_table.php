<?php
/**
 * @package Mediboard\Eai
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CSQLDataSource;
use Ox\Core\FileUtil\CCSVFile;

CCanDo::checkAdmin();

$ds = CSQLDataSource::get("ASIP");
$path = "modules/eai/resources";

if (!$ds) {
  CAppUI::stepAjax("Import impossible - Aucune source de données", UI_MSG_ERROR);
  CApp::rip();
}

$files = glob("$path/*.jv");
$lineCount = 0;
foreach ($files as $_file) {
  $name = basename($_file);
  $name = substr($name, strpos($name, "_")+1);
  $table = substr($name, 0, strrpos($name, "."));
  $table = strtolower($table);
  if (!$ds) {
    CAppUI::stepAjax("Import impossible - Source non présente", UI_MSG_ERROR);
    continue;
  }
  $ds->query("CREATE TABLE IF NOT EXISTS `$table` (
                `table_id` INT (11) UNSIGNED NOT NULL auto_increment PRIMARY KEY,
                `code` VARCHAR (255) NOT NULL,
                `oid` VARCHAR (255) NOT NULL,
                `libelle` VARCHAR (255) NOT NULL,
                INDEX (`table_id`)
              )/*! ENGINE=MyISAM */;");

  $ds->query("DELETE FROM `$table`");

  $csv = new CCSVFile($_file);
  $csv->jumpLine(3);
  while ($line = $csv->readLine()) {
    list($oid, $code, $libelle) = $line;
    if (strpos($code, "/") === false) {
      continue;
    }
    $query = "INSERT INTO `$table`(
        `code`, `oid`, `libelle`)
        VALUES (?1, ?2, ?3);";
    $query = $ds->prepare($query, $code, $oid, $libelle);
    $result = $ds->query($query);
    if (!$result) {
      $msg = $ds->error();
      CAppUI::displayAjaxMsg("Erreur de requête SQL: $msg", UI_MSG_ERROR);
      CApp::rip();
    }
    $lineCount++;
  }
}

CAppUI::stepAjax("Import effectué avec succès de $lineCount lignes", UI_MSG_OK);