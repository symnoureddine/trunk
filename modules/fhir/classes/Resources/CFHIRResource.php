<?php
/**
 * @package Mediboard\Fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir\Resources;

use DOMDocument;
use DOMElement;
use DOMNode;
use Exception;
use JsonSerializable;
use Ox\Core\Autoload\IShortNameAutoloadable;
use Ox\Core\CApp;
use Ox\Core\CAppUI;
use Ox\Core\CHTTPClient;
use Ox\Core\CMbArray;
use Ox\Core\CMbDT;
use Ox\Core\CMbException;
use Ox\Core\CMbObject;
use Ox\Core\CMbString;
use Ox\Core\CMbXMLDocument;
use Ox\Core\CSQLDataSource;
use Ox\Core\CStoredObject;
use Ox\Core\CView;
use Ox\Interop\Cda\CCdaTools;
use Ox\Interop\Dmp\CDMPTools;
use Ox\Interop\Dmp\CDMPValueSet;
use Ox\Interop\Eai\CDomain;
use Ox\Interop\Eai\CInteropActor;
use Ox\Interop\Eai\CInteropReceiver;
use Ox\Interop\Fhir\CFHIR;
use Ox\Interop\Fhir\CFHIRXPath;
use Ox\Interop\Fhir\Controllers\CFHIRController;
use Ox\Interop\Fhir\CReceiverFHIR;
use Ox\Interop\Fhir\Datatypes\CFHIRDataType;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeCode;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeId;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeInstant;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeInteger;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeString;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeUri;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeAddress;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeAttachment;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeBackboneElement;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeCodeableConcept;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeCoding;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeComplex;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeContained;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeExtension;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeHumanName;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeIdentifier;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeMeta;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeReference;
use Ox\Interop\Fhir\Event\CFHIREvent;
use Ox\Interop\Fhir\Exception\CFHIRException;
use Ox\Interop\Fhir\Exception\CFHIRExceptionNotFound;
use Ox\Interop\Fhir\Interactions\CFHIRInteraction;
use Ox\Interop\Fhir\Interactions\CFHIRInteractionCreate;
use Ox\Interop\Fhir\Interactions\CFHIRInteractionSearch;
use Ox\Interop\Fhir\Request\CFHIRRequest;
use Ox\Interop\Fhir\Response\CFHIRResponse;
use Ox\Interop\Fhir\Response\CFHIRResponseJSON;
use Ox\Interop\Fhir\Response\CFHIRResponseXML;
use Ox\Interop\InteropResources\CInteropResources;
use Ox\Interop\Xds\Structure\CXDSValueSet;
use Ox\Mediboard\Cabinet\CConsultation;
use Ox\Mediboard\Cabinet\CPlageconsult;
use Ox\Mediboard\CompteRendu\CCompteRendu;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Files\CDocumentItem;
use Ox\Mediboard\Files\CDocumentReference;
use Ox\Mediboard\Files\CFile;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\PlanningOp\CSejour;
use Ox\Mediboard\Sante400\CHyperTextLink;
use Ox\Mediboard\Sante400\CIdSante400;
use Ox\Mediboard\System\CSenderHTTP;
use Ox\Mediboard\System\CUserLog;

/**
 * FHIR generic resource
 */
class CFHIRResource implements JsonSerializable, IShortNameAutoloadable
{
    /** @var string */
    public const RESOURCE_TYPE = "";

    /** @var string[] */
    public const PROFILE = [];

    /** @var bool */
    protected const ACCEPT_VERSION_ID = false;

    /** @var bool */
    protected const APPEND_SELF = true;

    /** @var CFHIRDataTypeId */
    public $id;

    /** @var CFHIRDataTypeMeta */
    public $meta;

    /** @var CFHIRDataTypeContained[] */
    public $contained;

    /** @var CFHIRDataTypeIdentifier[] */
    public $identifier;

    /** @var CSenderHTTP */
    public $_sender;

    /** @var CReceiverFHIR */
    public $_receiver;

    /** @var Search ID */
    public $_search_id;

    /** @var CFHIRInteraction */
    private $interaction;

    /**
     * @param                    $name
     * @param                    $item
     * @param DOMNode|DOMElement $node
     */
    public static function appendToDOM($name, $item, DOMNode $node)
    {
        /** @var DOMDocument $dom */
        $dom = $node->ownerDocument ?: $node;

        if (is_array($item)) {
            foreach ($item as $_element) {
                if (!$_element) {
                    continue;
                }
                self::appendToDOM($name, $_element, $node);
            }

            return;
        }

        if ($item instanceof CFHIRResource) {
            $_dom_element = $dom->createElement($name);
            $node->appendChild($_dom_element);
            $item->toXML($_dom_element);

            return;
        }

        if ($item instanceof CFHIRDataTypeComplex) {
            $_dom_element = $dom->createElement($name);

            if ($item instanceof CFHIRDataTypeExtension) {
                $_dom_element->setAttribute('url', $item->url);
                $item->url = null;
            }
            $node->appendChild($_dom_element);
            $item->toXML($_dom_element);

            return;
        }

        if ($item instanceof CFHIRDataType && $item->getValue()) {
            $_dom_element = $dom->createElement($name);
            $_dom_element->setAttribute("value", utf8_encode($item->toXML($_dom_element)));
            $node->appendChild($_dom_element);

            return;
        }

        if ($item && is_string($item)) {
            $_dom_element = $dom->createElement($name);
            $_dom_element->setAttribute("value", utf8_encode($item));
            $node->appendChild($_dom_element);
        }
    }

    /**
     * @param DOMElement|DOMDocument $node
     */
    public function toXML(DOMNode $node)
    {
        $dom = $node->ownerDocument ?: $node;

        if ($this::APPEND_SELF) {
            $root = $dom->createElementNS(CFHIRResponseXML::NS, $this->getResourceType());
            $node->appendChild($root);
            $node = $root;
        }

        foreach ($this as $_name => $_items) {
            if (!$_items || $_name[0] === '_') {
                continue;
            }

            self::appendToDOM($_name, $_items, $node);
        }
    }

    /**
     * Get resource type
     *
     * @return string
     */
    public function getResourceType(): string
    {
        return $this::RESOURCE_TYPE;
    }

    /**
     * Transform response in XML
     *
     * @param string $response response
     * @param string $format   format
     *
     * @return array
     */
    static function transformResponseInXML($response, $format)
    {
        switch (true) {
            case preg_match("/application\/fhir\+json/", $format):
                $dom1 = CFHIRResponseJSON::toXML($response);

                // Weird bug, we can't use the DOMDocument directly .......
                $dom = new CMbXMLDocument('utf-8');
                $dom->loadXML($dom1->saveXML());
                $dom->formatOutput = true;

                $lang = "javascript";
                break;

            default:
            case preg_match("/application\/fhir\+xml/", $format):
                if (strpos($response, "<") === false) {
                    CAppUI::stepAjax("CReceiverFHIR-msg-No XML response", UI_MSG_WARNING);
                    CView::checkin();
                    CApp::rip();
                }

                $dom = new DOMDocument();
                $dom->loadXML($response);
                $dom->formatOutput = true;

                $response = $dom->saveXML();
                $lang     = "xml";
                break;
        }

        return ["lang" => $lang, "response" => $response, "dom" => $dom];
    }

