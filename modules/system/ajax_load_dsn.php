<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;

CCanDo::checkAdmin();

$dsn     = CView::get("dsn", "str notNull");
$dsn_uid = CView::get("dsn_uid", "str");

CView::checkin();

$smarty = new CSmartyDP();
$smarty->assign("dsn", $dsn);
$smarty->assign("dsn_uid", $dsn_uid);
$smarty->display("inc_view_dsn.tpl");