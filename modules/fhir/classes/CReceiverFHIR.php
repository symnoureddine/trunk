<?php
/**
 * @package Mediboard\Fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir;

use Exception;
use Ox\Core\CMbArray;
use Ox\Core\CMbDT;
use Ox\Core\CMbException;
use Ox\Core\CMbObject;
use Ox\Interop\Eai\CInteropReceiver;
use Ox\Interop\Fhir\Interactions\CFHIRInteraction;
use Ox\Interop\Fhir\Interactions\CFHIRInteractionCapabilities;
use Ox\Interop\Ftp\CSourceFTP;
use Ox\Interop\Ftp\CSourceSFTP;
use Ox\Interop\Hl7\CSourceMLLP;
use Ox\Interop\Webservices\CSourceSOAP;
use Ox\Mediboard\System\CExchangeSource;
use Ox\Mediboard\System\CSourceFileSystem;
use Ox\Mediboard\System\CSourceHTTP;

/**
 * Class CReceiverFHIR
 * Receiver FHIR
 */
class CReceiverFHIR extends CInteropReceiver
{
    /** @var array Sources supportées par un destinataire */
    public static $supported_sources = [
        CSourceHTTP::TYPE,
    ];

    public $receiver_fhir_id;

    /** @var string */
    public $_tag_fhir;

    /** @var CSourceHTTP */
    public $_source;

    /**
     * @inheritdoc
     */
    function getSpec()
    {
        $spec           = parent::getSpec();
        $spec->table    = 'receiver_fhir';
        $spec->key      = 'receiver_fhir_id';
        $spec->messages = [
            "FHIR" => ["CFHIR"],
            "PDQm" => ["CPDQm"],
            "PIXm" => ["CPIXm"],
            "MHD"  => ["CMHD"],
        ];

        return $spec;
    }

    /**
     * @inheritdoc
     */
    function getProps()
    {
        $props = parent::getProps();

        $props["group_id"]  .= " back|destinataires_fhir";
        $props["_tag_fhir"] = "str";

        return $props;
    }

    /**
     * @inheritdoc
     */
    function updateFormFields()
    {
        parent::updateFormFields();

        $this->_tag_fhir = CFHIR::getObjectTag($this->group_id);

        if (!$this->_configs) {
            $this->loadConfigValues();
        }
    }

    /**
     * @inheritdoc
     */
    function sendEvent(
        $request,
        $object,
        $data = [],
        $headers = [],
        $message_return = false,
        $soapVar = false,
        $method = "GET"
    ) {
        if (!parent::sendEvent($request, $object, $data, $headers, $message_return, $soapVar)) {
            return null;
        }

        /** @var CFHIRInteraction $request */
        $request->_receiver = $this;

        // Request build and generate exchange
        if ($method === "GET") {
            $data = $request->buildQuery();
        } else {
            $data = $request->buildQuery($data);
        }

        // Génération de l'échange
        $exchange = $request->generateExchange($data);

        // Si on n'est pas en synchrone
        if (!$this->synchronous) {
            return null;
        }

        /** @var CSourceHTTP $source */
        $source        = !$this->_source || !$this->_source->_id ? CExchangeSource::get(
            "{$this->_guid}-{$request->profil}"
        ) : $this->_source;
        $this->_source = $source;

        if (!$source->_id || !$source->active) {
            throw new CMbException("CExchangeSource-not_available_for_%s", $this->nom);
        }

        $source->_method = $method;
        if ($method === "POST" || $method === "PUT") {
            $source->_multipart = false;
            $source->_mimetype  = $request->format;
        }

        $source->_request_http_headers = "Accept: " . ($request->format);

        // Evite d'avoir un retour du serveur HTTP/1.1 100 Continue
        // TODO : Tout depend du serveur en face
        $source->_http_header_expect = "Expect: ";
        //$source->_http_header_expect = "Expect: application/fhir+xml";

        // Ajout du token dans le header pour se connecter via l'API
        $source->_OXAPI_KEY = $source->token;

        $exchange->send_datetime = CMbDT::dateTime();

      //  dd($data);
        $source->setData($data);
        try {
            $query = $request instanceof CFHIRInteractionCapabilities ? "" : "?";
            $source->send(CMbArray::get($data, "event"), CMbArray::get($data, "data"), null, $query);
        } catch (Exception $e) {
            throw new CMbException("CExchangeSource-no-response %s", $this->nom);
        }

        $exchange->response_datetime = CMbDT::dateTime();

        $ack_data = $source->getACQ();
        if (!$ack_data) {
            $exchange->store();

            return null;
        }

        $exchange->statut_acquittement = "ok";
        $exchange->acquittement_valide = 1;
        $exchange->_acquittement       = $ack_data;
        $exchange->store();

        return $ack_data;
    }
}
