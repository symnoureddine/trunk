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

$dsn = CView::get("dsn", "str notNull");
$host = CView::get("host", "str notNull");

CView::checkin();

$smarty = new CSmartyDP();
$smarty->assign("dsn", $dsn);
$smarty->assign("host", $host);
$smarty->display("inc_create_db.tpl");