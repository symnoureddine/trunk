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
use Ox\AppFine\Server\CAppFineServer;
use Ox\Core\CAppUI;
use Ox\Core\CMbArray;
use Ox\Core\CMbObject;
use Ox\Core\Module\CModule;
use Ox\Core\CSQLDataSource;
use Ox\Core\CStoredObject;
use Ox\Interop\Eai\CDomain;
use Ox\Interop\Fhir\CFHIRXPath;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeBoolean;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeCode;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeDate;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeDateTime;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeId;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeInteger;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeString;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeAddress;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeAttachment;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeBackboneElement;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeCodeableConcept;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeCoding;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeContactPoint;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeHumanName;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeIdentifier;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeMeta;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeNarrative;
use Ox\Interop\Fhir\Event\CFHIREvent;
use Ox\Interop\Fhir\Exception\CFHIRException;
use Ox\Interop\Fhir\Exception\CFHIRExceptionBadRequest;
use Ox\Interop\Fhir\Exception\CFHIRExceptionEmptyResultSet;
use Ox\Interop\Fhir\Exception\CFHIRExceptionNotFound;
use Ox\Interop\Fhir\Interactions\CFHIRInteraction;
use Ox\Interop\Fhir\Response\CFHIRResponse;
use Ox\Interop\Fhir\Response\CFHIRResponseJSON;
use Ox\Mediboard\Mediusers\CMediusers;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\Sante400\CIdSante400;
use Ox\Mediboard\System\CUserLog;
use phpDocumentor\Reflection\Types\Parent_;

/**
 * FIHR patient resource
 */
class CFHIRResourcePatient extends CFHIRResourceDomainResource
{
    /** @var string  */
    public const RESOURCE_TYPE = "Patient";

    /** @var CFHIRDataTypeId[] */
    public $id;

    /** @var CFHIRDataTypeMeta */
    public $meta;

    /** @var CFHIRDataTypeNarrative */
    public $text;

    /** @var CFHIRDataTypeIdentifier[] */
    public $identifier;

    /** @var CFHIRDataTypeBoolean */
    public $active;

    /** @var CFHIRDataTypeHumanName[] */
    public $name;

    /** @var CFHIRDataTypeContactPoint[] */
    public $telecom;

    /** @var CFHIRDataTypeCode */
    public $gender;

    /** @var CFHIRDataTypeDate */
    public $birthDate;

    /** @var CFHIRDataTypeBoolean|CFHIRDataTypeDateTime */
    public $deceased;

    /** @var CFHIRDataTypeAddress[] */
    public $address;

    /** @var CFHIRDataTypeCodeableConcept */
    public $maritalStatus;

    /** @var CFHIRDataTypeBoolean|CFHIRDataTypeInteger */
    public $multipleBirth;

    /** @var CFHIRDataTypeAttachment[] */
    public $photo;

    /** @var CFHIRDataTypeBackBoneElement[] */
    public $contact;

    /**
     * return CPatient
     */
    public function getClass(): ?string
    {
        return CPatient::class;
    }

    /**
     * Perform a search query based on the current object data
     *
     * @param array  $data   Data to handle
     * @param string $format Format
     *
     * @return CStoredObject[]
     * @throws CFHIRExceptionBadRequest
     * @throws CFHIRExceptionEmptyResultSet
     * @throws CFHIRExceptionNotFound
     */
    public function interactionSearch(?array $data, ?string $format = null): array
    {
        // offset
        $offset = $this->getOffset($data);

        // limit
        $limit = $this->getLimit($data, $offset);

        // Identifiers
        $identifiers       = [];
        $patient_to_return = null;
        if (isset($data["identifier"]) && count($data["identifier"])) {
            foreach ($data["identifier"] as $_identifier) {
                if (strpos($_identifier[1], "urn:oid:") !== 0) {
                    continue;
                }

                [$system, $value] = explode("|", substr($_identifier[1], 8));

                if ($value) {
                    $patient_to_return = $this->findPatientByIdentifier($system, $value);
                } elseif ($system) {
                    $identifiers[] = $system;
                }
            }

            if ($patient_to_return) {
                $patient_to_return->_returned_oids = $identifiers;

                return [
                    "list"     => [$patient_to_return],
                    "total"    => 1,
                    "step"     => $limit,
                    "offset"   => $offset,
                    "paginate" => isset($data["_count"]),
                    "format"   => $format,
                ];
            }
        }

        return parent::interactionSearch($data, $format);
    }

