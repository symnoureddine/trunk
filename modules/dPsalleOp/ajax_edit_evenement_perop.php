<?php
/**
 * @package Mediboard\SalleOp
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CMbDT;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Admin\CAccessMedicalData;
use Ox\Mediboard\PlanningOp\COperation;
use Ox\Mediboard\SalleOp\CAnesthPerop;
use Ox\Mediboard\SalleOp\CAnesthPeropCategorie;

CCanDo::checkEdit();
$evenement_guid = CView::get("evenement_guid", "str");
$operation_id   = CView::get("operation_id", "ref class|COperation");
$datetime       = CView::get("datetime", "dateTime");
$type           = CView::get("type", "str default|perop");
CView::checkin();

$interv = new COperation;
$interv->load($operation_id);

CAccessMedicalData::logAccess($interv);

$interv->loadRefAnesth();
$interv->loadRefPatient();

if (!$datetime) {
  $datetime = CMbDT::date($interv->_datetime) . " " . CMbDT::time();
}

list($evenement_class, $evenement_id) = explode("-", $evenement_guid);

/** @var CAnesthPerop $evenement */
$evenement = new $evenement_class;

if ($evenement_id) {
  $evenement->load($evenement_id);
  $evenement->loadRefsNotes();
  $evenement->loadRefCategorie();

  $geste_perop = $evenement->loadRefGestePerop();
  $precisions = $geste_perop->loadRefPrecisions();

  $evenement_precision = $evenement->loadRefGestePeropPrecision();
  $evenement_precision->loadRefValeurs();
}
else {
  $evenement->datetime = $datetime;
}
$evenement->operation_id = $interv->_id;

$evenement_category   = new CAnesthPeropCategorie();
$evenement_categories = $evenement_category->loadGroupList();

foreach ($evenement_categories as $_categorie) {
  $_categorie->loadRefFile();
}

// Lock add new or edit event
$limit_date_min = null;

if ($interv->entree_reveil && ($type == 'sspi')) {
  $limit_date_min = $interv->entree_reveil;
}

// Création du template
$smarty = new CSmartyDP();
$smarty->assign("evenement"           , $evenement);
$smarty->assign("evenement_categories", $evenement_categories);
$smarty->assign("datetime"            , $datetime);
$smarty->assign("operation"           , $interv);
$smarty->assign("limit_date_min"      , $limit_date_min);
$smarty->display("inc_edit_evenement_perop");
