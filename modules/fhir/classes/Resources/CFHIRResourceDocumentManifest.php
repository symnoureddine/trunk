<?php
/**
 * @package Mediboard\fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir\Resources;

use DOMNode;
use Ox\Core\CAppUI;
use Ox\Core\CMbObject;
use Ox\Core\CStoredObject;
use Ox\Interop\Eai\CDomain;
use Ox\Interop\Eai\CInteropActor;
use Ox\Interop\Eai\CMbOID;
use Ox\Interop\Fhir\CFHIR;
use Ox\Interop\Fhir\CFHIRXPath;
use Ox\Interop\Fhir\Controllers\CFHIRController;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeCode;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeId;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeInstant;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeString;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeUri;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeBackboneElement;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeCodeableConcept;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeIdentifier;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeReference;
use Ox\Interop\Fhir\Event\CFHIREvent;
use Ox\Interop\Fhir\Exception\CFHIRException;
use Ox\Interop\Fhir\Exception\CFHIRExceptionBadRequest;
use Ox\Interop\Fhir\Exception\CFHIRExceptionNotFound;
use Ox\Mediboard\Cabinet\CConsultation;
use Ox\Mediboard\CompteRendu\CCompteRendu;
use Ox\Mediboard\Files\CDocumentItem;
use Ox\Mediboard\Files\CDocumentManifest;
use Ox\Mediboard\Files\CDocumentReference;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\PlanningOp\COperation;
use Ox\Mediboard\PlanningOp\CSejour;
use Ox\Mediboard\Sante400\CIdSante400;

/**
 * Description
 */
class CFHIRResourceDocumentManifest extends CFHIRResource
{
    /** @var string  */
    public const RESOURCE_TYPE = 'DocumentManifest';

    /** @var string  */
    public const SYSTEM = 'urn:ietf:rfc:3986';

    /** @var CFHIRDataTypeId[] */
    public $id;

    /** @var array */
    public $contained = [];

    /** @var CFHIRDataTypeIdentifier[] */
    public $masterIdentifier;

    /** @var CFHIRDataTypeIdentifier[] */
    public $identifier;

    /** @var CFHIRDataTypeCode */
    public $status;

    /** @var CFHIRDataTypeCodeableConcept */
    public $type;

    /** @var CFHIRDataTypeReference */
    public $subject;

    /** @var CFHIRDataTypeInstant[] */
    public $created;

    /** @var CFHIRDataTypeReference */
    public $author;

    /** @var CFHIRDataTypeReference */
    public $recipient;

    /** @var CFHIRDataTypeUri */
    public $source;

    /** @var CFHIRDataTypeString */
    public $description;

    /** @var CFHIRDataTypeReference[] */
    public $content;

    /** @var CFHIRDataTypeBackboneElement */
    public $related;

    /** @var string */
    public $_id_submission_lot;

    /** @var string */
    public $_id_submission_lot_to_document;

    /** @var string */
    public $_full_url_document_reference;

    /**
     * Mapping DocumentManifest resource to create CDocumentManifest
     *
     * @param CFHIRXPath    $xpath             xpath
     * @param DOMNode       $node_doc_manifest node doc manifest
     * @param CFHIRResource $resource          resource
     * @param string        $type              type
     *
     * @return CDocumentManifest
     * @throws CFHIRException
     */
    public static function mapping(CFHIRXPath $xpath, DOMNode $node_doc_manifest, $resource, $type = "XDS")
    {
        if (!$repositoryUniqueIDExternal = $xpath->getAttributeValue(
            "fhir:masterIdentifier/fhir:value",
            $node_doc_manifest
        )) {
            throw new CFHIRException(
                "Impossible to retrieve DocumentManifest.masterIdentifier.value. This value must be present."
            );
        }

        $doc_manifest                             = new CDocumentManifest();
        $doc_manifest->repositoryUniqueIDExternal = $repositoryUniqueIDExternal;
        $doc_manifest->type                       = $type;
        $doc_manifest->setActor($resource->_sender);
        $doc_manifest->initiator = "server";
        $doc_manifest->loadMatchingObject();
        if ($doc_manifest->_id) {
            throw new CFHIRException(
                "A DocumentManifest with repositoryUniqueID '$doc_manifest->repositoryUniqueID' and version '$doc_manifest->version' always exists."
            );
        }

        return $doc_manifest;
    }

