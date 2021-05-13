<?php
/**
 * @package Mediboard\Fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir\Resources;

use DOMDocument;
use DOMNode;
use Exception;
use Ox\Core\CMbDT;
use Ox\Core\CMbObject;
use Ox\Core\CStoredObject;
use Ox\Core\CView;
use Ox\Interop\Eai\CDomain;
use Ox\Interop\Eai\CMbOID;
use Ox\Interop\Fhir\CFHIR;
use Ox\Interop\Fhir\CFHIRXPath;
use Ox\Interop\Fhir\Controllers\CFHIRController;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeBase64Binary;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeBoolean;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeCode;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeId;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeInstant;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeString;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeAttachment;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeBackboneElement;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeCodeableConcept;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeCoding;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeIdentifier;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeReference;
use Ox\Interop\Fhir\Event\CFHIREvent;
use Ox\Interop\Fhir\Exception\CFHIRException;
use Ox\Interop\Fhir\Exception\CFHIRExceptionBadRequest;
use Ox\Interop\Fhir\Exception\CFHIRExceptionNotFound;
use Ox\Interop\Xds\Structure\CXDSValueSet;
use Ox\Mediboard\Cabinet\CConsultation;
use Ox\Mediboard\CompteRendu\CCompteRendu;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Files\CDocumentItem;
use Ox\Mediboard\Files\CDocumentManifest;
use Ox\Mediboard\Files\CDocumentReference;
use Ox\Mediboard\Files\CFile;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\PlanningOp\COperation;
use Ox\Mediboard\PlanningOp\CSejour;
use Ox\Mediboard\Sante400\CIdSante400;

/**
 * FIHR document reference resource
 */
class CFHIRResourceDocumentReference extends CFHIRResource
{
    /** @var string */
    public const RESOURCE_TYPE = 'DocumentReference';

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

    /** @var CFHIRDataTypeCode */
    public $docStatus;

    /** @var CFHIRDataTypeCodeableConcept */
    public $type;

    /** @var CFHIRDataTypeCoding */
    public $class;

    /** @var CFHIRDataTypeReference */
    public $subject;

    /** @var CFHIRDataTypeInstant[] */
    public $created;

    /** @var CFHIRDataTypeInstant[] */
    public $indexed;

    /** @var CFHIRDataTypeReference */
    public $author;

    /** @var CFHIRDataTypeReference */
    public $authenticator;

    /** @var CFHIRDataTypeString */
    public $description;

    /** @var CFHIRDataTypeCodeableConcept */
    public $securityLabel;

    /** @var CFHIRDataTypeBackboneElement */
    public $content;

    /** @var CFHIRDataTypeBackboneElement */
    public $context;

    /** @var CFHIRDataTypeBackboneElement */
    public $relatesTo;

    /**
     * Return the mime type
     *
     * @param String $type type
     *
     * @return null|string
     */
    public static function getFileType($type)
    {
        $file_type = null;

        $type = strtolower($type);

        switch ($type) {
            case "image/jpeg":
            case "image/jpg":
                $file_type = ".jpeg";
                break;
            case "image/png":
                $file_type = ".png";
                break;
            case "application/rtf":
            case "text/rtf":
                $file_type = ".rtf";
                break;
            case "image/tiff":
                $file_type = ".tiff";
                break;
            case "application/pdf":
                $file_type = ".pdf";
                break;
            case "text/plain":
                $file_type = ".txt";
                break;
            default:
                break;
        }

        return $file_type;
    }

    /**
     * Get document reference in response
     *
     * @param DOMDocument $dom dom
     *
     * @return array
     * @throws Exception
     */
    public static function getDocumentReferenceFromXML(DOMDocument $dom)
    {
        $xpath = new CFHIRXPath($dom);

        $files = [];
        switch ($dom->documentElement->nodeName) {
            case "DocumentReference":
                $file                              = $dom->documentElement;
                $mbFile                            = new CFile();
                $mbFile                            = self::mapDocumentReferenceFromXML($mbFile, $file);
                $files[$mbFile->_fhir_resource_id] = $mbFile;
                break;
            case "Bundle":
                $entries = $xpath->query("//fhir:entry");
                foreach ($entries as $_entry) {
                    $file = $xpath->queryUniqueNode("fhir:resource/fhir:DocumentReference", $_entry);

                    $mbFile                            = new CFile();
                    $mbFile                            = self::mapDocumentReferenceFromXML($mbFile, $file);
                    $files[$mbFile->_fhir_resource_id] = $mbFile;
                }
                break;
            default:
        }

        return $files;
    }

