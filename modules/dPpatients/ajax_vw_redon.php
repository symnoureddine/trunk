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
use Ox\Mediboard\Patients\CRedon;
use Ox\Mediboard\Patients\CReleveRedon;

CCanDo::checkEdit();

$redon_id = CView::get("redon_id", "ref class|CRedon");

CView::checkin();

$redon = new CRedon();
$redon->load($redon_id);

$redon->loadRefLastReleve();
$redon->getQteCumul();

$releve = new CReleveRedon();
$releve->redon_id = $redon_id;
$releve->date = CMbDT::dateTime();

// Création du template
$smarty = new CSmartyDP();

$smarty->assign("redon", $redon);
$smarty->assign("releve", $releve);

$smarty->display("inc_vw_redon");
