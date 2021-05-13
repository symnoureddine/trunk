<?php
/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CSmartyDP;
use Ox\Core\CSQLDataSource;
use Ox\Core\CValue;
use Ox\Mediboard\Mediusers\CFunctions;
use Ox\Mediboard\Patients\CTypeEvenementPatient;

$inner_content = CValue::get("inner_content", 0);

$function    = new CFunctions();
$functions   = $function->loadListWithPerms(PERM_EDIT);
$inFunctions = CSQLDataSource::prepareIn(array_keys($functions));

$type  = new CTypeEvenementPatient();
/** @var CTypeEvenementPatient[] $types */
$types = $type->loadListWithPerms(PERM_EDIT);
foreach ($types as $_type) {
  $_type->loadRefFunction();
}

$smarty = new CSmartyDP();

$smarty->assign("inner_content", $inner_content);
$smarty->assign("types", $types);

$smarty->display("inc_vw_types_evenement_patient");
