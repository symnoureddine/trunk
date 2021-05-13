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
use Ox\Mediboard\Facturation\CFacture;
use Ox\Mediboard\Facturation\CFactureEtablissement;

CCanDo::checkRead();

$date_min      = CView::get("_date_min", "date default|now", true);
$date_max      = CView::get("_date_max", "date default|now", true);
$chir_id       = CView::get("chir_id", "str", true);
$page          = CView::get("page", "str default|0");
$csv           = CView::get("csv", "bool default|0");

CView::checkin();
CView::enableSlave();

$listPrat = CConsultation::loadPraticiensCompta($chir_id);
$facture_par_page = 20;
$facture_index    = $page / $facture_par_page;

$factures = array();
foreach (array(CFactureCabinet::class, CFactureEtablissement::class) as $facture_class) {
  /** @var CFactureEtablissement|CFactureCabinet $facture*/
  $facture = new $facture_class();

  $ljoin = array(
    "facture_liaison" => "facture_liaison.facture_id = " . $facture->_spec->table . ".facture_id"
       . " AND facture_liaison.facture_class = '" . $facture->_class . "'",
    "consultation"    => "consultation.consultation_id = facture_liaison.object_id"
       . " AND facture_liaison.object_class = 'CConsultation'",
    "plageconsult"    => "plageconsult.plageconsult_id = consultation.plageconsult_id"
  );
  $where = array(
    "plageconsult.date BETWEEN '$date_min' AND '$date_max'",
    $facture->_spec->table.".annule" => "= '0'",
    $facture->_spec->table.".cloture" => "IS NOT NULL",
    $facture->_spec->table.".praticien_id" =>  CSQLDataSource::prepareIn(array_keys($listPrat)),
  );
  $factures = array_merge($factures, $facture->loadList($where, null, null, $facture->_spec->table . ".facture_id", $ljoin));
}

if (!$csv) {
  $nb_factures = count($factures);
  $factures    = array_chunk($factures, $facture_par_page, true);
  $factures    = isset($factures[$facture_index]) ? $factures[$facture_index] : array();
}
$montants = array();
foreach ($factures as $_facture) {
  /** @var CFactureEtablissement|CFactureCabinet $_facture */
  $_facture->loadRefsReglements();
  $_facture->loadRefPatient();
  $_facture->loadRefsObjects();
  $_facture->loadRefsItems();
  $tarmed = 0;
  $caisse = 0;
  foreach ($_facture->_ref_items as $_item) {
    $tarmed += $_item->type === "CActeTarmed" ? ($_item->pm + $_item->pt) * $_item->coeff * $_item->quantite : 0;
    $caisse += $_item->type === "CActeCaisse" ? ($_item->pm + $_item->pt) * $_item->coeff * $_item->quantite : 0;
  }
  $montants[$_facture->_id] = array(
    "tarmed" => sprintf("%.2f", CFacture::roundValue($tarmed)),
    "caisse" => sprintf("%.2f", CFacture::roundValue($caisse)),
  );
}
if (!$csv) {
  $smarty = new CSmartyDP();
  $smarty->assign("factures",    $factures);
  $smarty->assign("montants",    $montants);
  $smarty->assign("page",        $page);
  $smarty->assign("nb_factures", $nb_factures);
  $smarty->display("print_autre_tarmed_cot");}
else {
  $file = new CCSVFile();
  //Titres
  $titles = array(
    CAppUI::tr("CFacture-date"),
    CAppUI::tr("CFactureCabinet-numero"),
    CAppUI::tr("CPatient-nom"),
    CAppUI::tr("CPatient-prenom"),
    CAppUI::tr("CConsultation-derniere"),
    CAppUI::tr("CFactureCabinet-amount-invoice"),
    CAppUI::tr("CActeTarmed"),
    CAppUI::tr("CActeCaisse"),
  );
  $file->writeLine($titles);

  foreach ($factures as $_facture) {
    /* @var $_facture CFacture */
    if ($_facture->_ref_last_consult->_id) {
      $_facture->_ref_last_consult->loadRefPlageConsult();
      $date_element = CMbDT::format($_facture->_ref_last_consult->_datetime, CAppUI::conf('datetime'));
    }
    elseif ($_facture->_ref_last_evt->_id) {
      $date_element = CAppUI::tr("CEvenementPatient")
        ." - ".CMbDT::format($_facture->_ref_last_evt->date, CAppUI::conf('datetime'));
    }
    $montant = $montants[$_facture->_id];
    $line = array(
      $_facture->cloture ? : $_facture->ouverture,
      $_facture->_view,
      $_facture->_ref_patient->nom,
      $_facture->_ref_patient->prenom,
      $date_element,
      $_facture->_montant_avec_remise,
      $montant["tarmed"],
      $montant["caisse"],
    );

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
