<?php
/**
 * @package Mediboard\Files
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\Module\CModule;
use Ox\Core\CSmartyDP;
use Ox\Core\CSQLDataSource;
use Ox\Core\CStoredObject;
use Ox\Core\CValue;
use Ox\Core\CView;
use Ox\Mediboard\Files\CDocumentItem;
use Ox\Mediboard\Mediusers\CFunctions;
use Ox\Mediboard\Mediusers\CMediusers;

CCanDo::checkAdmin();

// Get Concrete class
$doc_class = CValue::get("doc_class", "CFile");
if (!is_subclass_of($doc_class, CDocumentItem::class)) {
  trigger_error("Wrong '$doc_class' won't inerit from CDocumentItem", E_USER_ERROR);
  return;
}

CView::enableSlave();

$func = new CFunctions();

/** @var CDocumentItem $doc */
$doc = new $doc_class;
$users_stats = $doc->getUsersStats();
$funcs_stats = array();
$groups_stats = array();

$total = array(
  "docs_weight" => 0,
  "docs_count" => 0,
);

if (CModule::getActive("mediusers")) {
  $users_ids = array();
  foreach ($users_stats as $_stat_user) {
    $users_ids[] = $_stat_user["owner_id"];
  }

  $user = new CMediusers();
  $users = $user->loadList(array("user_id" => CSQLDataSource::prepareIn($users_ids)));
  CStoredObject::massLoadFwdRef($users, "function_id");
}

// Stat per user
foreach ($users_stats as &$_stat_user) {
  $total["docs_weight"] += $_stat_user["docs_weight"];
  $total["docs_count"]  += $_stat_user["docs_count"];

  $_stat_user["_docs_average_weight"] = $_stat_user["docs_weight"] / $_stat_user["docs_count"];

  // Make it mediusers uninstalled compliant
  if (CModule::getActive("mediusers")) {
    // Get the owner
    $user = CMediusers::get($_stat_user["owner_id"]);
    $_stat_user["_ref_owner"] = $user;

    if (!$user->_id) {
      continue;
    }

    // Initialize function data
    $function = $user->loadRefFunction();
    if (!isset($funcs_stats[$function->_id])) {
      $funcs_stats[$function->_id] = array(
        "docs_weight" => 0,
        "docs_count"  => 0,
        "_ref_owner"  => $function,
      );
    }
    
    // Cummulate data per function
    $stat_func =& $funcs_stats[$function->_id];
    $stat_func["docs_weight"] += $_stat_user["docs_weight"];
    $stat_func["docs_count" ] += $_stat_user["docs_count" ];

    // Initialize group data
    $group = $function->loadRefGroup();
    if (!isset($groups_stats[$group->_id])) {
      $groups_stats[$group->_id] = array(
        "docs_weight" => 0,
        "docs_count"  => 0,
        "_ref_owner"  => $group,
      );
    }

    // Cummulate data per group
    $stat_group =& $groups_stats[$group->_id];
    $stat_group["docs_weight"] += $_stat_user["docs_weight"];
    $stat_group["docs_count" ] += $_stat_user["docs_count" ];
  }
}

// Get user data percentages
foreach ($users_stats as &$_stat_user) {
  $_stat_user["_docs_weight_percent"] = $_stat_user["docs_weight"] / $total["docs_weight"];
  $_stat_user["_docs_count_percent" ] = $_stat_user["docs_count" ] / $total["docs_count" ];
}

// Get function data percentages
foreach ($funcs_stats as $function_id => &$_stat_func) {
  $_stat_func["_docs_weight_percent"] = $_stat_func["docs_weight"] / $total["docs_weight"];
  $_stat_func["_docs_count_percent" ] = $_stat_func["docs_count" ] / $total["docs_count" ];
  $_stat_func["_docs_average_weight"] = $_stat_func["docs_weight"] / $_stat_func["docs_count"];
}

// Get function data percentages
foreach ($groups_stats as $group_id => &$_stat_group) {
  $_stat_group["_docs_weight_percent"] = $_stat_group["docs_weight"] / $total["docs_weight"];
  $_stat_group["_docs_count_percent" ] = $_stat_group["docs_count" ] / $total["docs_count" ];
  $_stat_group["_docs_average_weight"] = $_stat_group["docs_weight"] / $_stat_group["docs_count"];
}

$total["_docs_average_weight"] = $total["docs_count"] ? ($total["docs_weight"] / $total["docs_count"]) : 0;

// Création du template
$smarty = new CSmartyDP();
$smarty->assign("doc_class", $doc_class);
$smarty->assign("users_stats", $users_stats);
$smarty->assign("funcs_stats", $funcs_stats);
$smarty->assign("groups_stats", $groups_stats);
$smarty->assign("total", $total);
$smarty->display("vw_stats.tpl");
