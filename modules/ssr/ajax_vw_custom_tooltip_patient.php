<?php
/**
 * @package Mediboard\Ssr
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CMbDT;
use Ox\Core\CSmartyDP;
use Ox\Core\CStoredObject;
use Ox\Core\CView;
use Ox\Mediboard\Ssr\CPlageGroupePatient;

global $m;

CCanDo::check();
/** @var CPlageGroupePatient $object */
$object    = mbGetObjectFromGet("object_class", "object_id", "object_guid");
$date      = CView::get("date", "date default|now", true);
$sejour_id = CView::get("sejour_id", "str", true);
CView::checkin();

$object->_date = CMbDT::date("$object->groupe_day this week", $date);
$object->loadView();

$where              = array();
$where["debut"]     = "BETWEEN '$object->_date 00:00:00' AND '$object->_date 23:59:59'";
$where["sejour_id"] = "= '$sejour_id'";

$evenements = $object->loadRefEvenementsSSR($where);

CStoredObject::massLoadFwdRef($evenements, "prescription_line_element_id");
CStoredObject::massLoadBackRefs($evenements, "actes_csarr");

foreach ($evenements as $_evenement) {
  $_evenement->loadRefPrescriptionLineElement();
  $_evenement->loadRefsActesCsARR();
}

$smarty = new CSmartyDP();
$smarty->assign("other_view", true);
$smarty->assign("object"    , $object);
$smarty->display("CPlageGroupePatient_view");
