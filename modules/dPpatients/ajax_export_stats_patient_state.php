<?php
/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CMbDT;
use Ox\Core\CView;
use Ox\Core\FileUtil\CCSVFile;
use Ox\Mediboard\Patients\CPatientStateTools;

if (!CAppUI::pref("allowed_modify_identity_status")) {
  CAppUI::accessDenied();
}

$number_day    = CView::get("_number_day", "num default|8", true);
$number_day    = $number_day ?: 8;
$now           = CView::get("_date_end", "date default|now", true);
$merge_patient = CView::get("_merge_patient", "bool");
$before        = CMbDT::date("-$number_day DAY", $now);
CView::checkin();

$csv = new CCSVFile();

$line = array(
  "Date",
  CAppUI::tr("CPatient.status.VIDE"),
  CAppUI::tr("CPatient.status.PROV"),
  CAppUI::tr("CPatient.status.DPOT"),
  CAppUI::tr("CPatient.status.ANOM"),
  CAppUI::tr("CPatient.status.CACH"),
  CAppUI::tr("CPatient.status.VALI"),
  $merge_patient ? CAppUI::tr("CPatient.status.merged") : ""
);
$csv->writeLine($line);

$results = CPatientStateTools::getPatientStateByDate($before, $now);

$values = array();
for ($i = $number_day; $i >= 0; $i--) {
  $values[CMbDT::date("-$i DAY", $now)] = array(
    "VIDE" => 0,
    "PROV" => 0,
    "DPOT" => 0,
    "ANOM" => 0,
    "CACH" => 0,
    "VALI" => 0,
  );

  if ($merge_patient) {
    $values[CMbDT::date("-$i DAY", $now)]["merged"] = 0;
  }
}

foreach ($results as $_result) {
  $values[$_result["date"]][$_result["state"]] = $_result["total"];
}

if ($merge_patient) {
  $results_merge = CPatientStateTools::getPatientMergeByDate($before, $now);
  for ($i = $number_day; $i >= 0; $i--) {
    $values[CMbDT::date("-$i DAYS", $now)]["merged"] = 0;
  }

  foreach ($results_merge as $_result) {
    $values[$_result["date"]]["merged"] = $_result["total"];
  }
}

foreach ($values as $_date => $_value) {
  $line = array(
    $_date
  );
  $line = array_merge($line, array_values($_value));

  $csv->writeLine($line);
}

$csv->stream("statut_patient_par_date");