    /**
     * @param array  $data   data
     * @param string $format format
     *
     * @return DOMDocument
     * @throws CFHIRException
     */
    static function transformRequestInXML($data, $format)
    {
        if (!preg_match("@application/fhir\+(\w+)@", $format, $matches)) {
            throw new CFHIRException("Unsupported format '$format'");
        }

        switch ($format) {
            case CFHIR::CONTENT_TYPE_XML:
                $xml = CMbArray::get($data, "data");

                if (strpos($xml, "<") === false) {
                    throw new CFHIRException("XML mal formed");
                }

                $dom = new DOMDocument();
                $dom->loadXML($xml);
                $dom->formatOutput = true;
                break;
            case CFHIR::CONTENT_TYPE_JSON:
                $json = CMbArray::get($data, "data");

                $dom1 = CFHIRResponseJSON::toXML($json);
                $dom  = new DOMDocument();
                $dom->loadXML($dom1->saveXML());
                $dom->formatOutput = true;
                break;
            default:
                throw new CFHIRException("Unsupported format '$format'");
        }

        return $dom;
    }

    /**
     * Get resource type
     *
     * @return string
     */
    public function getResourceId(): ?string
    {
        if (!$this->id) {
            return null;
        }

        if (!$this->id instanceof CFHIRDataTypeId) {
            return null;
        }

        return $this->id->getValue();
    }

    /**
     * Map data to the current object
     *
     * @param array $data Data to map from
     *
     * @return void
     */
    public function map($data)
    {
        foreach (get_object_vars($this) as $_key => $_value) {
            if (array_key_exists($_key, $data)) {
                $this->{$_key} = $data[$_key];
            }
        }
    }

    /**
     * Process data
     *
     * @param string $interactionName Interaction name
     * @param array  $data            Data
     * @param string $format          Format
     *
     * @return CFHIRResponse|CFHIRRequest
     * @throws CFHIRException
     */
    public function process(string $interactionName, ?array $data = null, ?string $format = null)
    {
        $this->interaction = CFHIRInteraction::make($interactionName);
        // TODO : Charger le sender http
        $this->_sender = CMbObject::loadFromGuid("CSenderHTTP-1");

        $method = $this->interaction->getResourceMethodName();
        if (!method_exists($this, $method)) {
            throw new CFHIRException("Unknown interaction type: '$interactionName'", 404);
        }

        $result = $this->$method($data, $format);

        return $this->interaction->handleResult($this, $result);
    }

    /**
     * Build Resource
     *
     * @param CMbObject  $object object
     * @param CFHIREvent $event  FHIR event
     *
     * @return void
     * @throws CFHIRException
     */
    public function build(CMbObject $object, CFHIREvent $event)
    {
        $this->_receiver = $event->_receiver;

        if (!$this->_receiver || !$this->_receiver->_id) {
            throw new CFHIRException("Object receiver not present");
        }

        if (!$this->_receiver->_tag_fhir) {
            throw new CFHIRException("Tag FHIR not defined on receiver");
        }
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $data = CFHIR::filterData($this);

        if (!$this::APPEND_SELF) {
            return $data;
        }

        $prepend = [
            "resourceType" => $this::RESOURCE_TYPE,
        ];

        return array_merge($prepend, $data);
    }

    public function toJSON()
    {
        return CMbArray::toJSON($this);
    }

    public function addWhereFromQuery(CSQLDataSource $ds, $query, $query_field, &$where, $where_field)
    {
        if (isset($query[$query_field]) && count($query[$query_field])) {
            $query_item          = $query[$query_field][0];
            $where[$where_field] = $ds->prepare("$query_item[0] ?", $query_item[1]);
        }
    }

    /**
     * Build Meta data for resource
     *
     * @param string $profile profile
     *
     * @return CFHIRDataTypeReference
     */
    public function addMetaData($profile = null)
    {
        return CFHIRDataTypeReference::build(
            ["profile" => new CFHIRDataTypeString($profile)]
        );
    }

    /**
     * @param string[]|string $profiles
     *
     * @return void
     */
    protected function addProfiles($profiles): void
    {
        if (!$this->meta) {
            $this->meta = new CFHIRDataTypeMeta();
        }

        if (!is_array($profiles)) {
            $profiles = [$profiles];
        }
        $profiles = array_map(
            function ($profile) {
                return new CFHIRDataTypeString($profile);
            },
            $profiles
        );

        $this->meta->profile = array_merge($this->meta->profile ?: [], $profiles);
    }

    /**
     * Build type for resource
     *
     * @param string $type type
     *
     * @return CFHIRDataTypeCode
     */
    public function addTypeCode($type = null)
    {
        return new CFHIRDataTypeCode($type);
    }

    /**
     * Set masterIdentifier field on resource
     *
     * @param string $identifier identifier
     * @param string $system     system
     *
     * @return CFHIRDataTypeIdentifier
     */
    public function addMasterIdentifier($identifier, $system = null)
    {
        return CFHIRDataTypeIdentifier::build(
            [
                "system" => $system,
                "value"  => $identifier,
            ]
        );
    }

    /**
     * Build identifier field on resource
     *
     * @param string                       $identifier identifier
     * @param CFHIRDataTypeCodeableConcept $type       type
     * @param string                       $use        use value
     * @param string                       $system     system value
     * @param bool                         $merge
     *
     * @return CFHIRDataTypeIdentifier[]
     */
    public function addIdentifier(
        ?string $identifier = null,
        ?CFHIRDataTypeCodeableConcept $type = null,
        ?string $use = null,
        ?string $system = null,
        bool $merge = true
    ): array {
        if (!$this->identifier) {
            $this->identifier = [];
        }

        $data = CFHIRDataTypeIdentifier::build(
            [
                'type' => $type ?: null,
                'use' => $use ? new CFHIRDataTypeCode($use) : null,
                'system' => $system ? new CFHIRDataTypeUri($system) : null,
                'value' => $identifier ? new CFHIRDataTypeString($identifier) : null,
            ]
        );

        if (!$merge) {
            return [$data];
        }

        return array_merge($this->identifier, [$data]);
    }

    /**
     * @param string          $family
     * @param string[]|string $given
     * @param string|null     $use
     * @param string|null     $text
     * @param array           $reference
     *
     * @return CFHIRDataTypeHumanName[]
     */
    protected function addName(
        string $family,
        $given,
        ?string $use = null,
        ?string $text = null,
        array $reference = []
    ): array {
        if (!is_array($given)) {
            $given = [$given];
        }

        $formatted_given = array_map(
            function ($give) {
                return new CFHIRDataTypeString($give);
            },
            array_filter($given)
        );

        return array_merge(
            $reference,
            [
                CFHIRDataTypeHumanName::build(
                    [
                        "use"    => $use ? new CFHIRDataTypeCode($use) : null,
                        "text"   => $text ? new CFHIRDataTypeString($text) : null,
                        "family" => new CFHIRDataTypeString($family),
                        "given"  => $formatted_given,
                    ]
                ),
            ]
        );
    }

