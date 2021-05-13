<?php
/**
 * @package Mediboard\
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir\Controllers;

use DOMDocument;
use Exception;
use Ox\AppFine\Server\CAppFineServer;
use Ox\Core\Api\Request\CRequestApi;
use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CController;
use Ox\Core\Chronometer;
use Ox\Core\CMbArray;
use Ox\Core\CMbDT;
use Ox\Core\Kernel\Routing\CRouteManager;
use Ox\Core\Kernel\Routing\CRouter;
use Ox\Interop\Connectathon\CBlink1;
use Ox\Interop\Fhir\CFHIR;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeBackbone;
use Ox\Interop\Fhir\Exception\CFHIRException;
use Ox\Interop\Fhir\Exception\CFHIRExceptionNotFound;
use Ox\Interop\Fhir\Interactions\CFHIRInteractionCapabilities;
use Ox\Interop\Fhir\Interactions\CFHIRInteractionCreate;
use Ox\Interop\Fhir\Interactions\CFHIRInteractionDelete;
use Ox\Interop\Fhir\Interactions\CFHIRInteractionHistory;
use Ox\Interop\Fhir\Interactions\CFHIRInteractionRead;
use Ox\Interop\Fhir\Interactions\CFHIRInteractionSearch;
use Ox\Interop\Fhir\Interactions\CFHIRInteractionUpdate;
use Ox\Interop\Fhir\Resources\CFHIRResourceDocumentReference;
use Ox\Interop\Fhir\Resources\CFHIRResourceOperationOutcome;
use Ox\Interop\Fhir\Subscriber\CFHIRListener;
use Ox\Mediboard\Admin\CUser;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Files\CDocumentReference;
use Ox\Mediboard\Files\CFile;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Sante400\CIdSante400;
use Ox\Mediboard\System\CExchangeHTTP;
use Ox\Mediboard\System\CSenderHTTP;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Description
 */
class CFHIRController extends CController
{
    /** @var CSenderHTTP */
    public static $sender_http;

    /** @var CExchangeHTTP */
    public static $exchange_http;

    /** @var Chronometer */
    public static $chrono;

    /**
     * @throws \Exception
     */
    public static function initBlink1()
    {
        $blink = new CBlink1();
        $blink->addPattern(CFHIR::BLINK1_UNKNOW, "3,#0489B1,0.5,#000000,0.5");
        $blink->addPattern(CFHIR::BLINK1_ERROR, "3,#FFBF00,0.5,#000000,0.5");
        $blink->addPattern(CFHIR::BLINK1_WARNING, "3,#8B6500,0.5,#000000,0.5");
        $blink->addPattern(CFHIR::BLINK1_OK, "3,#3ADF00,0.5,#000000,0.5");
    }

    /**
     * @param Request $request
     *
     * @throws Exception
     */
    public static function start(Request $request)
    {
        CApp::$chrono->stop();
        self::$chrono = new Chronometer();
        self::$chrono->start();

        // Création de l'échange HTTP
        $exchange_http                = new CExchangeHTTP();
        $exchange_http->date_echange  = "now";
        $exchange_http->destinataire  = CAppUI::conf("mb_id");
        $method                       = $request->getMethod();
        $exchange_http->function_name = $method;
        $input                        = ($method == "POST") ? $request->getContent() : $request->getRequestUri();
        $exchange_http->input         = serialize($input);

        // Chargement du sender HTTP
        $sender_http          = new CSenderHTTP();
        $sender_http->user_id = CUser::get()->_id;
        $sender_http->role    = CAppUI::conf("instance_role");
        $sender_http->actif   = "1";
        $sender_http->loadMatchingObject();

        $exchange_http->emetteur = $sender_http->_id ? $sender_http->nom : CAppUI::tr("Unknown");

        if (!$sender_http->_id) {
            $exchange_http->date_echange  = "now";
            $exchange_http->http_fault    = 1;
            $exchange_http->response_time = self::$chrono->total;
            $exchange_http->store();
            //throw new CFHIRException("Sender HTTP not found", Response::HTTP_UNAUTHORIZED);
        }

        /*$exchange_http->source_class = $sender_http->_class;
        $exchange_http->source_id    = $sender_http->_id;*/
        $exchange_http->emetteur = $sender_http->nom;

        self::$exchange_http = $exchange_http;
        self::$sender_http   = $sender_http;
    }

