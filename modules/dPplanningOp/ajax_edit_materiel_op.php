<?php
/**
 * @package Mediboard\PlanningOp
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\PlanningOp\CMaterielOperatoire;

CCanDo::checkEdit();

$materiel_operatoire_id  = CView::get("materiel_operatoire_id", "ref class|CMaterielOperatoire");
$protocole_operatoire_id = CView::get("protocole_operatoire_id", "ref class|CProtocoleOperatoire");

CView::checkin();

$materiel_op = new CMaterielOperatoire();

if (!$materiel_op->load($materiel_operatoire_id)) {
  $materiel_op->protocole_operatoire_id = $protocole_operatoire_id;
}

$materiel_op->loadRelatedProduct();

// Création du template
$smarty = new CSmartyDP();

$smarty->assign("materiel_op", $materiel_op);

$smarty->display("inc_edit_materiel_op");