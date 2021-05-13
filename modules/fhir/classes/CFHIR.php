<?php
/**
 * @package Mediboard\Fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir;

use DateTime;
use DateTimeZone;
use Exception;
use JsonSerializable;
use Ox\Core\Cache;
use Ox\Core\CAppUI;
use Ox\Core\CMbArray;
use Ox\Core\CMbSecurity;
use Ox\Core\CMbString;
use Ox\Interop\Eai\CExchangeDataFormat;
use Ox\Interop\Eai\CInteropNorm;
use Ox\Interop\Fhir\Datatypes\CFHIRDataType;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeComplex;
use Ox\Interop\Fhir\Exception\CFHIRException;
use Ox\Interop\Fhir\Resources\CFHIRResource;
use Ox\Interop\Fhir\Resources\CFHIRResourceAppointment;
use Ox\Interop\Fhir\Resources\CFHIRResourceBinary;
use Ox\Interop\Fhir\Resources\CFHIRResourceCapabilityStatement;
use Ox\Interop\Fhir\Resources\CFHIRResourceDocumentManifest;
use Ox\Interop\Fhir\Resources\CFHIRResourceDocumentReference;
use Ox\Interop\Fhir\Resources\CFHIRResourceEncounter;
use Ox\Interop\Fhir\Resources\CFHIRResourcePatient;
use Ox\Interop\Fhir\Resources\CFHIRResourcePractitioner;
use Ox\Interop\Fhir\Resources\CFHIRResourcePractitionerRole;
use Ox\Interop\Fhir\Resources\CFHIRResourceSchedule;
use Ox\Interop\Fhir\Resources\CFHIRResourceSlot;
use Ox\Interop\Fhir\Resources\CFHIRResourceStructureDefinition;
use Ox\Mediboard\Etablissement\CGroups;
use Ox\Mediboard\Files\CDocumentItem;
use Ox\Mediboard\Sante400\CIdSante400;

/**
 * FHIR interop norm class
 */
class CFHIR extends CInteropNorm
{
    public const CONTENT_TYPE_XML  = "application/fhir+xml";
    public const CONTENT_TYPE_JSON = "application/fhir+json";

    public const BLINK1_UNKNOW  = "fhir unknown";
    public const BLINK1_ERROR   = "fhir error";
    public const BLINK1_WARNING = "fhir warning";
    public const BLINK1_OK      = "fhir ok";

    /** @var string[] Versions */
    public static $versions = [
        "0.0",
        "1.0",
        "3.0",
        "4.0",
    ];

    /** @var string[] Relation map */
    public static $relation_map = [
        "first"    => "fast-backward",
        "previous" => "step-backward",
        //"self"     => "circle-o",
        "next"     => "step-forward",
        "last"     => "fast-forward",
    ];

    /** @var string[] Interaction resource patient */
    public static $resource_patient = [
        "read",
        "search",
        "create",
        "update",
        "delete",
        "history",
    ];

    /** @var string[] Interaction resource encounter */
    public static $resource_encounter = [
        "read",
        "search",
    ];

    /** @var string[] Interaction resource appointment */
    public static $resource_fr_appointment = [
        "read",
        "search",
        "create",
        "update",
        "delete",
    ];

    /** @var string[] Interaction resource practitioner */
    public static $resource_practitioner = [
        "read",
        "search",
        "create",
        "update",
        "delete",
    ];

    /** @var string[] Interaction resource practitioner role */
    public static $resource_practitioner_role = [
        "read",
        "search",
        "create",
        "update",
        "delete",
    ];

    /** @var string[] Interaction resource document reference */
    public static $resource_document_reference = [
        "read",
        "search",
        "create",
    ];

    /** @var string[] Interaction resource document manifest */
    public static $resource_document_manifest = [
        "read",
        "search",
    ];

    /** @var string[] Interaction resource structure definition */
    public static $resource_structure_definition = [
        "read",
        "search",
    ];

    /** @var string[] Interaction resource binary */
    public static $resource_binary = [
        "read",
        "search",
    ];

    /** @var string[] Interaction resource capability statement */
    public static $resource_capability_statement = [
        "capabilities",
    ];

    /** @var string[] Interaction resource fr schedule */
    public static $resource_fr_schedule = [
        "read",
        "create",
        "search",
    ];

