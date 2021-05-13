<?php
/**
 * @package Mediboard\Fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir\Interactions;

use Exception;
use Ox\Core\Autoload\IShortNameAutoloadable;
use Ox\Core\CMbDT;
use Ox\Core\CMbString;
use Ox\Interop\Eai\CInteropSender;
use Ox\Interop\Fhir\CExchangeFHIR;
use Ox\Interop\Fhir\CFHIR;
use Ox\Interop\Fhir\CReceiverFHIR;
use Ox\Interop\Fhir\Exception\CFHIRException;
use Ox\Interop\Fhir\Resources\CFHIRResource;
use Ox\Interop\Fhir\Response\CFHIRResponse;

/**
 * Description
 */
class CFHIRInteraction implements IShortNameAutoloadable
{
    /** @var string Name */
    public const NAME = "";

    /** @var string Profil */
    public $profil;

    /** @var string Resource type */
    public $resourceType;

    /** @var string Format */
    public $format = "application/fhir+xml";

    /** @var string Complement URL */
    public $complement_url;

    /** @var bool Add format */
    public $add_format = true;

    /** @var array Parameters */
    public $parameters = [];

    /** @var CReceiverFHIR */
    public $_receiver;
    /** @var CInteropSender */
    public $_sender;

    /** @var CExchangeFHIR */
    public $_exchange_fhir;

    /**
     * CFHIRInteraction constructor.
     *
     * @param string $resourceType Resource type to search in (Patient, etc)
     * @param string $format       Requested return type
     */
    public function __construct(?string $resourceType = null, string $format = "application/fhir+xml")
    {
        $this->resourceType = $resourceType;
        if (in_array($format, ["application/fhir+xml", "application/fhir+json",], true)) {
            $this->format = $format;
        }
    }

    /**
     * Make Interaction object
     *
     * @param string $interaction Interaction name, possibly with heading "_"
     *
     * @return self
     * @throws CFHIRException
     */
    public static function make(string $interaction): self
    {
        if (!$interaction) {
            throw new CFHIRException("Empty interaction name");
        }

        $interaction_class = preg_replace('/[^\w]/', ' ', $interaction);
        $interaction_class = CMbString::capitalize(strtolower($interaction_class));
        $interaction_class = str_replace([' ', '_'], '', $interaction_class);

        $class = "CFHIRInteraction" . $interaction_class;
        if (!class_exists($class)) {
            throw new CFHIRException("Unsupported interaction '$interaction'");
        }

        return new $class();
    }

    /**
     * Get the resource method name
     *
     * @return string
     */
    public function getResourceMethodName(): string
    {
        return "interaction" . $this::NAME;
    }

    /**
     * Process the data
     *
     * @param string $resourceType Resource type
     * @param array  $data         Data to process
     *
     * @return CFHIRResponse
     * @throws CFHIRException
     */
    public function process(string $resourceType, array $data): CFHIRResponse
    {
        $resource = CFHIR::makeResource($resourceType);

        if (!$resource) {
            throw new CFHIRException("Unknown resource type: '$resourceType'");
        }

        $result = $resource->{$this->name}($data);

        $resource->mapFrom($result);

        return new CFHIRResponse($resource);
    }

    /**
     * Add a parameter to the query
     *
     * @param string $field    Field to search in
     * @param string $value    Value to search
     * @param string $modifier Modifier
     *
     * @return void
     */
    public function addParameter(string $field, ?string $value, ?string $modifier = null): void
    {
        if ($value === null || $value === "") {
            return;
        }

        $this->parameters[] = [
            "field"    => $field,
            "value"    => $value,
            "modifier" => $modifier,
        ];
    }

    /**
     * Build the query
     *
     * @param array Datas
     *
     * @return array
     */
    public function buildQuery(?array $data = array()): array
    {
        $params = [];

        if ($this->complement_url) {
            $params[] = urlencode($this->complement_url);
        }

        foreach ($this->parameters as $_param) {
            $params[] = $_param["field"] . "=" . urlencode($_param["value"]);
        }

        if ($this->add_format) {
            $params[] = "_format=" . urlencode($this->format);
        }

        return [
            "event" => $this->resourceType . ($this->getQueryName() ? "/" . $this->getQueryName() : ""),
            "data"  => implode("&", $params),
        ];
    }

    /**
     * Handles the intercation result to make a response
     *
     * @param CFHIRResource $resource FHIR resource
     * @param mixed         $result   Result
     *
     * @return CFHIRResponse
     */
    public function handleResult(CFHIRResource $resource, $result): CFHIRResponse
    {
        return new CFHIRResponse($resource);
    }

    /**
     * @return string
     */
    public function getQueryName(): ?string
    {
        return $this->name;
    }

    /**
     * Generate exchange FHIR
     *
     * @return CExchangeFHIR
     * @throws Exception
     */
    public function generateExchange(?array $data): CExchangeFHIR
    {
        $exchange_fhir                  = $this->_exchange_fhir ? $this->_exchange_fhir : new CExchangeFHIR();
        $exchange_fhir->date_production = CMbDT::dateTime();
        $exchange_fhir->receiver_id     = $this->_receiver->_id;
        $exchange_fhir->group_id        = $this->_receiver->group_id;
        $exchange_fhir->sender_id       = $this->_sender ? $this->_sender->_id : null;
        $exchange_fhir->sender_class    = $this->_sender ? $this->_sender->_id : null;
        $exchange_fhir->format          = $this->format;
        $exchange_fhir->message_valide  = 1;
        $exchange_fhir->_message        = serialize($data);

        $exchange_fhir->store();

        return $this->_exchange_fhir = $exchange_fhir;
    }
}