    /**
     * Build request field on resource
     *
     * @param string $method method
     * @param string $url    url
     *
     * @return void
     */
    public function request($method, $url)
    {
        $this->request = CFHIRDataTypeBackboneElement::build(
            [
                "method" => new CFHIRDataTypeString($method),
                "url"    => new CFHIRDataTypeString($url),
            ]
        );
    }

    /**
     * Build status field on resource
     *
     * @param string $status status
     *
     * @return CFHIRDataTypeCode
     */
    function addStatus($status)
    {
        return new CFHIRDataTypeCode($status);
    }

    /**
     * Build type field on resource
     *
     * @param CMbObject $object object
     *
     * @return CFHIRDataTypeCodeableConcept
     */
    function addTypeCodeableConcept(CMbObject $object)
    {
        $xds_value_set = new CXDSValueSet();

        return CFHIRDataTypeCodeableConcept::build(
            $this->formatCodeableConcept($xds_value_set->getContentTypeCode($object))
        );
    }

    /**
     * Format codeable concept fhir type
     *
     * @param array $data data
     *
     * @return array
     */
    public function formatCodeableConcept($data)
    {
        return [
            "coding" => [
                CFHIRDataTypeCoding::build(
                    [
                        "system"  => new CFHIRDataTypeString(CMbArray::get($data, "codeSystem")),
                        "code"    => new CFHIRDataTypeString(CMbArray::get($data, "code")),
                        "display" => new CFHIRDataTypeString(CMbArray::get($data, "displayName")),
                    ]
                ),
            ],
        ];
    }

    /**
     * @param array       $codingData
     * @param string|null $text
     * @param array       $reference
     *
     * @return CFHIRDataTypeCodeableConcept[]
     */
    protected function addCodeableConcepts(array $codingData, ?string $text = null, array $reference = []): array
    {
        $codingRefs = [];
        foreach ($codingData as $key => $coding) {
            if (is_object($coding) && $coding instanceof CFHIRDataTypeCoding) {
                $codingRefs[] = $coding;
                continue;
            }
            $system  = CMbArray::get($coding, 'system', '');
            $code    = CMbArray::get($coding, 'code', '');
            $display = CMbArray::get($coding, 'display', '');

            $codingRefs[] = $this->addCoding($system, $code, $display);
        }

        $data = ['coding' => $codingRefs];
        if ($text) {
            $data['text'] = new CFHIRDataTypeString($text);
        }

        return array_merge($reference, [CFHIRDataTypeCodeableConcept::build($data)]);
    }

    /**
     * @param array $data
     *
     * @return mixed
     */
    protected function first(array $data)
    {
        return reset($data);
    }

    /**
     * @param string $system
     * @param string $code
     * @param string $displayName
     * @param array  $reference
     *
     * @return CFHIRDataTypeCoding[]
     */
    protected function addCoding(string $system, string $code, string $displayName, array $reference = []): array
    {
        $data = [
            "system"  => new CFHIRDataTypeString($system),
            "code"    => new CFHIRDataTypeCode($code),
            "display" => new CFHIRDataTypeString($displayName),
        ];

        return array_merge($reference, [CFHIRDataTypeCoding::build($data)]);
    }

    /**
     * Build docStatus field on resource
     *
     * @param string $docStatus docStatus
     *
     * @return CFHIRDataTypeCode
     */
    function addDocStatus($docStatus = "final")
    {
        return new CFHIRDataTypeCode($docStatus);
    }

    /**
     * Build description field on resource
     *
     * @param string $description description
     *
     * @return CFHIRDataTypeString
     */
    function addDescription($description)
    {
        return new CFHIRDataTypeString($description);
    }

    /**
     * Build securityLabel field on resource
     *
     * @param array $values values
     *
     * @return CFHIRDataTypeCodeableConcept
     */
    function addSecurityLabel($values)
    {
        return CFHIRDataTypeCodeableConcept::build(
            $this->formatCodeableConcept($values)
        );
    }

    /**
     * Build class code field on resource
     *
     * @param array $values values
     *
     * @return CFHIRDataTypeCoding
     */
    function addClassCode($values)
    {
        return CFHIRDataTypeCoding::build(
            $this->formatCodeableConcept($values)
        );
    }

    /**
     * Build created field on resource
     *
     * @param string $creation_date creation date
     *
     * @return CFHIRDataTypeInstant
     */
    function addCreated($creation_date)
    {
        return new CFHIRDataTypeInstant($creation_date);
    }

    /**
     * Build indexed field on resource
     *
     * @param string $creation_date creation date
     *
     * @return CFHIRDataTypeInstant
     */
    function addIndexed($creation_date)
    {
        return new CFHIRDataTypeInstant($creation_date);
    }

    /**
     * Build subject field on resource
     *
     * @param CMbObject $object object
     *
     * @return CFHIRDataTypeReference
     * @throws CFHIRException
     */
    function addSubject(CMbObject $object)
    {
        $resource_name = null;
        switch ($object->_class) {
            case "CPatient":
                $resource_name = "Patient";
                break;
            case "CSejour":
                $resource_name = "Encounter";
                break;
            case "CMediusers":
                $resource_name = "Practitioner";
                break;
            default;
        }

        return CFHIRDataTypeReference::build(
            [
                "reference" => new CFHIRDataTypeString(
                    CFHIRController::getUrl(
                        "fhir_read",
                        [
                            'resource'    => $resource_name,
                            'resource_id' => $object->_id,
                        ]
                    )
                ),
                // TODO XDS TOOLKIT
                //"reference" => new CFHIRDataTypeString("http://localhost:8080/xds_toolkit_7.2.1/fsim/default__fhir_support/fhir/Patient/4e7a2153-6659-47d4-b0f6-82616a81b806"),
            ]
        );
    }

    /**
     * Build identifier field on resource
     *
     * @param string $full_url_uuid uuid submission
     *
     * @return CFHIRDataTypeReference
     */
    function addContent($full_url_uuid)
    {
        return CFHIRDataTypeReference::build(
            [
                "pReference" => CFHIRDataTypeReference::build(
                    ["reference" => new CFHIRDataTypeString($full_url_uuid)]
                ),
            ]
        );
    }

    /**
     * Add association like XDS in relatesTo
     *
     * @param CCompteRendu       $cr                 cr
     * @param CDocumentReference $document_reference document reference
     * @param CInteropReceiver   $receiver           receiver
     *
     * @return CFHIRDataTypeBackboneElement
     * @throws CFHIRException
     */
    function addAssociation(CCompteRendu $cr, CDocumentReference $document_reference, CInteropReceiver $receiver)
    {
        $hypertext               = new CHyperTextLink();
        $hypertext->object_class = $document_reference->_class;
        $hypertext->object_id    = $document_reference->parent_id;
        $hypertext->name         = "DocReference_" . $receiver->_guid;
        $hypertext->loadMatchingObject();

        if (!$hypertext->_id) {
            return null;
        }

        return CFHIRDataTypeBackboneElement::build(
            [
                "code"   => new CFHIRDataTypeString("replaces"),
                "target" => CFHIRDataTypeReference::build(
                    [
                        /*"reference" => new CFHIRDataTypeString(
                          FhirController::getUrl("fhir_read", array(
                            'resource'    => "DocumentReference",
                            'resource_id' => $document_reference->parent_id
                          ))
                        )*/
                        "reference" => new CFHIRDataTypeString($hypertext->link),
                    ]
                ),
            ]
        );
    }

