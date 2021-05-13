<?php
/**
 * @package Mediboard\Hospi
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\PlanningOp\CSejour;

CCanDo::checkRead();
/* @var CSejour $sejour */
$sejour = mbGetObjectFromGet('object_class', 'object_id', 'object_guid');
CView::checkin();

$sejour->loadRefsMacrocible();

$smarty = new CSmartyDP();

$smarty->assign("sejour", $sejour);
$smarty->assign("readonly", true);
$smarty->assign("show_compact_trans", true);
$smarty->assign("list_transmissions", $sejour->_ref_macrocibles);

$smarty->display("inc_list_transmissions.tpl");
