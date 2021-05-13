<?php
/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CMbArray;
use Ox\Core\CSmartyDP;
use Ox\Core\CSQLDataSource;
use Ox\Core\CView;
use Ox\Mediboard\CompteRendu\CCompteRendu;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Mediusers\CFunctions;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Patients\CTypeEvenementPatient;

/** @var int $type_id */
$type_id = CView::get("type_evenement_patient_id", "num");
CView::checkin();

$type = new CTypeEvenementPatient();
$type->load($type_id);

$function  = new CFunctions();
$functions = $function->loadListWithPerms(PERM_EDIT, null, "text");

$cr = new CCompteRendu();
$ds = $cr->getDS();

$functions_ids = CSQLDataSource::prepareIn(CMbArray::pluck($functions, "_id"));
$user_id       = CMediusers::get()->_id;
$group_id      = CGroups::get()->_id;

$where = [
  $ds->prepare("user_id = ?", $user_id) . " OR function_id $functions_ids OR " . $ds->prepare("group_id = ?", $group_id),
  "modele_id"    => "IS NULL",
  "object_class" => "= 'CEvenementPatient'",
];

$models = $cr->loadList($where);

$smarty = new CSmartyDP();

$smarty->assign("type", $type);
$smarty->assign("functions", $functions);
$smarty->assign("mailing_models", $models);

$smarty->display("inc_edit_types_evenement_patient.tpl");