    /**
     * @param array       $data
     * @param string      $limit
     *
     * @param string|null $offset
     *
     * @return array
     * @throws Exception
     */
    protected function specificSearch(array $data, string $limit, ?string $offset = null): array
    {
        /** @var CPatient $object */
        $object = $this->getObject();
        $this->getWhere($data, $object, $where, $ljoin);
        $list  = $object->loadList($where, "patient_id", $limit, null, $ljoin);
        $total = $object->countList($where, null, $ljoin);

        return [$list, $total];
    }

    /**
     * Perform a search query based on the current object data
     *
     * @param array  $data   Data to handle
     * @param string $format Format
     *
     * @return CFHIRResponse
     * @throws CFHIRExceptionBadRequest
     * @throws CFHIRExceptionEmptyResultSet
     * @throws CFHIRExceptionNotFound
     */
    public function interaction_search_appFine($data, $format = null)
    {
        /** @var CPatient $object */
        $object = $this->getObject();

        $limit = isset($data["_count"]) ? (int)$data["_count"][0][1] : null;
        if ($limit) {
            // Limite imposée à 50
            $limit = min($limit, 50);
        } else {
            $limit = 10;
        }

        $offset = isset($data["_offset"]) ? (int)$data["_offset"][0][1] : null;

        // _id
        if (isset($data["_id"]) && count($data["_id"])) {
            $object->load($data["_id"][0][1]);

            $object = $this->outWithPerm($object);

            if ($object) {
                /*return [
                    $object,
                ];*/
            }

            throw new CFHIRExceptionNotFound("Could not find patient #" . $data["_id"][0][1]);
        }

        $where = [];
        $ljoin = [];

        $this->getWhereAppFine($data, $object, $where, $ljoin);

        $list  = $object->loadList($where, "patient_id", $offset ? "$offset, $limit" : $limit, null, $ljoin);
        $total = $object->countList($where, null, $ljoin);

        $result = [
            "list"     => $list,
            "total"    => $total,
            "step"     => $limit,
            "offset"   => $offset,
            "paginate" => isset($data["_count"]),
            "format"   => $format,
        ];

        $interaction = CFHIRInteraction::make("Search");

        return $interaction->handleResult($this, $result);
    }

    /**
     * Perform a history query based on the current object data
     *
     * @param mixed $data Data to handle
     *
     * @return CStoredObject
     * @throws CFHIRExceptionNotFound
     */
    public function interaction_history($data)
    {
        /** @var CPatient $patient */
        $patient = $this->getObject();

        // _id
        if (isset($data["_id"]) && count($data["_id"])) {
            $id = $data["_id"][0][1];
            $patient->load($id);
            $this->_search_id = $id;

            $patient->loadListByHistory();

            return $patient;
        }

        throw new CFHIRExceptionNotFound();
    }

    /**
     * Perform a search query based on the current object data
     *
     * @param array $data Data to handle
     *
     * @return CStoredObject
     * @throws CFHIRException
     */
    public function operation_ihe_pix($data)
    {
        $sourceIdentifier = $data['sourceIdentifier'];

        if (count($sourceIdentifier) !== 1) {
            throw new CFHIRException("Invalid number of source identifiers");
        }

        $sourceIdentifier = $sourceIdentifier[0][1];

        if (strpos($sourceIdentifier, "urn:oid:") === 0) {
            [$system, $value] = explode("|", substr($sourceIdentifier, 8));

            $patient = $this->findPatientByIdentifier($system, $value);

            if (isset($data["targetSystem"]) && count($data["targetSystem"])) {
                $identifiers = [];

                foreach ($data["targetSystem"] as $_system) {
                    if (strpos($_system[1], "urn:oid:") !== 0) {
                        continue;
                    }

                    $array  = explode("|", str_replace("urn:oid:", "", $_system[1]));
                    $system = CMbArray::get($array, 0);

                    $identifiers[] = $system;
                }

                $patient->_returned_oids = $identifiers;
            }

            return $patient;
        }

        throw new CFHIRExceptionEmptyResultSet();
    }

