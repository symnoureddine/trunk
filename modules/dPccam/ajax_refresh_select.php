<?php
/**
 * @package Mediboard\Ccam
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Ccam\CCCAM;

CCanDo::checkRead();

$value_selected = CView::get("value_selected", 'str');
$codePere       = CView::get("codePere", 'str');

CView::checkin();

// On récupère les chapitres du niveau concerné
$list = CCCAM::getChapters($codePere);

$smarty = new CSmartyDP();
$smarty->assign("value_selected" , $value_selected);
$smarty->assign("list"           , $list);
$smarty->display("inc_select_codes.tpl");