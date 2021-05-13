<?php
/**
 * @package Mediboard\PlanningOp
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CMbDT;
use Ox\Core\Module\CModule;
use Ox\Core\CSmartyDP;
use Ox\Core\CStoredObject;
use Ox\Core\CView;
use Ox\Mediboard\Admin\CAccessMedicalData;
use Ox\Mediboard\Hospi\CItemLiaison;
use Ox\Mediboard\Hospi\CPrestationJournaliere;
use Ox\Mediboard\Hospi\CPrestationPonctuelle;
use Ox\Mediboard\PlanningOp\CSejour;

$sejour_id     = CView::get("sejour_id", "ref class|CSejour");
$relative_date = CView::get("relative_date", "date");

CView::checkin();

$sejour = new CSejour();
$sejour->load($sejour_id);

CAccessMedicalData::logAccess($sejour);

$prestations_j = CPrestationJournaliere::loadCurrentList($sejour->type, $sejour->type_pec);

$dates         = array();
$prestations_p = array();
$liaisons_j    = array();
$liaisons_p    = array();
$liaisons_p_forfait = array();
$date_modif    = array();
$save_state    = array();

$sejour->loadRefPrescriptionSejour();
$sejour->loadRefCurrAffectation();
$sejour->loadRefsOperations();

$dossier_medical_sejour = $sejour->loadRefDossierMedical();
$dossier_medical_sejour->loadRefsAntecedents();

$patient = $sejour->loadRefPatient();
$patient->loadRefPhotoIdentite();
$patient->loadRefLatestConstantes();

$dossier_medical = $patient->loadRefDossierMedical();
$dossier_medical->loadRefsAntecedents();
$dossier_medical->loadRefsAllergies();
$dossier_medical->countAntecedents();
$dossier_medical->countAllergies();

$where_actif = array("actif" => "= '1'");

$items = CStoredObject::massLoadBackRefs($prestations_j, "items", "rank", $where_actif);
CStoredObject::massLoadBackRefs($items, "sous_items", "nom", $where_actif);

foreach ($prestations_j as $_prestation_id => $_prestation) {
  $items = $_prestation->loadRefsItems($where_actif);
  foreach ($items as $_item) {
    $_item->loadRefsSousItems($where_actif);
  }
}

// Droits de modification
$editRights = CModule::getCanDo("dPhospi")->edit;

$duree = CMbDT::daysRelative($sejour->entree, $sejour->sortie);

$date_temp = CMbDT::date($sejour->entree);

while ($date_temp <= CMbDT::date($sejour->sortie)) {
  $dates[$date_temp] = $date_temp;
  $date_temp = CMbDT::date("+1 day", $date_temp);
}

// Gestion des liaisons hors séjour
$dates_after = array();

/** @var CItemLiaison[] $items_liaisons */
$items_liaisons = $sejour->loadRefItemsLiaisons();
CStoredObject::massLoadFwdRef($items_liaisons, "item_souhait_id");
CStoredObject::massLoadFwdRef($items_liaisons, "item_realise_id");
CStoredObject::massLoadFwdRef($items_liaisons, "sous_item_id");

foreach ($items_liaisons as $_item_liaison) {
  if (!$_item_liaison->date) {
    $liaisons_p_forfait[$_item_liaison->item_souhait_id] = $_item_liaison->_id;
    continue;
  }

  if ($_item_liaison->date > CMbDT::date($sejour->sortie)) {
    $dates_after[$_item_liaison->date] = $_item_liaison->date;
  }

  $item_souhait = $_item_liaison->loadRefItem();
  $item_realise = $_item_liaison->loadRefItemRealise();
  $_item_liaison->loadRefSousItem();

  $object_class = $_item_liaison->prestation_id ? "CPrestationJournaliere" : "CPrestationPonctuelle";

  switch ($object_class) {
    case "CPrestationJournaliere":
    default:
      $liaisons_j[$_item_liaison->date][$_item_liaison->prestation_id] = $_item_liaison;

      if (!isset($prestations_j[$_item_liaison->prestation_id])) {
        $prestation = new CPrestationJournaliere();
        $prestation->load($_item_liaison->prestation_id);
        $prestation->loadRefsItems();
        $prestations_j[$_item_liaison->prestation_id] = $prestation;
      }

      if ($item_souhait->_id && !isset($prestations_j[$item_souhait->object_id]->_ref_items[$item_souhait->_id])) {
        $prestations_j[$item_souhait->object_id]->_ref_items[$item_souhait->_id] = $item_souhait;
        $item_souhait->loadRefsSousItems();
      }
      if ($item_realise->_id && !isset($prestations_j[$item_realise->object_id]->_ref_items[$item_realise->_id])) {
        $prestations_j[$item_realise->object_id]->_ref_items[$item_realise->_id] = $item_realise;
      }
      if ($_item_liaison->sous_item_id) {
        $sous_item = $_item_liaison->_ref_sous_item;
        $item      = $sous_item->loadRefItemPrestation();
        $prestations_j[$item->object_id]->_ref_items[$sous_item->item_prestation_id]->_refs_sous_items[$sous_item->_id] = $sous_item;
      }
      break;
    case "CPrestationPonctuelle":
      $liaisons_p[$_item_liaison->date][$_item_liaison->_ref_item->object_id][] = $_item_liaison;

      if (!isset($prestations_p[$item_souhait->object_id])) {
        $prestation = new CPrestationPonctuelle();
        $prestation->load($item_souhait->object_id);
        $prestation->loadRefsItems();
        $prestations_p[$item_souhait->object_id] = $prestation;
      }
  }
}

