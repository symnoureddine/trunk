<?php
/**
 * @package Mediboard\Fhir
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Fhir\Interactions;

use Ox\Interop\Fhir\Resources\CFHIRResource;
use Ox\Interop\Fhir\Response\CFHIRResponse;

/**
 * The capabilities interaction retrieves the information about a server's capabilitie
 */
class CFHIRInteractionCapabilities extends CFHIRInteraction
{
    /** @var string Interaction name */
    public const NAME = "Capabilities";

    /**
     * @inheritdoc
     */
    public function buildQuery(?array $data = array()): array
    {
        return [
            "data" => "metadata",
        ];
    }

    /**
     * @inheritdoc
     */
    public function handleResult(CFHIRResource $resource, $result): CFHIRResponse
    {
        return new CFHIRResponse($resource);
    }
}
