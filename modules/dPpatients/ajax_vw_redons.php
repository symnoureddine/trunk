<?php
/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CMbDT;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Patients\CReleveRedon;
use Ox\Mediboard\PlanningOp\CSejour;

CCanDo::checkEdit();

$sejour_id = CView::get("sejour_id", "ref class|CSejour");

CView::checkin();

$sejour = new CSejour();
$sejour->load($sejour_id);

$sejour->loadRefRedons();
$releves = [];

foreach ($sejour->_ref_redons as $_redon) {
    $_redon->loadRefLastReleve();
    $_redon->getQteCumul();

    $releve = new CReleveRedon();
    $releve->redon_id = $_redon->_id;
    $releve->date = CMbDT::dateTime();

    $releves[$_redon->_id] = $releve;
}

// Création du template
$smarty = new CSmartyDP();

$smarty->assign("sejour", $sejour);
$smarty->assign("releves", $releves);

$smarty->display("inc_vw_redons");