    /**
     * @inheritdoc
     *
     * @param CFile $object
     *
     * @throws \Exception
     */
    public static function mapDocumentReferenceFromXML(CMbObject $object, DOMNode $node)
    {
        $xpath = new CFHIRXPath($node->ownerDocument);

        $object->_fhir_resource_id = $xpath->getAttributeValue("fhir:id", $node);
        $object->annule            = $xpath->getAttributeValue("fhir:status", $node) == "current" ? 0 : 1;

        // Si on a pas la balise <created>, on prend la balise <indexed>
        $created           = $xpath->getAttributeValue("fhir:created", $node);
        $object->file_date = $created ? $created : CMbDT::dateTime($xpath->getAttributeValue("fhir:indexed", $node));

        // TODO : On commente pour le connectathon. A prendre en compte plusieurs authors dans une réponse
        /*$practitionner_value = $xpath->getAttributeValue("fhir:author/fhir:reference", $node);
        $practitionner_id    = preg_replace("#Practitioner/#", "", $practitionner_value);

        if ($practitionner_id) {
          $practitionner = new CMediusers();
          $practitionner->load($practitionner_id);

          if ($practitionner->_id) {
            $object->author_id   = $practitionner_id;
            $object->_ref_author = $practitionner;
          }
        }*/

        // Récupération contenu (le contenu est soit dans le message, soit il faut aller le requêter)
        $object->file_name = $xpath->getAttributeValue("fhir:content/fhir:attachment/fhir:title", $node);
        $object->file_type = $xpath->getAttributeValue("fhir:content/fhir:attachment/fhir:contentType", $node);
        $url               = $xpath->getAttributeValue("fhir:content/fhir:attachment/fhir:url", $node);

        if ($url) {
            // TODO XDS TOOLKIT : xds toolkit retourne directement le contenu et non une resource binary
            // Si la chaine n'est pas en base64, on l'encode en base64 car pour l'affichage il faut que ça soit encodé
            //$object->_binary_content = !CFHIRResource::validBase64($binary_content) ? base64_encode($binary_content) : $binary_content;

            $content = null;
            // Soit le contenu du fichier est dans la trame (<Binary>), soit il faut une requête GET pour récupérer le contenu
            $entry_node = $xpath->getNode("//fhir:fullUrl[@value='$url']/ancestor::*[position()=1]");

            // Récupération du contenu dans la trame
            if ($entry_node && $entry_node->nodeName == "entry") {
                $content_value = $xpath->getAttributeValue("fhir:resource/fhir:Binary/fhir:content", $entry_node);

                if ($content_value) {
                    $content = base64_decode($content_value);
                }
            } // Récupération du binary en lançant une autre requête
            else {
                $binary_resource             = CFHIRResource::getExterneResource($url, "binary");
                $document_reference_resource = new self();
                $content                     = $document_reference_resource->getContentFromBinaryResource(
                    $binary_resource,
                    $url
                );

                // Si la chaine n'est pas en base64, on l'encode en base64 car pour l'affichage il faut que ça soit encodé
                $content = !CFHIRResource::validBase64($content) ? base64_encode($content) : $content;
            }

            $object->_binary_content = $content;
        } else {
            $object->_binary_content = $xpath->getAttributeValue("fhir:content/fhir:attachment/fhir:data", $node);
        }

        CView::setSession($object->_fhir_resource_id, $object->_binary_content);
        CView::setSession($object->_fhir_resource_id . "_file_type", $object->file_type);

        // Récupération contexte (type sejour)
        $encounter_value = $xpath->getAttributeValue("fhir:context/fhir:encounter/fhir:reference", $node);
        $encounter_id    = preg_replace("#Encounter/#", "", $encounter_value);

        if ($encounter_id) {
            $sejour = new CSejour();
            $sejour->load($encounter_id);

            if ($sejour->_id) {
                $object->object_id    = $sejour->_id;
                $object->object_class = $sejour->_class;
                $sejour->loadRefPatient();
                $object->_ref_object = $sejour;
            }
        }

        return $object;
    }

    /**
     * @inheritdoc
     */
    public function getClass(): ?string
    {
        return CDocumentReference::class;
    }