    /**
     * Build context field on resource
     *
     * @param CDocumentItem $object object
     * @param CSejour       $sejour sejour
     *
     * @return CFHIRDataTypeBackboneElement
     * @throws Exception
     */
    function addContext(CDocumentItem $object, CSejour $sejour = null)
    {
        $data = [];

        if ($sejour) {
            $data["encounter"] = CFHIRDataTypeReference::build(
                [
                    "reference" => new CFHIRDataTypeString(
                        CFHIRController::getUrl(
                            "fhir_read",
                            [
                                'resource'    => "Encounter",
                                'resource_id' => $sejour->_id,
                            ]
                        )
                    ),
                ]
            );

            $sejour->loadRefPatient();
        }

        // DocumentEntry.eventCodeList XDS
        // TODO : A gerer
        /*$data["event"] = CFHIRDataTypeCodeableConcept::build(
          array(
            "coding" => CFHIRDataTypeCodeableConcept::build(
              array("system" => "titi")
            )
          )
        );*/

        // Period
        $date = $object instanceof CCompteRendu ? $object->creation_date : $object->file_date;
        /*$data["period"] = CFHIRDataTypePeriod::build(
          $this->formatPeriod($date, $date)
        );*/

        // DocumentEntry.healthcareFacilityTypeCode XDS
        $group         = $sejour ? $sejour->loadRefEtablissement() : CGroups::loadCurrent();
        $xds_value_set = new CXDSValueSet();
        //$values_healthcare_facility = $xds_value_set::getHealthcareFacilityTypeCode($group);

        $values_healthcare_facility = [
            "codeSystem"  => "http://snomed.info/sct",
            "code"        => "35971002",
            "displayName" => "Ambulatory care site",
        ];

        if ($values_healthcare_facility) {
            $data["facilityType"] = CFHIRDataTypeCodeableConcept::build(
                $this->formatCodeableConcept($values_healthcare_facility)
            );
        }

        // DocumentEntry.practiceSettingCode XDS
        $values_practice_setting_code = $xds_value_set::getPracticeSettingCode();
        $values_practice_setting_code = [
            "codeSystem"  => "http://connectathon.ihe",
            "code"        => "Practice-E",
            "displayName" => "Ophthalmology",
        ];

        if ($values_practice_setting_code) {
            $data["practiceSetting"] = CFHIRDataTypeCodeableConcept::build(
                $this->formatCodeableConcept($values_practice_setting_code)
            );
        }

        if ($sejour) {
            $data["sourcePatientInfo"] = CFHIRDataTypeReference::build(
            //array("reference" => new CFHIRDataTypeString(CFHIR::getRootUrl()."/Patient/".$sejour->_ref_patient->_id))
                ["reference" => new CFHIRDataTypeString("#" . $sejour->_ref_patient->_guid)]
            );
        }

        return CFHIRDataTypeBackboneElement::build($data);
    }

    /**
     * Format period fhir type
     *
     * @param string $start start
     * @param string $end   end
     *
     * @return array
     */
    function formatPeriod($start, $end = null)
    {
        $data = [
            "start" => new CFHIRDataTypeInstant($start),
        ];

        if ($end) {
            $data['end'] = new CFHIRDataTypeInstant($end);
        }

        return $data;
    }

    /**
     * Set content attachment field on resource
     *
     * @param CDocumentItem $object object
     *
     * @return CFHIRDataTypeBackboneElement
     * @throws CMbException
     */
    function addAttachment(CDocumentItem $object)
    {
        $mediaType = "application/pdf";

        $file = null;
        //Génération du PDF
        if ($object instanceof CFile) {
            $path = $object->_file_path;
            switch ($object->file_type) {
                case "image/tiff":
                    $mediaType = "image/tiff";
                    break;
                case "application/pdf":
                    $mediaType = $object->file_type;
                    $path      = CCdaTools::generatePDFA($object->_file_path);
                    break;
                case "image/jpeg":
                case "image/jpg":
                    $mediaType = "image/jpeg";
                    break;
                case "application/rtf":
                    $mediaType = "text/rtf";
                    break;
                default:
                    throw new CMbException("fhir-msg-Document type authorized in FHIR|pl");
            }
        } else {
            if ($msg = $object->makePDFpreview(1, 0)) {
                throw new CMbException($msg);
            }
            $file = $object->_ref_file;
            $path = CCdaTools::generatePDFA($file->_file_path);
        }

        return CFHIRDataTypeBackboneElement::build(
            [
                "attachment" => CFHIRDataTypeAttachment::build(
                    [
                        "contentType" => new CFHIRDataTypeCode($mediaType),
                        "language"    => new CFHIRDataTypeString("fr-FR"),
                        //"data"        => new CFHIRDataTypeBase64Binary(base64_encode(file_get_contents($path))),
                        "title"       => new CFHIRDataTypeString(
                            $object instanceof CCompteRendu ? $object->nom : $object->file_name
                        ),
                        "url"         => new CFHIRDataTypeString($this->constructUrlBinary($object)),
                        "size"        => new CFHIRDataTypeInteger(
                            $object instanceof CFile ? $object->doc_size : $file->doc_size
                        ),
                    ]
                ),

                "format" => CFHIRDataTypeCoding::build(
                    [
                        "system"  => new CFHIRDataTypeString("urn:oid:1.3.6.1.4.1.19376.1.2.3"),
                        "code"    => new CFHIRDataTypeString("urn:ihe:pcc:apr:handp:2008"),
                        "display" => new CFHIRDataTypeString("Antepartum Record (APR) - History and Physical"),
                    ]
                ),
            ]
        );
    }

    /**
     * Construct URL for binary resource
     *
     * @param CDocumentItem $object object
     *
     * @return string
     * @throws CFHIRException
     */
    function constructUrlBinary(CDocumentItem $object)
    {
        return $object->_guid;
    }

    /**
     * Build source for resource
     *
     * @param string $uri uri
     *
     * @return CFHIRDataTypeUri
     */
    function addSourceURI($uri)
    {
        return new CFHIRDataTypeUri($uri);
    }

    /**
     * Build author field on resource
     *
     * @param CMediusers $author author
     *
     * @return CFHIRDataTypeReference
     */
    function addAuthor(CMediusers $author)
    {
        return CFHIRDataTypeReference::build(
            [
                "reference" => new CFHIRDataTypeString("#" . $author->_guid),
            ]
        );
    }

    /**
     * Build authenticator field on resource
     *
     * @param CMediusers $author author
     *
     * @return CFHIRDataTypeReference
     */
    function addAuthenticator(CMediusers $author)
    {
        return CFHIRDataTypeReference::build(
            [
                "reference" => new CFHIRDataTypeString("#" . $author->_guid),
            ]
        );
    }