    public static function stop(Response $response)
    {
        $exchange_http = self::$exchange_http;
        if (!$exchange_http) {
            return;
        }
        $str    = 'HTTP/' . $response->getProtocolVersion() . " " . $response->getStatusCode() . "\r\n";
        $str    .= $response->headers->__toString();
        $output = $str . "\r\n" . $response->getContent();

        $exchange_http->date_echange  = CMbDT::dateTime();
        $exchange_http->output        = serialize($output);
        $exchange_http->response_time = self::$chrono->total;
        $exchange_http->store();

        CApp::$chrono->start();
    }

    /**
     * @param string      $msg
     * @param string      $code
     * @param null|string $format
     *
     * @return Response
     * @throws Exception
     */
    public static function getResponse($msg, $code, $format = null)
    {
        if (is_null($format)) {
            $format = isset($_GET['_format']) ? $_GET['_format'] : CFHIR::CONTENT_TYPE_XML;
        }

        $response = new Response();
        $response->setStatusCode($code, $msg);

        /** @var CFHIRResourceOperationOutcome $resource */
        $resource          = CFHIR::makeResource("OperationOutcome");
        $resource->issue[] = CFHIRDataTypeBackbone::build(
            [
                "severity"    => "success",
                "code"        => "$code",
                "diagnostics" => $msg,
            ]
        );

        switch ($format) {
            case CFHIR::CONTENT_TYPE_XML:
                $dom               = new DOMDocument("1.0", "UTF-8");
                $dom->formatOutput = true;
                $resource->toXML($dom);
                $xml = $dom->saveXML();
                $response->setContent($xml);
                $response->headers->set("content-type", CFHIR::CONTENT_TYPE_XML);
                break;
            case CFHIR::CONTENT_TYPE_JSON;
                $json = CMbArray::toJSON($resource, false);
                $response->setContent($json);
                $response->headers->set("content-type", CFHIR::CONTENT_TYPE_JSON);
                break;
            default:
        }

        return $response;
    }

    /**
     * @param Exception   $e
     * @param null|string $format
     * @param bool        $blink1
     *
     * @return Response
     * @throws Exception
     */
    public static function getErrorResponse(Exception $e, $format = null, $blink1 = false)
    {
        // todo remove (mathias) ?
        if ($blink1) {
            CBlink1::getInstance()->playPattern(CBlink1::FHIR_ERROR);
        }

        if (is_null($format)) {
            $format = isset($_GET['_format']) ? $_GET['_format'] : CFHIR::CONTENT_TYPE_XML;
        }

        $response = new Response();
        $response->setStatusCode($e->getCode(), $e->getMessage());

        /** @var CFHIRResourceOperationOutcome $resource */
        $resource          = CFHIR::makeResource("OperationOutcome");
        $resource->issue[] = CFHIRDataTypeBackbone::build(
            [
                "severity"    => "error",
                "code"        => "code-invalid",
                "diagnostics" => $e->getMessage(),
            ]
        );

        switch ($format) {
            // TODO : Gestion des autres cas (json, xml, application/json, application/xml, etc)
            case CFHIR::CONTENT_TYPE_XML:
                $dom               = new DOMDocument("1.0", "UTF-8");
                $dom->formatOutput = true;
                $resource->toXML($dom);

                $xml = $dom->saveXML();
                $response->setContent($xml);
                $response->headers->set("content-type", CFHIR::CONTENT_TYPE_XML);
                break;
            case CFHIR::CONTENT_TYPE_JSON;
                $json = CMbArray::toJSON($resource, false);
                $response->setContent($json);
                $response->headers->set("content-type", CFHIR::CONTENT_TYPE_JSON);
                break;
            default:
        }

        return $response;
    }

