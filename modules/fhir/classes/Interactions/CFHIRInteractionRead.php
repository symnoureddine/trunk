<?php
/**
 * @package Mediboard\Fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir\Interactions;

use Ox\Interop\Fhir\Exception\CFHIRExceptionNotFound;
use Ox\Interop\Fhir\Resources\CFHIRResource;
use Ox\Interop\Fhir\Response\CFHIRResponse;
use Ox\Mediboard\Patients\CPatient;

/**
 * The read interaction accesses the current contents of a resource
 */
class CFHIRInteractionRead extends CFHIRInteraction
{
    /** @var string Interaction name */
    public const NAME = "Read";

    /**
     * @inheritdoc
     *
     * @param CPatient $result
     *
     * @throws CFHIRExceptionNotFound
     */
    public function handleResult(CFHIRResource $resource, $result): CFHIRResponse
    {
        if (!$result || !$result->_id) {
            throw new CFHIRExceptionNotFound(
                "Could not find " . $resource->getResourceType() . " #$resource->_search_id"
            );
        }

        $resource->mapFrom($result);

        return new CFHIRResponse($resource);
    }

    /**
     * Build the query
     *
     * @return array
     */
    public function buildQuery(?array $data = array()): array
    {
        $id = $version_id = null;

        $params = [];

        foreach ($this->parameters as $_param) {
            if ($_param["field"] === "_id") {
                $id = urlencode($_param["value"]);
            } elseif ($_param["field"] === "version_id") {
                $version_id = urlencode($_param["value"]);
            } else {
                $params[] = $_param["field"] . "=" . urlencode($_param["value"]);
            }
        }

        $params[] = "_format=" . urlencode($this->format);

        return [
            "event" => $this->resourceType . "/" . $id . ($version_id ? "/_history/$version_id" : null),
            "data"  => implode("&", $params),
        ];
    }
}