    /**
     * Get type
     *
     * @param CDocumentItem $object object
     *
     * @return string
     * @throws CMbException
     */
    function getContentType(CDocumentItem $object)
    {
        $mediaType = "application/pdf";

        if ($object instanceof CFile) {
            switch ($object->file_type) {
                case "image/tiff":
                    $mediaType = "image/tiff";
                    break;
                case "application/pdf":
                    $mediaType = $object->file_type;
                    break;
                case "image/jpeg":
                case "image/jpg":
                    $mediaType = "image/jpeg";
                    break;
                case "application/rtf":
                    $mediaType = "text/rtf";
                    break;
                case "text/plain":
                    $mediaType = "text/plain";
                    break;
                default:
                    throw new CMbException("fhir-msg-Document type authorized in FHIR|pl");
            }
        }

        return new CFHIRDataTypeString($mediaType);
    }

    /**
     * Put content on FHIR Resource
     *
     * @param CDocumentItem $object object
     *
     * @return CFHIRDataTypeString
     * @throws CMbException
     */
    function putContent(CDocumentItem $object)
    {
        //Génération du PDF
        if ($object instanceof CFile) {
            $path = $object->_file_path;
            switch ($object->file_type) {
                case "image/tiff":
                    $mediaType = "image/tiff";
                    break;
                case "application/pdf":
                    $mediaType = $object->file_type;
                    $path      = CCdaTools::generatePDFA($object->_file_path);
                    break;
                case "image/jpeg":
                case "image/jpg":
                    $mediaType = "image/jpeg";
                    break;
                case "application/rtf":
                    $mediaType = "text/rtf";
                    break;
                case "text/plain":
                    $mediaType = "text/plain";
                    break;
                default:
                    throw new CMbException("fhir-msg-Document type authorized in FHIR|pl");
            }
        } else {
            if ($msg = $object->makePDFpreview(1, 0)) {
                throw new CMbException($msg);
            }
            $file = $object->_ref_file;
            $path = CCdaTools::generatePDFA($file->_file_path);
        }

        // TODO : Voir si on fait un PDFA
        return new CFHIRDataTypeString(base64_encode($object->getBinaryContent()));
        //return new CFHIRDataTypeString(base64_encode(file_get_contents($path)));
    }

    /**
     * Get patient of document
     *
     * @param CFHIRXPath $xpath              xpath
     * @param DOMNode    $node_doc_reference node
     *
     * @return CPatient
     * @throws CFHIRException|Exception
     */
    function getSubject(CFHIRXPath $xpath, DOMNode $node_doc_reference)
    {
        $url = $xpath->getAttributeValue("fhir:subject/fhir:reference", $node_doc_reference);
        if (!$url) {
            throw new CFHIRException("DocumentReference.subject.reference not found. This value must be present.");
        }

        $patient_node         = CFHIRResourcePatient::getNodePatient(
            CFHIRResource::getExterneResource($url, "patient")
        );
        $patient              = CFHIRResourcePatient::mapPatientFromXML(new CPatient(), $patient_node);
        $mb_patient           = CFHIRResourcePatient::patientIsKnow($patient);
        $mb_patient->_ref_url = $url;

        return $mb_patient;
    }

    /**
     * Get externe resource
     *
     * @param string $url           url
     * @param string $resource_type resource type
     *
     * @return bool|string
     * @throws Exception
     */
    static function getExterneResource($url, $resource_type)
    {
        switch ($resource_type) {
            case "binary":
            case "patient":
                $http_client = new CHTTPClient($url . "?_format=application/fhir+json");

                return $http_client->get();
                break;
            default;
        }
    }

    /**
     * Get encounter (context) of document
     *
     * @param CFHIRXPath $xpath              xpath
     * @param DOMNode    $node_doc_reference node
     *
     * @return CSejour|null
     * @throws CFHIRException
     */
    function getEncounter(CFHIRXPath $xpath, DOMNode $node_doc_reference)
    {
        $url_encounter = $xpath->getAttributeValue("fhir:context/fhir:encounter/fhir:reference", $node_doc_reference);
        if (!$url_encounter) {
            return null;
        }

        // Voir comment on va gérer
        return null;

        // Est-ce que la requête concerne une ressource du serveur ?
        $host = CMbArray::get(parse_url($url_encounter), "host");
        if (!$host) {
            throw new CFHIRException("Host not retrieve");
        }

        if ($host && !preg_match("#" . $host . "#", CFHIR::getRootUrl())) {
            // TODO : A decommenter
            // throw new CFHIRException("Value of subject for DocumentReference not found on server");
        }

        // Vérification du format de la requête (http://host_mb/Encounter/ID)
        $info_url = explode("/", $url_encounter);

        // Avant derniere position
        $key_type_resource = count($info_url) - 2;
        $type_resource     = CMbArray::get($info_url, $key_type_resource);

        $url_example = CFHIR::getRootUrl() . "Encounter/ID";
        if ($type_resource !== "Encounter") {
            throw new CFHIRException(
                "Resource type in url of DocumentReference.context.encounter.reference must be 'Encounter' and not '$type_resource'. Url must be, for example, $url_example"
            );
        }

        $id_encounter = end($info_url);
        if (!$id_encounter) {
            throw new CFHIRException(
                "Impossible to retrieve ID encounter's in url of DocumentReference.context.encounter.reference. Url must be, for example, $url_example"
            );
        }

        $sejour = new CSejour();
        $sejour->load($id_encounter);

        if (!$sejour || !$sejour->_id || ($sejour->_id != $id_encounter)) {
            throw new CFHIRException("Impossible to retrieve encounter with id '$id_encounter'.");
        }

        return $sejour;
    }

    /**
     * Get sourcePatientInfo in DocumentReference
     *
     * @param CFHIRXPath $xpath
     * @param DOMNode    $node_doc_reference
     *
     * @return CPatient
     * @throws CFHIRException|Exception
     */
    function getPatientInfo(CFHIRXPath $xpath, DOMNode $node_doc_reference)
    {
        $source_patient_info_url = $xpath->getAttributeValue(
            "fhir:context/fhir:sourcePatientInfo/fhir:reference",
            $node_doc_reference
        );

        if (!$source_patient_info_url) {
            throw new CFHIRException("Impossible to retrieve DocumentReference.context.sourcePatientInfo.reference.");
        }
        // Suppression du premier caractere qui est l'ancre (exemple : #CPatient-22)
        $source_patient_info_url = substr($source_patient_info_url, 1);

        $contained_patient_node = $xpath->getNode(
            "//fhir:id[@value='$source_patient_info_url']/ancestor::*[position()=1]"
        );

        if (!$contained_patient_node || $contained_patient_node->nodeName !== "Patient") {
            throw new CFHIRException("Impossible to retrieve contained.Patient with id $source_patient_info_url");
        }

        return CFHIRResourcePatient::mapPatientFromXML(new CPatient(), $contained_patient_node);
    }