    /**
     * @warning do not use in production need ref with cached url generator
     *
     * @param string $route_name
     * @param array  $parameters
     *
     * @return string
     * @throws CFHIRException
     */
    public static function getUrl($route_name, $parameters = [])
    {
        return CRouter::getInstance()->getGenerator()->generate(
            $route_name,
            $parameters,
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * @return mixed
     */
    public function getClient()
    {
        return CAppUI::$user->_user_username;
    }

    /**
     * Get format
     *
     * @param Request $request
     *
     * @return string format
     */
    public static function getFormat(Request $request)
    {
        $format_request = $request->get("_format");
        if ($format = self::getFormatSupported($format_request)) {
            return $format;
        }

        $accept_request = CMbArray::get($request->getAcceptableContentTypes(), 0);
        if ($format = self::getFormatSupported($accept_request)) {
            return $format;
        }

        return CFHIR::CONTENT_TYPE_XML;
    }

    /**
     * Get format supported
     *
     * @param $format
     *
     * @return string format
     */
    public static function getFormatSupported($format)
    {
        switch ($format) {
            case "application/fhir+xml":
            case "application/xml+fhir":
            case "xml":
                return CFHIR::CONTENT_TYPE_XML;
            case "application/fhir+json":
            case "application/json+fhir":
            case "json":
                return CFHIR::CONTENT_TYPE_JSON;
            default:
                return null;
        }
    }

    /**
     * Search route
     *
     * @param String  $resource    Resource name
     * @param int     $resource_id Resource ID
     * @param int|null version_id   History version ID
     * @param Request $request     Request
     *
     * @return Response
     * @throws CFHIRException
     * @throws Exception
     *
     * @api
     */
    public function read($resource, $resource_id, $version_id = null, Request $request)
    {
        $format = self::getFormat($request);
        $data   = [
            "_id"        => [["=", $resource_id]],
            "version_id" => $version_id,
        ];

        try {
            $resource         = CFHIR::makeResource($resource);
            $resourceResponse = $resource->process(CFHIRInteractionRead::NAME, $data, $format);

            return $resourceResponse->output($format);
        } catch (CFHIRException $e) {
            return static::getErrorResponse($e, $format);
        }
    }

    /**
     * Search route
     *
     * @param String  $resource Resource name
     * @param Request $request  Request
     *
     * @return Response
     * @throws CFHIRException
     * @throws Exception
     *
     * @api
     */
    public function search($resource, Request $request)
    {
        $format = self::getFormat($request);
        $data   = CFHIR::getDataFromRequestMethod($request->getMethod(), $request->getContent());

        if (preg_match("#json#", $format)) {
            $format = "application/fhir+json";
        } else {
            $format = "application/fhir+xml";
        }

        try {
            $resource         = CFHIR::makeResource($resource);
            $resourceResponse = $resource->process(CFHIRInteractionSearch::NAME, $data, $format);

            return $resourceResponse->output($format);
        } catch (CFHIRException $e) {
            return static::getErrorResponse($e, $format);
        }
    }

    /**
     * Search route
     *
     * @param String  $resource Resource name
     * @param Request $request  Request
     *
     * @return Response
     * @throws Exception
     *
     * @api
     */
    public function search_appFine($resource, Request $request)
    {
        $format = self::getFormat($request);
        $data   = CFHIR::getDataFromRequestMethod($request->getMethod(), $request->getContent());

        if (preg_match("#json#", $format)) {
            $format = "application/fhir+json";
        } else {
            $format = "application/fhir+xml";
        }

        try {
            $resource = CFHIR::makeResource($resource);

            $resourceResponse = $resource->interaction_search_appFine($data, $format);

            return $resourceResponse->output($format);
        } catch (CFHIRException $e) {
            return static::getErrorResponse($e, $format);
        }
    }

    /**
     * Create patient user for AppFine
     *
     * @param String  $resource Resource name
     * @param Request $request  Request
     *
     * @return Response
     * @throws Exception
     *
     * @api
     */
    public function patient_user_appFine(Request $request)
    {
        $format = self::getFormat($request);
        $data   = CFHIR::getDataFromRequestMethod($request->getMethod(), $request->getContent());

        if (preg_match("#json#", $format)) {
            $format = "application/fhir+json";
        } else {
            $format = "application/fhir+xml";
        }

        $http_sender          = new CSenderHTTP();
        $http_sender->user_id = CAppUI::$instance->user_id;
        $http_sender->actif   = 1;
        $http_sender->role    = CAppUI::conf('instance_role');
        $http_sender->loadMatchingObjectEsc();

        if (!$http_sender || !$http_sender->_id) {
            $e = new Exception(CAppUI::tr("CSenderHTTP.none"), 500);

            return static::getErrorResponse($e, $format);
        }

        $data              = json_decode(CMbArray::get($data, 'data'), true);
        $code              = CMbArray::get($data, "code");
        $patient_id        = CMbArray::get($data, "patient_id");
        $patient_id_client = CMbArray::get($data, "patient_id_client");

        return CAppFineServer::createPatientUserAPI($http_sender, $format, $patient_id, $patient_id_client, $code);
    }

    /**
     * @param         $resource_id
     * @param Request $request
     *
     * @return string
     * @throws CFHIRExceptionNotFound
     * @api
     */
    public function read_binary($resource_id, Request $request)
    {
        $format = self::getFormat($request);
        $data   = CFHIR::getDataFromRequestMethod($request->getMethod(), $request->getContent());

        $document_reference = new CDocumentReference();
        $document_reference->load($resource_id);

        if (!$document_reference->_id) {
            throw new CFHIRExceptionNotFound("Could not find Binary" . get_class($this) . " #$resource_id");
        }

        $document_reference->loadRefObject();

        return $document_reference->_ref_object->getBinaryContent();
    }

    /**
     * Search route
     *
     * @param String   $resource    Resource name
     * @param int|null $resource_id Resource ID
     * @param Request  $request     Request
     *
     * @return Response
     * @throws CFHIRException
     * @throws Exception
     * @api
     */
    public function history($resource, $resource_id = null, Request $request)
    {
        $format = self::getFormat($request);
        $data   = [
            "_id" => [["=", $resource_id]],
        ];

        try {
            $resource         = CFHIR::makeResource($resource);
            $resourceResponse = $resource->process(CFHIRInteractionHistory::NAME, $data, $format);

            return $resourceResponse->output($format);
        } catch (CFHIRException $e) {
            return static::getErrorResponse($e, $format);
        }
    }

    /**
     * Capability statement routes
     *
     * @param Request $request
     *
     * @return Response
     * @throws CFHIRException
     * @throws Exception
     * @api
     */
    public function metadata(Request $request)
    {
        $format = self::getFormat($request);
        try {
            $resource         = CFHIR::makeResource("CapabilityStatement");
            $resourceResponse = $resource->process(CFHIRInteractionCapabilities::NAME, null, $format);

            return $resourceResponse->output($format);
        } catch (CFHIRException $e) {
            return $this->getErrorResponse($e, $format);
        }
    }

    /**
     * Create resource
     *
     * @param Request $request Request
     *
     * @return Response
     * @throws CFHIRException
     * @throws Exception
     * @api
     */
    public function create(Request $request)
    {
        $format = self::getFormat($request);
        $data   = CFHIR::getDataFromRequestMethod($request->getMethod(), $request->getContent());

        try {
            $resource         = new CFHIRResourceDocumentReference();
            $resourceResponse = $resource->process(CFHIRInteractionCreate::NAME, $data, $format);

            return $resourceResponse->output($format);
        } catch (CFHIRException $e) {
            return static::getErrorResponse($e, $format);
        }
    }

    /**
     * Update resource
     *
     * @param String   $resource    Resource name
     * @param int|null $resource_id Resource ID
     * @param Request  $request     Request
     *
     * @return Response
     * @throws CFHIRException
     * @throws Exception
     * @api
     */
    public function update($resource, $resource_id, Request $request)
    {
        $format = self::getFormat($request);
        $data   = [
            "_id"  => [["=", $resource_id]],
            "data" => $request->getContent(),
        ];

        try {
            $resource         = CFHIR::makeResource($resource);
            $resourceResponse = $resource->process(CFHIRInteractionUpdate::NAME, $data, $format);

            return $resourceResponse->output($format);
        } catch (CFHIRException $e) {
            return static::getErrorResponse($e, $format);
        }
    }

    /**
     * Update resource
     *
     * @param String   $resource    Resource name
     * @param int|null $resource_id Resource ID
     * @param Request  $request     Request
     *
     * @return Response
     * @throws CFHIRException
     * @throws Exception
     * @api
     */
    public function delete($resource, $resource_id, Request $request)
    {
        $format = self::getFormat($request);
        $data   = [
            "_id" => [["=", $resource_id]],
        ];

        try {
            $resource         = CFHIR::makeResource($resource);
            $resourceResponse = $resource->process(CFHIRInteractionDelete::NAME, $data, $format);

            return $resourceResponse->output($format);
        } catch (CFHIRException $e) {
            return static::getErrorResponse($e, $format);
        }
    }

    /**
     * Create route
     *
     * @param String  $resource Resource name
     * @param Request $request  Request
     *
     * @return Response
     * @throws CFHIRException
     * @throws Exception
     * @api
     */
    public function ihepix($resource, Request $request)
    {
        $format = self::getFormat($request);
        $data   = CFHIR::getDataFromRequestMethod($request->getMethod(), $request->getContent());

        try {
            $resource         = CFHIR::makeResource($resource);
            $resourceResponse = $resource->process("\$ihe-pix", $data, $format);

            return $resourceResponse->output($format);
        } catch (CFHIRException $e) {
            return static::getErrorResponse($e, $format);
        }
    }

    /**
     * View form in AppFine
     *
     * @param CRequestApi $request_api
     *
     * @return string
     * @throws CFHIRException
     * @api
     */
    public function preview_form_appFine(Request $request)
    {
        $format = self::getFormat($request);
        $data   = CFHIR::getDataFromRequestMethod($request->getMethod(), $request->getContent());
        $json   = json_decode(CMbArray::get($data, "data"), true);
        try {
            if (!$form_guid = CMbArray::get($json, "object_guid")) {
                throw new CFHIRException("Invalid argument 'ex_class_id'");
            }

            if (!$content_xml = CMbArray::get($json, "form_xml")) {
                throw new CFHIRException("Invalid argument 'form_xml'");
            }

            if (!$file_name = CMbArray::get($json, "file_name")) {
                throw new CFHIRException("Invalid argument 'file_name'");
            }

            $group     = CGroups::loadCurrent();
            $user      = CUser::get();
            $ext       = "xml";
            $file_type = "application/mbForm";
            $form_xml  = base64_decode($content_xml);
            $tag       = CAppFineServer::getObjectTagAppFineViewForm($group->_id);

            // Création du fichier pour le formulaire
            $file = new CFile();
            $file->setObject($user);
            $file->file_name = "$file_name.$ext";
            $file->file_type = $file_type;
            $file->file_date = CMbDT::dateTime();
            $file->fillFields();
            $file->updateFormFields();
            $file->setContent($form_xml);
            if ($msg = $file->store()) {
                throw new CFHIRException($msg);
            }

            // store de l'id sante 400 pour suppression a posteriori avec le tag
            $id_sante_400               = new CIdSante400();
            $id_sante_400->object_id    = $file->_id;
            $id_sante_400->object_class = $file->_class;
            $id_sante_400->id400        = $form_guid;
            $id_sante_400->tag          = $tag;
            if ($msg = $id_sante_400->store()) {
                $file->delete();
                throw new CFHIRException($msg);
            }

            $content_response = [
                "file_id" => $file->_id,
            ];

            return $this->renderJsonResponse(json_encode($content_response));
        } catch (CFHIRException $e) {
            return static::getErrorResponse($e, $format);
        }
    }

    /**
     * @return array|EventSubscriberInterface
     */
    public function getEventSubscribers()
    {
        return [
            new CFHIRListener(),
        ];
    }
}