    public static function getManifestFromXML($dom)
    {
        $xpath = new CFHIRXPath($dom);

        $documents_manifest = [];
        switch ($dom->documentElement->nodeName) {
            case "DocumentManifest":
                $doc_manifest_node                                         = $dom->documentElement;
                $document_manifest                                         = new CDocumentManifest();
                $document_manifest                                         = self::mapDocumentManifestFromXML(
                    $document_manifest,
                    $doc_manifest_node
                );
                $documents_manifest[$document_manifest->_fhir_resource_id] = $document_manifest;
                break;
            case "Bundle":
                $entries = $xpath->query("//fhir:entry");
                foreach ($entries as $_entry) {
                    $doc_manifest_node                                         = $xpath->queryUniqueNode(
                        "fhir:resource/fhir:DocumentManifest",
                        $_entry
                    );
                    $document_manifest                                         = new CDocumentManifest();
                    $document_manifest                                         = self::mapDocumentManifestFromXML(
                        $document_manifest,
                        $doc_manifest_node
                    );
                    $documents_manifest[$document_manifest->_fhir_resource_id] = $document_manifest;
                }
                break;
            default:
        }

        return $documents_manifest;
    }

    /**
     * @inheritdoc
     *
     * @param CDocumentManifest $object
     * @param DOMNode           $node
     *
     * @throws \Exception
     */
    public static function mapDocumentManifestFromXML(CDocumentManifest $object, DOMNode $node)
    {
        $xpath = new CFHIRXPath($node->ownerDocument);

        $object->_fhir_resource_id  = $xpath->getAttributeValue("fhir:id", $node);
        $object->repositoryUniqueID = $xpath->getAttributeValue("fhir:masterIdentifier/fhir:value", $node);
        $object->status             = $xpath->getAttributeValue("fhir:status", $node);

        $pReferences = $xpath->query("fhir:content/fhir:pReference", $node);

        $references = [];
        foreach ($pReferences as $_pReference) {
            $references[] = $xpath->getAttributeValue("fhir:reference", $_pReference);
        }

        $object->_ref_documents_reference = $references;

        return $object;
    }

    /**
     * @inheritdoc
     */
    public function getClass(): ?string
    {
        return CDocumentManifest::class;
    }

    /**
     * @param array       $data Data to handle
     *
     * @param string      $limit
     * @param string|null $offset
     *
     * @return array
     * @throws CFHIRExceptionBadRequest
     * @throws \Exception
     */
    public function specificSearch(array $data, string $limit, ?string $offset = null): array
    {
        /** @var CDocumentManifest $object */
        $object = $this->getObject();
        $this->getWhere($data, $object, $where, $ljoin);

        $list  = $object->loadList($where, "document_manifest_id", $limit, null, $ljoin);
        $total = $object->countList($where, null, $ljoin);

        return [$list, $total];
    }

