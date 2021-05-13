<?php
/**
 * @package Mediboard\Cabinet
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CCanDo;
use Ox\Core\CMbDT;
use Ox\Core\CSmartyDP;
use Ox\Core\CSQLDataSource;
use Ox\Core\CStoredObject;
use Ox\Core\CView;
use Ox\Mediboard\Cabinet\CConsultation;
use Ox\Mediboard\Cabinet\CPlageconsult;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Patients\CPatient;

CCanDo::checkRead();

CApp::setMemoryLimit("512M");

$ds = CSQLDataSource::get("std");

$function_id = CView::get("function_id", "ref class|CFunctions");
$chir_ids    = CView::get("chir_ids", "str");
$date        = CView::get("date", "date default|".CMbDT::date());
$period      = CView::get("period", "str default|12-weeks");

CView::checkin();
CView::enforceSlave();

// Praticiens sélectionnés
$user = new CMediusers();
$praticiens = array();
if ($function_id) {
  $praticiens = CConsultation::loadPraticiens(PERM_EDIT, $function_id);
}

if ($chir_ids) {
  $praticiens = $user->loadAll(explode("-", $chir_ids));
}

// Bornes de dates
list($period_count, $period_type) = explode("-", $period);
$period_count++;
$date_min = CMbDT::date($date);
if (!$period) {
  $date_max = CMbDT::date("last day of week", $date);
}
else {
  $date_max = CMbDT::date("+ $period_count $period_type - 1 day", $date);
}

// Chargement de toutes les plages concernées
$where["chir_id"] = CSQLDataSource::prepareIn(array_keys($praticiens));
$where["date"] = $ds->prepare("BETWEEN %1 AND %2", $date_min, $date_max);
$order = "date, debut";

$plage = new CPlageconsult();
/** @var CPlageconsult[] $plages */
$plages = $plage->loadList($where, $order);

/** @var CPlageconsult[][] $plages Plages par mois*/
$listPlages = array();

$bank_holidays = array_merge(CMbDT::getHolidays($date_min), CMbDT::getHolidays($date_max));

$totals = array();

// Chargement des places disponibles pour chaque plage
foreach ($plages as $_plage) {
  // Classement par mois
  $month = CMbDT::format($_plage->date, "%B_%Y");
  $listPlages[$month][] = $_plage;

  // Praticien
  $_plage->_ref_chir = $praticiens[$_plage->chir_id];
  $_plage->_ref_chir->loadRefFunction();

  // Totaux
  if (!isset($totals[$month])) {
    $totals[$month] = array(
      "affected" => 0,
      "total"    => 0,
    );
  }

  $_plage->loadFillRate();
  $totals[$month]["affected"] += $_plage->_affected;
  $totals[$month]["total"   ] += $_plage->_total   ;

  $_plage->loadRefAgendaPraticien();

  // Détails des consultations
  $_plage->_listPlace = array();
  for ($i = 0; $i < $_plage->_total; $i++) {
    $minutes = $_plage->_freq * $i;
    $_plage->_listPlace[$i]["time"] = CMbDT::time("+ $minutes minutes", $_plage->debut);
    $_plage->_listPlace[$i]["consultations"] = array();
  }

  // Optimisation du chargement patient
  $patient = new CPatient();
  $patient->_spec->columns = array("nom", "prenom", "nom_jeune_fille", "civilite");

  $consultations = $_plage->loadRefsConsultations();
  CStoredObject::massLoadFwdRef($consultations, "patient_id");
  CStoredObject::massLoadFwdRef($consultations, "categorie_id");
  CStoredObject::massLoadFwdRef($consultations, "element_prescription_id");
  foreach ($consultations as $_consultation) {
    $_consultation->loadRefPatient();
    $_consultation->loadRefCategorie();
    $_consultation->loadRefElementPrescription();

    $place = CMbDT::timeCountIntervals($_plage->debut, $_consultation->heure, $_plage->freq);
    for ($i = 0;  $i < $_consultation->duree; $i++) {
      if (isset($_plage->_listPlace[($place + $i)])) {
        $_plage->_listPlace[($place + $i)]["consultations"][] = $_consultation;
      }
    }
  }
}

// Création du template
$smarty = new CSmartyDP();

$smarty->assign("period_count"   , $period_count);
$smarty->assign("period_type"    , $period_type);
$smarty->assign("date_min"       , $date_min);
$smarty->assign("date_max"       , $date_max);
$smarty->assign("praticiens"     , $praticiens);
$smarty->assign("plageconsult_id", null);
$smarty->assign("listPlages"     , $listPlages);
$smarty->assign("totals"         , $totals);
$smarty->assign("bank_holidays"  , $bank_holidays);
$smarty->assign("online"         , false);

$smarty->display("offline_programme_consult.tpl");