    /**
     * Finds a patient by his identifier
     *
     * @param string $system Identifying system (OID)
     * @param string $value  Identifier value
     *
     * @return CMbObject
     * @throws CFHIRExceptionBadRequest
     * @throws CFHIRExceptionEmptyResultSet
     */
    public function findPatientByIdentifier($system, $value)
    {
        $domain      = new CDomain();
        $domain->OID = $system;
        $domain->loadMatchingObject();

        if (!$domain->_id) {
            throw new CFHIRExceptionBadRequest("sourceIdentifier Assigning Authority not found");
        }

        $idex = CIdSante400::getMatch("CPatient", $domain->tag, $value);

        if (!$idex->_id) {
            throw new CFHIRExceptionEmptyResultSet("Unknown Patient identified by '$system|$value'");
        }

        return $idex->loadTargetObject();
    }

    /**
     * @inheritdoc
     *
     * @param CPatient $object
     */
    public function mapFrom(CMbObject $object): void
    {
        parent::mapFrom($object);

        if (CModule::getActive("appFine")) {
            $this->active = new CFHIRDataTypeString(
                CAppFineServer::checkPatientUserIsActive($object->_id) ? "true" : "false"
            );
        } else {
            $this->active = new CFHIRDataTypeString("true");
        }

        $this->address[] = CFHIRDataTypeAddress::build(
            [
                "use" => "home",
                "type"       => "postal",
                "line"       => preg_split('/[\r\n]+/', $object->adresse),
                "city"       => $object->ville,
                "postalCode" => $object->cp,
            ]
        );

        if ($object->tel) {
            $this->contact[] = CFHIRDataTypeContactPoint::build(
                [
                    "system" => "phone",
                    "value"  => $object->tel,
                ]
            );
        }

        if ($object->tel2) {
            $this->contact[] = CFHIRDataTypeContactPoint::build(
                [
                    "system" => "phone",
                    "value"  => $object->tel2,
                ]
            );
        }

        if ($object->tel_autre) {
            $this->contact[] = CFHIRDataTypeContactPoint::build(
                [
                    "system" => "phone",
                    "value"  => $object->tel_autre,
                ]
            );
        }

        if ($object->email) {
            $this->contact[] = CFHIRDataTypeContactPoint::build(
                [
                    "system" => "email",
                    "value"  => $object->email,
                ]
            );
        }
    }

    public function mapFromLight(CMbObject $object): void
    {
        parent::mapFromLight($object);
        /** @var CPatient $object */
        $object->loadIPP();
        $domains = CDomain::loadDomainIdentifiers($object);
        foreach ($domains as $_domain) {
            if (empty($object->_returned_oids) || in_array($_domain->OID, $object->_returned_oids)) {
                $this->identifier[] = CFHIRDataTypeIdentifier::build(
                    [
                        "system" => "urn:oid:$_domain->OID",
                        "value"  => $_domain->_identifier->id400,
                    ]
                );
            }
        }

        // "system" => new CFHIRDataTypeString("urn:oid:$master_domain->OID"),
        // "value"  => new CFHIRDataTypeString($object->_IPP),

        // Gender
        $this->gender = new CFHIRDataTypeCode($this->formatGender($object->sexe));

        // birth date
        $this->birthDate = new CFHIRDataTypeDate($object->naissance);

        // name
        $text       = "$object->nom" .
            ($object->nom_jeune_fille && $object->nom_jeune_fille != $object->nom ? " ($object->nom_jeune_fille)" : "")
            . " $object->prenom";
        $this->name = $this->addName(
            $object->nom,
            [$object->prenom, $object->_prenom_2, $object->_prenom_3, $object->_prenom_4],
            'usual',
            $text
        );
    }

    /**
     * Makes a LIKE clause
     *
     * @param CSQLDataSource $ds                Datasource
     * @param string         $value             Value to find
     * @param bool           $include_modifiers Include modifiers in the query
     *
     * @return string
     */
    private function like(CSQLDataSource $ds, $value, $include_modifiers = true)
    {
        $value[1] = str_replace("*", "%", $value[1]);

        // Any patients with a name containing a given part with "$nom" at the start of the name
        //  Any patients with a name with a given part that is exactly "Eve".
        if ($value[0] === "exact") {
            $value[1] = "$value[1]";
        } // Any patients with a name with a given part containing "eve" at any position
        elseif ($value[0] === "contains") {
            $value[1] = "%$value[1]%";
        } else {
            $value[1] = "$value[1]%";
        }

        $modifier = ($value[0] === "!=" ? "NOT " : "");

        return ($include_modifiers ? $modifier : "") .
            $ds->prepare("LIKE %", preg_replace("/[^\w%]+/", "_", $value[1]));
    }

