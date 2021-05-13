<?php
/**
 * @package Mediboard\ImportTools
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Import\ImportTools\CPurgeImportedObjects;

CCanDo::checkAdmin();

$smarty = new CSmartyDP();
$smarty->assign("available_classes", CPurgeImportedObjects::$purge_classes);
$smarty->display("vw_purge_imported_objects");