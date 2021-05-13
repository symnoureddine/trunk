<?php 
/**
 * @package Mediboard\OpenData
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;

CCanDo::checkRead();

$audit = CView::get('audit', 'bool');

CView::checkin();

$smarty = new CSmartyDP();
$smarty->assign('audit', $audit);
$smarty->display('inc_handle_conflict.tpl');