foreach ($dates as $_date) {
  if (!isset($liaisons_j[$_date])) {
    $liaisons_j[$_date] = array();
  }

  foreach ($prestations_j as $_prestation_id => $_prestation) {
    $item_liaison = new CItemLiaison();
    $item_liaison->_id = "temp";
    $item_liaison->loadRefItem();
    $item_liaison->loadRefItemRealise();
    $item_liaison->loadRefSousItem();

    if (isset($liaisons_j[$_date][$_prestation_id])) {
      $date_modif[$_date] = 1;
      copyLiaison($item_liaison, $liaisons_j[$_date][$_prestation_id]);

      $save_state[$_prestation_id] = $item_liaison;
    }
    elseif (isset($save_state[$_prestation_id])) {
      copyLiaison($item_liaison, $save_state[$_prestation_id]);

      $liaisons_j[$_date][$_prestation_id] = $item_liaison;
    }
  }
}

/**
 * Clone des propriétés de liaison
 *
 * @param CItemLiaison $item_dest   La destination
 * @param CItemLiaison $item_source La source
 *
 * @return void
 */
function copyLiaison(&$item_dest, $item_source) {
  $item_dest->item_souhait_id          = $item_source->item_souhait_id;
  $item_dest->item_realise_id          = $item_source->item_realise_id;
  $item_dest->sous_item_id             = $item_source->sous_item_id;
  $item_dest->_ref_item->_id           = $item_source->_ref_item->_id;
  $item_dest->_ref_item->nom           = $item_source->_ref_item->nom;
  $item_dest->_ref_item->object_id     = $item_source->_ref_item->object_id;
  $item_dest->_ref_item->rank          = $item_source->_ref_item->rank;
  $item_dest->_ref_item->color         = $item_source->_ref_item->color;
  $item_dest->_ref_item->actif         = $item_source->_ref_item->actif;
  $item_dest->_ref_item_realise->_id   = $item_source->_ref_item_realise->_id;
  $item_dest->_ref_item_realise->nom   = $item_source->_ref_item_realise->nom;
  $item_dest->_ref_item_realise->object_id = $item_source->_ref_item_realise->object_id;
  $item_dest->_ref_item_realise->rank  = $item_source->_ref_item_realise->rank;
  $item_dest->_ref_item_realise->color = $item_source->_ref_item_realise->color;
  $item_dest->_ref_item_realise->actif = $item_source->_ref_item_realise->actif;
  $item_dest->_ref_sous_item->_id      = $item_source->_ref_sous_item->_id;
  $item_dest->_ref_sous_item->nom      = $item_source->_ref_sous_item->nom;
  $item_dest->_ref_sous_item->actif    = $item_source->_ref_sous_item->actif;
  $item_dest->_ref_sous_item->item_prestation_id = $item_source->_ref_sous_item->item_prestation_id;
}

$empty_liaison = new CItemLiaison();
$empty_liaison->_id = "temp";
$empty_liaison->loadRefItem();
$empty_liaison->loadRefItemRealise();

// La date pour l'ajout d'une prestation ponctuelle doit être dans les dates du séjour
// Si la date actuelle est hors des bornes, alors réinitialisation à la date d'entrée du séjour
$today_ponctuelle = CMbDT::date();
if ($today_ponctuelle < CMbDT::date($sejour->entree) || $today_ponctuelle > CMbDT::date($sejour->sortie)) {
  $today_ponctuelle = CMbDT::date($sejour->entree);
}

// Prestation ponctuelles au forfait
$prestations_p_forfait = CPrestationPonctuelle::loadCurrentListForfait($sejour->type, $sejour->type_pec);

CStoredObject::massLoadBackRefs($prestations_p_forfait, "items");
foreach ($prestations_p_forfait as $_prestation) {
  $_prestation->loadRefsItems();
}

$smarty = new CSmartyDP();

$smarty->assign("today_ponctuelle", $today_ponctuelle);
$smarty->assign("dates"        , $dates);
$smarty->assign("dates_after"  , $dates_after);
$smarty->assign("relative_date", $relative_date);
$smarty->assign("sejour"       , $sejour);
$smarty->assign("prestations_j", $prestations_j);
$smarty->assign("prestations_p", $prestations_p);
$smarty->assign("empty_liaison", $empty_liaison);
$smarty->assign("liaisons_p"   , $liaisons_p);
$smarty->assign("liaisons_j"   , $liaisons_j);
$smarty->assign("liaisons_p_forfait", $liaisons_p_forfait);
$smarty->assign("date_modified", $date_modif);
$smarty->assign("editRights"   , $editRights);
$smarty->assign("bank_holidays", CMbDT::getHolidays(CMbDT::date()));
$smarty->assign("prestations_p_forfait", $prestations_p_forfait);

$smarty->display("inc_vw_prestations");
