<?php
/**
 * @package Mediboard\Hl7
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbPath;
use Ox\Core\CSQLDataSource;

CCanDo::checkAdmin();

$ds = CSQLDataSource::get("hl7v2");

if ($ds && $ds->loadTable("table_entry")) {
  CAppUI::stepAjax("La table a déjà été importée - Import impossible", UI_MSG_ERROR);
}

$sourcePath = "modules/hl7/base/hl7v2.tar.gz";
$targetDir = "tmp/hl7";
$targetPath = "tmp/hl7/hl7v2.sql";

// Extract the SQL dump
if (null == $nbFiles = CMbPath::extract($sourcePath, $targetDir)) {
  CAppUI::stepAjax("Erreur, impossible d'extraire l'archive", UI_MSG_ERROR);
} 

CAppUI::stepAjax("Extraction de $nbFiles fichier(s)", UI_MSG_OK);

if (null == $lineCount = $ds->queryDump($targetPath)) {
  $msg = $ds->error();
  CAppUI::stepAjax("Erreur de requête SQL: $msg", UI_MSG_ERROR);
}

CAppUI::stepAjax("Import effectué avec succès de $lineCount lignes", UI_MSG_OK);

unlink($targetPath);
rmdir($targetDir);

/*
$path = "/path/to/hl7_tables";

$ds = CSQLDataSource::get("hl7v2");

$query = "CREATE TABLE IF NOT EXISTS `table_entry` (
  `number` INT(5) UNSIGNED NOT NULL,
  `code_hl7_from` VARCHAR(30), 
  `code_hl7_to` VARCHAR(30), 
  `code_mb_from` VARCHAR(30), 
  `code_mb_to` VARCHAR(30), 
  `description` VARCHAR(255)
)";
$ds->exec($query);

$ds->exec("TRUNCATE TABLE `table_entry`");

$csv_files = glob($path."/table-*.csv");
natsort($csv_files);

$count = 0;

foreach($csv_files as $csv_file) {
  preg_match('/(\d*)\.csv$/', $csv_file, $matches);
  $number = $matches[1];
  
  $items = array();
  
  $fp = fopen($csv_file, "r");
  while($line = fgetcsv($fp, null, ";")) {
    $desc = $ds->escape($line[1]);
    $hl7  = $ds->escape($line[0]);
    $items[] = "($number, '$hl7', '$desc')";
  }
  
  $count += count($items);
  $query = "INSERT INTO `table_entry` (`number`, `code_hl7_from`, `description`) VALUES ".implode(", ", $items);
  $ds->exec($query);
}

CAppUI::stepAjax("$count éléments importées dans ".count($csv_files)." tables");

CApp::rip();*/
