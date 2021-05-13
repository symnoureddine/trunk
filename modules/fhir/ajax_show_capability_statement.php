<?php
/**
 * @package Mediboard\Fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CMbException;
use Ox\Core\CMbObject;
use Ox\Core\CSmartyDP;
use Ox\Core\CValue;
use Ox\Core\CView;
use Ox\Interop\Fhir\CReceiverFHIR;
use Ox\Interop\Fhir\Interactions\CFHIRInteractionCapabilities;
use Ox\Mediboard\Patients\CPatient;

CCanDo::checkAdmin();

$cn_receiver_guid = CValue::sessionAbs("cn_receiver_guid");
CView::checkin();

if (!$cn_receiver_guid) {
  CAppUI::stepAjax("CInteropReceiver.none", UI_MSG_ERROR);
}

$request = new CFHIRInteractionCapabilities();
$request->profil = "CFHIR";

/** @var CReceiverFHIR $receiver_fhir */
$receiver_fhir = CMbObject::loadFromGuid($cn_receiver_guid);
try {
  $response = $receiver_fhir->sendEvent($request, new CPatient());
}
catch (CMbException $e) {
  $e->stepAjax();
  return;
}

$smarty = new CSmartyDP();
$smarty->assign("query"           , $request->buildQuery());
$smarty->assign("lang"            , "xml");
$smarty->assign("response_code"   , $receiver_fhir->_source->_response_http_code);
$smarty->assign("response_message", $receiver_fhir->_source->_response_http_message);
$smarty->assign("response_headers", $receiver_fhir->_source->_response_http_headers);
$smarty->assign("response"        , $response);
$smarty->display("inc_vw_crud_operation_result.tpl");