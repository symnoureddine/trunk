<?php
/**
 * @package Mediboard\System
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 * @deprecated Views calling this script have been removed, due to the lack of usage
 */

use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;

CCanDo::checkAdmin();

$ips_list = CView::get('ips_list', 'str');

CView::checkin();


$get["m"]    = "system";
$get["a"]    = "httpreq_check_shared_memory";
$get["ajax"] = "1";

if (empty($ips_list)) {
  $ips_list = trim(CAppUI::conf("servers_ip"));
}

$result_send = CApp::multipleServerCall($ips_list, $get, null);

$smarty = new CSmartyDP();
$smarty->assign("result_send", $result_send);
$smarty->display("inc_do_empty_shared_all_servers.tpl");