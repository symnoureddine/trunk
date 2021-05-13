<?php
/**
 * @package Mediboard\Board
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\System\CSourcePOP;

/**
 * dPboard
 */
CCanDo::checkRead();

// Récupération des paramètres
$chirSel = CView::get("chirSel", "ref class|CMediusers", true);
$date    = CView::get("date", "date default|now", true);

CView::checkin();

// Création du template
$smarty = new CSmartyDP();

$account               = new CSourcePOP();
$account->object_class = "CMediusers";
$account->object_id    = $chirSel;
$account->loadMatchingObject();

$smarty->assign("account", $account);
$smarty->assign("date", $date);
$smarty->assign("chirSel", $chirSel);

$smarty->display("inc_worklist");
