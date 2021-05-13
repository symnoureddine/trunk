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
use Ox\Mediboard\Ccam\CFavoriCCAM;
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

$codeacte           = CView::get('_codes_ccam', 'str', true);
$object_class       = CView::get("object_class", 'enum list|COperation|CSejour|CConsultation default|COperation');
$hideSelect         = CView::get("hideSelect", 'bool default|0');
$situation_patient  = CView::get('situation_patient', 'enum list|cmu|acs|none default|none');
$speciality         = CView::get('speciality', "num min|1 max|80 default|$default_spec");
$contract           = CView::get('contract', "enum list|optam|optamco|none default|$default_contract");
$sector             = CView::get('sector', "enum list|1|1dp|2|nc default|$default_sector");
$date               = CView::get('date', 'date');

CView::checkin();

$date = CMbDT::date($date);

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

$code = CDatedCodeCCAM::get($codeacte, $date);

// Variable permettant de savoir si l'affichage du code complet est necessaire
$codeComplet = false;
$codeacte = $code->code;

if ($code->_activite != "") {
  $codeComplet = true;
  $codeacte .= "-$code->_activite";  
  if ($code->_phase != "") {
    $codeacte .= "-$code->_phase";
  }
}

$code->getPrice($user, $patient, $date);

$codeacte = strtoupper($codeacte);

$favoris = new CFavoriCCAM();

// Création du template
$smarty = new CSmartyDP();

$smarty->assign("code"              , $code);
$smarty->assign("codeComplet"       , $codeComplet);
$smarty->assign("favoris"           , $favoris);
$smarty->assign("codeacte"          , $codeacte);
$smarty->assign("object_class"      , $object_class);
$smarty->assign("hideSelect"        , $hideSelect);
$smarty->assign('situation_patient' , $situation_patient);
$smarty->assign('speciality'        , $speciality);
$smarty->assign('specialities'      , CSpecCPAM::getList());
$smarty->assign('contract'          , $contract);
$smarty->assign('sector'            , $sector);
$smarty->assign('date'              , $date);
$smarty->assign('user'              , $user);

$smarty->display("vw_full_code.tpl");
