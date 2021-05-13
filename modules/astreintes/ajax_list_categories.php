<?php
/**
 * @package Mediboard\Astreintes
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Mediboard\Astreintes\CCategorieAstreinte;

CCanDo::admin();

$oncall_category = new CCategorieAstreinte();
$categories      = $oncall_category->loadGroupList() + $oncall_category->loadList('group_id is null');

$smarty = new CSmartyDP();

$smarty->assign("categories", $categories);

$smarty->display("inc_list_categories");
