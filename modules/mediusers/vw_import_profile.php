<?php
/**
 * @package Mediboard\Mediusers
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Mediboard\Etablissement\CGroups;

CCanDo::checkAdmin();

$etabs = CGroups::loadGroups(PERM_READ);

$smarty = new CSmartyDP();
$smarty->assign('etabs', $etabs);
$smarty->display('vw_import_profile.tpl');