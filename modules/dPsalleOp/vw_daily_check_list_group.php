<?php
/**
 * @package Mediboard\SalleOp
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\SalleOp\CDailyCheckListGroup;

CCanDo::checkAdmin();
$check_list_group_id = CView::get('check_list_group_id', 'ref class|CDailyCheckListGroup');
$duplicate           = CView::get('duplicate', 'bool default|0');

CView::checkin();

$check_list_group = new CDailyCheckListGroup();
if ($check_list_group_id) {
  $check_list_group->load($check_list_group_id);
  foreach ($check_list_group->loadRefChecklist() as $list_type) {
    $list_type->loadRefsCategories();
  }
}
$check_list_groups = $check_list_group->loadGroupList(null, 'actif DESC, title');

// Création du template
$smarty = new CSmartyDP();

$smarty->assign("check_list_groups" , $check_list_groups);
$smarty->assign("check_list_group"  , $check_list_group);
$smarty->assign("duplicate"         , $duplicate);

if ($check_list_group_id !== null) {
  $smarty->display("inc_edit_check_list_group.tpl");
}
else {
  $smarty->display("vw_daily_check_list_group.tpl");
}