    /**
     * Makes a WHERE clause from data
     *
     * @param array    $data    Input data
     * @param CPatient $patient Patient to make the query for
     * @param array    $where   WHERE clause
     * @param array    $ljoin   LEFT JOIN clause
     *
     * @return array
     */
    private function getWhere($data, CPatient $patient, &$where, &$ljoin)
    {
        $ds = $patient->getDS();

        if (CAppUI::isCabinet()) {
            $function_id          = CMediusers::get()->function_id;
            $where["function_id"] = "= '$function_id'";
        }

        if (isset($data["family"]) && count($data["family"])) {
            $nom = $data["family"][0];

            $whereOr   = [];
            $whereOr[] = "nom " . $this->like($ds, $nom, false);
            $whereOr[] = "nom_jeune_fille " . $this->like($ds, $nom, false);

            $where[] = ($nom[0] === "!=" ? "NOT" : "") . "(" . implode(" OR ", $whereOr) . ")";
        }

        if (isset($data["given"]) && count($data["given"])) {
            $given = $data["given"];

            $prenom          = $given[0];
            $where["prenom"] = $this->like($ds, $prenom);

            if (isset($given[1])) {
                $prenom  = $given[1];
                $where[] = 'prenoms ' . $this->like($ds, $prenom);
            }
            if (isset($given[2])) {
                $prenom  = $given[2];
                $where[] = 'prenoms ' . $this->like($ds, $prenom);
            }
            if (isset($given[3])) {
                $prenom  = $given[3];
                $where[] = 'prenoms ' . $this->like($ds, $prenom);
            }
        }

        // Birthdate
        $this->addWhereFromQuery($ds, $data, "birthdate", $where, "naissance");

        // Gender
        if (isset($data["gender"]) && count($data["gender"])) {
            $query_item    = $data["gender"][0];
            $where["sexe"] = $ds->prepare("$query_item[0] ?", $query_item[1] === "female" ? "f" : "m");
        }

        // City
        $this->addWhereFromQuery($ds, $data, "address-city", $where, "ville");

        // Postal code
        $this->addWhereFromQuery($ds, $data, "address-postalcode", $where, "cp");

        // Address
        $this->addWhereFromQuery($ds, $data, "address", $where, "adresse");

        // Identifiers
        if (isset($patient->_returned_oids)) {
            $i = 1;
            foreach ($patient->_returned_oids as $_oid) {
                $domain      = new CDomain();
                $domain->OID = $_oid;

                if ($domain->OID) {
                    $domain->loadMatchingObject();
                }

                $ljoin[20 + $i] = "id_sante400 AS id$i ON id$i.object_id = patients.patient_id";
                $where[]        = $ds->prepare("id$i.tag = %", $domain->tag);

                $i++;
            }
        }

        return $where;
    }

    /**
     * Makes a WHERE clause from data
     *
     * @param array    $data    Input data
     * @param CPatient $patient Patient to make the query for
     * @param array    $where   WHERE clause
     * @param array    $ljoin   LEFT JOIN clause
     *
     * @return array
     */
    private function getWhereAppFine($data, CPatient $patient, &$where, &$ljoin)
    {
        $ds = $patient->getDS();

        // Last name
        $this->addWhereFromQuery($ds, $data, "family", $where, "nom");

        // First name
        $this->addWhereFromQuery($ds, $data, "given", $where, "prenom");

        // Birthdate
        $this->addWhereFromQuery($ds, $data, "birthdate", $where, "naissance");

        // user_name jointure avec user
        $this->addWhereFromQuery($ds, $data, "email", $where, "users.user_username");

        $ljoin["patient_user"] = " patients.patient_id = patient_user.patient_id";
        $ljoin["users"]        = " patient_user.user_id = users.user_id";

        return $where;
    }

