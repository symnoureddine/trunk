<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CSQLDataSource;
use Ox\Core\CView;
use Ox\Core\SHM;

CCanDo::checkAdmin();

$dsn = CView::get("dsn", "str notNull");

CView::checkin();

// Check params
if (!$dsn) {
  CAppUI::stepAjax("CSQLDataSource-msg-No DSN specified", UI_MSG_ERROR);
}

SHM::rem("ds_metadata-$dsn");

$ds = @CSQLDataSource::get($dsn);

$hosts = array();
if (!$ds) {
  $dbhost = CAppUI::conf("db $dsn dbhost");
  $hosts = preg_split('/\s*,\s*/', $dbhost);
}

$smarty = new CSmartyDP();
$smarty->assign("ds", $ds);
$smarty->assign("dsn", $dsn);
$smarty->assign("section", "db");
$smarty->assign("hosts", $hosts);
$smarty->display("inc_dsn_status.tpl");
