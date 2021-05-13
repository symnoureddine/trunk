<?php
/**
 * @package Mediboard\Urgences
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CValue;
use Ox\Mediboard\Urgences\CRPU;

CCanDo::checkRead();
$rpu_id = CValue::getOrSession("rpu_id");

$rpu = new CRPU;
$rpu->load($rpu_id);
$rpu->loadRefEchelleTri();
$rpu->updateFormFields();
$rpu->_ref_sejour->loadRefGrossesse();

// Création du template
$smarty = new CSmartyDP();
$smarty->assign("rpu", $rpu);
$smarty->assign("sejour", $rpu->_ref_sejour);
$smarty->assign("patient", $rpu->_ref_sejour->_ref_patient);

$smarty->display("vw_echelle_tri");