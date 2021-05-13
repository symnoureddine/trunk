<?php
/**
 * @package Mediboard\Xds
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CValue;
use Ox\Interop\Xds\CXDSRequest;
use Ox\Mediboard\Patients\CPatient;

$patient_id = CValue::get("patient_id");
$oid        = CValue::get("oid");
$repository = CValue::get("repository_id");

$patient = new CPatient();
$patient->load($patient_id);
if (!$patient->_id) {
  CAppUI::stepAjax("Le patient n'a pas été retrouvé", UI_MSG_ERROR);
}

$receiver_hl7v3 = CXDSRequest::getDocumentRegistry();
if (!$receiver_hl7v3 || ($receiver_hl7v3 && !$receiver_hl7v3->_id)) {
  CAppUI::stepAjax("Aucun destinataire configuré", UI_MSG_ERROR);
}

$request = CXDSRequest::sendEventRetrieveDocumentSetRequest($receiver_hl7v3, $patient, $repository, $oid);
mbTrace($request);
/*
$smarty = new CSmartyDP();
$smarty->assign("patient_id", $patient->_id);
$smarty->assign("data"      , $request);
$smarty->assign("repository", $repository);
$smarty->assign("oid"       , $oid);
$smarty->display("inc_send_data_retrieve_document.tpl");*/