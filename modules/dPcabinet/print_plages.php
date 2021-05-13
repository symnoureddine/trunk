<?php
/**
 * @package Mediboard\Cabinet
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbDT;
use Ox\Core\CMbObject;
use Ox\Core\CSmartyDP;
use Ox\Core\CSQLDataSource;
use Ox\Core\CView;
use Ox\Mediboard\Cabinet\CConsultation;
use Ox\Mediboard\Cabinet\CPlageconsult;

CCanDo::checkRead();

$filter                  = new CConsultation;
$filter->plageconsult_id = CView::getRefCheckRead("plage_id", "ref class|CPlageconsult");
$filter->_date_min       = CView::get("_date_min", "date default|now");
$filter->_date_max       = CView::get("_date_max", "date default|now");
$filter->_telephone      = CView::get("_telephone", "bool default|1");
$filter->_coordonnees    = CView::get("_coordonnees", "str");
$filter->_plages_vides   = CView::get("_plages_vides", "bool default|1");
$filter->_non_pourvues   = CView::get("_non_pourvues", "bool default|1");
$canceled                = CView::get("canceled", "enum list|all|not_canceled|canceled default|not_canceled");
$filter->_print_ipp      = CView::get("_print_ipp", "bool default|" . CAppUI::gconf("dPcabinet CConsultation show_IPP_print_consult"));
$visite_domicile         = CView::get("visite_domicile", "bool default|0");
$libelle_plage           = CView::get("libelle", "str");
$plagesconsult_ids       = CView::get("plagesconsult_ids", "str");

$chir         = CView::getRefCheckRead("chir", "ref class|CMediusers");
$function_id  = CView::getRefCheckRead("function_id", "ref class|CFunctions");
$categorie_id = CView::get("category_id", "ref class|CConsultationCategorie");
CView::checkin();

$show_lit = false;

// On selectionne les plages
$plage = new CPlageconsult;
$where = array();

if ($filter->plageconsult_id) {
  $plage->load($filter->plageconsult_id);
  $filter->_date_min         = $filter->_date_max = $plage->date;
  $filter->_ref_plageconsult = $plage;
  $where["plageconsult_id"]  = "= '$filter->plageconsult_id'";
}
elseif ($plagesconsult_ids) {
  $first_plage    = new CPlageconsult();
  $last_plage     = new CPlageconsult();
  $plages_consult = explode("|", $plagesconsult_ids);
  $first_plage->load($plages_consult[0]);
  $filter->_date_min        = $filter->_date_max = $first_plage->date;
  $where["plageconsult_id"] = CSQLDataSource::prepareIn($plages_consult);
}
else {
  $where["date"] = "BETWEEN '$filter->_date_min' AND '$filter->_date_max'";

  // Liste des praticiens
  $listPrat         = CConsultation::loadPraticiens(PERM_EDIT, $function_id);
  $where["chir_id"] = CSQLDataSource::prepareIn(array_keys($listPrat), $chir);
}

if ($libelle_plage) {
  $libelle_plage = utf8_decode($libelle_plage);

  $where['libelle'] = " LIKE '%$libelle_plage%'";
}

$order   = array();
$order[] = "date";
$order[] = "chir_id";
$order[] = "debut";
/** @var CPlageconsult[] $listPlage */
$listPlage = $plage->loadList($where, $order);

// Pour chaque plage on selectionne les consultations
foreach ($listPlage as $plage) {
  $plage->listPlace     = array();
  $plage->listPlace2    = array();
  $plage->listBefore    = array();
  $plage->listAfter     = array();
  $plage->listHorsPlace = array();
  $listPlage[$plage->_id]->loadRefs(in_array($canceled, ["all", "canceled"]), 1);

  CMbObject::massLoadFwdRef($plage->_ref_consultations, "sejour_id");

  for ($i = 0; $i <= $plage->_total; $i++) {
    $minutes                               = $plage->_freq * $i;
    $plage->listPlace[$i]["time"]          = CMbDT::time("+ $minutes minutes", $plage->debut);
    $plage->listPlace[$i]["consultations"] = array();
  }

  foreach ($plage->_ref_consultations as $keyConsult => $valConsult) {
    // if the appointment respects the home visit filter
    // if the appointment respects the category filter
    // if the appointment respects the canceled filter
    if (($visite_domicile && !$valConsult->visite_domicile)
      || ($categorie_id && (($valConsult->categorie_id && $valConsult->categorie_id != $categorie_id) || !$valConsult->categorie_id))
      || ($canceled === "not_canceled" && $valConsult->annule) || ($canceled === "canceled" && !$valConsult->annule)) {

      unset($plage->_ref_consultations[$keyConsult]);
      continue;
    }
    /** @var CConsultation $consultation */
    $consultation = $plage->_ref_consultations[$keyConsult];

    $patient = $consultation->loadRefPatient(1);
    $patient->loadIPP();

    if ($consultation->sejour_id) {
      $patient->_ref_curr_affectation = $consultation->loadRefSejour()->loadRefCurrAffectation(CMbDT::date($consultation->_datetime));
      $patient->_ref_curr_affectation->loadView();
      if ($patient->_ref_curr_affectation->_id) {
        $show_lit = true;
      }
    }

    // Chargement de la categorie
    $consultation->loadRefCategorie();
    $consultation->loadRefConsultAnesth();
    $consultation->loadRefPlageConsult();
    $consult_anesth = $consultation->_ref_consult_anesth;
    if ($consult_anesth->operation_id) {
      $consult_anesth->loadRefOperation();
      $consult_anesth->_ref_operation->loadRefPraticien(true);
      $consult_anesth->_ref_operation->loadRefPlageOp(true);
      $consult_anesth->_ref_operation->loadExtCodesCCAM();
      $consult_anesth->_date_op =& $consult_anesth->_ref_operation->_ref_plageop->date;
    }

    $keyPlace = CMbDT::timeCountIntervals($plage->debut, $consultation->heure, $plage->freq);

    for ($i = 0; $i < $consultation->duree; $i++) {
      if (!isset($plage->listPlace[($keyPlace + $i)]["time"])) {
        $plage->listPlace[($keyPlace + $i)]["time"]             = CMbDT::time("+ " . $plage->_freq * $i . " minutes", $consultation->heure);
        @$plage->listPlace[($keyPlace + $i)]["consultations"][] = $consultation;
      }
      else {
        @$plage->listPlace[($keyPlace + $i)]["consultations"][] = $consultation;
      }
    }
  }
}

// Suppression des plages vides
if (!$filter->_plages_vides) {
  foreach ($listPlage as $plage) {
    if (!count($plage->_ref_consultations)) {
      unset($listPlage[$plage->_id]);
    }
  }
}

// Création du template
$smarty = new CSmartyDP();

$smarty->assign("filter", $filter);
$smarty->assign("listPlage", $listPlage);
$smarty->assign("show_lit", $show_lit);
$smarty->assign("visite_domicile", $visite_domicile);

$smarty->display("print_plages.tpl");