    static function getPatientsFromXML(DOMDocument $dom)
    {
        $xpath = new CFHIRXPath($dom);

        $patients = [];
        switch ($dom->documentElement->nodeName) {
            case "Patient":
                $patient                                 = $dom->documentElement;
                $mbPatient                               = new CPatient();
                $mbPatient                               = self::mapPatientFromXML($mbPatient, $patient);
                $patients[$mbPatient->_fhir_resource_id] = $mbPatient;
                break;
            case "Bundle":
                $entries = $xpath->query("//fhir:entry");
                foreach ($entries as $_entry) {
                    $patient = $xpath->queryUniqueNode("fhir:resource/fhir:Patient", $_entry);

                    $mbPatient                               = new CPatient();
                    $mbPatient                               = self::mapPatientFromXML($mbPatient, $patient);
                    $patients[$mbPatient->_fhir_resource_id] = $mbPatient;
                }
                break;
            default:
        }

        return $patients;
    }

    /**
     * Get node patient in response for external url
     *
     * @param string $response response
     *
     * @return DOMNode
     * @throws CFHIRException
     */
    static function getNodePatient($response)
    {
        // Réponse en JSON
        if (strpos($response, "<") === false) {
            $response = CFHIRResponseJSON::toXML($response)->saveXML();
        }

        $dom = new DOMDocument();
        $dom->loadXML($response);
        $patient_node = $dom->documentElement;
        if (!$patient_node || $patient_node->nodeName !== "Patient") {
            throw  new CFHIRException(
                "Impossible to retrieve patient with external url provide in DocumentReference.subject"
            );
        }

        return $patient_node;
    }

    /**
     * Check if patient is known in MB with identifiers
     *
     * @param CPatient $patient patient
     *
     * @return CPatient
     * @throws CFHIRException
     */
    static function patientIsKnow(CPatient $patient)
    {
        $identifiers = $mb_patient = null;
        foreach ($patient->_identifiers as $_identifier) {
            if ($mb_patient) {
                continue;
            }
            $oid   = str_replace("urn:oid:", "", CMbArray::get($_identifier, "system"));
            $value = CMbArray::get($_identifier, "value");

            if (!$value || !$oid) {
                continue;
            }

            // TODO : Ajouter dans le loadMatching actor_id, actor_class pour savoir quel expéditeur nous envoie la requete et récupérer le bon domain
            $domain      = new CDomain();
            $domain->OID = $oid;
            $domain->loadMatchingObject();

            if (!$domain->_id) {
                continue;
            }

            $idex               = new CIdSante400();
            $idex->object_class = "CPatient";
            $idex->id400        = $value;
            $idex->tag          = $domain->tag;
            $idex->loadMatchingObject();

            // Patient retrouvé par son IPP
            if ($idex->_id) {
                $mb_patient = $idex->loadTargetObject();

                if ($mb_patient && $mb_patient->_id && $mb_patient->_class == "CPatient") {
                    /** @var CPatient $mb_patient */
                    $mb_patient->loadIPP();

                    return $mb_patient;
                }
            }

            $identifiers = $identifiers . "$oid|$value ,";
        }

        // Recherche du patient par nom/prenom/naissance
        if (!$mb_patient) {
            $patient_found            = new CPatient();
            $patient_found->nom       = $patient->nom;
            $patient_found->prenom    = $patient->prenom;
            $patient_found->naissance = $patient->naissance;

            $count_patients = $patient_found->countMatchingList();

            if ($count_patients == 0 || $count_patients > 1) {
                throw new CFHIRException(
                    "Patient '$patient_found->nom $patient_found->prenom' born '$patient_found->naissance' matchs with any or multiple patients."
                );
            }

            $patient_found->loadMatchingObject();

            if ($patient_found->_id) {
                return $patient_found;
            }
        }

        $identifiers = rtrim($identifiers, ",");

        throw new CFHIRException(
            "DocumentReference.subject Error : Patient not found with list identifiers : $identifiers and with first name/last name/birth date."
        );
    }

