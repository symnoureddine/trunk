<?php

/**
 * @package Mediboard\Fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

use Ox\Core\CAppUI;
use Ox\Core\CCanDo;
use Ox\Core\CClassMap;
use Ox\Core\CMbArray;
use Ox\Core\CMbException;
use Ox\Core\CMbObject;
use Ox\Core\CMbXMLDocument;
use Ox\Core\CSmartyDP;
use Ox\Core\CValue;
use Ox\Core\CView;
use Ox\Interop\Fhir\CFHIR;
use Ox\Interop\Fhir\CFHIRXPath;
use Ox\Interop\Fhir\CReceiverFHIR;
use Ox\Interop\Fhir\Exception\CFHIRExceptionNotFound;
use Ox\Interop\Fhir\Interactions\CFHIRInteractionCreate;
use Ox\Interop\Fhir\Interactions\CFHIRInteractionDelete;
use Ox\Interop\Fhir\Interactions\CFHIRInteractionHistory;
use Ox\Interop\Fhir\Interactions\CFHIRInteractionRead;
use Ox\Interop\Fhir\Interactions\CFHIRInteractionSearch;
use Ox\Interop\Fhir\Resources\CFHIRResource;
use Ox\Mediboard\Cabinet\CConsultation;
use Ox\Mediboard\Cabinet\CPlageconsult;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Sante400\CIdSante400;

CCanDo::checkAdmin();

$cn_receiver_guid = CValue::sessionAbs("cn_receiver_guid");

$resource_type = CView::get("resource_type", "str");
$crud          = CView::get("crud", "str");
$resource_id   = CView::get("resource_id", "str");
$version_id    = CView::get("version_id", "num");
$contents      = CView::get("contents", "str");
$lang          = CView::request("response_type", "enum list|xml|json");
$format        = "application/fhir+" . ($lang ?: 'xml');
CView::checkin();

if (!$cn_receiver_guid) {
    CAppUI::stepAjax("CInteropReceiver.none", UI_MSG_ERROR);
}

/** @var CReceiverFHIR $receiver_fhir */
$receiver_fhir = CMbObject::loadFromGuid($cn_receiver_guid);

$request_method = 'GET';

$resource = CMbArray::get(CFHIR::getResources(), $resource_type);
$data     = null;
switch ($crud) {
    case "read":
        $request            = new CFHIRInteractionRead($resource_type, $format);
        $request->_receiver = $receiver_fhir;
        $request->addParameter("_id", $resource_id);
        break;
    case "search":
        $request            = new CFHIRInteractionSearch($resource_type, $format);
        $request->_receiver = $receiver_fhir;
        break;
    case "history":
        if ($version_id) {
            $request            = new CFHIRInteractionRead($resource_type, $format);
            $request->_receiver = $receiver_fhir;
            $request->addParameter("_id", $resource_id);
            $request->addParameter("version_id", $version_id);
            break;
        }
        $request            = new CFHIRInteractionHistory($resource_type, $format);
        $request->_receiver = $receiver_fhir;
        $request->addParameter("_id", $resource_id);
        break;
    case "create":
        if (!$resource || !in_array('create', $resource)) {
            CAppUI::stepAjax("CFHIRInteraction-action-Not implemented for this resource", UI_MSG_ERROR);
        }

        if (!$resource_id) {
            CAppUI::stepAjax("CFHIRInteraction-msg-Not resource id", UI_MSG_ERROR);
        }

        $object = new CMbObject();
        switch ($resource_type) {
            case 'Appointment':
                $object = new CConsultation();
                break;

            case 'Practitioner':
                $object = new CMediusers();
                break;

            case 'Patient':
                $object = new CPatient();
                break;

            case 'Schedule':
            case 'Slot':
                $object = new CPlageconsult();
                break;

            default:
        }

        // todo find solution
        $object->load($resource_id);
        if (!$object || !$object->_id) {
            CAppUI::stepAjax('CFHIRInteraction-msg-Impossible to get object', UI_MSG_ERROR);
        }

        $request            = new CFHIRInteractionCreate($resource_type, $format);
        $request->_receiver = $receiver_fhir;
        $resourceRequest    = $request->build($object);
        $data               = $resourceRequest->output($format);

        $request_method = "POST";

        break;

    case "delete":
        if (!$resource || !in_array('delete', $resource)) {
            CAppUI::stepAjax("CFHIRInteraction-action-Not implemented for this resource", UI_MSG_ERROR);
        }

        if (!$resource_id) {
            CAppUI::stepAjax('CFHIRInteraction-msg-Not resource id', UI_MSG_ERROR);
        }

        $object = new CMbObject();
        switch ($resource_type) {
            case 'Practitioner':
                $object_class = CClassMap::getSN('CMediusers');
                break;

            case 'Patient':
                $object_class = CClassMap::getSN('CPatient');
                break;

            default:
        }

        $idex = CIdSante400::getMatch($object_class, $receiver_fhir->_tag_fhir, $resource_id);
        if (!$idex || !$idex->_id) {
            CAppUI::stepAjax('CFHIRInteraction-msg-Impossible to get object', UI_MSG_ERROR);
        }
        $object = new $object_class();
        $object->load($idex->object_id);

        $request            = new CFHIRInteractionDelete($resource_type, $format);
        $request->_receiver = $receiver_fhir;
        $request->addParameter("_id", $resource_id);

        $request_method = 'DELETE';

        break;
    default:
        CAppUI::stepAjax('CFHIRInteraction-action-Not implemented', UI_MSG_ERROR);
}

$request->profil = 'CFHIR';

try {
    $response = $receiver_fhir->sendEvent($request, new CPatient(), [$data], [], false, false, $request_method);
} catch (CMbException $e) {
    $e->stepAjax();

    return;
}

if ($crud == 'read') {
    // Faire un mapFrom

} elseif ($crud == 'create') {
    $source  = $receiver_fhir->_source;
    $pattern = '/\/#resource_type\/(?\'resource_location\'([a-z0-9\-\.]{1,64}))/';
    $pattern = str_replace('#resource_type', $resource_type, $pattern);
    if (!preg_match($pattern, $source->_location_resource, $matches)) {
        CAppUI::stepAjax('HEADER location not found', UI_MSG_ERROR);
    }

    // Création de l'idex
    if (($resource_id = CMbArray::get($matches, 'resource_location')) && $object) {
        $idex        = CIdSante400::getMatchFor($object, $receiver_fhir->_tag_fhir);
        $idex->id400 = $resource_id;
        $idex->store();
    }
}

$smarty = new CSmartyDP();
$smarty->assign("query", $request->buildQuery());
$smarty->assign("lang", $lang);
$smarty->assign("response_code", $receiver_fhir->_source->_response_http_code);
$smarty->assign("response_message", $receiver_fhir->_source->_response_http_message);
$smarty->assign("response_headers", $receiver_fhir->_source->_response_http_headers);
$smarty->assign("response", $response);
$smarty->display("inc_vw_crud_operation_result.tpl");
