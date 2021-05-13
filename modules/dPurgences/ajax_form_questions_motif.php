<?php
/**
 * @package Mediboard\Urgences
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Urgences\CRPU;

CCanDo::checkRead();
$rpu_id          = CView::get("rpu_id", "ref class|CRPU", true);
$just_validation = CView::get("just_validation", "bool default|0");
CView::checkin();

$rpu = new CRPU();
if ($rpu_id && !$rpu->load($rpu_id)) {
  global $m, $tab;
  CAppUI::setMsg("Ce RPU n'est pas ou plus disponible", UI_MSG_WARNING);
  CAppUI::redirect("m=$m&tab=$tab&rpu_id=0");
}

$rpu->loadRefsReponses();
$rpu->loadCanValideRPU();

// Création du template
$smarty = new CSmartyDP();
$smarty->assign("rpu", $rpu);
$smarty->assign("just_validation", $just_validation);

$smarty->display("inc_form_questions_motif");