    /**
     * @inheritdoc
     *
     * @param CPatient $object
     *
     * @throws \Exception
     */
    static function mapPatientFromXML(CPatient $object, DOMNode $node)
    {
        $xpath = new CFHIRXPath($node->ownerDocument);

        $object->_fhir_resource_id = $xpath->getAttributeValue("fhir:id", $node);

        $name   = $xpath->queryUniqueNode("fhir:name", $node);
        $family = $xpath->getAttributeValue("fhir:family", $name);

        $givens_node = $xpath->query("fhir:given", $name);
        $givens      = [];
        foreach ($givens_node as $_given) {
            $givens[] = $xpath->getValueAttributNode($_given, "value");
        }

        $gender = $xpath->getAttributeValue("fhir:gender", $node) === "female" ? "f" : "m";

        $birthDate = $xpath->getAttributeValue("fhir:birthDate", $node);

        $telecoms = $xpath->query("fhir:telecom", $node);
        $phone    = null;
        $email    = null;
        foreach ($telecoms as $_telecom) {
            switch ($xpath->getAttributeValue("fhir:system", $_telecom)) {
                case "phone":
                    $phone = $xpath->getAttributeValue("fhir:value", $_telecom);
                    break;
                case "email":
                    $email = $xpath->getAttributeValue("fhir:value", $_telecom);
                    break;
                default:
            }
        }

        $address   = $address_line = $postalCode = $city = null;
        $addresses = $xpath->query("fhir:address", $node);
        $address   = null;
        if ($addresses->length >= 1) {
            $address = $addresses->item(0);
        }
        if ($address) {
            $address_lines = $xpath->query("fhir:line", $address);

            $address_line = "";
            /** @var DOMElement $_address_line */
            foreach ($address_lines as $_address_line) {
                $address_line = $address_line . " " . $_address_line->getAttribute("value");
            }

            $postalCode = $xpath->getAttributeValue("fhir:postalCode", $address);
            $city       = $xpath->getAttributeValue("fhir:city", $address);
        }

        $identifiers         = $xpath->query("fhir:identifier", $node);
        $patient_identifiers = [];

        $master_domain = CDomain::getMasterDomain("CPatient");
        foreach ($identifiers as $_identifier) {
            $system = $xpath->getAttributeValue("fhir:system", $_identifier);
            $value  = $xpath->getAttributeValue("fhir:value", $_identifier);

            $patient_identifiers[] = [
                "system" => $system,
                "value"  => $value,
            ];

            // IPP
            // Suppression du urn:oid: dans le $system si c'est présent
            $system = str_replace("urn:oid:", "", $system);
            if ($master_domain->OID == $system) {
                $object->_IPP = $value;
            }
        }

        $object->nom          = $family;
        $object->prenom       = CMbArray::get($givens, 0);
        $object->prenoms      = trim(implode(' ', [CMbArray::get($givens, 1), CMbArray::get($givens, 2), CMbArray::get($givens, 3)]));
        $object->sexe         = $gender;
        $object->naissance    = $birthDate;
        $object->tel          = $phone;
        $object->email        = $email;
        $object->adresse      = $address_line ?: null;
        $object->cp           = $postalCode ?: null;
        $object->ville        = $city ?: null;
        $object->_identifiers = $patient_identifiers;
        $object->civilite     = "guess";

        $object->updateFormFields();

        return $object;
    }

    /**
     * Check if patient is active on AppFine
     *
     * @param CPatient $object
     * @param DOMNode  $node
     *
     * @return array
     * @throws \Exception
     */
    static function getPatients(DOMDocument $dom)
    {
        $xpath = new CFHIRXPath($dom);

        switch ($dom->documentElement->nodeName) {
            case "Patient":
                return self::getActiveField($dom->documentElement);
                break;
            case "Bundle":
                $result = [];

                $entries = $xpath->query("//fhir:entry");
                foreach ($entries as $_entry) {
                    $patient        = $xpath->queryUniqueNode("fhir:resource/fhir:Patient", $_entry);
                    $result_patient = self::getActiveField($patient);

                    if (!CMbArray::get($result_patient, "active")) {
                        return $result_patient;
                    }

                    $result = $result_patient;
                }
                break;
            default:
        }

        return $result;
    }

    /**
     * Get active field in response FHIR
     *
     * @param DOMNode $node node
     *
     * @return string
     */
    static function getActiveField(DOMNode $node)
    {
        $xpath = new CFHIRXPath($node->ownerDocument);

        $id     = $xpath->getAttributeValue("fhir:id", $node);
        $active = $xpath->getAttributeValue("fhir:active", $node);

        return ["patient_id" => $id, "active" => $active && $active == "true" ? true : false];
    }

    /**
     * @inheritdoc
     */
    public function build(CMbObject $object, CFHIREvent $event)
    {
        parent::build($object, $event);

        if (!$object instanceof CPatient) {
            throw new CFHIRException("Object is not an practitioner");
        }

        $this->mapFrom($object);
    }
}
