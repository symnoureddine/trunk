<?php
/**
 * @package Mediboard\Maternite
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CMbArray;
use Ox\Core\CMbDT;
use Ox\Core\CSmartyDP;
use Ox\Core\CSQLDataSource;
use Ox\Core\CStoredObject;
use Ox\Core\CValue;
use Ox\Mediboard\Cabinet\CConsultation;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Mediusers\CMediusers;

/**
 * Liste des consultations du jour du tableau de bord
 */
CCanDo::checkRead();

$date = CValue::get("date", CMbDT::date());

$group = CGroups::loadCurrent();

$consultation = new CConsultation();

$where                                 = array();
$where["consultation.grossesse_id"]    = "IS NOT NULL";
$where["consultation.annule"]          = "= '0'";
$where["plageconsult.date"]            = "= '$date'";
$where["functions_mediboard.group_id"] = "= '$group->_id'";

$ljoin                        = array();
$ljoin["plageconsult"]        = "plageconsult.plageconsult_id = consultation.plageconsult_id";
$ljoin["users_mediboard"]     = "plageconsult.chir_id = users_mediboard.user_id";
$ljoin["functions_mediboard"] = "functions_mediboard.function_id = users_mediboard.function_id";

$curr_user = CMediusers::get();
if ($curr_user->isSageFemme()) {
  $where["plageconsult.chir_id"] = CSQLDataSource::prepareIn(CMbArray::pluck($curr_user->loadListFromType(array("Sage Femme")), "_id"));
}

/** @var CConsultation[] $listConsults */
$listConsults = $consultation->loadList($where, "heure ASC", null, null, $ljoin);

$plages = CStoredObject::massLoadFwdRef($listConsults, "plageconsult_id");
CStoredObject::massLoadFwdRef($plages, "chir_id");
CStoredObject::massLoadFwdRef($listConsults, "sejour_id");
$grossesses = CStoredObject::massLoadFwdRef($listConsults, "grossesse_id");
$patientes  = CStoredObject::massLoadFwdRef($grossesses, "parturiente_id");
CStoredObject::massLoadBackRefs($patientes, "bmr_bhre");

foreach ($listConsults as $_consult) {
  $_consult->loadRefPraticien();
  $_consult->loadRefSejour()->loadRefGrossesse();
  $_consult->loadRefGrossesse()->loadRefParturiente()->updateBMRBHReStatus();
}

$smarty = new CSmartyDP();

$smarty->assign("date", $date);
$smarty->assign("listConsults", $listConsults);

$smarty->display("inc_tdb_consultations.tpl");