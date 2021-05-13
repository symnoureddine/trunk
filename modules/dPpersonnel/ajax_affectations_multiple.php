<?php
/**
 * @package Mediboard\Personnel
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Personnel\CAffectationPersonnel;

CCanDo::checkAdmin();

$object       = mbGetObjectFromGet(null, null, "object_guid");
$personnel_id = CView::get("personnel_id", "ref class|CPersonnel");

CView::checkin();

$affectation = new CAffectationPersonnel();
$affectation->setObject($object);
$affectation->personnel_id = $personnel_id;
$affectation->loadRefPersonnel();

$_multiple = array(
  "object"       => $affectation->_ref_object,
  "personnel"    => $affectation->_ref_personnel,
  "affectations" => $affectation->loadMatchingList()
);
$_multiple["affect_count"] = count($_multiple["affectations"]);

// Création du template
$smarty = new CSmartyDP();

$smarty->assign("_multiple", $_multiple);

$smarty->display("inc_affectations_multiple.tpl");
