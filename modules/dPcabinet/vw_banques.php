<?php
/**
 * @package Mediboard\Cabinet
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Cabinet\CBanque;

CCanDo::check();

$banque_id = CView::get("banque_id", "ref class|CBanque");
$edit_mode = CView::get("edit_mode", "bool default|0");

CView::checkin();

// Chargement de la banque selectionnée
$banque = new CBanque();
$banque->load($banque_id);

$smarty = new CSmartyDP();
$smarty->assign("banque"  , $banque);

if (!$edit_mode) {
  $smarty->assign("banques" , CBanque::loadAllBanques());
  $smarty->display("vw_banques");
}
else {
  $smarty->display("inc_banques_edit");
}