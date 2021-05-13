<?php 
/**
 * @package Mediboard\Ccam
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CMbDT;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Mediusers\CSpecCPAM;

CCanDo::checkRead();

CView::checkin();

$specialities = CSpecCPAM::getList();

$smarty = new CSmartyDP();
$smarty->assign('specs', $specialities);
$smarty->assign('spec', reset($specialities));
$smarty->assign('date', CMbDT::date());
$smarty->display('vw_ngap.tpl');