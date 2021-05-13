<?php
/**
 * @package Mediboard\Astreintes
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Astreintes\CCategorieAstreinte;
use Ox\Mediboard\Etablissement\CGroups;

CCanDo::read();

$category_id = CView::get("category_id", "ref class|CCategorieAstreinte");

CView::checkin();

$categorie_astreinte = new CCategorieAstreinte();

$category = new CCategorieAstreinte();
if ($category_id > 0) {
  $category = $categorie_astreinte->load($category_id);
}

$smarty = new CSmartyDP();

$smarty->assign("category", $category);
$smarty->assign("groups", CGroups::loadGroups());

$smarty->display("inc_edit_category");