    /**
     * Get document unique id (entryUUID.uniqueID en XDS)
     *
     * @param CFHIRXPath $xpath              xpath
     * @param DOMNode    $node_doc_reference node
     *
     * @return string
     * @throws CFHIRException
     */
    function getDocUniqueID(CFHIRXPath $xpath, DOMNode $node_doc_reference)
    {
        $doc_unique_id = $xpath->getAttributeValue("fhir:masterIdentifier/fhir:value", $node_doc_reference);
        if (!$doc_unique_id) {
            throw new CFHIRException(
                "Impossible to retrieve DocumentReference.masterIdentifier.value. This value must be present."
            );
        }

        return $doc_unique_id;
    }

    /**
     * Get content of document
     *
     * @param CFHIRXPath $xpath              xpath
     * @param DOMNode    $node_doc_reference node
     * @param CMbObject  $object             object
     *
     * @return CFile
     * @throws CFHIRException|Exception
     */
    function getAttachment(CFHIRXPath $xpath, DOMNode $node_doc_reference, $object)
    {
        $nodes_attachment = $xpath->query("fhir:content/fhir:attachment", $node_doc_reference);
        $node_attachment  = $nodes_attachment->item(0);

        $content_type = $xpath->getAttributeValue("fhir:contentType", $node_attachment);
        if (!$content_type) {
            throw new CFHIRException(
                "Impossible to retrieve DocumentReference.content.attachment.contentType. This value must be present."
            );
        }

        $file            = new CFile();
        $file->file_type = $content_type;

        $name_file       = $xpath->getAttributeValue("fhir:title", $node_attachment);
        $file->file_name = $name_file ? $name_file : "Document_FHIR";

        $language       = $xpath->getAttributeValue("fhir:language", $node_attachment);
        $file->language = $language ? $language : "fr-FR";

        $data = $xpath->getAttributeValue("fhir:data", $node_attachment);
        $url  = $xpath->getAttributeValue("fhir:url", $node_attachment);

        if (!$data && !$url) {
            throw new CFHIRException(
                "Impossible to retrieve DocumentReference.content.attachment.data and 
      DocumentReference.content.attachment.url. One of these two values must be present."
            );
        }

        $content = null;
        if ($data) {
            $content = base64_decode($data);
        }

        if ($url && !$content) {
            // Soit le contenu du fichier est dans le message (<Binary>), soit il faut une requête GET pour récupérer le contenu
            // On check dans la valeur de <fullUrl> ou dans l'id de <entry>
            $entry_node                  = $xpath->getNode("//fhir:entry[@id='$url']");
            $entry_node_by_full_url_node = $xpath->getNode("//fhir:fullUrl[@value='$url']/ancestor::*[position()=1]");

            // Récupération du contenu dans la trame
            if ($entry_node && $entry_node->nodeName == "entry") {
                $content_value = $xpath->getAttributeValue("fhir:resource/fhir:Binary/fhir:content", $entry_node);

                if ($content_value) {
                    $content = base64_decode($content_value);
                }
            } elseif ($entry_node_by_full_url_node && $entry_node_by_full_url_node->nodeName == "entry") {
                $content_value = $xpath->getAttributeValue(
                    "fhir:resource/fhir:Binary/fhir:content",
                    $entry_node_by_full_url_node
                );

                if ($content_value) {
                    $content = base64_decode($content_value);
                }
            } // Récupération du binary en lançant une autre requête
            else {
                $binary_resource = CFHIRResource::getExterneResource($url, "binary");
                $content         = $this->getContentFromBinaryResource($binary_resource, $url);
            }
        }

        if (!$content) {
            throw new CFHIRException("Impossible to retrieve content of DocumentReference.");
        }

        // Author : prendre un CMediusers ?
        $file->author_id = CMediusers::get()->_id;

        $file->fillFields();
        $file->updateFormFields();
        $file->setObject($object);
        $file->setContent($content);

        if ($msg = $file->store()) {
            throw new CFHIRException("Impossible to store document : $msg");
        }

        return $file;
    }

    /**
     * Get value of content node (content document)
     *
     * @param string $binary_resource binary resource
     * @param string $url             url
     *
     * @return string
     * @throws CFHIRException
     */
    function getContentFromBinaryResource($binary_resource, $url)
    {
        if (!$binary_resource) {
            throw new CFHIRException("Impossible to retrieve data from $url");
        }

        // TODO XDS TOOLKIT : Contenu directement retourné :
        return base64_encode($binary_resource);

        if (strpos($binary_resource, "<") === false) {
            $dom1 = CFHIRResponseJSON::toXML($binary_resource);
            $dom  = new DOMDocument();
            $dom->loadXML($dom1->saveXML());
        } else {
            $dom = new DOMDocument();
            $dom->loadXML($binary_resource);
        }

        $xpath_binary_resource = new CFHIRXPath($dom);
        $content               = $xpath_binary_resource->getAttributeValue("fhir:content", $dom->documentElement);

        if (!$content) {
            throw new CFHIRException("Impossible to retrieve content from $url");
        }

        return CFHIRResource::validBase64($content) ? base64_decode($content) : $content;
    }

    /**
     * Check string if encoded in base64
     *
     * @param string $string string
     *
     * @return bool
     */
    static function validBase64($string)
    {
        $decoded = base64_decode($string, true);

        // Check if there is no invalid character in string
        if (!preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $string)) {
            return false;
        }

        // Decode the string in strict mode and send the response
        if (!base64_decode($string, true)) {
            return false;
        }

        // Encode and compare it to original one
        if (base64_encode($decoded) != $string) {
            return false;
        }

