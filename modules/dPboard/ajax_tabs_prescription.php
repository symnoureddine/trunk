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

CCanDo::checkRead();

// R�cup�ration des param�tres
$chirSel = CView::get("chirSel", "ref class|CMediusers", true);
$date    = CView::get("date", "date default|now", true);

CView::checkin();

// Cr�ation du template
$smarty = new CSmartyDP();

$smarty->assign("date", $date);
$smarty->assign("chirSel", $chirSel);

$smarty->display("inc_tabs_prescription");
