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

// Check params
$source_guid = CView::get("source_guid", "str");
CView::checkin();

// Création du template
$smarty = new CSmartyDP();
$smarty->assign("source_guid", $source_guid);
$smarty->display("inc_manage_files.tpl");