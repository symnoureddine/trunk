<?php
/**
 * @package Mediboard\Ccam
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CMbObject;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;

CCanDo::checkRead();
$object_class = CView::get('object_class', "str");
$object_id    = CView::get('object_id', "ref class|$object_class");
CView::checkin();

$object = CMbObject::loadFromGuid("$object_class-$object_id");
$object->loadRefPraticien();
$object->loadRefPatient();
$list_devis = $object->loadBackRefs('devis_codage', 'creation_date ASC', null, 'devis_codage_id');

foreach ($list_devis as $_devis) {
  $_devis->updateFormFields();
  $_devis->countActes();
}

$smarty = new CSmartyDP();
$smarty->assign('object', $object);
$smarty->assign('list_devis', $list_devis);
$smarty->display('inc_list_devis.tpl');