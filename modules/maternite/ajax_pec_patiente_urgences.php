<?php
/**
 * @package Mediboard\Maternite
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CMbDT;
use Ox\Core\CSmartyDP;
use Ox\Core\CSQLDataSource;
use Ox\Core\CStoredObject;
use Ox\Mediboard\PlanningOp\CSejour;

CCanDo::checkEdit();

// Liste des patientes qui sont aux urgences et qui ont une grossesse en cours
$sejour = new CSejour();

$where = [
  "sejour.type"   => CSQLDataSource::prepareIn(CSejour::getTypesSejoursUrgence()),
  "sejour.sortie" => "> '" . CMbDT::dateTime() . "'",
  "sejour.annule" => "= '0'",
  "patients.sexe" => "= 'f'"
];

$ljoin = [
  "patients" => "patients.patient_id = sejour.patient_id"
];

$sejours = $sejour->loadList($where, null, null, null, $ljoin);

CStoredObject::massLoadFwdRef($sejours, "patient_id");

foreach ($sejours as $_sejour) {
  if (!$_sejour->loadRefPatient()->loadLastGrossesse()->_id) {
    unset($sejours[$_sejour->_id]);
  }
}

// Création du template
$smarty = new CSmartyDP();

$smarty->assign("sejours", $sejours);

$smarty->display("inc_pec_patiente_urgences");