    /**
     * Makes a WHERE clause from data
     *
     * @param array             $data             Input data
     * @param CDocumentManifest $documentManifest Document manifest
     * @param array             $where            WHERE clause
     * @param array             $ljoin            LEFT JOIN clause
     *
     * @return array
     * @throws CFHIRExceptionBadRequest
     */
    private function getWhere($data, CDocumentManifest $documentManifest, &$where, &$ljoin)
    {
        $ds = $documentManifest->getDS();

        // patient
        $this->addWhereFromQuery($ds, $data, "patient", $where, "patient_reference");

        // patient.identifier
        if (isset($data["patient.identifier"]) && count($data["patient.identifier"])) {
            $identifier = $data["patient.identifier"];
            [$system, $value] = explode("|", $identifier[0][1]);

            $system = str_replace("urn:oid:", "", $system);

            $domain      = new CDomain();
            $domain->OID = $system;
            $domain->loadMatchingObject();

            if (!$domain->_id) {
                throw new CFHIRExceptionBadRequest("sourceIdentifier Assigning Authority not found");
            }

            $idex = CIdSante400::getMatch("CPatient", $domain->tag, $value);

            $where["patient_id"] = " = '$idex->object_id'";
        }

        // author.given
        $this->addWhereFromQuery($ds, $data, "author.given", $where, "author_given");

        // author.familty
        $this->addWhereFromQuery($ds, $data, "author.familty", $where, "author_familty");

        // created
        $this->addWhereFromQuery($ds, $data, "created", $where, "created_datetime");

        $where["initiator"] = " = 'server' ";

        // status
        $this->addWhereFromQuery($ds, $data, "status", $where, "status");
    }

    /**
     * @inheritdoc
     */
    public function build(CMbObject $object, CFHIREvent $event)
    {
        parent::build($object, $event);

        $document_reference = new CDocumentReference();
        $document_reference->setObject($object);
        $document_reference->setActor($event->_receiver);
        $document_reference->loadMatchingObject("document_reference_id DESC");

        // Création ou modification d'un document => on crée toujours un CDocumentManifest
        // car chaque requête = 1 nouveau lot de soumission
        $document_manifest = $this->createDocumentManifest($object, $event->_receiver, "client");

        // Création du CDocumentReference
        $this->createDocumentReference(
            $object,
            $event,
            $document_manifest,
            $document_reference->_id ? $document_reference->_id : null
        );

        $this->id = CFHIR::generateUUID();

        // Ajout des <contained>
        $author          = $object->loadRefAuthor();
        $this->contained = $this->addContained($author, new CFHIRResourcePractitioner());

        // XDS : SubmissionSet.uniqueID
        $this->masterIdentifier = $this->addMasterIdentifier(
            $document_manifest->repositoryUniqueID,
            self::SYSTEM
        );

        // XDS : SubmissionSet.entryUUID
        $this->identifier = $this->addIdentifier("urn:uuid:" . CFHIR::generateUUID());

        // XDS : SubmissionSet status
        $this->status = $this->addStatus($object->annule ? "superseded" : "current");

        // XDS : SubmissionSet contentTypeCode
        //$this->type = $this->addTypeCodeableConcept($object);

        $xds_value_set = [
            "codeSystem"  => "http://snomed.info/sct",
            "code"        => "22232009",
            "displayName" => "Hospital",
        ];
        //$this->type = $this->addTypeCodeCoding($xds_value_set->getTypeCode(CMbArray::get($type, 1)));
        $this->type = $this->addTypeCodeCoding($xds_value_set);

        $object->loadTargetObject();
        $patient = new CPatient();
        if ($object->_ref_object instanceof CPatient) {
            $patient = $object->_ref_object;
        } elseif (
            $object->_ref_object instanceof CSejour || $object->_ref_object instanceof CConsultation
            || $object->_ref_object instanceof COperation
        ) {
            $patient = $object->_ref_object->loadRefPatient();
        }

        // XDS : SubmissionSet.patientId
        $this->subject = $this->addSubject($patient);

        // XDS : SubmissionSet.submissionTime
        $this->created = $this->addCreated(
            $object instanceof CCompteRendu ? $object->creation_date : $object->file_date
        );

        // XDS : SubmissionSet.author
        $this->author = $this->addAuthor($author);

        // XDS : SubmissionSet.sourceId
        $this->source = $this->addSourceURI("urn:oid:" . CAppUI::conf("mb_oid"));

        // XDS : SubmissionSet.title
        $this->description = $this->addDescription($object instanceof CCompteRendu ? $object->nom : $object->file_name);

        // XDS : SubmissionSet DocumentEntry(s)
        $this->_full_url_document_reference = "urn:uuid:" . CFHIR::generateUUID();
        $this->content[]                    = $this->addContent($this->_full_url_document_reference);
    }