    /** @var string[] Interaction resource fr schedule */
    public static $resource_fr_slot = [
        "read",
        "create",
        "search",
    ];

    /**
     * @var array Events
     */
    public static $evenements = [
        "create"       => "CFHIRInteractionCreate",
        "update"       => "CFHIRInteractionUpdate",
        "delete"       => "CFHIRInteractionDelete",
        "read"         => "CFHIRInteractionRead",
        "search"       => "CFHIRInteractionSearch",
        "history"      => "CFHIRInteractionHistory",
        "capabilities" => "CFHIRInteractionCapabilities",
    ];

    /**
     * @see parent::__construct
     */
    public function __construct()
    {
        $this->name = "FHIR";
        $this->type = "FHIR";

        $this->_categories = self::getResources();

        parent::__construct();
    }

    /**
     * Get content types
     *
     * @return array CONTENT_TYPE
     */
    public static function getContentTypes(): array
    {
        return [
            static::CONTENT_TYPE_JSON,
            static::CONTENT_TYPE_XML,
        ];
    }

    /** Get resources
     *
     * @return array Resources
     */
    public static function getResources(): array
    {
        return [
            CFHIRResourcePatient::RESOURCE_TYPE             => self::$resource_patient,
            CFHIRResourceEncounter::RESOURCE_TYPE           => self::$resource_encounter,
            CFHIRResourceAppointment::RESOURCE_TYPE         => self::$resource_fr_appointment,
            CFHIRResourcePractitioner::RESOURCE_TYPE        => self::$resource_practitioner,
            CFHIRResourcePractitionerRole::RESOURCE_TYPE    => self::$resource_practitioner_role,
            CFHIRResourceDocumentReference::RESOURCE_TYPE   => self::$resource_document_reference,
            CFHIRResourceDocumentManifest::RESOURCE_TYPE    => self::$resource_document_manifest,
            CFHIRResourceBinary::RESOURCE_TYPE              => self::$resource_binary,
            CFHIRResourceStructureDefinition::RESOURCE_TYPE => self::$resource_structure_definition,
            CFHIRResourceCapabilityStatement::RESOURCE_TYPE => self::$resource_capability_statement,
            CFHIRResourceSchedule::RESOURCE_TYPE            => self::$resource_fr_schedule,
            CFHIRResourceSlot::RESOURCE_TYPE                => self::$resource_fr_slot,
        ];
    }

    /**
     * @see parent::getEvenements
     */
    public function getEvenements(): ?array
    {
        return self::$evenements;
    }

    /**
     * Get object tag
     *
     * @param string $group_id Group
     *
     * @return string|null
     */
    public static function getObjectTag(?int $group_id = null): ?string
    {
        // Recherche de l'établissement
        $group = CGroups::get($group_id);
        if (!$group_id) {
            $group_id = $group->_id;
        }

        $cache = new Cache(__METHOD__, [$group_id], Cache::INNER);

        if ($cache->exists()) {
            return $cache->get();
        }

        $tag = self::getDynamicTag();

        return $cache->put(str_replace('$g', $group_id, $tag));
    }

    /**
     * Get object dynamic tag
     *
     * @return string
     */
    public static function getDynamicTag(): ?string
    {
        return CAppUI::conf("fhir tag_default");
    }

    /**
     * Get a resource class from a resource name
     *
     * @param string $resource_type Resource type (Patient, etc)
     *
     * @return CFHIRResource|null
     */
    public static function makeResource(string $resource_type): ?CFHIRResource
    {
        $class_name = "CFHIRResource$resource_type";

        if (class_exists($class_name)) {
            return new $class_name();
        }

        return null;
    }

    /**
     * Parses GET parameters, keeping repeating values inside an array
     *
     * @param string $string The query string to parse
     * @param bool   $raw    Get raw results, do not parse modifiers
     *
     * @return array
     */
    public static function parseQueryString(?string $string = null, ?bool $raw = false): array
    {
        $query  = explode('&', $string ?: $_SERVER['QUERY_STRING']);
        $params = [];

        foreach ($query as $param) {
            if (strpos($param, '=')) {
                [$name, $value] = explode('=', $param, 2);
            } else {
                $name  = $param;
                $value = null;
            }

            // Custom field name to stop query processing
            if ($name === "_fhir_stop") {
                break;
            }

            if ($raw) {
                $params[urldecode($name)][] = urldecode($value);
            } else {
                $field = CFHIR::parseCondition(urldecode($name));

                $params[$field[0]][] = [$field[1], urldecode($value)];
            }
        }

        return $params;
    }

