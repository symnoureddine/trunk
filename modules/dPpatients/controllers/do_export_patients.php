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
use Ox\Core\CMbDT;
use Ox\Core\CMbException;
use Ox\Core\Import\CMbObjectExport;
use Ox\Core\CMbPath;
use Ox\Core\CMbString;
use Ox\Core\Module\CModule;
use Ox\Core\CStoredObject;
use Ox\Core\CView;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Patients\CPatient;

CCanDo::checkAdmin();

// Common vars
$directory      = CView::post("directory", "str notNull");
$directory_name = CView::post("directory_name", "str default|export-" . CMbDT::date());
$step           = CView::post("step", "num default|100");
$start          = CView::post("start", "num default|0");
$praticien_id   = CView::post("praticien_id", "str");
$all_prats      = CView::post("all_prats", "str");
$update         = CView::post("update", "str");
$patient_id     = CView::post("patient_id", "ref class|CPatient");

// Files vars
$ignore_files         = CView::post("ignore_files", "str");
$generate_pdfpreviews = CView::post("generate_pdfpreviews", "str");
$archive_sejour       = CView::post("archive_sejour", "str"); // Create dossier_soins for CSejour
$archive_mode         = CView::post("archive_mode", "str"); // Create timeline and synthèse med for patients

// Dates
$date_min = CView::post("date_min", "date");
$date_max = CView::post("date_max", "date");

// Types
$use_function  = CView::post("use_function", "str"); // TAMM Mode
$patient_infos = CView::post("patient_infos", "str"); // Minified backrefs
$consult_only  = CView::post("consult_only", "str");
$sejour_only   = CView::post("sejour_only", "str");

if (!$all_prats && !$praticien_id) {
    CAppUI::stepAjax("Veuillez choisir au moins un praticien, ou cocher 'Tous les praticiens'", UI_MSG_WARNING);

    return;
}

if (!is_dir($directory)) {
    CAppUI::stepAjax("'%s' is not a directory", UI_MSG_WARNING, $directory);

    return;
}

CApp::setTimeLimit(600);
CApp::setMemoryLimit("4096M");

$directory = str_replace("\\\\", "\\", $directory);

CView::setSession("praticien_id", $praticien_id);
CView::setSession("all_prats", $all_prats);
CView::setSession("step", $step);
CView::setSession("start", $start);
CView::setSession("directory", $directory);
CView::setSession("ignore_files", $ignore_files);
CView::setSession("generate_pdfpreviews", $generate_pdfpreviews);
CView::setSession("date_min", $date_min);
CView::setSession("patient_id", $patient_id);
CView::setSession("use_function", $use_function);
CView::setSession("patient_infos", $patient_infos);
CView::setSession("date_max", $date_max);
CView::setSession("update", $update);

CView::enforceSlave();

CView::checkin();

$step = min($step, 1000);

$patient = new CPatient();
$ds      = $patient->getDS();

$order = [
    "patients.nom",
    "patients.nom_jeune_fille",
    "patients.prenom",
    "patients.naissance",
    "patients.patient_id",
];

if ($patient_id) {
    $patient = new CPatient();
    $patient->load($patient_id);
    $patients      = [$patient];
    $patient_count = 1;
    $patient_total = 1;
} elseif ($all_prats) {
    [$patients, $patient_total] = CMbObjectExport::getAllPatients($start, $step, $order);
    $patient_count = count($patients);
} elseif ($use_function) {
    [$patients, $patient_total] = CMbObjectExport::getPatientToExportFunction($praticien_id, $start, $step);
    $patient_count = count($patients);
} else {
    $type = ($consult_only ? 'consult' : ($sejour_only ? 'sejour' : null));
    [$patients, $patient_total] =
        CMbObjectExport::getPatientsToExport($praticien_id, $date_min, $date_max, $start, $step, $order, $type);
    $patient_count = count($patients);
}

CAppUI::stepAjax("%d patients à exporter", UI_MSG_OK, $patient_total);

$date = CMbDT::format(null, "%Y-%m-%d");

// Callback qui exclut les objets n'appartenant à personne dans la liste des $praticien_id
$filter_callback = function (CStoredObject $object) use ($praticien_id, $date_min, $date_max) {
    return CMbObjectExport::exportFilterCallback($object, $date_min, $date_max, $praticien_id);
};

$back_refs = ($patient_infos) ? CMbObjectExport::$minimized_backrefs_tree : CMbObjectExport::$default_backrefs_tree;
$fw_refs   = ($patient_infos) ? CMbObjectExport::$minimized_fwrefs_tree : CMbObjectExport::$default_fwdrefs_tree;

if (!$patient_infos) {
    if (CModule::getInstalled('notifications')) {
        $back_refs = array_merge_recursive($back_refs, CMbObjectExport::$notif_back_tree);
        $fw_refs   = array_merge_recursive($fw_refs, CMbObjectExport::$notif_fw_tree);
    }

    if (CModule::getInstalled('tarmed')) {
        $back_refs = array_merge_recursive($back_refs, CMbObjectExport::$tarmed_back_tree);
        $fw_refs   = array_merge_recursive($fw_refs, CMbObjectExport::$tarmed_fw_tree);
    }
}

if ($consult_only) {
    $back_refs = [
        "CPatient" => [
            "consultations",
        ],
    ];

    $fw_refs = [
        "CConsultation" => [
            "plageconsult_id",
        ],
        "CPlageconsult" => [
            "chir_id",
        ],
    ];
} elseif ($sejour_only) {
    $back_refs = [
        "CPatient" => [
            "sejours",
        ],
    ];

    $fw_refs = [
        "CSejour" => [
            "praticien_id",
        ],
    ];
}

$dir = "$directory/$directory_name/";

$group_id = CGroups::loadCurrent()->_id;
foreach ($patients as $_patient) {
    try {
        $dir_pat = $dir . $_patient->_guid;
        if ($archive_mode) {
            $_patient->loadIPP($group_id);
            $dir_pat = $dir . utf8_encode(
                preg_replace(
                    '/\W+/',
                    '_',
                    "{$_patient->_IPP} {$_patient->nom} {$_patient->prenom} {$_patient->naissance}"
                )
            );
        }

        if (!$update && file_exists($dir_pat . '/export.xml')) {
            continue;
        }

        CMbPath::forceDir($dir_pat);

        $export = new CMbObjectExport($_patient, $back_refs);

        $callback = function (CStoredObject $object) use (
            $dir_pat,
            $ignore_files,
            $generate_pdfpreviews,
            $archive_sejour,
            $archive_mode
        ) {
            CMbObjectExport::exportCallBack(
                $object,
                $dir_pat,
                $generate_pdfpreviews,
                $ignore_files,
                $archive_sejour,
                false,
                $archive_mode
            );
        };

        $export->empty_values = false;
        $export->setObjectCallback($callback);

        if ($praticien_id) {
            $export->setFilterCallback($filter_callback);
        }

        $export->setForwardRefsTree($fw_refs);

        $xml = $export->toDOM()->saveXML();
        file_put_contents("$dir_pat/export.xml", $xml);
    } catch (CMbException $e) {
        $e->stepAjax(UI_MSG_ERROR);
    }
}

CAppUI::stepAjax("%d patients au total", UI_MSG_OK, $patient_count);

if ($patient_count && !$patient_id) {
    CAppUI::js("nextStepPatients()");
}
