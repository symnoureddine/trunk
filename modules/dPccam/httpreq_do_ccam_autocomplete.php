<?php
/**
 * @package Mediboard\Ccam
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CCanDo;
use Ox\Core\CMbDT;
use Ox\Core\CSmartyDP;
use Ox\Core\CView;
use Ox\Mediboard\Ccam\CDatedCodeCCAM;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Patients\CPatient;

/**
 * dPccam
 */
CCanDo::checkRead();

$input_field  = CView::request("input_field", "str default|_codes_ccam");
$keywords     = CView::request($input_field, "str default|%%");
/* Can be a date or a datetime */
$date         = CView::request("date", 'str default|' . CMbDT::date());
$user_id      = CView::request('user_id', 'ref class|CMediusers');
$patient_id   = CView::request('patient_id', 'ref class|CPatient');

CView::checkin();
CView::enableSlave();

$user = null;
if ($user_id) {
  $user = CMediusers::loadFromGuid("CMediusers-{$user_id}");
}
$patient = null;
if ($patient_id) {
  $patient = CPatient::loadFromGuid("CPatient-{$patient_id}");
}

$codes = array();
$code = new CDatedCodeCCAM(null, CMbDT::date($date));
foreach ($code->findCodes($keywords, $keywords) as $_code) {
  $_code_value = $_code["CODE"];
  $code = CDatedCodeCCAM::get($_code_value, $date);
  if ($code->code != "-") {
    $codes[$_code_value] = $code;
  }
  $code->getPrice($user, $patient, $date);
}

// Création du template
$smarty = new CSmartyDP();
$smarty->debugging = false;

$smarty->assign("keywords", $keywords);
$smarty->assign("codes"   , $codes);
$smarty->assign("nodebug" , true);

$smarty->display("httpreq_do_ccam_autocomplete.tpl");
