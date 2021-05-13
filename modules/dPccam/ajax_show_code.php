<?php
/**
 * @package Mediboard\Ccam
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CMbDT;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Ccam\CDatedCodeCCAM;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Mediusers\CSpecCPAM;
use Ox\Mediboard\Patients\CPatient;

CCanDo::checkRead();

$user = CMediusers::get();
$default_spec = 1;
if ($user->spec_cpam_id) {
  $default_spec = $user->spec_cpam_id;
}

$default_sector = '1';
if ($user->secteur) {
  $default_sector = $user->secteur;
}

$default_contract = '1';
if ($user->pratique_tarifaire) {
  $default_contract = $user->pratique_tarifaire;
}

$code_ccam          = CView::get("code_ccam", 'str notNull');
$date_version       = CView::get("date_version", 'str');
$date_demandee      = CView::get("date_demandee", 'str');
$situation_patient  = CView::get('situation_patient', 'enum list|cmu|acs|none default|none');
$speciality         = CView::get('speciality', "num min|1 max|80 default|$default_spec");
$contract           = CView::get('contract', "enum list|optam|optamco|none default|$default_contract");
$sector             = CView::get('sector', "enum list|1|1dp|2|nc default|$default_sector");

CView::checkin();

$patient = new CPatient();
if ($situation_patient == 'cmu') {
  $patient->cmu = '1';
}
elseif ($situation_patient == 'acs') {
  $patient->acs = '1';
}

$user->spec_cpam_id = $speciality;
$user->secteur = $sector;
$user->pratique_tarifaire = $contract;

$date_version_to = null;
if ($date_demandee) {
  $date_version_to = CDatedCodeCCAM::mapDateToDash($date_demandee);
}
if ($date_version) {
  $date_version_to = CDatedCodeCCAM::mapDateToSlash($date_version);
}
$date_demandee = CDatedCodeCCAM::mapDateFrom($date_version_to);

$date = CMbDT::dateFromLocale($date_version);

$date_versions = array();
$code_complet = CDatedCodeCCAM::get($code_ccam, $date_version_to);
foreach ($code_complet->_ref_code_ccam->_ref_infotarif as $_infotarif) {
  $date_versions[] = $code_complet->mapDateFrom($_infotarif->date_effet);
}
foreach ($code_complet->activites as $_activite) {
  $code_complet->_count_activite += count($_activite->assos);
}

$code_complet->getPrice($user, $patient, $date);
$acte_voisins = $code_complet->loadActesVoisins();

$specialities = CSpecCPAM::getList();

$smarty = new CSmartyDP();
if (!in_array($date_demandee, $date_versions) && $date_demandee) {
  $smarty->assign("no_date_found", "CDatedCodeCCAM-msg-No date found for date searched");
}
$smarty->assign("code_complet"      , $code_complet);
$smarty->assign("numberAssociations", $code_complet->_count_activite);
$smarty->assign("date_versions"     , $date_versions);
$smarty->assign("date_version"      , $date_version);
$smarty->assign("date_demandee"     , $date_demandee);
$smarty->assign("code_ccam"         , $code_ccam);
$smarty->assign("acte_voisins"      , $acte_voisins);
$smarty->assign('situation_patient' , $situation_patient);
$smarty->assign('speciality'        , $speciality);
$smarty->assign('specialities'      , $specialities);
$smarty->assign('contract'          , $contract);
$smarty->assign('sector'            , $sector);
$smarty->display("inc_show_code.tpl");