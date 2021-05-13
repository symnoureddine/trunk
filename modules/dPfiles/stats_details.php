<?php
/**
 * @package Mediboard\Files
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CMbArray;
use Ox\Core\CMbDT;
use Ox\Core\CSmartyDP;
use Ox\Core\CStoredObject;
use Ox\Core\CView;
use Ox\Mediboard\Files\CDocumentItem;
use Ox\Mediboard\Files\CFilesCategory;

CCanDo::checkAdmin();
$doc_class   = CView::get("doc_class", "str default|CFile");
$doc_id      = CView::get("doc_id", "ref class|$doc_class");
$owner_guid  = CView::get("owner_guid", "str");
$date_min    = CView::get("date_min", "dateTime");
$date_max    = CView::get("date_max", "dateTime");
$period      = CView::get("period", "enum list|year|month|week|day|hour");
CView::checkin();
CView::enableSlave();

// Get concrete class
if (!is_subclass_of($doc_class, CDocumentItem::class)) {
  trigger_error("Wrong '$doc_class' won't inerit from CDocumentItem", E_USER_ERROR);
  return;
}

// Users
$owner = $owner_guid ? CStoredObject::loadFromGuid($owner_guid, true) : null;
$user_ids = CDocumentItem::getUserIds($owner);

// Period alternative to date interval
if ($date_min && $period) {
  $date_max = CMbDT::dateTime("+ 1 $period", $date_min);
}

// Query prepare
/** @var CDocumentItem $doc */
$doc = new $doc_class;
$doc->load($doc_id);
$user_details = $doc->getUsersStatsDetails($user_ids, $date_min, $date_max);

// Reorder and make totals
$class_totals = array();
$class_details = array();
$category_totals = array();
$category_details = array();

$big_totals = array(
  "count"  => array_sum(CMbArray::pluck($user_details, "docs_count")),
  "weight" => array_sum(CMbArray::pluck($user_details, "docs_weight")),
);

// Details
foreach ($user_details as &$_details) {
  $_details["count" ] = $_details["docs_count" ];
  $_details["weight"] = $_details["docs_weight"];
  $_details["count_percent" ] = $_details["count" ] / $big_totals["count" ];
  $_details["weight_percent"] = $_details["weight"] / $big_totals["weight"];
  $_details["weight_average"] = $_details["weight"] / $_details["count"];
}

CMbArray::pluckSort($user_details, SORT_DESC, "weight");

// Totals
$report = error_reporting(0);
foreach ($user_details as $_details) {
  $count  = $_details["count"];
  $weight = $_details["weight"];
  $object_class = $_details["object_class"];
  $category_id  = $_details["category_id"];

  $class_totals[$object_class]["count" ] += $count;
  $class_totals[$object_class]["weight"] += $weight;
  $category_totals[$category_id]["count" ] += $count;
  $category_totals[$category_id]["weight"] += $weight;
}
error_reporting($report);

foreach ($class_totals as &$_total) {
  $_total["count_percent"] =  $_total["count"] / $big_totals["count"];
  $_total["weight_percent"] =  $_total["weight"] / $big_totals["weight"];
  $_total["weight_average"] =  $_total["weight"] / $_total["count"];
}

CMbArray::pluckSort($class_totals, SORT_DESC, "weight");

foreach ($category_totals as &$_total) {
  $_total["count_percent"] =  $_total["count"] / $big_totals["count"];
  $_total["weight_percent"] =  $_total["weight"] / $big_totals["weight"];
  $_total["weight_average"] =  $_total["weight"] / $_total["count"];
}

CMbArray::pluckSort($category_totals, SORT_DESC, "weight");

// All categories
$category = new CFilesCategory();
$categories = $category->loadAll(array_keys($category_totals));

// All classes
$classes = array_keys($class_totals);

// Création du template
$smarty = new CSmartyDP();
$smarty->assign("doc", $doc);
$smarty->assign("owner", $owner);
$smarty->assign("owner_guid", $owner_guid);
$smarty->assign("user_details", $user_details);
$smarty->assign("class_totals", $class_totals);
$smarty->assign("category_totals", $category_totals);
$smarty->assign("big_totals", $big_totals);
$smarty->assign("categories", $categories);
$smarty->assign("classes", $classes);
$smarty->assign("date_min", $date_min);
$smarty->assign("date_max", $date_max);

$smarty->display("stats_details");
