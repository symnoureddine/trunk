<?php
/**
 * @package Mediboard\Etablissement
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbObject;
use Ox\Core\CMbPath;
use Ox\Core\FileUtil\CCSVFile;
use Ox\Core\Sessions\CSessionHandler;
use Ox\Mediboard\Etablissement\CEtabExterne;

CCanDo::checkAdmin();

CMbObject::$useObjectCache = false;

/**
 * Get the value matching ^="(.*)"$
 *
 * @param string $value The value to get the result from
 *
 * @return string
 */
function getValue($value) {
  if (preg_match('/^="(.*)"$/', $value, $matches)) {
    $value = $matches[1];
  }

  return trim($value, " \t\n\r\0\"'");
}

/**
 * Removes all all numeric chars from a string
 *
 * @param string $value The value to get the number of
 *
 * @return string
 */
function getNum($value) {
  return preg_replace("/[^0-9]/", "", $value);
}

CApp::setTimeLimit(3600);
CSessionHandler::writeClose();

$file = $_FILES["import"];
$imported_elements_count = 0;

if (empty($file["tmp_name"])) {
  return;
}

$dir = "tmp/import_etab_externe";
CMbPath::forceDir($dir);

$csv = new CCSVFile($file['tmp_name'], CCSVFile::PROFILE_EXCEL);
$csv->jumpLine(1); // first line

while ($line = $csv->readLine()) {
  if (!isset($line[1])) {
    continue;
  }

  $line = array_map("getValue", $line);

  $etab         = new CEtabExterne();
  $etab->finess = getNum($line[0]);

  if ($etab->loadMatchingObject()) {
    continue;
  }

  $etab->siret          = getNum($line[1]);
  $etab->ape            = $line[2];
  $etab->nom            = $line[3];
  $etab->raison_sociale = $line[4];
  $etab->adresse        = $line[5];
  $etab->cp             = $line[6];
  $etab->ville          = $line[7];
  $etab->tel            = getNum($line[8]);
  $etab->fax            = getNum($line[9]);
  $etab->provenance     = getNum($line[10]);
  $etab->destination    = getNum($line[11]);
  $etab->priority       = getNum($line[12]);
  $etab->repair();

  $type = $etab->_id ? "modify" : "create";
  if ($msg = $etab->store()) {
    CAppUI::setMsg($msg, UI_MSG_WARNING);
    continue;
  }
  else {
    $imported_elements_count++;
  }
}
$msg = CAppUI::tr(
  "CEtabExterne-import_success",
  [
    "var1" => $imported_elements_count
  ]
);
CAppUI::stepAjax($msg, UI_MSG_OK);
CAppUI::setMsg($msg, UI_MSG_OK);
