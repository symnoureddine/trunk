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
use Ox\Core\CMbObject;
use Ox\Core\CView;
use Ox\Mediboard\Patients\CCSVImportPatients;

CCanDo::checkAdmin();
ini_set("auto_detect_line_endings", true);

// Basic
$start    = CView::post("start", "num default|0");
$count    = CView::post("count", 'num default|100');
$callback = CView::post("callback", "str");

// Action when a patient is found
$patient_found = CView::post("patient_found", "str default|0");

// interop fields
$by_IPP          = CView::post("by_IPP", "str default|0");
$generate_IPP    = CView::post("generate_IPP", "str default|0");
$diable_handlers = CView::post('disable_handlers', "str default|0");

// advanced options
$no_create     = CView::post("no_create", "str default|0");
$fail_on_empty = CView::post("fail_on_empty", "str default|0");

// Identito fields
$identito_nom            = CView::post("identito_nom", "str default|0");
$identito_prenom         = CView::post("identito_prenom", "str default|0");
$identito_naissance      = CView::post("identito_naissance", "str default|0");
$identito_sexe           = CView::post("identito_sexe", "str default|0");
$identito_prenoms_autres = CView::post("identito_prenoms_autres", "str default|0");
$identito_tel            = CView::post("identito_tel", "str default|0");
$identito_matricule      = CView::post("identito_matricule", "str default|0");
$secondary_operand       = CView::post("secondary_operand", "str default|or");

CView::checkin();

CApp::setTimeLimit(600);
CApp::setMemoryLimit("1024M");

if ($diable_handlers) {
  CApp::disableCacheAndHandlers();
}

CAppUI::stepAjax("Désactivation du gestionnaire", UI_MSG_OK);

CMbObject::$useObjectCache = false;

$import_patients = new CCSVImportPatients($start, $count);
$import_patients->setOptions($by_IPP, $generate_IPP, $patient_found, $no_create, $fail_on_empty);
$import_patients->setIdentito(
  $identito_nom, $identito_prenom, $identito_naissance, $identito_sexe, $identito_prenoms_autres, $identito_tel, $identito_matricule,
  $secondary_operand
);

$ret = $import_patients->import();

$start += $count;
file_put_contents(CAppUI::conf("root_dir") . "/tmp/import_patient.txt", "$start;$count");

echo CAppUI::getMsg();

if ($callback && $ret) {
  CAppUI::js("$callback($start,$count)");
}

CMbObject::$useObjectCache = true;
CApp::rip();