    /**
     * Create DocumentManifest
     *
     * @param CDocumentItem $object    object
     * @param CInteropActor $actor     actor
     * @param string        $initiator initiator
     *
     * @return CDocumentManifest
     * @throws CFHIRException
     */
    public function createDocumentManifest(
        CDocumentItem $object,
        CInteropActor $actor,
        string $initiator = "client"
    ): CDocumentManifest {
        $document_manifest                     = new CDocumentManifest();
        $document_manifest->repositoryUniqueID = "urn:oid:" . CMbOID::getOIDFromClass($object) . "." . hexdec(uniqid());
        $document_manifest->treated_datetime   = "now";
        $document_manifest->type               = "FHIR";
        $document_manifest->initiator          = $initiator;
        $document_manifest->patient_id         = $object->object_class === "CPatient"
            ? $object->object_id : $object->loadTargetObject(
            )->loadRefPatient()->_id;
        $document_manifest->setActor($actor);

        if ($msg = $document_manifest->store()) {
            throw new CFHIRException("Impossible to store DocumentManifest : $msg");
        }

        return $document_manifest;
    }

    /**
     * Create DocumentReference
     *
     * @param CDocumentItem      $object            object
     * @param CFHIREvent         $event             event
     * @param CDocumentManifest  $document_manifest document manifest
     * @param CDocumentReference $parent_id         id of document reference parent
     *
     * @throws CFHIRException
     */
    public function createDocumentReference(
        CDocumentItem $object,
        CFHIREvent $event,
        CDocumentManifest $document_manifest,
        $parent_id = null
    ) {
        $document_reference = new CDocumentReference();

        $version                                  = $object->_version;
        $document_reference->version              = $version;
        $document_reference->status               = "current";
        $document_reference->security_label       = "Normal";
        $document_reference->initiator            = "client";
        $document_reference->uniqueID             = "urn:uuid:" . CMbOID::getOIDFromClass(
                $object
            ) . "." . $object->_id . "." . $version;
        $document_reference->document_manifest_id = $document_manifest->_id;
        $document_reference->parent_id            = $parent_id;
        $document_reference->setActor($event->_receiver);
        $document_reference->setObject($object);

        if ($msg = $document_reference->store()) {
            throw new CFHIRException("Impossible to store DocumentReference : $msg");
        }
    }

    /**
     * @inheritdoc
     */
    public function mapFrom(CMbObject $object): void
    {
        /** @var CDocumentManifest $document_manifest */
        $document_manifest = $object;

        $this->id[] = new CFHIRDataTypeId($document_manifest->_id);

        $this->masterIdentifier = $this->addMasterIdentifier(
            $document_manifest->repositoryUniqueID,
            "urn:oid:" . CAppUI::conf("mb_oid")
        );

        $this->status = new CFHIRDataTypeCode("current");

        $url_patient   = CFHIRController::getUrl(
            "fhir_read",
            [
                'resource'    => "Patient",
                'resource_id' => $document_manifest->patient_id,
            ]
        );
        $this->subject = CFHIRDataTypeReference::build(
            [
                "reference" => new CFHIRDataTypeString($url_patient),
            ]
        );

        $docs_reference = $document_manifest->loadRefsDocumentsReferences();
        foreach ($docs_reference as $_doc_reference) {
            $this->content[] = CFHIRDataTypeBackboneElement::build(
                [
                    "pReference" => CFHIRDataTypeReference::build(
                        [
                            "reference" => new CFHIRDataTypeString("DocumentReference/" . $_doc_reference->_id),
                        ]
                    ),
                ]
            );
        }
    }
}
