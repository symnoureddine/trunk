<?php
/**
 * @package Mediboard\PlanningOp
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CMbArray;
use Ox\Core\CSmartyDP;
use Ox\Core\CStoredObject;
use Ox\Core\CView;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Mediusers\CFunctions;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\PlanningOp\CProtocoleOperatoire;

CCanDo::checkEdit();

$chir_id                 = CView::get("chir_id", "ref class|CMediusers", true);
$function_id             = CView::get("function_id", "ref class|CFunctions", true);
$page                    = CView::get("page", "num default|0");
$order_way               = CView::get("order_way", "enum list|ASC|DESC default|ASC", true);
$order_col               = CView::get("order_col", "str default|_ref_chir", true);
$search_all_protocole_op = CView::get("search_all_protocole_op", "bool default|0");

CView::checkin();
/** @var CProtocoleOperatoire[] $protocoles_op */
$protocoles_op = [];
$limit         = "$page,30";
$chir          = new CMediusers();
$function      = new CFunctions();
$group         = new CGroups();
$protocole_op  = new CProtocoleOperatoire();

if ($search_all_protocole_op) {
  $group               = CGroups::get();
  $protocoles_op_group = [];

  // On r�cup�re les functions de l'�tablissement
  $functions = $group->loadFunctions();
  // On r�cup�re les praticiens des functions
  $chirs = CStoredObject::massLoadBackRefs($functions, "users");
  // On r�cup�re les protocoles des functions
  $protocoles_op_funcs = CStoredObject::massLoadBackRefs($functions, "protocoles_op");
  // On r�cup�re les protocoles des praticiens
  $protocoles_op_chirs = CStoredObject::massLoadBackRefs($chirs, "protocoles_op");


  if ($protocoles_op_funcs && count($protocoles_op_funcs)) {
    foreach ($protocoles_op_funcs as $_protocole_op) {
      $_protocole_op->loadRefFunction();
    }
    CMbArray::pluckSort($protocoles_op_funcs, $order_way === 'DESC' ? SORT_DESC : SORT_ASC, "_ref_function", "text");
  }
  // On r�cup�re et trie les protocoles op�ratoires li�s aux praticiens de l'�tablissement
  if ($protocoles_op_chirs && count($protocoles_op_chirs)) {
    foreach ($protocoles_op_chirs as $_protocole_op) {
      $_protocole_op->loadRefChir();
    }
    CMbArray::pluckSort($protocoles_op_chirs, $order_way === 'DESC' ? SORT_DESC : SORT_ASC, "_ref_chir", "_user_last_name");
  }
  $protocoles_op = array_merge($protocoles_op_chirs, $protocoles_op_funcs);
}
elseif ($function_id) {
  $function->load($function_id);
  $protocoles_op = $function->loadRefsProtocolesOperatoires($limit);
}
elseif ($chir_id) {
  $chir->load($chir_id);
  $protocoles_op = $chir->loadRefsProtocolesOperatoires($limit);
}
CStoredObject::massLoadFwdRef($protocoles_op, "validation_praticien_id");
CStoredObject::massLoadFwdRef($protocoles_op, "validation_cadre_bloc_id");

CMbArray::pluckSort($protocoles_op, SORT_ASC, 'libelle');

foreach ($protocoles_op as $_protocole_op) {
  $_protocole_op->loadRefValidationPraticien();
  $_protocole_op->loadRefValidationCadreBloc();
}
$smarty = new CSmartyDP();

$smarty->assign("protocoles_op", $protocoles_op);
$smarty->assign("order_way", $order_way);
$smarty->assign("order_col", $order_col);
$smarty->assign("search_all_protocole_op", $search_all_protocole_op);

$smarty->display("inc_list_protocoles_op");
