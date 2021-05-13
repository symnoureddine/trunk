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

$page   = intval(CView::get('page'  , 'num default|0'));

CView::checkin();

$listChap1 = CCCAM::getChapters();

// Création du template
$smarty = new CSmartyDP();
$smarty->assign("listChap1"        , $listChap1);
$smarty->assign("page"             , $page);
$smarty->display("vw_find_acte.tpl");