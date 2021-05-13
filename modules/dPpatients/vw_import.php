<?php
/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbArray;
use Ox\Core\Import\CMbObjectExport;
use Ox\Core\CSmartyDP;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Patients\CCSVImportPatients;
use Ox\Mediboard\Patients\CCSVImportSejours;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\PlanningOp\CChargePriceIndicator;
use Ox\Mediboard\PlanningOp\CModeEntreeSejour;
use Ox\Mediboard\PlanningOp\CModeSortieSejour;
use Ox\Mediboard\PlanningOp\CSejour;

/**
 * View import
 */
CCanDo::checkAdmin();

/**
 * Patients
 */

// Nombre de patients
$patient = new CPatient();

// import temp file
$start_pat = 0;
$count_pat = 20;
if ($data = @file_get_contents(CAppUI::conf("root_dir") . "/tmp/import_patient.txt", "r")) {
  $nb        = explode(";", $data);
  $start_pat = $nb[0];
  $count_pat = $nb[1];
}

$patient_specs = array(
  '_IPP' => $patient->_props['_IPP']
);

$patient_specs = array_merge($patient_specs, $patient->getPlainProps());

/**
 * Séjours
 */

$start_sej = 0;
$count_sej = 20;
if ($data = @file_get_contents(CAppUI::conf("root_dir") . "/tmp/import_cegi_sejour.txt", "r")) {
  $nb        = explode(";", $data);
  $start_sej = $nb[0];
  $count_sej = $nb[1];
}

$sejour    = new CSejour();
$patient   = new CPatient();
$mediusers = new CMediusers();

$group_id = CGroups::loadCurrent()->_id;

$mode_traitement           = new CChargePriceIndicator();
$mode_traitement->group_id = $group_id;
$mode_traitement->actif    = 1;

/** @var CChargePriceIndicator[] $modes_traitement */
$modes_traitement = $mode_traitement->loadMatchingList();
$MDT              = CMbArray::pluck($modes_traitement, 'code');

$mode_entree           = new CModeEntreeSejour();
$mode_entree->group_id = $group_id;
$mode_entree->actif    = 1;

/** @var CModeEntreeSejour[] $modes_entree */
$modes_entree = $mode_entree->loadMatchingList();
$MDE          = CMbArray::pluck($modes_entree, 'code');

$mode_sortie           = new CModeSortieSejour();
$mode_sortie->group_id = $group_id;
$mode_sortie->actif    = 1;

/** @var CModeSortieSejour[] $modes_sortie */
$modes_sortie = $mode_sortie->loadMatchingList();
$MDS          = CMbArray::pluck($modes_entree, 'code');

$sejour_specs = array(
  '_IPP'  => $patient->_props['_IPP'],
  '_NDA'  => $sejour->_props['_NDA'],
  'adeli' => $mediusers->_props['adeli'],
  'rpps'  => $mediusers->_props['rpps'],
);

if ($MDT) {
  $sejour_specs['MDT'] = 'enum list|' . implode('|', $MDT) . ' notNull';
}

if ($MDE) {
  $sejour_specs['MDE'] = 'enum list|' . implode('|', $MDE);
}

if ($MDS) {
  $sejour_specs['MDS'] = 'enum list|' . implode('|', $MDS);
}

$sejour_specs = array_merge($sejour_specs, $sejour->getPlainProps());

$patient_options            = CCSVImportPatients::$options;
$patient_interop            = CCSVImportPatients::$options_interop;
$patient_found              = CCSVImportPatients::$options_found;
$patient_identito_main      = CCSVImportPatients::$identito_main;
$patient_identito_secondary = CCSVImportPatients::$identito_secondary;

$fields_import_sejour = CCSVImportSejours::$options;

$allowed_types = array("Chirurgien", "Anesthésiste", "Médecin", "Dentiste", "Infirmière", "Sage Femme");
$praticiens    = CMbObjectExport::getPraticiensFromGroup($allowed_types);

$smarty = new CSmartyDP();

$smarty->assign('group', CGroups::loadCurrent());
$smarty->assign('praticiens', $praticiens);
$smarty->assign('praticien_id', array());
// Patients
$smarty->assign("count_pat", $count_pat);
$smarty->assign("start_pat", $start_pat);
$smarty->assign("patient_specs", $patient_specs);

$smarty->assign("patient_options", $patient_options);
$smarty->assign("patient_interop", $patient_interop);
$smarty->assign("patient_identito_main", $patient_identito_main);
$smarty->assign("patient_identito_secondary", $patient_identito_secondary);
$smarty->assign("patient_found", $patient_found);

// Sejours
$smarty->assign("start_sej", $start_sej);
$smarty->assign("count_sej", $count_sej);
$smarty->assign("sejour_specs", $sejour_specs);
$smarty->assign("fields_import_sejour", $fields_import_sejour);

$smarty->display("vw_import.tpl");
