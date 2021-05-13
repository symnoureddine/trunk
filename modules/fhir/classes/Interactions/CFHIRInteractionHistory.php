<?php
/**
 * @package Mediboard\Fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir\Interactions;

use Ox\Core\CMbArray;
use Ox\Interop\Fhir\CFHIR;
use Ox\Interop\Fhir\Controllers\CFHIRController;
use Ox\Interop\Fhir\Datatypes\CFHIRDataTypeUnsignedInt;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeBackboneElement;
use Ox\Interop\Fhir\Datatypes\Complex\CFHIRDataTypeComplex;
use Ox\Interop\Fhir\Exception\CFHIRExceptionNotFound;
use Ox\Interop\Fhir\Resources\CFHIRResource;
use Ox\Interop\Fhir\Resources\CFHIRResourceBundle;
use Ox\Interop\Fhir\Resources\CFHIRResourceBundleEntry;
use Ox\Interop\Fhir\Response\CFHIRResponse;
use Ox\Mediboard\Patients\CPatient;
use Ox\Mediboard\System\CUserLog;

/**
 * The history interaction retrieves the history of either a particular resource, all resources of a given type, or all
 * resources supported by the system
 */
class CFHIRInteractionHistory extends CFHIRInteraction
{
    /** @var string Interaction name */
    public const NAME = "_history";

    /**
     * @inheritdoc
     */
    public function handleResult(CFHIRResource $resource, $result): CFHIRResponse
    {
        /** @var CPatient $patient */
        $patient = $result;
        if (!$result || !$result->_id) {
            throw new CFHIRExceptionNotFound(
                "Could not find " . $resource->getResourceType() . " #$resource->_search_id"
            );
        }

        if (!$patient->_history) {
            throw new CFHIRExceptionNotFound("No history " . $resource->getResourceType() . " #$patient->_id");
        }

        $bundle        = new CFHIRResourceBundle();
        $bundle->total = new CFHIRDataTypeUnsignedInt(count($patient->_history));
        $bundle->type  = "history";

        $parts = explode("?", urldecode(CMbArray::get($_SERVER, "REQUEST_URI")), 2);

        $params = null;
        if (count($parts) > 1) {
            $params = $parts[1];
        }

        $root = CFHIRController::getUrl(
            "fhir_search",
            [
                'resource' => $resource->getResourceType(),
            ]
        );

        $url           = $root . ($params ? "?$params" : "");
        $parsed_params = CFHIR::parseQueryString($params, true);

        // relation = self
        $link           = new CFHIRDataTypeComplex();
        $link->relation = "self";
        $link->url      = $url;
        $bundle->link[] = $link;

        $patients = $patient->loadListByHistory();
        foreach ($patients as $_history_key => $_patient_history) {
            /** @var CPatient $_history */
            $_res = CFHIR::makeResource($resource->getResourceType());
            $_res->mapFrom($_patient_history);

            $_entry           = new CFHIRResourceBundleEntry();
            $_entry->resource = $_res;

            // Récupérer le log pour le method de la request
            $user_log = new CUserLog();
            $user_log->load($_history_key);
            switch ($user_log->type) {
                case "delete":
                    $method = "DELETE";
                    break;
                case "create":
                    $method = "POST";
                    break;
                default:
                    $method = "PUT";
                    break;
            }
            $_entry->request = CFHIRDataTypeBackboneElement::build(
                [
                    //	GET | HEAD | POST | PUT | DELETE | PATCH
                    "method" => $method,
                    "url"    => $_entry->fullUrl = CFHIRController::getUrl(
                        "fhir_history_id_version",
                        [
                            'resource'    => $_res->getResourceType(),
                            'resource_id' => $_patient_history->patient_id,
                            'version_id'  => $_history_key,
                        ]
                    ),
                ]
            );

            $bundle->entry[] = $_entry;
        }

        return new CFHIRResponse($bundle);
    }

    /**
     * Build the query
     *
     * @return array
     */
    public function buildQuery(?array $data = array()): array
    {
        $id = null;

        $params = [];
        foreach ($this->parameters as $_param) {
            if ($_param["field"] === "_id") {
                $id = "/" . urlencode($_param["value"]);
            }
        }

        $params[] = "_format=" . urlencode($this->format);

        return [
            "event" => $this->resourceType . $id . "/_history",
            "data"  => implode("&", $params),
        ];
    }
}
