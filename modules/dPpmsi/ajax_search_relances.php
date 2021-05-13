<?php
/**
 * @package Mediboard\Pmsi
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbArray;
use Ox\Core\CMbDT;
use Ox\Core\CSmartyDP;
use Ox\Core\CStoredObject;
use Ox\Core\CView;
use Ox\Core\FileUtil\CCSVFile;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\PlanningOp\CSejour;
use Ox\Mediboard\Pmsi\CRelancePMSI;

CCanDo::checkEdit();
$status           = CView::get("status", "str", true);
$urgence          = CView::get("urgence", "str", true);
$type_doc         = CView::get("type_doc", "str", true);
$commentaire_med  = CView::get("commentaire_med", "str", true);
$chir_id          = CView::get("chir_id", "ref class|CMediusers", true);
$date_min_relance = CView::get("date_min_relance", "date", true);
$date_max_relance = CView::get("date_max_relance", "date", true);
$date_min_sejour  = CView::get("date_min_sejour", "date", true);
$date_max_sejour  = CView::get("date_max_sejour", "date", true);
$NDA              = CView::get("NDA", "str");
$export           = CView::get('export', 'bool default|0');
$type_sejour      = CView::get('type', "enum list|" . implode("|", CSejour::$types), true);
CView::checkin();

$where = array();
$ljoin = array();

$relances = array();
$sejour_exist = 0;

if ($NDA) {
  $sejour = new CSejour();
  $sejour->loadFromNDA($NDA);
  $sejour->loadRefPatient();

  if ($sejour->_id) {
    $relance            = new CRelancePMSI();
    $relance->sejour_id = $sejour->_id;
    $relance->loadMatchingObject();

    if ($relance->_id) {
      $relances[] = $relance;
    }

    $sejour_exist = 1;
  }
}
else {
  $ljoin["sejour"] = "sejour.sejour_id = relance_pmsi.sejour_id";
  $where["group_id"] = "= '".CGroups::get()->_id."'";
  if ($date_min_sejour && $date_max_sejour) {
    $where[] = "DATE(sejour.entree) < '$date_max_sejour'";
    $where[] = "DATE(sejour.sortie) > '$date_min_sejour'";
  }

  if ($status) {
    switch ($status) {
      case "non_cloturees":
        $where["datetime_cloture"] = "IS NULL";
        break;
      case "datetime_creation":
        $where["datetime_relance"] = "IS NULL";
        $where["datetime_cloture"] = "IS NULL";
        break;
      case "datetime_relance":
        $where["datetime_relance"] = "IS NOT NULL";
        $where["datetime_cloture"] = "IS NULL";
        break;
      case "datetime_cloture":
        $where["datetime_cloture"] = "IS NOT NULL";
        break;
      default:
    }
  }

  if ($urgence) {
    $where["urgence"] = "= '$urgence'";
  }

  if ($type_doc) {
    $where[$type_doc] = "= '1'";
  }

  if ($commentaire_med != "") {
    $where["commentaire_med"] = "IS " . ($commentaire_med == "1" ? "NOT" : "") . " NULL";
  }

  if ($chir_id) {
    $where["chir_id"] = "= '$chir_id'";
  }

  if($type_sejour){
      $where["type"] = "= '".$type_sejour."'";
  }

  $where[] = "DATE(relance_pmsi.datetime_creation) BETWEEN '$date_min_relance' AND '$date_max_relance'";

  $relance  = new CRelancePMSI();
  $relances = $relance->loadList($where, "datetime_creation DESC", null, null, $ljoin);
}

CStoredObject::massLoadFwdRef($relances, "patient_id");
CStoredObject::massLoadFwdRef($relances, "chir_id");

$sejours = CStoredObject::massLoadFwdRef($relances, "sejour_id");

CSejour::massLoadNDA($sejours);

foreach ($relances as $_relance) {
  $_relance->loadRefSejour();
  $_relance->loadRefPatient();
  $_relance->loadRefChir();
}

array_multisort(
  CMbArray::pluck($relances, "_ref_patient", "nom")   , SORT_ASC,
  CMbArray::pluck($relances, "_ref_patient", "prenom"), SORT_ASC,
  $relances
);

// Relances par praticien (pour l'impression)
$relances_by_prat = array();
$prats = array();

foreach ($relances as $_relance) {
  if (!isset($relances_by_prat[$_relance->chir_id])) {
    $relances_by_prat[$_relance->chir_id] = array();
    $prats[$_relance->chir_id] = $_relance->_ref_chir;
  }

  $relances_by_prat[$_relance->chir_id][] = $_relance;
}

// Tri par nom de praticien
array_multisort(
  CMbArray::pluck($prats, "_user_last_name") , SORT_ASC,
  CMbArray::pluck($prats, "_user_first_name"), SORT_ASC,
  $prats
);

// La fonction array_multisort écrase les clés numériques
$prats_sorted = array();
$relances_by_prat_sorted = array();
foreach ($prats as $_prat) {
  $prats_sorted[$_prat->_id] = $_prat;
  $relances_by_prat_sorted[$_prat->_id] = $relances_by_prat[$_prat->_id];
}

$prats = $prats_sorted;
$relances_by_prat = $relances_by_prat_sorted;

$chir = new CMediusers();
if ($chir_id) {
  $chir = CMediusers::get($chir_id);
}

if (!$export) {
  // Création du template
  $smarty = new CSmartyDP();
  $smarty->assign("relances"        , $relances);
  $smarty->assign("relances_by_prat", $relances_by_prat);
  $smarty->assign("prats"           , $prats);
  $smarty->assign("date_min_relance", $date_min_relance);
  $smarty->assign("date_max_relance", $date_max_relance);
  $smarty->assign("status"          , $status);
  $smarty->assign("urgence"         , $urgence);
  $smarty->assign("type_doc"        , $type_doc);
  $smarty->assign("commentaire_med" , $commentaire_med);
  $smarty->assign("chir"            , $chir);
  $smarty->assign("sejour_exist"    , $sejour_exist);

  if ($sejour_exist) {
    $smarty->assign("sejour"          , $sejour);
  }

  $smarty->display("inc_search_relances");
}
else {
  $csv = new CCSVFile();

  $titles = array(
    CAppUI::tr("CPatient-NDA"),
    CAppUI::tr("CPatient"),
    CAppUI::tr("CSejour-_date_entree"),
    CAppUI::tr("CRelancePMSI-Statistics-court"),
    CAppUI::tr("CRelancePMSI-Responsible Physician-court"),
    CAppUI::tr("CRelancePMSI-Restate Status"),
    CAppUI::tr("CRelancePMSI-cro"),
    CAppUI::tr("CRelancePMSI-crana"),
    CAppUI::tr("CRelancePMSI-cra"),
    CAppUI::tr("CRelancePMSI-ls"),
    CAppUI::tr("CRelancePMSI-cotation"),
    CAppUI::tr("CRelancePMSI-autre"),
    CAppUI::tr("CRelancePMSI-commentaire_dim"),
    CAppUI::tr("CRelancePMSI-commentaire_med-court"),
    CAppUI::tr("CRelancePMSI-Level")
  );
  $csv->writeLine($titles);

  foreach ($relances as $_relance) {
    $sejour    = $_relance->_ref_sejour;
    $patient   = $_relance->_ref_patient;
    $praticien = $_relance->_ref_chir;
    $statut_relance = "Relance";

    if ($_relance->datetime_cloture) {
      $statut_relance = "Clôturée";
    }
    elseif ($_relance->datetime_relance) {
      $statut_relance = "2ème relance";
    }

    $data_line = array(
      $sejour->_NDA,
      $patient->_view,
      CMbDT::format($sejour->sortie, CAppUI::conf("datetime")),
      $sejour->sortie_reelle ? "Term." : "En cours",
      $praticien->_view,
      $statut_relance,
    );

    foreach (CRelancePMSI::$docs as $_doc) {
      if (CAppUI::gconf("dPpmsi relances $_doc")) {
        $data_line[] = $_relance->$_doc ? "X" : "";
      }
    }

    $data_line[] = $_relance->commentaire_dim;
    $data_line[] = $_relance->commentaire_med;
    $data_line[] = $_relance->urgence;

    $csv->writeLine($data_line);
  }

  $period = "du_" . CMbDT::format($date_min_relance, "%d_%m_%Y"). "_" . "_au_" . CMbDT::format($date_max_relance, "%d_%m_%Y");
  $csv->stream("relances_" . $period);
  CApp::rip();
}

