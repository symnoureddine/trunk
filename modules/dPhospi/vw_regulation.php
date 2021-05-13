<?php
/**
 * @package Mediboard\Hospi
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CMbArray;
use Ox\Core\CMbDT;
use Ox\Core\CRequest;
use Ox\Core\CSmartyDP;
use Ox\Core\CSQLDataSource;
use Ox\Core\CStoredObject;
use Ox\Core\CView;
use Ox\Mediboard\Hospi\CService;
use Ox\Mediboard\Mediusers\CFunctions;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\PlanningOp\CSejour;

CCanDo::checkRead();
$date_min     = CView::get("date_regulation", array("dateTime", "default" => CMbDT::dateTime("-24 hours")));
$services_id  = CView::get("services_id", "str", true);
$types        = CView::get("type", "str", true);
$praticien_id = CView::get("praticien_id", "ref class|CMediusers", true);
$function_id  = CView::get("function_id", "ref class|CFunctions", true);
$type_log     = CView::get("type_log", "enum list|create|store", true);
$see_results  = CView::get("see_results", "bool default|0");
CView::checkin();
CView::enforceSlave();

$services_select = explode(",", $services_id);
CMbArray::removeValue("", $services_select);
$types = explode(",", $types);
CMbArray::removeValue("", $types);

$filter               = new CSejour();
$filter->_date_min    = $date_min;
$filter->praticien_id = $praticien_id;

if ($see_results) {
  // Rercherche des séjours suivants le critère suivant :
  // - Création ou entrée modifiée depuis moins de 24h
  $ds       = CSQLDataSource::get("std");
  $date_max = CMbDT::dateTime("+24 hours", $date_min);

  $sejours_ids = array();
  $request     = new CRequest();
  $request->addSelect("object_id, object_id");
  $request->addTable("user_log");
  $request->addWhereClause("user_log.object_class", "= 'CSejour'");
  $request->addWhereClause("user_log.type", "= 'create'");
  $request->addWhereClause("user_log.date", "BETWEEN '$date_min' AND '$date_max'");
  if (!$type_log || $type_log == "create") {
    // Créations
    $sejours_ids = $ds->loadHashList($request->makeSelect());
  }
  if (!$type_log || $type_log == "store") {
    // Modification de l'entrée
    unset($request->where["user_log.type"]);
    $request->addWhereClause("user_log.type", "= 'store'");
    $request->addWhereClause(null, "(user_log.fields LIKE '%entree_prevue%') OR (user_log.fields LIKE '%entree_reelle%')");
    $sejours_ids += $ds->loadHashList($request->makeSelect());
    $sejours_ids = array_unique($sejours_ids);
  }

  $sejour = new CSejour();
  $ljoin  = array();
  $where  = array(
    "sejour.annule"    => "= '0'",
    "sejour.sejour_id" => CSQLDataSource::prepareIn($sejours_ids)
  );
  if (count($types)) {
    $where["sejour.type"] = CSQLDataSource::prepareIn($types);
  }
  if ($praticien_id) {
    $where["sejour.praticien_id"] = " = '$praticien_id'";
  }
  elseif ($function_id) {
    $ljoin["users_mediboard"]             = "users_mediboard.user_id = sejour.praticien_id";
    $where["users_mediboard.function_id"] = "= '$function_id'";
  }
  if (count($services_select)) {
    $ljoin["affectation"]            = "sejour.sejour_id = affectation.sejour_id";
    $where["affectation.service_id"] = CSQLDataSource::prepareIn($services_select);
    $where[]                         = "affectation.entree <= '$date_max' AND affectation.sortie >= '$date_min'";
  }
  /** @var CSejour[] $sejours */
  $sejours = $sejour->loadGroupList($where, null, null, null, $ljoin);

  /** @var CPatient[] $patients */
  $patients = CStoredObject::massLoadFwdRef($sejours, "patient_id");
  CPatient::massLoadIPP($patients);
  CPatient::massCountPhotoIdentite($patients);
  CSejour::massLoadSurrAffectation($sejours);
  CSejour::massLoadFwdRef($sejours, "praticien_id");
  CStoredObject::massLoadBackRefs($patients, "bmr_bhre");

  foreach ($sejours as $_sejour) {
    $_sejour->loadRefPatient()->loadRefPhotoIdentite();
    $_sejour->loadRefPraticien();
    $_sejour->checkDaysRelative($date_max);
    $_sejour->_ref_patient->updateBMRBHReStatus();
  }

  CMbArray::pluckSort($sejours, SORT_ASC, "_ref_patient", "nom");
}
else {
  $service            = new CService();
  $where              = array();
  $where["cancelled"] = "= '0'";
  $services           = $service->loadListWithPerms(PERM_READ, $where);
  $praticien          = new CMediusers();
  $praticiens         = $praticien->loadPraticiens();
  foreach ($praticiens as $_prat) {
    $_prat->loadRefFunction();
  }
  $function  = new CFunctions();
  $functions = $function->loadSpecialites();
}

// Création du template
$smarty = new CSmartyDP();

if ($see_results) {
  $smarty->assign("sejours", $sejours);
  $smarty->assign("date_min", $date_min);
  $smarty->assign("date_max", $date_max);

  $smarty->display("vw_list_regulation.tpl");
}
else {
  $smarty->assign("services", $services);
  $smarty->assign("praticiens", $praticiens);
  $smarty->assign("filter", $filter);
  $smarty->assign("functions", $functions);
  $smarty->assign("function_id", $function_id);
  $smarty->assign("services_id", $services_select);
  $smarty->assign("types", $types);
  $smarty->assign("type_log", $type_log);

  $smarty->display("vw_regulation.tpl");
}
