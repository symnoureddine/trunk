<?php
/**
 * @package Mediboard\Facturation
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbDT;
use Ox\Core\CSmartyDP;
use Ox\Core\CSQLDataSource;
use Ox\Core\CView;
use Ox\Core\FileUtil\CCSVFile;
use Ox\Mediboard\Cabinet\CConsultation;
use Ox\Mediboard\Facturation\CFactureCabinet;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Facturation\CFacture;
use Ox\Mediboard\Facturation\CFactureEtablissement;

CCanDo::checkRead();

$date_min      = CView::get("_date_min", "date default|now", true);
$date_max      = CView::get("_date_max", "date default|now", true);
$chir_id       = CView::get("chir_id", "str", true);
$state         = CView::get("state", "enum list|waiting|paid|send|unpaid|error");
$facture_class = CView::get("facture_class", "enum list|CFactureCabinet|CFactureEtablissement");
$page          = CView::get("page", "str default|0");
$csv           = CView::get("csv", "bool default|0");

CView::checkin();
CView::enableSlave();

$listPrat = CConsultation::loadPraticiensCompta($chir_id);
$facture_par_page = 20;

/** @var CFactureCabinet|CFactureEtablissement $facture */
$facture = new $facture_class();
$facture_table = $facture->_spec->table;

$factures = array();
foreach (array("CConsultation" => "consultation", "CEvenementPatient" => "evenement_patient") as $_class => $_table) {
  $where = array(
    "$facture_table.annule"   => "= '0'",
    "$facture_table.group_id" => "= '" . CGroups::get()->_id . "'",
    "$facture_table.cloture" => "IS NOT NULL",
  );
  $ljoin = array(
    "facture_liaison" => "facture_liaison.facture_id = $facture_table.facture_id"
      ." AND facture_class = '$facture_class'"
      ." AND object_class = '$_class'",
    $_table => "$_table." . $_table ."_id = facture_liaison.object_id",
  );
  $date_field = "$_table.date";
  if ($_class === "CConsultation") {
    $ljoin["plageconsult"] = "consultation.plageconsult_id = plageconsult.plageconsult_id";
    $date_field = "plageconsult.date";
  }

  if (in_array($state, array("waiting", "send", "error"))) {
    $facture_send_states = array("waiting" => "non_envoye", "send" => "envoye", "error" => "echec");
    $where["$facture_table.statut_envoi"] = "= '". $facture_send_states[$state] ."'";
  }
  if ($date_min && $date_max) {
    $where[] = "$date_field BETWEEN '$date_min' AND '$date_max'";
  }
  elseif ($date_min) {
    $where[$date_field] = "> '$date_min'";
  }
  elseif ($date_max) {
    $where[$date_field]= "< '$date_max'";
  }
  $where[$facture_table.".praticien_id"] = CSQLDataSource::prepareIn(array_keys($listPrat));
  $factures = $factures + $facture->loadList(
    $where,
    "$facture_table.facture_id",
    null,
    "$facture_table.facture_id",
    $ljoin
  );
}

if (in_array($state, array("paid", "unpaid", "waiting"))) {
  foreach ($factures as $_facture_id => $_facture) {
    $_facture->loadRefsReglements();
    $montant = CFacture::roundValue($_facture->_montant_avec_remise);
    // Récupération manuelle du montant restant dans le cas d'un _montant_avec_remise présumé faux
    if ($montant <= 0) {
      $montant = $_facture->du_patient + $_facture->du_tiers - $_facture->remise;
    }
    if ((in_array($state, array("unpaid", "waiting")) && ($montant <= $_facture->_reglements_total))
        || ($state === "paid" && ($montant > $_facture->_reglements_total))
    ) {
      unset($factures[$_facture_id]);
    }
  }
}

if (!$csv) {
  $nb_factures = count($factures);
  $factures    = array_chunk($factures, $facture_par_page, true);
  $factures    = isset($factures[($page / $facture_par_page)]) ? $factures[($page / $facture_par_page)] : array();

  foreach ($factures as $_facture) {
    $_facture->loadRefsReglements();
    $_facture->loadRefsObjects();
    $_facture->loadRefPatient();
  }

  $smarty = new CSmartyDP();
  $smarty->assign("state"        , $state);
  $smarty->assign("facture_class", $facture_class);
  $smarty->assign("factures"     , $factures);
  $smarty->assign("page"         , $page);
  $smarty->assign("nb_factures"  , $nb_factures);
  $smarty->display("print_autre");
}
else {

  $file = new CCSVFile();
  //Titres
  $titles = array(
    CAppUI::tr("CFacture-date"),
    CAppUI::tr("CFactureCabinet-numero"),
    CAppUI::tr("CPatient-nom"),
    CAppUI::tr("CPatient-prenom"),
    CAppUI::tr($facture_class === "CFactureEtablissement" ? "CSejour-date" : "CConsultation-date"),
    CAppUI::tr("CFactureCabinet-amount-invoice"),
    CAppUI::tr("CFactureCabinet-amount-paid"),
    CAppUI::tr("CFactureCabinet-amount-unpaid"),
  );
  if ($state === "paid") {
    $titles[] = CAppUI::tr("CFactureEtablissement-patient_date_reglement");
  }
  $file->writeLine($titles);

  foreach ($factures as $_facture) {
    /* @var $_facture CFacture */
    $_facture->loadRefsObjects();
    $_facture->loadRefPatient();
    $_facture->loadRefsReglements();
    $date_element = "";
    if ($facture_class === "CFactureEtablissement") {
      $date_element = CMbDT::format($_facture->_ref_last_sejour->entree_prevue, CAppUI::conf('datetime'));
    }
    else {
      if ($_facture->_ref_last_consult->_id) {
        $_facture->_ref_last_consult->loadRefPlageConsult();
        $date_element = CMbDT::format($_facture->_ref_last_consult->_datetime, CAppUI::conf('datetime'));
      }
      elseif ($_facture->_ref_last_evt->_id) {
        $date_element = CAppUI::tr("CEvenementPatient")
          ." - ".CMbDT::format($_facture->_ref_last_evt->date, CAppUI::conf('datetime'));
      }
    }
    $line = array(
      $_facture->cloture ? : $_facture->ouverture,
      $_facture->_view,
      $_facture->_ref_patient->nom,
      $_facture->_ref_patient->prenom,
      $date_element,
      $_facture->_montant_avec_remise,
      $_facture->_reglements_total,
      $_facture->_du_restant,
    );
    if ($state === "paid") {
      $line[] = $_facture->patient_date_reglement;
    }

    $file->writeLine($line);
  }

  $file_name = CAppUI::tr("Compta.print")."_";
  if ($chir_id && $listPrat[$chir_id]) {
    $file_name.= $listPrat[$chir_id]->_user_first_name."_".$listPrat[$chir_id]->_user_last_name."_";
  }
  if ($date_min != $date_max) {
    $file_name .= CMbDT::format($date_min, '%d-%m-%Y')
      ."_".CAppUI::tr("date.to")."_".CMbDT::format($date_max, '%d-%m-%Y');
  }
  else {
    $file_name .= CMbDT::format($date_min, '%d-%m-%Y');
  }

  $file->stream($file_name);
  CApp::rip();
}