    /**
     * Makes a query string from an array
     *
     * @param array $query The query array
     *
     * @return string
     */
    public static function makeQueryString(?string $query): ?string
    {
        $parts = [];

        foreach ($query as $_key => $_values) {
            foreach ($_values as $_value) {
                $parts[] = urlencode($_key) . "=" . str_replace("+", "%2B", urlencode($_value));
            }
        }

        return implode("&", $parts);
    }

    /**
     *
     *
     * @param $name
     *
     * @return array
     */
    public static function parseCondition(string $name): array
    {
        if (CMbString::endsWith($name, ":exact")) {
            $pos = strrpos($name, ":exact");

            return [substr($name, 0, $pos), "exact"];
        }

        if (CMbString::endsWith($name, ":contains")) {
            $pos = strrpos($name, ":contains");

            return [substr($name, 0, $pos), "contains"];
        }

        if (CMbString::endsWith($name, ":not")) {
            $pos = strrpos($name, ":not");

            return [substr($name, 0, $pos), "!="];
        }

        return [$name, "="];
    }

    /**
     * Filter data
     *
     * @param mixed $object
     *
     * @return array
     */
    public static function filterData($object): array
    {
        return array_filter(
            get_object_vars($object),
            function ($v, $k) {
                if ($v === null || strpos($k, '_') === 0) {
                    return false;
                }

                if (is_array($v) && count($v) === 0) {
                    return false;
                }

                if ($v instanceof CFHIRDataType && !$v instanceof CFHIRDataTypeComplex && $v->getValue() === null) {
                    return false;
                }

                return true;
            },
            ARRAY_FILTER_USE_BOTH
        );
    }

    /**
     * To JSON
     *
     * @param mixed $object
     *
     * @return mixed|null
     */
    public static function toJSON($object)
    {
        if ($object === null) {
            return null;
        }

        if (is_scalar($object)) {
            return $object;
        }

        if (is_object($object)) {
            $object = get_object_vars($object);
        }

        $result = [];

        foreach ($object as $key => $datum) {
            if ($datum instanceof JsonSerializable) {
                $result[$key] = $datum->jsonSerialize();
                continue;
            }

            if (is_array($datum)) {
                $result[$key] = array_map(["CFHIR", "toJSON"], $datum);
            }
        }

        return $result;
    }

    /**
     * Créer un UUID
     *
     * @return string
     */
    public static function generateUUID(): string
    {
        return CMbSecurity::generateUUID();
    }

    /**
     * Retourne le datetime actuelle au format UTC
     *
     * @param String $date now
     * @param bool   $z    Z
     *
     * @return string
     * @throws Exception
     */
    public static function getTimeUtc(string $date = "now", bool $z = true): string
    {
        $timezone_local = new DateTimeZone(CAppUI::conf("timezone"));
        $timezone_utc   = new DateTimeZone("UTC");
        $date           = new DateTime($date, $timezone_local);
        $date->setTimezone($timezone_utc);

        return $z ? $date->format("Y-m-d\TH:i:sP") . "Z" : $date->format("Y-m-d\TH:i:sP");
    }

    /**
     * Load idex FHIR
     *
     * @param CDocumentItem $object   object
     * @param string        $group_id Group
     *
     * @return CIdSante400
     */
    public static function loadIdex(CDocumentItem $object, ?int $group_id = null): CIdSante400
    {
        return $object->_ref_fhir_idex = CIdSante400::getMatchFor($object, self::getObjectTag($group_id));
    }

    /**
     * Get data from request method
     *
     * @param string $method method
     * @param string $input  input
     *
     * @return array|null
     */
    public static function getDataFromRequestMethod(string $method, ?string $input = null): ?array
    {
        switch ($method) {
            case "GET":
                return CFHIR::parseQueryString();
                break;
            case "POST":
                return ["data" => $input];
                break;
            default:
                return null;
        }
    }
}