    /**
     * @param array       $data
     * @param string      $limit
     * @param string|null $offset
     *
     * @return array
     * @throws CFHIRExceptionBadRequest
     * @throws Exception
     */
    public function specificSearch(array $data, string $limit, ?string $offset = null): array
    {
        $ljoin = [
            "document_manifest" => "`document_reference`.`document_manifest_id` = `document_manifest`.`document_manifest_id`"
        ];
        /** @var CDocumentReference $object */
        $object = $this->getObject();
        $this->getWhere($data, $object, $where, $ljoin);

        $list  = $object->loadList($where, null, $limit, null, $ljoin);
        $total = $object->countList($where, null, $ljoin);

        return [$list, $total];
    }

    /**
     * Makes a WHERE clause from data
     *
     * @param array              $data          Input data
     * @param CDocumentReference $doc_reference Document manifest
     * @param array              $where         WHERE clause
     * @param array              $ljoin         LEFT JOIN clause
     *
     * @return array
     * @throws CFHIRExceptionBadRequest
     */
    private function getWhere($data, CMbObject $doc_reference, &$where, &$ljoin)
    {
        $ds = $doc_reference->getDS();

        // patient
        if (isset($data["patient"]) && count($data["patient"])) {
            $where["document_manifest.patient_reference"] = " = '" . $data["patient"][0][1] . "'";
        }

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

            $idex                                  = CIdSante400::getMatch("CPatient", $domain->tag, $value);
            $where["document_manifest.patient_id"] = " = '{$idex->object_id}'";
        }

        // status
        if (isset($data["status"]) && count($data["status"])) {
            $query_item                         = $data["status"][0];
            $where["document_reference.status"] = $ds->prepare("$query_item[0] ?", $query_item[1]);
        }

