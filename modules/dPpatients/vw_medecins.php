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
use Ox\Core\CSmartyDP;
use Ox\Core\CValue;
use Ox\Core\CView;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Patients\CMedecin;

CCanDo::checkRead();

$dialog     = CValue::get("dialog");
$medecin_id = CValue::getOrSession("medecin_id");

// Parametre de tri
$order_way = CValue::getOrSession("order_way", "DESC");
$order_col = CValue::getOrSession("order_col", "ccmu");

// Mode annuaire
$annuaire = CValue::get("annuaire", 0);

// pagination
$start_med = CValue::get("start_med", 0);
$step_med  = CValue::get("step_med", 20);

$medecin = new CMedecin();
$ds      = $medecin->getDS();

// Récuperation des médecins recherchés
if ($dialog) {
  $medecin_nom         = CView::get("nom", "str");
  $medecin_prenom      = CView::get("prenom", "str");
  $medecin_function_id = CView::get("function_id", "ref class|CFunctions");
  $medecin_cp          = CView::get("cp", "numchar");
  $medecin_ville       = CView::get("ville", "str");
  $medecin_type        = CView::get(
    "type", "enum list|"
    . implode('|', CMedecin::$types)
    . "|pharmacie|maison_medicale|autre default|medecin"
  );
  $medecin_disciplines = CView::get("disciplines", "text");
  $actif               = CView::get("actif", "bool");
  $rpps                = CView::get("rpps", "numchar", true);
}
else {
  $medecin_nom         = CView::get("nom", "str", true);
  $medecin_prenom      = CView::get("prenom", "str", true);
  $medecin_function_id = CView::get("function_id", "ref class|CFunctions", true);
  $medecin_cp          = CView::get("cp", "numchar", true);
  $medecin_ville       = CView::get("ville", "str", true);
  $medecin_type        = CView::get(
    "type", "enum list|"
    . implode('|', CMedecin::$types)
    . "|pharmacie|maison_medicale|autre default|medecin", true
  );
  $medecin_disciplines = CView::get("disciplines", "text", true);
  $actif               = CView::get("actif", "bool", true);
  $rpps                = CView::get("rpps", "numchar", true);
}

CView::checkin();

$where = array();

$current_user = CMediusers::get();
$is_admin     = $current_user->isAdmin();

if ($rpps && strlen($rpps) === 11) {
  $where["rpps"] = $ds->prepareLike($rpps);
}
else {
  if ($annuaire) {
    // Cas de la consultation en annuaire
    $where["function_id"] = "IS NULL";
    if (CAppUI::isGroup()) {
      $where["group_id"] = "IS NULL";
    }
  }
  elseif ($medecin_function_id && $is_admin) {
    // Cas de la consultation en administrateur, filtré sur une fonction
    $where["function_id"] = "= '$medecin_function_id'";
  }
  elseif (CAppUI::isCabinet() && !$is_admin) {
    // Cas du cloisonnement en non administrateur
    $where["function_id"] = "= '$current_user->function_id'";
  }
  elseif (CAppUI::isGroup() && !$is_admin) {
    $where["group_id"] = "= '" . $current_user->loadRefFunction()->group_id . "'";
  }

  if ($medecin_nom) {
    $medecin_nom  = stripslashes($medecin_nom);
    $where["nom"] = $ds->prepareLike("$medecin_nom%");
  }

  if ($medecin_prenom) {
    $where["prenom"] = $ds->prepareLike("%$medecin_prenom%");
  }

  if ($medecin_disciplines) {
    $where["disciplines"] = $ds->prepareLike("%$medecin_disciplines%");
  }

  $where["actif"] = "= '$actif'";

  if ($medecin_cp && $medecin_cp != "00") {
    $cps = preg_split("/\s*[\s\|,]\s*/", $medecin_cp);
    CMbArray::removeValue("", $cps);

    $where_cp = array();
    foreach ($cps as $cp) {
      $where_cp[] = "cp LIKE '" . $cp . "%'";
    }

    $where[] = implode(" OR ", $where_cp);
  }

  if ($medecin_ville) {
    $where["ville"] = $ds->prepareLike("%$medecin_ville%");
  }

  if ($medecin_type) {
    $where["type"] = "= '$medecin_type'";
  }
}

$order = "nom, prenom";

if ($order_col == "cp") {
  $order = "cp $order_way, nom, prenom";
}
else {
  if ($order_col == "ville") {
    $order = "ville $order_way, nom, prenom";
  }
}

$medecin = new CMedecin();

$count_medecins = $medecin->countList($where);
/** @var CMedecin[] $medecins */
$medecins = $medecin->loadList($where, $order, "$start_med, $step_med");
foreach ($medecins as $_medecin) {
  $_medecin->loadRefFunction();
}

$list_types = $medecin->_specs['type']->_locales;

// Création du template
$smarty = new CSmartyDP();

$smarty->assign("is_admin"      , $is_admin);
$smarty->assign("dialog"        , $dialog);
$smarty->assign("annuaire"      , $annuaire);
$smarty->assign("nom"           , $medecin_nom);
$smarty->assign("prenom"        , $medecin_prenom);
$smarty->assign("cp"            , $medecin_cp);
$smarty->assign("type"          , $medecin_type);
$smarty->assign("medecins"      , $medecins);
$smarty->assign("medecin"       , $medecin);
$smarty->assign("list_types"    , $list_types);
$smarty->assign("count_medecins", $count_medecins);
$smarty->assign("order_col"     , $order_col);
$smarty->assign("order_way"     , $order_way);
$smarty->assign("start_med"     , $start_med);
$smarty->assign("step_med"      , $step_med);

$smarty->display("vw_medecins.tpl");
