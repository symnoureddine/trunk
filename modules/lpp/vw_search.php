<?php
/**
 * @package Mediboard\Lpp
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Mediboard\Lpp\CLPPChapter;

CCanDo::checkRead();

$chapters = CLPPChapter::loadfromParent('0');

$smarty = new CSmartyDP();
$smarty->assign('chapters', $chapters);
$smarty->assign('codes', array());
$smarty->assign('start', 0);
$smarty->assign('total', 0);
$smarty->display('vw_search');