        $where["document_reference.initiator"] = " = 'server' ";
    }

    /**
     * @param array  $data   data
     * @param string $format format
     *
     * @return CDocumentReference
     * @throws CFHIRException
     */
    public function interactionCreate(?array $data, ?string $format): CStoredObject
    {
        $dom = CFHIRResource::transformRequestInXML($data, $format);
        if (!$dom) {
            throw new CFHIRException("Impossible to retrieve data");
        }

        return $this->handleCreate($dom);
    }

    /**
     * Handle create
     *
     * @param DOMDocument $dom dom
     *
     * @return CDocumentReference[]
     * @throws CFHIRException
     */
    public function handleCreate(DOMDocument $dom): array
    {
        $xpath = new CFHIRXPath($dom);

        /*
          Dans le cas d'une création :
          1/ Note : le serveur ne doit pas contenir compte des id d'un DocumentReference et du DocumentManifest
          2/ identifier le patient et vérifier qu'il existe bien, où que l'on peut le récupérer pour le créer
          3/ il doit vérifier qu'il peut récupérer le Binary
          4/ rechercher la cible Encounter de la ressource DocumentReference
          5/ créer le CFile
          6/ créer le DocumentManifest
          7/ créer le DocumentReference sur la cible ou le patient le cas échéant
          8/ Retourner un CFHIRResourceBundle.type = transaction-response
        */

        // Récupération du DocumentManifest - uniquement dans le cas de IHE
        $nodes_document_manifest = $xpath->query(
            "fhir:entry/fhir:resource/fhir:DocumentManifest",
            $dom->documentElement
        );
        if ($nodes_document_manifest->length == 0 || $nodes_document_manifest->length > 1) {
            throw new CFHIRException(
                "Node DocumentManifest not found or node DocumentManifest are too many"
                . "$nodes_document_manifest->length)"
            );
        }

        $node_document_manifest = $nodes_document_manifest->item(0);
        $doc_manifest           = CFHIRResourceDocumentManifest::mapping(
            $xpath,
            $node_document_manifest,
            $this,
            "FHIR"
        );

        // Récupération des DocumentReference
        $nodes_doc_reference = $xpath->query("fhir:entry/fhir:resource/fhir:DocumentReference", $dom->documentElement);
        $docs_reference      = [];
        foreach ($nodes_doc_reference as $_node_doc_reference) {
            $doc_reference = new CDocumentReference();
            $doc_reference->setActor($this->_sender);

            // Récupération du patient par l'URL
            $patient = $this->getSubject($xpath, $_node_doc_reference);

            // Récupération de la cible Encounter (CSejour)
            // $sejour = $this->getEncounter($xpath, $_node_doc_reference);

            // Pour le moment on rattache le document au patient sur le serveur
            $file = $this->getAttachment($xpath, $_node_doc_reference, $patient);

            $doc_manifest->repositoryUniqueID = "urn:oid:" . CMbOID::getOIDFromClass($patient) . "." . hexdec(uniqid());
            $doc_manifest->status             = $xpath->getAttributeValue("fhir:status", $node_document_manifest);
            $doc_manifest->created_datetime   = $xpath->getAttributeValue("fhir:created", $node_document_manifest);
            $doc_manifest->patient_id         = $patient->_id;
            if (isset($patient->_ref_url)) {
                $doc_manifest->patient_reference = $patient->_ref_url;
            }


            if (!$doc_manifest->_id) {
                $doc_manifest->treated_datetime = "now";
                $doc_manifest->store();
            }

            // Est-ce un remplacement ?
            $relatesTo = $xpath->query("fhir:relatesTo", $_node_doc_reference);

            CFHIRResourceDocumentReference::mapping($xpath, $_node_doc_reference, $doc_manifest, $doc_reference, $file);

            $docs_reference[] = [
                "DocumentManifest"  => $doc_manifest,
                "DocumentReference" => $doc_reference,
                "Binary"            => $doc_reference,
            ];
        }

        return $docs_reference;
    }

    /**
     * Mapping DocumentReference resource to create CDocumentReference
     *
     * @param CFHIRXPath         $xpath              xpath
     * @param DOMNode            $node_doc_reference node doc reference
     * @param CDocumentManifest  $documentManifest   document manifest
     * @param CDocumentReference $documentReference  document reference
     * @param CFile              $file               file
     *
     * @return void
     * @throws CFHIRException
     *
     */
    public static function mapping(
        CFHIRXPath $xpath,
        DOMNode $node_doc_reference,
        CDocumentManifest $documentManifest,
        CDocumentReference $documentReference,
        CFile $file
    ) {
        // Hash
        $documentReference->hash = $xpath->getAttributeValue(
            "fhir:content/fhir:attachment/fhir:hash",
            $node_doc_reference
        );
        // Size
        $documentReference->size = $xpath->getAttributeValue(
            "fhir:content/fhir:attachment/fhir:size",
            $node_doc_reference
        );
        if (!$documentReference->size) {
            $documentReference->size = $file->doc_size;
        } else {
            if ($documentReference->size != $file->doc_size) {
                throw new CFHIRException("Size of DocumentReference is differente of size in registry");
            }
        }
        // Status
        $documentReference->status = $xpath->getAttributeValue("fhir:status", $node_doc_reference);
        // SecurityLabel
        $documentReference->security_label = $xpath->getAttributeValue(
            "fhir:securityLabel/fhir:coding/fhir:display",
            $node_doc_reference
        );

        $documentReference->setObject($file);
        // CFile : version = 1
        $version                                 = 1;
        $documentReference->uniqueID             = "urn:uuid:" . CMbOID::getOIDFromClass(
                $file
            ) . "." . $file->_id . "." . $version;
        $documentReference->document_manifest_id = $documentManifest->_id;
        $documentReference->initiator            = "server";
        if ($msg = $documentReference->store()) {
            throw new CFHIRException("Impossible to store metadata of DocumentReference : $msg");
        }
    }

    /**
     * @inheritdoc
     */
    public function mapFrom(CMbObject $object): void
    {
        /** @var CDocumentReference $doc_reference */
        $doc_reference = $object;

        $this->id[] = new CFHIRDataTypeId($doc_reference->_id);

        $file         = $doc_reference->loadRefObject();
        $this->status = new CFHIRDataTypeBoolean($file->annule ? "superseded" : "current");
        $this->type   = CFHIRDataTypeCodeableConcept::build(
            [
                "coding" => CFHIRDataTypeCoding::build(
                    [
                        "system"  => new CFHIRDataTypeString("http://loinc.org"),
                        "code"    => new CFHIRDataTypeString("34133-9"),
                        "display" => new CFHIRDataTypeString("Summary of Episode Note"),

                    ]
                ),
                "text"   => "Summary of Episode Note",
            ]
        );

        $this->masterIdentifier = $this->addMasterIdentifier($doc_reference->uniqueID, "urn:ietf:rfc:3986");

        // TODO : DSTU 4 => y'a plus "created" et "indexed" y'a juste "date"
        $this->created = new CFHIRDataTypeInstant($file instanceof CFile ? $file->file_date : $file->creation_date);
        $this->indexed = new CFHIRDataTypeInstant($file instanceof CFile ? $file->file_date : $file->creation_date);

        $author       = $file->loadRefAuthor();
        $this->author = CFHIRDataTypeReference::build(
            [
                "reference" => "Practitioner/$author->_id",
            ]
        );

        $this->description = new CFHIRDataTypeString($file instanceof CFile ? $file->file_name : $file->nom);

        $url_document_reference = CFHIRController::getUrl(
            "fhir_read",
            [
                'resource'    => "Binary",
                'resource_id' => $doc_reference->_id,
            ]
        );

        $this->content = CFHIRDataTypeBackboneElement::build(
            [
                "attachment" => CFHIRDataTypeAttachment::build(
                    [
                        "contentType" => new CFHIRDataTypeCode($file->file_type),
                        //"data"        => new CFHIRDataTypeBase64Binary(base64_encode($file->getBinaryContent())),
                        "url"         => new CFHIRDataTypeBase64Binary($url_document_reference),
                        "title"       => new CFHIRDataTypeString(
                            $file instanceof CFile ? $file->file_name : $file->nom
                        ),
                    ]
                ),
            ]
        );

        $context_data = [];

        $object = $file->loadTargetObject();
        if ($object instanceof CSejour) {
            $url_encounter = CFHIRController::getUrl(
                "fhir_read",
                [
                    'resource'    => "Encounter",
                    'resource_id' => $object->_id,
                ]
            );

            $context_data["encounter"] = CFHIRDataTypeReference::build(
                [
                    "reference" => $url_encounter,
                ]
            );
        }

        $document_manifest = $doc_reference->loadRefDocumentManifest();
        if ($document_manifest->_id) {
            $url_document_manifest = CFHIRController::getUrl(
                "fhir_read",
                [
                    'resource'    => "DocumentManifest",
                    'resource_id' => $document_manifest->_id,
                ]
            );

            if ($url_document_manifest) {
                $context_data["related"] = CFHIRDataTypeReference::build(
                    [
                        "reference" => new CFHIRDataTypeString($url_document_manifest),
                    ]
                );
            }
        }
        //$this->context = CFHIRDataTypeBackboneElement::build($context_data);
    }

    /**
     * @inheritdoc
     */
    public function build(CMbObject $object, CFHIREvent $event)
    {
        parent::build($object, $event);

        if (!$object instanceof CCompteRendu && !$object instanceof CFile) {
            throw  new CFHIRException("Object is not document item");
        }

        // On prend le CDocumentReference le plus récent
        $document_reference = new CDocumentReference();
        $document_reference->setObject($object);
        $document_reference->setActor($event->_receiver);
        /** @var CDocumentItem $version */
        $version                      = $object->_version;
        $document_reference->uniqueID = "urn:uuid:" . CMbOID::getOIDFromClass(
                $object
            ) . "." . $object->_id . "." . $version;
        $document_reference->loadMatchingObject();

        if (!$document_reference->_id) {
            throw new CFHIRException("Impossible to get DocumentReference");
        }

        $this->id = CFHIR::generateUUID();

        // Ajout des <contained>
        /** @var CDocumentItem $author */
        $author          = $object->loadRefAuthor();
        $this->contained = $this->addContained($author, new CFHIRResourcePractitioner());
        $group           = $this->getGroup($object);
        if ($group) {
            // todo ref avec resource
            $this->contained = array_merge($this->contained, $this->buildContained($group))[0];
        }

        $object->loadTargetObject();
        $patient = new CPatient();
        if ($object->_ref_object instanceof CPatient) {
            $patient = $object->_ref_object;
        } elseif ($object->_ref_object instanceof CSejour || $object->_ref_object instanceof CConsultation
            || $object->_ref_object instanceof COperation
        ) {
            $patient = $object->_ref_object->loadRefPatient();
        }

        $this->contained = $this->addContained($patient, new CFHIRResourcePatient());

        // XDS : DocumentEntry.uniqueId
        $version                = $object->_version;
        $this->masterIdentifier = $this->addMasterIdentifier(
            "urn:oid:" . CMbOID::getOIDFromClass($object) . "." . $object->_id . "." . $version,
            self::SYSTEM
        );

        // XDS : DocumentEntry.entryUUID
        //$this->identifier = $this->addIdentifier("urn:uuid:".CFHIR::generateUUID());

        // XDS : DocumentEntry.status
        $this->status = $this->addStatus($object->annule ? "superseded" : "current");

        // XDS : No correspondance
        //$this->docStatus = $this->addDocStatus();

        // XDS : DocumentEntry.type
        if ($object->type_doc_dmp) {
            $type          = explode("^", $object->type_doc_dmp);
            $xds_value_set = new CXDSValueSet();

            $xds_value_set = [
                "codeSystem"  => "http://loinc.org",
                "code"        => "34133-9",
                "displayName" => "Summary of Episode Note",
            ];
            //$this->type = $this->addTypeCodeCoding($xds_value_set->getTypeCode(CMbArray::get($type, 1)));
            $this->type = $this->addTypeCodeCoding($xds_value_set);
        }

        // XDS : DocumentEntry.class
        $xds_value_set = new CXDSValueSet();
        $values        = $xds_value_set->getClassCode();

        /*$values_data = array(
          "codeSystem"  => CMbArray::get($values, 1),
          "code"        => CMbArray::get($values, 0),
          "displayName" => CMbArray::get($values, 2)
        );*/
        $values_data = [
            "codeSystem"  => "urn:oid:1.3.6.1.4.1.19376.1.2.6.1",
            "code"        => "REPORTS",
            "displayName" => "Reports",
        ];
        $this->class = $this->addClassCode($values_data);

        // XDS : DocumentEntry.patientId
        $this->subject = $this->addSubject($patient);

        // XDS : DocumentEntry.submissionTime
        // TODO : Il ne faut pas le mettre en création
        //$this->created = $this->addCreated($object instanceof CCompteRendu ? $object->creation_date : $object->file_date);

        // XDS : DocumentEntry.submissionTime
        $this->indexed = $this->addIndexed(
            $object instanceof CCompteRendu ? $object->creation_date : $object->file_date
        );

        // XDS : DocumentEntry.author
        $this->author = $this->addAuthor($author);

        // XDS : DocumentEntry.legalAuthenticator
        $this->authenticator = $this->addAuthenticator($author);

        // XDS : DocumentEntry.description
        $this->description = $this->addDescription($object instanceof CCompteRendu ? $object->nom : $object->file_name);

        // XDS : DocumentEntry.confidentialityCode
        $xds_value_set = new CXDSValueSet();
        //$this->securityLabel = $this->addSecurityLabel($xds_value_set::getConfidentialityCode());
        $values_data         = [
            "codeSystem"  => "http://hl7.org/fhir/v3/Confidentiality",
            "code"        => "N",
            "displayName" => "Normal",
        ];
        $this->securityLabel = $this->addSecurityLabel($values_data);

        // XDS : DocumentEntry.mimeType DocumentEntry.languageCode DocumentEntry.URI DocumentEntry.size DocumentEntry.title
        $this->content = $this->addAttachment($object);

        // XDS : DocumentEntry.eventCodeList DocumentEntry.serviceStartTime DocumentEntry.serviceStopTime
        // DocumentEntry.healthcareFacilityTypeCode XDS : DocumentEntry.practiceSettingCode DocumentEntry.sourcePatientInfo
        // DocumentEntry.sourcePatientId DocumentEntry.referenceList
        $this->context = $this->addContext(
            $object,
            $object->_ref_object instanceof CSejour ? $object->_ref_object : null
        );

        // XDS : Association
        // Permet le remplacement d'un document
        if ($object instanceof CCompteRendu && $document_reference->parent_id) {
            $this->relatesTo = $this->addAssociation($object, $document_reference, $event->_receiver);
        }
    }

    /**
     * Get groups from Model document
     *
     * @param CDocumentItem $object object
     *
     * @return CGroups|null
     */
    public function getGroup(CDocumentItem $object)
    {
        $group_id = null;

        if ($object instanceof CCompteRendu) {
            $group_id = $object->group_id;
        } elseif ($object instanceof CFile) {
            $group_id = $object->loadRefAuthor()->loadRefFunction()->group_id;
        }

        if (!$group_id) {
            $target = $object->loadTargetObject();

            if ($target instanceof CSejour) {
                $group_id = $target->group_id;
            }
        }

        if (!$group_id) {
            return null;
        }

        $group = new CGroups();
        $group->load($group_id);

        if (!$group || !$group->_id) {
            return null;
        }

        return $group;
    }
}
