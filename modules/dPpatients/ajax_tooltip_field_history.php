<?php
/**
 * @package Mediboard\Patients
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;

CCanDo::checkAdmin();

$object = mbGetObjectFromGet("object_class", "object_id", "object_guid");
$field  = CView::get('field', 'str notNull');

CView::checkin();

$history = $object->getFieldHistory($field);

$smarty = new CSmartyDP();
$smarty->assign('history', $history);
$smarty->assign('object_class', $object->_class);
$smarty->assign('field_name', $field);
$smarty->display('vw_field_history.tpl');