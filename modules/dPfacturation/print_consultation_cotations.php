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
use Ox\Mediboard\Cabinet\CConsultationCategorie;
use Ox\Mediboard\Ccam\CActe;
use Ox\Mediboard\Facturation\CFactureCabinet;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Facturation\CFacture;
use Ox\Mediboard\Facturation\CFactureEtablissement;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Tarmed\CActeCaisse;
use Ox\Mediboard\Tarmed\CActeTarmed;

CCanDo::checkRead();

$date_min      = CView::get("_date_min", "date default|now", true);
$date_max      = CView::get("_date_max", "date default|now", true);
$chir_id       = CView::get("chir_id", "str", true);
$categorie_id  = CView::get("categorie_id", "ref class|CConsultationCategorie", true);
$page          = CView::get("page", "str default|0");
$csv           = CView::get("csv", "bool default|0");

CView::checkin();
CView::enableSlave();

$praticien = CMediusers::get($chir_id);

$categorie = new CConsultationCategorie();
$where[] = "`praticien_id` = '$chir_id' OR `function_id` = '$praticien->function_id'";
$categories = $categorie->loadList($where, "nom_categorie");

$actes = array();
if ($categorie_id && array_key_exists($categorie_id, $categories)) {
  $ljoin = array();
  $ljoin["consultation"] = "acte_tarmed.object_id = consultation.consultation_id AND acte_tarmed.object_class = 'CConsultation'";
  $ljoin["plageconsult"] = "consultation.plageconsult_id = plageconsult.plageconsult_id";

  $where = array();
  $where[] = "plageconsult.date BETWEEN '$date_min' AND '$date_max'";
  $where["plageconsult.chir_id"] = " = '$chir_id'";
  $where["consultation.categorie_id"] = " = '$categorie_id'";
  $where["consultation.patient_id"] = " IS NOT NULL";

  $order = "plageconsult.date, plageconsult.debut";

  $acte_tarmed = new CActeTarmed();
  $actes_tarmed = $acte_tarmed->loadList($where, $order, null, "acte_tarmed.acte_tarmed_id", $ljoin);

  unset($ljoin["acte_tarmed"]);
  $ljoin["consultation"] = "acte_caisse.object_id = consultation.consultation_id AND acte_caisse.object_class = 'CConsultation'";
  $acte_caisse = new CActeCaisse();
  $actes_caisse = $acte_caisse->loadList($where, $order, null, "acte_caisse.acte_caisse_id", $ljoin);

  $actes = array_merge($actes_tarmed, $actes_caisse);
}

$consult_par_page = 20;
if (!$csv) {
  $nb_actes = count($actes);
  $actes    = array_chunk($actes, $consult_par_page, true);
  $actes    = isset($actes[($page / $consult_par_page)]) ? $actes[($page / $consult_par_page)] : array();

  /* @var CActe[] $actes*/
  foreach ($actes as $_acte) {
    $_consultation = $_acte->loadTargetObject();
    $_consultation->loadRefPlageConsult();
    $_consultation->loadRefPatient();
    if ($_acte->_class == "CActeCaisse") {
      $_acte->loadRefPrestationCaisse();
    }
  }

  $smarty = new CSmartyDP();
  $smarty->assign("categories"  , $categories);
  $smarty->assign("actes"       , $actes);
  $smarty->assign("categorie_id", $categorie_id);
  $smarty->assign("nb_actes"    , $nb_actes);
  $smarty->assign("page"        , $page);
  $smarty->display("print_consultation_cotations");
}
else {

  $file = new CCSVFile();
  //Titres
  $titles = array(
    CAppUI::tr("CPatient-nom"),
    CAppUI::tr("CPatient-prenom"),
    CAppUI::tr("CPatient-naissance"),
    CAppUI::tr("Export-Date_intervention"),
    CAppUI::tr("Export-operateur1"),
    CAppUI::tr("Export-tarmed"),
    CAppUI::tr("CActeTarmed-cote"),
    CAppUI::tr("Export-hors_tarmed"),
    CAppUI::tr("CEditPdf.diagnostic"),
    CAppUI::tr("Export-temps_operatoire"),
    CAppUI::tr("Export-operateur2"),
    CAppUI::tr("Export-complications"),
    CAppUI::tr("Export-duree_hop")
  );

  $file->writeLine($titles);

  /* @var CActe[] $actes*/
  foreach ($actes as $_acte) {
    $_consultation = $_acte->loadTargetObject();
    $plage_consult = $_consultation->loadRefPlageConsult();
    $patient = $_consultation->loadRefPatient();
    if ($_acte->_class == "CActeCaisse") {
      $_acte->loadRefPrestationCaisse();
    }

    $line = array(
      $patient->nom,
      $patient->prenom,
      CMbDT::format($patient->naissance, CAppUI::conf("date")),
      CMbDT::format($_consultation->_date, CAppUI::conf("date")),
      $plage_consult->_ref_chir->_view,
      $_acte->_class == "CActeTarmed" ? $_acte->code : "",
      $_acte->_class == "CActeTarmed" && $_acte->cote ? CAppUI::tr("CActeTarmed.cote.".$_acte->cote) : "",
      $_acte->_class == "CActeCaisse" ? $_acte->code.": ".$_acte->_ref_prestation_caisse->libelle : "",
      $_consultation->motif,
      $_consultation->rques,
      $_consultation->examen,
      $_consultation->histoire_maladie,
      $_consultation->conclusion,
    );
    $file->writeLine($line);
  }

  $file_name = CAppUI::tr("CFacture.cotations_consultation")."_".$praticien->_view."_";
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