        return true;
    }

    /**
     * @param CMbObject $object_mb   object_mb
     * @param CMbObject $object_fhir object fhir
     *
     * @return bool
     */
    public function compareObject(CMbObject $object_mb, CMbObject $object_fhir): bool
    {
        switch ($object_mb->_class) {
            case "CPatient":
                /** @var CPatient $patient1 */
                $patient1 = $object_mb;
                /** @var CPatient $patient2 */
                $patient2 = $object_fhir;

                if ($patient2->_class !== "CPatient") {
                    return false;
                }

                if ($patient1->nom && $patient2->nom && ($patient1->nom != $patient2->nom)) {
                    return false;
                }

                if ($patient1->prenom && $patient2->prenom && ($patient1->prenom != $patient2->prenom)) {
                    return false;
                }

                $patient1->loadIPP();
                if ($patient1->_IPP && $patient2->_IPP && ($patient1->_IPP != $patient2->_IPP)) {
                    return false;
                }
                break;
            default:
                return true;
        }

        return true;
    }

    /**
     * @param CConsultation $consultation
     *
     * @return CFHIRDataTypeCodeableConcept[]
     * @throws Exception
     */
    public function setServiceType(CConsultation $consultation): array
    {
        $categorie = $consultation->loadRefCategorie();

        return $this->addCodeableConcepts(
            $this->addCoding('urn:oid:' . CAppUI::conf('mb_oid'), $categorie->_id, $categorie->nom_categorie)
        );
    }

  /**
     * @param CConsultation $consultation
     *
     * @return array
     */
    public function setParticipants(CConsultation $consultation, ?bool $creation = false): array
    {
        /** @var CInteropActor $actor */
        $actor = $this->_receiver ? $this->_receiver : $this->_sender;

        $values = [
            "codeSystem"  => "urn:oid:2.16.840.1.113883.4.642.3.245",
            "code"        => "ADM",
            "displayName" => "admitter",
        ];

        if ($creation) {
            $status_patient      = 'accepted';
            $status_practitioner = 'needs-action';
        } else {
            $status_patient = $consultation->annule &&
            ($consultation->motif_annulation == 'by_patient' || $consultation->motif_annulation == 'not_arrived')
                ? 'declined' : 'accepted';

            $status_practitioner = $consultation->annule && $consultation->motif_annulation == 'other'
                ? 'declined' : 'accepted';
        }
        // todo attention les _tag_fhir n'existe pas sur un senderHttp lorsqu'on fait en local
        $praticien = $consultation->loadRefPraticien();
        $idex_prat = CIdSante400::getMatchFor($praticien, $actor->_tag_fhir ?? '');

        $patient  = $consultation->loadRefPatient();
        $idex_pat = CIdSante400::getMatchFor($patient, $actor->_tag_fhir ?? '');

        $function      = $praticien->loadRefFunction();
        $idex_function = CIdSante400::getMatchFor($function, $actor->_tag_fhir ?? '');

        $data = [
            // Ajout du praticien
            CFHIRDataTypeBackboneElement::build(
                [
                    'type'     => [
                        CFHIRDataTypeCodeableConcept::build(
                            $this->formatCodeableConcept($values)
                        ),
                    ],
                    'actor'    => CFHIRDataTypeReference::build(
                        [
                            'reference' => $idex_prat->_id ? "Practitioner/$idex_prat->id400" : "#" . $praticien->_guid,
                            'display'   => $praticien->_view,
                        ]
                    ),
                    'required' => 'required',
                    'status'   => $status_practitioner,
                ]
            ),
            // Ajout du rôle du praticien
            CFHIRDataTypeBackboneElement::build(
                [
                    'actor'    => CFHIRDataTypeReference::build(
                        [
                            'reference' => $idex_function->_id ? "PractitionerRole/$idex_function->id400" : "#" . $function->_guid,
                            'display'   => $function->_view,
                        ]
                    ),
                    'required' => 'required',
                    'status'   => 'needs-action',
                ]
            ),
            // Ajout du patient
            CFHIRDataTypeBackboneElement::build(
                [
                    'actor'    => CFHIRDataTypeReference::build(
                        [
                            "reference" => $idex_pat->_id ? "Patient/$idex_pat->id400" : "#" . $patient->_guid,
                            'display'   => $patient->_view,
                        ]
                    ),
                    'required' => 'required',
                    'status'   => $status_patient,
                ]
            ),
        ];

        return $data;
    }

    /**
     * @param CMediusers $mediusers
     *
     * @return CFHIRDataTypeCoding[]
     */
    public function setSpecialty(CMediusers $mediusers): array
    {
        $spec = $mediusers->loadRefOtherSpec();

        if (!$spec->libelle) {
            return [];
        }

        $subject_role = CMbArray::get(CDMPTools::$subjectRole, $mediusers->_user_type);
        $entry        = CInteropResources::loadEntryJV(
            CMbArray::get(CDMPValueSet::$JDV, "subjectRole"),
            $subject_role,
            CDMPValueSet::$type
        );

        if (CMbArray::get($entry, 'code') !== '10' && CMbArray::get($entry, 'code') !== '21') {
            return [];
        }

        $code  = $spec->code;
        $code  = substr($code, strpos($code, "/") + 1);
        $entry = CInteropResources::loadEntryJV(
            CMbArray::get(CDMPValueSet::$JDV, "subjectRole"),
            $code,
            CDMPValueSet::$type
        );

        $values = [
            'codeSystem'  => 'https://mos.esante.gouv.fr/NOS/TRE_R38-SpecialiteOrdinale/FHIR/TRE-R38-SpecialiteOrdinale',
            'code'        => CMbArray::get($entry, 'code'),
            'displayName' => CMbString::removeAccents(CMbArray::get($entry, 'displayName')),
        ];

        return [$this->addTypeCodeCoding($values)];
    }

    /**
     * Build type field on resource
     *
     * @param array $values values
     *
     * @return CFHIRDataTypeCoding
     */
    function addTypeCodeCoding($values)
    {
        return CFHIRDataTypeCoding::build(
            $this->formatCodeableConcept($values)
        );
    }

    /**
     * @param string $url
     * @param array  $data
     *
     * @return array
     */
    public function addExtensions(array $items): array
    {
        $extensions = [];
        foreach ($items as $item) {
            $extensions[] = $this->addExtension($item);
        }

        return $extensions;
    }

    /**
     * @param string $url
     * @param array  $data
     *
     * @return array
     */
    public function addExtension(array $item): CFHIRDataTypeExtension
    {
        return CFHIRDataTypeExtension::build($item);
    }

    /**
     * @param string $url
     * @param array  $data
     *
     * @return array
     */
    public function formatExtension(string $url, array $data): array
    {
        return array_merge(["url" => $url], $data);
    }

    /**
     * Perform a search query based on the current object data
     *
     * @param mixed $data Data to interact with
     *
     * @return CStoredObject
     * @throws CFHIRExceptionNotFound
     */
    public function interactionRead(?array $data): ?CStoredObject
    {
        // check as _id
        $_id = CMbArray::get($data, "_id");
        if (!$_id || !count($_id)) {
            throw new CFHIRExceptionNotFound();
        }
        $resource_id = $_id[0][1];

        // check load
        $object = $this->loadObjectFromContext($resource_id);

        // check version_id
        if ($this::ACCEPT_VERSION_ID && ($version_id = CMbArray::get($data, "version_id"))) {
            $user_log = new CUserLog();
            $user_log->load($version_id);
            $is_not_match = ($user_log->object_class != $object->_class) || ($user_log->object_id != $object->_id);
            if (!$user_log->_id || $is_not_match) {
                throw new CFHIRExceptionNotFound(
                    "Version #{$version_id} is not valid for resource '" . $this::RESOURCE_TYPE . "' #{$resource_id}"
                );
            }

            $object = $object->loadListByHistory($version_id);
        }

        // add serch_id
        $this->_search_id = $object->_id;

        return $this->outWithPerm($object);
    }

    /**
     * @param array       $data
     * @param string|null $offset
     *
     * @return string
     */
    protected function getLimit(array $data, ?string $offset = null): string
    {
        // limit
        $limit = isset($data["_count"]) ? (int)$data["_count"][0][1] : null;
        $limit = $limit ? min($limit, CFHIRResponse::SEARCH_MAX_ITEMS) : CFHIRResponse::SEARCH_MAX_ITEMS;

        if ($offset) {
            $limit = "$offset, $limit";
        }

        return $limit;
    }

    /**
     * @param array $data
     *
     * @return string|null
     */
    protected function getOffset(array $data): ?string
    {
        return isset($data["_offset"]) ? (int)$data["_offset"][0][1] : null;
    }

    /**
     * Perform a search query based on the current object data
     *
     * @param array       $data Data to handle
     * @param string|null $format
     *
     * @return CStoredObject[]
     * @throws CFHIRExceptionNotFound
     */
    public function interactionSearch(?array $data, ?string $format = null): array
    {
        $object = $this->getObject();
        // offset
        $offset = $this->getOffset($data);

        // limit
        $limit = $this->getLimit($data, $offset);

        // _id
        if (isset($data["_id"]) && count($data["_id"])) {
            $resource_id = $data["_id"][0][1];
            $object->load($resource_id);

            if ($object = $this->outWithPerm($object)) {
                return [
                    "list"     => [$object],
                    "total"    => 1,
                    "step"     => $limit,
                    "offset"   => $offset,
                    "paginate" => isset($data["_count"]),
                ];
            }

            throw new CFHIRExceptionNotFound("Could not find encounter #" . $resource_id);
        }

        // specific search
        if ($specificSearch = $this->specificSearch($data, $limit, $offset)) {
            [$list, $total] = $specificSearch;
        } else {
            $list  = $object->loadList(null, null, $limit);
            $total = $object->countList();
        }

        return [
            "list"     => $list,
            "total"    => $total,
            "step"     => $limit,
            "offset"   => $offset,
            "paginate" => isset($data["_count"]),
        ];
    }

    /**
     * @param array       $data
     * @param string      $limit
     *
     * @param string|null $offset
     *
     * @return array [list, total]
     */
    protected function specificSearch(array $data, string $limit, ?string $offset = null): array
    {
        return [];
    }

    /**
     * @param string $resource_id
     *
     * @return CStoredObject
     * @throws CFHIRExceptionNotFound
     * @throws Exception
     */
    private function loadObjectFromContext(string $resource_id): CStoredObject
    {
        $object = $this->getObject();

        // load by idex
        if ($this->_receiver) {
            $idex = CIdSante400::getMatch($object->_class, $this->_receiver->_tag_fhir, $resource_id);
            if (!$idex || !$idex->_id) {
                throw new CFHIRExceptionNotFound("Could not find " . $this::RESOURCE_TYPE . " #$resource_id");
            }
            $object = $idex->loadTargetObject();
        }

        // load by _id
        if ($this->_sender) {
            $object->load($resource_id);
        }

        // check object found
        if (!$object || !$object->_id) {
            throw new CFHIRExceptionNotFound("Could not find " . $this::RESOURCE_TYPE . " #$resource_id");
        }

        return $object;
    }

    /**
     * @return CStoredObject
     */
    public function getObject(): CStoredObject
    {
        $class = $this->getClass();

        return new $class();
    }

    /**
     * Get the associated MbClass
     *
     * @return string
     */
    public function getClass(): ?string
    {
        return null;
    }

    protected function outWithPerm(CStoredObject $object)
    {
        if (!$object || !$object->getPerm(PERM_READ)) {
            return false;
        }

        return $object;
    }

    /**
     * Map the resource from a CMbObject
     *
     * @param CMbObject $object The object with the data to get
     *
     * @return void
     */
    public function mapFrom(CMbObject $object): void
    {
        $this->id = new CFHIRDataTypeId($object->_id);

        $last_log   = $object->loadLastLog();
        $this->meta = CFHIRDataTypeMeta::build(
            [
                "versionId" => new CFHIRDataTypeId($last_log->_id),
                //"lastUpdated" => new CFHIRDataTypeInstant($last_log->date)
            ]
        );
        $this->addProfiles($this::PROFILE);

        // set light object
        $this->mapFromLight($object);
    }

    /**
     * Map the resource in mode light for a CMbObject
     *
     * @param CMbObject $object
     *
     * @return void
     */
    public function mapFromLight(CMbObject $object): void
    {
        if (!$this->id) {
            $this->id = new CFHIRDataTypeId($object->_guid);
        }
    }

    /**
     * Build contained field on resource
     *
     * @param CMbObject     $object object
     * @param CFHIRResource $resource
     *
     * @return CFHIRDataTypeContained[]
     * @throws CFHIRExceptionNotFound
     * @throws Exception
     */
    protected function addContained(CMbObject $object, CFHIRResource $resource): array
    {
        if (!$this->contained) {
            $this->contained = [];
        }

        //$result = $this->buildContained($object);
        $resource->mapFromLight($object);

        return array_merge($this->contained, [new CFHIRDataTypeContained($resource)]);
    }

    /**
     * Build data array
     *
     * @param array         $array
     * @param CMbObject     $object object
     *
     * @param CFHIRResource $resource
     *
     * @return array
     * @throws CFHIRExceptionNotFound
     */
    function buildContained(CMbObject $object)
    {
        $data = [];
        switch ($object->_class) {
            case "CGroups":
                /** @var CGroups $object */
                $data = [
                    'resourceType' => 'Organization',
                    "id"           => new CFHIRDataTypeString($object->_guid),
                    "identifier"   => CFHIRDataTypeIdentifier::build(
                        [
                            "system" => new CFHIRDataTypeString("urn:ietf:rfc:3986"),
                            "value"  => new CFHIRDataTypeString(CAppUI::conf("mb_oid")),
                        ]
                    ),
                    "name"         => new CFHIRDataTypeString($object->_name),
                    "address"      => $this->formatAddress($object->adresse, $object->ville, $object->cp),
                ];
                break;
            default:
        }

        return $data;
    }

    /**
     * @param string $value
     *
     * @return string
     */
    protected function formatGender(string $value): string
    {
        switch ($value) {
            case 'm':
                $gender = 'male';
                break;

            case 'f':
                $gender = 'female';
                break;

            default:
                $gender = 'unknown';
        }

        return $gender;
    }

    /**
     * @param string           $resource_class
     * @param CStoredObject    $object
     * @param CIdSante400|null $idex
     *
     * @return string
     */
    protected function getIdentifier(string $resource_class, CStoredObject $object, ?CIdSante400 $idex = null): string
    {
        /** @var CFHIRResource $resource_class */
        if ($this->interaction instanceof CFHIRInteractionCreate) {
            return $idex && $idex->_id ? $resource_class::RESOURCE_TYPE . "/$idex->id400" : "#" . $object->_guid;
        }

        return $resource_class::RESOURCE_TYPE . "/" . ($idex && $idex->_id ? $idex->id400 : $object->_guid);
    }

    /**
     * Format address fhir type
     *
     * @param string $address  address
     * @param string $city     city
     * @param string $zip_code zip code
     * @param string $country  country
     * @param string $use      use
     * @param string $type     type
     *
     * @return CFHIRDataTypeAddress
     */
    function formatAddress($address = null, $city = null, $zip_code = null, $country = null, $use = null, $type = null)
    {
        return CFHIRDataTypeAddress::build(
            [
                "use"        => new CFHIRDataTypeCode($use),
                "type"       => new CFHIRDataTypeCode($type),
                "text"       => new CFHIRDataTypeString("$address $zip_code $country"),
                "line"       => new CFHIRDataTypeString($address),
                "city"       => new CFHIRDataTypeString($city),
                "postalCode" => new CFHIRDataTypeString($zip_code),
                "country"    => new CFHIRDataTypeString($country),
            ]
        );
    }
}
